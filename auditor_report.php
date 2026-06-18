<?php
session_start();

// Solo Auditor o Admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['id_rol'], [1, 3])) {
    header("Location: /index.php");
    exit;
}

require_once 'config/db.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $contenido = trim($_POST['contenido']);
    if (!empty($contenido)) {
        try {
            $stmt = $conn->prepare("INSERT INTO notificaciones (id_remitente, id_rol_receptor, contenido) VALUES (:rem, 2, :cont)");
            $stmt->execute([':rem' => $_SESSION['user_id'], ':cont' => $contenido]);
            $message = "Reporte enviado a los GMs.";
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    } else {
        $message = "El reporte no puede estar vacío.";
    }
}

$page_title = 'Crear Reporte - Auditoría';
require_once 'includes/header.php';
?>

    <div class="container my-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header">
                        <h2 class="mb-0"><i class="bi bi-megaphone"></i> Crear Reporte para GMs</h2>
                    </div>
                    <div class="card-body">
                        <?php if($message): ?>
                            <div class="alert alert-<?php echo (strpos($message, 'Error') === false) ? 'success' : 'danger'; ?> alert-dismissible fade show">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="mb-3">
                                <label for="contenido" class="form-label"><strong>Contenido del Reporte</strong></label>
                                <textarea name="contenido" id="contenido" class="form-control" rows="8" required 
                                          placeholder="Escribe aquí las observaciones, irregularidades o sugerencias para los Game Masters..."></textarea>
                                <div class="form-text">Este reporte será visible para todos los GMs en su panel de control.</div>
                            </div>
                            <button type="submit" class="btn btn-warning btn-lg">
                                <i class="bi bi-send"></i> Enviar Reporte
                            </button>
                            <a href="auditor_list.php" class="btn btn-secondary btn-lg">
                                <i class="bi bi-arrow-left"></i> Cancelar
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>
