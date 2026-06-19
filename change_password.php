 <?php
session_start();
require_once 'config/db.php';

// Usuario debe estar logueado para cambiar contraseña
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = 'danger';

// Verificar si realmente debe cambiar contraseña
$stmt = $conn->prepare("SELECT debe_cambiar_password, username FROM usuarios WHERE id_usuario = :id");
$stmt->execute([':id' => $user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no necesita cambiar contraseña, redirigir al panel
if (!$user_data['debe_cambiar_password']) {
    header("Location: /index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validaciones
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "Todos los campos son obligatorios.";
    } elseif ($new_password !== $confirm_password) {
        $message = "Las contraseñas nuevas no coinciden.";
    } elseif (strlen($new_password) < 6) {
        $message = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        // Verificar contraseña actual
        $stmt = $conn->prepare("SELECT password_hash FROM usuarios WHERE id_usuario = :id");
        $stmt->execute([':id' => $user_id]);
        $current_hash = $stmt->fetchColumn();
        
        if (password_verify($current_password, $current_hash)) {
            try {
                // Actualizar contraseña y desactivar flag
                $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
                $update = $conn->prepare("UPDATE usuarios 
                                          SET password_hash = :hash, 
                                              debe_cambiar_password = 0 
                                          WHERE id_usuario = :id");
                $update->execute([':hash' => $new_hash, ':id' => $user_id]);
                
                $message = "Contraseña actualizada exitosamente. Redirigiendo...";
                $message_type = 'success';
                
                // Redirigir al panel después de 2 segundos
                header("refresh:2;url=/index.php");
            } catch (Exception $e) {
                $message = "Error al actualizar contraseña: " . $e->getMessage();
            }
        } else {
            $message = "La contraseña actual es incorrecta.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambio Obligatorio de Contraseña - Georol</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme.js"></script>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="card shadow-lg">
                    <div class="card-header bg-warning text-dark text-center">
                        <h4 class="mb-0">
                            <i class="bi bi-shield-lock"></i> Cambio Obligatorio de Contraseña
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-warning" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> 
                            <strong>Atención, <?= htmlspecialchars($user_data['username']) ?>:</strong><br>
                            El administrador ha restablecido tu contraseña. Por seguridad, debes cambiarla antes de continuar.
                        </div>

                        <?php if($message): ?>
                            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                                <?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="post" autocomplete="off">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">
                                    <i class="bi bi-key"></i> Contraseña Actual (temporal)
                                </label>
                                <input type="password" 
                                       class="form-control" 
                                       id="current_password" 
                                       name="current_password" 
                                       placeholder="Contraseña que te dio el administrador" 
                                       required 
                                       autocomplete="off">
                                <small class="text-muted">Esta es la contraseña temporal que recibiste del administrador</small>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">
                                    <i class="bi bi-shield-check"></i> Nueva Contraseña
                                </label>
                                <input type="password" 
                                       class="form-control" 
                                       id="new_password" 
                                       name="new_password" 
                                       placeholder="Mínimo 6 caracteres" 
                                       required 
                                       minlength="6"
                                       autocomplete="new-password">
                                <small class="text-muted">Usa una contraseña segura que solo tú conozcas</small>
                            </div>

                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">
                                    <i class="bi bi-shield-check"></i> Confirmar Nueva Contraseña
                                </label>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       placeholder="Repite la contraseña nueva" 
                                       required 
                                       minlength="6"
                                       autocomplete="new-password">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle"></i> Cambiar Contraseña y Continuar
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <a href="logout.php" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-box-arrow-left"></i> Cerrar Sesión
                            </a>
                        </div>
                    </div>
                    <div class="card-footer text-center text-muted">
                        <small>
                            <i class="bi bi-info-circle"></i> 
                            No podrás acceder al sistema hasta que cambies tu contraseña
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Validación en tiempo real
        const newPass = document.getElementById('new_password');
        const confirmPass = document.getElementById('confirm_password');
        
        confirmPass.addEventListener('input', function() {
            if (this.value !== newPass.value) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });
        
        newPass.addEventListener('input', function() {
            if (confirmPass.value && confirmPass.value !== this.value) {
                confirmPass.setCustomValidity('Las contraseñas no coinciden');
            } else {
                confirmPass.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
