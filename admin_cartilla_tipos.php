<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['id_rol'] != 1) {
    header("Location: index.php"); exit;
}
require_once 'config/db.php';

$msg = ''; $msg_type = 'success';

// ───── POST ─────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Categorías ──
    if (isset($_POST['add_cat'])) {
        $n = trim($_POST['cat_nombre']); $ic = trim($_POST['cat_icono']) ?: 'box';
        $co = trim($_POST['cat_color']) ?: 'secondary'; $or = intval($_POST['cat_orden']);
        if ($n) {
            try { $conn->prepare("INSERT INTO cartilla_categorias (nombre,icono,color,orden) VALUES (?,?,?,?)")
                      ->execute([$n,$ic,$co,$or]);
                $msg = "Categoría creada.";
            } catch(PDOException $e) { $msg = "Error: ".$e->getMessage(); $msg_type='danger'; }
        }
    }
    if (isset($_POST['edit_cat'])) {
        $id=intval($_POST['id_cat']); $n=trim($_POST['cat_nombre']);
        $ic=trim($_POST['cat_icono']); $co=trim($_POST['cat_color']); $or=intval($_POST['cat_orden']);
        try { $conn->prepare("UPDATE cartilla_categorias SET nombre=?,icono=?,color=?,orden=? WHERE id_categoria=?")
                  ->execute([$n,$ic,$co,$or,$id]);
            $msg="Categoría actualizada.";
        } catch(PDOException $e) { $msg="Error: ".$e->getMessage(); $msg_type='danger'; }
    }
    if (isset($_POST['del_cat'])) {
        $id=intval($_POST['id_cat']);
        try { $conn->prepare("DELETE FROM cartilla_categorias WHERE id_categoria=?")->execute([$id]);
            $msg="Categoría eliminada.";
        } catch(PDOException $e) { $msg="Error: ".$e->getMessage(); $msg_type='danger'; }
    }

    // ── Tipos ──
    if (isset($_POST['add_tipo'])) {
        $n=trim($_POST['tipo_nombre']); $idc=intval($_POST['tipo_categoria']);
        $t=$_POST['tipo_tipo']; $m=floatval($_POST['tipo_mult']);
        $up=trim($_POST['tipo_unidad']) ?: null; $or=intval($_POST['tipo_orden']);
        if ($n && $idc) {
            try { $conn->prepare("INSERT INTO cartilla_tipos (id_categoria,nombre,tipo,multiplicador,unidad_produccion,orden) VALUES (?,?,?,?,?,?)")
                      ->execute([$idc,$n,$t,$m,$up,$or]);
                $msg="Tipo creado.";
            } catch(PDOException $e) { $msg="Error: ".$e->getMessage(); $msg_type='danger'; }
        }
    }
    if (isset($_POST['edit_tipo'])) {
        $id=intval($_POST['id_tipo']); $n=trim($_POST['tipo_nombre']);
        $idc=intval($_POST['tipo_categoria']); $t=$_POST['tipo_tipo'];
        $m=floatval($_POST['tipo_mult']); $up=trim($_POST['tipo_unidad']) ?: null;
        $or=intval($_POST['tipo_orden']); $ac=isset($_POST['tipo_activo'])?1:0;
        try { $conn->prepare("UPDATE cartilla_tipos SET id_categoria=?,nombre=?,tipo=?,multiplicador=?,unidad_produccion=?,orden=?,activo=? WHERE id_tipo=?")
                  ->execute([$idc,$n,$t,$m,$up,$or,$ac,$id]);
            $msg="Tipo actualizado.";
        } catch(PDOException $e) { $msg="Error: ".$e->getMessage(); $msg_type='danger'; }
    }
    if (isset($_POST['del_tipo'])) {
        $id=intval($_POST['id_tipo']);
        try { $conn->prepare("DELETE FROM cartilla_tipos WHERE id_tipo=?")->execute([$id]);
            $msg="Tipo eliminado.";
        } catch(PDOException $e) { $msg="Error: ".$e->getMessage(); $msg_type='danger'; }
    }
    if (isset($_POST['toggle_tipo'])) {
        $id=intval($_POST['id_tipo']); $ac=intval($_POST['activo']);
        $conn->prepare("UPDATE cartilla_tipos SET activo=? WHERE id_tipo=?")->execute([$ac,$id]);
        $msg="Estado actualizado.";
    }
}

