<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['id_rol'] != 1) { header("Location: index.php"); exit; }
require_once 'config/db.php';
require_once 'includes/functions.php';

$message = ''; $message_type = 'info';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['create_user'])) {
        $username = trim($_POST['new_username']); $password = $_POST['new_password'];
        $discord = trim($_POST['new_discord']); $role = intval($_POST['new_role']);
        if (!empty($username) && !empty($password)) {
            try {
                $check = $conn->prepare("SELECT id_usuario FROM usuarios WHERE username = :u");
                $check->execute([':u' => $username]);
                if ($check->rowCount() > 0) { $message = "El usuario ya existe."; $message_type='danger'; }
                else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $conn->prepare("INSERT INTO usuarios (username,password_hash,discord_user,id_rol,activo) VALUES(:u,:p,:d,:r,1)")
                         ->execute([':u'=>$username,':p'=>$hash,':d'=>$discord,':r'=>$role]);
                    $message = "Usuario <strong>$username</strong> creado."; $message_type='success';
                }
            } catch(PDOException $e) { $message="Error: ".$e->getMessage(); $message_type='danger'; }
        } else { $message="Usuario y contraseña son obligatorios."; $message_type='warning'; }
    }
    if (isset($_POST['reset_user_id'])) {
        $reset_id=$_POST['reset_user_id']; $new_pass=$_POST['new_password'];
        if(!empty($new_pass)) {
            try { $hash=password_hash($new_pass,PASSWORD_BCRYPT);
                $conn->prepare("UPDATE usuarios SET password_hash=:p,debe_cambiar_password=1 WHERE id_usuario=:id")->execute([':p'=>$hash,':id'=>$reset_id]);
                $message="Contraseña restablecida. El usuario debe cambiarla al próximo login."; $message_type='success';
            } catch(PDOException $e){ $message="Error: ".$e->getMessage(); $message_type='danger'; }
        }
    }
    if (isset($_POST['change_role_id'])) {
        try { $conn->prepare("UPDATE usuarios SET id_rol=:r WHERE id_usuario=:id")->execute([':r'=>$_POST['new_role'],':id'=>$_POST['change_role_id']]);
            $message="Rol actualizado."; $message_type='success';
        } catch(PDOException $e){ $message="Error: ".$e->getMessage(); $message_type='danger'; }
    }
    if (isset($_POST['toggle_active_id'])) {
        try { $ns=intval($_POST['new_status']);
            $conn->prepare("UPDATE usuarios SET activo=:a WHERE id_usuario=:id")->execute([':a'=>$ns,':id'=>$_POST['toggle_active_id']]);
            $message=$ns==1?"Usuario activado.":"Usuario suspendido."; $message_type=$ns==1?'success':'warning';
        } catch(PDOException $e){ $message="Error: ".$e->getMessage(); $message_type='danger'; }
    }
    if (isset($_POST['delete_user_id'])) {
        try { $conn->prepare("DELETE FROM usuarios WHERE id_usuario=:id")->execute([':id'=>$_POST['delete_user_id']]);
            $message="Usuario eliminado permanentemente."; $message_type='success';
        } catch(PDOException $e){ $message="Error: ".$e->getMessage(); $message_type='danger'; }
    }
}

