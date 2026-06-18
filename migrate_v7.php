<?php
/**
 * Migration V7: Sistema de Alianzas
 * Ejecutar: php migrate_v7.php
 */

require_once 'config/db.php';

echo "Iniciando migración V7: Sistema de Alianzas...\n\n";

try {
    $sql = file_get_contents(__DIR__ . '/SQL/SQL V7.sql');
    
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $conn->exec($statement);
                echo ".";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column') === false && 
                    strpos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
            }
        }
    }
    
    echo "\n\n✓ Tabla alianzas creada exitosamente\n";
    echo "✓ Campo id_alianza agregado a tabla paises\n";
    echo "✓ Tabla historial_alianzas creada exitosamente\n\n";
    echo "¡Migración V7 completada!\n\n";
    echo "Ahora:\n";
    echo "1. Los usuarios pueden crear alianzas con nombre y logo\n";
    echo "2. Los países pueden unirse a alianzas\n";
    echo "3. Historial completo de movimientos entre alianzas\n";
    
} catch (Exception $e) {
    echo "\n✗ Error en migración: " . $e->getMessage() . "\n";
    exit(1);
}
