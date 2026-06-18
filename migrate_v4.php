<?php
// Migration V4 - Historial de Países
require_once 'config/db.php';

try {
    echo "Iniciando migración V4: Historial de Países...\n\n";
    
    // Create historial_paises table
    $sql = "CREATE TABLE IF NOT EXISTS historial_paises (
        id_historial INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        id_pais INT NOT NULL,
        nombre_pais_historico VARCHAR(100) NOT NULL,
        bandera_url_historica VARCHAR(255),
        fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
        fecha_fin DATETIME DEFAULT CURRENT_TIMESTAMP,
        razon_cambio VARCHAR(255) DEFAULT 'Invasión/Cambio de país',
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
        INDEX idx_usuario (id_usuario),
        INDEX idx_pais (id_pais)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->exec($sql);
    echo "✓ Tabla historial_paises creada exitosamente\n";
    
    echo "\n¡Migración V4 completada!\n";
    
} catch(PDOException $e) {
    echo "Error en migración: " . $e->getMessage() . "\n";
}
