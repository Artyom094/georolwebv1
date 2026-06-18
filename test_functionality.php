<?php
/**
 * Test script to verify:
 * 1. PNG conversion functionality
 * 2. Filename format (userid_countryname.png)
 * 3. Country history archiving
 */

require_once 'config/db.php';
require_once 'includes/functions.php';

echo "=== TEST DE FUNCIONALIDAD ===\n\n";

// Test 1: Check database structure
echo "1. Verificando estructura de base de datos...\n";
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'historial_paises'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ Tabla historial_paises existe\n";
    } else {
        echo "   ✗ Tabla historial_paises NO existe\n";
    }
    
    $stmt = $conn->query("DESCRIBE historial_paises");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $required = ['id_historial', 'id_usuario', 'id_pais', 'nombre_pais_historico', 'bandera_url_historica', 'fecha_inicio', 'fecha_fin', 'razon_cambio', 'cartilla_turno_global', 'cartilla_snapshot_json', 'cartilla_reporte_texto'];
    $missing = array_diff($required, $columns);
    if (empty($missing)) {
        echo "   ✓ Todas las columnas necesarias están presentes\n";
    } else {
        echo "   ✗ Faltan columnas: " . implode(', ', $missing) . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test 2: Check functions exist
echo "\n2. Verificando funciones...\n";
$functions = ['processAndSaveFlagImage', 'archiveCountryToHistory', 'getUserCountryHistory'];
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "   ✓ Función $func existe\n";
    } else {
        echo "   ✗ Función $func NO existe\n";
    }
}

// Test 3: Check GD Library
echo "\n3. Verificando GD Library...\n";
if (function_exists('imagecreatefromjpeg')) {
    echo "   ✓ GD Library está disponible\n";
    $gd_info = gd_info();
    echo "   - JPEG Support: " . ($gd_info['JPEG Support'] ? 'Sí' : 'No') . "\n";
    echo "   - PNG Support: " . ($gd_info['PNG Support'] ? 'Sí' : 'No') . "\n";
    echo "   - GIF Read Support: " . ($gd_info['GIF Read Support'] ? 'Sí' : 'No') . "\n";
    echo "   - WebP Support: " . (isset($gd_info['WebP Support']) && $gd_info['WebP Support'] ? 'Sí' : 'No') . "\n";
} else {
    echo "   ✗ GD Library NO está disponible\n";
}

// Test 4: Check upload directory
echo "\n4. Verificando directorio de uploads...\n";
$upload_dir = __DIR__ . '/assets/uploads/banderas/';
if (is_dir($upload_dir)) {
    echo "   ✓ Directorio existe: $upload_dir\n";
    $perms = fileperms($upload_dir);
    echo "   - Permisos: " . substr(sprintf('%o', $perms), -4) . "\n";
    if (is_writable($upload_dir)) {
        echo "   ✓ Directorio es escribible\n";
    } else {
        echo "   ✗ Directorio NO es escribible\n";
    }
} else {
    echo "   ✗ Directorio NO existe\n";
}

// Test 5: Check existing countries with users
echo "\n5. Verificando países con usuarios asignados...\n";
try {
    $stmt = $conn->query("
        SELECT u.id_usuario, u.username, p.id_pais, p.nombre_pais, p.bandera_url
        FROM usuarios u
        LEFT JOIN paises p ON u.id_pais = p.id_pais
        WHERE u.id_pais IS NOT NULL
        LIMIT 5
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($users) > 0) {
        echo "   ✓ Usuarios con países asignados: " . count($users) . "\n";
        foreach ($users as $user) {
            $expected_filename = $user['id_usuario'] . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $user['nombre_pais']) . '.png';
            echo "   - Usuario: {$user['username']} → País: {$user['nombre_pais']}\n";
            echo "     Nombre esperado: $expected_filename\n";
            if ($user['bandera_url']) {
                echo "     Nombre actual: " . basename($user['bandera_url']) . "\n";
            }
        }
    } else {
        echo "   ℹ No hay usuarios con países asignados\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test 6: Check existing history
echo "\n6. Verificando historial existente...\n";
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM historial_paises");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   ✓ Registros en historial: $count\n";
    
    if ($count > 0) {
        $stmt = $conn->query("
            SELECT u.username, h.nombre_pais_historico, h.fecha_inicio, h.fecha_fin, h.razon_cambio
            FROM historial_paises h
            JOIN usuarios u ON h.id_usuario = u.id_usuario
            ORDER BY h.fecha_fin DESC
            LIMIT 5
        ");
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($history as $record) {
            echo "   - {$record['username']}: {$record['nombre_pais_historico']} ({$record['razon_cambio']})\n";
            echo "     {$record['fecha_inicio']} → {$record['fecha_fin']}\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEL TEST ===\n";
