<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['id_rol'], [1, 2])) { header("Location: index.php"); exit; }
require_once 'config/db.php';
require_once 'includes/functions.php';

$message = ''; $message_type = 'info';

// Turno global
$turno_global = $conn->query("SELECT turno_actual, ultima_actualizacion FROM turno_global WHERE id=1")->fetch(PDO::FETCH_ASSOC);
$turno_actual = $turno_global['turno_actual'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['avanzar_turno'])) {
        $nuevo = $turno_actual + 1;
        $conn->beginTransaction();
        try {
            $conn->prepare("UPDATE turno_global SET turno_actual=:t WHERE id=1")->execute([':t'=>$nuevo]);
            $conn->prepare("UPDATE paises SET turno_actual=:t")->execute([':t'=>$nuevo]);
            $conn->prepare("INSERT INTO historial_turnos(turno_anterior,turno_nuevo,tipo_cambio,id_usuario_accion) VALUES(:a,:n,'avanzar',:u)")
                 ->execute([':a'=>$turno_actual,':n'=>$nuevo,':u'=>$_SESSION['user_id']]);
            $conn->commit(); $turno_actual=$nuevo;
            $message="Turno avanzado a <strong>#$nuevo</strong> para todos los países."; $message_type='success';
        } catch(PDOException $e){ $conn->rollBack(); $message="Error: ".$e->getMessage(); $message_type='danger'; }
    }
    if (isset($_POST['retroceder_turno']) && $turno_actual > 1) {
        $nuevo = $turno_actual - 1;
        $conn->beginTransaction();
        try {
            $conn->prepare("UPDATE turno_global SET turno_actual=:t WHERE id=1")->execute([':t'=>$nuevo]);
            $conn->prepare("UPDATE paises SET turno_actual=:t")->execute([':t'=>$nuevo]);
            $conn->prepare("INSERT INTO historial_turnos(turno_anterior,turno_nuevo,tipo_cambio,id_usuario_accion) VALUES(:a,:n,'anular',:u)")
                 ->execute([':a'=>$turno_actual,':n'=>$nuevo,':u'=>$_SESSION['user_id']]);
            $conn->commit(); $turno_actual=$nuevo;
            $message="Turno retrocedido a <strong>#$nuevo</strong>."; $message_type='warning';
        } catch(PDOException $e){ $conn->rollBack(); $message="Error: ".$e->getMessage(); $message_type='danger'; }
    }
    if (isset($_POST['ajustar_turno'])) {
        $nuevo=intval($_POST['turno_nuevo']);
        if ($nuevo > 0) {
            $conn->beginTransaction();
            try {
                $conn->prepare("UPDATE turno_global SET turno_actual=:t WHERE id=1")->execute([':t'=>$nuevo]);
                $conn->prepare("UPDATE paises SET turno_actual=:t")->execute([':t'=>$nuevo]);
                $conn->prepare("INSERT INTO historial_turnos(turno_anterior,turno_nuevo,tipo_cambio,id_usuario_accion) VALUES(:a,:n,'ajustar',:u)")
                     ->execute([':a'=>$turno_actual,':n'=>$nuevo,':u'=>$_SESSION['user_id']]);
                $conn->commit(); $turno_actual=$nuevo;
                $message="Turno ajustado manualmente a <strong>#$nuevo</strong>."; $message_type='info';
            } catch(PDOException $e){ $conn->rollBack(); $message="Error: ".$e->getMessage(); $message_type='danger'; }
        }
    }
}

