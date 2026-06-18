<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['id_rol'], [1, 2, 3])) {
    header("Location: index.php");
    exit;
}
require_once 'config/db.php';
require_once 'includes/functions.php';

ensureCartillaHistoryTable($conn);

$turno_actual = getCurrentTurn($conn);
$turno_filtro = isset($_GET['turno']) ? intval($_GET['turno']) : $turno_actual;
$pais_filtro = isset($_GET['pais']) ? intval($_GET['pais']) : 0;

$turnos_disponibles = $conn->query("SELECT DISTINCT turno_global FROM cartilla_historial ORDER BY turno_global DESC LIMIT 24")->fetchAll(PDO::FETCH_COLUMN);
$paises_disponibles = $conn->query("SELECT id_pais, nombre_pais FROM paises ORDER BY nombre_pais")->fetchAll(PDO::FETCH_ASSOC);

$sql = "
    SELECT h.*, p.nombre_pais AS nombre_pais_db, p.bandera_url AS bandera_db, u.username
    FROM cartilla_historial h
    LEFT JOIN paises p ON p.id_pais = h.id_pais
    LEFT JOIN usuarios u ON u.id_usuario = h.id_usuario
    WHERE h.turno_global = :turno
";
$params = [':turno' => $turno_filtro];
if ($pais_filtro > 0) {
    $sql .= " AND h.id_pais = :pais";
    $params[':pais'] = $pais_filtro;
}
$sql .= " ORDER BY COALESCE(p.nombre_pais, h.nombre_pais), h.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Historial de Cartillas';
require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Cartillas</h2>
            <p class="text-muted mb-0">Snapshots por país y turno con reporte completo</p>
        </div>
        <div class="d-flex gap-2">
            <a href="admin_cartilla.php" class="btn btn-outline-primary"><i class="bi bi-clipboard2-data-fill"></i> Cartillas</a>
            <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-3 align-items-end" method="get">
                <div class="col-md-4">
                    <label class="form-label">Turno</label>
                    <select name="turno" class="form-select">
                        <?php if (!in_array($turno_filtro, $turnos_disponibles, true)): ?>
                            <option value="<?= $turno_filtro ?>" selected>#<?= $turno_filtro ?> (actual)</option>
                        <?php endif; ?>
                        <?php foreach ($turnos_disponibles as $turno): ?>
                            <option value="<?= intval($turno) ?>" <?= intval($turno) === $turno_filtro ? 'selected' : '' ?>>
                                #<?= intval($turno) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">País</label>
                    <select name="pais" class="form-select">
                        <option value="0">Todos los países</option>
                        <?php foreach ($paises_disponibles as $pais): ?>
                            <option value="<?= intval($pais['id_pais']) ?>" <?= intval($pais['id_pais']) === $pais_filtro ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pais['nombre_pais']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!$registros): ?>
        <div class="alert alert-info">
            No hay registros para el turno #<?= $turno_filtro ?> con el filtro seleccionado.
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($registros as $registro): ?>
                <?php
                    $snapshot = json_decode($registro['snapshot_json'], true);
                    $totales = $snapshot['totales'] ?? [];
                    $bandera = $registro['bandera_db'] ?: ($registro['bandera_url'] ?? '');
                ?>
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($bandera)): ?>
                                    <img src="<?= htmlspecialchars($bandera) ?>" alt="Bandera" style="width:36px;height:24px;object-fit:cover" class="rounded border">
                                <?php endif; ?>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($registro['nombre_pais_db'] ?: $registro['nombre_pais']) ?></div>
                                    <div class="text-muted small">Turno #<?= intval($registro['turno_global']) ?> · <?= htmlspecialchars($registro['accion']) ?> · <?= htmlspecialchars($registro['username'] ?: 'Sistema') ?></div>
                                </div>
                            </div>
                            <div class="text-end small text-muted">
                                <div><?= htmlspecialchars($registro['created_at']) ?></div>
                                <div>Balance IM: <?= number_format(intval($registro['balance_im'])) ?></div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-2 mb-3">
                                <div class="col-md-3"><div class="border rounded p-2"><div class="small text-muted">Tipos</div><strong><?= number_format(intval($registro['total_tipos'])) ?></strong></div></div>
                                <div class="col-md-3"><div class="border rounded p-2"><div class="small text-muted">Unidades netas</div><strong><?= number_format(intval($registro['total_unidades'])) ?></strong></div></div>
                                <div class="col-md-2"><div class="border rounded p-2"><div class="small text-muted">IC</div><strong><?= number_format(intval($registro['total_produccion_ic'])) ?></strong></div></div>
                                <div class="col-md-2"><div class="border rounded p-2"><div class="small text-muted">IM</div><strong><?= number_format(intval($registro['total_produccion_im'])) ?></strong></div></div>
                                <div class="col-md-2"><div class="border rounded p-2"><div class="small text-muted">IT</div><strong><?= number_format(intval($registro['total_produccion_it'])) ?></strong></div></div>
                            </div>

                            <?php if (!empty($totales)): ?>
                                <div class="alert alert-light border mb-3">
                                    <strong>Resumen de industria:</strong>
                                    IC <?= number_format(intval($totales['produccion_ic'] ?? 0)) ?> ·
                                    IM <?= number_format(intval($totales['produccion_im'] ?? 0)) ?> ·
                                    IT <?= number_format(intval($totales['produccion_it'] ?? 0)) ?> ·
                                    Mantenimiento IM <?= number_format(intval($totales['mantenimiento_im'] ?? 0)) ?> ·
                                    Balance IM <?= number_format(intval($totales['balance_im'] ?? 0)) ?>
                                </div>
                            <?php endif; ?>

                            <details>
                                <summary class="fw-semibold">Ver reporte completo</summary>
                                <pre class="mt-3 p-3 bg-light border rounded" style="white-space:pre-wrap;word-break:break-word;max-height:420px;overflow:auto"><?= htmlspecialchars($registro['reporte_texto']) ?></pre>
                            </details>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
