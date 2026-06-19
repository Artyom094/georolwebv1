<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['id_rol'], [1, 2])) {
    header("Location: polymarket.php");
    exit;
}

require_once 'config/db.php';
require_once 'includes/functions.php';

ensurePolymarketTables($conn);

$user_id = intval($_SESSION['user_id']);
$role_id = intval($_SESSION['id_rol'] ?? 0);
$is_manager = in_array($role_id, [1, 2], true);

$page_title = 'Panel GM — Polymarket';
$message = '';
$message_type = 'info';

$categorias_polymarket = ['Política', 'Militar', 'Economía', 'Noticias', 'Diplomacia'];

function polymarket_split_options($raw_text) {
    $lines = preg_split('/\r\n|\r|\n/', (string) $raw_text);
    $options = [];
    foreach ($lines as $line) {
        $value = trim($line);
        if ($value !== '') {
            $options[] = $value;
        }
    }
    return array_values(array_unique($options));
}

// Procesar POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        if ($action === 'create_market') {
            $titulo = trim($_POST['titulo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $categoria = trim($_POST['categoria'] ?? '');
            $fecha_cierre = trim($_POST['fecha_cierre'] ?? '');
            $apuesta_minima_ic = max(1, intval($_POST['apuesta_minima_ic'] ?? 1));
            $cierre_silencioso_minutos = max(0, intval($_POST['cierre_silencioso_minutos'] ?? 0));
            $alianzas_ven_apuestas = isset($_POST['alianzas_ven_apuestas']) ? 1 : 0;
            $alianza_info_publica = trim($_POST['alianza_info_publica'] ?? '');
            $opciones = polymarket_split_options($_POST['opciones_texto'] ?? '');

            if ($titulo === '' || $descripcion === '' || $fecha_cierre === '' || count($opciones) < 2) {
                throw new Exception('Completa título, descripción, fecha de cierre y al menos 2 opciones.');
            }
            if (!in_array($categoria, $categorias_polymarket, true)) {
                throw new Exception('Categoría inválida.');
            }
            if (strtotime($fecha_cierre) <= time()) {
                throw new Exception('La fecha de cierre debe ser futura.');
            }

            $conn->beginTransaction();
            $insert_market = $conn->prepare("
                INSERT INTO markets
                    (titulo, descripcion, categoria, fecha_cierre, estado, apuesta_minima_ic, alianzas_ven_apuestas, alianza_info_publica, cierre_silencioso_minutos, id_usuario_creador)
                VALUES
                    (:titulo, :descripcion, :categoria, :fecha_cierre, 'abierto', :apuesta_minima_ic, :alianzas_ven_apuestas, :alianza_info_publica, :cierre_silencioso_minutos, :id_usuario_creador)
            ");
            $insert_market->execute([
                ':titulo' => $titulo,
                ':descripcion' => $descripcion,
                ':categoria' => $categoria,
                ':fecha_cierre' => date('Y-m-d H:i:s', strtotime($fecha_cierre)),
                ':apuesta_minima_ic' => $apuesta_minima_ic,
                ':alianzas_ven_apuestas' => $alianzas_ven_apuestas,
                ':alianza_info_publica' => $alianza_info_publica !== '' ? $alianza_info_publica : null,
                ':cierre_silencioso_minutos' => $cierre_silencioso_minutos,
                ':id_usuario_creador' => $user_id,
            ]);
            $id_market = intval($conn->lastInsertId());

            $insert_option = $conn->prepare("
                INSERT INTO market_options (id_market, titulo, descripcion, orden)
                VALUES (:id_market, :titulo, :descripcion, :orden)
            ");
            foreach ($opciones as $index => $opcion) {
                $insert_option->execute([
                    ':id_market' => $id_market,
                    ':titulo' => $opcion,
                    ':descripcion' => null,
                    ':orden' => $index + 1,
                ]);
            }
            $conn->commit();

            $message = 'Mercado creado correctamente.';
            $message_type = 'success';
        }

        if ($action === 'approve_suggestion') {
            $id_suggestion = intval($_POST['id_suggestion'] ?? 0);
            $fecha_cierre = trim($_POST['fecha_cierre'] ?? '');
            $apuesta_minima_ic = max(1, intval($_POST['apuesta_minima_ic'] ?? 1));
            $cierre_silencioso_minutos = max(0, intval($_POST['cierre_silencioso_minutos'] ?? 0));
            $alianzas_ven_apuestas = isset($_POST['alianzas_ven_apuestas']) ? 1 : 0;
            $alianza_info_publica = trim($_POST['alianza_info_publica'] ?? '');

            if ($fecha_cierre === '') {
                throw new Exception('La fecha de cierre es requerida.');
            }
            if (strtotime($fecha_cierre) <= time()) {
                throw new Exception('La fecha de cierre debe ser futura.');
            }

            $suggestion_stmt = $conn->prepare("SELECT * FROM market_suggestions WHERE id_suggestion = :id LIMIT 1 FOR UPDATE");
            $conn->beginTransaction();
            $suggestion_stmt->execute([':id' => $id_suggestion]);
            $suggestion = $suggestion_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$suggestion || $suggestion['estado'] !== 'pendiente') {
                throw new Exception('La sugerencia no está disponible para aprobar.');
            }

            $opciones = json_decode($suggestion['opciones_json'], true) ?: [];
            if (count($opciones) < 2) {
                throw new Exception('La sugerencia no tiene suficientes opciones.');
            }

            $insert_market = $conn->prepare("
                INSERT INTO markets
                    (titulo, descripcion, categoria, fecha_cierre, estado, apuesta_minima_ic, alianzas_ven_apuestas, alianza_info_publica, cierre_silencioso_minutos, id_usuario_creador)
                VALUES
                    (:titulo, :descripcion, :categoria, :fecha_cierre, 'abierto', :apuesta_minima_ic, :alianzas_ven_apuestas, :alianza_info_publica, :cierre_silencioso_minutos, :id_usuario_creador)
            ");
            $insert_market->execute([
                ':titulo' => $suggestion['titulo'],
                ':descripcion' => $suggestion['descripcion'],
                ':categoria' => $suggestion['categoria'],
                ':fecha_cierre' => date('Y-m-d H:i:s', strtotime($fecha_cierre)),
                ':apuesta_minima_ic' => $apuesta_minima_ic,
                ':alianzas_ven_apuestas' => $alianzas_ven_apuestas,
                ':alianza_info_publica' => $alianza_info_publica !== '' ? $alianza_info_publica : null,
                ':cierre_silencioso_minutos' => $cierre_silencioso_minutos,
                ':id_usuario_creador' => intval($suggestion['id_usuario']),
            ]);
            $id_market = intval($conn->lastInsertId());

            $insert_option = $conn->prepare("INSERT INTO market_options (id_market, titulo, descripcion, orden) VALUES (:id_market, :titulo, :descripcion, :orden)");
            foreach ($opciones as $index => $opcion) {
                $insert_option->execute([
                    ':id_market' => $id_market,
                    ':titulo' => $opcion,
                    ':descripcion' => null,
                    ':orden' => $index + 1,
                ]);
            }

            $upd = $conn->prepare("
                UPDATE market_suggestions
                SET estado = 'aprobada', id_market = :id_market, revisado_por = :revisado_por, revisado_en = NOW(), observaciones = :observaciones
                WHERE id_suggestion = :id_suggestion
            ");
            $upd->execute([
                ':id_market' => $id_market,
                ':revisado_por' => $user_id,
                ':observaciones' => trim($_POST['observaciones'] ?? '') !== '' ? trim($_POST['observaciones']) : null,
                ':id_suggestion' => $id_suggestion,
            ]);
            $conn->commit();

            $message = 'Sugerencia aprobada y convertida en mercado.';
            $message_type = 'success';
        }

        if ($action === 'reject_suggestion') {
            $id_suggestion = intval($_POST['id_suggestion'] ?? 0);
            $upd = $conn->prepare("
                UPDATE market_suggestions
                SET estado = 'rechazada', revisado_por = :revisado_por, revisado_en = NOW(), observaciones = :observaciones
                WHERE id_suggestion = :id_suggestion AND estado = 'pendiente'
            ");
            $upd->execute([
                ':revisado_por' => $user_id,
                ':observaciones' => trim($_POST['observaciones'] ?? '') !== '' ? trim($_POST['observaciones']) : null,
                ':id_suggestion' => $id_suggestion,
            ]);
            $message = 'Sugerencia rechazada.';
            $message_type = 'warning';
        }

        if ($action === 'validate_bet') {
            $id_bet = intval($_POST['id_bet'] ?? 0);
            $upd = $conn->prepare("
                UPDATE market_bets
                SET estado = 'validada', validado_por = :validado_por, validado_en = NOW(), observaciones = :observaciones
                WHERE id_bet = :id_bet AND estado = 'pendiente'
            ");
            $upd->execute([
                ':validado_por' => $user_id,
                ':observaciones' => trim($_POST['observaciones'] ?? '') !== '' ? trim($_POST['observaciones']) : null,
                ':id_bet' => $id_bet,
            ]);
            $message = 'Apuesta validada.';
            $message_type = 'success';
        }

        if ($action === 'reject_bet') {
            $id_bet = intval($_POST['id_bet'] ?? 0);
            $upd = $conn->prepare("
                UPDATE market_bets
                SET estado = 'rechazada', validado_por = :validado_por, validado_en = NOW(), observaciones = :observaciones
                WHERE id_bet = :id_bet AND estado = 'pendiente'
            ");
            $upd->execute([
                ':validado_por' => $user_id,
                ':observaciones' => trim($_POST['observaciones'] ?? '') !== '' ? trim($_POST['observaciones']) : null,
                ':id_bet' => $id_bet,
            ]);
            $message = 'Apuesta rechazada.';
            $message_type = 'warning';
        }

        if ($action === 'resolve_market') {
            $id_market = intval($_POST['id_market'] ?? 0);
            $id_option_ganadora = intval($_POST['id_option_ganadora'] ?? 0);
            $resultado = resolvePolymarket($conn, $id_market, $id_option_ganadora, $user_id);
            if (!$resultado['success']) {
                throw new Exception($resultado['error'] ?? 'No se pudo resolver el mercado.');
            }
            $message = 'Mercado resuelto. Multiplicador: ' . number_format((float) $resultado['multiplicador'], 4) . 'x';
            $message_type = 'success';
        }

        if ($action === 'close_market') {
            $id_market = intval($_POST['id_market'] ?? 0);
            $upd = $conn->prepare("
                UPDATE markets
                SET estado = 'cerrado',
                    fecha_cierre_real = NOW(),
                    fecha_cierre = NOW()
                WHERE id_market = :id_market AND estado IN ('abierto', 'silencioso')
            ");
            $upd->execute([':id_market' => $id_market]);
            $message = 'Mercado cerrado manualmente. Ya no acepta más apuestas.';
            $message_type = 'success';
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Actualizar estados automáticos para mercados que expiraron
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

// Consultas GM
$suggestions_rows = $conn->query("
    SELECT s.*, u.username AS sugerente_username, m.titulo AS market_titulo
    FROM market_suggestions s
    JOIN usuarios u ON u.id_usuario = s.id_usuario
    LEFT JOIN markets m ON m.id_market = s.id_market
    ORDER BY FIELD(s.estado, 'pendiente', 'aprobada', 'rechazada'), s.fecha_creacion DESC
")->fetchAll(PDO::FETCH_ASSOC);

$pending_bets_rows = $conn->query("
    SELECT b.*, u.username, m.titulo AS market_titulo, o.titulo AS option_titulo
    FROM market_bets b
    JOIN usuarios u ON u.id_usuario = b.id_usuario
    JOIN markets m ON m.id_market = b.id_market
    JOIN market_options o ON o.id_option = b.id_option
    WHERE b.estado = 'pendiente'
    ORDER BY b.fecha_apuesta ASC, b.id_bet ASC
")->fetchAll(PDO::FETCH_ASSOC);

$market_rows = $conn->query("
    SELECT m.*, u.username AS creador_username, res.username AS resuelve_username, o.titulo AS ganador_titulo
    FROM markets m
    LEFT JOIN usuarios u ON u.id_usuario = m.id_usuario_creador
    LEFT JOIN usuarios res ON res.id_usuario = m.id_usuario_resuelve
    LEFT JOIN market_options o ON o.id_option = m.id_opcion_ganadora
    ORDER BY FIELD(m.estado, 'cerrado', 'abierto', 'silencioso', 'resuelto', 'cancelado'), m.fecha_cierre DESC, m.id_market DESC
")->fetchAll(PDO::FETCH_ASSOC);

$options_rows = $conn->query("SELECT * FROM market_options ORDER BY orden, id_option")->fetchAll(PDO::FETCH_ASSOC);
$options_by_market = [];
foreach ($options_rows as $option_row) {
    $options_by_market[intval($option_row['id_market'])][] = $option_row;
}

$ready_markets = array_filter($market_rows, function ($m) {
    return $m['estado'] === 'cerrado';
});

$active_markets = array_filter($market_rows, function ($m) {
    return in_array($m['estado'], ['abierto', 'silencioso'], true);
});

// Stats rápidas
$stats = [
    'pendientes_sugerencias' => 0,
    'pendientes_apuestas' => count($pending_bets_rows),
    'cerrados_resolucion' => count($ready_markets)
];
foreach ($suggestions_rows as $sug) {
    if ($sug['estado'] === 'pendiente') {
        $stats['pendientes_sugerencias']++;
    }
}

require_once 'includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">

    <!-- HERO -->
    <div class="card flag-glow mb-4" style="border-radius:20px;overflow:hidden">
        <div style="background:linear-gradient(135deg,#198754,#20c997);color:#fff;padding:2rem 2.5rem;position:relative;overflow:hidden">
            <div style="position:absolute;top:-50px;right:-50px;width:200px;height:200px;background:rgba(255,255,255,.08);border-radius:50%;filter:blur(25px)"></div>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:56px;height:56px;background:rgba(255,255,255,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.7rem;backdrop-filter:blur(8px)">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <h1 class="mb-0" style="font-size:1.6rem;font-weight:800">Panel GM — Polymarket</h1>
                        <p class="mb-0 mt-1" style="opacity:.8;font-size:.875rem">Administración de mercados predictivos, validación de apuestas y resolución de pozos</p>
                    </div>
                </div>
                <div>
                    <a href="polymarket.php" class="btn btn-light btn-sm text-success fw-bold">
                        <i class="bi bi-arrow-left me-1"></i>Ver Vista Jugadores
                    </a>
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
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:rgba(255,193,7,.18);color:#9a6700"><i class="bi bi-lightbulb"></i></div>
                    <div>
                        <div class="stat-value" style="color:#9a6700"><?= $stats['pendientes_sugerencias'] ?></div>
                        <div class="stat-label">Sugerencias pendientes</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:rgba(13,110,253,.12);color:#0d6efd"><i class="bi bi-cash-coin"></i></div>
                    <div>
                        <div class="stat-value" style="color:#0d6efd"><?= $stats['pendientes_apuestas'] ?></div>
                        <div class="stat-label">Apuestas por validar</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:rgba(220,53,69,.12);color:#dc3545"><i class="bi bi-stopwatch"></i></div>
                    <div>
                        <div class="stat-value" style="color:#dc3545"><?= $stats['cerrados_resolucion'] ?></div>
                        <div class="stat-label">Mercados listos para resolver</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- CREAR MERCADO -->
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-plus-circle me-2"></i><strong>Crear mercado</strong></div>
                <div class="card-body">
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="create_market">
                        <div class="col-12">
                            <label class="form-label">Título</label>
                            <input type="text" name="titulo" class="form-control" maxlength="180" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Categoría</label>
                            <select name="categoria" class="form-select" required>
                                <?php foreach ($categorias_polymarket as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de cierre</label>
                            <input type="datetime-local" name="fecha_cierre" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apuesta mínima IC</label>
                            <input type="number" name="apuesta_minima_ic" class="form-control" min="1" value="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cierre silencioso (min)</label>
                            <input type="number" name="cierre_silencioso_minutos" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Opciones del mercado</label>
                            <textarea name="opciones_texto" class="form-control" rows="5" placeholder="Una opción por línea" required></textarea>
                            <div class="form-text">Escribe una opción por línea. Al menos 2 opciones.</div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="alianzas_ven_apuestas" id="createAllianceVisibility">
                                <label class="form-check-label" for="createAllianceVisibility">Permitir visibilidad ampliada para miembros de alianzas</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nota para alianzas (opcional)</label>
                            <textarea name="alianza_info_publica" class="form-control" rows="2" placeholder="Información visible solo para miembros de alianzas."></textarea>
                        </div>
                        <div class="col-12 d-grid mt-4">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Crear mercado</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- RESOLUCIÓN Y VALIDACIONES -->
        <div class="col-lg-7">
            <!-- APUESTAS PENDIENTES -->
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-cash-stack me-2"></i><strong>Apuestas pendientes de validación</strong></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Mercado</th>
                                    <th>Jugador</th>
                                    <th>Opción</th>
                                    <th class="text-end">IC</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$pending_bets_rows): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No hay apuestas pendientes.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($pending_bets_rows as $bet): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($bet['market_titulo']) ?></strong></td>
                                    <td><?= htmlspecialchars($bet['username']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($bet['option_titulo']) ?></span></td>
                                    <td class="text-end fw-bold"><?= number_format(intval($bet['ic_apostado'])) ?></td>
                                    <td>
                                        <form method="post" class="d-flex gap-2 align-items-center">
                                            <input type="hidden" name="id_bet" value="<?= intval($bet['id_bet']) ?>">
                                            <input type="text" name="observaciones" class="form-control form-control-sm" placeholder="Obs. opcional" style="max-width:140px">
                                            <button type="submit" name="action" value="validate_bet" class="btn btn-sm btn-success">Validar</button>
                                            <button type="submit" name="action" value="reject_bet" class="btn btn-sm btn-outline-danger">Rechazar</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- MERCADOS ACTIVOS (Para cierre manual) -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-play-circle me-2"></i><strong>Mercados Activos</strong></span>
                    <span class="badge bg-light text-success"><?= count($active_markets) ?> activos</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Mercado</th>
                                    <th>Estado</th>
                                    <th>Cierre Prog.</th>
                                    <th class="text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$active_markets): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No hay mercados activos en este momento.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($active_markets as $market): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($market['titulo']) ?></strong>
                                        <div class="small text-muted"><?= htmlspecialchars($market['categoria']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $market['estado'] === 'abierto' ? 'success' : 'warning text-dark' ?>">
                                            <?= ucfirst($market['estado']) ?>
                                        </span>
                                    </td>
                                    <td class="small">
                                        <?= date('d/m/Y H:i', strtotime($market['fecha_cierre'])) ?>
                                    </td>
                                    <td class="text-end">
                                        <form method="post" onsubmit="return confirm('¿Seguro que deseas cerrar este mercado manualmente ahora?')">
                                            <input type="hidden" name="action" value="close_market">
                                            <input type="hidden" name="id_market" value="<?= intval($market['id_market']) ?>">
                                            <button type="submit" class="btn btn-sm btn-warning"><i class="bi bi-stop-fill me-1"></i>Cerrar ya</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- MERCADOS PARA RESOLUCIÓN -->
            <div class="card">
                <div class="card-header"><i class="bi bi-flag me-2"></i><strong>Mercados listos para resolución</strong></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Mercado</th>
                                    <th>Estado</th>
                                    <th>Ganadora</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$ready_markets): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No hay mercados cerrados pendientes de resolución.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($ready_markets as $market): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($market['titulo']) ?></strong>
                                        <div class="small text-muted"><?= htmlspecialchars($market['categoria']) ?> · Cerró <?= date('d/m/Y H:i', strtotime($market['fecha_cierre'])) ?></div>
                                    </td>
                                    <td><span class="badge bg-secondary">Cerrado</span></td>
                                    <td>
                                        <form method="post" class="d-flex gap-2 align-items-center">
                                            <input type="hidden" name="action" value="resolve_market">
                                            <input type="hidden" name="id_market" value="<?= intval($market['id_market']) ?>">
                                            <select name="id_option_ganadora" class="form-select form-select-sm" style="min-width:180px" required>
                                                <option value="">Seleccionar opción ganadora</option>
                                                <?php foreach (($options_by_market[intval($market['id_market'])] ?? []) as $option): ?>
                                                <option value="<?= intval($option['id_option']) ?>"><?= htmlspecialchars($option['titulo']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                    </td>
                                    <td>
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Resolver el mercado e iniciar la repartición de ganancias?')">Resolver</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SUGERENCIAS DE MERCADO -->
    <?php if ($suggestions_rows): ?>
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-inbox me-2"></i><strong>Sugerencias de Mercado de los Jugadores</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Categoría</th>
                            <th>Estado</th>
                            <th>Autor</th>
                            <th>Mercado Relacionado</th>
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
                            <td><?= htmlspecialchars($suggestion['market_titulo'] ?? 'Ninguno') ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#suggestDetail<?= intval($suggestion['id_suggestion']) ?>">
                                    Ver / Gestionar
                                </button>
                            </td>
                        </tr>
                        <tr class="collapse" id="suggestDetail<?= intval($suggestion['id_suggestion']) ?>">
                            <td colspan="6">
                                <div class="p-3 bg-body-tertiary rounded m-2">
                                    <p class="mb-2"><strong>Descripción:</strong> <?= nl2br(htmlspecialchars($suggestion['descripcion'])) ?></p>
                                    <div class="small text-muted mb-3"><strong>Opciones sugeridas:</strong> <?= implode(', ', json_decode($suggestion['opciones_json'], true) ?: []) ?></div>
                                    
                                    <?php if ($suggestion['estado'] === 'pendiente'): ?>
                                    <form method="post" class="mt-3 card p-3 border">
                                        <input type="hidden" name="id_suggestion" value="<?= intval($suggestion['id_suggestion']) ?>">
                                        
                                        <h6 class="mb-3 text-primary"><i class="bi bi-gear-fill me-1"></i>Configuración del Mercado a Crear</h6>
                                        
                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">Fecha de cierre</label>
                                                <input type="datetime-local" name="fecha_cierre" class="form-control form-control-sm" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small fw-bold">Apuesta mínima IC</label>
                                                <input type="number" name="apuesta_minima_ic" class="form-control form-control-sm" min="1" value="1" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small fw-bold">Cierre silencioso (min)</label>
                                                <input type="number" name="cierre_silencioso_minutos" class="form-control form-control-sm" min="0" value="0">
                                            </div>
                                            <div class="col-12">
                                                <div class="form-check form-switch mt-2">
                                                    <input class="form-check-input" type="checkbox" name="alianzas_ven_apuestas" id="suggestAllianceVisibility<?= intval($suggestion['id_suggestion']) ?>">
                                                    <label class="form-check-label small" for="suggestAllianceVisibility<?= intval($suggestion['id_suggestion']) ?>">Permitir visibilidad ampliada para miembros de alianzas</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small fw-bold">Nota para alianzas (opcional)</label>
                                                <textarea name="alianza_info_publica" class="form-control form-control-sm" rows="2" placeholder="Información visible solo para miembros de alianzas."></textarea>
                                            </div>
                                        </div>

                                        <div class="row g-2">
                                            <div class="col-md-8">
                                                <label class="form-label small fw-bold">Observaciones / Feedback para el creador (opcional)</label>
                                                <input type="text" name="observaciones" class="form-control form-control-sm" placeholder="Mensaje visible para el jugador">
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end gap-2 justify-content-end">
                                                <button type="submit" name="action" value="approve_suggestion" class="btn btn-success btn-sm">
                                                    <i class="bi bi-check-circle me-1"></i>Aprobar y Crear
                                                </button>
                                                <button type="submit" name="action" value="reject_suggestion" class="btn btn-outline-danger btn-sm">
                                                    <i class="bi bi-x-circle me-1"></i>Rechazar
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                    <?php else: ?>
                                    <div class="small">
                                        <strong>Revisado por:</strong> <?= htmlspecialchars($suggestion['revisado_por'] ?? 'Sistema') ?> en <?= htmlspecialchars($suggestion['revisado_en'] ?? 'N/A') ?><br>
                                        <strong>Observaciones de GM:</strong> <em class="text-muted"><?= htmlspecialchars($suggestion['observaciones'] ?? 'Ninguna') ?></em>
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
