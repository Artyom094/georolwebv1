<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['id_rol'] != 1) {
    header("Location: index.php"); exit;
}
require_once 'config/db.php';
require_once 'includes/functions.php';

$msg = ''; $msg_type = 'success';

// ───── POST ─────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['save_cartilla'])) {
        $id_pais = intval($_POST['id_pais']);
        try {
            $upsert = $conn->prepare("
                INSERT INTO cartilla_valores (id_pais, id_tipo, cantidad)
                VALUES (:id_pais, :id_tipo, :cantidad)
                ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad)
            ");
            if (isset($_POST['tipo']) && is_array($_POST['tipo'])) {
                foreach ($_POST['tipo'] as $id_tipo => $cantidad) {
                    $upsert->execute([
                        ':id_pais'  => $id_pais,
                        ':id_tipo'  => intval($id_tipo),
                        ':cantidad' => intval($cantidad)
                    ]);
                }
            }
            saveCartillaHistorySnapshot($conn, $id_pais, $_SESSION['user_id'], 'guardado');
            $msg = "✅ Cartilla guardada.";
        } catch (PDOException $e) {
            $msg = "❌ Error: " . $e->getMessage(); $msg_type = 'danger';
        }
    }

    if (isset($_POST['reset_cartilla'])) {
        $id_pais = intval($_POST['id_pais_reset']);
        try {
            $conn->prepare("UPDATE cartilla_valores SET cantidad=0 WHERE id_pais=?")
                 ->execute([$id_pais]);
            saveCartillaHistorySnapshot($conn, $id_pais, $_SESSION['user_id'], 'reinicio');
            $msg = "🔄 Cartilla reiniciada a cero."; $msg_type = 'warning';
        } catch (PDOException $e) {
            $msg = "❌ " . $e->getMessage(); $msg_type = 'danger';
        }
    }
}

