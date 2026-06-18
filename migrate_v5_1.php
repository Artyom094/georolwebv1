<?php
/**
 * Migration V5.1: Requisitos para Investigaciones
 * Ejecutar: php migrate_v5_1.php
 */

require_once 'config/db.php';

echo "Iniciando migración V5.1: Sistema de Requisitos...\n\n";

try {
    $sql = file_get_contents(__DIR__ . '/SQL/SQL V5.1.sql');
    $conn->exec($sql);
    
    echo "✓ Tabla investigaciones_requisitos creada exitosamente\n\n";
    echo "¡Migración V5.1 completada!\n\n";
    echo "Ahora puedes:\n";
    echo "1. Definir requisitos previos en gm_research.php\n";
    echo "2. El sistema validará automáticamente el orden de desbloqueo\n";
    echo "3. Los países solo podrán investigar tecnologías si cumplen requisitos\n";
    
} catch (Exception $e) {
    echo "\n✗ Error en migración: " . $e->getMessage() . "\n";
    exit(1);
}
