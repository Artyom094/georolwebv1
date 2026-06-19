<?php
// Helper functions for image processing, security, and country history

/**
 * Generate CSRF token
 * @return string - CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * @param string $token - Token to validate
 * @return bool - True if valid
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Convert uploaded image to PNG and save with pais_id_name format.
 * @param array  $file         — $_FILES element
 * @param int    $id_pais      — Country ID (used as filename prefix)
 * @param string $nombre_pais  — Country name (sanitized for filename)
 * @return array ['success'=>bool, 'path'=>string, 'error'=>string]
 */
function processAndSaveFlagImage($file, $id_pais, $nombre_pais) {
    $result = ['success' => false, 'path' => '', 'error' => ''];

    // ── GD check ──
    if (!function_exists('imagecreatefrompng')) {
        $result['error'] = 'GD Library no disponible en el servidor';
        return $result;
    }

    // ── Upload error ──
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errs = [
            UPLOAD_ERR_INI_SIZE   => 'Archivo demasiado grande (max: ' . ini_get('upload_max_filesize') . ')',
            UPLOAD_ERR_FORM_SIZE  => 'Archivo demasiado grande (límite formulario)',
            UPLOAD_ERR_PARTIAL    => 'Subida incompleta, intenta de nuevo',
            UPLOAD_ERR_NO_TMP_DIR => 'Error de servidor: directorio temporal faltante',
            UPLOAD_ERR_CANT_WRITE => 'Error de servidor: sin permisos de escritura',
        ];
        $result['error'] = $errs[$file['error']] ?? 'Error desconocido (código ' . $file['error'] . ')';
        return $result;
    }

    // ── Tamaño máximo: 10 MB ──
    if ($file['size'] > 10 * 1024 * 1024) {
        $result['error'] = 'Imagen demasiado grande. Máximo 10 MB.';
        return $result;
    }

    // ── MIME real ──
    $finfo     = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $mime_to_ext = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        'image/bmp'  => 'bmp',
    ];

    if (!isset($mime_to_ext[$mime_type])) {
        $result['error'] = 'Formato no permitido. Solo: JPG, PNG, GIF, WEBP, BMP.';
        return $result;
    }

    $detected_ext = $mime_to_ext[$mime_type];

    // ── Rutas ──
    $nombre_sanitizado = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombre_pais);
    $filename          = intval($id_pais) . '_' . $nombre_sanitizado . '.png';
    $upload_dir        = dirname(__DIR__) . '/assets/uploads/banderas/';
    $full_path         = $upload_dir . $filename;
    $web_path          = 'assets/uploads/banderas/' . $filename;

    // ── Crear directorio si falta ──
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0775, true)) {
            $result['error'] = 'No se pudo crear el directorio de subidas.';
            return $result;
        }
    }

    // ── Cargar imagen fuente ──
    $source = null;
    switch ($detected_ext) {
        case 'jpg': $source = @imagecreatefromjpeg($file['tmp_name']); break;
        case 'png': $source = @imagecreatefrompng($file['tmp_name']);  break;
        case 'gif': $source = @imagecreatefromgif($file['tmp_name']);  break;
        case 'webp': $source = @imagecreatefromwebp($file['tmp_name']); break;
        case 'bmp':
            // BMP no tiene función directa en versiones antiguas
            if (function_exists('imagecreatefrombmp')) {
                $source = @imagecreatefrombmp($file['tmp_name']);
            } else {
                $result['error'] = 'BMP no soportado en esta versión de PHP/GD. Usa PNG o JPG.';
                return $result;
            }
            break;
    }

    if (!$source) {
        $result['error'] = 'No se pudo leer la imagen. Asegúrate que no esté corrupta.';
        return $result;
    }

    // ── Guardar como PNG ──
    if (imagepng($source, $full_path, 6)) { // compresión 6 (balance velocidad/tamaño)
        imagedestroy($source);
        chmod($full_path, 0664); // asegurar que sea legible por Apache
        $result['success'] = true;
        $result['path']    = $web_path;
    } else {
        imagedestroy($source);
        $result['error'] = 'Error al escribir la imagen en disco. Revisa permisos del directorio.';
    }

    return $result;
}



/**
 * Process and save user avatar (profile photo).
 * Crops to square, saves as JPEG (80% quality) for small file sizes.
 * @param array  $file      — $_FILES element
 * @param int    $id_usuario — User ID (used as filename)
 * @return array ['success'=>bool, 'path'=>string, 'error'=>string]
 */