$users = $conn->query("SELECT
        u.id_usuario,
        COALESCE(NULLIF(TRIM(u.username), ''), CONCAT('Usuario #', u.id_usuario)) AS username_display,
        u.username,
        u.discord_user,
        u.activo,
        u.avatar_url,
        COALESCE(r.nombre_rol, 'Sin rol') AS nombre_rol,
        u.id_rol,
        p.nombre_pais
    FROM usuarios u
    LEFT JOIN roles r ON u.id_rol = r.id_rol
    LEFT JOIN paises p ON u.id_pais = p.id_pais
    ORDER BY COALESCE(u.id_rol, 99), u.id_usuario")->fetchAll(PDO::FETCH_ASSOC);
$roles = $conn->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Gestión de Usuarios';
require_once 'includes/header.php';

$rolBadge = ['Administrador'=>'danger','Game Master'=>'warning','Participante'=>'primary'];
$rolIcon  = ['Administrador'=>'shield-lock','Game Master'=>'joystick','Participante'=>'person'];
?>

<div class="container-fluid px-3 px-lg-4 py-4">

    <!-- PAGE HERO -->
    <div class="card flag-glow mb-4" style="border-radius:20px;overflow:hidden">
        <div style="background:linear-gradient(135deg,#6610f2,#d63384);color:#fff;padding:2rem 2.5rem;position:relative;overflow:hidden">
            <div style="position:absolute;top:-60px;right:-60px;width:220px;height:220px;background:rgba(255,255,255,.07);border-radius:50%;filter:blur(30px)"></div>
            <div class="d-flex align-items-center gap-3">
                <div style="width:56px;height:56px;background:rgba(255,255,255,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;backdrop-filter:blur(8px)">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <h1 class="mb-0" style="font-size:1.6rem;font-weight:800">Gestión de Usuarios</h1>
                    <p class="mb-0 mt-1" style="opacity:.8;font-size:.875rem"><?= count($users) ?> usuarios registrados</p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show mb-4">
        <?= $message ?><button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- CREAR USUARIO -->
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2"
             style="background:linear-gradient(135deg,rgba(25,135,84,.15),rgba(25,135,84,.05))">
            <i class="bi bi-person-plus-fill text-success"></i>
            <strong>Crear Nuevo Usuario</strong>
        </div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-500">Usuario <span class="text-danger">*</span></label>
                    <input type="text" name="new_username" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-500">Contraseña <span class="text-danger">*</span></label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-500">Discord</label>
                    <input type="text" name="new_discord" class="form-control" placeholder="usuario#1234">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-500">Rol <span class="text-danger">*</span></label>
                    <select name="new_role" class="form-select" required>
                        <?php foreach($roles as $r): ?>
                        <option value="<?= $r['id_rol'] ?>"><?= htmlspecialchars($r['nombre_rol']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" name="create_user" class="btn btn-success w-100">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLA DE USUARIOS -->
    <div class="card">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-table"></i>
            <strong>Listado de Usuarios</strong>
            <span class="badge ms-auto" style="background:var(--glass-bg);color:var(--text-primary);border:1px solid var(--border-color)">
                <?= count($users) ?>
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle" style="min-width:900px">
                    <thead>
                        <tr>
                            <th style="width:44px">#</th>
                            <th>Usuario</th>
                            <th>Discord</th>
                            <th>Rol</th>
                            <th>País</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($users as $user):
                        $suspended = $user['activo'] == 0;
                        $rName = $user['nombre_rol'];
                        $rColor = $rolBadge[$rName] ?? 'secondary';
                        $displayUsername = $user['username_display'];
                        $displayDiscord = trim((string)($user['discord_user'] ?? ''));
                    ?>
                    <tr style="<?= $suspended ? 'opacity:.55' : '' ?>">
                        <td class="text-muted" style="font-size:.8rem">#<?= $user['id_usuario'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?= getAvatarHtml($user['avatar_url'] ?? '', $displayUsername, 32) ?>
                                <div>
                                    <strong><?= htmlspecialchars($displayUsername) ?></strong>
                                    <div class="text-muted" style="font-size:.72rem">ID <?= $user['id_usuario'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--text-secondary);font-size:.875rem">
                            <?= $displayDiscord !== '' ? '<i class="bi bi-discord me-1"></i>'.htmlspecialchars($displayDiscord) : '<em class="text-muted">—</em>' ?>
                        </td>
                        <td>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="change_role_id" value="<?= $user['id_usuario'] ?>">
                                <select name="new_role" class="form-select form-select-sm" style="min-width:140px"
                                        onchange="this.form.submit()">
                                    <option value="" <?= empty($user['id_rol']) ? 'selected' : '' ?>>Sin rol</option>
                                    <?php foreach($roles as $r): ?>
                                    <option value="<?= $r['id_rol'] ?>" <?= $r['id_rol']==$user['id_rol']?'selected':'' ?>>
                                        <?= htmlspecialchars($r['nombre_rol']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td style="font-size:.875rem">
                            <?= $user['nombre_pais'] ? '<i class="bi bi-flag me-1"></i>'.htmlspecialchars($user['nombre_pais']) : '<em class="text-muted">Sin asignar</em>' ?>
                        </td>
                        <td>
                            <?php if($user['activo']==1): ?>
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Activo</span>
                            <?php else: ?>
                            <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Suspendido</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <!-- Reset Password -->
                                <form method="post" class="d-inline"
                                      onsubmit="return confirm('¿Restablecer contraseña?')">
                                    <input type="hidden" name="reset_user_id" value="<?= $user['id_usuario'] ?>">
                                    <div class="input-group input-group-sm" style="min-width:180px">
                                        <input type="text" name="new_password" class="form-control form-control-sm"
                                               placeholder="Clave temporal" required minlength="6">
                                        <button type="submit" class="btn btn-warning btn-sm" title="Resetear password">
                                            <i class="bi bi-key"></i>
                                        </button>
                                    </div>
                                </form>
                                <!-- Toggle Active -->
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="toggle_active_id" value="<?= $user['id_usuario'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $user['activo']==1?0:1 ?>">
                                    <button type="submit" class="btn btn-sm <?= $user['activo']==1?'btn-outline-secondary':'btn-outline-success' ?>">
                                        <i class="bi bi-<?= $user['activo']==1?'pause-circle':'play-circle' ?>"></i>
                                        <?= $user['activo']==1?'Suspender':'Activar' ?>
                                    </button>
                                </form>
                                <!-- Delete -->
                                <form method="post" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar permanentemente a <?= addslashes($displayUsername) ?>?')">
                                    <input type="hidden" name="delete_user_id" value="<?= $user['id_usuario'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<?php require_once 'includes/footer.php'; ?>
