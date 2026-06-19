<?php
/**
 * Migración V11 — Polymarket
 * Acceder UNA SOLA VEZ como admin: /georol/migrate_v11_polymarket.php
 * Luego borrar este archivo.
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['id_rol'] != 1) {
    die("⛔ Solo admins pueden ejecutar migraciones.");
}
require_once 'config/db.php';
require_once 'includes/functions.php';

$steps = [];

try {
    ensurePolymarketTables($conn);
    $steps[] = ['ok', 'Tablas de <code>Polymarket</code> creadas o verificadas correctamente.'];
} catch (Throwable $e) {
    $steps[] = ['error', 'Error al crear las tablas: ' . $e->getMessage()];
}

$icon = ['ok' => '✅', 'skip' => '⏭️', 'error' => '❌'];
$clr  = ['ok' => 'success', 'skip' => 'secondary', 'error' => 'danger'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración V11 · Georol</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="p-4">
<div class="container" style="max-width:720px">
    <div class="card">
        <div class="card-header" style="background:linear-gradient(135deg,#0d6efd,#198754);color:#fff">
            <h5 class="mb-0">🔧 Migración V11 — Polymarket</h5>
        </div>
        <div class="card-body">
            <?php foreach ($steps as [$type, $msg]): ?>
            <div class="alert alert-<?= $clr[$type] ?> py-2 mb-2" style="font-size:.875rem">
                <?= $icon[$type] ?> <?= $msg ?>
            </div>
            <?php endforeach; ?>

            <hr>
            <p class="text-muted small mb-3">
                <i class="bi bi-info-circle"></i>
                Migración completada. <strong>Borra este archivo</strong> del servidor una vez confirmado que todo funciona.
            </p>
            <div class="d-flex gap-2 flex-wrap">
                <a href="polymarket.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-graph-up-arrow me-1"></i>Ver Polymarket
                </a>
                <a href="gm_polymarket.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-shield-check me-1"></i>Panel GM
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
