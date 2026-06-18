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
        
        if (!empty($nombre)) {
            try {
                $stmt = $conn->prepare("INSERT INTO alianzas (nombre_alianza, descripcion, id_fundador) VALUES (:n, :d, :f)");
                $stmt->execute([':n' => $nombre, ':d' => $desc, ':f' => $user_id]);
                $message = "Alianza creada correctamente.";
            } catch (PDOException $e) {
                $message = "Error: El nombre ya existe.";
            }
        }
    }
    
    // Subir Logo
    if (isset($_FILES['logo_alianza']) && isset($_POST['id_alianza_logo'])) {
        $id_alianza = intval($_POST['id_alianza_logo']);
        
        // Verificar permisos (fundador o admin/gm)
        $check = $conn->prepare("SELECT id_fundador FROM alianzas WHERE id_alianza = :id");
        $check->execute([':id' => $id_alianza]);
        $fundador = $check->fetchColumn();
        
        if ($fundador == $user_id || in_array($_SESSION['id_rol'], [1, 2])) {
            if ($_FILES['logo_alianza']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($_FILES['logo_alianza']['name'], PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $filename = 'alianza_' . $id_alianza . '_' . time() . '.' . $ext;
                    $upload_dir = __DIR__ . '/assets/uploads/alianzas/';
                    $upload_path = 'assets/uploads/alianzas/' . $filename;
                    $full_path = $upload_dir . $filename;
                    
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    if (move_uploaded_file($_FILES['logo_alianza']['tmp_name'], $full_path)) {
                        // Delete old logo
                        $old = $conn->prepare("SELECT logo_url FROM alianzas WHERE id_alianza = :id");
                        $old->execute([':id' => $id_alianza]);
                        $old_url = $old->fetchColumn();
                        if (!empty($old_url) && file_exists(__DIR__ . '/' . $old_url)) {
                            unlink(__DIR__ . '/' . $old_url);
                        }
                        
                        $update = $conn->prepare("UPDATE alianzas SET logo_url = :url WHERE id_alianza = :id");
                        $update->execute([':url' => $upload_path, ':id' => $id_alianza]);
                        $message = "Logo subido correctamente.";
                    }
                }
            }
        }
    }
    
    // Unirse a Alianza
    if (isset($_POST['join_alliance']) && $user_pais) {
        $id_alianza = intval($_POST['id_alianza_join']);
        
        // Get current alliance
        $current = $conn->prepare("SELECT id_alianza FROM paises WHERE id_pais = :id");
        $current->execute([':id' => $user_pais]);
        $current_alliance = $current->fetchColumn();
        
        // Update
        $update = $conn->prepare("UPDATE paises SET id_alianza = :a WHERE id_pais = :p");
        $update->execute([':a' => $id_alianza, ':p' => $user_pais]);
        
        // Log
        $log = $conn->prepare("
            INSERT INTO historial_alianzas (id_pais, id_alianza_anterior, id_alianza_nueva, accion, id_usuario_accion)
            VALUES (:p, :ant, :nue, 'unirse', :u)
        ");
        $log->execute([':p' => $user_pais, ':ant' => $current_alliance, ':nue' => $id_alianza, ':u' => $user_id]);
        
        $message = "Te has unido a la alianza.";
    }
    
    // Abandonar Alianza
    if (isset($_POST['leave_alliance']) && $user_pais) {
        $current = $conn->prepare("SELECT id_alianza FROM paises WHERE id_pais = :id");
        $current->execute([':id' => $user_pais]);
        $current_alliance = $current->fetchColumn();
        
        $update = $conn->prepare("UPDATE paises SET id_alianza = NULL WHERE id_pais = :p");
        $update->execute([':p' => $user_pais]);
        
        $log = $conn->prepare("
            INSERT INTO historial_alianzas (id_pais, id_alianza_anterior, id_alianza_nueva, accion, id_usuario_accion)
            VALUES (:p, :ant, NULL, 'abandonar', :u)
        ");
        $log->execute([':p' => $user_pais, ':ant' => $current_alliance, ':u' => $user_id]);
        
        $message = "Has abandonado la alianza.";
    }
    
    // Eliminar Alianza (Solo fundador o admin/gm)
    if (isset($_POST['delete_alliance'])) {
        $id_alianza = intval($_POST['id_alianza_delete']);
        
        $check = $conn->prepare("SELECT id_fundador FROM alianzas WHERE id_alianza = :id");
        $check->execute([':id' => $id_alianza]);
        $fundador = $check->fetchColumn();
        
        if ($fundador == $user_id || in_array($_SESSION['id_rol'], [1, 2])) {
            $conn->prepare("DELETE FROM alianzas WHERE id_alianza = :id")->execute([':id' => $id_alianza]);
            $message = "Alianza eliminada.";
        }
    }
}

// ==================== OBTENER DATOS ====================

// All alliances
$alianzas = $conn->query("
    SELECT a.*, u.username as fundador_username,
           (SELECT COUNT(*) FROM paises WHERE id_alianza = a.id_alianza) as num_miembros
    FROM alianzas a
    LEFT JOIN usuarios u ON a.id_fundador = u.id_usuario
    ORDER BY a.nombre_alianza
")->fetchAll(PDO::FETCH_ASSOC);

// User's current alliance
$user_alliance = null;
if ($user_pais) {
    $stmt = $conn->prepare("
        SELECT a.* FROM alianzas a
        JOIN paises p ON a.id_alianza = p.id_alianza
        WHERE p.id_pais = :id
    ");
    $stmt->execute([':id' => $user_pais]);
    $user_alliance = $stmt->fetch(PDO::FETCH_ASSOC);
}

include 'includes/header.php';
?>

<main class="container my-4">
    
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-people-fill"></i> Alianzas</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="bi bi-plus-lg"></i> Nueva Alianza
                </button>
            </div>
        </div>
    </div>
    
    <!-- Mi Alianza Actual -->
    <?php if ($user_pais): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card <?= $user_alliance ? 'border-primary' : 'border-secondary' ?>">
                    <div class="card-header <?= $user_alliance ? 'bg-primary text-white' : 'bg-secondary text-white' ?>">
                        <h5 class="mb-0"><i class="bi bi-shield-fill"></i> Mi Alianza</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($user_alliance): ?>
                            <div class="row align-items-center">
                                <div class="col-md-2 text-center">
                                    <?php if (!empty($user_alliance['logo_url'])): ?>
                                        <img src="<?= htmlspecialchars($user_alliance['logo_url']) ?>" 
                                             alt="Logo" class="img-fluid rounded" style="max-height: 100px;">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" 
                                             style="height: 100px;">
                                            <i class="bi bi-shield-fill" style="font-size: 3rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-7">
                                    <h4><?= htmlspecialchars($user_alliance['nombre_alianza']) ?></h4>
                                    <p class="text-muted mb-0"><?= htmlspecialchars($user_alliance['descripcion'] ?: 'Sin descripción') ?></p>
                                </div>
                                <div class="col-md-3 text-end">
                                    <form method="post" onsubmit="return confirm('¿Seguro que deseas abandonar esta alianza?')">
                                        <button type="submit" name="leave_alliance" class="btn btn-danger">
                                            <i class="bi bi-box-arrow-right"></i> Abandonar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> No perteneces a ninguna alianza. Únete a una o crea la tuya.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Lista de Alianzas -->
    <div class="row g-4">
        <?php foreach ($alianzas as $alianza): 
            // Get members
            $members_stmt = $conn->prepare("
                SELECT p.nombre_pais, p.bandera_url, u.username
                FROM paises p
                LEFT JOIN usuarios u ON p.id_pais = u.id_pais
                WHERE p.id_alianza = :id
                ORDER BY p.nombre_pais
            ");
            $members_stmt->execute([':id' => $alianza['id_alianza']]);
            $members = $members_stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong><?= htmlspecialchars($alianza['nombre_alianza']) ?></strong>
                        <?php if ($alianza['id_fundador'] == $user_id || in_array($_SESSION['id_rol'], [1, 2])): ?>
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
                        
                        <?php if ($user_pais && (!$user_alliance || $user_alliance['id_alianza'] != $alianza['id_alianza'])): ?>
                            <form method="post" class="mt-3">
                                <input type="hidden" name="id_alianza_join" value="<?= $alianza['id_alianza'] ?>">
                                <button type="submit" name="join_alliance" class="btn btn-success btn-sm w-100">
                                    <i class="bi bi-box-arrow-in-right"></i> Unirse
                                </button>
                            </form>
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
                                    <small class="text-muted">JPG, PNG, GIF, WEBP</small>
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
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle"></i> Podrás subir el logo después de crear la alianza.
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
