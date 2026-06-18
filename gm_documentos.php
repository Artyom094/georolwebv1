<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['id_rol'], [1, 2])) {
    header("Location: index.php"); exit;
}
require_once 'config/db.php';
require_once 'includes/functions.php';

ensureSiteDocumentsTable($conn);

$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_document'])) {
    $slug = $_POST['slug'] ?? '';
    $titulo = trim($_POST['titulo'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');
    $allowed = ['reglas', 'privacidad'];

    if (!in_array($slug, $allowed, true)) {
        $message = 'Documento no válido.';
        $message_type = 'danger';
    } else {
        $doc = getSiteDocument($conn, $slug);
        $can_edit = ($_SESSION['id_rol'] == 1) || ($slug === 'reglas' && ($_SESSION['id_rol'] == 2) && !empty($doc['editable_by_gm']));

        if (!$can_edit) {
            $message = 'No tienes permisos para editar este documento.';
            $message_type = 'danger';
        } elseif ($titulo === '' || $contenido === '') {
            $message = 'Título y contenido son obligatorios.';
            $message_type = 'warning';
        } else {
            saveSiteDocument($conn, $slug, $titulo, $contenido, intval($_SESSION['user_id']));
            $message = 'Documento actualizado correctamente.';
            $message_type = 'success';
        }
    }
}

$docs = [
    'reglas' => getSiteDocument($conn, 'reglas'),
    'privacidad' => getSiteDocument($conn, 'privacidad'),
];

$page_title = 'Gestión de Documentos';
require_once 'includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <div class="card flag-glow mb-4" style="border-radius:20px;overflow:hidden">
        <div style="background:linear-gradient(135deg,#dc3545,#fd7e14);color:#fff;padding:2rem 2.5rem;position:relative;overflow:hidden">
            <div style="position:absolute;top:-60px;right:-60px;width:220px;height:220px;background:rgba(255,255,255,.07);border-radius:50%;filter:blur(30px)"></div>
            <div class="d-flex align-items-center gap-3 position-relative">
                <div style="width:56px;height:56px;background:rgba(255,255,255,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;backdrop-filter:blur(8px)">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div>
                    <h1 class="mb-0" style="font-size:1.6rem;font-weight:800">Gestión de Documentos</h1>
                    <p class="mb-0 mt-1" style="opacity:.8;font-size:.875rem">Administra las reglas y las políticas de privacidad del sitio</p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show mb-4">
        <?= htmlspecialchars($message) ?><button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php foreach ($docs as $slug => $doc):
            $can_edit = ($_SESSION['id_rol'] == 1) || ($slug === 'reglas' && ($_SESSION['id_rol'] == 2) && !empty($doc['editable_by_gm']));
        ?>
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-<?= $slug === 'reglas' ? 'journal-text' : 'shield-lock' ?>"></i>
                    <strong><?= htmlspecialchars($doc['titulo'] ?? ucfirst($slug)) ?></strong>
                    <span class="badge ms-auto" style="background:var(--glass-bg);color:var(--text-primary);border:1px solid var(--border-color)">
                        <?= $can_edit ? 'Editable' : 'Solo lectura' ?>
                    </span>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">
                        <div class="mb-3">
                            <label class="form-label fw-500">Título</label>
                            <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($doc['titulo'] ?? '') ?>" <?= $can_edit ? '' : 'readonly' ?> required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-500">Contenido</label>
                            <textarea name="contenido" class="form-control" rows="14" <?= $can_edit ? '' : 'readonly' ?> required><?= htmlspecialchars($doc['contenido'] ?? '') ?></textarea>
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                            <small class="text-muted">Actualizado: <?= !empty($doc['updated_at']) ? htmlspecialchars($doc['updated_at']) : 'sin registrar' ?></small>
                            <?php if ($can_edit): ?>
                            <button type="submit" name="save_document" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Guardar cambios
                            </button>
                            <?php else: ?>
                            <span class="text-muted small">Los Game Masters pueden modificar Reglas; Privacidad queda reservada al administrador.</span>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>