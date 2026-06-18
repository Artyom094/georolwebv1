-- phpMyAdmin SQL Dump
-- version 4.9.11
-- https://www.phpmyadmin.net/
--
-- Servidor: db5019448316.hosting-data.io
-- Tiempo de generación: 13-05-2026 a las 01:55:47
-- Versión del servidor: 8.0.36
-- Versión de PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `dbs15216161`
--
CREATE DATABASE IF NOT EXISTS `dbs15216161` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `dbs15216161`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alianzas`
--

CREATE TABLE `alianzas` (
  `id_alianza` int NOT NULL,
  `nombre_alianza` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_alianza` enum('Militar','Economica') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Militar',
  `logo_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `id_fundador` int DEFAULT NULL,
  `aprobada` tinyint(1) DEFAULT '0' COMMENT '0=Pendiente GM, 1=Aprobada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `alianzas`
--

INSERT INTO `alianzas` (`id_alianza`, `nombre_alianza`, `tipo_alianza`, `logo_url`, `descripcion`, `fecha_creacion`, `id_fundador`, `aprobada`) VALUES
(4, 'Medio orienton', 'Militar', 'assets/uploads/alianzas/Medio_orienton.png', '', '2026-01-25 06:23:47', 28, 1),
(6, 'Unión Europea del Sur', 'Militar', 'assets/uploads/alianzas/Uni__n_Europea_del_Sur.png', 'Solo europeos no niggas', '2026-01-25 06:29:51', 23, 1),
(9, 'Unión Europea del Norte', 'Militar', 'assets/uploads/alianzas/Uni__n_Europea_del_Norte.png', 'Ya se murio', '2026-01-25 16:23:34', 3, 1),
(10, 'Merkezi Asya', 'Economica', 'assets/uploads/alianzas/Merkezi_Asya.png', 'Unión Económica de Asia Central', '2026-01-26 18:16:54', 6, 1),
(12, 'BPRIV', 'Economica', 'assets/uploads/alianzas/BRICV.png', 'Alianza de carácter económico', '2026-01-28 03:47:01', 4, 1),
(14, 'D.Y.L.A.N.', 'Economica', NULL, 'Defence of Young and Liberated Allied Nations', '2026-02-12 04:42:53', 2, 1),
(18, 'Pacto de Cooperación Estratégica Oriental', 'Militar', 'assets/uploads/alianzas/Pacto_de_Cooperaci__n_Estrat__gica_Oriental.png', 'Alianza de caracter militar', '2026-02-17 03:48:21', 4, 1),
(19, 'Globaltech Alliance', 'Economica', 'assets/uploads/alianzas/Globaltech_Alliance.png', '', '2026-02-20 02:34:45', 22, 1),
(20, 'EEES????????', 'Economica', NULL, 'Espacio Económico Europeo del Sur????????', '2026-02-23 05:44:19', 15, 1),
(22, 'SAT', 'Militar', NULL, '', '2026-03-13 03:51:45', 10, 1),
(23, 'FINT', 'Economica', NULL, 'Regresamos pero más jodidos', '2026-03-16 23:58:23', 23, 1),
(24, 'SATCOM', 'Militar', NULL, 'Strategic Allied Treaty Coalition for Operational', '2026-03-19 00:53:25', 31, 1),
(26, 'SATCOM Economic', 'Economica', NULL, 'Rama administrativa y comercial de la SATCOM', '2026-03-19 20:13:32', 31, 1),
(27, 'Beýik ýol', 'Militar', 'assets/uploads/alianzas/Be__ik___ol.png', 'Alianza orientada a comunicar los mares índico y mediterráneo a través de Asia', '2026-03-20 02:03:14', 33, 1),
(28, 'CSAT', 'Militar', NULL, 'Tratado de Alianza Estrategica del Protocolo Canton', '2026-03-24 01:14:12', 19, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alianzas_invitaciones`
--

