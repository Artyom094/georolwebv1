-- Migración V11 — Polymarket
-- Ejecutar para limpiar/eliminar tablas antiguas de Polymarket e iniciar con la estructura correcta.

DROP TABLE IF EXISTS market_reputation;
DROP TABLE IF EXISTS market_results;
DROP TABLE IF EXISTS market_suggestions;
DROP TABLE IF EXISTS market_bets;
DROP TABLE IF EXISTS market_options;
DROP TABLE IF EXISTS markets;

-- Crear tabla markets
CREATE TABLE IF NOT EXISTS markets (
    id_market int NOT NULL AUTO_INCREMENT,
    titulo varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
    descripcion text COLLATE utf8mb4_unicode_ci,
    categoria varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
    fecha_cierre datetime NOT NULL,
    estado enum('abierto','silencioso','cerrado','resuelto','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'abierto',
    apuesta_minima_ic int NOT NULL DEFAULT '1',
    alianzas_ven_apuestas tinyint(1) NOT NULL DEFAULT '0',
    alianza_info_publica text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    cierre_silencioso_minutos int NOT NULL DEFAULT '0',
    id_opcion_ganadora int DEFAULT NULL,
    id_usuario_creador int NOT NULL,
    id_usuario_resuelve int DEFAULT NULL,
    fecha_creacion timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fecha_cierre_real datetime DEFAULT NULL,
    fecha_resolucion datetime DEFAULT NULL,
    snapshot_silencioso_json longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (id_market),
    KEY idx_markets_estado (estado),
    KEY idx_markets_cierre (fecha_cierre),
    KEY idx_markets_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla market_options
CREATE TABLE IF NOT EXISTS market_options (
    id_option int NOT NULL AUTO_INCREMENT,
    id_market int NOT NULL,
    titulo varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
    descripcion text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    orden int NOT NULL DEFAULT '0',
    fecha_creacion timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_option),
    UNIQUE KEY uniq_market_option (id_market, titulo),
    KEY idx_market_options_market (id_market)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla market_bets
CREATE TABLE IF NOT EXISTS market_bets (
    id_bet int NOT NULL AUTO_INCREMENT,
    id_market int NOT NULL,
    id_option int NOT NULL,
    id_usuario int NOT NULL,
    ic_apostado int NOT NULL,
    estado enum('pendiente','validada','rechazada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
    observaciones text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    validado_por int DEFAULT NULL,
    validado_en datetime DEFAULT NULL,
    fecha_apuesta timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_bet),
    UNIQUE KEY uniq_market_user_bet (id_market, id_usuario),
    KEY idx_market_bets_market (id_market),
    KEY idx_market_bets_option (id_option),
    KEY idx_market_bets_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla market_suggestions
CREATE TABLE IF NOT EXISTS market_suggestions (
    id_suggestion int NOT NULL AUTO_INCREMENT,
    id_usuario int NOT NULL,
    titulo varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
    descripcion text COLLATE utf8mb4_unicode_ci,
    categoria varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
    fecha_cierre datetime DEFAULT NULL,
    apuesta_minima_ic int NOT NULL DEFAULT '1',
    alianzas_ven_apuestas tinyint(1) NOT NULL DEFAULT '0',
    alianza_info_publica text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    cierre_silencioso_minutos int NOT NULL DEFAULT '0',
    opciones_json longtext COLLATE utf8mb4_unicode_ci NOT NULL,
    estado enum('pendiente','aprobada','rechazada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
    id_market int DEFAULT NULL,
    revisado_por int DEFAULT NULL,
    revisado_en datetime DEFAULT NULL,
    observaciones text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    fecha_creacion timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_suggestion),
    KEY idx_market_suggestions_estado (estado),
    KEY idx_market_suggestions_user (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla market_results
CREATE TABLE IF NOT EXISTS market_results (
    id_result int NOT NULL AUTO_INCREMENT,
    id_market int NOT NULL,
    id_bet int NOT NULL,
    id_usuario int NOT NULL,
    id_option int NOT NULL,
    apuesta_ic int NOT NULL,
    pozo_total_ic int NOT NULL,
    pozo_ganador_ic int NOT NULL,
    multiplicador decimal(12,4) NOT NULL DEFAULT '0.0000',
    ganancia_ic int NOT NULL DEFAULT '0',
    ganancia_neta_ic int NOT NULL DEFAULT '0',
    opcion_ganadora_titulo varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
    turno_global int DEFAULT NULL,
    fecha_resolucion timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_result),
    UNIQUE KEY uniq_market_bet_result (id_bet),
    KEY idx_market_results_market (id_market),
    KEY idx_market_results_user (id_usuario),
    KEY idx_market_results_turno (turno_global)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla market_reputation
CREATE TABLE IF NOT EXISTS market_reputation (
    id_reputation int NOT NULL AUTO_INCREMENT,
    id_usuario int NOT NULL,
    mercados_participados int NOT NULL DEFAULT '0',
    mercados_ganados int NOT NULL DEFAULT '0',
    mercados_perdidos int NOT NULL DEFAULT '0',
    total_ic_apostado int NOT NULL DEFAULT '0',
    total_ic_ganado int NOT NULL DEFAULT '0',
    precision_pct decimal(5,2) NOT NULL DEFAULT '0.00',
    fecha_actualizacion timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_reputation),
    UNIQUE KEY uniq_market_reputation_user (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
