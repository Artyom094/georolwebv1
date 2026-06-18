-- Migración V9: Fotos de perfil de usuario
-- Ejecutar en la BD una sola vez

ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS avatar_url VARCHAR(255) NULL DEFAULT NULL
    COMMENT 'Ruta relativa a assets/uploads/avatars/';

-- Directorio de avatares (crear en servidor manualmente con chmod 775)
-- /var/www/html/georol/assets/uploads/avatars/
