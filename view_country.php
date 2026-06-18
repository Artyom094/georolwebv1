<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

$id_pais = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Todos los usuarios pueden ver cualquier cartilla (solo lectura)
// Solo GM y Admin pueden editar

// Obtener datos del país y cartilla con enfoque activo
$stmt = $conn->prepare("
    SELECT p.*,
           e.nombre_enfoque, e.tipo_enfoque, e.descripcion,
           e.multiplicador_ic, e.multiplicador_im, e.multiplicador_it,
           e.bonus_defensa, e.cooldown_guerra_reducido
    FROM paises p
    LEFT JOIN enfoques e ON p.id_enfoque_activo = e.id_enfoque
    WHERE p.id_pais = :id");
$stmt->execute([':id' => $id_pais]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) die("País no encontrado.");

$turno_global_actual = getCurrentTurn($conn);
$cartilla_historial_registros = getCartillaHistoryRecords($conn, $id_pais);
$cartilla_historial_registros_asc = array_reverse($cartilla_historial_registros);
$cartilla_historial_resumen = [
    'turnos' => count($cartilla_historial_registros),
    'altas_unidades' => 0,
    'bajas_unidades' => 0,
    'altas_tipos' => 0,
    'bajas_tipos' => 0,
];
$cartilla_historial_detalle = [];
$cartilla_anterior_map = [];

foreach ($cartilla_historial_registros_asc as $registro) {
    $snapshot = $registro['snapshot'] ?? [];
    $items = $snapshot['items'] ?? [];
    $actual_map = [];
    $cambios = [];
    $altas_unidades = 0;
    $bajas_unidades = 0;
    $altas_tipos = 0;
    $bajas_tipos = 0;

    foreach ($items as $item) {
        $key = isset($item['id_tipo']) && $item['id_tipo'] !== null
            ? 'tipo_' . intval($item['id_tipo'])
            : 'legacy_' . md5(($item['nombre'] ?? 'unidad') . '|' . ($item['cantidad'] ?? 0));
        $cantidad = intval($item['cantidad'] ?? 0);
        $actual_map[$key] = [
            'nombre' => $item['nombre'] ?? 'Unidad',
            'cantidad' => $cantidad,
            'legacy' => !empty($item['legacy']),
        ];
    }

    foreach ($actual_map as $key => $item) {
        $prev = $cartilla_anterior_map[$key]['cantidad'] ?? 0;
        $delta = $item['cantidad'] - $prev;
        if ($delta > 0) {
            $altas_unidades += $delta;
            $altas_tipos++;
            $cambios[] = ['tipo' => 'alta', 'nombre' => $item['nombre'], 'delta' => $delta, 'legacy' => $item['legacy']];
        } elseif ($delta < 0) {
            $bajas_unidades += abs($delta);
            $bajas_tipos++;
            $cambios[] = ['tipo' => 'baja', 'nombre' => $item['nombre'], 'delta' => $delta, 'legacy' => $item['legacy']];
        }
    }

    foreach ($cartilla_anterior_map as $key => $item) {
        if (!isset($actual_map[$key]) && intval($item['cantidad'] ?? 0) > 0) {
            $bajas_unidades += intval($item['cantidad']);
            $bajas_tipos++;
            $cambios[] = ['tipo' => 'baja', 'nombre' => $item['nombre'], 'delta' => -intval($item['cantidad']), 'legacy' => $item['legacy']];
        }
    }

    $cartilla_historial_resumen['altas_unidades'] += $altas_unidades;
    $cartilla_historial_resumen['bajas_unidades'] += $bajas_unidades;
    $cartilla_historial_resumen['altas_tipos'] += $altas_tipos;
    $cartilla_historial_resumen['bajas_tipos'] += $bajas_tipos;

    $cartilla_historial_detalle[] = [
        'registro' => $registro,
        'snapshot' => $snapshot,
        'altas_unidades' => $altas_unidades,
        'bajas_unidades' => $bajas_unidades,
        'altas_tipos' => $altas_tipos,
        'bajas_tipos' => $bajas_tipos,
        'cambios' => array_slice($cambios, 0, 8),
    ];

    $cartilla_anterior_map = $actual_map;
}

$cartilla_historial_detalle = array_reverse($cartilla_historial_detalle);

// Cargar tipos de cartilla activos agrupados por categoría
$tipos_all = $conn->query("
    SELECT ct.*, cc.nombre as cat_nombre, cc.color as cat_color,
           cc.icono as cat_icono, cc.id_categoria, cc.orden as cat_orden
    FROM cartilla_tipos ct
    JOIN cartilla_categorias cc ON ct.id_categoria = cc.id_categoria
    WHERE ct.activo = 1
    ORDER BY cc.orden, ct.orden, ct.nombre
")->fetchAll(PDO::FETCH_ASSOC);

// Cargar valores del país
$vals_raw = $conn->prepare("SELECT id_tipo, cantidad FROM cartilla_valores WHERE id_pais = :id");
$vals_raw->execute([':id' => $id_pais]);
$vals = [];
foreach ($vals_raw->fetchAll(PDO::FETCH_ASSOC) as $v) $vals[$v['id_tipo']] = $v['cantidad'];

// Agrupar por categoría
$cats_map = [];
foreach ($tipos_all as $t) {
    $cid = $t['id_categoria'];
    if (!isset($cats_map[$cid])) $cats_map[$cid] = [
        'nombre' => $t['cat_nombre'], 'color' => $t['cat_color'],
        'icono' => $t['cat_icono'], 'tipos' => []
    ];
    $cats_map[$cid]['tipos'][] = $t;
}

// Gestión de Turnos (Solo GM/Admin)
if (in_array($_SESSION['id_rol'], [1, 2])) {
    if (isset($_POST['avanzar_turno'])) {
        if (avanzarTurno($conn, $id_pais, $_SESSION['user_id'], $_POST['notas'] ?? '')) {
            $msg = "Turno avanzado correctamente.";
            $stmt->execute([':id' => $id_pais]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $msg = "Error al avanzar turno.";
        }
    }
    
    if (isset($_POST['anular_turno'])) {
        if (anularTurno($conn, $id_pais, $_SESSION['user_id'], $_POST['notas'] ?? '')) {
            $msg = "Turno anulado correctamente.";
            $stmt->execute([':id' => $id_pais]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $msg = "Error al anular turno.";
        }
    }
    
    if (isset($_POST['ajustar_turno'])) {
        $nuevo_turno = intval($_POST['nuevo_turno']);
        if (ajustarTurno($conn, $id_pais, $nuevo_turno, $_SESSION['user_id'], $_POST['notas'] ?? '')) {
            $msg = "Turno ajustado correctamente.";
            $stmt->execute([':id' => $id_pais]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $msg = "Error al ajustar turno.";
        }
    }
}

// Guardar cambios (GM y Admin pueden editar)
$msg = $msg ?? '';
if (isset($_GET['msg']) && $_GET['msg'] == 'updated') {
    $msg = "Datos actualizados correctamente.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_SESSION['id_rol'], [1, 2])
    && !isset($_POST['avanzar_turno']) && !isset($_POST['anular_turno']) && !isset($_POST['ajustar_turno'])) {
    if (isset($_POST['tipo']) && is_array($_POST['tipo'])) {
        try {
            $upsert = $conn->prepare("
                INSERT INTO cartilla_valores (id_pais, id_tipo, cantidad)
                VALUES (:id_pais, :id_tipo, :cantidad)
                ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad)
            ");
            foreach ($_POST['tipo'] as $id_tipo => $cantidad) {
                $upsert->execute([':id_pais' => $id_pais, ':id_tipo' => intval($id_tipo), ':cantidad' => intval($cantidad)]);
            }
            saveCartillaHistorySnapshot($conn, $id_pais, $_SESSION['user_id'], 'guardado');
            $msg = "Datos actualizados correctamente.";
            // Recargar valores
            $vr = $conn->prepare("SELECT id_tipo, cantidad FROM cartilla_valores WHERE id_pais = :id");
            $vr->execute([':id' => $id_pais]);
            $vals = [];
            foreach ($vr->fetchAll(PDO::FETCH_ASSOC) as $v) $vals[$v['id_tipo']] = $v['cantidad'];
        } catch (PDOException $e) {
            $msg = "Error: " . $e->getMessage();
        }
    }
}

$page_title = 'Cartilla - ' . htmlspecialchars($data['nombre_pais']);
$readonly = !in_array($_SESSION['id_rol'], [1, 2]) ? 'readonly' : '';

require_once 'includes/header.php';

$flag_url = $data['bandera_url'] ?? '';
$has_flag = !empty($flag_url) && $flag_url !== 'default_flag.png';
?>

<div class="container-fluid px-3 px-lg-4 my-4" id="paisPage">

    <!-- ── HERO DEL PAÍS ── -->
    <div class="card flag-glow mb-4" id="countryHero" style="border-radius:20px;overflow:hidden">
        <div class="flag-hero p-4 p-lg-5" id="heroGradient"
             style="background:linear-gradient(135deg,var(--flag-color,#0d6efd) 0%, hsl(var(--flag-h,216),var(--flag-s,80%),calc(var(--flag-l,45%) - 12%)) 100%);color:#fff;position:relative;overflow:hidden">

            <!-- Orb decorativo -->
            <div style="position:absolute;top:-60px;right:-60px;width:220px;height:220px;background:rgba(255,255,255,.07);border-radius:50%;filter:blur(30px);pointer-events:none"></div>
            <div style="position:absolute;bottom:-40px;left:20%;width:140px;height:140px;background:rgba(0,0,0,.1);border-radius:50%;filter:blur(20px);pointer-events:none"></div>

            <div class="d-flex align-items-center gap-4 flex-wrap position-relative">
                <!-- Bandera -->
                <?php if ($has_flag): ?>
                <img id="mainFlag"
                     src="<?= htmlspecialchars($flag_url) ?>"
                     class="flag-auto"
                     data-flag-target="#paisPage"
                     data-flag-accent="true"
                     alt="Bandera de <?= htmlspecialchars($data['nombre_pais']) ?>"
                     style="height:90px;width:auto;border-radius:10px;border:2px solid rgba(255,255,255,.35);box-shadow:0 8px 24px rgba(0,0,0,.3);object-fit:cover">
                <?php else: ?>
                <div style="width:130px;height:90px;background:rgba(255,255,255,.12);border-radius:10px;border:2px solid rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center">
                    <i class="bi bi-flag" style="font-size:2.5rem;opacity:.5"></i>
                </div>
                <?php endif; ?>

                <!-- Info -->
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h2 class="mb-0 fw-800" style="font-size:2rem;letter-spacing:-.5px;font-weight:800">
                            <?= htmlspecialchars($data['nombre_pais']) ?>
                        </h2>
                        <span class="badge" style="background:rgba(255,255,255,.2);font-size:.8rem">
                            Turno #<?= $data['turno_actual'] ?>
                        </span>
                    </div>
                    <?php if ($data['nombre_enfoque']): ?>
                    <div class="mb-2">
                        <span class="badge" style="background:rgba(255,255,255,.15);font-size:.78rem">
                            <i class="bi bi-crosshair2"></i> <?= htmlspecialchars($data['nombre_enfoque']) ?>
                            &nbsp;·&nbsp; <?= htmlspecialchars($data['tipo_enfoque']) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex gap-2 flex-wrap mt-2">
                        <a href="index.php" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(8px)">
                            <i class="bi bi-arrow-left"></i> Dashboard
                        </a>
                        <?php if (in_array($_SESSION['id_rol'], [1, 2])): ?>
                        <a href="admin_cartilla.php" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(8px)">
                            <i class="bi bi-pencil-square"></i> Admin Cartillas
                        </a>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#cartillaHistoryModal"
                                style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(8px)">
                            <i class="bi bi-clipboard-data"></i> Historial de Cartilla
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($_SESSION['id_rol'] == 4): ?>
    <div class="alert alert-info fade-up">
        <i class="bi bi-eye-fill"></i> <strong>Modo Solo Lectura:</strong> Solo los Game Masters pueden modificar las estadísticas.
    </div>
    <?php endif; ?>

    <?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show fade-up">
        <?= $msg ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="post">
        <div class="row g-4">
                        
                        <!-- TURNO -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-calendar3"></i> Gestión de Turno</span>
                                    <?php if(in_array($_SESSION['id_rol'], [1, 2])): ?>
                                        <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#turnModal">
                                            <i class="bi bi-gear"></i> Gestionar
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h3 class="mb-0">
                                                <span class="badge bg-dark" style="font-size: 1.5rem;">
                                                    Turno Actual: <?= $data['turno_actual'] ?>
                                                </span>
                                            </h3>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#cartillaHistoryModal">
                                                <i class="bi bi-clipboard-data"></i> Ver Historial de Cartilla
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ENFOQUE NACIONAL -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header <?= $data['nombre_enfoque'] ? 'bg-success text-white' : 'bg-secondary text-white' ?> d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-crosshair"></i> Enfoque Nacional</span>
                                    <a href="enfoques.php" class="btn btn-sm btn-light">
                                        <i class="bi bi-gear"></i> Gestionar Enfoque
                                    </a>
                                </div>
                                <div class="card-body">
                                    <?php if ($data['nombre_enfoque']): ?>
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h4 class="mb-2">
                                                    <?= htmlspecialchars($data['nombre_enfoque']) ?>
                                                    <span class="badge bg-<?= 
                                                        $data['tipo_enfoque'] == 'Atacante' ? 'danger' : 
                                                        ($data['tipo_enfoque'] == 'Defensor' ? 'info' : 
                                                        ($data['tipo_enfoque'] == 'IC' ? 'warning' : 
                                                        ($data['tipo_enfoque'] == 'IM' ? 'dark' : 'secondary'))) 
                                                    ?>">
                                                        <?= $data['tipo_enfoque'] ?>
                                                    </span>
                                                </h4>
                                                <p class="text-muted small mb-2"><?= htmlspecialchars($data['descripcion']) ?></p>
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <?php if ($data['multiplicador_ic']): ?>
                                                        <span class="badge bg-warning text-dark">IC: x<?= $data['multiplicador_ic'] ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($data['multiplicador_im']): ?>
                                                        <span class="badge bg-dark">IM: x<?= $data['multiplicador_im'] ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($data['multiplicador_it']): ?>
                                                        <span class="badge bg-secondary">IT: x<?= $data['multiplicador_it'] ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($data['bonus_defensa'] > 0): ?>
                                                        <span class="badge bg-info">+<?= $data['bonus_defensa'] ?> Bonus Defensa</span>
                                                    <?php endif; ?>
                                                    <?php if ($data['cooldown_guerra_reducido']): ?>
                                                        <span class="badge bg-danger">Cooldown Reducido</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <?php if ($data['fecha_ultimo_cambio_enfoque']): ?>
                                                    <small class="text-muted d-block">Último cambio:</small>
                                                    <strong><?= date('d/m/Y', strtotime($data['fecha_ultimo_cambio_enfoque'])) ?></strong>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning mb-0">
                                            <i class="bi bi-exclamation-triangle"></i> 
                                            <strong>Este país no tiene un enfoque activo.</strong>
                                            <a href="enfoques.php" class="alert-link">Seleccionar enfoque →</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- CARTILLA DINÁMICA: balance IM -->
                        <?php
                        // Calcular producciones y mantenimiento dinámicamente
                        $prod_por_unidad = []; // unidad => total
                        $mant_total = 0;
                        foreach ($tipos_all as $t) {
                            $cant = $vals[$t['id_tipo']] ?? 0;
                            if ($t['tipo'] === 'produccion') {
                                $up = $t['unidad_produccion'] ?: 'misc';
                                // Aplicar modificador de enfoque para IC/IM/IT
                                $mult = $t['multiplicador'];
                                if ($up === 'IC' && $data['multiplicador_ic']) $mult = $data['multiplicador_ic'];
                                if ($up === 'IM' && $data['multiplicador_im']) $mult = $data['multiplicador_im'];
                                if ($up === 'IT' && $data['multiplicador_it']) $mult = $data['multiplicador_it'];
                                $prod_por_unidad[$up] = ($prod_por_unidad[$up] ?? 0) + $cant * $mult;
                            } else {
                                $mant_total += $cant * $t['multiplicador'];
                            }
                        }
                        $prod_im     = $prod_por_unidad['IM'] ?? 0;
                        $balance_im  = $prod_im - $mant_total;
                        $tiene_deficit = $balance_im < 0;
                        $pct_uso     = $prod_im > 0 ? round(($mant_total / $prod_im) * 100, 1) : 0;
                        ?>

                        <!-- Panel de balance IM -->
                        <div class="col-12">
                            <div class="card border-<?= $tiene_deficit ? 'danger' : 'success' ?>">
                                <div class="card-header bg-<?= $tiene_deficit ? 'danger' : 'success' ?> text-white d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-wrench-adjustable-circle-fill"></i> Balance de Mantenimiento (IM)</span>
                                    <span class="badge bg-dark"><?= $tiene_deficit ? '⚠ DÉFICIT' : '✓ SUPERÁVIT' ?></span>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center g-2">
                                        <div class="col">
                                            <small class="text-muted d-block">Producción IM</small>
                                            <h5 class="text-success mb-0"><?= number_format($prod_im) ?> IM</h5>
                                        </div>
                                        <div class="col">
                                            <small class="text-muted d-block">Mantenimiento</small>
                                            <h5 class="text-warning mb-0"><?= number_format($mant_total) ?> IM</h5>
                                        </div>
                                        <div class="col">
                                            <small class="text-muted d-block">Balance</small>
                                            <h5 class="<?= $tiene_deficit ? 'text-danger' : 'text-success' ?> mb-0">
                                                <?= $tiene_deficit ? '' : '+' ?><?= number_format($balance_im) ?> IM
                                            </h5>
                                        </div>
                                        <div class="col">
                                            <small class="text-muted d-block">% Uso IM</small>
                                            <span class="badge bg-<?= $pct_uso > 100 ? 'danger' : ($pct_uso > 80 ? 'warning text-dark' : 'info') ?>" style="font-size:1rem">
                                                <?= $pct_uso ?>%
                                            </span>
                                        </div>
                                        <?php foreach ($prod_por_unidad as $up => $val): if ($up === 'IM') continue; ?>
                                        <div class="col">
                                            <small class="text-muted d-block">Prod. <?= htmlspecialchars($up) ?></small>
                                            <h5 class="text-info mb-0"><?= number_format($val) ?></h5>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if ($tiene_deficit): ?>
                                        <div class="alert alert-danger mb-0 mt-2 small">
                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                            Tu mantenimiento excede la producción de IM. Esto afecta la operatividad.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Secciones dinámicas por categoría -->
                        <?php foreach ($cats_map as $cat): ?>
                        <div class="col-lg-6 col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-<?= $cat['color'] ?> text-white">
                                    <i class="bi bi-<?= $cat['icono'] ?>"></i> <?= htmlspecialchars($cat['nombre']) ?>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($cat['tipos'] as $t):
                                        $cant = $vals[$t['id_tipo']] ?? 0;
                                        $up   = $t['unidad_produccion'] ?: 'misc';
                                        if ($t['tipo'] === 'produccion') {
                                            $mult = $t['multiplicador'];
                                            if ($up === 'IC' && $data['multiplicador_ic']) $mult = $data['multiplicador_ic'];
                                            if ($up === 'IM' && $data['multiplicador_im']) $mult = $data['multiplicador_im'];
                                            if ($up === 'IT' && $data['multiplicador_it']) $mult = $data['multiplicador_it'];
                                            $output = $cant * $mult;
                                        } else {
                                            $output = $cant * $t['multiplicador'];
                                        }
                                    ?>
                                    <div class="mb-2">
                                        <label class="form-label form-label-sm">
                                            <?= htmlspecialchars($t['nombre']) ?>
                                            <?php if ($t['tipo'] === 'mantenimiento' && $t['multiplicador'] > 0): ?>
                                                <span class="badge bg-warning text-dark"><?= number_format($t['multiplicador'], 0) ?> IM/u</span>
                                            <?php elseif ($t['tipo'] === 'produccion'): ?>
                                                <span class="badge bg-info">×<?= number_format($t['multiplicador'], 1) ?> <?= $t['unidad_produccion'] ?></span>
                                            <?php endif; ?>
                                        </label>
                                        <input type="number"
                                               name="tipo[<?= $t['id_tipo'] ?>]"
                                               class="form-control form-control-sm"
                                               value="<?= intval($cant) ?>"
                                               <?= $readonly ?>>
                                        <small class="text-muted">
                                            <?= $t['tipo'] === 'produccion'
                                                ? "Producción: <strong class='text-success'>".number_format($output)." ".$t['unidad_produccion']."</strong>"
                                                : ($t['multiplicador'] > 0 ? "Mantenimiento: <strong class='text-warning'>".number_format($output)." IM</strong>" : "")
                                            ?>
                                        </small>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                    </div><!-- /row g-4 -->

                    <?php if(in_array($_SESSION['id_rol'], [1, 2])): ?>
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Guardar Cambios
                        </button>
                        <a href="index.php" class="btn btn-secondary btn-lg">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-secondary btn-lg">
                            <i class="bi bi-arrow-left"></i> Volver al Dashboard
                        </a>
                        <a href="profile_history.php" class="btn btn-info btn-lg">
                            <i class="bi bi-clock-history"></i> Ver Historial
                        </a>
                    </div>
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle"></i> Solo los Game Masters y Administradores pueden modificar las cartillas.
                    </div>
                    <?php endif; ?>
                </form>
    </div><!-- /container-fluid #paisPage -->

<!-- Modal: Gestionar Turnos (Solo GM/Admin) -->
<?php if(in_array($_SESSION['id_rol'], [1, 2])): ?>
<div class="modal fade" id="turnModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-calendar3"></i> Gestión de Turno</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Turno Actual:</strong> <?= $data['turno_actual'] ?>
                </div>
                
                <!-- Avanzar Turno -->
                <form method="post" class="mb-3">
                    <h6 class="border-bottom pb-2">Avanzar Turno</h6>
                    <div class="mb-2">
                        <label class="form-label">Notas (opcional)</label>
                        <input type="text" name="notas" class="form-control" placeholder="Ej: Fin del turno 5">
                    </div>
                    <button type="submit" name="avanzar_turno" class="btn btn-success w-100">
                        <i class="bi bi-arrow-right-circle"></i> Avanzar a Turno <?= $data['turno_actual'] + 1 ?>
                    </button>
                </form>
                
                <hr>
                
                <!-- Anular Turno -->
                <form method="post" class="mb-3" onsubmit="return confirm('¿Seguro que deseas anular el turno actual?')">
                    <h6 class="border-bottom pb-2">Anular Turno</h6>
                    <div class="mb-2">
                        <label class="form-label">Notas (opcional)</label>
                        <input type="text" name="notas" class="form-control" placeholder="Ej: Corrección de error">
                    </div>
                    <button type="submit" name="anular_turno" class="btn btn-danger w-100" <?= $data['turno_actual'] <= 1 ? 'disabled' : '' ?>>
                        <i class="bi bi-arrow-left-circle"></i> Retroceder a Turno <?= max(1, $data['turno_actual'] - 1) ?>
                    </button>
                </form>
                
                <hr>
                
                <!-- Ajustar Manualmente -->
                <form method="post" onsubmit="return confirm('¿Seguro que deseas cambiar el turno manualmente?')">
                    <h6 class="border-bottom pb-2">Ajuste Manual</h6>
                    <div class="mb-2">
                        <label class="form-label">Nuevo Turno</label>
                        <input type="number" name="nuevo_turno" class="form-control" min="1" value="<?= $data['turno_actual'] ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Notas (opcional)</label>
                        <input type="text" name="notas" class="form-control" placeholder="Ej: Corrección administrativa">
                    </div>
                    <button type="submit" name="ajustar_turno" class="btn btn-warning w-100">
                        <i class="bi bi-pencil-square"></i> Ajustar Turno
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Historial de Cartilla -->
<div class="modal fade" id="cartillaHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-clipboard-data"></i> Historial de Cartilla del País</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (empty($cartilla_historial_detalle)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle"></i> No hay registros de cartilla para este país todavía.
                    </div>
                <?php else: ?>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="small text-muted">Turnos guardados</div><div class="fw-bold"><?= number_format($cartilla_historial_resumen['turnos']) ?></div></div></div>
                        <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="small text-muted">Altas de unidades</div><div class="fw-bold text-success"><?= number_format($cartilla_historial_resumen['altas_unidades']) ?></div></div></div>
                        <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="small text-muted">Bajas de unidades</div><div class="fw-bold text-danger"><?= number_format($cartilla_historial_resumen['bajas_unidades']) ?></div></div></div>
                        <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="small text-muted">Neto</div><div class="fw-bold"><?= number_format($cartilla_historial_resumen['altas_unidades'] - $cartilla_historial_resumen['bajas_unidades']) ?></div></div></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Turno</th>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Altas</th>
                                    <th>Bajas</th>
                                    <th>Balance IM</th>
                                    <th>Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartilla_historial_detalle as $item):
                                    $registro = $item['registro'];
                                ?>
                                    <tr>
                                        <td><span class="badge bg-dark">#<?= intval($registro['turno_global']) ?></span></td>
                                        <td><?= htmlspecialchars($registro['created_at']) ?></td>
                                        <td><?= htmlspecialchars($registro['username'] ?: 'Sistema') ?></td>
                                        <td><span class="badge bg-success"><?= number_format($item['altas_unidades']) ?></span></td>
                                        <td><span class="badge bg-danger"><?= number_format($item['bajas_unidades']) ?></span></td>
                                        <td><?= number_format(intval($registro['balance_im'])) ?></td>
                                        <td>
                                            <details>
                                                <summary class="small">Ver cambios</summary>
                                                <?php if (empty($item['cambios'])): ?>
                                                    <div class="small text-muted mt-2">Sin cambios respecto al turno anterior.</div>
                                                <?php else: ?>
                                                    <div class="mt-2 small">
                                                        <?php foreach ($item['cambios'] as $cambio): ?>
                                                            <div class="mb-1">
                                                                <span class="badge <?= $cambio['tipo'] === 'alta' ? 'bg-success' : 'bg-danger' ?>"><?= $cambio['tipo'] === 'alta' ? 'Alta' : 'Baja' ?></span>
                                                                <?= htmlspecialchars($cambio['nombre']) ?>
                                                                <strong><?= $cambio['delta'] > 0 ? '+' : '' ?><?= number_format(intval($cambio['delta'])) ?></strong>
                                                                <?php if (!empty($cambio['legacy'])): ?><span class="badge bg-secondary ms-1">legado</span><?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </details>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
