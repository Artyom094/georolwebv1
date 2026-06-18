<?php
/**
 * Migration V5: Sistema de Investigaciones
 * Ejecutar: php migrate_v5.php
 */

require_once 'config/db.php';

echo "Iniciando migración V5: Sistema de Investigaciones...\n\n";

try {
    // Read and execute SQL file
    $sql = file_get_contents(__DIR__ . '/SQL/SQL V5.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && stripos($statement, 'CREATE TABLE') !== false || 
            stripos($statement, 'INSERT INTO') !== false) {
            try {
                $conn->exec($statement);
                echo ".";
            } catch (PDOException $e) {
                // Skip if table already exists
                if (strpos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
            }
        }
    }
    
    echo "\n\n✓ Tablas de investigación creadas exitosamente\n";
    echo "✓ Categorías iniciales insertadas\n";
    echo "✓ Subcategorías de ejemplo creadas\n\n";
    echo "¡Migración V5 completada!\n\n";
    echo "Próximos pasos:\n";
    echo "1. Accede a gm_research.php para gestionar investigaciones\n";
    echo "2. Agrega investigaciones específicas para cada categoría\n";
    echo "3. Los países podrán investigar desde view_country.php\n";
    
} catch (Exception $e) {
    echo "\n✗ Error en migración: " . $e->getMessage() . "\n";
    exit(1);
}
