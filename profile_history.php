<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

require_once 'config/db.php';
require_once 'includes/functions.php';

$page_title = 'Historial de Países';
$user_id = $_SESSION['user_id'];

// Get user's country history
$history = getUserCountryHistory($conn, $user_id);

include 'includes/header.php';
?>

<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="bi bi-clock-history me-2"></i>Historial de Países</h4>
                    <a href="profile.php" class="btn btn-sm btn-light">
                        <i class="bi bi-arrow-left"></i> Volver al Perfil
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($history)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            No tienes historial de países anteriores. Este es tu primer país.
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($history as $index => $record): ?>
                                <div class="timeline-item mb-4 pb-4 <?= $index < count($history) - 1 ? 'border-bottom' : '' ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-2 text-center">
                                            <?php if (!empty($record['bandera_url_historica'])): ?>
                                                <img src="<?= htmlspecialchars($record['bandera_url_historica']) ?>" 
                                                     alt="Bandera" 
                                                     class="img-fluid rounded shadow-sm"
                                                     style="max-height: 80px;">
                                            <?php else: ?>
                                                <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" 
                                                     style="height: 80px;">
                                                    <i class="bi bi-flag-fill" style="font-size: 2rem;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-10">
                                            <h5 class="mb-2">
                                                <i class="bi bi-geo-alt-fill text-primary me-2"></i>
                                                <?= htmlspecialchars($record['nombre_pais_historico']) ?>
                                            </h5>
                                            <div class="text-muted small">
                                                <div class="mb-1">
                                                    <i class="bi bi-calendar-check me-1"></i>
                                                    <strong>Inicio:</strong> 
                                                    <?= date('d/m/Y H:i', strtotime($record['fecha_inicio'])) ?>
                                                </div>
                                                <div class="mb-1">
                                                    <i class="bi bi-calendar-x me-1"></i>
                                                    <strong>Fin:</strong> 
                                                    <?= date('d/m/Y H:i', strtotime($record['fecha_fin'])) ?>
                                                </div>
                                                <?php 
                                                $inicio = new DateTime($record['fecha_inicio']);
                                                $fin = new DateTime($record['fecha_fin']);
                                                $diff = $inicio->diff($fin);
                                                $duracion = '';
                                                if ($diff->y > 0) $duracion .= $diff->y . ' año(s) ';
                                                if ($diff->m > 0) $duracion .= $diff->m . ' mes(es) ';
                                                if ($diff->d > 0) $duracion .= $diff->d . ' día(s) ';
                                                if (empty($duracion)) $duracion = 'Menos de un día';
                                                ?>
                                                <div class="mb-2">
                                                    <i class="bi bi-hourglass-split me-1"></i>
                                                    <strong>Duración:</strong> 
                                                    <?= trim($duracion) ?>
                                                </div>
                                                <?php if (!empty($record['razon_cambio'])): ?>
                                                    <div class="badge bg-info text-dark">
                                                        <i class="bi bi-info-circle me-1"></i>
                                                        <?= htmlspecialchars($record['razon_cambio']) ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($record['cartilla_reporte_texto'])): ?>
                                                    <div class="mt-3">
                                                        <details>
                                                            <summary class="fw-semibold text-primary">Ver última cartilla archivada</summary>
                                                            <div class="mt-3 row g-2">
                                                                <div class="col-md-4">
                                                                    <div class="border rounded p-2 bg-light">
                                                                        <div class="small text-muted">Turno archivado</div>
                                                                        <strong>#<?= intval($record['cartilla_turno_global'] ?? 0) ?></strong>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-8">
                                                                    <div class="border rounded p-2 bg-light">
                                                                        <div class="small text-muted">Estado</div>
                                                                        <div><?= htmlspecialchars($record['razon_cambio'] ?: 'País archivado') ?></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <pre class="mt-3 p-3 bg-white border rounded" style="white-space:pre-wrap;word-break:break-word;max-height:360px;overflow:auto"><?= htmlspecialchars($record['cartilla_reporte_texto']) ?></pre>
                                                        </details>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="alert alert-secondary mt-4">
                            <i class="bi bi-bar-chart me-2"></i>
                            <strong>Total de países:</strong> <?= count($history) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
