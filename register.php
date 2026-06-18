<?php
session_start();
require_once 'config/db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $discord = trim($_POST['discord']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($username) || empty($password) || empty($discord)) {
        $error = "Todos los campos son obligatorios.";
    } elseif ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE username = :username");
        $stmt->execute([':username' => $username]);
        
        if ($stmt->rowCount() > 0) {
            $error = "El nombre de usuario ya está en uso.";
        } else {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $sql = "INSERT INTO usuarios (username, password_hash, discord_user, id_rol) VALUES (:u, :p, :d, 4)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':u' => $username, ':p' => $hash, ':d' => $discord]);
                
                $success = "Registro exitoso. Ahora puedes <a href='login.php' class='alert-link'>iniciar sesión</a>.";
            } catch(PDOException $e) {
                $error = "Error al registrar: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Georol</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme.js"></script>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="login-container">
        <div class="card shadow-lg">
            <div class="card-body p-5">
                <h2 class="card-title text-center mb-4">
                    <i class="bi bi-person-plus"></i> <strong>Registro</strong>
                </h2>
                
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php else: ?>

                <form method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Usuario</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="discord" class="form-label">Usuario Discord</label>
                        <input type="text" class="form-control" name="discord" required placeholder="ej. usuario#1234">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">Registrarse</button>
                </form>
                <?php endif; ?>
                
                <div class="text-center">
                    <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
                </div>
            </div>
        </div>
    </div>

    <button id="theme-toggle" class="btn btn-dark theme-toggle" title="Cambiar tema">
        <i class="bi bi-moon-fill"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