// ───── GET ─────
$categorias = $conn->query("SELECT * FROM cartilla_categorias ORDER BY orden,nombre")->fetchAll(PDO::FETCH_ASSOC);
$tipos = $conn->query("
    SELECT ct.*, cc.nombre as cat_nombre, cc.color as cat_color
    FROM cartilla_tipos ct
    JOIN cartilla_categorias cc ON ct.id_categoria = cc.id_categoria
    ORDER BY cc.orden, ct.orden, ct.nombre
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Gestión de Tipos de Cartilla';
require_once 'includes/header.php';

$colores = ['primary','success','info','warning','danger','secondary','dark'];
$cat_modals = [];
$tipo_modals = [];
?>

<div class="container-fluid py-4">
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0"><i class="bi bi-sliders"></i> Tipos de Cartilla</h2>
        <p class="text-muted mb-0">Define categorías y unidades disponibles para todos los países</p>
    </div>
    <div class="d-flex gap-2">
        <a href="admin_cartilla.php" class="btn btn-outline-primary"><i class="bi bi-clipboard2-data"></i> Ver Cartillas</a>
        <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
</div>

<?php if($msg): ?>
<div class="alert alert-<?=$msg_type?> alert-dismissible fade show">
    <?=$msg?> <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">

<!-- ═══ COLUMNA IZQUIERDA: CATEGORÍAS ═══ -->
<div class="col-lg-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-folder-fill"></i> Categorías</h5>
            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modalAddCat">
                <i class="bi bi-plus-lg"></i> Nueva
            </button>
        </div>
        <div class="list-group list-group-flush">
            <?php foreach($categorias as $cat): ?>
            <div class="list-group-item">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-<?=$cat['color']?> rounded-pill" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-<?=$cat['icono']?>"></i>
                    </span>
                    <div class="flex-grow-1">
                        <strong><?=htmlspecialchars($cat['nombre'])?></strong>
                        <small class="text-muted d-block">Orden: <?=$cat['orden']?></small>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-warning" data-bs-toggle="modal"
                                data-bs-target="#editCat<?=$cat['id_categoria']?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="post" class="d-inline"
                              onsubmit="return confirm('¿Eliminar esta categoría y TODOS sus tipos?')">
                            <input type="hidden" name="id_cat" value="<?=$cat['id_categoria']?>">
                            <button type="submit" name="del_cat" class="btn btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <?php ob_start(); ?>
            <div class="modal fade" id="editCat<?=$cat['id_categoria']?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="post">
                    <input type="hidden" name="id_cat" value="<?=$cat['id_categoria']?>">
                    <div class="modal-header"><h5 class="modal-title">Editar Categoría</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <?php include __DIR__.'/includes/_cat_form.php'; // reutilizamos ?>
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input name="cat_nombre" class="form-control" value="<?=htmlspecialchars($cat['nombre'])?>" required>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Ícono Bootstrap</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-<?=$cat['icono']?>" id="ic_prev_<?=$cat['id_categoria']?>"></i></span>
                                    <input name="cat_icono" class="form-control"
                                           value="<?=htmlspecialchars($cat['icono'])?>"
                                           oninput="document.getElementById('ic_prev_<?=$cat['id_categoria']?>').className='bi bi-'+this.value">
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Color</label>
                                <select name="cat_color" class="form-select">
                                    <?php foreach($colores as $c): ?>
                                    <option value="<?=$c?>" <?=$c==$cat['color']?'selected':''?>>
                                        <?=ucfirst($c)?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mt-2">
                            <label class="form-label">Orden</label>
                            <input type="number" name="cat_orden" class="form-control" value="<?=$cat['orden']?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="edit_cat" class="btn btn-warning">Guardar</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <?php $cat_modals[] = ob_get_clean(); ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ═══ COLUMNA DERECHA: TIPOS ═══ -->
<div class="col-lg-8">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Unidades e Industrias</h5>
            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modalAddTipo">
                <i class="bi bi-plus-lg"></i> Nuevo Tipo
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Tipo</th>
                        <th class="text-end">Valor</th>
                        <th>Unidad</th>
                        <th>Orden</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($tipos as $t): ?>
                    <tr class="<?=$t['activo']?'':'table-secondary opacity-50'?>">
                        <td><strong><?=htmlspecialchars($t['nombre'])?></strong></td>
                        <td><span class="badge bg-<?=$t['cat_color']?>"><?=htmlspecialchars($t['cat_nombre'])?></span></td>
                        <td>
                            <?php if($t['tipo']==='produccion'): ?>
                                <span class="badge bg-success"><i class="bi bi-arrow-up-circle"></i> Producción</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-tools"></i> Mantenimiento</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-bold"><?=number_format($t['multiplicador'],2)?></td>
                        <td><?=$t['unidad_produccion'] ? '<span class="badge bg-info">'.$t['unidad_produccion'].'</span>' : '<span class="text-muted">IM</span>'?></td>
                        <td><?=$t['orden']?></td>
                        <td class="text-center">
                            <form method="post" class="d-inline">
                                <input type="hidden" name="id_tipo" value="<?=$t['id_tipo']?>">
                                <input type="hidden" name="activo" value="<?=$t['activo']?0:1?>">
                                <button type="submit" name="toggle_tipo"
                                        class="btn btn-sm <?=$t['activo']?'btn-success':'btn-secondary'?>">
                                    <i class="bi bi-<?=$t['activo']?'check-circle':'x-circle'?>"></i>
                                </button>
                            </form>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-warning" data-bs-toggle="modal"
                                        data-bs-target="#editTipo<?=$t['id_tipo']?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar este tipo? Se perderán los valores de todos los países.')">
                                    <input type="hidden" name="id_tipo" value="<?=$t['id_tipo']?>">
                                    <button type="submit" name="del_tipo" class="btn btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <?php ob_start(); ?>
                    <div class="modal fade" id="editTipo<?=$t['id_tipo']?>" tabindex="-1">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <form method="post">
                            <input type="hidden" name="id_tipo" value="<?=$t['id_tipo']?>">
                            <div class="modal-header">
                                <h5 class="modal-title">Editar: <?=htmlspecialchars($t['nombre'])?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input name="tipo_nombre" class="form-control"
                                           value="<?=htmlspecialchars($t['nombre'])?>" required>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label">Categoría</label>
                                        <select name="tipo_categoria" class="form-select" required>
                                            <?php foreach($categorias as $cat): ?>
                                            <option value="<?=$cat['id_categoria']?>"
                                                <?=$cat['id_categoria']==$t['id_categoria']?'selected':''?>>
                                                <?=htmlspecialchars($cat['nombre'])?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Tipo</label>
                                        <select name="tipo_tipo" class="form-select" id="tipoSel<?=$t['id_tipo']?>"
                                                onchange="toggleUnidad(this,'<?=$t['id_tipo']?>')">
                                            <option value="mantenimiento" <?=$t['tipo']==='mantenimiento'?'selected':''?>>Mantenimiento</option>
                                            <option value="produccion"    <?=$t['tipo']==='produccion'?'selected':''?>>Producción</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-2 mt-1">
                                    <div class="col-6">
                                        <label class="form-label">
                                            <span id="multLabel<?=$t['id_tipo']?>">
                                                <?=$t['tipo']==='produccion'?'Producción por unidad (×)':'Costo por unidad (IM)'?>
                                            </span>
                                        </label>
                                        <input type="number" step="0.01" name="tipo_mult" class="form-control"
                                               value="<?=$t['multiplicador']?>" required>
                                    </div>
                                    <div class="col-6" id="unidadDiv<?=$t['id_tipo']?>"
                                         style="<?=$t['tipo']==='mantenimiento'?'display:none':''?>">
                                        <label class="form-label">Unidad producida</label>
                                        <input name="tipo_unidad" class="form-control" placeholder="IC, IM, IT, Energía…"
                                               value="<?=htmlspecialchars($t['unidad_produccion']??'')?>">
                                    </div>
                                </div>
                                <div class="row g-2 mt-1">
                                    <div class="col-6">
                                        <label class="form-label">Orden</label>
                                        <input type="number" name="tipo_orden" class="form-control" value="<?=$t['orden']?>">
                                    </div>
                                    <div class="col-6 d-flex align-items-end">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="tipo_activo"
                                                   id="ac<?=$t['id_tipo']?>" <?=$t['activo']?'checked':''?>>
                                            <label class="form-check-label" for="ac<?=$t['id_tipo']?>">Activo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" name="edit_tipo" class="btn btn-warning">Guardar</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                    <?php $tipo_modals[] = ob_get_clean(); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div><!-- /col -->
</div><!-- /row -->
</div><!-- /container -->

<?php foreach ($cat_modals as $modal) echo $modal; ?>
<?php foreach ($tipo_modals as $modal) echo $modal; ?>

<!-- ═══ Modal: Nueva Categoría ═══ -->
<div class="modal fade" id="modalAddCat" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header bg-dark text-white">
            <h5 class="modal-title"><i class="bi bi-folder-plus"></i> Nueva Categoría</h5>
            <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Nombre *</label>
                <input name="cat_nombre" class="form-control" placeholder="Ej: Industria Energética" required>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label">Ícono Bootstrap</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-box" id="ic_prev_new"></i></span>
                        <input name="cat_icono" class="form-control" placeholder="lightning-fill"
                               oninput="document.getElementById('ic_prev_new').className='bi bi-'+this.value">
                    </div>
                    <small class="text-muted"><a href="https://icons.getbootstrap.com" target="_blank">Ver íconos</a></small>
                </div>
                <div class="col-6">
                    <label class="form-label">Color</label>
                    <select name="cat_color" class="form-select">
                        <?php foreach($colores as $c): ?>
                        <option value="<?=$c?>"><?=ucfirst($c)?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-2">
                <label class="form-label">Orden</label>
                <input type="number" name="cat_orden" class="form-control" value="<?=count($categorias)+1?>">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" name="add_cat" class="btn btn-dark">Crear Categoría</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══ Modal: Nuevo Tipo ═══ -->
<div class="modal fade" id="modalAddTipo" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title"><i class="bi bi-plus-square"></i> Nuevo Tipo de Unidad/Industria</h5>
            <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Nombre *</label>
                <input name="tipo_nombre" class="form-control" placeholder="Ej: Planta Nuclear" required>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label">Categoría *</label>
                    <select name="tipo_categoria" class="form-select" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach($categorias as $cat): ?>
                        <option value="<?=$cat['id_categoria']?>"><?=htmlspecialchars($cat['nombre'])?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">Tipo *</label>
                    <select name="tipo_tipo" class="form-select" id="tipoSelNew"
                            onchange="toggleUnidad(this,'New')">
                        <option value="mantenimiento">Mantenimiento (consume IM)</option>
                        <option value="produccion">Producción (genera recursos)</option>
                    </select>
                </div>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-6">
                    <label class="form-label" id="multLabelNew">Costo por unidad (IM)</label>
                    <input type="number" step="0.01" min="0" name="tipo_mult" class="form-control"
                           placeholder="0" required>
                </div>
                <div class="col-6" id="unidadDivNew" style="display:none">
                    <label class="form-label">Unidad producida</label>
                    <input name="tipo_unidad" class="form-control" placeholder="IC, IM, IT, Energía…">
                    <small class="text-muted">Nombre libre de la unidad que produce</small>
                </div>
            </div>
            <div class="mt-2">
                <label class="form-label">Orden</label>
                <input type="number" name="tipo_orden" class="form-control" value="1">
            </div>
            <div class="alert alert-info mt-3 small mb-0">
                <strong>Producción:</strong> cada unidad genera <em>multiplicador × cantidad</em> de la unidad indicada.<br>
                <strong>Mantenimiento:</strong> cada unidad consume <em>multiplicador</em> IM por turno.
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" name="add_tipo" class="btn btn-primary">Crear Tipo</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function toggleUnidad(sel, suffix) {
    const esProd = sel.value === 'produccion';
    const div = document.getElementById('unidadDiv' + suffix);
    const lbl = document.getElementById('multLabel' + suffix);
    if (div) div.style.display = esProd ? '' : 'none';
    if (lbl) lbl.textContent = esProd ? 'Producción por unidad (×)' : 'Costo por unidad (IM)';
}
</script>

<?php require_once 'includes/footer.php'; ?>