CREATE TABLE `alianzas_invitaciones` (
  `id_invitacion` int NOT NULL,
  `id_alianza` int NOT NULL,
  `id_pais` int NOT NULL,
  `id_invitador` int NOT NULL,
  `estado` enum('pendiente','aceptada','rechazada') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `fecha_invitacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_respuesta` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `alianzas_invitaciones`
--

INSERT INTO `alianzas_invitaciones` (`id_invitacion`, `id_alianza`, `id_pais`, `id_invitador`, `estado`, `fecha_invitacion`, `fecha_respuesta`) VALUES
(4, 6, 14, 23, 'aceptada', '2026-01-25 06:52:46', '2026-01-25 06:53:25'),
(5, 6, 42, 23, 'aceptada', '2026-01-25 06:53:17', '2026-01-26 12:53:32'),
(6, 6, 27, 23, 'aceptada', '2026-01-25 06:53:51', '2026-01-28 02:48:03'),
(15, 9, 2, 3, 'aceptada', '2026-01-25 16:24:08', '2026-01-25 17:35:28'),
(16, 9, 20, 3, 'aceptada', '2026-01-25 16:24:16', '2026-01-26 02:25:45'),
(18, 6, 20, 23, 'aceptada', '2026-01-25 22:35:21', '2026-01-26 02:26:12'),
(19, 9, 20, 3, 'rechazada', '2026-01-26 16:38:49', '2026-01-28 20:16:55'),
(21, 10, 44, 6, 'aceptada', '2026-01-26 22:28:18', '2026-01-27 15:53:31'),
(24, 12, 16, 4, 'aceptada', '2026-01-28 03:47:59', '2026-01-30 14:57:50'),
(25, 12, 28, 4, 'aceptada', '2026-01-28 03:48:03', '2026-01-29 11:24:24'),
(28, 9, 20, 3, 'pendiente', '2026-01-30 18:55:18', NULL),
(31, 14, 30, 2, 'aceptada', '2026-02-12 04:43:41', '2026-02-12 04:45:40'),
(32, 14, 9, 2, 'rechazada', '2026-02-13 17:57:42', '2026-03-02 04:34:45'),
(35, 19, 12, 22, 'rechazada', '2026-03-02 01:06:05', '2026-03-19 00:52:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cartillas`
--

CREATE TABLE `cartillas` (
  `id_cartilla` int NOT NULL,
  `id_pais` int NOT NULL,
  `f_ic` int DEFAULT '0' COMMENT 'Industrial Capacity',
  `f_im` int DEFAULT '0' COMMENT 'Military Industry',
  `f_it` int DEFAULT '0' COMMENT 'Technology Industry',
  `e_inf` int DEFAULT '0' COMMENT 'Infantería',
  `e_b` int DEFAULT '0' COMMENT 'Blindados',
  `e_ifv` int DEFAULT '0' COMMENT 'Vehículos de Combate',
  `e_arti` int DEFAULT '0' COMMENT 'Artillería',
  `e_aa` int DEFAULT '0' COMMENT 'Anti-Aéreo',
  `e_sam` int DEFAULT '0' COMMENT 'SAM',
  `e_mrl` int DEFAULT '0' COMMENT 'Lanzacohetes Múltiples',
  `a_cazas` int DEFAULT '0' COMMENT 'Cazas',
  `a_ataque` int DEFAULT '0' COMMENT 'Ataque',
  `a_bombarderos` int DEFAULT '0' COMMENT 'Bombarderos',
  `a_helis` int DEFAULT '0' COMMENT 'Helicópteros',
  `a_ent_medio` int DEFAULT '0' COMMENT 'Entrenadores medios (2 IM/u)',
  `a_ent_avanzado` int DEFAULT '0' COMMENT 'Entrenadores avanzados (2 IM/u)',
  `a_cazas_furtivos` int DEFAULT '0' COMMENT 'Cazas furtivos 5a gen (400 IM/u)',
  `a_awacs` int DEFAULT '0' COMMENT 'AWACS (50 IM/u)',
  `n_submarinos` int DEFAULT '0' COMMENT 'Submarinos',
  `n_fragatas` int DEFAULT '0' COMMENT 'Fragatas',
  `n_corbetas` int DEFAULT '0' COMMENT 'Corbetas',
  `n_destructores` int DEFAULT '0' COMMENT 'Destructores',
  `n_cruceros` int DEFAULT '0' COMMENT 'Cruceros',
  `n_portaaviones` int DEFAULT '0' COMMENT 'Portaaviones',
  `n_defensa_costera` int DEFAULT '0' COMMENT 'Defensa Costera',
  `icbm` int DEFAULT '0' COMMENT 'Misiles Balísticos Intercontinentales'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cartillas`
--

INSERT INTO `cartillas` (`id_cartilla`, `id_pais`, `f_ic`, `f_im`, `f_it`, `e_inf`, `e_b`, `e_ifv`, `e_arti`, `e_aa`, `e_sam`, `e_mrl`, `a_cazas`, `a_ataque`, `a_bombarderos`, `a_helis`, `a_ent_medio`, `a_ent_avanzado`, `a_cazas_furtivos`, `a_awacs`, `n_submarinos`, `n_fragatas`, `n_corbetas`, `n_destructores`, `n_cruceros`, `n_portaaviones`, `n_defensa_costera`, `icbm`) VALUES
(1, 1, 60225404, 13017997, 5309, 377379, 267708, 277210, 34241, 51546, 166060, 8209, 1165601, 40343, 10000, 134450, 16980, 0, 125, 5, 1725, 3450, 6971, 950, 637, 691, 71000, 30),
(2, 2, 2901419, 1327021, 2689, 622000, 2757, 90, 22, 6, 3, 1, 2261, 4, 12, 28, 0, 0, 0, 0, 7, 406, 406, 0, 0, 0, 0, 0),
(9, 9, 19590269, 3123330, 3131, 3601, 10002, 37097, 2520, 532, 3739, 3951, 132615, 55216, 164, 11210, 6, 0, 16, 5, 11580, 3386, 657, 2420, 745, 166, 0, 40),
(12, 12, 2147483647, 2147483647, 9348, 113080, 105766, 42311, 1739, 55050, 3261, 1870, 253990, 186000, 6028, 88500, 2, 0, 64, 0, 21000, 1400, 181500, 7975, 1726, 109, 20043, 800),
(14, 14, 200000, -250000, 5000, 30500, 314, 302, 500, 100, 200, 3, 300, 500, 4, 200, 0, 0, 0, 5, 50, 60, 56, 0, 0, 1, 600, 0),
(15, 15, 7000000, 5000, 10000, 30, 30, 8, 4, 4, 1, 2, 15, 15, 0, 10, 60, 0, 0, 0, 85, 0, 50, 0, 0, 0, 0, 0),
(16, 16, 321606, 682533, 17491, 108680, 37794, 30322, 291, 15571, 1339, 653, 26137, 11120, 650, 18824, 0, 0, 45, 5, 13900, 4165, 2660, 3160, 2395, 16, 9458, 15),
(20, 20, 9138017, 2304556, 6250, 111637, 1629, 135671, 14253, 2657, 7800, 1836, 162963, 10740, 3000, 2520, 1000, 0, 130, 5, 31, 500, 500, 1300, 2, 20, 242, 50),
(21, 21, 1557447, 500206, 880, 3609, 544, 8, 487, 10149, 72, 5, 1743, 10, 99, 109, 0, 0, 0, 0, 12, 3000, 10, 0, 0, 15, 0, 24),
(27, 27, 4327573, 2005214, 60705, 64760, 27444, 8130, 100, 50, 485, 1312, 68680, 10730, 13560, 5095, 60, 0, 74, 2, 14, 16, 767, 4, 0, 1, 312, 35),
(28, 28, 11407, 1000, 1000, 1000, 100, 100, 10, 100, 0, 0, 150, 150, 0, 46, 0, 0, 0, 0, 3, 7, 9, 0, 0, 0, 14, 0),
(29, 29, 17568, 16660, 44, 1014, 11, 4, 56, 2, 0, 1, 6, 4, 0, 6, 0, 0, 0, 0, 0, 2, 4, 0, 0, 0, 0, 0),
(30, 30, 1021000, 4562895, 1292, 326016, 227008, 232210, 26065, 11546, 95760, 1001, 990892, 10, 0, 100000, 0, 0, 75, 5, 0, 0, 0, 0, 0, 0, 0, 0),
(31, 31, 745889, 1429519, 1010, 5884, 42916, 14200, 3380, 2045, 155, 373, 110735, 20050, 0, 12060, 30, 0, 0, 0, 6070, 5, 14, 1, 0, 0, 7, 0),
(38, 38, 1048096, 1114465, 4623, 25246, 30094, 20079, 1502, 25072, 1002, 2, 55080, 60124, 0, 46, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(42, 42, 1741096, 407071, 6709, 33706, 9366, 11673, 1562, 5844, 212, 647, 13467, 15659, 1004, 7352, 7000, 0, 35, 5, 4, 8, 6, 3, 0, 0, 710, 0),
(44, 44, 1160782, 620324, 6250, 149999, 36266, 36316, 5500, 44404, 6000, 5000, 272275, 50000, 1500, 97266, 56316, 0, 95, 5, 0, 250, 252, 0, 0, 0, 20, 0),
(51, 51, 8882500, 2710000, 10601, 67000, 29000, 254000, 13050, 40000, 15100, 8000, 400000, 95000, 0, 215000, 0, 0, 128, 5, 1130, 10, 23, 1040, 900, 70, 11100, 150),
(52, 52, 172876, 33991, 4063, 5425, 1215, 1920, 519, 25, 5, 544, 4935, 2720, 0, 35, 0, 0, 0, 0, 0, 12, 18, 0, 0, 0, 5, 0),
(58, 58, 23871, 3755, 105, 140, 76, 80, 25, 28, 6, 16, 65, 28, 0, 40, 25, 0, 0, 0, 4, 8, 6, 0, 0, 0, 6, 0),
(59, 59, 4347, -5200000, 180, 135, 70, 110, 28, 46, 6, 14, 32, 14, 0, 45, 30, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(61, 61, 2147483647, -10000000, 0, 0, 0, 500, 0, 0, 3251, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, 20, 0, 0, 0, 524, 0),
(63, 63, 4665689, 1324133, 2000, 10000, 33000, 19937, 1700, 95, 3200, 75, 80800, 53000, 0, 210, 55, 0, 20, 5, 10, 75, 8032, 8040, 2, 30, 171, 0),
(65, 65, 2050000, 1024500, 10000, 145700, 22600, 11200, 0, 11200, 1500, 0, 141000, 0, 0, 19900, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(66, 66, 79920, 210280, 2150, 10000, 7000, 5000, 500, 0, 0, 0, 10000, 5000, 5000, 7000, 5000, 0, 20, 5, 0, 1000, 3000, 1000, 2000, 50, 9123, 0),
(67, 67, 5903505, 2293814, 28000, 121950, 21186, 23446, 1420, 8100, 3120, 260, 19420, 12210, 3000, 4800, 0, 0, 0, 5, 6180, 7220, 1850, 5120, 5000, 5, 720, 20);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_investigacion`
--

CREATE TABLE `categorias_investigacion` (
  `id_categoria` int NOT NULL,
  `nombre_categoria` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias_investigacion`
--

INSERT INTO `categorias_investigacion` (`id_categoria`, `nombre_categoria`, `orden`, `created_at`) VALUES
(1, 'Aéreo', 1, '2026-01-22 05:14:22'),
(2, 'Terrestre', 2, '2026-01-22 05:14:22'),
(3, 'Marítimo', 3, '2026-01-22 05:14:22'),
(4, 'Unidades Especiales', 4, '2026-01-22 05:14:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `enfoques`
--

CREATE TABLE `enfoques` (
  `id_enfoque` int NOT NULL,
  `nombre_enfoque` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_enfoque` enum('Atacante','Defensor','IC','IM','IT') COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `multiplicador_ic` decimal(3,1) DEFAULT NULL COMMENT 'Multiplicador IC (null = sin cambio)',
  `multiplicador_im` decimal(3,1) DEFAULT NULL COMMENT 'Multiplicador IM (null = sin cambio)',
  `multiplicador_it` decimal(3,1) DEFAULT NULL COMMENT 'Multiplicador IT (null = sin cambio)',
  `bonus_defensa` int DEFAULT '0' COMMENT 'Bonus a la calculadora en defensa',
  `cooldown_guerra_reducido` tinyint(1) DEFAULT '0' COMMENT 'Cooldown alternado 3-1-3-1',
  `activo` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `enfoques`
--

INSERT INTO `enfoques` (`id_enfoque`, `nombre_enfoque`, `tipo_enfoque`, `descripcion`, `multiplicador_ic`, `multiplicador_im`, `multiplicador_it`, `bonus_defensa`, `cooldown_guerra_reducido`, `activo`, `fecha_creacion`) VALUES
(1, 'Puño de Acero', 'Atacante', 'Cooldown reducido a 1 turno (alternado 3-1-3-1). Cada guerra ofensiva consecutiva genera penalización progresiva en IM.', NULL, NULL, NULL, 0, 1, 1, '2026-02-09 00:46:12'),
(2, 'Muralla Inquebrantable', 'Defensor', 'Bonus de +10 a la calculadora en las batallas del primer turno de cada guerra defensiva.', NULL, NULL, NULL, 10, 0, 1, '2026-02-09 00:46:12'),
(3, 'Oro Antes que Balas', 'IC', 'IC: x5 → x3. Penalización: Producción IM x5 → x3', '3.0', '3.0', NULL, 0, 0, 1, '2026-02-09 00:46:12'),
(4, 'Forja de Guerra', 'IM', 'IM: x2 → x3. Penalización: Producción IC x2 → x1.4', '1.4', '7.0', NULL, 0, 0, 1, '2026-02-09 00:46:12'),
(5, 'Guerra del Futuro', 'IT', 'IT: x8 → x11. Penalización: Producción IM x5 → x3.5', NULL, '3.5', '11.0', 0, 0, 1, '2026-02-09 00:46:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_alianzas`
--

CREATE TABLE `historial_alianzas` (
  `id_historial_alianza` int NOT NULL,
  `id_pais` int NOT NULL,
  `id_alianza_anterior` int DEFAULT NULL,
  `id_alianza_nueva` int DEFAULT NULL,
  `accion` enum('unirse','abandonar','expulsar','disolver','fundar') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_alianza` enum('Militar','Economica') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_usuario_accion` int DEFAULT NULL,
  `fecha_accion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notas` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `historial_alianzas`
--

INSERT INTO `historial_alianzas` (`id_historial_alianza`, `id_pais`, `id_alianza_anterior`, `id_alianza_nueva`, `accion`, `tipo_alianza`, `id_usuario_accion`, `fecha_accion`, `notas`) VALUES
(1, 1, NULL, NULL, 'fundar', NULL, 2, '2026-01-24 05:57:55', NULL),
(2, 1, NULL, NULL, 'fundar', NULL, 2, '2026-01-24 05:58:59', NULL),
(3, 1, NULL, NULL, 'fundar', NULL, 2, '2026-01-24 18:31:55', NULL),
(4, 29, NULL, 4, 'fundar', NULL, 28, '2026-01-25 06:23:47', NULL),
(8, 14, NULL, 6, 'unirse', NULL, 15, '2026-01-25 06:53:25', NULL),
(11, 1, NULL, NULL, 'unirse', NULL, 2, '2026-01-25 07:01:05', NULL),
(15, 2, NULL, 9, 'unirse', NULL, 1, '2026-01-25 17:35:28', NULL),
(18, 20, NULL, 9, 'unirse', NULL, 8, '2026-01-26 02:25:45', NULL),
(19, 20, 9, 6, 'unirse', NULL, 8, '2026-01-26 02:26:12', NULL),
(20, 42, NULL, 6, 'unirse', NULL, 22, '2026-01-26 12:53:32', NULL),
(21, 15, NULL, 10, 'fundar', NULL, 6, '2026-01-26 18:16:54', NULL),
(23, 20, 6, NULL, 'abandonar', NULL, 8, '2026-01-26 21:31:38', NULL),
(25, 44, NULL, 10, 'unirse', NULL, 33, '2026-01-27 15:53:31', NULL),
(26, 27, NULL, 6, 'unirse', NULL, 18, '2026-01-28 02:48:03', NULL),
(30, 28, NULL, 12, 'unirse', NULL, 16, '2026-01-29 11:24:24', NULL),
(31, 16, NULL, 12, 'unirse', NULL, 5, '2026-01-30 14:57:50', NULL),
(32, 21, NULL, NULL, 'unirse', NULL, 32, '2026-01-30 16:23:19', NULL),
(37, 1, NULL, NULL, 'abandonar', 'Militar', 2, '2026-02-12 04:40:30', NULL),
(38, 1, NULL, 14, 'fundar', 'Economica', 2, '2026-02-12 04:42:53', NULL),
(40, 30, NULL, 14, 'unirse', 'Economica', 27, '2026-02-12 04:45:40', NULL),
(43, 15, NULL, NULL, 'fundar', 'Militar', 6, '2026-02-16 14:43:12', NULL),
(44, 1, 14, NULL, 'abandonar', 'Economica', 2, '2026-02-16 23:48:03', NULL),
(45, 1, NULL, NULL, 'fundar', 'Militar', 2, '2026-02-16 23:48:33', NULL),
(48, 42, NULL, 19, 'fundar', 'Economica', 22, '2026-02-20 02:34:45', NULL),
(49, 14, NULL, 20, 'fundar', 'Economica', 15, '2026-02-23 05:44:19', NULL),
(50, 2, 9, NULL, 'abandonar', 'Militar', 1, '2026-02-25 15:19:23', NULL),
(51, 9, NULL, NULL, 'fundar', 'Militar', 19, '2026-03-02 04:35:09', NULL),
(52, 27, 6, NULL, 'abandonar', 'Militar', 11, '2026-03-06 22:33:25', NULL),
(53, 51, NULL, 22, 'fundar', 'Militar', 10, '2026-03-13 03:51:45', NULL),
(54, 14, 6, NULL, 'abandonar', 'Militar', 15, '2026-03-15 04:14:45', NULL),
(55, 14, 20, NULL, 'abandonar', 'Economica', 15, '2026-03-15 04:14:51', NULL),
(56, 63, NULL, 23, 'fundar', 'Economica', 23, '2026-03-16 23:58:23', NULL),
(57, 12, NULL, 24, 'fundar', 'Militar', 31, '2026-03-19 00:53:25', NULL),
(58, 12, NULL, 26, 'fundar', 'Economica', 31, '2026-03-19 20:13:32', NULL),
(59, 44, NULL, 27, 'fundar', 'Militar', 33, '2026-03-20 02:03:14', NULL),
(60, 15, NULL, NULL, 'abandonar', 'Militar', 6, '2026-03-20 07:16:24', NULL),
(61, 9, NULL, NULL, 'abandonar', 'Militar', 19, '2026-03-23 01:42:13', NULL),
(62, 9, NULL, 28, 'fundar', 'Militar', 19, '2026-03-24 01:14:12', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_enfoques`
--

CREATE TABLE `historial_enfoques` (
  `id_historial` int NOT NULL,
  `id_pais` int NOT NULL,
  `id_enfoque_anterior` int DEFAULT NULL,
  `id_enfoque_nuevo` int DEFAULT NULL,
  `turno_cambio` int NOT NULL,
  `fecha_cambio` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `historial_enfoques`
--

INSERT INTO `historial_enfoques` (`id_historial`, `id_pais`, `id_enfoque_anterior`, `id_enfoque_nuevo`, `turno_cambio`, `fecha_cambio`) VALUES
(2, 16, NULL, 4, 15, '2026-02-09 00:57:32'),
(3, 2, NULL, 3, 15, '2026-02-09 01:04:48'),
(4, 44, NULL, 3, 15, '2026-02-09 01:05:04'),
(5, 44, 3, 3, 15, '2026-02-09 01:05:18'),
(8, 20, NULL, 3, 15, '2026-02-09 01:26:19'),
(10, 12, NULL, 3, 15, '2026-02-09 01:29:28'),
(11, 1, NULL, 3, 15, '2026-02-09 01:29:46'),
(13, 42, NULL, 3, 15, '2026-02-09 02:51:04'),
(14, 14, NULL, 3, 15, '2026-02-09 02:52:31'),
(20, 15, NULL, 3, 16, '2026-02-09 13:24:31'),
(22, 28, NULL, 4, 16, '2026-02-09 13:57:15'),
(23, 31, NULL, 3, 16, '2026-02-09 15:38:54'),
(26, 30, NULL, 4, 16, '2026-02-10 01:05:27'),
(28, 9, NULL, 3, 16, '2026-02-10 03:34:11'),
(29, 51, NULL, 3, 19, '2026-02-13 13:12:44'),
(32, 52, NULL, 1, 27, '2026-02-21 04:56:58'),
(35, 16, 4, 3, 29, '2026-02-23 03:39:39'),
(37, 12, 3, 1, 29, '2026-02-23 13:11:45'),
(39, 42, 3, 4, 31, '2026-02-25 04:48:44'),
(44, 20, 3, 4, 34, '2026-02-28 18:44:47'),
(45, 27, NULL, 3, 37, '2026-03-02 20:43:06'),
(46, 16, 3, 4, 37, '2026-03-03 03:25:33'),
(47, 44, 3, 5, 37, '2026-03-03 14:40:15'),
(48, 20, 4, 3, 40, '2026-03-06 06:35:17'),
(51, 51, 3, 2, 42, '2026-03-07 14:19:20'),
(53, 59, NULL, 3, 42, '2026-03-07 14:33:04'),
(56, 44, 5, 4, 48, '2026-03-13 11:59:03'),
(57, 42, 4, 3, 48, '2026-03-14 03:03:22'),
(59, 63, NULL, 3, 50, '2026-03-16 23:56:20'),
(60, 65, NULL, 3, 52, '2026-03-18 17:31:11'),
(61, 51, 2, 3, 52, '2026-03-18 22:23:58'),
(64, 61, NULL, 3, 54, '2026-03-21 18:06:26'),
(65, 12, 1, 3, 54, '2026-03-24 11:37:55'),
(66, 67, NULL, 3, 54, '2026-03-24 14:54:19'),
(67, 28, 4, 3, 54, '2026-03-25 00:46:05'),
(68, 38, NULL, 2, 54, '2026-03-26 01:09:41'),
(69, 44, 4, 3, 62, '2026-03-27 15:31:24'),
(70, 51, 3, 2, 63, '2026-03-28 17:40:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_paises`
--

CREATE TABLE `historial_paises` (
  `id_historial` int NOT NULL,
  `id_usuario` int NOT NULL,
  `id_pais` int NOT NULL,
  `nombre_pais_historico` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bandera_url_historica` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_inicio` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_fin` datetime DEFAULT CURRENT_TIMESTAMP,
  `razon_cambio` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'Cambio de país'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `historial_paises`
--

INSERT INTO `historial_paises` (`id_historial`, `id_usuario`, `id_pais`, `nombre_pais_historico`, `bandera_url_historica`, `fecha_inicio`, `fecha_fin`, `razon_cambio`) VALUES
(1, 2, 1, 'Estados Unidos Mexicanos', 'default_flag.png', '2026-01-24 00:34:43', '2026-01-24 00:34:43', 'Reasignación por GM'),
(2, 1, 2, 'Gran Reino de Suecia', 'default_flag.png', '2026-01-24 00:36:12', '2026-01-24 00:36:12', 'País removido por GM'),
(3, 2, 1, 'Estados Unidos Mexicanos', 'assets/uploads/banderas/2_Estados_Unidos_Mexicanos.png', '2026-01-24 00:42:51', '2026-01-24 00:42:51', 'Reasignación por GM'),
(4, 2, 3, 'Estados Unidos de América', 'default_flag.png', '2026-01-24 00:43:02', '2026-01-24 00:43:02', 'Reasignación por GM'),
(5, 2, 4, 'Federación Rusa', 'default_flag.png', '2026-01-24 00:43:29', '2026-01-24 00:43:29', 'Reasignación por GM'),
(6, 2, 5, 'República Popular China', 'default_flag.png', '2026-01-24 00:44:03', '2026-01-24 00:44:03', 'Reasignación por GM'),
(7, 2, 6, 'República Federal de Alemania', 'default_flag.png', '2026-01-24 00:44:17', '2026-01-24 00:44:17', 'Reasignación por GM'),
(8, 2, 7, 'Reino Unido', 'default_flag.png', '2026-01-24 00:44:40', '2026-01-24 00:44:40', 'Reasignación por GM'),
(9, 2, 8, 'Japón', 'default_flag.png', '2026-01-24 00:45:01', '2026-01-24 00:45:01', 'Reasignación por GM'),
(10, 2, 9, 'Mancomunidad de Australia', 'default_flag.png', '2026-01-24 00:45:17', '2026-01-24 00:45:17', 'Reasignación por GM'),
(11, 2, 10, 'Reino de España', 'default_flag.png', '2026-01-24 00:45:48', '2026-01-24 00:45:48', 'Reasignación por GM'),
(12, 2, 11, 'Ucrania', 'default_flag.png', '2026-01-24 00:46:11', '2026-01-24 00:46:11', 'Reasignación por GM'),
(13, 2, 12, 'República Bolivariana de Venezuela', 'default_flag.png', '2026-01-24 00:46:54', '2026-01-24 00:46:54', 'País removido por GM'),
(14, 2, 13, 'Hungría', 'default_flag.png', '2026-01-24 00:48:00', '2026-01-24 00:48:00', 'Reasignación por GM'),
(15, 2, 14, 'Francia', 'default_flag.png', '2026-01-24 00:48:04', '2026-01-24 00:48:04', 'Reasignación por GM'),
(16, 2, 15, 'Kazajistán', 'default_flag.png', '2026-01-24 00:48:26', '2026-01-24 00:48:26', 'Reasignación por GM'),
(17, 2, 16, 'India', 'default_flag.png', '2026-01-24 00:48:30', '2026-01-24 00:48:30', 'Reasignación por GM'),
(18, 2, 17, 'Canadá', 'default_flag.png', '2026-01-24 00:48:35', '2026-01-24 00:48:35', 'Reasignación por GM'),
(19, 2, 18, 'Taiwan', 'default_flag.png', '2026-01-24 00:48:38', '2026-01-24 00:48:38', 'Reasignación por GM'),
(20, 2, 19, 'Bosnia', 'default_flag.png', '2026-01-24 00:48:45', '2026-01-24 00:48:45', 'Reasignación por GM'),
(21, 2, 20, 'Países Bajos', 'default_flag.png', '2026-01-24 00:48:59', '2026-01-24 00:48:59', 'Reasignación por GM'),
(22, 2, 21, 'Corea del Norte', 'default_flag.png', '2026-01-24 00:49:03', '2026-01-24 00:49:03', 'Reasignación por GM'),
(23, 2, 22, 'Bielorrusia', 'default_flag.png', '2026-01-24 00:49:15', '2026-01-24 00:49:15', 'Reasignación por GM'),
(24, 2, 23, 'Uzbequistán', 'default_flag.png', '2026-01-24 00:49:19', '2026-01-24 00:49:19', 'Reasignación por GM'),
(25, 2, 24, 'Cuba', 'default_flag.png', '2026-01-24 00:49:22', '2026-01-24 00:49:22', 'Reasignación por GM'),
(26, 2, 25, 'Brasil', 'default_flag.png', '2026-01-24 00:49:30', '2026-01-24 00:49:30', 'Reasignación por GM'),
(27, 2, 26, 'Camboya', 'default_flag.png', '2026-01-24 00:49:34', '2026-01-24 00:49:34', 'Reasignación por GM'),
(28, 2, 27, 'Turquía', 'default_flag.png', '2026-01-24 00:49:37', '2026-01-24 00:49:37', 'Reasignación por GM'),
(29, 2, 28, 'Vanatu', 'default_flag.png', '2026-01-24 00:49:44', '2026-01-24 00:49:44', 'Reasignación por GM'),
(30, 2, 29, 'Irak', 'default_flag.png', '2026-01-24 00:49:46', '2026-01-24 00:49:46', 'Reasignación por GM'),
(31, 2, 30, 'Suiza', 'default_flag.png', '2026-01-24 00:49:49', '2026-01-24 00:49:49', 'Reasignación por GM'),
(32, 2, 31, 'Eritrea', 'default_flag.png', '2026-01-24 00:49:53', '2026-01-24 00:49:53', 'Reasignación por GM'),
(33, 2, 32, 'Ecuador', 'default_flag.png', '2026-01-24 00:49:58', '2026-01-24 00:49:58', 'Reasignación por GM'),
(34, 2, 33, 'Paraguay', 'default_flag.png', '2026-01-24 00:50:02', '2026-01-24 00:50:02', 'Reasignación por GM'),
(35, 2, 34, 'Italia', 'default_flag.png', '2026-01-24 00:50:09', '2026-01-24 00:50:09', 'Reasignación por GM'),
(36, 2, 35, 'Israel', 'default_flag.png', '2026-01-24 00:50:12', '2026-01-24 00:50:12', 'Reasignación por GM'),
(37, 2, 36, 'Líbano', 'default_flag.png', '2026-01-24 00:50:19', '2026-01-24 00:50:19', 'Reasignación por GM'),
(38, 2, 37, 'Colombia', 'default_flag.png', '2026-01-24 00:50:24', '2026-01-24 00:50:24', 'Reasignación por GM'),
(39, 2, 38, 'Etiopía', 'default_flag.png', '2026-01-24 00:50:26', '2026-01-24 00:50:26', 'Reasignación por GM'),
(40, 2, 39, 'Rumania', 'default_flag.png', '2026-01-24 00:50:46', '2026-01-24 00:50:46', 'Reasignación por GM'),
(41, 2, 1, 'Estados Unidos Mexicanos', 'assets/uploads/banderas/2_Estados_Unidos_Mexicanos.png', '2026-02-02 02:13:35', '2026-02-02 02:13:35', 'Reasignación por GM'),
(42, 2, 46, 'Sudáfrica', 'default_flag.png', '2026-02-02 02:13:41', '2026-02-02 02:13:41', 'Reasignación por GM'),
(43, 27, 11, 'Ucrania', 'default_flag.png', '2026-02-07 22:07:33', '2026-02-07 22:07:33', 'Reasignación por GM'),
(44, 25, 5, 'República Popular China', 'assets/uploads/banderas/25_Rep__blica_Popular_China.png', '2026-02-08 02:05:11', '2026-02-08 02:05:11', 'País removido por GM'),
(45, 12, 13, 'Hungría', 'assets/uploads/banderas/12_Hungr__a.png', '2026-02-08 02:07:05', '2026-02-08 02:07:05', 'País removido por GM'),
(46, 29, 18, 'Taiwan', 'assets/uploads/banderas/29_Taiwan.png', '2026-02-12 02:06:12', '2026-02-12 02:06:12', 'País removido por GM'),
(47, 26, 53, 'Afganistan', 'default_flag.png', '2026-02-25 01:23:34', '2026-02-25 01:23:34', 'País removido por GM'),
(48, 18, 27, 'Turquía', 'assets/uploads/banderas/18_Turqu__a.png', '2026-03-01 01:10:08', '2026-03-01 01:10:08', 'País removido por GM'),
(49, 11, 6, 'Unión de Repúblicas Centroeuropeas', 'assets/uploads/banderas/11_Uni__n_Centroeuropea.png', '2026-03-01 01:10:45', '2026-03-01 01:10:45', 'País removido por GM'),
(50, 29, 50, 'Egipto', 'default_flag.png', '2026-03-15 16:59:35', '2026-03-15 16:59:35', 'País removido por GM'),
(51, 25, 54, 'Argelia', 'default_flag.png', '2026-03-16 17:58:23', '2026-03-16 17:58:23', 'Reasignación por GM'),
(52, 25, 54, 'Argelia', 'default_flag.png', '2026-03-16 17:59:08', '2026-03-16 17:59:08', 'Reasignación por GM'),
(53, 25, 54, 'Argelia', 'default_flag.png', '2026-03-16 18:00:55', '2026-03-16 18:00:55', 'Reasignación por GM'),
(54, 25, 64, 'Italia', 'default_flag.png', '2026-03-16 19:54:00', '2026-03-16 19:54:00', 'País removido por GM'),
(55, 23, 60, 'Nigeria', 'default_flag.png', '2026-03-16 19:54:22', '2026-03-16 19:54:22', 'Reasignación por GM'),
(56, 29, 62, 'China', 'default_flag.png', '2026-03-16 23:42:53', '2026-03-16 23:42:53', 'País removido por GM'),
(57, 9, 17, 'Canadá', 'assets/uploads/banderas/9_Canad__.png', '2026-03-23 21:30:05', '2026-03-23 21:30:05', 'País removido por GM');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_turnos`
--

CREATE TABLE `historial_turnos` (
  `id_historial_turno` int NOT NULL,
  `id_pais` int DEFAULT NULL COMMENT 'NULL para turnos globales',
  `turno_anterior` int NOT NULL,
  `turno_nuevo` int NOT NULL,
  `tipo_cambio` enum('avanzar','retroceder','ajustar') COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_gm` int DEFAULT NULL COMMENT 'NULL para cambios de sistema',
  `id_usuario_accion` int DEFAULT NULL,
  `fecha_cambio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notas` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `historial_turnos`
--

INSERT INTO `historial_turnos` (`id_historial_turno`, `id_pais`, `turno_anterior`, `turno_nuevo`, `tipo_cambio`, `id_gm`, `id_usuario_accion`, `fecha_cambio`, `notas`) VALUES
(1, NULL, 1, 2, 'avanzar', NULL, 4, '2026-01-26 15:57:27', NULL),
(2, NULL, 2, 3, 'avanzar', NULL, 4, '2026-01-27 13:48:14', NULL),
(3, NULL, 3, 4, 'avanzar', NULL, 4, '2026-01-28 06:56:52', NULL),
(4, NULL, 4, 5, 'avanzar', NULL, 4, '2026-01-29 07:08:18', NULL),
(5, NULL, 5, 6, 'avanzar', NULL, 4, '2026-01-30 07:30:00', NULL),
(6, NULL, 6, 7, 'avanzar', NULL, 4, '2026-01-31 07:11:36', NULL),
(7, NULL, 7, 8, 'avanzar', NULL, 4, '2026-02-02 08:24:44', NULL),
(8, NULL, 8, 9, 'avanzar', NULL, 4, '2026-02-02 08:24:47', NULL),
(9, NULL, 9, 10, 'avanzar', NULL, 4, '2026-02-03 07:18:35', NULL),
(10, NULL, 10, 11, 'avanzar', NULL, 4, '2026-02-04 07:03:31', NULL),
(11, NULL, 11, 12, 'avanzar', NULL, 4, '2026-02-05 06:47:19', NULL),
(12, NULL, 12, 13, 'avanzar', NULL, 4, '2026-02-06 06:52:42', NULL),
(13, NULL, 13, 14, 'avanzar', NULL, 4, '2026-02-07 06:55:11', NULL),
(14, NULL, 14, 15, 'avanzar', NULL, 4, '2026-02-08 07:35:21', NULL),
(15, NULL, 15, 16, 'avanzar', NULL, 4, '2026-02-09 07:29:52', NULL),
(16, NULL, 16, 17, 'avanzar', NULL, 4, '2026-02-10 06:58:49', NULL),
(17, NULL, 17, 18, 'avanzar', NULL, 4, '2026-02-12 07:15:27', NULL),
(18, NULL, 18, 19, 'avanzar', NULL, 4, '2026-02-12 07:15:30', NULL),
(19, NULL, 19, 20, 'avanzar', NULL, 4, '2026-02-14 07:24:45', NULL),
(20, NULL, 20, 21, 'avanzar', NULL, 4, '2026-02-14 07:24:48', NULL),
(21, NULL, 21, 22, 'avanzar', NULL, 29, '2026-02-15 04:40:39', NULL),
(22, NULL, 22, 23, 'avanzar', NULL, 4, '2026-02-16 07:12:45', NULL),
(23, NULL, 23, 24, 'avanzar', NULL, 4, '2026-02-18 08:32:35', NULL),
(24, NULL, 24, 25, 'avanzar', NULL, 4, '2026-02-18 08:32:38', NULL),
(25, NULL, 25, 26, 'avanzar', NULL, 4, '2026-02-20 08:59:49', NULL),
(26, NULL, 26, 27, 'avanzar', NULL, 4, '2026-02-20 08:59:55', NULL),
(27, NULL, 27, 28, 'avanzar', NULL, 4, '2026-02-21 10:00:29', NULL),
(28, NULL, 28, 29, 'avanzar', NULL, 4, '2026-02-22 20:20:26', NULL),
(29, NULL, 29, 30, 'avanzar', NULL, 4, '2026-02-24 06:55:34', NULL),
(30, NULL, 30, 31, 'avanzar', NULL, 4, '2026-02-24 06:55:36', NULL),
(31, NULL, 31, 32, 'avanzar', NULL, 4, '2026-02-25 06:49:58', NULL),
(32, NULL, 32, 33, 'avanzar', NULL, 4, '2026-02-26 08:07:51', NULL),
(33, NULL, 33, 34, 'avanzar', NULL, 4, '2026-02-27 07:14:25', NULL),
(34, NULL, 34, 35, 'avanzar', NULL, 4, '2026-03-01 06:55:05', NULL),
(35, NULL, 35, 36, 'avanzar', NULL, 4, '2026-03-01 06:55:08', NULL),
(36, NULL, 36, 37, 'avanzar', NULL, 4, '2026-03-02 07:23:56', NULL),
(37, NULL, 37, 38, 'avanzar', NULL, 4, '2026-03-05 07:34:30', NULL),
(38, NULL, 38, 39, 'avanzar', NULL, 4, '2026-03-05 07:34:33', NULL),
(39, NULL, 39, 40, 'avanzar', NULL, 4, '2026-03-05 07:34:35', NULL),
(40, NULL, 40, 41, 'avanzar', NULL, 4, '2026-03-07 07:42:45', NULL),
(41, NULL, 41, 42, 'avanzar', NULL, 4, '2026-03-07 07:42:54', NULL),
(42, NULL, 42, 43, 'avanzar', NULL, 4, '2026-03-11 06:36:16', NULL),
(43, NULL, 43, 44, 'avanzar', NULL, 4, '2026-03-11 06:36:31', NULL),
(44, NULL, 44, 45, 'avanzar', NULL, 4, '2026-03-11 06:36:34', NULL),
(45, NULL, 45, 46, 'avanzar', NULL, 4, '2026-03-11 06:36:36', NULL),
(46, NULL, 46, 47, 'avanzar', NULL, 4, '2026-03-12 08:50:15', NULL),
(47, NULL, 47, 48, 'avanzar', NULL, 4, '2026-03-13 07:48:03', NULL),
(48, NULL, 48, 49, 'avanzar', NULL, 4, '2026-03-14 06:13:08', NULL),
(49, NULL, 49, 50, 'avanzar', NULL, 4, '2026-03-16 01:12:12', NULL),
(50, NULL, 50, 51, 'avanzar', NULL, 31, '2026-03-17 12:44:26', NULL),
(51, NULL, 51, 52, 'avanzar', NULL, 31, '2026-03-18 13:20:57', NULL),
(52, NULL, 52, 53, 'avanzar', NULL, 31, '2026-03-19 14:05:04', NULL),
(53, NULL, 53, 54, 'avanzar', NULL, 31, '2026-03-20 14:15:14', NULL),
(54, NULL, 54, 55, 'avanzar', NULL, 29, '2026-03-27 06:09:19', NULL),
(55, NULL, 55, 61, 'ajustar', NULL, 29, '2026-03-27 06:09:25', NULL),
(56, NULL, 61, 62, 'avanzar', NULL, 29, '2026-03-27 06:10:03', NULL),
(57, NULL, 62, 63, 'avanzar', NULL, 29, '2026-03-28 05:06:05', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `investigaciones`
--

CREATE TABLE `investigaciones` (
  `id_investigacion` int NOT NULL,
  `id_categoria` int NOT NULL,
  `id_subcategoria` int DEFAULT NULL,
  `nombre_investigacion` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `costo_it` int NOT NULL DEFAULT '0',
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `orden` int DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `investigaciones_requisitos`
--

CREATE TABLE `investigaciones_requisitos` (
  `id_requisito` int NOT NULL,
  `id_investigacion` int NOT NULL,
  `id_investigacion_requerida` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `miembros_alianza`
--

CREATE TABLE `miembros_alianza` (
  `id_alianza` int NOT NULL,
  `id_pais` int NOT NULL,
  `fecha_ingreso` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id_notificacion` int NOT NULL,
  `id_usuario_emisor` int NOT NULL,
  `id_rol_receptor` int DEFAULT NULL COMMENT 'Para enviar a un rol (ej: todos los GMs)',
  `id_usuario_receptor` int DEFAULT NULL COMMENT 'Para enviar a usuario específico',
  `mensaje` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `leido` tinyint(1) DEFAULT '0',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paises`
--

CREATE TABLE `paises` (
  `id_pais` int NOT NULL,
  `nombre_pais` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bandera_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default_flag.png',
  `idg_puntos` int DEFAULT '0',
  `turno_actual` int DEFAULT '1',
  `id_alianza_militar` int DEFAULT NULL,
  `id_alianza_economica` int DEFAULT NULL,
  `id_enfoque_activo` int DEFAULT NULL COMMENT 'Enfoque nacional activo',
  `fecha_ultimo_cambio_enfoque` timestamp NULL DEFAULT NULL COMMENT 'Fecha del último cambio de enfoque',
  `turnos_sin_guerra_ofensiva` int DEFAULT '0' COMMENT 'Contador de turnos consecutivos sin declarar guerra',
  `guerras_ofensivas_consecutivas` int DEFAULT '0' COMMENT 'Contador de guerras ofensivas bajo Puño de Acero'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `paises`
--

INSERT INTO `paises` (`id_pais`, `nombre_pais`, `bandera_url`, `idg_puntos`, `turno_actual`, `id_alianza_militar`, `id_alianza_economica`, `id_enfoque_activo`, `fecha_ultimo_cambio_enfoque`, `turnos_sin_guerra_ofensiva`, `guerras_ofensivas_consecutivas`) VALUES
(1, 'Unión Mexicana', 'assets/uploads/banderas/2_Uni__n_Mexicana.png', 0, 63, NULL, NULL, 3, '2026-02-09 01:29:46', 0, 0),
(2, 'Gran Reino de Suecia', 'assets/uploads/banderas/1_Gran_Reino_de_Suecia.png', 0, 63, NULL, NULL, 3, '2026-02-09 01:04:48', 0, 0),
(9, 'Nuevo Reino de Erusea', 'assets/uploads/banderas/19_Nuevo_Reino_de_Erusea.png', 0, 63, 28, NULL, 3, '2026-02-10 03:34:11', 0, 0),
(12, 'Federación de las Américas', 'assets/uploads/banderas/31_Federaci__n_de_las_Am__ricas.png', 0, 63, 24, 26, 3, '2026-03-24 11:37:55', 0, 0),
(14, 'República Socialista Francesa☭', 'assets/uploads/banderas/15_Rep__blica_Francesa.png', 0, 63, NULL, NULL, 3, '2026-02-09 02:52:31', 0, 0),
(15, 'Gran Kanato de Kazajistán', 'assets/uploads/banderas/6_Kazajist__n.png', 0, 63, NULL, 10, 3, '2026-02-09 13:24:31', 0, 0),
(16, 'Reino Teocrático de India', 'assets/uploads/banderas/5_India.png', 0, 63, NULL, 12, 4, '2026-03-03 03:25:33', 0, 0),
(20, 'Reino de los Países Bajos', 'assets/uploads/banderas/8_Reino_de_los_Pa__ses_Bajos.png', 0, 63, NULL, NULL, 3, '2026-03-06 06:35:17', 0, 0),
(21, 'Corea del Norte', 'assets/uploads/banderas/32_Corea_del_Norte.png', 0, 63, NULL, NULL, NULL, NULL, 0, 0),
(27, 'Turquía', 'assets/uploads/banderas/18_Turqu__a.png', 0, 63, NULL, NULL, 3, '2026-03-02 20:43:06', 0, 0),
(28, 'Vanuatu', 'assets/uploads/banderas/16_Vanuatu.png', 0, 63, NULL, 12, 3, '2026-03-25 00:46:05', 0, 0),
(29, 'Irak', 'assets/uploads/banderas/28_Irak.png', 0, 63, 4, NULL, NULL, NULL, 0, 0),
(30, 'Suiza', 'default_flag.png', 0, 63, NULL, 14, 4, '2026-02-10 01:05:27', 0, 0),
(31, 'Eritrea', 'assets/uploads/banderas/24_Eritrea.png', 0, 63, NULL, NULL, 3, '2026-02-09 15:38:54', 0, 0),
(38, 'Etiopía', 'assets/uploads/banderas/21_Etiop__a.png', 0, 63, NULL, NULL, 2, '2026-03-26 01:09:41', 0, 0),
(42, 'España', 'assets/uploads/banderas/22_Espa__a.png', 0, 63, 6, 19, 3, '2026-03-14 03:03:22', 0, 0),
(44, 'Turkmenistán', 'default_flag.png', 0, 63, 27, 10, 3, '2026-03-27 15:31:24', 0, 0),
(51, 'Syria', 'assets/uploads/banderas/10_Siria.png', 0, 63, 22, NULL, 2, '2026-03-28 17:40:31', 0, 0),
(52, 'Arabia Saudita', 'default_flag.png', 0, 63, NULL, NULL, 1, '2026-02-21 04:56:58', 0, 0),
(58, 'Chile', 'assets/uploads/banderas/34_Chile.png', 0, 63, NULL, NULL, NULL, NULL, 0, 0),
(59, 'Austria-Hungria', 'default_flag.png', 0, 63, NULL, NULL, 3, '2026-03-07 14:33:04', 0, 0),
(61, 'Sealand', 'assets/uploads/banderas/4_Sealand.png', 0, 63, NULL, NULL, 3, '2026-03-21 18:06:26', 0, 0),
(63, 'Italia 1', 'assets/uploads/banderas/23_Italia_1.png', 0, 63, NULL, 23, 3, '2026-03-16 23:56:20', 0, 0),
(65, 'Taiwan', 'default_flag.png', 0, 63, NULL, NULL, 3, '2026-03-18 17:31:11', 0, 0),
(66, 'Japon', 'default_flag.png', 0, 63, NULL, NULL, NULL, NULL, 0, 0),
(67, 'Corea del sur', 'default_flag.png', 0, 63, NULL, NULL, 3, '2026-03-24 14:54:19', 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paises_investigaciones`
--

CREATE TABLE `paises_investigaciones` (
  `id_pais_investigacion` int NOT NULL,
  `id_pais` int NOT NULL,
  `id_investigacion` int NOT NULL,
  `fecha_completada` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int NOT NULL,
  `nombre_rol` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre_rol`) VALUES
(1, 'Administrador'),
(3, 'Auditor'),
(2, 'GM'),
(4, 'Participante');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subcategorias_investigacion`
--

CREATE TABLE `subcategorias_investigacion` (
  `id_subcategoria` int NOT NULL,
  `id_categoria` int NOT NULL,
  `nombre_subcategoria` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `subcategorias_investigacion`
--

INSERT INTO `subcategorias_investigacion` (`id_subcategoria`, `id_categoria`, `nombre_subcategoria`, `orden`, `created_at`) VALUES
(1, 2, 'Infantería', 1, '2026-01-22 05:14:22'),
(2, 2, 'Blindados', 2, '2026-01-22 05:14:22'),
(3, 2, 'Artillería', 3, '2026-01-22 05:14:22'),
(4, 2, 'Defensa Antiaérea', 4, '2026-01-22 05:14:22'),
(5, 3, 'Submarinos', 1, '2026-01-22 05:14:22'),
(6, 3, 'Fragatas y Corbetas', 2, '2026-01-22 05:14:22'),
(7, 3, 'Destructores y Cruceros', 3, '2026-01-22 05:14:22'),
(8, 3, 'Portaaviones', 4, '2026-01-22 05:14:22'),
(9, 3, 'Defensa Costera', 5, '2026-01-22 05:14:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turno_global`
--

CREATE TABLE `turno_global` (
  `id` int NOT NULL,
  `turno_actual` int NOT NULL DEFAULT '1',
  `fecha_inicio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ultima_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `turno_global`
--

INSERT INTO `turno_global` (`id`, `turno_actual`, `fecha_inicio`) VALUES
(1, 63, '2026-01-22 05:14:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discord_user` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_rol` int DEFAULT NULL,
  `id_pais` int DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `debe_cambiar_password` tinyint(1) DEFAULT '0' COMMENT 'Flag para forzar cambio de contraseña en próximo login'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `username`, `password_hash`, `discord_user`, `id_rol`, `id_pais`, `activo`, `fecha_registro`, `debe_cambiar_password`) VALUES
(1, 'artyom', '$2y$12$dG6gxbDlfUAxigUza1.LYuW6hDJPdiY6L8zjqSLtv3EAf5JIbENQ2', 'artyrick', 1, 2, 1, '2026-01-22 05:18:26', 0),
(2, 'Delta', '$2y$12$NPnHa0z3HybpCP8.DjHqgOmico7VDIHEwLNG0oPRzIbbNxqtahc2y', 'Delta', 1, 1, 1, '2026-01-24 05:32:34', 0),
(3, 'Yisus', '$2y$12$JmHQ1B07pF.lPvUQWKnxze8oRYtenVUvVvce5hnKGZLC0MFEUo6wK', 'Yisus', 4, 65, 1, '2026-01-24 06:12:48', 0),
(4, 'Yuan_B3M', '$2y$12$5lfX2JfBExC.d7NeYAs0gelwlXLKj3bEeEx.34HdxmFh90IQctLfO', 'yuan_0211', 1, 61, 1, '2026-01-24 07:34:51', 0),
(5, 'Martvius', '$2y$12$sVzHhW8NOvsVvMTipwQMOOe72FMIkdI8XJLT6WrNG9eKjLxL5AiVi', 'martvius0605', 4, 16, 1, '2026-01-24 16:49:44', 0),
(6, 'Mr.Penguin', '$2y$12$coJQBmBTnAYtyxpis/NzKOq4D9kc3BkFg9P.OPUHMIhrmLXO2.w/.', 'mr.penguin500', 4, 15, 1, '2026-01-24 16:53:35', 0),
(7, 'Himepuerca', '$2y$12$Q25GEsAHYPlYQoZnesDCA.JHzzej28.VN1AcZ8L7RbUCuzPCeiISi', 'Dayan_Hime', 4, NULL, 1, '2026-01-24 17:00:54', 0),
(8, 'Redview', '$2y$12$ZIS5oTu8rdlUUczqIERuGeaYlYZM8o.zkkOAtunNTAOI5oXW/KPxq', 'Redview', 4, 20, 1, '2026-01-24 17:02:05', 0),
(9, 'Ala', '$2y$12$5R3ADVApwm/oai3E3xGDb.7W5QtmFM9Ff3zYyFPo0IG9wMuNMl06O', 'enigmahunter772', 4, 67, 1, '2026-01-24 17:11:55', 0),
(10, 'Nat', '$2y$12$ib9d7wcvxB0yJhd3ULAHROORi7Oo/DLypS/QMtGsSwcA2w6cLTGZK', 'KGB_1991#4838', 4, 51, 1, '2026-01-24 17:15:25', 0),
(11, 'Diegold', '$2y$12$Bn89O7ev0pg7QbyWtcfIkuLhauRBxo.2hjbbNERufoZvlu3zMJLt2', 'username_unknow.', 4, 27, 1, '2026-01-24 17:18:09', 0),
(12, 'The big comu', '$2y$12$7f05EvsRbSKa/dfu0D7hZOFc/6bEjVYuWDS9jTkN2ZQ3UHad/TYkW', 'thebigcomu', 4, 59, 1, '2026-01-24 17:22:19', 0),
(13, 'Uzbekistán', '$2y$12$ODauwigVnhD.dRRhSZS8j..IITgl8S486SXqIKhc18TpWxVKEv4RO', 'Uzbekistán', 4, NULL, 1, '2026-01-24 17:26:47', 0),
(14, 'Chito', '$2y$12$5l3nPlcXuGzOWBn.nvYVoexVzfIzOBA9yVcY587hdIIRCn0IjtP/q', 'tksdin234', 4, NULL, 1, '2026-01-24 17:39:04', 0),
(15, 'ariel_22.', '$2y$12$iz7ivum8PtfqmmcXzRZQseTKqbPXoCR.uKhJ/GBTmhsIBN/0JcfwS', 'Aiel', 4, 14, 1, '2026-01-24 17:50:22', 0),
(16, 'Eculluce', '$2y$12$BwY8Luq/lUF93xNi4P4mk.hK0HguZP0UeKDDejJUz76QIjAduJyx6', 'eculluce_50', 4, 28, 1, '2026-01-24 18:10:50', 0),
(17, 'Arkham', '$2y$12$7jddnQJuQvTwGbSwIO1IDO00t5xUWJ9Wb/Rhe5SEtN3K3QRHCr3vi', 'aruk_khan', 4, NULL, 1, '2026-01-24 18:55:05', 0),
(18, 'dylaen', '$2y$12$6qzoRkn05R3L8SugWZqgd.g1f8/N0JoZFe0yAkLWAFME39nXstWu6', 'dylaenz', 4, NULL, 1, '2026-01-24 20:33:56', 0),
(19, 'Polski', '$2y$12$kDk8FkBOwGYk9Ckd7RlwSOKyab9Ayw21JS.JQ620NtCnAUohCtuNm', 'p0d0', 4, 9, 1, '2026-01-24 21:32:04', 0),
(20, 'DaniDecar', '$2y$12$70JTZ8HxgcxIVyIHxJtmCeviCcwuOGyN7TnKawQ0ZkV.7xe//7IFS', 'Daniel_Gonzalez02', 4, NULL, 1, '2026-01-25 00:31:19', 0),
(21, 'Jacks', '$2y$12$1x/mGYjQtFo.mEZIuZEtd.rfAwEYXD588K5EQRmPmuxW0x5UwBGDu', 'drajacks', 4, 38, 1, '2026-01-25 01:28:16', 0),
(22, 'Juan de la rosa', '$2y$12$cxBlnRppa1cGf8DXqnpbCe9Lb8babNzsAtAtWVGHOyLN3G9Rq2wmy', 'zetsuboumonster#1917', 4, 42, 1, '2026-01-25 03:19:14', 0),
(23, 'Elixnl0l', '$2y$12$N0IqLRyBoDjlry0hvJcj9u1fBsMCJlXM6G.WkKw04GZGzUL8pacp.', 'elinxl0l', 4, 63, 1, '2026-01-25 03:32:01', 0),
(24, 'Flavio Camel', '$2y$12$BEgjhyQIVY.P4oBaOrpzpu659/ItuS6nqBcX4aTiX48MyxpjE1eL.', 'Kohlenklau73', 4, 31, 1, '2026-01-25 03:39:53', 0),
(25, 'DannielDeV', '$2y$12$tK0hhzgkzcCbSxMfVo5I3ugTPpPD.ajQPpEhSdq11gEwO./p6wFcq', 'Doto Oaxaqueña', 4, NULL, 1, '2026-01-25 03:44:14', 0),
(26, 'Matapendejos007', '$2y$12$ESiO9yWntkkyjiib95vaNOr3mbRkDMsUVWBTohBXcSBRyH3qpSLsm', 'Cactus Mercenary', 4, NULL, 1, '2026-01-25 06:07:38', 0),
(27, 'Apachezote', '$2y$12$Un4S8.1syVQlAfWQVOlbouVgec48eOWm/GkNXsMUcGW4TKtHAl1xG', 'Ukraine destroyer', 4, 30, 1, '2026-01-25 06:19:55', 0),
(28, 'nitrolas1', '$2y$12$rFWdUrWiCINlSt9I2Qvt9uR4LT.zWzQ0TNTgxQ.l50dExrugLtLkW', 'yolocausto', 4, 29, 1, '2026-01-25 06:21:42', 0),
(29, 'Ayrton', '$2y$12$2mUXoCLorWe11n8w15JBM.MUpFPoQA/ymqtiD2u1Dv/U9top0qJsy', 'ramses1945', 1, NULL, 1, '2026-01-25 06:49:00', 0),
(30, 'DylanJames01', '$2y$12$.3.Zy/s78JIRwC0Hr8Jfju1dlwppfyssuuXXq1Dpst27N9YqLkMnK', 'dylan027334', 3, 52, 1, '2026-01-25 06:51:42', 0),
(31, 'Hearbreak_0-1', '$2y$12$pVRNwUejBeAW9RCGrfBAuuVbge8jbbYKOk6YRB1DHDPYy6Es6lXfO', 'ISKANDER_YT', 2, 12, 1, '2026-01-25 14:50:31', 0),
(32, 'EngelxD', '$2y$12$CyVOEtfxs9DhuLQ5tRCgfORVu7ow6/sGN7FSRCjFXHKT3KOCliJ7S', 'EngelxD', 4, 21, 1, '2026-01-25 16:02:18', 0),
(33, 'Dermek', '$2y$12$MOAiPYBxeqZpf17tRWcbdOIELw4Oxn5iwPaMcHpXGD3WCk3wQdmkO', 'dermek#8459', 4, 44, 1, '2026-01-25 17:21:36', 0),
(34, 'shinra', '$2y$12$frW9/bTEE3dBZ7ugd2sDHecmW4WqN191WXHv21/6SlCEYb00bmXMy', 'shinra#5984', 4, 58, 1, '2026-01-25 22:13:32', 0),
(35, 'Pol Hitler', '$2y$12$kLsmheGkPuhQKH1KfdKsyOHkD7uJVsZS/ask8POTB7KIKtJrjZxuW', 'Washawayusall', 4, NULL, 1, '2026-01-26 01:54:33', 0),
(36, 'Diegochan', '$2y$12$KaCDaJOy2hn0JK3E.85zEOhng2z15F6UOopoDFUc9cllmiZStOyja', 'Diegochan#1917', 4, NULL, 1, '2026-01-26 05:14:58', 0),
(37, 'Aguila23', '$2y$12$E4c5RPSyoDvoPe7pToIoDOHLOLOTRFXB1kVfuHpZQdKF2/5UNbkBq', 'death_the_kid', 4, NULL, 0, '2026-02-01 06:45:36', 0),
(38, 'Ashley04', '$2y$12$jqjc3jR3HAbYB6OvzvS1xO.X986WnM35m6v0m4EQ3EduyC8ts9baq', 'ashley0408005', 4, 66, 1, '2026-03-19 19:57:24', 0);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alianzas`
--
ALTER TABLE `alianzas`
  ADD PRIMARY KEY (`id_alianza`),
  ADD UNIQUE KEY `nombre_alianza` (`nombre_alianza`),
  ADD KEY `idx_nombre` (`nombre_alianza`),
  ADD KEY `idx_fundador` (`id_fundador`);

--
-- Indices de la tabla `alianzas_invitaciones`
--
ALTER TABLE `alianzas_invitaciones`
  ADD PRIMARY KEY (`id_invitacion`),
  ADD UNIQUE KEY `unica_invitacion` (`id_alianza`,`id_pais`,`estado`),
  ADD KEY `id_pais` (`id_pais`),
  ADD KEY `id_invitador` (`id_invitador`);

--
-- Indices de la tabla `cartillas`
--
ALTER TABLE `cartillas`
  ADD PRIMARY KEY (`id_cartilla`),
  ADD UNIQUE KEY `id_pais` (`id_pais`);

--
-- Indices de la tabla `categorias_investigacion`
--
ALTER TABLE `categorias_investigacion`
  ADD PRIMARY KEY (`id_categoria`),
  ADD KEY `idx_orden` (`orden`);

--
-- Indices de la tabla `enfoques`
--
ALTER TABLE `enfoques`
  ADD PRIMARY KEY (`id_enfoque`),
  ADD UNIQUE KEY `nombre_enfoque` (`nombre_enfoque`),
  ADD KEY `idx_tipo` (`tipo_enfoque`);

--
-- Indices de la tabla `historial_alianzas`
--
ALTER TABLE `historial_alianzas`
  ADD PRIMARY KEY (`id_historial_alianza`),
  ADD KEY `idx_pais_fecha` (`id_pais`,`fecha_accion`),
  ADD KEY `id_alianza_anterior` (`id_alianza_anterior`),
  ADD KEY `id_alianza_nueva` (`id_alianza_nueva`),
  ADD KEY `id_usuario_accion` (`id_usuario_accion`);

--
-- Indices de la tabla `historial_enfoques`
--
ALTER TABLE `historial_enfoques`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `id_enfoque_anterior` (`id_enfoque_anterior`),
  ADD KEY `id_enfoque_nuevo` (`id_enfoque_nuevo`),
  ADD KEY `idx_historial_enfoques_pais` (`id_pais`),
  ADD KEY `idx_historial_enfoques_fecha` (`fecha_cambio`);

--
-- Indices de la tabla `historial_paises`
--
ALTER TABLE `historial_paises`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `idx_usuario` (`id_usuario`),
  ADD KEY `idx_pais` (`id_pais`);

--
-- Indices de la tabla `historial_turnos`
--
ALTER TABLE `historial_turnos`
  ADD PRIMARY KEY (`id_historial_turno`),
  ADD KEY `idx_pais_fecha` (`id_pais`,`fecha_cambio`),
  ADD KEY `idx_fecha` (`fecha_cambio`),
  ADD KEY `id_gm` (`id_gm`),
  ADD KEY `id_usuario_accion` (`id_usuario_accion`);

--
-- Indices de la tabla `investigaciones`
--
ALTER TABLE `investigaciones`
  ADD PRIMARY KEY (`id_investigacion`),
  ADD KEY `idx_categoria` (`id_categoria`),
  ADD KEY `idx_subcategoria` (`id_subcategoria`),
  ADD KEY `idx_orden` (`orden`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `investigaciones_requisitos`
--
ALTER TABLE `investigaciones_requisitos`
  ADD PRIMARY KEY (`id_requisito`),
  ADD UNIQUE KEY `unique_requisito` (`id_investigacion`,`id_investigacion_requerida`),
  ADD KEY `id_investigacion_requerida` (`id_investigacion_requerida`);

--
-- Indices de la tabla `miembros_alianza`
--
ALTER TABLE `miembros_alianza`
  ADD PRIMARY KEY (`id_alianza`,`id_pais`),
  ADD KEY `id_pais` (`id_pais`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `idx_rol_receptor` (`id_rol_receptor`),
  ADD KEY `idx_usuario_receptor` (`id_usuario_receptor`),
  ADD KEY `idx_leido` (`leido`),
  ADD KEY `id_usuario_emisor` (`id_usuario_emisor`);

--
-- Indices de la tabla `paises`
--
ALTER TABLE `paises`
  ADD PRIMARY KEY (`id_pais`),
  ADD KEY `idx_nombre` (`nombre_pais`),
  ADD KEY `fk_paises_alianza_militar` (`id_alianza_militar`),
  ADD KEY `fk_paises_alianza_economica` (`id_alianza_economica`),
  ADD KEY `idx_paises_enfoque` (`id_enfoque_activo`);

--
-- Indices de la tabla `paises_investigaciones`
--
ALTER TABLE `paises_investigaciones`
  ADD PRIMARY KEY (`id_pais_investigacion`),
  ADD UNIQUE KEY `unique_pais_investigacion` (`id_pais`,`id_investigacion`),
  ADD KEY `idx_pais` (`id_pais`),
  ADD KEY `idx_investigacion` (`id_investigacion`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD KEY `idx_nombre` (`nombre_rol`);

--
-- Indices de la tabla `subcategorias_investigacion`
--
ALTER TABLE `subcategorias_investigacion`
  ADD PRIMARY KEY (`id_subcategoria`),
  ADD KEY `idx_orden` (`orden`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `turno_global`
--
ALTER TABLE `turno_global`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_rol` (`id_rol`),
  ADD KEY `idx_pais` (`id_pais`),
  ADD KEY `idx_usuarios_cambio_password` (`debe_cambiar_password`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alianzas`
--
ALTER TABLE `alianzas`
  MODIFY `id_alianza` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `alianzas_invitaciones`
--
ALTER TABLE `alianzas_invitaciones`
  MODIFY `id_invitacion` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `cartillas`
--
ALTER TABLE `cartillas`
  MODIFY `id_cartilla` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT de la tabla `categorias_investigacion`
--
ALTER TABLE `categorias_investigacion`
  MODIFY `id_categoria` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `enfoques`
--
ALTER TABLE `enfoques`
  MODIFY `id_enfoque` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `historial_alianzas`
--
ALTER TABLE `historial_alianzas`
  MODIFY `id_historial_alianza` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT de la tabla `historial_enfoques`
--
ALTER TABLE `historial_enfoques`
  MODIFY `id_historial` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT de la tabla `historial_paises`
--
ALTER TABLE `historial_paises`
  MODIFY `id_historial` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de la tabla `historial_turnos`
--
ALTER TABLE `historial_turnos`
  MODIFY `id_historial_turno` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de la tabla `investigaciones`
--
ALTER TABLE `investigaciones`
  MODIFY `id_investigacion` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `investigaciones_requisitos`
--
ALTER TABLE `investigaciones_requisitos`
  MODIFY `id_requisito` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `paises`
--
ALTER TABLE `paises`
  MODIFY `id_pais` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT de la tabla `paises_investigaciones`
--
ALTER TABLE `paises_investigaciones`
  MODIFY `id_pais_investigacion` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `subcategorias_investigacion`
--
ALTER TABLE `subcategorias_investigacion`
  MODIFY `id_subcategoria` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `turno_global`
--
ALTER TABLE `turno_global`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alianzas`
--
ALTER TABLE `alianzas`
  ADD CONSTRAINT `alianzas_ibfk_1` FOREIGN KEY (`id_fundador`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL;

--
-- Filtros para la tabla `alianzas_invitaciones`
--
ALTER TABLE `alianzas_invitaciones`
  ADD CONSTRAINT `alianzas_invitaciones_ibfk_1` FOREIGN KEY (`id_alianza`) REFERENCES `alianzas` (`id_alianza`) ON DELETE CASCADE,
  ADD CONSTRAINT `alianzas_invitaciones_ibfk_2` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`) ON DELETE CASCADE,
  ADD CONSTRAINT `alianzas_invitaciones_ibfk_3` FOREIGN KEY (`id_invitador`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cartillas`
--
ALTER TABLE `cartillas`
  ADD CONSTRAINT `cartillas_ibfk_1` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`) ON DELETE CASCADE;

--
-- Filtros para la tabla `historial_alianzas`
--
ALTER TABLE `historial_alianzas`
  ADD CONSTRAINT `historial_alianzas_ibfk_1` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`) ON DELETE CASCADE,
  ADD CONSTRAINT `historial_alianzas_ibfk_2` FOREIGN KEY (`id_alianza_anterior`) REFERENCES `alianzas` (`id_alianza`) ON DELETE SET NULL,
  ADD CONSTRAINT `historial_alianzas_ibfk_3` FOREIGN KEY (`id_alianza_nueva`) REFERENCES `alianzas` (`id_alianza`) ON DELETE SET NULL,
  ADD CONSTRAINT `historial_alianzas_ibfk_4` FOREIGN KEY (`id_usuario_accion`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL;

--
-- Filtros para la tabla `historial_enfoques`
--
ALTER TABLE `historial_enfoques`
  ADD CONSTRAINT `historial_enfoques_ibfk_1` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`) ON DELETE CASCADE,
  ADD CONSTRAINT `historial_enfoques_ibfk_2` FOREIGN KEY (`id_enfoque_anterior`) REFERENCES `enfoques` (`id_enfoque`) ON DELETE SET NULL,
  ADD CONSTRAINT `historial_enfoques_ibfk_3` FOREIGN KEY (`id_enfoque_nuevo`) REFERENCES `enfoques` (`id_enfoque`) ON DELETE SET NULL;

--
-- Filtros para la tabla `historial_paises`
--
ALTER TABLE `historial_paises`
  ADD CONSTRAINT `historial_paises_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `historial_turnos`
--
ALTER TABLE `historial_turnos`
  ADD CONSTRAINT `historial_turnos_ibfk_1` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`) ON DELETE CASCADE,
  ADD CONSTRAINT `historial_turnos_ibfk_2` FOREIGN KEY (`id_gm`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL,
  ADD CONSTRAINT `historial_turnos_ibfk_3` FOREIGN KEY (`id_usuario_accion`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL;

--
-- Filtros para la tabla `investigaciones`
--
ALTER TABLE `investigaciones`
  ADD CONSTRAINT `investigaciones_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias_investigacion` (`id_categoria`) ON DELETE CASCADE,
  ADD CONSTRAINT `investigaciones_ibfk_2` FOREIGN KEY (`id_subcategoria`) REFERENCES `subcategorias_investigacion` (`id_subcategoria`) ON DELETE SET NULL;

--
-- Filtros para la tabla `investigaciones_requisitos`
--
ALTER TABLE `investigaciones_requisitos`
  ADD CONSTRAINT `investigaciones_requisitos_ibfk_1` FOREIGN KEY (`id_investigacion`) REFERENCES `investigaciones` (`id_investigacion`) ON DELETE CASCADE,
  ADD CONSTRAINT `investigaciones_requisitos_ibfk_2` FOREIGN KEY (`id_investigacion_requerida`) REFERENCES `investigaciones` (`id_investigacion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `miembros_alianza`
--
ALTER TABLE `miembros_alianza`
  ADD CONSTRAINT `miembros_alianza_ibfk_1` FOREIGN KEY (`id_alianza`) REFERENCES `alianzas` (`id_alianza`) ON DELETE CASCADE,
  ADD CONSTRAINT `miembros_alianza_ibfk_2` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_usuario_emisor`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `paises`
--
ALTER TABLE `paises`
  ADD CONSTRAINT `fk_paises_alianza_economica` FOREIGN KEY (`id_alianza_economica`) REFERENCES `alianzas` (`id_alianza`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_paises_alianza_militar` FOREIGN KEY (`id_alianza_militar`) REFERENCES `alianzas` (`id_alianza`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_paises_enfoque` FOREIGN KEY (`id_enfoque_activo`) REFERENCES `enfoques` (`id_enfoque`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `paises_investigaciones`
--
ALTER TABLE `paises_investigaciones`
  ADD CONSTRAINT `paises_investigaciones_ibfk_1` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`) ON DELETE CASCADE,
  ADD CONSTRAINT `paises_investigaciones_ibfk_2` FOREIGN KEY (`id_investigacion`) REFERENCES `investigaciones` (`id_investigacion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `subcategorias_investigacion`
--
ALTER TABLE `subcategorias_investigacion`
  ADD CONSTRAINT `subcategorias_investigacion_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias_investigacion` (`id_categoria`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE RESTRICT,
  ADD CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`) ON DELETE SET NULL;
COMMIT;
