<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require_once 'config/db.php';
require_once 'includes/functions.php';

$doc = getSiteDocument($conn, 'privacidad');
$page_title = $doc['titulo'] ?? 'Políticas de Privacidad';
require_once 'includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <div class="card flag-glow mb-4" style="border-radius:20px;overflow:hidden">
        <div style="background:linear-gradient(135deg,#0d6efd,#6610f2);color:#fff;padding:2rem 2.5rem;position:relative;overflow:hidden">
            <div style="position:absolute;top:-60px;right:-60px;width:220px;height:220px;background:rgba(255,255,255,.07);border-radius:50%;filter:blur(30px)"></div>
            <div class="d-flex align-items-center gap-3 position-relative">
                <div style="width:56px;height:56px;background:rgba(255,255,255,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;backdrop-filter:blur(8px)">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <div>
                    <h1 class="mb-0" style="font-size:1.6rem;font-weight:800"><?= htmlspecialchars($page_title) ?></h1>
                    <p class="mb-0 mt-1" style="opacity:.8;font-size:.875rem">Lo puse mas por oblicacion</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-4 p-lg-5" style="font-size:1rem;line-height:1.75">
            <?= $doc ? renderSiteDocumentHtml($doc['contenido']) : '<p class="text-muted mb-0">No hay contenido configurado.</p>' ?>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>