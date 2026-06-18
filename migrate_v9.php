<?php
/**
 * Migración V9 — Fotos de perfil de usuario
 * Acceder UNA SOLA VEZ como admin: /georol/migrate_v9.php
 * Luego borrar este archivo.
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['id_rol'] != 1) {
    die("⛔ Solo admins pueden ejecutar migraciones.");
}
require_once 'config/db.php';

$steps = [];

// 1. Agregar columna avatar_url a usuarios
try {
    $conn->exec("ALTER TABLE usuarios ADD COLUMN avatar_url VARCHAR(255) NULL DEFAULT NULL COMMENT 'Ruta relativa a assets/uploads/avatars/'");
    $steps[] = ['ok', 'Columna <code>avatar_url</code> añadida a <code>usuarios</code>.'];
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        $steps[] = ['skip', 'Columna <code>avatar_url</code> ya existía — sin cambios.'];
    } else {
        $steps[] = ['error', 'Error al añadir columna: ' . $e->getMessage()];
    }
}

// 2. Crear directorio de avatares
$dir = __DIR__ . '/assets/uploads/avatars/';
if (!is_dir($dir)) {
    if (mkdir($dir, 0775, true)) {
        $steps[] = ['ok', 'Directorio <code>assets/uploads/avatars/</code> creado.'];
    } else {
        $steps[] = ['error', 'No se pudo crear el directorio. Créalo manualmente con permisos 775.'];
    }
} else {
    $steps[] = ['skip', 'Directorio <code>assets/uploads/avatars/</code> ya existía.'];
}

// 3. Verificar permisos de escritura
if (is_writable($dir)) {
    $steps[] = ['ok', 'Directorio de avatares con permisos de escritura correctos ✅'];
} else {
    $steps[] = ['error', "Sin permisos de escritura en <code>$dir</code>. Ejecuta: <code>chmod 775 $dir</code>"];
}

$icon = ['ok' => '✅', 'skip' => '⏭️', 'error' => '❌'];
$clr  = ['ok' => 'success', 'skip' => 'secondary', 'error' => 'danger'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración V9 · Georol</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="p-4">
<div class="container" style="max-width:640px">
    <div class="card">
        <div class="card-header" style="background:linear-gradient(135deg,#6610f2,#0d6efd);color:#fff">
            <h5 class="mb-0">🔧 Migración V9 — Fotos de Perfil</h5>
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
                <a href="profile.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-person-circle me-1"></i>Ir a Mi Perfil
                </a>
                <a href="index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-house me-1"></i>Inicio
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
