<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/db.php';
require_once 'includes/functions.php';

ensurePolymarketTables($conn);

$user_id = intval($_SESSION['user_id']);
$role_id  = intval($_SESSION['id_rol']);
$is_gm_admin = in_array($role_id, [1, 2], true);

$message = '';
$message_type = 'info';
$market_categories = ['Política', 'Militar', 'Economía', 'Noticias', 'Diplomacia'];

$user_stmt = $conn->prepare("
    SELECT u.id_usuario, u.username, u.id_pais, p.nombre_pais,
           COALESCE(am.id_alianza, ae.id_alianza) AS alliance_id,
           COALESCE(am.nombre_alianza, ae.nombre_alianza) AS alliance_name
    FROM usuarios u
    LEFT JOIN paises p ON p.id_pais = u.id_pais
    LEFT JOIN alianzas am ON p.id_alianza_militar = am.id_alianza AND am.aprobada = 1
    LEFT JOIN alianzas ae ON p.id_alianza_economica = ae.id_alianza AND ae.aprobada = 1
    WHERE u.id_usuario = :id
    LIMIT 1
");
$user_stmt->execute([':id' => $user_id]);
$current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
$user_alliance_name = $current_user['alliance_name'] ?? null;

$user_country_id = intval($current_user['id_pais'] ?? 0);
$user_ic_production = polymarket_get_country_ic_production($conn, $user_country_id);

function polymarket_parse_options($raw) {
    $lines = preg_split('/\r\n|\r|\n/', (string) $raw);
    $options = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $options[] = $line;
        }
    }
    return array_values(array_unique($options));
}

function polymarket_build_stats(array $market, array $options, array $optionTotals, array $validatedBets) {
    $total_volume = 0;
    foreach ($optionTotals as $amount) {
        $total_volume += intval($amount);
    }

    $participants = count($validatedBets);
    $distribution = [];
    $leading_option = '';
    $leading_share = 0;
    $top_amount = 0;
    $whales = ['🐋 Ballenas' => 0, '🐬 Medianos' => 0, '🐟 Pequeños' => 0];

    foreach ($options as $option) {
        $amount = intval($optionTotals[$option['id_option']] ?? 0);
        $share = $total_volume > 0 ? round(($amount / $total_volume) * 100, 1) : 0;
        $distribution[$option['id_option']] = [
            'id_option' => intval($option['id_option']),
            'titulo' => $option['titulo'],
            'descripcion' => $option['descripcion'],
            'orden' => intval($option['orden']),
            'total_ic' => $amount,
            'porcentaje' => $share,
        ];
        if ($amount >= $top_amount) {
            $top_amount = $amount;
            $leading_option = $option['titulo'];
            $leading_share = $share;
        }
    }

    foreach ($validatedBets as $bet) {
        $tier = marketWhaleTier($bet['ic_apostado'], $total_volume);
        if (!isset($whales[$tier])) {
            $whales[$tier] = 0;
        }
        $whales[$tier]++;
    }

    $consensus_index = $total_volume > 0 ? (int) round($leading_share) : 0;
    $snapshot = [
        'volume_total' => $total_volume,
        'participants' => $participants,
        'consensus_index' => $consensus_index,
        'consensus_label' => marketConsensusLabel($consensus_index),
        'trend_option' => $leading_option,
        'trend_share' => $leading_share,
        'whales' => $whales,
        'distribution' => $distribution,
    ];

    return $snapshot;
}

