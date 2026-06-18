<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$page_title  = 'Mi Perfil';
$user_id     = $_SESSION['user_id'];
$id_pais     = $_SESSION['id_pais'] ?? null;
$message     = '';
$message_type= 'success';

// ── Leer datos actuales ──
$stmt = $conn->prepare("
    SELECT u.username, u.discord_user, u.id_rol, u.avatar_url, u.google_uid,
           p.id_pais, p.nombre_pais, p.bandera_url, p.turno_actual
    FROM usuarios u
    LEFT JOIN paises p ON u.id_pais = p.id_pais
    WHERE u.id_usuario = :id
");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// ── POST handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // — Actualizar Discord —
    if (isset($_POST['discord_user'])) {
        $nd = trim($_POST['discord_user']);
        $conn->prepare("UPDATE usuarios SET discord_user=:d WHERE id_usuario=:id")
             ->execute([':d' => $nd, ':id' => $user_id]);
        $message = 'Datos de cuenta actualizados.';
        $user['discord_user'] = $nd;
    }

    // — Actualizar nombre del país —
    if (isset($_POST['nombre_pais']) && $id_pais) {
        $nn = trim($_POST['nombre_pais']);
        if (!empty($nn)) {
            $conn->prepare("UPDATE paises SET nombre_pais=:n WHERE id_pais=:id")
                 ->execute([':n' => $nn, ':id' => $id_pais]);
            $message = 'País actualizado.';
            $user['nombre_pais'] = $nn;
        }
    }

    // — Subir bandera —
    if (isset($_FILES['bandera']) && $_FILES['bandera']['error'] !== UPLOAD_ERR_NO_FILE
        && $id_pais && !empty($user['nombre_pais'])) {
        $res = processAndSaveFlagImage($_FILES['bandera'], $id_pais, $user['nombre_pais']);
        if ($res['success']) {
            if (!empty($user['bandera_url'])) { $op = __DIR__.'/'.$user['bandera_url']; if(file_exists($op)) @unlink($op); }
            $conn->prepare("UPDATE paises SET bandera_url=:url WHERE id_pais=:id")
                 ->execute([':url' => $res['path'], ':id' => $id_pais]);
            $message = 'Bandera actualizada.';
            $user['bandera_url'] = $res['path'];
        } else { $message = 'Error bandera: ' . $res['error']; $message_type = 'danger'; }
    }

    // — Subir foto de perfil (AVATAR) —
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $res = processAndSaveAvatarImage($_FILES['avatar'], $user_id);
        if ($res['success']) {
            // Borrar avatar anterior si existe
            if (!empty($user['avatar_url'])) { $op = __DIR__.'/'.$user['avatar_url']; if(file_exists($op)) @unlink($op); }
            $conn->prepare("UPDATE usuarios SET avatar_url=:url WHERE id_usuario=:id")
                 ->execute([':url' => $res['path'], ':id' => $user_id]);
            $message = 'Foto de perfil actualizada.';
            $user['avatar_url'] = $res['path'];
        } else { $message = 'Error avatar: ' . $res['error']; $message_type = 'danger'; }
    }

    // — Eliminar foto de perfil —
    if (isset($_POST['remove_avatar'])) {
        if (!empty($user['avatar_url'])) { $op = __DIR__.'/'.$user['avatar_url']; if(file_exists($op)) @unlink($op); }
        $conn->prepare("UPDATE usuarios SET avatar_url=NULL WHERE id_usuario=:id")->execute([':id' => $user_id]);
        $message = 'Foto de perfil eliminada.';
        $user['avatar_url'] = null;
    }

    // — Desvincular Google —
    if (isset($_POST['unlink_google'])) {
        $conn->prepare("UPDATE usuarios SET google_uid=NULL WHERE id_usuario=:id")->execute([':id' => $user_id]);
        $message = 'Cuenta de Google desvinculada.';
        $user['google_uid'] = null;
    }
}

// Helpers
$flagUrl  = $user['bandera_url'] ?? '';
$hasFlag  = !empty($flagUrl) && $flagUrl !== 'default_flag.png';
$avatarUrl= $user['avatar_url'] ?? '';
$isExternalAvatar = !empty($avatarUrl) && (strpos($avatarUrl, 'http://') === 0 || strpos($avatarUrl, 'https://') === 0);
$hasAvatar= !empty($avatarUrl) && ($isExternalAvatar || file_exists(__DIR__ . '/' . $avatarUrl));
$avatarBust = ($hasAvatar && !$isExternalAvatar) ? '?v=' . filemtime(__DIR__ . '/' . $avatarUrl) : '';