function processAndSaveAvatarImage($file, $id_usuario) {
    $result = ['success' => false, 'path' => '', 'error' => ''];

    if (!function_exists('imagecreatefrompng')) {
        $result['error'] = 'GD Library no disponible.';
        return $result;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errs = [
            UPLOAD_ERR_INI_SIZE   => 'Archivo muy grande (máx ' . ini_get('upload_max_filesize') . ')',
            UPLOAD_ERR_PARTIAL    => 'Subida incompleta, intenta de nuevo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Error de servidor: directorio temporal faltante.',
            UPLOAD_ERR_CANT_WRITE => 'Error de servidor: sin permisos de escritura.',
        ];
        $result['error'] = $errs[$file['error']] ?? 'Error código ' . $file['error'];
        return $result;
    }

    // Max 5 MB for avatars
    if ($file['size'] > 5 * 1024 * 1024) {
        $result['error'] = 'La foto es muy grande. Máximo 5 MB.';
        return $result;
    }

    // Detect real MIME
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mime     = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $mime_map = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($mime_map[$mime])) {
        $result['error'] = 'Formato no permitido. Usa JPG, PNG, GIF o WEBP.';
        return $result;
    }
    $ext = $mime_map[$mime];

    // Paths
    $filename   = 'avatar_' . intval($id_usuario) . '.jpg'; // always saved as JPEG
    $upload_dir = dirname(__DIR__) . '/assets/uploads/avatars/';
    $full_path  = $upload_dir . $filename;
    $web_path   = 'assets/uploads/avatars/' . $filename;

    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0775, true)) {
            $result['error'] = 'No se pudo crear el directorio de avatares.';
            return $result;
        }
    }

    // Load source
    $source = null;
    switch ($ext) {
        case 'jpg':  $source = @imagecreatefromjpeg($file['tmp_name']); break;
        case 'png':  $source = @imagecreatefrompng($file['tmp_name']);  break;
        case 'gif':  $source = @imagecreatefromgif($file['tmp_name']);  break;
        case 'webp': $source = @imagecreatefromwebp($file['tmp_name']); break;
    }
    if (!$source) {
        $result['error'] = 'No se pudo leer la imagen. ¿Está corrupta?';
        return $result;
    }

    $orig_w = imagesx($source);
    $orig_h = imagesy($source);

    // Square-crop from center
    $size    = min($orig_w, $orig_h);
    $off_x   = intval(($orig_w - $size) / 2);
    $off_y   = intval(($orig_h - $size) / 2);
    $target_size = 256; // output 256×256 px

    $square = imagecreatetruecolor($target_size, $target_size);
    imagecopyresampled($square, $source, 0, 0, $off_x, $off_y, $target_size, $target_size, $size, $size);
    imagedestroy($source);

    // Save as JPEG 82%
    if (imagejpeg($square, $full_path, 82)) {
        imagedestroy($square);
        chmod($full_path, 0664);
        $result['success'] = true;
        $result['path']    = $web_path;
    } else {
        imagedestroy($square);
        $result['error'] = 'Error al escribir el avatar en disco. Revisa permisos de ' . $upload_dir;
    }

    return $result;
}

/**
 * Returns HTML for a user avatar: img if exists, else initials circle.
 * @param string|null $avatar_url  — stored path (may be empty/null)
 * @param string      $username    — fallback initials source
 * @param int         $size        — px size for the element (default 36)
 * @param string      $extra_style — extra inline CSS
 */
function getAvatarHtml($avatar_url, $username, $size = 36, $extra_style = '') {
    $initial   = strtoupper(substr($username, 0, 1));
    $font_size = round($size * 0.42);
    $border_r  = round($size / 2);

    if (!empty($avatar_url)) {
        if (strpos($avatar_url, 'http://') === 0 || strpos($avatar_url, 'https://') === 0) {
            return '<img src="' . htmlspecialchars($avatar_url) . '"'
                 . ' alt="' . htmlspecialchars($username) . '"'
                 . ' style="width:' . $size . 'px;height:' . $size . 'px;border-radius:' . $border_r . 'px;object-fit:cover;flex-shrink:0;' . $extra_style . '">';
        } elseif (file_exists(dirname(__DIR__) . '/' . $avatar_url)) {
            return '<img src="' . htmlspecialchars($avatar_url) . '?v=' . filemtime(dirname(__DIR__) . '/' . $avatar_url) . '"'
                 . ' alt="' . htmlspecialchars($username) . '"'
                 . ' style="width:' . $size . 'px;height:' . $size . 'px;border-radius:' . $border_r . 'px;object-fit:cover;flex-shrink:0;' . $extra_style . '">';
        }
    }

    return '<div style="width:' . $size . 'px;height:' . $size . 'px;border-radius:' . $border_r . 'px;'
         . 'background:linear-gradient(135deg,var(--accent),#6610f2);'
         . 'display:flex;align-items:center;justify-content:center;'
         . 'font-size:' . $font_size . 'px;color:#fff;font-weight:700;flex-shrink:0;' . $extra_style . '">'
         . $initial . '</div>';
}

/**
 * Ensure the editable site documents table exists and has the default records.
 */
