<?php
/**
 * Migration script: Convert existing flag images to PNG format with new naming convention
 * Format: userid_countryname.png
 */

require_once 'config/db.php';
require_once 'includes/functions.php';

echo "=== MIGRACIÓN DE BANDERAS A PNG ===\n\n";

// Get all countries with users and flags
$stmt = $conn->query("
    SELECT u.id_usuario, u.username, p.id_pais, p.nombre_pais, p.bandera_url
    FROM usuarios u
    JOIN paises p ON u.id_pais = p.id_pais
    WHERE p.bandera_url IS NOT NULL AND p.bandera_url != ''
");

$countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($countries);
$converted = 0;
$skipped = 0;
$errors = 0;

echo "Encontradas $total banderas para procesar...\n\n";

foreach ($countries as $country) {
    $user_id = $country['id_usuario'];
    $old_url = $country['bandera_url'];
    $old_path = __DIR__ . '/' . $old_url;
    $nombre_pais = $country['nombre_pais'];
    $username = $country['username'];
    
    echo "Procesando: $username → $nombre_pais\n";
    echo "  Archivo actual: $old_url\n";
    
    // Skip if file doesn't exist
    if (!file_exists($old_path)) {
        echo "  ⚠ Archivo no existe, saltando...\n\n";
        $skipped++;
        continue;
    }
    
    // Skip default flags
    if (strpos($old_url, 'default_flag') !== false) {
        echo "  ⚠ Bandera por defecto, saltando...\n\n";
        $skipped++;
        continue;
    }
    
    // Get image info
    $info = @getimagesize($old_path);
    if ($info === false) {
        echo "  ✗ No es una imagen válida\n\n";
        $errors++;
        continue;
    }
    
    // Load source image based on type
    $source = null;
    switch ($info['mime']) {
        case 'image/jpeg':
            $source = @imagecreatefromjpeg($old_path);
            break;
        case 'image/png':
            $source = @imagecreatefrompng($old_path);
            break;
        case 'image/gif':
            $source = @imagecreatefromgif($old_path);
            break;
        case 'image/webp':
            $source = @imagecreatefromwebp($old_path);
            break;
        default:
            echo "  ✗ Formato no soportado: {$info['mime']}\n\n";
            $errors++;
            continue 2;
    }
    
    if (!$source) {
        echo "  ✗ Error al cargar imagen\n\n";
        $errors++;
        continue;
    }
    
    // Generate new filename
    $nombre_sanitizado = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombre_pais);
    $new_filename = $user_id . '_' . $nombre_sanitizado . '.png';
    $new_path = __DIR__ . '/assets/uploads/banderas/' . $new_filename;
    $new_url = 'assets/uploads/banderas/' . $new_filename;
    
    // Save as PNG
    if (imagepng($source, $new_path, 9)) {
        imagedestroy($source);
        
        // Update database
        $update = $conn->prepare("UPDATE paises SET bandera_url = :url WHERE id_pais = :id");
        $update->execute([':url' => $new_url, ':id' => $country['id_pais']]);
        
        // Delete old file if different
        if ($old_path != $new_path && file_exists($old_path)) {
            unlink($old_path);
        }
        
        echo "  ✓ Convertida a: $new_filename\n\n";
        $converted++;
    } else {
        imagedestroy($source);
        echo "  ✗ Error al guardar PNG\n\n";
        $errors++;
    }
}

echo "=== RESUMEN ===\n";
echo "Total: $total\n";
echo "Convertidas: $converted\n";
echo "Saltadas: $skipped\n";
echo "Errores: $errors\n";
echo "\n¡Migración completada!\n";
