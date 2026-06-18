<?php
/**
 * Migration V6: Sistema de Turnos
 * Ejecutar: php migrate_v6.php
 */

require_once 'config/db.php';

echo "Iniciando migración V6: Sistema de Turnos...\n\n";

try {
    $sql = file_get_contents(__DIR__ . '/SQL/SQL V6.sql');
    
    // Split statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $conn->exec($statement);
                echo ".";
            } catch (PDOException $e) {
                // Skip if already exists
                if (strpos($e->getMessage(), 'Duplicate column') === false && 
                    strpos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
            }
        }
    }
    
    echo "\n\n✓ Campo turno_actual agregado a tabla paises\n";
    echo "✓ Tabla historial_turnos creada exitosamente\n\n";
    echo "¡Migración V6 completada!\n\n";
    echo "Ahora puedes:\n";
    echo "1. Los GMs pueden gestionar turnos en view_country.php\n";
    echo "2. Los participantes ven su turno pero no pueden editarlo\n";
    echo "3. Historial completo de cambios de turno registrado\n";
    
} catch (Exception $e) {
    echo "\n✗ Error en migración: " . $e->getMessage() . "\n";
    exit(1);
}