function ensureSiteDocumentsTable(PDO $conn) {
    $conn->exec("CREATE TABLE IF NOT EXISTS site_documents (
        id_documento int NOT NULL AUTO_INCREMENT,
        slug varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
        titulo varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
        contenido longtext COLLATE utf8mb4_unicode_ci NOT NULL,
        editable_by_admin tinyint(1) NOT NULL DEFAULT '1',
        editable_by_gm tinyint(1) NOT NULL DEFAULT '0',
        updated_by int DEFAULT NULL,
        updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id_documento),
        UNIQUE KEY slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $defaults = [
        [
            'slug' => 'privacidad',
            'titulo' => 'Políticas de Privacidad',
            'contenido' => "Política de Privacidad de Georol\n\n1. La información de cuenta se usa únicamente para operar el sistema y la comunidad.\n2. Los datos visibles del perfil, país y rol se muestran dentro de la plataforma según el acceso del usuario.\n3. No compartimos credenciales ni contraseñas.\n4. Los archivos subidos por usuarios (banderas, avatares u otros recursos) se almacenan para el funcionamiento del sitio.\n5. El equipo de administración puede revisar información técnica o de moderación cuando sea necesario para mantener la seguridad y el orden.",
            'editable_by_admin' => 1,
            'editable_by_gm' => 0,
        ],
        [
            'slug' => 'reglas',
            'titulo' => 'Reglas de Georol',
            'contenido' => "Reglas de Georol\n\n1. Respeta a los demás miembros de la comunidad.\n2. No uses nombres, mensajes ni descripciones ofensivas o que promuevan acoso.\n3. Las decisiones de administración y Game Master deben seguir el flujo de moderación establecido.\n4. No manipules el sistema ni intentes acceder a funciones sin permiso.\n5. Los Game Masters pueden actualizar este documento cuando cambie la dinámica del juego.\n6. El incumplimiento de estas reglas puede causar advertencias, suspensión o expulsión según la gravedad.",
            'editable_by_admin' => 1,
            'editable_by_gm' => 1,
        ],
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO site_documents (slug, titulo, contenido, editable_by_admin, editable_by_gm) VALUES (:slug, :titulo, :contenido, :editable_by_admin, :editable_by_gm)");
    foreach ($defaults as $doc) {
        $stmt->execute([
            ':slug' => $doc['slug'],
            ':titulo' => $doc['titulo'],
            ':contenido' => $doc['contenido'],
            ':editable_by_admin' => $doc['editable_by_admin'],
            ':editable_by_gm' => $doc['editable_by_gm'],
        ]);
    }
}

/**
 * Fetch a site document by slug.
 */
function getSiteDocument(PDO $conn, $slug) {
    ensureSiteDocumentsTable($conn);
    $stmt = $conn->prepare("SELECT * FROM site_documents WHERE slug = :slug LIMIT 1");
    $stmt->execute([':slug' => $slug]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Insert or update a site document.
 */
function saveSiteDocument(PDO $conn, $slug, $titulo, $contenido, $updated_by) {
    ensureSiteDocumentsTable($conn);
    $exists = $conn->prepare("SELECT id_documento FROM site_documents WHERE slug = :slug LIMIT 1");
    $exists->execute([':slug' => $slug]);

    if ($exists->fetchColumn()) {
        $stmt = $conn->prepare("UPDATE site_documents SET titulo = :titulo, contenido = :contenido, updated_by = :updated_by WHERE slug = :slug");
        $stmt->execute([
            ':titulo' => $titulo,
            ':contenido' => $contenido,
            ':updated_by' => $updated_by,
            ':slug' => $slug,
        ]);
    } else {
        $insert = $conn->prepare("INSERT INTO site_documents (slug, titulo, contenido, updated_by) VALUES (:slug, :titulo, :contenido, :updated_by)");
        $insert->execute([
            ':slug' => $slug,
            ':titulo' => $titulo,
            ':contenido' => $contenido,
            ':updated_by' => $updated_by,
        ]);
    }
}

/**
 * Render a document as safe readable HTML.
 */
function renderSiteDocumentHtml($contenido) {
    return nl2br(htmlspecialchars($contenido, ENT_QUOTES, 'UTF-8'));
}

/**
 * Ensure the cartilla history table exists.
 */
function ensureCartillaHistoryTable(PDO $conn) {
    $conn->exec("CREATE TABLE IF NOT EXISTS cartilla_historial (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/**
 * Get the current global turn.
 */
function getCurrentTurn(PDO $conn) {
    $turno = $conn->query("SELECT turno_actual FROM turno_global WHERE id = 1 LIMIT 1")->fetchColumn();
    return $turno !== false ? intval($turno) : 1;
}

/**
 * Build a complete cartilla snapshot for a country.
 */
function buildCartillaHistorySnapshot(PDO $conn, $id_pais) {
    ensureCartillaHistoryTable($conn);

    $turno_global = getCurrentTurn($conn);

    $pais_stmt = $conn->prepare("SELECT p.id_pais, p.nombre_pais, p.bandera_url FROM paises p WHERE p.id_pais = :id LIMIT 1");
    $pais_stmt->execute([':id' => $id_pais]);
    $pais = $pais_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pais) {
        return [null, null];
    }

    $types_stmt = $conn->prepare("\n        SELECT\n            ct.id_tipo, ct.id_categoria, ct.nombre, ct.tipo, ct.multiplicador, ct.unidad_produccion,\n            ct.orden, ct.activo, cc.nombre AS categoria_nombre, cc.color AS categoria_color, cc.icono AS categoria_icono,\n            COALESCE(v.cantidad, 0) AS cantidad\n        FROM cartilla_tipos ct\n        LEFT JOIN cartilla_categorias cc ON ct.id_categoria = cc.id_categoria\n        LEFT JOIN cartilla_valores v ON v.id_tipo = ct.id_tipo AND v.id_pais = :id_pais\n        ORDER BY COALESCE(cc.orden, 9999), ct.orden, ct.nombre\n    ");
    $types_stmt->execute([':id_pais' => $id_pais]);
    $rows = $types_stmt->fetchAll(PDO::FETCH_ASSOC);

    $legacy_stmt = $conn->prepare("\n        SELECT\n            v.id_tipo, COALESCE(v.cantidad, 0) AS cantidad,\n            ct.id_categoria, ct.nombre, ct.tipo, ct.multiplicador, ct.unidad_produccion, ct.orden, ct.activo,\n            cc.nombre AS categoria_nombre, cc.color AS categoria_color, cc.icono AS categoria_icono\n        FROM cartilla_valores v\n        LEFT JOIN cartilla_tipos ct ON v.id_tipo = ct.id_tipo\n        LEFT JOIN cartilla_categorias cc ON ct.id_categoria = cc.id_categoria\n        WHERE v.id_pais = :id_pais AND ct.id_tipo IS NULL\n        ORDER BY v.id_tipo\n    ");
    $legacy_stmt->execute([':id_pais' => $id_pais]);
    $legacy_rows = $legacy_stmt->fetchAll(PDO::FETCH_ASSOC);

    $snapshot_items = [];
    $grouped = [];
    $legacy_items = [];
    $totals = [
        'total_tipos' => 0,
        'total_unidades' => 0,
        'produccion_ic' => 0,
        'produccion_im' => 0,
        'produccion_it' => 0,
        'mantenimiento_im' => 0,
        'balance_im' => 0,
    ];

    $add_item = function(array $item, $is_legacy = false) use (&$snapshot_items, &$grouped, &$legacy_items, &$totals) {
        $cantidad = intval($item['cantidad'] ?? 0);
        $multiplicador = floatval($item['multiplicador'] ?? 0);
        $tipo = $item['tipo'] ?? 'desconocido';
        $unidad_produccion = $item['unidad_produccion'] ?? null;
        $legacy = $is_legacy || intval($item['activo'] ?? 1) !== 1 || empty($item['id_tipo']);

        $normalized = [
            'id_tipo' => isset($item['id_tipo']) ? intval($item['id_tipo']) : null,
            'nombre' => $item['nombre'] ?? ('Tipo #' . ($item['id_tipo'] ?? 'N/A')),
            'tipo' => $tipo,
            'multiplicador' => $multiplicador,
            'unidad_produccion' => $unidad_produccion,
            'cantidad' => $cantidad,
            'categoria_nombre' => $item['categoria_nombre'] ?? 'Sin categoría',
            'categoria_color' => $item['categoria_color'] ?? 'secondary',
            'categoria_icono' => $item['categoria_icono'] ?? 'box',
            'activo' => intval($item['activo'] ?? 0),
            'legacy' => $legacy,
        ];

        $snapshot_items[] = $normalized;
        $grouped[$normalized['categoria_nombre']][] = $normalized;

        if ($legacy) {
            $legacy_items[] = $normalized;
        }

        $totals['total_tipos']++;
        $totals['total_unidades'] += $cantidad;

        if ($tipo === 'produccion' && $unidad_produccion) {
            $produccion = $cantidad * $multiplicador;
            if ($unidad_produccion === 'IC') {
                $totals['produccion_ic'] += $produccion;
            } elseif ($unidad_produccion === 'IM') {
                $totals['produccion_im'] += $produccion;
            } elseif ($unidad_produccion === 'IT') {
                $totals['produccion_it'] += $produccion;
            }
        } elseif ($tipo === 'mantenimiento') {
            $totals['mantenimiento_im'] += $cantidad * $multiplicador;
        }
    };

    foreach ($rows as $row) {
        $add_item($row, false);
    }

    foreach ($legacy_rows as $row) {
        $row['id_tipo'] = isset($row['id_tipo']) ? intval($row['id_tipo']) : null;
        $row['nombre'] = 'Unidad eliminada #' . intval($row['id_tipo']);
        $row['tipo'] = 'legacy';
        $row['multiplicador'] = 0;
        $row['unidad_produccion'] = null;
        $row['activo'] = 0;
        $row['categoria_nombre'] = 'Legado';
        $row['categoria_color'] = 'secondary';
        $row['categoria_icono'] = 'clock-history';
        $add_item($row, true);
    }

    $totals['balance_im'] = $totals['produccion_im'] - $totals['mantenimiento_im'];

    $report_lines = [];
    $report_lines[] = 'Registro de cartilla';
    $report_lines[] = 'País: ' . $pais['nombre_pais'];
    $report_lines[] = 'Turno global: #' . $turno_global;
    $report_lines[] = 'Fecha: ' . date('d/m/Y H:i:s');
    $report_lines[] = 'Resumen industria:';
    $report_lines[] = 'IC: ' . number_format($totals['produccion_ic']);
    $report_lines[] = 'IM: ' . number_format($totals['produccion_im']);
    $report_lines[] = 'IT: ' . number_format($totals['produccion_it']);
    $report_lines[] = 'Mantenimiento IM: ' . number_format($totals['mantenimiento_im']);
    $report_lines[] = 'Balance IM: ' . number_format($totals['balance_im']);
    $report_lines[] = '';

    foreach ($grouped as $categoria => $items) {
        $report_lines[] = '[' . $categoria . ']';
        foreach ($items as $item) {
            $legacy_tag = $item['legacy'] ? ' (legado)' : '';
            $unidad_tag = $item['unidad_produccion'] ? ' [' . $item['unidad_produccion'] . ']' : '';
            $mult_tag = $item['multiplicador'] ? ' x' . $item['multiplicador'] : '';
            $report_lines[] = '- ' . $item['nombre'] . ': ' . $item['cantidad'] . $unidad_tag . $mult_tag . $legacy_tag;
        }
        $report_lines[] = '';
    }

    if (!empty($legacy_items)) {
        $report_lines[] = 'Unidades de legado:';
        foreach ($legacy_items as $item) {
            $report_lines[] = '- #' . $item['id_tipo'] . ' ' . $item['nombre'] . ': ' . $item['cantidad'];
        }
        $report_lines[] = '';
    }

    $snapshot = [
        'pais' => $pais,
        'turno_global' => $turno_global,
        'totales' => $totals,
        'categorias' => $grouped,
        'unidades_legado' => $legacy_items,
        'items' => $snapshot_items,
    ];

    return [$snapshot, implode("\n", $report_lines)];
}

/**
 * Store or refresh a cartilla snapshot for the current turn.
 */
function saveCartillaHistorySnapshot(PDO $conn, $id_pais, $id_usuario = null, $accion = 'guardado') {
    list($snapshot, $report_text) = buildCartillaHistorySnapshot($conn, $id_pais);

    if (!$snapshot || !$report_text) {
        return false;
    }

    $stmt = $conn->prepare("\n        INSERT INTO cartilla_historial\n            (id_pais, turno_global, id_usuario, accion, nombre_pais, bandera_url, total_tipos, total_unidades, total_produccion_ic, total_produccion_im, total_produccion_it, total_mantenimiento_im, balance_im, unidades_legado, snapshot_json, reporte_texto)\n        VALUES\n            (:id_pais, :turno_global, :id_usuario, :accion, :nombre_pais, :bandera_url, :total_tipos, :total_unidades, :total_produccion_ic, :total_produccion_im, :total_produccion_it, :total_mantenimiento_im, :balance_im, :unidades_legado, :snapshot_json, :reporte_texto)\n        ON DUPLICATE KEY UPDATE\n            id_usuario = VALUES(id_usuario),\n            accion = VALUES(accion),\n            nombre_pais = VALUES(nombre_pais),\n            bandera_url = VALUES(bandera_url),\n            total_tipos = VALUES(total_tipos),\n            total_unidades = VALUES(total_unidades),\n            total_produccion_ic = VALUES(total_produccion_ic),\n            total_produccion_im = VALUES(total_produccion_im),\n            total_produccion_it = VALUES(total_produccion_it),\n            total_mantenimiento_im = VALUES(total_mantenimiento_im),\n            balance_im = VALUES(balance_im),\n            unidades_legado = VALUES(unidades_legado),\n            snapshot_json = VALUES(snapshot_json),\n            reporte_texto = VALUES(reporte_texto),\n            created_at = CURRENT_TIMESTAMP\n    ");

    $stmt->execute([
        ':id_pais' => intval($id_pais),
        ':turno_global' => intval($snapshot['turno_global']),
        ':id_usuario' => $id_usuario !== null ? intval($id_usuario) : null,
        ':accion' => $accion,
        ':nombre_pais' => $snapshot['pais']['nombre_pais'],
        ':bandera_url' => $snapshot['pais']['bandera_url'] ?? null,
        ':total_tipos' => intval($snapshot['totales']['total_tipos']),
        ':total_unidades' => intval($snapshot['totales']['total_unidades']),
        ':total_produccion_ic' => intval($snapshot['totales']['produccion_ic']),
        ':total_produccion_im' => intval($snapshot['totales']['produccion_im']),
        ':total_produccion_it' => intval($snapshot['totales']['produccion_it']),
        ':total_mantenimiento_im' => intval($snapshot['totales']['mantenimiento_im']),
        ':balance_im' => intval($snapshot['totales']['balance_im']),
        ':unidades_legado' => json_encode($snapshot['unidades_legado'], JSON_UNESCAPED_UNICODE),
        ':snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
        ':reporte_texto' => $report_text,
    ]);

    return true;
}

/**
 * Fetch the cartilla snapshot for a country and turn.
 */
function getCartillaHistoryRecord(PDO $conn, $id_pais, $turno_global = null) {
    ensureCartillaHistoryTable($conn);

    if ($turno_global === null) {
        $turno_global = getCurrentTurn($conn);
    }

    $stmt = $conn->prepare("\n        SELECT h.*, u.username\n        FROM cartilla_historial h\n        LEFT JOIN usuarios u ON u.id_usuario = h.id_usuario\n        WHERE h.id_pais = :id_pais AND h.turno_global = :turno_global\n        ORDER BY h.created_at DESC, h.id_historial DESC\n        LIMIT 1\n    ");
    $stmt->execute([
        ':id_pais' => intval($id_pais),
        ':turno_global' => intval($turno_global),
    ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        return null;
    }

    $record['snapshot'] = json_decode($record['snapshot_json'], true);
    $record['unidades_legado'] = json_decode($record['unidades_legado'] ?? '[]', true) ?: [];
    return $record;
}

/**
 * Fetch all cartilla snapshots for a country.
 */
function getCartillaHistoryRecords(PDO $conn, $id_pais) {
    ensureCartillaHistoryTable($conn);

    $stmt = $conn->prepare("\n        SELECT h.*, u.username\n        FROM cartilla_historial h\n        LEFT JOIN usuarios u ON u.id_usuario = h.id_usuario\n        WHERE h.id_pais = :id_pais\n        ORDER BY h.turno_global DESC, h.created_at DESC, h.id_historial DESC\n    ");
    $stmt->execute([':id_pais' => intval($id_pais)]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($records as &$record) {
        $record['snapshot'] = json_decode($record['snapshot_json'], true);
        $record['unidades_legado'] = json_decode($record['unidades_legado'] ?? '[]', true) ?: [];
    }
    unset($record);

    return $records;
}

/**
 * Ensure country history has cartilla snapshot columns.
 */
function ensureCountryHistoryCartillaColumns(PDO $conn) {
    $columns = [];
    try {
        $stmt = $conn->query("SHOW COLUMNS FROM historial_paises");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $columns[$column['Field']] = true;
        }
    } catch (PDOException $e) {
        return;
    }

    if (!isset($columns['cartilla_turno_global'])) {
        $conn->exec("ALTER TABLE historial_paises ADD COLUMN cartilla_turno_global int DEFAULT NULL AFTER razon_cambio");
    }
    if (!isset($columns['cartilla_snapshot_json'])) {
        $conn->exec("ALTER TABLE historial_paises ADD COLUMN cartilla_snapshot_json longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER cartilla_turno_global");
    }
    if (!isset($columns['cartilla_reporte_texto'])) {
        $conn->exec("ALTER TABLE historial_paises ADD COLUMN cartilla_reporte_texto longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER cartilla_snapshot_json");
    }
}

/**
 * Archive a country into historial_paises, including its latest cartilla snapshot.
 */
function archiveCountrySnapshotToHistory(PDO $conn, $id_usuario, $id_pais, $razon = 'Cambio de país') {
    try {
        ensureCountryHistoryCartillaColumns($conn);

        $stmt = $conn->prepare("SELECT p.id_pais, p.nombre_pais, p.bandera_url
                                FROM paises p
                                WHERE p.id_pais = :pid");
        $stmt->execute([':pid' => intval($id_pais)]);
        $country = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$country) {
            return false;
        }

        $cartilla_snapshot = null;
        $cartilla_reporte = null;
        $cartilla_turno = null;
        list($cartilla_snapshot, $cartilla_reporte) = buildCartillaHistorySnapshot($conn, $id_pais);
        if ($cartilla_snapshot) {
            $cartilla_turno = intval($cartilla_snapshot['turno_global'] ?? getCurrentTurn($conn));
        }

        $insert = $conn->prepare("INSERT INTO historial_paises
                                 (id_usuario, id_pais, nombre_pais_historico, bandera_url_historica, razon_cambio, cartilla_turno_global, cartilla_snapshot_json, cartilla_reporte_texto)
                                 VALUES (:uid, :pid, :nombre, :url, :razon, :cartilla_turno_global, :cartilla_snapshot_json, :cartilla_reporte_texto)");
        $insert->execute([
            ':uid' => $id_usuario !== null ? intval($id_usuario) : null,
            ':pid' => intval($country['id_pais']),
            ':nombre' => $country['nombre_pais'],
            ':url' => $country['bandera_url'],
            ':razon' => $razon,
            ':cartilla_turno_global' => $cartilla_turno,
            ':cartilla_snapshot_json' => $cartilla_snapshot ? json_encode($cartilla_snapshot, JSON_UNESCAPED_UNICODE) : null,
            ':cartilla_reporte_texto' => $cartilla_reporte,
        ]);
        return true;
    } catch(PDOException $e) {
        error_log("Error archiving country snapshot: " . $e->getMessage());
        return false;
    }
}


/**
 * Archive current country to history when user changes country

 * @param PDO $conn - Database connection
 * @param int $id_usuario - User ID
 * @param string $razon - Reason for change
 * @return bool - Success
 */
function archiveCountryToHistory($conn, $id_usuario, $razon = 'Cambio de país') {
    try {
        // Get current country data
        $stmt = $conn->prepare("SELECT u.id_pais, p.nombre_pais, p.bandera_url 
                                FROM usuarios u 
                                JOIN paises p ON u.id_pais = p.id_pais 
                                WHERE u.id_usuario = :uid AND u.id_pais IS NOT NULL");
        $stmt->execute([':uid' => $id_usuario]);
        $country = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($country) {
            return archiveCountrySnapshotToHistory($conn, $id_usuario, $country['id_pais'], $razon);
        }
        return false;
    } catch(PDOException $e) {
        error_log("Error archiving country: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's country history
 * @param PDO $conn - Database connection
 * @param int $id_usuario - User ID
 * @return array - Array of historical countries
 */
function getUserCountryHistory($conn, $id_usuario) {
    try {
        $stmt = $conn->prepare("SELECT * FROM historial_paises 
                                WHERE id_usuario = :uid 
                                ORDER BY fecha_fin DESC");
        $stmt->execute([':uid' => $id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting history: " . $e->getMessage());
        return [];
    }
}

/**
 * Advance country turn
 * @param PDO $conn - Database connection
 * @param int $id_pais - Country ID
 * @param int $id_gm - GM user ID
 * @param string $notas - Optional notes
 * @return bool - Success
 */
function avanzarTurno($conn, $id_pais, $id_gm, $notas = '') {
    try {
        // Get current turn
        $stmt = $conn->prepare("SELECT turno_actual FROM paises WHERE id_pais = :id");
        $stmt->execute([':id' => $id_pais]);
        $turno_actual = $stmt->fetchColumn();
        
        if ($turno_actual === false) return false;
        
        $turno_nuevo = $turno_actual + 1;
        
        // Update turn
        $update = $conn->prepare("UPDATE paises SET turno_actual = :nuevo WHERE id_pais = :id");
        $update->execute([':nuevo' => $turno_nuevo, ':id' => $id_pais]);
        
        // Log to history
        $log = $conn->prepare("
            INSERT INTO historial_turnos (id_pais, turno_anterior, turno_nuevo, accion, id_gm, notas)
            VALUES (:p, :ant, :nue, 'avanzar', :gm, :n)
        ");
        $log->execute([
            ':p' => $id_pais,
            ':ant' => $turno_actual,
            ':nue' => $turno_nuevo,
            ':gm' => $id_gm,
            ':n' => $notas
        ]);
        
        return true;
    } catch(PDOException $e) {
        error_log("Error advancing turn: " . $e->getMessage());
        return false;
    }
}

/**
 * Cancel/rollback country turn
 * @param PDO $conn - Database connection
 * @param int $id_pais - Country ID
 * @param int $id_gm - GM user ID
 * @param string $notas - Optional notes
 * @return bool - Success
 */
function anularTurno($conn, $id_pais, $id_gm, $notas = '') {
    try {
        // Get current turn
        $stmt = $conn->prepare("SELECT turno_actual FROM paises WHERE id_pais = :id");
        $stmt->execute([':id' => $id_pais]);
        $turno_actual = $stmt->fetchColumn();
        
        if ($turno_actual === false || $turno_actual <= 1) return false;
        
        $turno_nuevo = $turno_actual - 1;
        
        // Update turn
        $update = $conn->prepare("UPDATE paises SET turno_actual = :nuevo WHERE id_pais = :id");
        $update->execute([':nuevo' => $turno_nuevo, ':id' => $id_pais]);
        
        // Log to history
        $log = $conn->prepare("
            INSERT INTO historial_turnos (id_pais, turno_anterior, turno_nuevo, accion, id_gm, notas)
            VALUES (:p, :ant, :nue, 'anular', :gm, :n)
        ");
        $log->execute([
            ':p' => $id_pais,
            ':ant' => $turno_actual,
            ':nue' => $turno_nuevo,
            ':gm' => $id_gm,
            ':n' => $notas
        ]);
        
        return true;
    } catch(PDOException $e) {
        error_log("Error canceling turn: " . $e->getMessage());
        return false;
    }
}

/**
 * Set country turn manually
 * @param PDO $conn - Database connection
 * @param int $id_pais - Country ID
 * @param int $turno_nuevo - New turn number
 * @param int $id_gm - GM user ID
 * @param string $notas - Optional notes
 * @return bool - Success
 */
function ajustarTurno($conn, $id_pais, $turno_nuevo, $id_gm, $notas = '') {
    try {
        // Get current turn
        $stmt = $conn->prepare("SELECT turno_actual FROM paises WHERE id_pais = :id");
        $stmt->execute([':id' => $id_pais]);
        $turno_actual = $stmt->fetchColumn();
        
        if ($turno_actual === false || $turno_nuevo < 1) return false;
        
        // Update turn
        $update = $conn->prepare("UPDATE paises SET turno_actual = :nuevo WHERE id_pais = :id");
        $update->execute([':nuevo' => $turno_nuevo, ':id' => $id_pais]);
        
        // Log to history
        $log = $conn->prepare("
            INSERT INTO historial_turnos (id_pais, turno_anterior, turno_nuevo, accion, id_gm, notas)
            VALUES (:p, :ant, :nue, 'ajuste_manual', :gm, :n)
        ");
        $log->execute([
            ':p' => $id_pais,
            ':ant' => $turno_actual,
            ':nue' => $turno_nuevo,
            ':gm' => $id_gm,
            ':n' => $notas
        ]);
        
        return true;
    } catch(PDOException $e) {
        error_log("Error adjusting turn: " . $e->getMessage());
        return false;
    }
}

/**
 * Get turn history for a country
 * @param PDO $conn - Database connection
 * @param int $id_pais - Country ID
 * @param int $limit - Max records (default 50)
 * @return array - Turn history
 */
function getTurnHistory($conn, $id_pais, $limit = 50) {
    try {
        $stmt = $conn->prepare("
            SELECT ht.*, u.username as gm_username
            FROM historial_turnos ht
            JOIN usuarios u ON ht.id_gm = u.id_usuario
            WHERE ht.id_pais = :id
            ORDER BY ht.fecha_cambio DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':id', $id_pais, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting turn history: " . $e->getMessage());
        return [];
    }
}

/**
 * Ensure Polymarket tables exist.
 */
function ensurePolymarketTables(PDO $conn) {
    $conn->exec("CREATE TABLE IF NOT EXISTS markets (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->exec("CREATE TABLE IF NOT EXISTS market_options (
        id_option int NOT NULL AUTO_INCREMENT,
        id_market int NOT NULL,
        titulo varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
        descripcion text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        orden int NOT NULL DEFAULT '0',
        fecha_creacion timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id_option),
        UNIQUE KEY uniq_market_option (id_market, titulo),
        KEY idx_market_options_market (id_market)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->exec("CREATE TABLE IF NOT EXISTS market_bets (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->exec("CREATE TABLE IF NOT EXISTS market_suggestions (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->exec("CREATE TABLE IF NOT EXISTS market_results (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->exec("CREATE TABLE IF NOT EXISTS market_reputation (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/**
 * Translate consensus percentage into a readable label.
 */
function marketConsensusLabel($consensus) {
    $consensus = max(0, min(100, intval($consensus)));
    if ($consensus < 20) return 'Muy Bajo';
    if ($consensus < 40) return 'Bajo';
    if ($consensus < 60) return 'Medio';
    if ($consensus < 80) return 'Alto';
    return 'Muy Alto';
}

/**
 * Classify a bet size by its weight in the market.
 */
function marketWhaleTier($betAmount, $totalAmount) {
    $betAmount = intval($betAmount);
    $totalAmount = intval($totalAmount);

    if ($totalAmount <= 0) {
        return '🐟 Pequeños';
    }

    $share = ($betAmount / $totalAmount) * 100;
    if ($share >= 20) return '🐋 Ballenas';
    if ($share >= 5) return '🐬 Medianos';
    return '🐟 Pequeños';
}

/**
 * Resolve a market and store payouts, results, and reputation.
 */
function resolvePolymarket(PDO $conn, $id_market, $id_option_ganadora, $id_usuario_resuelve) {
    ensurePolymarketTables($conn);

    $conn->beginTransaction();
    try {
        $market_stmt = $conn->prepare("SELECT * FROM markets WHERE id_market = :id_market FOR UPDATE");
        $market_stmt->execute([':id_market' => intval($id_market)]);
        $market = $market_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$market) {
            throw new Exception('Mercado no encontrado');
        }

        $winner_stmt = $conn->prepare("SELECT titulo FROM market_options WHERE id_market = :id_market AND id_option = :id_option LIMIT 1");
        $winner_stmt->execute([
            ':id_market' => intval($id_market),
            ':id_option' => intval($id_option_ganadora),
        ]);
        $winner_title = $winner_stmt->fetchColumn();
        if (!$winner_title) {
            throw new Exception('La opción ganadora no pertenece a este mercado');
        }

        if (!in_array($market['estado'], ['cerrado', 'abierto', 'silencioso'], true)) {
            throw new Exception('El mercado no puede resolverse en su estado actual');
        }

        $bets_stmt = $conn->prepare("SELECT b.*, o.titulo AS opcion_titulo
            FROM market_bets b
            JOIN market_options o ON o.id_option = b.id_option
            WHERE b.id_market = :id_market AND b.estado = 'validada'
            ORDER BY b.id_bet ASC");
        $bets_stmt->execute([':id_market' => intval($id_market)]);
        $bets = $bets_stmt->fetchAll(PDO::FETCH_ASSOC);

        $pozo_total = 0;
        $pozo_ganador = 0;
        foreach ($bets as $bet) {
            $pozo_total += intval($bet['ic_apostado']);
            if (intval($bet['id_option']) === intval($id_option_ganadora)) {
                $pozo_ganador += intval($bet['ic_apostado']);
            }
        }

        $multiplicador = $pozo_ganador > 0 ? ($pozo_total / $pozo_ganador) : 0;
        $turno_global = getCurrentTurn($conn);

        $insert_result = $conn->prepare("INSERT INTO market_results
            (id_market, id_bet, id_usuario, id_option, apuesta_ic, pozo_total_ic, pozo_ganador_ic, multiplicador, ganancia_ic, ganancia_neta_ic, opcion_ganadora_titulo, turno_global)
            VALUES (:id_market, :id_bet, :id_usuario, :id_option, :apuesta_ic, :pozo_total_ic, :pozo_ganador_ic, :multiplicador, :ganancia_ic, :ganancia_neta_ic, :opcion_ganadora_titulo, :turno_global)");

        $reputation_upsert = $conn->prepare("INSERT INTO market_reputation
            (id_usuario, mercados_participados, mercados_ganados, mercados_perdidos, total_ic_apostado, total_ic_ganado, precision_pct)
            VALUES (:id_usuario, :mercados_participados, :mercados_ganados, :mercados_perdidos, :total_ic_apostado, :total_ic_ganado, :precision_pct)
            ON DUPLICATE KEY UPDATE
                mercados_participados = mercados_participados + VALUES(mercados_participados),
                mercados_ganados = mercados_ganados + VALUES(mercados_ganados),
                mercados_perdidos = mercados_perdidos + VALUES(mercados_perdidos),
                total_ic_apostado = total_ic_apostado + VALUES(total_ic_apostado),
                total_ic_ganado = total_ic_ganado + VALUES(total_ic_ganado),
                precision_pct = VALUES(precision_pct)");

        $reputation_delta = [];

        foreach ($bets as $bet) {
            $es_ganadora = intval($bet['id_option']) === intval($id_option_ganadora);
            $payout = $es_ganadora ? (int) round(intval($bet['ic_apostado']) * $multiplicador) : 0;
            $profit = $payout - intval($bet['ic_apostado']);

            $insert_result->execute([
                ':id_market' => intval($id_market),
                ':id_bet' => intval($bet['id_bet']),
                ':id_usuario' => intval($bet['id_usuario']),
                ':id_option' => intval($bet['id_option']),
                ':apuesta_ic' => intval($bet['ic_apostado']),
                ':pozo_total_ic' => $pozo_total,
                ':pozo_ganador_ic' => $pozo_ganador,
                ':multiplicador' => $multiplicador,
                ':ganancia_ic' => $payout,
                ':ganancia_neta_ic' => $profit,
                ':opcion_ganadora_titulo' => $winner_title,
                ':turno_global' => $turno_global,
            ]);

            if (!isset($reputation_delta[$bet['id_usuario']])) {
                $reputation_delta[$bet['id_usuario']] = [
                    'mercados_participados' => 0,
                    'mercados_ganados' => 0,
                    'mercados_perdidos' => 0,
                    'total_ic_apostado' => 0,
                    'total_ic_ganado' => 0,
                ];
            }

            $reputation_delta[$bet['id_usuario']]['mercados_participados'] += 1;
            $reputation_delta[$bet['id_usuario']]['mercados_ganados'] += $es_ganadora ? 1 : 0;
            $reputation_delta[$bet['id_usuario']]['mercados_perdidos'] += $es_ganadora ? 0 : 1;
            $reputation_delta[$bet['id_usuario']]['total_ic_apostado'] += intval($bet['ic_apostado']);
            $reputation_delta[$bet['id_usuario']]['total_ic_ganado'] += $payout;
        }

        foreach ($reputation_delta as $id_usuario => $delta) {
            $participados = $delta['mercados_participados'];
            $ganados = $delta['mercados_ganados'];
            $precision = $participados > 0 ? round(($ganados / $participados) * 100, 2) : 0;
            $reputation_upsert->execute([
                ':id_usuario' => intval($id_usuario),
                ':mercados_participados' => $participados,
                ':mercados_ganados' => $ganados,
                ':mercados_perdidos' => $delta['mercados_perdidos'],
                ':total_ic_apostado' => $delta['total_ic_apostado'],
                ':total_ic_ganado' => $delta['total_ic_ganado'],
                ':precision_pct' => $precision,
            ]);
        }

        $update_market = $conn->prepare("UPDATE markets
            SET estado = 'resuelto',
                id_opcion_ganadora = :id_opcion_ganadora,
                id_usuario_resuelve = :id_usuario_resuelve,
                fecha_resolucion = NOW(),
                fecha_cierre_real = COALESCE(fecha_cierre_real, NOW())
            WHERE id_market = :id_market");
        $update_market->execute([
            ':id_opcion_ganadora' => intval($id_option_ganadora),
            ':id_usuario_resuelve' => intval($id_usuario_resuelve),
            ':id_market' => intval($id_market),
        ]);

        $conn->commit();
        return [
            'success' => true,
            'pozo_total' => $pozo_total,
            'pozo_ganador' => $pozo_ganador,
            'multiplicador' => $multiplicador,
            'bets' => count($bets),
        ];
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

/**
 * Get country's IC production.
 */
function polymarket_get_country_ic_production(PDO $conn, $id_pais) {
    if (!$id_pais) return 0;
    
    $focus_stmt = $conn->prepare("
        SELECT e.multiplicador_ic
        FROM paises p
        LEFT JOIN enfoques e ON p.id_enfoque_activo = e.id_enfoque
        WHERE p.id_pais = :id_pais
        LIMIT 1
    ");
    $focus_stmt->execute([':id_pais' => $id_pais]);
    $focus_mult = $focus_stmt->fetchColumn();
    $focus_mult_val = ($focus_mult !== false && $focus_mult !== null) ? floatval($focus_mult) : null;

    $prod_stmt = $conn->prepare("
        SELECT ct.multiplicador, COALESCE(cv.cantidad, 0) AS cantidad
        FROM cartilla_tipos ct
        LEFT JOIN cartilla_valores cv ON cv.id_tipo = ct.id_tipo AND cv.id_pais = :id_pais
        WHERE ct.tipo = 'produccion' AND ct.unidad_produccion = 'IC' AND ct.activo = 1
    ");
    $prod_stmt->execute([':id_pais' => $id_pais]);
    $items = $prod_stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_ic_production = 0;
    foreach ($items as $item) {
        $mult = ($focus_mult_val !== null) ? $focus_mult_val : floatval($item['multiplicador']);
        $total_ic_production += intval($item['cantidad']) * $mult;
    }
    return intval($total_ic_production);
}