$historial = $conn->query("SELECT h.*, u.username FROM historial_turnos h LEFT JOIN usuarios u ON h.id_usuario_accion=u.id_usuario ORDER BY h.fecha_cambio DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Gestión de Turnos';
include 'includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">

    <!-- HERO -->
    <div class="card flag-glow mb-4" style="border-radius:20px;overflow:hidden">
        <div style="background:linear-gradient(135deg,#0d6efd,#0dcaf0);color:#fff;padding:2rem 2.5rem;position:relative;overflow:hidden">
            <div style="position:absolute;top:-50px;right:-50px;width:200px;height:200px;background:rgba(255,255,255,.08);border-radius:50%;filter:blur(25px)"></div>
            <div class="d-flex align-items-center gap-3 position-relative">
                <div style="width:56px;height:56px;background:rgba(255,255,255,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;backdrop-filter:blur(8px)">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <h1 class="mb-0" style="font-size:1.6rem;font-weight:800">Gestión de Turnos</h1>
                    <p class="mb-0 mt-1" style="opacity:.8;font-size:.875rem">Control global del sistema de turnos del juego</p>
                </div>
                <div class="ms-auto text-end d-none d-md-block">
                    <div style="font-size:.78rem;opacity:.7">Turno actual</div>
                    <div style="font-size:2.5rem;font-weight:800;line-height:1">#<?= $turno_actual ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show mb-4">
        <?= $message ?><button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- CONTROL DE TURNO -->
    <div class="row g-4 mb-4">
        <!-- Hero número -->
        <div class="col-lg-5">
            <div class="card h-100 text-center" style="border-radius:16px;overflow:hidden">
                <div class="card-body d-flex flex-column align-items-center justify-content-center py-5">
                    <div style="font-size:.875rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.1em;margin-bottom:.5rem">Turno Global</div>
                    <div style="font-size:5rem;font-weight:800;line-height:1;background:linear-gradient(135deg,var(--accent),#6610f2);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">
                        <?= $turno_actual ?>
                    </div>
                    <?php if ($turno_global['ultima_actualizacion']): ?>
                    <div style="font-size:.78rem;color:var(--text-secondary);margin-top:.5rem">
                        Actualizado: <?= date('d/m/Y H:i', strtotime($turno_global['ultima_actualizacion'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-gear me-2"></i><strong>Controles</strong></div>
                <div class="card-body d-flex flex-column gap-3 justify-content-center">
                    <!-- Avanzar -->
                    <form method="post" onsubmit="return confirm('¿Avanzar al siguiente turno para TODOS los países?')">
                        <button type="submit" name="avanzar_turno" class="btn btn-success w-100 py-3" style="font-size:1.05rem;border-radius:12px">
                            <i class="bi bi-arrow-right-circle-fill me-2"></i>Avanzar Turno → #<?= $turno_actual+1 ?>
                        </button>
                    </form>
                    <!-- Retroceder -->
                    <?php if ($turno_actual > 1): ?>
                    <form method="post" onsubmit="return confirm('¿Retroceder al turno anterior?')">
                        <button type="submit" name="retroceder_turno" class="btn btn-outline-warning w-100 py-2" style="border-radius:12px">
                            <i class="bi bi-arrow-left-circle me-2"></i>Retroceder a #<?= $turno_actual-1 ?>
                        </button>
                    </form>
                    <?php endif; ?>
                    <!-- Ajustar -->
                    <button type="button" class="btn btn-outline-secondary w-100 py-2" style="border-radius:12px"
                            data-bs-toggle="modal" data-bs-target="#ajustarModal">
                        <i class="bi bi-sliders me-2"></i>Ajuste Manual
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- INFO -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex gap-3 flex-wrap">
                <div class="d-flex align-items-start gap-2" style="flex:1;min-width:200px">
                    <i class="bi bi-globe text-primary mt-1"></i>
                    <div><strong>Turno Global</strong><br><small class="text-muted">Todos los países en el mismo turno</small></div>
                </div>
                <div class="d-flex align-items-start gap-2" style="flex:1;min-width:200px">
                    <i class="bi bi-arrow-repeat text-success mt-1"></i>
                    <div><strong>Cambio Automático</strong><br><small class="text-muted">Al cambiar, se actualiza TODAS las cartillas</small></div>
                </div>
                <div class="d-flex align-items-start gap-2" style="flex:1;min-width:200px">
                    <i class="bi bi-clock-history text-info mt-1"></i>
                    <div><strong>Historial</strong><br><small class="text-muted">Todos los cambios quedan registrados</small></div>
                </div>
            </div>
        </div>
    </div>

    <!-- HISTORIAL -->
    <div class="card">
        <div class="card-header"><i class="bi bi-list-ul me-2"></i><strong>Historial de Cambios</strong></div>
        <div class="card-body p-0">
            <?php if (!empty($historial)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Fecha</th><th>Acción</th><th>Anterior</th><th>Nuevo</th><th>Usuario</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach($historial as $h):
                        $bc=['avanzar'=>'success','anular'=>'warning','ajustar'=>'info'][$h['tipo_cambio']]??'secondary';
                        $ic=['avanzar'=>'arrow-right-circle','anular'=>'arrow-left-circle','ajustar'=>'sliders'][$h['tipo_cambio']]??'clock';
                    ?>
                    <tr>
                        <td style="font-size:.8rem;color:var(--text-secondary)"><?= date('d/m/Y H:i:s', strtotime($h['fecha_cambio'])) ?></td>
                        <td><span class="badge bg-<?= $bc ?>"><i class="bi bi-<?= $ic ?> me-1"></i><?= ucfirst($h['tipo_cambio']) ?></span></td>
                        <td><span class="badge" style="background:var(--glass-bg);color:var(--text-primary);border:1px solid var(--border-color)">#<?= $h['turno_anterior'] ?></span></td>
                        <td><span class="badge bg-primary">#<?= $h['turno_nuevo'] ?></span></td>
                        <td style="font-size:.875rem"><?= htmlspecialchars($h['username']??'Sistema') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5" style="color:var(--text-secondary)">
                <i class="bi bi-inbox" style="font-size:2rem"></i>
                <div class="mt-2">Sin historial aún</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Ajustar -->
<div class="modal fade" id="ajustarModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header" style="background:linear-gradient(135deg,#0d6efd,#6610f2);color:#fff">
                    <h5 class="modal-title"><i class="bi bi-sliders me-2"></i>Ajuste Manual de Turno</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nuevo número de turno</label>
                        <input type="number" name="turno_nuevo" class="form-control form-control-lg" min="1" value="<?= $turno_actual ?>" required>
                        <small class="text-muted">Turno actual: <strong>#<?= $turno_actual ?></strong></small>
                    </div>
                    <div class="alert alert-warning py-2" style="font-size:.875rem">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Usa esta opción solo para correcciones. El cambio se registrará en el historial.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="ajustar_turno" class="btn btn-primary">Aplicar Ajuste</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
