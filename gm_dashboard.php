<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['id_rol'], [1, 2])) { header("Location: index.php"); exit; }
require_once 'config/db.php';
require_once 'includes/functions.php';

$message = ''; $message_type = 'info';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'assign_country') {
        $u_id=$_POST['user_id']; $p_id=$_POST['country_id'];
        try {
            archiveCountryToHistory($conn, $u_id, 'Reasignación por GM');
            if ($p_id == 'new') {
                $p_name = trim($_POST['new_country_name']);
                if (!empty($p_name)) {
                    $conn->prepare("INSERT INTO paises (nombre_pais) VALUES (:n)")->execute([':n'=>$p_name]);
                    $p_id = $conn->lastInsertId();
                    $conn->exec("INSERT INTO cartillas (id_pais) VALUES ($p_id)");
                } else throw new Exception("Nombre de país vacío");
            }
            $conn->prepare("UPDATE usuarios SET id_pais=:pid WHERE id_usuario=:uid")->execute([':pid'=>$p_id,':uid'=>$u_id]);
            $message="País asignado correctamente."; $message_type='success';
        } catch(Exception $e){ $message="Error: ".$e->getMessage(); $message_type='danger'; }
    }
    if ($_POST['action'] == 'remove_country') {
        $u_id=$_POST['user_id'];
        archiveCountryToHistory($conn, $u_id, 'País removido por GM');
        $conn->prepare("UPDATE usuarios SET id_pais=NULL WHERE id_usuario=:uid")->execute([':uid'=>$u_id]);
        $message="País desasignado."; $message_type='warning';
    }
}

$participants = $conn->query("SELECT u.id_usuario,u.username,p.nombre_pais,p.id_pais,r.nombre_rol
    FROM usuarios u LEFT JOIN paises p ON u.id_pais=p.id_pais LEFT JOIN roles r ON u.id_rol=r.id_rol
    ORDER BY u.id_rol,u.username")->fetchAll(PDO::FETCH_ASSOC);
$countries = $conn->query("SELECT * FROM paises ORDER BY nombre_pais")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Panel GM — Asignación';
require_once 'includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">

    <!-- HERO -->
    <div class="card flag-glow mb-4" style="border-radius:20px;overflow:hidden">
        <div style="background:linear-gradient(135deg,#198754,#20c997);color:#fff;padding:2rem 2.5rem;position:relative;overflow:hidden">
            <div style="position:absolute;top:-50px;right:-50px;width:200px;height:200px;background:rgba(255,255,255,.08);border-radius:50%;filter:blur(25px)"></div>
            <div class="d-flex align-items-center gap-3 position-relative">
                <div style="width:56px;height:56px;background:rgba(255,255,255,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.7rem;backdrop-filter:blur(8px)">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <h1 class="mb-0" style="font-size:1.6rem;font-weight:800">Panel GM — Asignación</h1>
                    <p class="mb-0 mt-1" style="opacity:.8;font-size:.875rem"><?= count($participants) ?> usuarios en el sistema</p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show mb-4">
        <?= $message ?><button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- TABLA -->
    <div class="card">
        <div class="card-header"><i class="bi bi-person-lines-fill me-2"></i><strong>Usuarios y Países</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="min-width:700px">
                    <thead>
                        <tr><th>Usuario</th><th>Rol</th><th>País Actual</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach($participants as $user):
                        $rolColors=['Administrador'=>'danger','Game Master'=>'warning','Participante'=>'primary'];
                        $rColor=$rolColors[$user['nombre_rol']]??'secondary';
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#198754,#20c997);display:flex;align-items:center;justify-content:center;font-size:.8rem;color:#fff;font-weight:700">
                                    <?= strtoupper(substr($user['username'],0,1)) ?>
                                </div>
                                <strong><?= htmlspecialchars($user['username']) ?></strong>
                            </div>
                        </td>
                        <td><span class="badge bg-<?= $rColor ?>"><?= htmlspecialchars($user['nombre_rol']) ?></span></td>
                        <td>
                            <?php if ($user['nombre_pais']): ?>
                                <span class="badge" style="background:var(--glass-bg);color:var(--text-primary);border:1px solid var(--border-color)">
                                    <i class="bi bi-flag me-1"></i><?= htmlspecialchars($user['nombre_pais']) ?>
                                </span>
                            <?php else: ?>
                                <em class="text-muted" style="font-size:.875rem">Sin asignar</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" class="d-flex gap-2 flex-wrap align-items-center">
                                <input type="hidden" name="action" value="assign_country">
                                <input type="hidden" name="user_id" value="<?= $user['id_usuario'] ?>">
                                <select name="country_id" class="form-select form-select-sm" style="min-width:160px">
                                    <option value="new">— Nuevo País —</option>
                                    <?php foreach($countries as $c): ?>
                                    <option value="<?= $c['id_pais'] ?>" <?= $c['id_pais']==$user['id_pais']?'selected':'' ?>>
                                        <?= htmlspecialchars($c['nombre_pais']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="new_country_name" class="form-control form-control-sm"
                                       placeholder="Nombre (si es nuevo)" style="max-width:160px">
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="bi bi-check-circle me-1"></i>Asignar
                                </button>
                                <?php if ($user['id_pais']): ?>
                                <a href="view_country.php?id=<?= $user['id_pais'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php endif; ?>
                            </form>
                            <?php if ($user['id_pais']): ?>
                            <form method="post" class="mt-1">
                                <input type="hidden" name="action" value="remove_country">
                                <input type="hidden" name="user_id" value="<?= $user['id_usuario'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('¿Desasignar país de <?= addslashes($user['username']) ?>?')">
                                    <i class="bi bi-x-circle me-1"></i>Quitar País
                                </button>
                            </form>
                            <?php endif; ?>
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