// ───── GET: cargar tipos activos ─────
$tipos_raw = $conn->query("
    SELECT ct.*, cc.nombre as cat_nombre, cc.color as cat_color,
           cc.icono as cat_icono, cc.orden as cat_orden, cc.id_categoria
    FROM cartilla_tipos ct
    JOIN cartilla_categorias cc ON ct.id_categoria = cc.id_categoria
    WHERE ct.activo = 1
    ORDER BY cc.orden, ct.orden, ct.nombre
")->fetchAll(PDO::FETCH_ASSOC);

// Agrupar tipos por categoría
$categorias_map = [];
foreach ($tipos_raw as $t) {
    $cid = $t['id_categoria'];
    if (!isset($categorias_map[$cid])) {
        $categorias_map[$cid] = [
            'nombre' => $t['cat_nombre'],
            'color'  => $t['cat_color'],
            'icono'  => $t['cat_icono'],
            'tipos'  => []
        ];
    }
    $categorias_map[$cid]['tipos'][] = $t;
}

// Cargar todos los países con sus valores
$paises = $conn->query("
    SELECT p.id_pais, p.nombre_pais, p.bandera_url, p.turno_actual
    FROM paises p ORDER BY p.nombre_pais
")->fetchAll(PDO::FETCH_ASSOC);

// Cargar valores para todos los países de una vez
$valores_raw = $conn->query("
    SELECT id_pais, id_tipo, cantidad FROM cartilla_valores
")->fetchAll(PDO::FETCH_ASSOC);

$valores = []; // [id_pais][id_tipo] = cantidad
foreach ($valores_raw as $v) {
    $valores[$v['id_pais']][$v['id_tipo']] = $v['cantidad'];
}

$page_title = 'Gestión de Cartillas';
require_once 'includes/header.php';
?>

<style>
.campo-row { display:grid; grid-template-columns:1fr 130px; gap:.5rem;
             align-items:center; padding:.3rem 0; border-bottom:1px solid var(--border-color); }
.campo-row:last-child { border-bottom:none; }
.campo-label { font-size:.82rem; color:var(--text-secondary); }
.campo-input { font-size:.85rem; text-align:right; padding:.25rem .5rem; height:32px; }
.sec-header { font-size:.72rem; font-weight:700; text-transform:uppercase;
              letter-spacing:.05em; padding:.4rem .75rem; opacity:.8; }
</style>

<div class="container-fluid py-4">

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0"><i class="bi bi-clipboard2-data-fill"></i> Cartillas</h2>
        <p class="text-muted mb-0">Edita industrias y unidades de todos los países</p>
    </div>
    <div class="d-flex gap-2">
        <a href="admin_cartilla_historial.php" class="btn btn-outline-primary">
            <i class="bi bi-clock-history"></i> Historial
        </a>
        <a href="admin_cartilla_tipos.php" class="btn btn-outline-dark">
            <i class="bi bi-sliders"></i> Gestionar Tipos
        </a>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>

<?php if($msg): ?>
<div class="alert alert-<?=$msg_type?> alert-dismissible fade show">
    <?=$msg?> <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if(empty($tipos_raw)): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    No hay tipos de unidad definidos aún.
    <a href="admin_cartilla_tipos.php" class="alert-link">Crea tipos aquí →</a>
</div>
<?php endif; ?>

<!-- Buscador -->
<div class="mb-3 d-flex gap-2 align-items-center">
    <input type="text" id="searchPais" class="form-control" style="max-width:300px"
           placeholder="🔍 Buscar país..." oninput="filtrarPaises(this.value)">
    <span class="text-muted small" id="cntVisible"><?=count($paises)?> países</span>
</div>

<!-- Grid de países -->
<div id="paisesGrid">
<?php foreach($paises as $p):
    $pv = $valores[$p['id_pais']] ?? [];

    // Calcular prod IM y mantenimiento total para badge rápido
    $prod_im = 0; $mant = 0;
    foreach ($tipos_raw as $t) {
        $cant = $pv[$t['id_tipo']] ?? 0;
        if ($t['tipo'] === 'produccion' && $t['unidad_produccion'] === 'IM')
            $prod_im += $cant * $t['multiplicador'];
        elseif ($t['tipo'] === 'mantenimiento')
            $mant += $cant * $t['multiplicador'];
    }
    $deficit = ($prod_im - $mant) < 0;
?>
<div class="card mb-2 pais-item" data-nombre="<?=strtolower(htmlspecialchars($p['nombre_pais']))?>">
    <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-1">
        <div class="d-flex align-items-center gap-2">
            <?php if(!empty($p['bandera_url'])): ?>
                <img src="<?=htmlspecialchars($p['bandera_url'])?>"
                     style="width:36px;height:24px;object-fit:cover" class="rounded border">
            <?php else: ?>
                <div class="bg-secondary rounded d-flex align-items-center justify-content-center"
                     style="width:36px;height:24px">
                    <i class="bi bi-flag text-white" style="font-size:.7rem"></i>
                </div>
            <?php endif; ?>
            <strong><?=htmlspecialchars($p['nombre_pais'])?></strong>
            <span class="badge bg-dark">T#<?=$p['turno_actual']?></span>
            <?php if($prod_im > 0 || $mant > 0): ?>
                <span class="badge <?=$deficit?'bg-danger':'bg-success'?>">
                    <?=$deficit?'DÉFICIT':'SUPERÁVIT'?>
                    (<?=($deficit?'':'+').number_format($prod_im-$mant)?> IM)
                </span>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-1">
            <form method="post" class="d-inline"
                  onsubmit="return confirm('¿Resetear todos los valores a 0?')">
                <input type="hidden" name="id_pais_reset" value="<?=$p['id_pais']?>">
                <button type="submit" name="reset_cartilla"
                        class="btn btn-sm btn-outline-warning" title="Resetear a cero">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </form>
            <button class="btn btn-sm btn-outline-primary"
                    data-bs-toggle="collapse"
                    data-bs-target="#edit<?=$p['id_pais']?>">
                <i class="bi bi-pencil-square"></i> Editar
            </button>
        </div>
    </div>

    <div class="collapse" id="edit<?=$p['id_pais']?>">
        <form method="post">
            <input type="hidden" name="id_pais" value="<?=$p['id_pais']?>">

            <!-- Balance rápido -->
            <?php $balance = $prod_im - $mant; $pct = $prod_im > 0 ? round(($mant/$prod_im)*100,1) : 0; ?>
            <div class="px-3 py-2 border-bottom">
                <div class="row g-1 text-center">
                    <div class="col"><div class="small text-muted">Prod. IM</div>
                        <strong class="text-success"><?=number_format($prod_im)?></strong></div>
                    <div class="col"><div class="small text-muted">Mantenimiento</div>
                        <strong class="text-warning"><?=number_format($mant)?></strong></div>
                    <div class="col"><div class="small text-muted">Balance</div>
                        <strong class="<?=$deficit?'text-danger':'text-success'?>">
                            <?=$deficit?'':'+' ?><?=number_format($balance)?></strong></div>
                    <div class="col"><div class="small text-muted">% Uso</div>
                        <span class="badge bg-<?=$pct>100?'danger':($pct>80?'warning text-dark':'info')?>">
                            <?=$pct?>%</span></div>
                </div>
            </div>

            <!-- Campos por categoría -->
            <div class="row g-0">
            <?php foreach($categorias_map as $cid => $cat): ?>
            <div class="col-xl col-lg-4 col-md-6 border-end">
                <div class="sec-header text-<?=$cat['color']?>">
                    <i class="bi bi-<?=$cat['icono']?>"></i> <?=htmlspecialchars($cat['nombre'])?>
                </div>
                <div class="px-3 pb-2">
                <?php foreach($cat['tipos'] as $t):
                    $cant = $pv[$t['id_tipo']] ?? 0;
                ?>
                    <div class="campo-row">
                        <label class="campo-label">
                            <?=htmlspecialchars($t['nombre'])?>
                            <?php if($t['tipo']==='mantenimiento' && $t['multiplicador'] > 0): ?>
                                <span class="badge bg-warning text-dark" style="font-size:.65rem">
                                    <?=number_format($t['multiplicador'],0)?> IM
                                </span>
                            <?php elseif($t['tipo']==='produccion'): ?>
                                <span class="badge bg-info" style="font-size:.65rem">
                                    ×<?=number_format($t['multiplicador'],1)?> <?=$t['unidad_produccion']?>
                                </span>
                            <?php endif; ?>
                        </label>
                        <input type="number"
                               name="tipo[<?=$t['id_tipo']?>]"
                               class="form-control campo-input"
                               value="<?=intval($cant)?>"
                               min="-9999999" step="1">
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            </div>

            <div class="px-3 py-2 border-top d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="collapse" data-bs-target="#edit<?=$p['id_pais']?>">
                    Cancelar
                </button>
                <button type="submit" name="save_cartilla" class="btn btn-sm btn-primary">
                    <i class="bi bi-save"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
</div>
</div>

<script>
function filtrarPaises(q){
    q=q.toLowerCase().trim();
    const items=document.querySelectorAll('.pais-item');
    let v=0;
    items.forEach(el=>{
        const show=(el.dataset.nombre||'').includes(q);
        el.style.display=show?'':'none';
        if(show)v++;
    });
    document.getElementById('cntVisible').textContent=v+' países';
}
</script>

<?php require_once 'includes/footer.php'; ?>
