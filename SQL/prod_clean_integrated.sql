-- Georol: preparar BD para produccion limpia
-- Objetivo:
-- 1) Integrar cambios de esquema pendientes (google_uid, avatar_url, aprobada, alianzas_invitaciones, cartilla_historial)
-- 2) Limpiar datos viejos manteniendo los catálogos base y los países

DELIMITER $$

DROP PROCEDURE IF EXISTS prod_clean_integrated $$
CREATE PROCEDURE prod_clean_integrated()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_table VARCHAR(128);

    DECLARE cur CURSOR FOR
        SELECT TABLE_NAME
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_TYPE = 'BASE TABLE'
                        AND TABLE_NAME NOT IN (
                                'enfoques',
                                'roles',
                                'categorias_investigacion',
                                'subcategorias_investigacion',
                                'paises',
                                'turno_global'
                        )
        ORDER BY TABLE_NAME;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    -- 1) Integrar cambios de esquema en `usuarios`
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'usuarios'
          AND COLUMN_NAME = 'avatar_url'
    ) THEN
        ALTER TABLE usuarios
            ADD COLUMN avatar_url VARCHAR(255) NULL DEFAULT NULL
            COMMENT 'Ruta relativa a assets/uploads/avatars/';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'usuarios'
          AND COLUMN_NAME = 'google_uid'
    ) THEN
        ALTER TABLE usuarios
            ADD COLUMN google_uid VARCHAR(191) NULL DEFAULT NULL
            AFTER id_pais;
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'usuarios'
          AND INDEX_NAME = 'ux_usuarios_google_uid'
    ) THEN
        ALTER TABLE usuarios
            ADD UNIQUE KEY ux_usuarios_google_uid (google_uid);
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'alianzas'
          AND COLUMN_NAME = 'aprobada'
    ) THEN
        ALTER TABLE alianzas
            ADD COLUMN aprobada TINYINT(1) DEFAULT 0 AFTER id_fundador;
    END IF;

    CREATE TABLE IF NOT EXISTS alianzas_invitaciones (
        id_invitacion int NOT NULL AUTO_INCREMENT,
        id_alianza int NOT NULL,
        id_pais int NOT NULL,
        id_invitador int NOT NULL,
        estado enum('pendiente','aceptada','rechazada') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
        fecha_invitacion timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        fecha_respuesta timestamp NULL DEFAULT NULL,
        PRIMARY KEY (id_invitacion),
        UNIQUE KEY unica_invitacion (id_alianza, id_pais, estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- 2) Integrar tabla de historial de cartillas (V10)
    CREATE TABLE IF NOT EXISTS cartilla_historial (
        id_historial int NOT NULL AUTO_INCREMENT,
        id_pais int NOT NULL,
        turno_global int NOT NULL,
        id_usuario int DEFAULT NULL,
        accion varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'guardado',
        nombre_pais varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
        bandera_url varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        total_tipos int NOT NULL DEFAULT '0',
        total_unidades int NOT NULL DEFAULT '0',
        total_produccion_ic int NOT NULL DEFAULT '0',
        total_produccion_im int NOT NULL DEFAULT '0',
        total_produccion_it int NOT NULL DEFAULT '0',
        total_mantenimiento_im int NOT NULL DEFAULT '0',
        balance_im int NOT NULL DEFAULT '0',
        unidades_legado longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        snapshot_json longtext COLLATE utf8mb4_unicode_ci NOT NULL,
        reporte_texto longtext COLLATE utf8mb4_unicode_ci NOT NULL,
        created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id_historial),
        UNIQUE KEY uniq_pais_turno (id_pais, turno_global),
        KEY idx_turno_global (turno_global),
        KEY idx_id_pais (id_pais)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- 3) Limpiar todas las tablas operativas
    SET FOREIGN_KEY_CHECKS = 0;

    DROP TABLE IF EXISTS miembros_alianza;
    DROP TABLE IF EXISTS notificaciones;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_table;
        IF done = 1 THEN
            LEAVE read_loop;
        END IF;

        SET @sql_stmt = CONCAT('TRUNCATE TABLE `', v_table, '`');
        PREPARE stmt FROM @sql_stmt;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END LOOP;
    CLOSE cur;

    -- 4) Restaurar catálogos base y estado inicial
    INSERT INTO roles (id_rol, nombre_rol) VALUES
        (1, 'Administrador'),
        (2, 'GM'),
        (3, 'Auditor'),
        (4, 'Participante')
    ON DUPLICATE KEY UPDATE nombre_rol = VALUES(nombre_rol);

    INSERT INTO enfoques (id_enfoque, nombre_enfoque, tipo_enfoque, descripcion, multiplicador_ic, multiplicador_im, multiplicador_it, bonus_defensa, cooldown_guerra_reducido, activo) VALUES
        (1, 'Puño de Acero', 'Atacante', 'Cooldown reducido a 1 turno (alternado 3-1-3-1). Cada guerra ofensiva consecutiva genera penalización progresiva en IM.', NULL, NULL, NULL, 0, 1, 1),
        (2, 'Muralla Inquebrantable', 'Defensor', 'Bonus de +10 a la calculadora en las batallas del primer turno de cada guerra defensiva.', NULL, NULL, NULL, 10, 0, 1),
        (3, 'Oro Antes que Balas', 'IC', 'IC: x5 → x3. Penalización: Producción IM x5 → x3', '3.0', '3.0', NULL, 0, 0, 1),
        (4, 'Forja de Guerra', 'IM', 'IM: x2 → x3. Penalización: Producción IC x2 → x1.4', '1.4', '7.0', NULL, 0, 0, 1),
        (5, 'Guerra del Futuro', 'IT', 'IT: x8 → x11. Penalización: Producción IM x5 → x3.5', NULL, '3.5', '11.0', 0, 0, 1)
    ON DUPLICATE KEY UPDATE
        nombre_enfoque = VALUES(nombre_enfoque),
        tipo_enfoque = VALUES(tipo_enfoque),
        descripcion = VALUES(descripcion),
        multiplicador_ic = VALUES(multiplicador_ic),
        multiplicador_im = VALUES(multiplicador_im),
        multiplicador_it = VALUES(multiplicador_it),
        bonus_defensa = VALUES(bonus_defensa),
        cooldown_guerra_reducido = VALUES(cooldown_guerra_reducido),
        activo = VALUES(activo);

    INSERT INTO categorias_investigacion (id_categoria, nombre_categoria, orden) VALUES
        (1, 'Aéreo', 1),
        (2, 'Terrestre', 2),
        (3, 'Marítimo', 3),
        (4, 'Unidades Especiales', 4)
    ON DUPLICATE KEY UPDATE
        nombre_categoria = VALUES(nombre_categoria),
        orden = VALUES(orden);

    INSERT INTO subcategorias_investigacion (id_subcategoria, id_categoria, nombre_subcategoria, orden) VALUES
        (1, 2, 'Infantería', 1),
        (2, 2, 'Blindados', 2),
        (3, 2, 'Artillería', 3),
        (4, 2, 'Defensa Antiaérea', 4),
        (5, 3, 'Submarinos', 1),
        (6, 3, 'Fragatas y Corbetas', 2),
        (7, 3, 'Destructores y Cruceros', 3),
        (8, 3, 'Portaaviones', 4),
        (9, 3, 'Defensa Costera', 5)
    ON DUPLICATE KEY UPDATE
        id_categoria = VALUES(id_categoria),
        nombre_subcategoria = VALUES(nombre_subcategoria),
        orden = VALUES(orden);

    UPDATE paises
       SET idg_puntos = 0,
           turno_actual = 1,
           id_alianza_militar = NULL,
           id_alianza_economica = NULL,
           id_enfoque_activo = NULL,
           fecha_ultimo_cambio_enfoque = NULL,
           turnos_sin_guerra_ofensiva = 0,
           guerras_ofensivas_consecutivas = 0;

    -- Estado inicial de turno global
    INSERT INTO turno_global (id, turno_actual)
    VALUES (1, 1)
    ON DUPLICATE KEY UPDATE turno_actual = VALUES(turno_actual);

    SET FOREIGN_KEY_CHECKS = 1;
END $$

DELIMITER ;

CALL prod_clean_integrated();
DROP PROCEDURE prod_clean_integrated;
