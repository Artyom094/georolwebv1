<?php
/**
 * Migración V10 — Historial completo de cartillas por turno
 * Acceder UNA SOLA VEZ como admin: /georol/migrate_v10.php
 * Luego borrar este archivo.
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['id_rol'] != 1) {
    die("⛔ Solo admins pueden ejecutar migraciones.");
}
require_once 'config/db.php';

$steps = [];

try {
    $conn->exec("CREATE TABLE IF NOT EXISTS cartilla_historial (
        id_historial int NOT NULL AUTO_INCREMENT,
        id_pais int NOT NULL,
        turno_global int NOT NULL,
        id_usuario int DEFAULT NULL,
        accion varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'guardado',
        nombre_pais varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
        bandera_url varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        total_tipos int NOT NULL DEFAULT '0',
        total_unidades int NOT NULL DEFAULT '0',
        total_produccion_ic int NOT NULL DEFAULT '0',
        total_produccion_im int NOT NULL DEFAULT '0',
        total_produccion_it int NOT NULL DEFAULT '0',
        total_mantenimiento_im int NOT NULL DEFAULT '0',
        balance_im int NOT NULL DEFAULT '0',
        unidades_legado longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        snapshot_json longtext COLLATE utf8mb4_unicode_ci NOT NULL,
        reporte_texto longtext COLLATE utf8mb4_unicode_ci NOT NULL,
        created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id_historial),
        UNIQUE KEY uniq_pais_turno (id_pais, turno_global),
        KEY idx_turno_global (turno_global),
        KEY idx_id_pais (id_pais)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $steps[] = ['ok', 'Tabla <code>cartilla_historial</code> creada o verificada correctamente.'];
} catch (PDOException $e) {
    $steps[] = ['error', 'Error al crear la tabla: ' . $e->getMessage()];
}

$icon = ['ok' => '✅', 'skip' => '⏭️', 'error' => '❌'];
$clr  = ['ok' => 'success', 'skip' => 'secondary', 'error' => 'danger'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración V10 · Georol</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="p-4">
<div class="container" style="max-width:720px">
    <div class="card">
        <div class="card-header" style="background:linear-gradient(135deg,#0d6efd,#198754);color:#fff">
            <h5 class="mb-0">🔧 Migración V10 — Historial de Cartillas</h5>
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
            <div class="d-flex gap-2">
                <a href="admin_cartilla_historial.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-clock-history me-1"></i>Ver Historial
                </a>
                <a href="admin_cartilla.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-clipboard2-data-fill me-1"></i>Cartillas
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
