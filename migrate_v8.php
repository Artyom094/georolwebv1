<?php
require_once 'config/db.php';

echo "=== MIGRACIÓN V8: Sistema de Invitaciones y Aprobación de Alianzas ===\n\n";

try {
    // 1. Agregar campo aprobada
    $conn->exec("ALTER TABLE alianzas ADD COLUMN aprobada TINYINT(1) DEFAULT 0 AFTER id_fundador");
    echo "✓ Campo 'aprobada' agregado a tabla alianzas\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "⚠ Campo 'aprobada' ya existe en tabla alianzas\n";
    } else {
        throw $e;
    }
}

try {
    // 2. Crear tabla de invitaciones
    $conn->exec("
        CREATE TABLE IF NOT EXISTS alianzas_invitaciones (
            id_invitacion INT AUTO_INCREMENT PRIMARY KEY,
            id_alianza INT NOT NULL,
            id_pais INT NOT NULL,
            id_invitador INT NOT NULL,
            estado ENUM('pendiente', 'aceptada', 'rechazada') DEFAULT 'pendiente',
            fecha_invitacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_respuesta TIMESTAMP NULL,
            FOREIGN KEY (id_alianza) REFERENCES alianzas(id_alianza) ON DELETE CASCADE,
            FOREIGN KEY (id_pais) REFERENCES paises(id_pais) ON DELETE CASCADE,
            FOREIGN KEY (id_invitador) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
            UNIQUE KEY unica_invitacion (id_alianza, id_pais, estado)
        )
    ");
    echo "✓ Tabla alianzas_invitaciones creada exitosamente\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "⚠ Tabla alianzas_invitaciones ya existe\n";
    } else {
        throw $e;
    }
}

// 3. Aprobar todas las alianzas existentes (retrocompatibilidad)
$conn->exec("UPDATE alianzas SET aprobada = 1 WHERE aprobada = 0");
echo "✓ Alianzas existentes aprobadas automáticamente\n";

echo "\n=== MIGRACIÓN V8 COMPLETADA ===\n";
