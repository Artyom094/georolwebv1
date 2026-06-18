-- ============================================================
-- GEOROL - Script de Reset Completo
-- Limpia todos los datos, elimina tablas no usadas,
-- y crea el usuario admin con contraseña 123456
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. ELIMINAR TABLAS NO USADAS EN LA APP
-- ============================================================

-- `miembros_alianza`: no se usa, la app usa id_alianza_militar/economica en paises
DROP TABLE IF EXISTS `miembros_alianza`;

-- `notificaciones`: no hay código que la utilice en ningún .php
DROP TABLE IF EXISTS `notificaciones`;

-- ============================================================
-- 2. LIMPIAR DATOS DE TODAS LAS TABLAS (orden por FK)
-- ============================================================

TRUNCATE TABLE `historial_alianzas`;
TRUNCATE TABLE `historial_enfoques`;
TRUNCATE TABLE `historial_paises`;
TRUNCATE TABLE `historial_turnos`;
TRUNCATE TABLE `alianzas_invitaciones`;
TRUNCATE TABLE `paises_investigaciones`;
TRUNCATE TABLE `investigaciones_requisitos`;
TRUNCATE TABLE `investigaciones`;
TRUNCATE TABLE `cartillas`;
TRUNCATE TABLE `alianzas`;
TRUNCATE TABLE `paises`;
TRUNCATE TABLE `usuarios`;

-- Tablas de catálogo (se conservan sus datos base)
-- enfoques, categorias_investigacion, subcategorias_investigacion, roles → se limpian y reinsertan
TRUNCATE TABLE `enfoques`;
TRUNCATE TABLE `categorias_investigacion`;
TRUNCATE TABLE `subcategorias_investigacion`;
TRUNCATE TABLE `roles`;
TRUNCATE TABLE `turno_global`;

-- ============================================================
-- 3. REINSERTAR DATOS DE CATÁLOGO (roles, enfoques, etc.)
-- ============================================================

-- Roles
INSERT INTO `roles` (`id_rol`, `nombre_rol`) VALUES
(1, 'Administrador'),
(2, 'GM'),
(3, 'Auditor'),
(4, 'Participante');

-- Turno global (reinicia en turno 1)
INSERT INTO `turno_global` (`id`, `turno_actual`) VALUES (1, 1);

-- Enfoques nacionales
INSERT INTO `enfoques` (`id_enfoque`, `nombre_enfoque`, `tipo_enfoque`, `descripcion`, `multiplicador_ic`, `multiplicador_im`, `multiplicador_it`, `bonus_defensa`, `cooldown_guerra_reducido`, `activo`) VALUES
(1, 'Puño de Acero',          'Atacante', 'Cooldown reducido a 1 turno (alternado 3-1-3-1). Cada guerra ofensiva consecutiva genera penalización progresiva en IM.', NULL, NULL, NULL, 0, 1, 1),
(2, 'Muralla Inquebrantable', 'Defensor', 'Bonus de +10 a la calculadora en las batallas del primer turno de cada guerra defensiva.', NULL, NULL, NULL, 10, 0, 1),
(3, 'Oro Antes que Balas',    'IC',       'IC: x5 → x3. Penalización: Producción IM x5 → x3', '3.0', '3.0', NULL, 0, 0, 1),
(4, 'Forja de Guerra',        'IM',       'IM: x2 → x3. Penalización: Producción IC x2 → x1.4', '1.4', '7.0', NULL, 0, 0, 1),
(5, 'Guerra del Futuro',      'IT',       'IT: x8 → x11. Penalización: Producción IM x5 → x3.5', NULL, '3.5', '11.0', 0, 0, 1);

-- Categorías de investigación
INSERT INTO `categorias_investigacion` (`id_categoria`, `nombre_categoria`, `orden`) VALUES
(1, 'Aéreo',             1),
(2, 'Terrestre',         2),
(3, 'Marítimo',          3),
(4, 'Unidades Especiales', 4);

-- Subcategorías de investigación
INSERT INTO `subcategorias_investigacion` (`id_subcategoria`, `id_categoria`, `nombre_subcategoria`, `orden`) VALUES
(1, 2, 'Infantería',          1),
(2, 2, 'Blindados',           2),
(3, 2, 'Artillería',          3),
(4, 2, 'Defensa Antiaérea',   4),
(5, 3, 'Submarinos',          1),
(6, 3, 'Fragatas y Corbetas', 2),
(7, 3, 'Destructores y Cruceros', 3),
(8, 3, 'Portaaviones',        4),
(9, 3, 'Defensa Costera',     5);

-- ============================================================
-- 4. CREAR USUARIO ADMINISTRADOR
-- Usuario: admin | Contraseña: 123456
-- ============================================================

INSERT INTO `usuarios` (`id_usuario`, `username`, `password_hash`, `discord_user`, `id_rol`, `id_pais`, `activo`, `debe_cambiar_password`) VALUES
(1, 'admin', '$2y$12$Kfe1qzv4RcZa5X8YLsE8UupLwTpZ6zjFft9b1GeTP1gvT0dEEgZ2C', NULL, 1, NULL, 1, 0);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Listo. Estado final:
--   - Tablas eliminadas: miembros_alianza, notificaciones
--   - Datos limpios en todas las tablas operativas
--   - Catálogos restaurados: roles, enfoques, categorias, subcategorias
--   - Turno global reiniciado en 1
--   - Usuario admin creado (pass: 123456)
-- ============================================================

-- ============================================================
-- MIGRACIONES ACUMULADAS (aplicar después del reset si BD es nueva)
-- ============================================================

-- V9: Fotos de perfil de usuario
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS avatar_url VARCHAR(255) NULL DEFAULT NULL
    COMMENT 'Ruta relativa a assets/uploads/avatars/ - foto de perfil del usuario';
