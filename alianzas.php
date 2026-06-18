<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

require_once 'config/db.php';
require_once 'includes/functions.php';

$page_title = 'Alianzas';
$message = '';
$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['id_rol'];
$is_admin_or_gm = in_array($role_id, [1, 2]);

// Get user's country
$user_stmt = $conn->prepare("SELECT id_pais FROM usuarios WHERE id_usuario = :id");
$user_stmt->execute([':id' => $user_id]);
$user_pais = $user_stmt->fetchColumn();

// ==================== PROCESAMIENTO ====================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Crear Alianza
    if (isset($_POST['create_alliance'])) {
        $nombre = trim($_POST['nombre_alianza']);
        $desc = trim($_POST['descripcion']);
        $tipo = $_POST['tipo_alianza'] ?? 'Militar';
        
        if (!empty($nombre) && $user_pais && in_array($tipo, ['Militar', 'Economica'])) {
            // Verificar si ya tiene una alianza de este tipo
            $campo_alianza = $tipo == 'Militar' ? 'id_alianza_militar' : 'id_alianza_economica';
            $check = $conn->prepare("SELECT $campo_alianza FROM paises WHERE id_pais = :id");
            $check->execute([':id' => $user_pais]);
            $alianza_actual = $check->fetchColumn();
            
            if ($alianza_actual) {
                $message = "error|Ya perteneces a una alianza de tipo $tipo. Debes abandonarla primero.";
            } else {
                try {
                    $conn->beginTransaction();
                    
                    // Crear alianza (pendiente de aprobación)
                    $stmt = $conn->prepare("INSERT INTO alianzas (nombre_alianza, tipo_alianza, descripcion, id_fundador, aprobada) VALUES (:n, :t, :d, :f, 0)");
                    $stmt->execute([':n' => $nombre, ':t' => $tipo, ':d' => $desc, ':f' => $user_id]);
                    $id_alianza = $conn->lastInsertId();
                    
                    // El fundador se une automáticamente a su alianza (aunque esté pendiente)
                    $update = $conn->prepare("UPDATE paises SET $campo_alianza = :a WHERE id_pais = :p");
                    $update->execute([':a' => $id_alianza, ':p' => $user_pais]);
                    
                    // Log
                    $log = $conn->prepare("
                        INSERT INTO historial_alianzas (id_pais, id_alianza_anterior, id_alianza_nueva, tipo_alianza, accion, id_usuario_accion)
                        VALUES (:p, NULL, :nue, :t, 'fundar', :u)
                    ");
                    $log->execute([':p' => $user_pais, ':nue' => $id_alianza, ':t' => $tipo, ':u' => $user_id]);
                    
                    $conn->commit();
                    $message = "success|Alianza creada correctamente. Pendiente de aprobación por GM.";
                } catch (PDOException $e) {
                    $conn->rollBack();
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $message = "error|El nombre de la alianza ya existe. Por favor, elige otro nombre.";
                    } else {
                        $message = "error|Error al crear la alianza: " . $e->getMessage();
                    }
                }
            }
        } else {
            $message = "error|Debes tener un país asignado y proporcionar un nombre para la alianza.";
        }
    }
    
    // Aprobar/Rechazar Alianza (Solo GM/Admin)
    if (isset($_POST['approve_alliance']) && $is_admin_or_gm) {
        $id_alianza = intval($_POST['id_alianza_approve']);
        $conn->prepare("UPDATE alianzas SET aprobada = 1 WHERE id_alianza = :id")->execute([':id' => $id_alianza]);
        $message = "success|Alianza aprobada correctamente.";
    }
    
    if (isset($_POST['reject_alliance']) && $is_admin_or_gm) {
        $id_alianza = intval($_POST['id_alianza_reject']);
        // Eliminar alianza rechazada
        $conn->prepare("DELETE FROM alianzas WHERE id_alianza = :id")->execute([':id' => $id_alianza]);
        $message = "success|Alianza rechazada y eliminada.";
    }
    
    // Editar Alianza (Fundador o GM/Admin)
    if (isset($_POST['edit_alliance'])) {
        $id_alianza = intval($_POST['id_alianza_edit']);
        $nombre = trim($_POST['nombre_alianza_edit']);
        $desc = trim($_POST['descripcion_edit']);
        $tipo = $_POST['tipo_alianza_edit'] ?? 'Militar';
        
        if (!empty($nombre) && in_array($tipo, ['Militar', 'Economica'])) {
            // Verificar permisos (fundador o admin/gm)
            $check = $conn->prepare("SELECT id_fundador, nombre_alianza, tipo_alianza FROM alianzas WHERE id_alianza = :id");
            $check->execute([':id' => $id_alianza]);
            $alianza_data = $check->fetch(PDO::FETCH_ASSOC);
            
            if ($alianza_data && ($alianza_data['id_fundador'] == $user_id || $is_admin_or_gm)) {
                try {
                    // Si se cambia el tipo de alianza, verificar que los miembros no tengan conflicto
                    $tipo_original = $alianza_data['tipo_alianza'];
                    if ($tipo != $tipo_original) {
                        // Obtener todos los miembros de esta alianza
                        $campo_original = $tipo_original == 'Militar' ? 'id_alianza_militar' : 'id_alianza_economica';
                        $campo_nuevo = $tipo == 'Militar' ? 'id_alianza_militar' : 'id_alianza_economica';
                        
                        // Verificar si algún miembro ya tiene una alianza del nuevo tipo
                        $check_conflict = $conn->prepare("
                            SELECT COUNT(*) FROM paises 
                            WHERE $campo_original = :id_alianza 
                            AND $campo_nuevo IS NOT NULL 
                            AND $campo_nuevo != :id_alianza
                        ");
                        $check_conflict->execute([':id_alianza' => $id_alianza]);
                        $conflictos = $check_conflict->fetchColumn();
                        
                        if ($conflictos > 0) {
                            $message = "error|No se puede cambiar el tipo: algunos miembros ya tienen una alianza de tipo $tipo.";
                        } else {
                            // Cambiar tipo: mover de un campo a otro en la tabla paises
                            $conn->beginTransaction();
                            
                            // Actualizar alianza
                            $update = $conn->prepare("
                                UPDATE alianzas 
                                SET nombre_alianza = :n, descripcion = :d, tipo_alianza = :t 
                                WHERE id_alianza = :id
                            ");
                            $update->execute([':n' => $nombre, ':d' => $desc, ':t' => $tipo, ':id' => $id_alianza]);
                            
                            // Mover referencias en paises
                            $move = $conn->prepare("
                                UPDATE paises 
                                SET $campo_nuevo = :id_alianza, $campo_original = NULL 
                                WHERE $campo_original = :id_alianza
                            ");
                            $move->execute([':id_alianza' => $id_alianza]);
                            
                            $conn->commit();
                            $message = "success|Alianza actualizada correctamente (tipo cambiado a $tipo).";
                        }
                    } else {
                        // Solo actualizar nombre y descripción
                        $update = $conn->prepare("
                            UPDATE alianzas 
                            SET nombre_alianza = :n, descripcion = :d 
                            WHERE id_alianza = :id
                        ");
                        $update->execute([':n' => $nombre, ':d' => $desc, ':id' => $id_alianza]);
                        $message = "success|Alianza actualizada correctamente.";
                    }
                } catch (PDOException $e) {
                    if ($tipo != $tipo_original) {
                        $conn->rollBack();
                    }
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $message = "error|El nombre de la alianza ya existe. Por favor, elige otro nombre.";
                    } else {
                        $message = "error|Error al actualizar la alianza: " . $e->getMessage();
                    }
                }
            } else {
                $message = "error|No tienes permisos para editar esta alianza.";
            }
        } else {
            $message = "error|Debes proporcionar un nombre válido y un tipo de alianza.";
        }
    }
    
    // Subir Logo (usando nomenclatura [nombre_alianza].png)
    if (isset($_FILES['logo_alianza']) && isset($_POST['id_alianza_logo'])) {
        $id_alianza = intval($_POST['id_alianza_logo']);
        
        // Verificar permisos (fundador o admin/gm)
        $check = $conn->prepare("SELECT id_fundador, nombre_alianza FROM alianzas WHERE id_alianza = :id");
        $check->execute([':id' => $id_alianza]);
        $alianza_data = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($alianza_data && ($alianza_data['id_fundador'] == $user_id || $is_admin_or_gm)) {
            if ($_FILES['logo_alianza']['error'] == 0) {
                // Validar tamaño (5MB máximo)
                $max_size = 5 * 1024 * 1024;
                if ($_FILES['logo_alianza']['size'] > $max_size) {
                    $message = "error|El archivo es muy grande. Máximo 5MB.";
                } else {
                    // Validar tipo MIME real del archivo
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $_FILES['logo_alianza']['tmp_name']);
                    finfo_close($finfo);
                    
                    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($mime_type, $allowed_mimes)) {
                        $message = "error|Solo se permiten imágenes JPG, PNG, GIF o WEBP.";
                    } else {
                        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        $ext = strtolower(pathinfo($_FILES['logo_alianza']['name'], PATHINFO_EXTENSION));
                        
                        if (in_array($ext, $allowed)) {
                    $upload_dir = __DIR__ . '/assets/uploads/alianzas/';
                    
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Nombre del archivo: [nombre_alianza].png
                    $temp_path = $_FILES['logo_alianza']['tmp_name'];
                    $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $alianza_data['nombre_alianza']);
                    $filename_png = $safe_name . '.png';
                    $full_path = $upload_dir . $filename_png;
                    $upload_path = 'assets/uploads/alianzas/' . $filename_png;
                    
                    // Convertir a PNG usando GD
                    $image = null;
                    switch ($ext) {
                        case 'jpg':
                        case 'jpeg':
                            $image = @imagecreatefromjpeg($temp_path);
                            break;
                        case 'png':
                            $image = @imagecreatefrompng($temp_path);
                            break;
                        case 'gif':
                            $image = @imagecreatefromgif($temp_path);
                            break;
                        case 'webp':
                            $image = @imagecreatefromwebp($temp_path);
                            break;
                    }
                    
                    if ($image) {
                        // Eliminar logo anterior si existe
                        $old = $conn->prepare("SELECT logo_url FROM alianzas WHERE id_alianza = :id");
                        $old->execute([':id' => $id_alianza]);
                        $old_url = $old->fetchColumn();
                        if (!empty($old_url) && $old_url != 'default_alliance.png' && file_exists(__DIR__ . '/' . $old_url)) {
                            @unlink(__DIR__ . '/' . $old_url);
                        }
                        
                        // Guardar como PNG
                        imagepng($image, $full_path);
                        imagedestroy($image);
                        
                        // Actualizar BD
                        $update = $conn->prepare("UPDATE alianzas SET logo_url = :url WHERE id_alianza = :id");
                        $update->execute([':url' => $upload_path, ':id' => $id_alianza]);
                        $message = "success|Logo subido correctamente como {$filename_png}.";
                    } else {
                        $message = "error|Error al procesar la imagen.";
                    }
                        }
                    }
                }
            }
        }
    }
    
    // Enviar Invitación (Solo fundador o admin/gm)
    if (isset($_POST['send_invitation'])) {
        $id_alianza = intval($_POST['id_alianza_inv']);
        $id_pais = intval($_POST['id_pais_inv']);
        
        // Verificar permisos y obtener tipo de alianza
        $check = $conn->prepare("SELECT id_fundador, aprobada, tipo_alianza FROM alianzas WHERE id_alianza = :id");
        $check->execute([':id' => $id_alianza]);
        $alianza = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($alianza && $alianza['aprobada'] == 1 && ($alianza['id_fundador'] == $user_id || $is_admin_or_gm)) {
            $tipo = $alianza['tipo_alianza'] ?? 'Militar';
            $campo_alianza = $tipo == 'Militar' ? 'id_alianza_militar' : 'id_alianza_economica';
            
            // Verificar si el país ya tiene una alianza de este tipo
            $check_existing = $conn->prepare("SELECT $campo_alianza FROM paises WHERE id_pais = :id");
            $check_existing->execute([':id' => $id_pais]);
            $existing = $check_existing->fetchColumn();
            
            if ($existing) {
                $message = "error|Este país ya pertenece a una alianza de tipo $tipo.";
            } else {
                // Verificar límite de 5 miembros para esta alianza
                $count_stmt = $conn->prepare("SELECT COUNT(*) FROM paises WHERE $campo_alianza = :id");
                $count_stmt->execute([':id' => $id_alianza]);
                $num_miembros = $count_stmt->fetchColumn();
                
                if ($num_miembros >= 5) {
                    $message = "error|La alianza ya tiene el máximo de 5 miembros permitidos.";
                } else {
                    try {
                        $stmt = $conn->prepare("
                            INSERT INTO alianzas_invitaciones (id_alianza, id_pais, id_invitador, estado)
                            VALUES (:a, :p, :i, 'pendiente')
                        ");
                        $stmt->execute([':a' => $id_alianza, ':p' => $id_pais, ':i' => $user_id]);
                        $message = "success|Invitación enviada correctamente.";
                    } catch (PDOException $e) {
                        $message = "error|Ya existe una invitación pendiente para este país.";
                    }
                }
            }
        }
    }
    
    // Aceptar Invitación
    if (isset($_POST['accept_invitation']) && $user_pais) {
        $id_invitacion = intval($_POST['id_invitacion']);
        
        // Verificar que la invitación es para el país del usuario y obtener tipo
        $check = $conn->prepare("
            SELECT inv.id_alianza, inv.id_pais, a.tipo_alianza 
            FROM alianzas_invitaciones inv
            JOIN alianzas a ON inv.id_alianza = a.id_alianza
            WHERE inv.id_invitacion = :id AND inv.estado = 'pendiente'
        ");
        $check->execute([':id' => $id_invitacion]);
        $inv = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($inv && $inv['id_pais'] == $user_pais) {
            $tipo = $inv['tipo_alianza'] ?? 'Militar';
            $campo_alianza = $tipo == 'Militar' ? 'id_alianza_militar' : 'id_alianza_economica';
            
            // Verificar si ya tiene una alianza de este tipo
            $check_existing = $conn->prepare("SELECT $campo_alianza FROM paises WHERE id_pais = :id");
            $check_existing->execute([':id' => $user_pais]);
            $existing = $check_existing->fetchColumn();
            
            if ($existing) {
                $message = "error|Ya perteneces a una alianza de tipo $tipo. Debes abandonarla primero.";
            } else {
                // Verificar límite de 5 miembros antes de aceptar
                $count_stmt = $conn->prepare("SELECT COUNT(*) FROM paises WHERE $campo_alianza = :id");
                $count_stmt->execute([':id' => $inv['id_alianza']]);
                $num_miembros = $count_stmt->fetchColumn();
                
                if ($num_miembros >= 5) {
                    $message = "error|La alianza ya tiene el máximo de 5 miembros permitidos. No puedes unirte.";
                } else {
                    $conn->beginTransaction();
                    
                    // Update country con el campo correcto
                    $update = $conn->prepare("UPDATE paises SET $campo_alianza = :a WHERE id_pais = :p");
                    $update->execute([':a' => $inv['id_alianza'], ':p' => $user_pais]);
                    
                    // Update invitation status
                    $upd_inv = $conn->prepare("UPDATE alianzas_invitaciones SET estado = 'aceptada', fecha_respuesta = NOW() WHERE id_invitacion = :id");
                    $upd_inv->execute([':id' => $id_invitacion]);
                    
                    // Log
                    $log = $conn->prepare("
                        INSERT INTO historial_alianzas (id_pais, id_alianza_anterior, id_alianza_nueva, tipo_alianza, accion, id_usuario_accion)
                        VALUES (:p, NULL, :nue, :t, 'unirse', :u)
                    ");
                    $log->execute([':p' => $user_pais, ':nue' => $inv['id_alianza'], ':t' => $tipo, ':u' => $user_id]);
                    
                    $conn->commit();
                    $message = "success|Te has unido a la alianza correctamente.";
                }
            }
        }
    }
    
    // Rechazar Invitación
    if (isset($_POST['reject_invitation']) && $user_pais) {
        $id_invitacion = intval($_POST['id_invitacion_reject']);
        
        $check = $conn->prepare("SELECT id_pais FROM alianzas_invitaciones WHERE id_invitacion = :id AND estado = 'pendiente'");
        $check->execute([':id' => $id_invitacion]);
        $inv_pais = $check->fetchColumn();
        
        if ($inv_pais == $user_pais) {
            $upd = $conn->prepare("UPDATE alianzas_invitaciones SET estado = 'rechazada', fecha_respuesta = NOW() WHERE id_invitacion = :id");
            $upd->execute([':id' => $id_invitacion]);
            $message = "success|Invitación rechazada.";
        }
    }
    
    // Abandonar Alianza
    if (isset($_POST['leave_alliance']) && $user_pais) {
        $id_alianza = intval($_POST['id_alianza_leave']);
        
        // Obtener tipo de la alianza
        $check = $conn->prepare("SELECT tipo_alianza FROM alianzas WHERE id_alianza = :id");
        $check->execute([':id' => $id_alianza]);
        $tipo = $check->fetchColumn();
        
        if ($tipo) {
            $campo_alianza = $tipo == 'Militar' ? 'id_alianza_militar' : 'id_alianza_economica';
            
            // Verificar que el país pertenece a esta alianza
            $verify = $conn->prepare("SELECT $campo_alianza FROM paises WHERE id_pais = :id");
            $verify->execute([':id' => $user_pais]);
            $current = $verify->fetchColumn();
            
            if ($current == $id_alianza) {
                $update = $conn->prepare("UPDATE paises SET $campo_alianza = NULL WHERE id_pais = :p");
                $update->execute([':p' => $user_pais]);
                
                $log = $conn->prepare("
                    INSERT INTO historial_alianzas (id_pais, id_alianza_anterior, id_alianza_nueva, tipo_alianza, accion, id_usuario_accion)
                    VALUES (:p, :ant, NULL, :t, 'abandonar', :u)
                ");
                $log->execute([':p' => $user_pais, ':ant' => $id_alianza, ':t' => $tipo, ':u' => $user_id]);
                
                $message = "success|Has abandonado la alianza.";
            } else {
                $message = "error|No perteneces a esta alianza.";
            }
        }
    }
    
    // Eliminar Alianza (Solo fundador o admin/gm)
    if (isset($_POST['delete_alliance'])) {
        $id_alianza = intval($_POST['id_alianza_delete']);
        
        $check = $conn->prepare("SELECT id_fundador FROM alianzas WHERE id_alianza = :id");
        $check->execute([':id' => $id_alianza]);
        $fundador = $check->fetchColumn();
        
        if ($fundador == $user_id || $is_admin_or_gm) {
            $conn->prepare("DELETE FROM alianzas WHERE id_alianza = :id")->execute([':id' => $id_alianza]);
            $message = "success|Alianza eliminada correctamente.";
        }
    }
}

// ==================== OBTENER DATOS ====================

// All alliances (aprobadas y pendientes)
$alianzas = $conn->query("
    SELECT a.*, u.username as fundador_username,
           (SELECT COUNT(*) 
            FROM paises 
            WHERE (a.tipo_alianza = 'Militar' AND id_alianza_militar = a.id_alianza) 
               OR (a.tipo_alianza = 'Economica' AND id_alianza_economica = a.id_alianza)
           ) as num_miembros
    FROM alianzas a
    LEFT JOIN usuarios u ON a.id_fundador = u.id_usuario
    ORDER BY a.aprobada DESC, a.nombre_alianza
")->fetchAll(PDO::FETCH_ASSOC);

// User's current alliances (puede tener hasta 2: militar y económica)
$user_alliances = [];
if ($user_pais) {
    $stmt = $conn->prepare("
        SELECT a.* FROM alianzas a
        JOIN paises p ON (a.id_alianza = p.id_alianza_militar OR a.id_alianza = p.id_alianza_economica)
        WHERE p.id_pais = :id
    ");
    $stmt->execute([':id' => $user_pais]);
    $user_alliances = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Mantener compatibilidad con código existente (primera alianza)
$user_alliance = !empty($user_alliances) ? $user_alliances[0] : null;

// Invitaciones pendientes para el usuario
$invitaciones = [];
if ($user_pais) {
    $inv_stmt = $conn->prepare("
        SELECT inv.*, a.nombre_alianza, a.logo_url, u.username as invitador_username
        FROM alianzas_invitaciones inv
        JOIN alianzas a ON inv.id_alianza = a.id_alianza
        LEFT JOIN usuarios u ON inv.id_invitador = u.id_usuario
        WHERE inv.id_pais = :id AND inv.estado = 'pendiente'
        ORDER BY inv.fecha_invitacion DESC
    ");
    $inv_stmt->execute([':id' => $user_pais]);
    $invitaciones = $inv_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Países disponibles para invitar (si el usuario es fundador de alguna alianza aprobada)
$paises_para_invitar = [];
$alliance_is_full = false;
if ($user_alliance && $user_alliance['id_fundador'] == $user_id && $user_alliance['aprobada'] == 1) {
    $tipo = $user_alliance['tipo_alianza'] ?? 'Militar';
    $campo_alianza = $tipo == 'Militar' ? 'id_alianza_militar' : 'id_alianza_economica';
    
    // Verificar si la alianza ya tiene 5 miembros
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM paises WHERE $campo_alianza = :id");
    $count_stmt->execute([':id' => $user_alliance['id_alianza']]);
    $num_miembros_actual = $count_stmt->fetchColumn();
    $alliance_is_full = ($num_miembros_actual >= 5);
    
    if (!$alliance_is_full) {
        // Listar países que NO tienen alianza de este tipo
        $paises_stmt = $conn->prepare("
            SELECT p.id_pais, p.nombre_pais, p.bandera_url, u.username
            FROM paises p
            LEFT JOIN usuarios u ON p.id_pais = u.id_pais
            WHERE ($campo_alianza IS NULL OR $campo_alianza != :id_alianza)
            ORDER BY p.nombre_pais
        ");
        $paises_stmt->execute([':id_alianza' => $user_alliance['id_alianza']]);
        $paises_para_invitar = $paises_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

include 'includes/header.php';
?>

<main class="container my-4">
    
    <?php if ($message): 
        $parts = explode('|', $message);
        $type = $parts[0] ?? 'success';
        $text = $parts[1] ?? $message;
        $alert_class = $type === 'error' ? 'alert-danger' : 'alert-success';
    ?>
        <div class="alert <?= $alert_class ?> alert-dismissible fade show">
            <?= htmlspecialchars($text) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-people-fill"></i> Alianzas</h2>
                <?php if ($user_pais): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="bi bi-plus-lg"></i> Nueva Alianza
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Invitaciones Pendientes -->
    <?php if (!empty($invitaciones)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-warning">
                    <h5><i class="bi bi-envelope-fill"></i> Invitaciones Pendientes</h5>
                    <?php foreach ($invitaciones as $inv): ?>
                        <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
                            <div>
                                <?php if (!empty($inv['logo_url'])): ?>
                                    <img src="<?= htmlspecialchars($inv['logo_url']) ?>" alt="Logo" style="width: 30px; height: 30px;" class="me-2">
                                <?php endif; ?>
                                <strong><?= htmlspecialchars($inv['nombre_alianza']) ?></strong>
                                <small class="text-muted">- Invitado por <?= htmlspecialchars($inv['invitador_username']) ?></small>
                            </div>
                            <div>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="id_invitacion" value="<?= $inv['id_invitacion'] ?>">
                                    <button type="submit" name="accept_invitation" class="btn btn-success btn-sm">
                                        <i class="bi bi-check-lg"></i> Aceptar
                                    </button>
                                </form>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="id_invitacion_reject" value="<?= $inv['id_invitacion'] ?>">
                                    <button type="submit" name="reject_invitation" class="btn btn-danger btn-sm">
                                        <i class="bi bi-x-lg"></i> Rechazar
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Mis Alianzas Actuales -->
    <?php if ($user_pais): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card <?= !empty($user_alliances) ? 'border-primary' : 'border-secondary' ?>">
                    <div class="card-header <?= !empty($user_alliances) ? 'bg-primary text-white' : 'bg-secondary text-white' ?>">
                        <h5 class="mb-0"><i class="bi bi-shield-fill"></i> Mis Alianzas</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($user_alliances)): ?>
                            <div class="row">
                                <?php foreach ($user_alliances as $idx => $alliance): 
                                    // Verificar si es fundador y si está llena para mostrar botón de invitar
                                    $is_founder = ($alliance['id_fundador'] == $user_id);
                                    $is_full = false;
                                    if ($is_founder && $alliance['aprobada'] == 1) {
                                        $tipo = $alliance['tipo_alianza'] ?? 'Militar';
                                        $campo = $tipo == 'Militar' ? 'id_alianza_militar' : 'id_alianza_economica';
                                        $cnt = $conn->prepare("SELECT COUNT(*) FROM paises WHERE $campo = :id");
                                        $cnt->execute([':id' => $alliance['id_alianza']]);
                                        $is_full = ($cnt->fetchColumn() >= 5);
                                    }
                                ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100 border-<?= ($alliance['tipo_alianza'] ?? 'Militar') == 'Militar' ? 'danger' : 'success' ?>">
                                            <div class="card-header bg-<?= ($alliance['tipo_alianza'] ?? 'Militar') == 'Militar' ? 'danger' : 'success' ?> text-white">
                                                <i class="bi bi-<?= ($alliance['tipo_alianza'] ?? 'Militar') == 'Militar' ? 'shield' : 'cash-coin' ?>"></i>
                                                <?= htmlspecialchars($alliance['tipo_alianza'] ?? 'Militar') ?>
                                            </div>
                                            <div class="card-body">
                                                <div class="row align-items-center">
                                                    <div class="col-4 text-center">
                                                        <?php if (!empty($alliance['logo_url'])): ?>
                                                            <img src="<?= htmlspecialchars($alliance['logo_url']) ?>" 
                                                                 alt="Logo" class="img-fluid rounded" style="max-height: 80px;">
                                                        <?php else: ?>
                                                            <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" 
                                                                 style="height: 80px;">
                                                                <i class="bi bi-shield-fill" style="font-size: 2rem;"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-8">
                                                        <h5 class="mb-1">
                                                            <?= htmlspecialchars($alliance['nombre_alianza']) ?>
                                                            <?php if ($alliance['aprobada'] == 0): ?>
                                                                <span class="badge bg-warning text-dark">Pendiente</span>
                                                            <?php endif; ?>
                                                        </h5>
                                                        <p class="text-muted small mb-2"><?= htmlspecialchars($alliance['descripcion'] ?: 'Sin descripción') ?></p>
                                                        <?php if ($is_founder): ?>
                                                            <small class="text-primary"><i class="bi bi-star-fill"></i> Fundador</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-footer">
                                                <div class="d-grid gap-2">
                                                    <?php if ($is_founder): ?>
                                                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#logoModal<?= $alliance['id_alianza'] ?>">
                                                            <i class="bi bi-image"></i> Logo
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($is_founder && $alliance['aprobada'] == 1): ?>
                                                        <?php if ($is_full): ?>
                                                            <div class="alert alert-warning btn-sm mb-0 p-2 small">
                                                                <i class="bi bi-exclamation-triangle-fill"></i> Completa (5/5)
                                                            </div>
                                                        <?php else: ?>
                                                            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#inviteModal<?= $alliance['id_alianza'] ?>">
                                                                <i class="bi bi-envelope-plus"></i> Invitar
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    <form method="post" onsubmit="return confirm('¿Abandonar esta alianza?')">
                                                        <input type="hidden" name="id_alianza_leave" value="<?= $alliance['id_alianza'] ?>">
                                                        <button type="submit" name="leave_alliance" class="btn btn-danger btn-sm w-100">
                                                            <i class="bi bi-box-arrow-right"></i> Abandonar
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Modal: Logo individual por alianza -->
                                        <?php if ($is_founder): ?>
                                        <div class="modal fade" id="logoModal<?= $alliance['id_alianza'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="post" enctype="multipart/form-data">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Subir Logo</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id_alianza_logo" value="<?= $alliance['id_alianza'] ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">Imagen</label>
                                                                <input type="file" name="logo_alianza" class="form-control" accept="image/*" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-primary">Subir</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Modal: Invitar individual por alianza -->
                                        <?php if ($is_founder && $alliance['aprobada'] == 1 && !$is_full): 
                                            $tipo_inv = $alliance['tipo_alianza'] ?? 'Militar';
                                            $campo_inv = $tipo_inv == 'Militar' ? 'id_alianza_militar' : 'id_alianza_economica';
                                            $paises_inv = $conn->prepare("
                                                SELECT p.id_pais, p.nombre_pais, p.bandera_url, u.username
                                                FROM paises p
                                                LEFT JOIN usuarios u ON p.id_pais = u.id_pais
                                                WHERE ($campo_inv IS NULL OR $campo_inv != :id)
                                                ORDER BY p.nombre_pais
                                            ");
                                            $paises_inv->execute([':id' => $alliance['id_alianza']]);
                                            $paises_disponibles = $paises_inv->fetchAll(PDO::FETCH_ASSOC);
                                        ?>
                                        <div class="modal fade" id="inviteModal<?= $alliance['id_alianza'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Invitar a <?= htmlspecialchars($alliance['nombre_alianza']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="list-group">
                                                            <?php foreach ($paises_disponibles as $pais): ?>
                                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                                    <div>
                                                                        <?php if (!empty($pais['bandera_url'])): ?>
                                                                            <img src="<?= htmlspecialchars($pais['bandera_url']) ?>" alt="Flag" style="width: 30px; height: 20px;" class="me-2">
                                                                        <?php endif; ?>
                                                                        <strong><?= htmlspecialchars($pais['nombre_pais']) ?></strong>
                                                                        <?php if ($pais['username']): ?>
                                                                            <small class="text-muted">(<?= htmlspecialchars($pais['username']) ?>)</small>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <form method="post">
                                                                        <input type="hidden" name="id_alianza_inv" value="<?= $alliance['id_alianza'] ?>">
                                                                        <input type="hidden" name="id_pais_inv" value="<?= $pais['id_pais'] ?>">
                                                                        <button type="submit" name="send_invitation" class="btn btn-primary btn-sm">
                                                                            <i class="bi bi-envelope-plus"></i> Invitar
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            <?php endforeach; ?>
                                                            <?php if (empty($paises_disponibles)): ?>
                                                                <div class="alert alert-info mb-0">No hay países disponibles</div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> No perteneces a ninguna alianza. Espera una invitación o crea la tuya.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Alianzas Pendientes de Aprobación (Solo GM/Admin) -->
    <?php if ($is_admin_or_gm): 
        $pendientes = array_filter($alianzas, fn($a) => $a['aprobada'] == 0);
        if (!empty($pendientes)):
    ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-danger">
                    <h5><i class="bi bi-exclamation-triangle-fill"></i> Alianzas Pendientes de Aprobación</h5>
                    <?php foreach ($pendientes as $pend): ?>
                        <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
                            <div>
                                <strong><?= htmlspecialchars($pend['nombre_alianza']) ?></strong>
                                <small class="text-muted">- Fundador: <?= htmlspecialchars($pend['fundador_username']) ?></small>
                            </div>
                            <div>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="id_alianza_approve" value="<?= $pend['id_alianza'] ?>">
                                    <button type="submit" name="approve_alliance" class="btn btn-success btn-sm">
                                        <i class="bi bi-check-lg"></i> Aprobar
                                    </button>
                                </form>
                                <form method="post" class="d-inline" onsubmit="return confirm('¿Rechazar y eliminar esta alianza?')">
                                    <input type="hidden" name="id_alianza_reject" value="<?= $pend['id_alianza'] ?>">
                                    <button type="submit" name="reject_alliance" class="btn btn-danger btn-sm">
                                        <i class="bi bi-x-lg"></i> Rechazar
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; endif; ?>
    
    <!-- Lista de Alianzas Aprobadas -->
    <h4 class="mb-3">Alianzas Activas</h4>
    <div class="row g-4">
        <?php 
        $aprobadas = array_filter($alianzas, fn($a) => $a['aprobada'] == 1);
        foreach ($aprobadas as $alianza): 
            // Get members según tipo de alianza
            $tipo = $alianza['tipo_alianza'] ?? 'Militar';
            $campo_alianza = $tipo == 'Militar' ? 'id_alianza_militar' : 'id_alianza_economica';
            
            $members_stmt = $conn->prepare("
                SELECT p.nombre_pais, p.bandera_url, u.username
                FROM paises p
                LEFT JOIN usuarios u ON p.id_pais = u.id_pais
                WHERE p.$campo_alianza = :id
                ORDER BY p.nombre_pais
            ");
            $members_stmt->execute([':id' => $alianza['id_alianza']]);
            $members = $members_stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong><?= htmlspecialchars($alianza['nombre_alianza']) ?></strong>
                        <?php if ($alianza['id_fundador'] == $user_id || $is_admin_or_gm): ?>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="bi bi-gear"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#logoModal<?= $alianza['id_alianza'] ?>">
                                            <i class="bi bi-image"></i> Cambiar Logo
                                        </button>
                                    </li>
                                    <?php if (($alianza['id_fundador'] == $user_id && $alianza['aprobada']) || $is_admin_or_gm): ?>
                                    <li>
                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editModal<?= $alianza['id_alianza'] ?>">
                                            <i class="bi bi-pencil"></i> Editar Alianza
                                        </button>
                                    </li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar esta alianza?')">
                                            <input type="hidden" name="id_alianza_delete" value="<?= $alianza['id_alianza'] ?>">
                                            <button type="submit" name="delete_alliance" class="dropdown-item text-danger">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body text-center">
                        <?php if (!empty($alianza['logo_url'])): ?>
                            <img src="<?= htmlspecialchars($alianza['logo_url']) ?>" 
                                 alt="Logo" class="img-fluid rounded mb-3" style="max-height: 120px;">
                        <?php else: ?>
                            <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center mb-3" 
                                 style="height: 120px;">
                                <i class="bi bi-shield-fill" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <p class="text-muted small mb-3"><?= htmlspecialchars($alianza['descripcion'] ?: 'Sin descripción') ?></p>
                        
                        <div class="mb-3">
                            <span class="badge bg-secondary">
                                <i class="bi bi-people"></i> <?= $alianza['num_miembros'] ?> miembro(s)
                            </span>
                            <?php if (!empty($alianza['tipo_alianza'])): ?>
                                <span class="badge <?= $alianza['tipo_alianza'] == 'Militar' ? 'bg-danger' : 'bg-success' ?>">
                                    <i class="bi bi-<?= $alianza['tipo_alianza'] == 'Militar' ? 'shield' : 'cash-coin' ?>"></i> 
                                    <?= htmlspecialchars($alianza['tipo_alianza']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!$alianza['aprobada']): ?>
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-clock"></i> Pendiente
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Members List -->
                        <?php if (!empty($members)): ?>
                            <div class="text-start">
                                <small class="text-muted"><strong>Miembros:</strong></small>
                                <ul class="list-unstyled small">
                                    <?php foreach (array_slice($members, 0, 5) as $member): ?>
                                        <li>
                                            <?php if (!empty($member['bandera_url'])): ?>
                                                <img src="<?= htmlspecialchars($member['bandera_url']) ?>" 
                                                     alt="Flag" style="width: 20px; height: 15px;" class="me-1">
                                            <?php endif; ?>
                                            <?= htmlspecialchars($member['nombre_pais']) ?>
                                            <?php if ($member['username']): ?>
                                                <small class="text-muted">(<?= htmlspecialchars($member['username']) ?>)</small>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (count($members) > 5): ?>
                                        <li class="text-muted">... y <?= count($members) - 5 ?> más</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-muted small">
                        Fundador: <?= htmlspecialchars($alianza['fundador_username'] ?? 'Desconocido') ?>
                    </div>
                </div>
            </div>
            
            <!-- Modal: Subir Logo -->
            <div class="modal fade" id="logoModal<?= $alianza['id_alianza'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="post" enctype="multipart/form-data">
                            <div class="modal-header">
                                <h5 class="modal-title">Cambiar Logo</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id_alianza_logo" value="<?= $alianza['id_alianza'] ?>">
                                <div class="mb-3">
                                    <label class="form-label">Subir Imagen</label>
                                    <input type="file" name="logo_alianza" class="form-control" accept="image/*" required>
                                    <small class="text-muted">JPG, PNG, GIF, WEBP - Se convertirá a PNG con el nombre de la alianza</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Subir</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Modal: Editar Alianza -->
            <?php if (($alianza['id_fundador'] == $user_id && $alianza['aprobada']) || $is_admin_or_gm): ?>
            <div class="modal fade" id="editModal<?= $alianza['id_alianza'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="post">
                            <div class="modal-header">
                                <h5 class="modal-title">Editar Alianza</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id_alianza_edit" value="<?= $alianza['id_alianza'] ?>">
                                <div class="mb-3">
                                    <label class="form-label">Nombre de la Alianza *</label>
                                    <input type="text" name="nombre_alianza_edit" class="form-control" 
                                           value="<?= htmlspecialchars($alianza['nombre_alianza']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Alianza *</label>
                                    <select name="tipo_alianza_edit" class="form-select" required>
                                        <option value="Militar" <?= ($alianza['tipo_alianza'] ?? 'Militar') == 'Militar' ? 'selected' : '' ?>>Militar</option>
                                        <option value="Economica" <?= ($alianza['tipo_alianza'] ?? '') == 'Economica' ? 'selected' : '' ?>>Económica</option>
                                    </select>
                                    <small class="text-muted">Cambiar el tipo verificará que los miembros no tengan conflictos</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Descripción</label>
                                    <textarea name="descripcion_edit" class="form-control" rows="3"><?= htmlspecialchars($alianza['descripcion'] ?? '') ?></textarea>
                                </div>
                                <div class="alert alert-warning small">
                                    <i class="bi bi-exclamation-triangle"></i> Si cambias el tipo de alianza, el sistema verificará que ningún miembro tenga conflictos con otra alianza del nuevo tipo.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" name="edit_alliance" class="btn btn-warning">
                                    <i class="bi bi-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</main>

<!-- Modal: Crear Alianza -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Alianza</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre de la Alianza *</label>
                        <input type="text" name="nombre_alianza" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Alianza *</label>
                        <select name="tipo_alianza" class="form-select" required>
                            <option value="Militar" selected>Militar</option>
                            <option value="Economica">Económica</option>
                        </select>
                        <small class="text-muted">Puedes pertenecer a una alianza de cada tipo</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle"></i> La alianza debe ser aprobada por un GM antes de estar activa. Te unirás automáticamente como fundador.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="create_alliance" class="btn btn-primary">Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