$rolNames = [1=>'Administrador', 2=>'Game Master', 4=>'Participante'];
$rolColor = [1=>'danger', 2=>'warning', 4=>'primary'];
$idRol    = $user['id_rol'] ?? 4;

require_once 'includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4" id="profilePage">

    <!-- ── HERO ── -->
    <div class="card flag-glow mb-4" style="border-radius:20px;overflow:hidden">
        <div class="flag-hero" id="profileHero"
             style="background:linear-gradient(135deg,var(--accent),#6610f2);color:#fff;padding:2rem 2.5rem;position:relative;overflow:hidden">
            <div style="position:absolute;inset:0;background:linear-gradient(135deg,rgba(0,0,0,.15),transparent);pointer-events:none"></div>
            <div style="position:absolute;top:-60px;right:-60px;width:240px;height:240px;background:rgba(255,255,255,.06);border-radius:50%;filter:blur(30px)"></div>

            <div class="d-flex align-items-center gap-4 flex-wrap position-relative">

                <!-- Avatar / foto de perfil -->
                <div class="avatar-hero-wrap" style="position:relative;cursor:pointer"
                     onclick="document.getElementById('avatarHeroInput').click()"
                     title="Haz clic para cambiar tu foto de perfil">
                    <?php if ($hasAvatar): ?>
                        <img src="<?= htmlspecialchars($avatarUrl . $avatarBust) ?>"
                             alt="Foto de perfil"
                             style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.4);box-shadow:0 6px 20px rgba(0,0,0,.3)">
                    <?php else: ?>
                        <div style="width:90px;height:90px;border-radius:50%;background:rgba(255,255,255,.18);border:3px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:2.2rem;color:#fff;font-weight:800;box-shadow:0 6px 20px rgba(0,0,0,.2)">
                            <?= strtoupper(substr($user['username'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <!-- Overlay de edición -->
                    <div style="position:absolute;inset:0;border-radius:50%;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;opacity:0;transition:.2s ease"
                         class="avatar-overlay">
                        <i class="bi bi-camera-fill" style="font-size:1.4rem"></i>
                    </div>
                    <!-- Input oculto -->
                    <form method="post" enctype="multipart/form-data" id="avatarHeroForm">
                        <input type="file" id="avatarHeroInput" name="avatar"
                               accept="image/jpeg,image/png,image/gif,image/webp"
                               style="display:none"
                               onchange="document.getElementById('avatarHeroForm').submit()">
                    </form>
                </div>

                <!-- Info texto -->
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h2 class="mb-0" style="font-size:1.8rem;font-weight:800"><?= htmlspecialchars($user['username']) ?></h2>
                        <span class="badge bg-<?= $rolColor[$idRol] ?? 'secondary' ?>"><?= $rolNames[$idRol] ?? 'Usuario' ?></span>
                        <?php if ($hasAvatar): ?>
                        <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar tu foto de perfil?')">
                            <button type="submit" name="remove_avatar" value="1"
                                    class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);font-size:.72rem;padding:2px 8px;border-radius:6px">
                                <i class="bi bi-trash me-1"></i>Quitar foto
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php if ($user['nombre_pais']): ?>
                    <div style="opacity:.85;font-size:.95rem">
                        <?php if ($hasFlag): ?>
                            <img src="<?= htmlspecialchars($flagUrl) ?>" alt=""
                                 style="height:16px;border-radius:2px;margin-right:6px;vertical-align:middle">
                        <?php else: ?>
                            <i class="bi bi-flag-fill me-1"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($user['nombre_pais']) ?>
                        <?php if ($user['turno_actual']): ?>
                            <span class="badge ms-1" style="background:rgba(255,255,255,.2);font-size:.75rem">
                                Turno #<?= $user['turno_actual'] ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div style="opacity:.7;font-size:.875rem"><i class="bi bi-info-circle me-1"></i>Sin país asignado · Contacta a un GM</div>
                    <?php endif; ?>
                    <div style="margin-top:.4rem;opacity:.65;font-size:.78rem">
                        <i class="bi bi-camera me-1"></i>Haz clic en tu foto para cambiarla
                    </div>
                </div>

                <!-- Bandera del país -->
                <?php if ($hasFlag): ?>
                <div class="d-none d-lg-block">
                    <img id="profileFlag" src="<?= htmlspecialchars($flagUrl) ?>"
                         class="flag-auto" data-flag-target="#profilePage" data-flag-accent="true"
                         alt="Bandera"
                         style="height:70px;border-radius:10px;border:2px solid rgba(255,255,255,.3);box-shadow:0 6px 20px rgba(0,0,0,.25);opacity:.9">
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show mb-4">
        <?= $message ?><button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- ── COL IZQ: País + Foto de perfil ── -->
        <div class="col-lg-5">

            <!-- Foto de perfil (tarjeta dedicada) -->
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2"
                     style="background:linear-gradient(135deg,rgba(102,16,242,.12),rgba(102,16,242,.04))">
                    <i class="bi bi-person-circle" style="color:#6610f2"></i>
                    <strong>Foto de Perfil</strong>
                </div>
                <div class="card-body text-center">
                    <!-- Preview actual -->
                    <div class="mb-3">
                        <?php if ($hasAvatar): ?>
                            <img src="<?= htmlspecialchars($avatarUrl . $avatarBust) ?>"
                                 alt="Foto de perfil"
                                 style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid var(--border-color);box-shadow:0 4px 16px rgba(0,0,0,.15)">
                        <?php else: ?>
                            <div style="width:120px;height:120px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#6610f2);display:inline-flex;align-items:center;justify-content:center;font-size:3rem;color:#fff;font-weight:800;box-shadow:0 4px 16px rgba(13,110,253,.25)">
                                <?= strtoupper(substr($user['username'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div class="mt-2" style="font-size:.75rem;color:var(--text-secondary)">
                            <?= $hasAvatar ? 'Tu foto actual' : 'Sin foto — se muestra inicial' ?>
                        </div>
                    </div>

                    <!-- Upload zone -->
                    <form method="post" enctype="multipart/form-data">
                        <div id="avatarDropZone" onclick="document.getElementById('avatarFileInput').click()"
                             style="border:2px dashed var(--border-color);border-radius:12px;padding:1.5rem 1rem;cursor:pointer;transition:all .2s ease">
                            <img id="avatarPreview" src="" style="display:none;width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:.5rem">
                            <div id="avatarDropText">
                                <i class="bi bi-cloud-upload" style="font-size:1.6rem;color:var(--text-secondary)"></i>
                                <div style="font-size:.8rem;color:var(--text-secondary);margin-top:.4rem">
                                    Clic o arrastra tu foto aquí
                                </div>
                                <div style="font-size:.72rem;color:var(--text-secondary)">
                                    JPG · PNG · GIF · WEBP · Máx 5 MB
                                </div>
                                <div style="font-size:.7rem;color:var(--text-secondary);margin-top:.2rem">
                                    Se recortará automáticamente en cuadrado
                                </div>
                            </div>
                            <input type="file" id="avatarFileInput" name="avatar"
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   style="display:none"
                                   onchange="previewAvatar(this)">
                        </div>
                        <div id="avatarFileInfo" class="mt-2" style="font-size:.78rem;color:var(--text-secondary)"></div>
                        <button type="submit" id="avatarSubmitBtn" class="btn btn-primary w-100 mt-3" disabled>
                            <i class="bi bi-cloud-upload me-1"></i>Guardar Foto de Perfil
                        </button>
                    </form>

                    <?php if ($hasAvatar): ?>
                    <hr style="border-color:var(--border-color)">
                    <form method="post" onsubmit="return confirm('¿Eliminar tu foto de perfil?')">
                        <button type="submit" name="remove_avatar" value="1" class="btn btn-sm btn-outline-danger w-100">
                            <i class="bi bi-trash me-1"></i>Eliminar foto de perfil
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- País -->
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2"
                     style="background:linear-gradient(135deg,rgba(13,110,253,.12),rgba(13,110,253,.04))">
                    <i class="bi bi-flag-fill text-primary"></i><strong>Mi País</strong>
                </div>
                <div class="card-body">
                    <?php if ($user['nombre_pais']): ?>
                    <div class="text-center mb-3">
                        <?php if ($hasFlag): ?>
                            <img src="<?= htmlspecialchars($flagUrl) ?>" alt="Bandera"
                                 style="max-height:90px;max-width:170px;border-radius:10px;border:2px solid var(--border-color);box-shadow:0 4px 12px rgba(0,0,0,.12)">
                        <?php else: ?>
                            <div class="py-3" style="color:var(--text-secondary)"><i class="bi bi-image" style="font-size:2rem"></i><br><small>Sin bandera</small></div>
                        <?php endif; ?>
                    </div>
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-500">Nombre del País</label>
                            <input type="text" name="nombre_pais" class="form-control" value="<?= htmlspecialchars($user['nombre_pais']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-500">Reemplazar Bandera</label>
                            <div id="flagDropZone" onclick="document.getElementById('flagFileInput').click()"
                                 style="border:2px dashed var(--border-color);border-radius:10px;padding:1.2rem;text-align:center;cursor:pointer;transition:all .2s">
                                <img id="flagPreview" src="" style="display:none;max-height:50px;border-radius:6px;margin-bottom:.4rem">
                                <div id="flagDropText">
                                    <i class="bi bi-cloud-upload" style="font-size:1.4rem;color:var(--text-secondary)"></i>
                                    <div style="font-size:.78rem;color:var(--text-secondary);margin-top:.2rem">JPG · PNG · GIF · WEBP</div>
                                </div>
                                <input type="file" id="flagFileInput" name="bandera" accept="image/*" style="display:none" onchange="prevFlag(this)">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save me-1"></i>Actualizar País
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="text-center py-4" style="color:var(--text-secondary)">
                        <i class="bi bi-map" style="font-size:2.5rem"></i>
                        <p class="mt-3 mb-0">Sin país asignado.<br><small>Contacta a un GM para asignación.</small></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── COL DER: Cuenta + Links ── -->
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2"
                     style="background:linear-gradient(135deg,rgba(13,110,253,.12),rgba(13,110,253,.04))">
                    <i class="bi bi-person-badge text-primary"></i><strong>Datos de Cuenta</strong>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label fw-500">Usuario</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                            <small class="text-muted">El nombre de usuario no se puede cambiar.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-500">Discord</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-discord" style="color:#7289da"></i></span>
                                <input type="text" name="discord_user" class="form-control"
                                       value="<?= htmlspecialchars($user['discord_user'] ?? '') ?>"
                                       placeholder="usuario#1234">
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Guardar
                            </button>
                            <a href="profile_history.php" class="btn btn-outline-secondary">
                                <i class="bi bi-clock-history me-1"></i>Historial
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Volver
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cuenta de Google -->
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2"
                     style="background:linear-gradient(135deg,rgba(220,53,69,.12),rgba(220,53,69,.04))">
                    <i class="bi bi-google text-danger"></i><strong>Cuenta de Google</strong>
                </div>
                <div class="card-body">
                    <?php if (!empty($user['google_uid'])): ?>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <div class="fw-bold text-success"><i class="bi bi-patch-check-fill me-1"></i>Conectado</div>
                                <div class="text-muted" style="font-size: .8rem;">Tu cuenta de Google está vinculada a Georol. Puedes usarla para iniciar sesión con un solo clic.</div>
                            </div>
                            <form method="post" onsubmit="return confirm('¿Desvincular tu cuenta de Google? Tendrás que usar tu contraseña para iniciar sesión.')">
                                <button type="submit" name="unlink_google" value="1" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-x-circle me-1"></i>Desvincular
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <div class="fw-bold text-warning"><i class="bi bi-exclamation-triangle-fill me-1"></i>No conectado</div>
                                <div class="text-muted" style="font-size: .8rem;">Conecta tu cuenta de Google para poder iniciar sesión rápidamente.</div>
                            </div>
                            <button type="button" id="btn-profile-google-link" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1">
                                <i class="bi bi-google"></i> Conectar Google
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick links -->
            <div class="card">
                <div class="card-header"><i class="bi bi-lightning-fill me-2 text-warning"></i><strong>Acceso Rápido</strong></div>
                <div class="card-body">
                    <div class="row g-2">
                        <?php if ($id_pais): ?>
                        <div class="col-6">
                            <a href="view_country.php?id=<?= $id_pais ?>" class="btn btn-outline-primary w-100">
                                <i class="bi bi-file-earmark-text me-1"></i>Mi Cartilla
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="enfoques.php" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-crosshair2 me-1"></i>Enfoques
                            </a>
                        </div>
                        <?php endif; ?>
                        <div class="col-6">
                            <a href="alianzas.php" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-people me-1"></i>Alianzas
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="change_password.php" class="btn btn-outline-warning w-100">
                                <i class="bi bi-key me-1"></i>Cambiar Clave
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /row -->
</div><!-- /container -->

<style>
.avatar-hero-wrap:hover .avatar-overlay { opacity: 1 !important; }
</style>

<script>
/* ── Avatar preview ── */
function previewAvatar(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
        document.getElementById('avatarFileInfo').innerHTML = '<span class="text-danger">⚠ Archivo muy grande (máx 5 MB)</span>';
        document.getElementById('avatarSubmitBtn').disabled = true;
        return;
    }
    const reader = new FileReader();
    reader.onload = e => {
        const prev = document.getElementById('avatarPreview');
        const zone = document.getElementById('avatarDropZone');
        prev.src = e.target.result;
        prev.style.display = 'block';
        document.getElementById('avatarDropText').style.display = 'none';
        zone.style.borderColor = '#6610f2';
        zone.style.background  = 'rgba(102,16,242,.04)';
        const kb = (file.size / 1024).toFixed(1);
        document.getElementById('avatarFileInfo').innerHTML =
            `<i class="bi bi-check-circle text-success"></i> ${file.name} <span class="text-muted">(${kb} KB)</span>`;
        document.getElementById('avatarSubmitBtn').disabled = false;
    };
    reader.readAsDataURL(file);
}

/* ── Flag preview ── */
function prevFlag(input) {
    const file = input.files[0]; if (!file) return;
    const r = new FileReader();
    r.onload = e => {
        document.getElementById('flagPreview').src = e.target.result;
        document.getElementById('flagPreview').style.display = 'block';
        document.getElementById('flagDropText').style.display = 'none';
        document.getElementById('flagDropZone').style.borderColor = 'var(--accent)';
    };
    r.readAsDataURL(file);
}

/* ── Drag & drop: Avatar ── */
const adz = document.getElementById('avatarDropZone');
if (adz) {
    adz.addEventListener('dragover', e => { e.preventDefault(); adz.style.borderColor = '#6610f2'; adz.style.background = 'rgba(102,16,242,.06)'; });
    adz.addEventListener('dragleave', () => { adz.style.borderColor = ''; adz.style.background = ''; });
    adz.addEventListener('drop', e => {
        e.preventDefault(); adz.style.borderColor = ''; adz.style.background = '';
        const fi = document.getElementById('avatarFileInput');
        fi.files = e.dataTransfer.files; previewAvatar(fi);
    });
}

/* ── Drag & drop: Flag ── */
const fdz = document.getElementById('flagDropZone');
if (fdz) {
    fdz.addEventListener('dragover', e => { e.preventDefault(); fdz.style.borderColor = 'var(--accent)'; });
    fdz.addEventListener('dragleave', () => { fdz.style.borderColor = ''; });
    fdz.addEventListener('drop', e => {
        e.preventDefault(); fdz.style.borderColor = '';
        const fi = document.getElementById('flagFileInput');
        fi.files = e.dataTransfer.files; prevFlag(fi);
    });
}
</script>

<!-- Firebase implementation for profile linking -->
<script type="module">
  import { initializeApp } from "https://www.gstatic.com/firebasejs/12.14.0/firebase-app.js";
  import { getAuth, signInWithPopup, GoogleAuthProvider } from "https://www.gstatic.com/firebasejs/12.14.0/firebase-auth.js";

  const firebaseConfig = {
    apiKey: "AIzaSyCtm8hKynIP42smkz-rCmpqG4Q3vDrBMfw",
    authDomain: "georol.firebaseapp.com",
    projectId: "georol",
    storageBucket: "georol.firebasestorage.app",
    messagingSenderId: "913683951965",
    appId: "1:913683951965:web:902050146ffb3a0b0fb8b4",
    measurementId: "G-4464DJ5L08"
  };

  const app = initializeApp(firebaseConfig);
  const auth = getAuth(app);
  const provider = new GoogleAuthProvider();

  window.triggerProfileGoogleLink = async function() {
      const btn = document.getElementById('btn-profile-google-link');
      if (!btn) return;
      
      const originalText = btn.innerHTML;
      try {
          btn.disabled = true;
          btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Conectando...';
          
          const result = await signInWithPopup(auth, provider);
          const user = result.user;
          const photoURL = user.photoURL || '';
          
          const response = await fetch('api/google_login.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                  action: 'link_profile',
                  google_uid: user.uid,
                  photo_url: photoURL
              })
          });
          
          const data = await response.json();
          if (!data.success) {
              throw new Error(data.error || 'Error al vincular la cuenta');
          }
          
          window.location.reload();
      } catch (err) {
          console.error(err);
          alert(err.message || 'Error al conectar con Google.');
      } finally {
          btn.disabled = false;
          btn.innerHTML = originalText;
      }
  };
</script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
      const linkBtn = document.getElementById('btn-profile-google-link');
      if (linkBtn) {
          linkBtn.addEventListener('click', function() {
              if (typeof window.triggerProfileGoogleLink === 'function') {
                  window.triggerProfileGoogleLink();
              }
          });
      }
  });
</script>

<?php require_once 'includes/footer.php'; ?>
