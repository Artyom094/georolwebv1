<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$id_pais = $_SESSION['id_pais'] ?? null;
$is_admin_or_gm = in_array($_SESSION['id_rol'], [1, 2]);

if (!$id_pais && !$is_admin_or_gm) { header("Location: index.php"); exit; }

$turno_global = $conn->query("SELECT turno_actual FROM turno_global WHERE id=1")->fetchColumn() ?? 1;
$message = ''; $message_type = 'success';

if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['cambiar_enfoque']) && $id_pais) {
    $id_enfoque_nuevo = intval($_POST['id_enfoque']);
    try {
        $stmt=$conn->prepare("SELECT id_enfoque_activo,fecha_ultimo_cambio_enfoque,turno_actual FROM paises WHERE id_pais=:id");
        $stmt->execute([':id'=>$id_pais]);
        $pais_data=$stmt->fetch(PDO::FETCH_ASSOC);
        $conn->beginTransaction();
        $conn->prepare("INSERT INTO historial_enfoques(id_pais,id_enfoque_anterior,id_enfoque_nuevo,turno_cambio) VALUES(:p,:a,:n,:t)")
             ->execute([':p'=>$id_pais,':a'=>$pais_data['id_enfoque_activo'],':n'=>$id_enfoque_nuevo,':t'=>$turno_global]);
        $conn->prepare("UPDATE paises SET id_enfoque_activo=:e,fecha_ultimo_cambio_enfoque=NOW(),guerras_ofensivas_consecutivas=0 WHERE id_pais=:id")
             ->execute([':e'=>$id_enfoque_nuevo,':id'=>$id_pais]);
        $conn->commit();
        $message="Enfoque cambiado exitosamente. No puedes declarar guerras este turno.";
    } catch(Exception $e) {
        if($conn->inTransaction()) $conn->rollBack();
        $message="Error: ".$e->getMessage(); $message_type='danger';
    }
}

$enfoques=$conn->query("SELECT * FROM enfoques WHERE activo=1 ORDER BY tipo_enfoque,nombre_enfoque")->fetchAll(PDO::FETCH_ASSOC);

$enfoque_actual=null;
if ($id_pais) {
    $stmt=$conn->prepare("SELECT p.*,e.nombre_enfoque,e.tipo_enfoque,e.descripcion,e.multiplicador_ic,e.multiplicador_im,e.multiplicador_it,e.bonus_defensa,e.cooldown_guerra_reducido FROM paises p LEFT JOIN enfoques e ON p.id_enfoque_activo=e.id_enfoque WHERE p.id_pais=:id");
    $stmt->execute([':id'=>$id_pais]);
    $enfoque_actual=$stmt->fetch(PDO::FETCH_ASSOC);
}

$historial=[];
if ($id_pais) {
    $hs=$conn->prepare("SELECT h.*,ea.nombre_enfoque as enfoque_anterior_nombre,en.nombre_enfoque as enfoque_nuevo_nombre FROM historial_enfoques h LEFT JOIN enfoques ea ON h.id_enfoque_anterior=ea.id_enfoque LEFT JOIN enfoques en ON h.id_enfoque_nuevo=en.id_enfoque WHERE h.id_pais=:id ORDER BY h.fecha_cambio DESC LIMIT 10");
    $hs->execute([':id'=>$id_pais]);
    $historial=$hs->fetchAll(PDO::FETCH_ASSOC);
}

$tipo_config=[
    'Comunismo'      => ['icon' => 'people-fill',      'grad' => '#c62828,#ef5350'],
    'Fascismo'       => ['icon' => 'flame-fill',       'grad' => '#4e342e,#d84315'],
    'Democracia'     => ['icon' => 'people',           'grad' => '#0d6efd,#20c997'],
    'Monarquía'      => ['icon' => 'crown',            'grad' => '#6f42c1,#fd7e14'],
    'Teocracia'      => ['icon' => 'star-fill',        'grad' => '#198754,#0dcaf0'],
    'Socialismo'     => ['icon' => 'kanban-fill',      'grad' => '#dc3545,#ff8f00'],
    'Liberalismo'    => ['icon' => 'graph-up-arrow',   'grad' => '#0d6efd,#6610f2'],
    'Anarquismo'     => ['icon' => 'exclamation-octagon-fill', 'grad' => '#212529,#6c757d'],
    'Autoritarismo'  => ['icon' => 'shield-lock-fill',  'grad' => '#343a40,#495057'],
    'Oligarquía'     => ['icon' => 'gem',               'grad' => '#795548,#d4a373'],
];