function polymarket_format_datetime($value) {
    if (!$value) {
        return 'Sin fecha';
    }
    return date('d/m/Y H:i', strtotime($value));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_suggestion'])) {
        $titulo = trim($_POST['suggestion_title'] ?? '');
        $descripcion = trim($_POST['suggestion_description'] ?? '');
        $categoria = trim($_POST['suggestion_category'] ?? '');
        $options = polymarket_parse_options($_POST['suggestion_options'] ?? '');

        if ($titulo === '' || $descripcion === '' || !in_array($categoria, $market_categories, true) || count($options) < 2) {
            $message = 'Debes completar el título, la descripción, la categoría y al menos dos opciones.';
            $message_type = 'warning';
        } else {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO market_suggestions
                        (id_usuario, titulo, descripcion, categoria, fecha_cierre, apuesta_minima_ic, alianzas_ven_apuestas, alianza_info_publica, cierre_silencioso_minutos, opciones_json, estado)
                    VALUES
                        (:id_usuario, :titulo, :descripcion, :categoria, NULL, 1, 0, NULL, 0, :opciones_json, 'pendiente')
                ");
                $stmt->execute([
                    ':id_usuario' => $user_id,
                    ':titulo' => $titulo,
                    ':descripcion' => $descripcion,
                    ':categoria' => $categoria,
                    ':opciones_json' => json_encode($options, JSON_UNESCAPED_UNICODE),
                ]);
                $message = 'Sugerencia enviada. Quedará pendiente de aprobación por GM/Admin.';
                $message_type = 'success';
            } catch (Throwable $e) {
                $message = 'Error al enviar la sugerencia: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }

    if (isset($_POST['place_bet'])) {
        $id_market = intval($_POST['market_id'] ?? 0);
        $id_option = intval($_POST['option_id'] ?? 0);
        $ic_apostado = intval($_POST['ic_amount'] ?? 0);

        try {
            $market_stmt = $conn->prepare("SELECT * FROM markets WHERE id_market = :id_market LIMIT 1");
            $market_stmt->execute([':id_market' => $id_market]);
            $market = $market_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$market) {
                throw new Exception('El mercado no existe.');
            }

            if (!in_array($market['estado'], ['abierto', 'silencioso'], true)) {
                throw new Exception('El mercado ya no acepta apuestas.');
            }

            if (strtotime($market['fecha_cierre']) <= time()) {
                throw new Exception('El mercado ya cerró.');
            }

            $option_stmt = $conn->prepare("SELECT * FROM market_options WHERE id_market = :id_market AND id_option = :id_option LIMIT 1");
            $option_stmt->execute([':id_market' => $id_market, ':id_option' => $id_option]);
            $option = $option_stmt->fetch(PDO::FETCH_ASSOC);
            if (!$option) {
                throw new Exception('La opción seleccionada no existe en este mercado.');
            }

            if ($ic_apostado < intval($market['apuesta_minima_ic'])) {
                throw new Exception('La apuesta es inferior al mínimo permitido.');
            }

            if ($user_country_id <= 0) {
                throw new Exception('No perteneces a ningún país, por lo que no puedes apostar.');
            }

            if ($ic_apostado > $user_ic_production) {
                throw new Exception('La apuesta (' . number_format($ic_apostado) . ' IC) excede la producción de tu país (' . number_format($user_ic_production) . ' IC).');
            }

            // Check if there is already a bet for this market and user
            $existing_bet_stmt = $conn->prepare("SELECT * FROM market_bets WHERE id_market = :id_market AND id_usuario = :id_usuario LIMIT 1");
            $existing_bet_stmt->execute([':id_market' => $id_market, ':id_usuario' => $user_id]);
            $existing_bet = $existing_bet_stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_bet) {
                if ($existing_bet['estado'] !== 'rechazada') {
                    throw new Exception('Ya tienes una apuesta registrada para este mercado.');
                }
                // Si la apuesta fue rechazada, la actualizamos
                $update_stmt = $conn->prepare("
                    UPDATE market_bets
                    SET id_option = :id_option,
                        ic_apostado = :ic_apostado,
                        estado = 'pendiente',
                        observaciones = NULL,
                        validado_por = NULL,
                        validado_en = NULL,
                        fecha_apuesta = NOW()
                    WHERE id_bet = :id_bet
                ");
                $update_stmt->execute([
                    ':id_option' => $id_option,
                    ':ic_apostado' => $ic_apostado,
                    ':id_bet' => intval($existing_bet['id_bet']),
                ]);
            } else {
                $bet_stmt = $conn->prepare("
                    INSERT INTO market_bets (id_market, id_option, id_usuario, ic_apostado, estado)
                    VALUES (:id_market, :id_option, :id_usuario, :ic_apostado, 'pendiente')
                ");
                $bet_stmt->execute([
                    ':id_market' => $id_market,
                    ':id_option' => $id_option,
                    ':id_usuario' => $user_id,
                    ':ic_apostado' => $ic_apostado,
                ]);
            }

            $message = 'Apuesta enviada. Quedará pendiente de validación por GM.';
            $message_type = 'success';
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'uniq_market_user_bet') !== false || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $message = 'Ya enviaste una apuesta para este mercado y no puede modificarse.';
            } else {
                $message = 'Error al registrar la apuesta: ' . $e->getMessage();
            }
            $message_type = 'danger';
        } catch (Throwable $e) {
            $message = 'Error al registrar la apuesta: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Transiciones de estado automáticas
$conn->exec("UPDATE markets
    SET estado = 'silencioso'
    WHERE estado = 'abierto'
      AND cierre_silencioso_minutos > 0
      AND fecha_cierre > NOW()
      AND fecha_cierre <= DATE_ADD(NOW(), INTERVAL cierre_silencioso_minutos MINUTE)");
$conn->exec("UPDATE markets
    SET estado = 'cerrado', fecha_cierre_real = COALESCE(fecha_cierre_real, NOW())
    WHERE estado IN ('abierto', 'silencioso')
      AND fecha_cierre <= NOW()");

// Snapshots para mercados silenciosos
$silent_markets = $conn->query("SELECT id_market, snapshot_silencioso_json FROM markets WHERE estado = 'silencioso'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($silent_markets as $silent_market) {
    if (!empty($silent_market['snapshot_silencioso_json'])) {
        continue;
    }

    $id_market = intval($silent_market['id_market']);
    $options_stmt = $conn->prepare("SELECT * FROM market_options WHERE id_market = :id_market ORDER BY orden, id_option");
    $options_stmt->execute([':id_market' => $id_market]);
    $options = $options_stmt->fetchAll(PDO::FETCH_ASSOC);

    $totals_stmt = $conn->prepare("
        SELECT id_option, SUM(ic_apostado) AS total_ic
        FROM market_bets
        WHERE id_market = :id_market AND estado = 'validada'
        GROUP BY id_option
    ");
    $totals_stmt->execute([':id_market' => $id_market]);
    $optionTotals = [];
    foreach ($totals_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $optionTotals[intval($row['id_option'])] = intval($row['total_ic']);
    }

    $validated_stmt = $conn->prepare("
        SELECT b.id_usuario, b.ic_apostado, b.id_option
        FROM market_bets b
        WHERE b.id_market = :id_market AND b.estado = 'validada'
        ORDER BY b.id_bet ASC
    ");
    $validated_stmt->execute([':id_market' => $id_market]);
    $validatedBets = $validated_stmt->fetchAll(PDO::FETCH_ASSOC);

    $market_row = ['estado' => 'silencioso'];
    $snapshot = polymarket_build_stats($market_row, $options, $optionTotals, $validatedBets);
    $conn->prepare("UPDATE markets SET snapshot_silencioso_json = :snap WHERE id_market = :id_market")
         ->execute([':snap' => json_encode($snapshot, JSON_UNESCAPED_UNICODE), ':id_market' => $id_market]);
}

$markets = $conn->query("
    SELECT m.*, u.username AS creador_username
    FROM markets m
    LEFT JOIN usuarios u ON u.id_usuario = m.id_usuario_creador
    ORDER BY FIELD(m.estado, 'abierto', 'silencioso', 'cerrado', 'resuelto', 'cancelado'), m.fecha_cierre ASC, m.id_market DESC
")->fetchAll(PDO::FETCH_ASSOC);

$marketsById = [];
$marketIds = [];
foreach ($markets as $market) {
    $marketsById[intval($market['id_market'])] = $market;
    $marketIds[] = intval($market['id_market']);
}

$optionsByMarket = [];
if (!empty($marketIds)) {
    $in = implode(',', array_fill(0, count($marketIds), '?'));
    $options_stmt = $conn->prepare("SELECT * FROM market_options WHERE id_market IN ($in) ORDER BY id_market, orden, id_option");
    $options_stmt->execute($marketIds);
    foreach ($options_stmt->fetchAll(PDO::FETCH_ASSOC) as $option) {
        $optionsByMarket[intval($option['id_market'])][] = $option;
    }
}

$optionTotalsByMarket = [];
$validatedBetsByMarket = [];
$userBetByMarket = [];
if (!empty($marketIds)) {
    $in = implode(',', array_fill(0, count($marketIds), '?'));
    $totals_stmt = $conn->prepare("
        SELECT id_market, id_option, SUM(ic_apostado) AS total_ic
        FROM market_bets
        WHERE estado = 'validada' AND id_market IN ($in)
        GROUP BY id_market, id_option
    ");
    $totals_stmt->execute($marketIds);
    foreach ($totals_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $optionTotalsByMarket[intval($row['id_market'])][intval($row['id_option'])] = intval($row['total_ic']);
    }

    $validated_stmt = $conn->prepare("
        SELECT b.*, o.titulo AS option_title, u.username
        FROM market_bets b
        JOIN market_options o ON o.id_option = b.id_option
        JOIN usuarios u ON u.id_usuario = b.id_usuario
        WHERE b.estado = 'validada' AND b.id_market IN ($in)
        ORDER BY b.id_market, b.id_bet ASC
    ");
    $validated_stmt->execute($marketIds);
    foreach ($validated_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $validatedBetsByMarket[intval($row['id_market'])][] = $row;
    }

    $my_bets_stmt = $conn->prepare("
        SELECT b.*, o.titulo AS option_title
        FROM market_bets b
        JOIN market_options o ON o.id_option = b.id_option
        WHERE b.id_usuario = ? AND b.id_market IN ($in)
        ORDER BY b.fecha_apuesta DESC
    ");
    $my_bets_stmt->execute(array_merge([$user_id], $marketIds));
    foreach ($my_bets_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $userBetByMarket[intval($row['id_market'])] = $row;
    }
}

$resultsByMarket = [];
if (!empty($marketIds)) {
    $in = implode(',', array_fill(0, count($marketIds), '?'));
    $results_stmt = $conn->prepare("
        SELECT r.*, u.username, o.titulo AS opcion_apostada
        FROM market_results r
        JOIN usuarios u ON u.id_usuario = r.id_usuario
        JOIN market_options o ON o.id_option = r.id_option
        WHERE r.id_market IN ($in)
        ORDER BY r.fecha_resolucion DESC, r.id_result DESC
    ");
    $results_stmt->execute($marketIds);
    foreach ($results_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $resultsByMarket[intval($row['id_market'])][] = $row;
    }
}

$reputation_stmt = $conn->prepare("SELECT * FROM market_reputation WHERE id_usuario = :id_usuario LIMIT 1");
$reputation_stmt->execute([':id_usuario' => $user_id]);
$reputation = $reputation_stmt->fetch(PDO::FETCH_ASSOC) ?: [
    'mercados_participados' => 0,
    'mercados_ganados' => 0,
    'mercados_perdidos' => 0,
    'total_ic_apostado' => 0,
    'total_ic_ganado' => 0,
    'precision_pct' => 0,
];

$globalStats = $conn->query("
    SELECT
        COUNT(*) AS total_markets,
        SUM(estado = 'abierto') AS markets_open,
        SUM(estado = 'silencioso') AS markets_silent,
        SUM(estado = 'cerrado') AS markets_closed,
        SUM(estado = 'resuelto') AS markets_resolved,
        (SELECT COUNT(*) FROM market_suggestions WHERE estado = 'pendiente') AS pending_suggestions,
        (SELECT COUNT(*) FROM market_bets WHERE estado = 'pendiente') AS pending_bets
    FROM markets
")->fetch(PDO::FETCH_ASSOC);

// Sugerencias
$suggestions_rows = $conn->query("
    SELECT s.*, u.username AS sugerente_username, m.titulo AS market_titulo
    FROM market_suggestions s
    JOIN usuarios u ON u.id_usuario = s.id_usuario
    LEFT JOIN markets m ON m.id_market = s.id_market
    ORDER BY FIELD(s.estado, 'pendiente', 'aprobada', 'rechazada'), s.fecha_creacion DESC
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Polymarket';
require_once 'includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- HERO -->
    <div class="card flag-glow mb-4" style="border-radius:20px;overflow:hidden">
        <div style="background:linear-gradient(135deg,#0d6efd,#20c997);color:#fff;padding:2rem 2.5rem;position:relative;overflow:hidden">
            <div style="position:absolute;top:-60px;right:-60px;width:220px;height:220px;background:rgba(255,255,255,.08);border-radius:50%;filter:blur(30px)"></div>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:56px;height:56px;background:rgba(255,255,255,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;backdrop-filter:blur(8px)">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div>
                        <h1 class="mb-0" style="font-size:1.6rem;font-weight:800">Polymarket</h1>
                        <p class="mb-0 mt-1" style="opacity:.8;font-size:.875rem">Mercados predictivos</p>
                    </div>
                </div>
                <div class="text-end">
                    <div class="small" style="opacity:.7">Mercados activos</div>
                    <div style="font-size:2rem;font-weight:800;line-height:1"><?= intval($globalStats['markets_open'] ?? 0) + intval($globalStats['markets_silent'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show mb-4">
        <?= $message ?><button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Abiertos</div><div class="fs-3 fw-bold text-success"><?= intval($globalStats['markets_open'] ?? 0) ?></div></div></div></div>
        <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Silenciosos</div><div class="fs-3 fw-bold text-warning"><?= intval($globalStats['markets_silent'] ?? 0) ?></div></div></div></div>
        <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Pend. sugerencias</div><div class="fs-3 fw-bold text-primary"><?= intval($globalStats['pending_suggestions'] ?? 0) ?></div></div></div></div>
        <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Pend. apuestas</div><div class="fs-3 fw-bold text-danger"><?= intval($globalStats['pending_bets'] ?? 0) ?></div></div></div></div>
    </div>

    <!-- PROPONER MERCADO Y REPUTACION -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <strong><i class="bi bi-lightbulb me-2"></i>Proponer mercado</strong>
                    <?php if ($is_gm_admin): ?>
                    <a href="gm_polymarket.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-shield-check me-1"></i>Gestión GM</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="post" class="row g-3">
                        <input type="hidden" name="submit_suggestion" value="1">
                        <div class="col-md-6">
                            <label class="form-label fw-500">Título</label>
                            <input type="text" name="suggestion_title" class="form-control" required maxlength="180">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-500">Categoría</label>
                            <select name="suggestion_category" class="form-select" required>
                                <option value="">Selecciona...</option>
                                <?php foreach ($market_categories as $category): ?>
                                <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-500">Descripción</label>
                            <textarea name="suggestion_description" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-500">Opciones del mercado</label>
                            <textarea name="suggestion_options" class="form-control" rows="5" required placeholder="Una opción por línea"></textarea>
                            <div class="form-text">Escribe una opción por línea. El módulo admite opciones ilimitadas.</div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Enviar sugerencia</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><strong><i class="bi bi-person-badge me-2"></i>Tu reputación</strong></div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-6"><div class="text-muted small">Participados</div><div class="fs-4 fw-bold"><?= intval($reputation['mercados_participados']) ?></div></div>
                        <div class="col-6"><div class="text-muted small">Ganados</div><div class="fs-4 fw-bold text-success"><?= intval($reputation['mercados_ganados']) ?></div></div>
                        <div class="col-6"><div class="text-muted small">Perdidos</div><div class="fs-4 fw-bold text-danger"><?= intval($reputation['mercados_perdidos']) ?></div></div>
                        <div class="col-6"><div class="text-muted small">Precisión</div><div class="fs-4 fw-bold text-info"><?= number_format(floatval($reputation['precision_pct']), 2) ?>%</div></div>
                    </div>
                    <hr>
                    <div class="small text-muted">IC apostado acumulado</div>
                    <div class="fw-bold"><?= number_format(intval($reputation['total_ic_apostado'])) ?></div>
                    <div class="small text-muted mt-2">IC ganado acumulado</div>
                    <div class="fw-bold text-success"><?= number_format(intval($reputation['total_ic_ganado'])) ?></div>
                    <?php if ($user_alliance_name): ?>
                    <hr>
                    <div class="alert alert-info mb-0 py-2">
                        <strong><i class="bi bi-people-fill me-1"></i><?= htmlspecialchars($user_alliance_name) ?></strong><br>
                        <?= !empty($current_user['alliance_id']) ? 'Perteneces a una alianza activa.' : 'Sin alianza activa.' ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- LISTA DE MERCADOS -->
    <h3 class="mb-3 mt-4"><i class="bi bi-graph-up me-2"></i>Mercados Activos e Históricos</h3>

    <?php if (empty($markets)): ?>
    <div class="alert alert-info py-3 text-center">No hay mercados disponibles actualmente.</div>
    <?php endif; ?>

    <?php foreach ($markets as $market):
        $id_market = intval($market['id_market']);
        $options = $optionsByMarket[$id_market] ?? [];
        $optionTotals = $optionTotalsByMarket[$id_market] ?? [];
        $validatedBets = $validatedBetsByMarket[$id_market] ?? [];
        $liveStats = polymarket_build_stats($market, $options, $optionTotals, $validatedBets);
        $snapshot = !empty($market['snapshot_silencioso_json']) ? json_decode($market['snapshot_silencioso_json'], true) : null;
        $publicStats = ($market['estado'] === 'silencioso' && is_array($snapshot)) ? $snapshot : $liveStats;
        $myBet = $userBetByMarket[$id_market] ?? null;
        $results = $resultsByMarket[$id_market] ?? [];
        $statusClass = [
            'abierto' => 'success',
            'silencioso' => 'warning text-dark',
            'cerrado' => 'secondary',
            'resuelto' => 'primary',
            'cancelado' => 'dark',
        ][$market['estado']] ?? 'secondary';
        $canBet = in_array($market['estado'], ['abierto', 'silencioso'], true) && strtotime($market['fecha_cierre']) > time() && (!$myBet || $myBet['estado'] === 'rechazada');
    ?>
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h5 class="mb-0 fw-bold"><?= htmlspecialchars($market['titulo']) ?></h5>
                    <span class="badge bg-<?= $statusClass ?>"><?= ucfirst($market['estado']) ?></span>
                    <span class="badge bg-info text-dark"><?= htmlspecialchars($market['categoria']) ?></span>
                </div>
                <div class="small text-muted">
                    Cierra: <?= htmlspecialchars(polymarket_format_datetime($market['fecha_cierre'])) ?> · Apuesta mínima: <?= number_format(intval($market['apuesta_minima_ic'])) ?> IC
                </div>
            </div>
            <div class="text-end small text-muted">
                <div>Creado por <?= htmlspecialchars($market['creador_username'] ?? 'GM') ?></div>
                <div><?= intval($publicStats['participants'] ?? 0) ?> participantes · <?= number_format(intval($publicStats['volume_total'] ?? 0)) ?> IC validados</div>
            </div>
        </div>
        <div class="card-body">
            <p class="mb-3 text-muted"><?= nl2br(htmlspecialchars($market['descripcion'])) ?></p>

            <?php if (!empty($market['alianzas_ven_apuestas']) && $user_alliance_name): ?>
            <div class="alert alert-info py-2">
                <i class="bi bi-people-fill me-1"></i>
                <strong>Visibilidad extendida para alianzas:</strong>
                <?= htmlspecialchars($market['alianza_info_publica'] ?: 'Los GM han habilitado información adicional para miembros de alianza.') ?>
            </div>
            <?php endif; ?>

            <?php if ($market['estado'] === 'silencioso'): ?>
            <div class="alert alert-warning py-2">
                <i class="bi bi-eye-slash me-1"></i> Cierre silencioso activo. Las métricas públicas quedan congeladas hasta el cierre.
            </div>
            <?php endif; ?>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="card h-100 bg-body-tertiary">
                        <div class="card-body py-2 px-3">
                            <div class="text-muted small">Consenso</div>
                            <div class="fs-5 fw-bold"><?= intval($publicStats['consensus_index'] ?? 0) ?>/100</div>
                            <span class="badge bg-primary mt-1"><?= htmlspecialchars($publicStats['consensus_label'] ?? marketConsensusLabel(0)) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 bg-body-tertiary">
                        <div class="card-body py-2 px-3">
                            <div class="text-muted small">Tendencia</div>
                            <div class="fs-6 fw-bold text-truncate" style="max-width:100%"><?= htmlspecialchars($publicStats['trend_option'] ?: 'Sin datos') ?></div>
                            <div class="text-muted small mt-1"><?= intval($publicStats['trend_share'] ?? 0) ?>% del volumen</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 bg-body-tertiary">
                        <div class="card-body py-2 px-3">
                            <div class="text-muted small">Ballenas / Medianos / Pequeños</div>
                            <div class="fw-bold mt-1"><?= intval($publicStats['whales']['🐋 Ballenas'] ?? 0) ?> · <?= intval($publicStats['whales']['🐬 Medianos'] ?? 0) ?> · <?= intval($publicStats['whales']['🐟 Pequeños'] ?? 0) ?></div>
                            <div class="small text-muted" style="font-size:0.7rem">Participación por peso de apuesta</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="bi bi-bar-chart-line me-1"></i>Distribución de apuestas validadas</h6>
                            <?php foreach ($options as $option):
                                $stats_opt = $publicStats['distribution'][$option['id_option']] ?? ['total_ic' => 0, 'porcentaje' => 0];
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                    <div>
                                        <strong><?= htmlspecialchars($option['titulo']) ?></strong>
                                    </div>
                                    <div class="text-end small">
                                        <strong><?= number_format(intval($stats_opt['total_ic'])) ?> IC</strong>
                                        <span class="text-muted ms-1">(<?= number_format(floatval($stats_opt['porcentaje']), 1) ?>%)</span>
                                    </div>
                                </div>
                                <div class="progress" style="height:10px">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?= number_format(floatval($stats_opt['porcentaje']), 1, '.', '') ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="bi bi-gem me-1"></i>Tu apuesta</h6>
                            <?php if ($myBet && $myBet['estado'] !== 'rechazada'): ?>
                            <div class="alert alert-<?= $myBet['estado'] === 'validada' ? 'success' : 'warning' ?> mb-0">
                                <div><strong>Opción elegida:</strong> <?= htmlspecialchars($myBet['option_title']) ?></div>
                                <div><strong>Monto:</strong> <?= number_format(intval($myBet['ic_apostado'])) ?> IC</div>
                                <div class="mt-1"><strong>Estado:</strong> <span class="badge bg-<?= $myBet['estado'] === 'validada' ? 'success' : 'warning text-dark' ?>"><?= ucfirst($myBet['estado']) ?></span></div>
                                <?php if (!empty($myBet['observaciones'])): ?>
                                <div class="mt-1 small text-muted"><strong>Obs:</strong> <?= htmlspecialchars($myBet['observaciones']) ?></div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                                <?php if ($myBet && $myBet['estado'] === 'rechazada'): ?>
                                <div class="alert alert-danger mb-3 py-2">
                                    <div><strong><i class="bi bi-exclamation-triangle-fill me-1"></i>Apuesta Rechazada:</strong> <?= htmlspecialchars($myBet['option_title']) ?> (<?= number_format(intval($myBet['ic_apostado'])) ?> IC)</div>
                                    <?php if (!empty($myBet['observaciones'])): ?>
                                    <div class="small mt-1"><strong>Obs:</strong> <?= htmlspecialchars($myBet['observaciones']) ?></div>
                                    <?php endif; ?>
                                    <div class="small fw-bold mt-1">Puedes enviar una nueva apuesta a continuación.</div>
                                </div>
                                <?php endif; ?>

                                <?php if ($canBet): ?>
                                <form method="post" class="row g-3">
                                    <input type="hidden" name="place_bet" value="1">
                                    <input type="hidden" name="market_id" value="<?= $id_market ?>">
                                    <div class="col-12">
                                        <label class="form-label fw-500">Opción</label>
                                        <select name="option_id" class="form-select" required>
                                            <option value="">Selecciona una opción</option>
                                            <?php foreach ($options as $option): ?>
                                            <option value="<?= intval($option['id_option']) ?>"><?= htmlspecialchars($option['titulo']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-500">Cantidad de IC</label>
                                        <input type="number" name="ic_amount" class="form-control" min="<?= intval($market['apuesta_minima_ic']) ?>" max="<?= $user_ic_production ?>" required>
                                        <div class="form-text small text-muted">
                                            Mínimo: <?= number_format(intval($market['apuesta_minima_ic'])) ?> IC · Máximo (Producción del país): <?= number_format($user_ic_production) ?> IC
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-success w-100"><i class="bi bi-send me-1"></i>Enviar apuesta</button>
                                    </div>
                                </form>
                                <?php else: ?>
                                <div class="alert alert-secondary mb-0">
                                    <?= in_array($market['estado'], ['resuelto', 'cancelado'], true) ? 'Este mercado ya no acepta apuestas.' : 'Ya enviaste una apuesta para este mercado.' ?>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (in_array($market['estado'], ['resuelto', 'cerrado'], true) && !empty($results)): ?>
            <hr>
            <h6 class="mb-3"><i class="bi bi-clock-history me-1"></i>Historial público de resultados</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Jugador</th>
                            <th>Opción elegida</th>
                            <th>IC apostado</th>
                            <th>Ganancia obtenida</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): ?>
                        <tr>
                            <td><?= htmlspecialchars($result['username']) ?></td>
                            <td><?= htmlspecialchars($result['opcion_apostada']) ?></td>
                            <td><?= number_format(intval($result['apuesta_ic'])) ?></td>
                            <td class="fw-bold text-<?= intval($result['ganancia_ic']) > 0 ? 'success' : 'secondary' ?>"><?= number_format(intval($result['ganancia_ic'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- SUGERENCIAS DE MERCADOS -->
    <?php if ($suggestions_rows): ?>
    <div class="card mt-4 mb-4">
        <div class="card-header"><i class="bi bi-lightbulb-fill me-2"></i><strong>Sugerencias de la Comunidad</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Categoría</th>
                            <th>Estado</th>
                            <th>Autor</th>
                            <th>Mercado Creado</th>
                            <th>Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suggestions_rows as $suggestion): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($suggestion['titulo']) ?></strong>
                                <div class="small text-muted"><?= date('d/m/Y H:i', strtotime($suggestion['fecha_creacion'])) ?></div>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($suggestion['categoria']) ?></span></td>
                            <td>
                                <span class="badge bg-<?= $suggestion['estado'] === 'pendiente' ? 'warning text-dark' : ($suggestion['estado'] === 'aprobada' ? 'success' : 'danger') ?>">
                                    <?= ucfirst($suggestion['estado']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($suggestion['sugerente_username']) ?></td>
                            <td><?= htmlspecialchars($suggestion['market_titulo'] ?? 'Pendiente') ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#suggestDetail<?= intval($suggestion['id_suggestion']) ?>">
                                    Ver
                                </button>
                            </td>
                        </tr>
                        <tr class="collapse" id="suggestDetail<?= intval($suggestion['id_suggestion']) ?>">
                            <td colspan="6">
                                <div class="p-3 bg-body-tertiary rounded m-2">
                                    <p class="mb-2"><strong>Descripción:</strong> <?= nl2br(htmlspecialchars($suggestion['descripcion'])) ?></p>
                                    <div class="small text-muted mb-2"><strong>Opciones propuestas:</strong> <?= implode(', ', json_decode($suggestion['opciones_json'], true) ?: []) ?></div>
                                    <?php if (!empty($suggestion['observaciones'])): ?>
                                    <div class="small">
                                        <strong>Nota de GM:</strong> <span class="text-danger"><?= htmlspecialchars($suggestion['observaciones']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
