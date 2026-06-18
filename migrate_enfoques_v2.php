<?php
require_once 'config/db.php';

$sql = <<<SQL
DELETE FROM historial_enfoques;
DELETE FROM enfoques;
ALTER TABLE historial_enfoques AUTO_INCREMENT = 1;
ALTER TABLE enfoques AUTO_INCREMENT = 1;
UPDATE paises SET id_enfoque_activo = NULL, fecha_ultimo_cambio_enfoque = NULL;
ALTER TABLE `enfoques` MODIFY `tipo_enfoque` enum('Comunismo','Fascismo','Democracia','Monarquía','Teocracia','Socialismo','Liberalismo','Anarquismo','Autoritarismo','Oligarquía') COLLATE utf8mb4_unicode_ci NOT NULL;
INSERT INTO `enfoques` (`id_enfoque`, `nombre_enfoque`, `tipo_enfoque`, `descripcion`, `multiplicador_ic`, `multiplicador_im`, `multiplicador_it`, `bonus_defensa`, `cooldown_guerra_reducido`, `activo`, `fecha_creacion`) VALUES
(1, 'Comunismo', 'Comunismo', 'Enfoque de Colectivización Total.', 1.2, 7.0, 9.0, 15, 0, 1, NOW()),
(2, 'Fascismo', 'Fascismo', 'Enfoque de Expansión Nacionalista.', 1.5, 6.5, NULL, 5, 1, 1, NOW()),
(3, 'Democracia', 'Democracia', 'Enfoque de Legitimidad y Alianzas.', 2.5, 1.5, NULL, 0, 0, 1, NOW()),
(4, 'Monarquía', 'Monarquía', 'Enfoque de Soberanía Hereditaria.', 2.4, NULL, 5.0, 5, 0, 1, NOW()),
(5, 'Teocracia', 'Teocracia', 'Enfoque de Mandato Divino.', 1.5, NULL, 6.0, 10, 0, 1, NOW()),
(6, 'Socialismo', 'Socialismo', 'Enfoque de Bienestar y Planificación Mixta.', 2.3, 6.0, 7.0, 0, 0, 1, NOW()),
(7, 'Liberalismo', 'Liberalismo', 'Enfoque de Libre Mercado Global.', 3.0, 1.0, 10.0, 0, 0, 1, NOW()),
(8, 'Anarquismo', 'Anarquismo', 'Enfoque de Resistencia Descentralizada.', 1.0, 3.0, NULL, 15, 0, 1, NOW()),
(9, 'Autoritarismo', 'Autoritarismo', 'Enfoque de Control Total del Estado.', 1.5, 6.5, 9.0, 0, 1, 1, NOW()),
(10, 'Oligarquía', 'Oligarquía', 'Enfoque de Poder Concentrado en Élites.', 2.5, 3.0, NULL, 0, 0, 1, NOW());
SQL;

try {
    $conn->exec($sql);
    echo "✓ Migración v2.0 completada exitosamente\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