$page_title='Enfoques Nacionales';
require_once 'includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">

    <!-- HERO -->
    <div class="card flag-glow mb-4" style="border-radius:20px;overflow:hidden">
        <div style="background:linear-gradient(135deg,#6610f2,#0d6efd);color:#fff;padding:2rem 2.5rem;position:relative;overflow:hidden">
            <div style="position:absolute;top:-60px;right:-60px;width:230px;height:230px;background:rgba(255,255,255,.07);border-radius:50%;filter:blur(30px)"></div>
            <div class="d-flex align-items-center gap-3 position-relative">
                <div style="width:56px;height:56px;background:rgba(255,255,255,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.7rem;backdrop-filter:blur(8px)">
                    <i class="bi bi-crosshair2"></i>
                </div>
                <div class="flex-grow-1">
                    <h1 class="mb-0" style="font-size:1.6rem;font-weight:800">Enfoques Nacionales</h1>
                    <p class="mb-0 mt-1" style="opacity:.8;font-size:.875rem">Define la ideología de tu nación y sus efectos estratégicos</p>
                </div>
                <div class="text-end d-none d-md-block">
                    <div style="font-size:.75rem;opacity:.7">Turno Global</div>
                    <div style="font-size:2.2rem;font-weight:800;line-height:1">#<?= $turno_global ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show mb-4">
        <?= $message ?><button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- ENFOQUE ACTUAL -->
    <?php if ($enfoque_actual && $enfoque_actual['id_enfoque_activo']):
        $tipo=$enfoque_actual['tipo_enfoque'];
        $cfg=$tipo_config[$tipo]??['icon'=>'crosshair','grad'=>'#6c757d,#495057'];
    ?>
    <div class="card mb-4" style="border-radius:16px;overflow:hidden">
        <div style="background:linear-gradient(135deg,<?= $cfg['grad'] ?>);color:#fff;padding:1.5rem 2rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
            <i class="bi bi-<?= $cfg['icon'] ?>" style="font-size:2rem"></i>
            <div class="flex-grow-1">
                <div style="font-size:.75rem;opacity:.8;text-transform:uppercase;letter-spacing:.08em">Enfoque Activo</div>
                <h3 class="mb-0" style="font-weight:800"><?= htmlspecialchars($enfoque_actual['nombre_enfoque']) ?></h3>
                    <span style="opacity:.85;font-size:.875rem"><?= htmlspecialchars($tipo) ?></span>
            </div>
            <?php if ($enfoque_actual['fecha_ultimo_cambio_enfoque']): ?>
            <div class="text-end">
                <div style="font-size:.75rem;opacity:.7">Último cambio</div>
                <strong style="font-size:.875rem"><?= date('d/m/Y H:i', strtotime($enfoque_actual['fecha_ultimo_cambio_enfoque'])) ?></strong>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <p class="mb-3"><?= htmlspecialchars($enfoque_actual['descripcion']) ?></p>
            <div class="d-flex gap-2 flex-wrap">
                <?php if ($enfoque_actual['multiplicador_ic']): ?><span class="badge bg-warning text-dark">IC ×<?= $enfoque_actual['multiplicador_ic'] ?></span><?php endif; ?>
                <?php if ($enfoque_actual['multiplicador_im']): ?><span class="badge bg-secondary">IM ×<?= $enfoque_actual['multiplicador_im'] ?></span><?php endif; ?>
                <?php if ($enfoque_actual['multiplicador_it']): ?><span class="badge bg-primary">IT ×<?= $enfoque_actual['multiplicador_it'] ?></span><?php endif; ?>
                <?php if ($enfoque_actual['bonus_defensa']>0): ?><span class="badge bg-info">+<?= $enfoque_actual['bonus_defensa'] ?> Defensa</span><?php endif; ?>
                <?php if ($enfoque_actual['cooldown_guerra_reducido']): ?><span class="badge bg-danger">Cooldown Reducido</span><?php endif; ?>
            </div>
        </div>
    </div>
    <?php elseif ($enfoque_actual): ?>
    <div class="alert alert-warning mb-4">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Sin enfoque activo.</strong> Selecciona uno para comenzar a jugar.
    </div>
    <?php endif; ?>

    <!-- GRID DE ENFOQUES -->
    <h2 class="mb-3" style="font-size:1.05rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.06em">
        <i class="bi bi-grid-3x3 me-2"></i>Enfoques Disponibles
    </h2>
    <div class="row g-3 mb-4">
    <?php foreach ($enfoques as $enfoque):
        $tipo=$enfoque['tipo_enfoque'];
        $cfg=$tipo_config[$tipo]??['icon'=>'crosshair','grad'=>'#6c757d,#495057'];
        $is_active=$enfoque_actual && $enfoque_actual['id_enfoque_activo']==$enfoque['id_enfoque'];
    ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 <?= $is_active?'flag-glow':'' ?>"
             style="border-radius:14px;overflow:hidden;<?= $is_active?'border:2px solid var(--accent)':'' ?>">
            <div style="background:linear-gradient(135deg,<?= $cfg['grad'] ?>);color:#fff;padding:1rem 1.25rem;display:flex;align-items:center;gap:.75rem">
                <i class="bi bi-<?= $cfg['icon'] ?>" style="font-size:1.4rem;opacity:.9"></i>
                <div class="flex-grow-1">
                    <div style="font-weight:700;font-size:.95rem"><?= htmlspecialchars($enfoque['nombre_enfoque']) ?></div>
                    <div style="font-size:.75rem;opacity:.8"><?= htmlspecialchars($tipo) ?></div>
                </div>
                <?php if ($is_active): ?><i class="bi bi-check-circle-fill" style="font-size:1.2rem"></i><?php endif; ?>
            </div>
            <div class="card-body d-flex flex-column">
                <p class="small mb-3" style="color:var(--text-secondary)"><?= htmlspecialchars($enfoque['descripcion']) ?></p>
                <div class="d-flex flex-wrap gap-1 mb-3">
                    <?php if ($enfoque['multiplicador_ic']): ?><span class="badge bg-warning text-dark">IC ×<?= $enfoque['multiplicador_ic'] ?></span><?php endif; ?>
                    <?php if ($enfoque['multiplicador_im']): ?><span class="badge bg-secondary">IM ×<?= $enfoque['multiplicador_im'] ?></span><?php endif; ?>
                    <?php if ($enfoque['multiplicador_it']): ?><span class="badge bg-primary">IT ×<?= $enfoque['multiplicador_it'] ?></span><?php endif; ?>
                    <?php if ($enfoque['bonus_defensa']>0): ?><span class="badge bg-info">+<?= $enfoque['bonus_defensa'] ?> Def</span><?php endif; ?>
                    <?php if ($enfoque['cooldown_guerra_reducido']): ?><span class="badge bg-danger" style="font-size:.65rem">CD Reducido</span><?php endif; ?>
                </div>
                <div class="mt-auto">
                    <?php if ($is_active): ?>
                    <div class="text-center py-2" style="color:var(--accent);font-weight:600;font-size:.875rem">
                        <i class="bi bi-check-circle-fill me-1"></i>Enfoque Activo
                    </div>
                    <?php elseif ($id_pais): ?>
                    <form method="post" onsubmit="return confirm('¿Cambiar a «<?= addslashes($enfoque['nombre_enfoque']) ?>»? No podrás declarar guerras 1 turno.')">
                        <input type="hidden" name="id_enfoque" value="<?= $enfoque['id_enfoque'] ?>">
                        <button type="submit" name="cambiar_enfoque" class="btn btn-sm w-100"
                                style="background:linear-gradient(135deg,<?= $cfg['grad'] ?>);color:#fff;border:none;border-radius:8px;font-weight:600">
                            <i class="bi bi-arrow-right-circle me-1"></i>Seleccionar
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- HISTORIAL -->
    <?php if (!empty($historial)): ?>
    <div class="card">
        <div class="card-header"><i class="bi bi-clock-history me-2"></i><strong>Historial de Cambios</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Turno</th><th>Fecha</th><th>Anterior</th><th>Nuevo</th></tr></thead>
                    <tbody>
                    <?php foreach ($historial as $h): ?>
                    <tr>
                        <td><span class="badge bg-dark">#<?= $h['turno_cambio'] ?></span></td>
                        <td style="font-size:.8rem;color:var(--text-secondary)"><?= date('d/m/Y H:i', strtotime($h['fecha_cambio'])) ?></td>
                        <td style="font-size:.875rem;color:var(--text-secondary)"><?= $h['enfoque_anterior_nombre']?htmlspecialchars($h['enfoque_anterior_nombre']):'<em>Sin enfoque</em>' ?></td>
                        <td><strong><?= htmlspecialchars($h['enfoque_nuevo_nombre']) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php require_once 'includes/footer.php'; ?>
