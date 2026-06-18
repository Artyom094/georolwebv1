<?php
session_start();

// Solo GM y Admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['id_rol'], [1, 2])) {
    header("Location: /index.php");
    exit;
}

require_once 'config/db.php';

$page_title = 'Gestión de Investigaciones';
$message = '';

// ==================== PROCESAMIENTO DE FORMULARIOS ====================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Agregar Categoría
    if (isset($_POST['add_category'])) {
        $nombre = trim($_POST['nombre_categoria']);
        $orden = intval($_POST['orden_categoria']);
        
        if (!empty($nombre)) {
            $stmt = $conn->prepare("INSERT INTO categorias_investigacion (nombre_categoria, orden) VALUES (:n, :o)");
            $stmt->execute([':n' => $nombre, ':o' => $orden]);
            $message = "Categoría agregada correctamente.";
        }
    }
    
    // Agregar Subcategoría
    if (isset($_POST['add_subcategory'])) {
        $id_cat = intval($_POST['id_categoria']);
        $nombre = trim($_POST['nombre_subcategoria']);
        $orden = intval($_POST['orden_subcategoria']);
        
        if (!empty($nombre) && $id_cat > 0) {
            $stmt = $conn->prepare("INSERT INTO subcategorias_investigacion (id_categoria, nombre_subcategoria, orden) VALUES (:c, :n, :o)");
            $stmt->execute([':c' => $id_cat, ':n' => $nombre, ':o' => $orden]);
            $message = "Subcategoría agregada correctamente.";
        }
    }
    
    // Agregar Investigación
    if (isset($_POST['add_research'])) {
        $id_cat = intval($_POST['id_categoria']);
        $id_subcat = !empty($_POST['id_subcategoria']) ? intval($_POST['id_subcategoria']) : null;
        $nombre = trim($_POST['nombre_investigacion']);
        $costo = intval($_POST['costo_it']);
        $desc = trim($_POST['descripcion']);
        $orden = intval($_POST['orden_investigacion']);
        $requisitos = isset($_POST['requisitos']) ? $_POST['requisitos'] : [];
        
        if (!empty($nombre) && $id_cat > 0) {
            $stmt = $conn->prepare("
                INSERT INTO investigaciones (id_categoria, id_subcategoria, nombre_investigacion, costo_it, descripcion, orden) 
                VALUES (:c, :sc, :n, :co, :d, :o)
            ");
            $stmt->execute([
                ':c' => $id_cat, 
                ':sc' => $id_subcat, 
                ':n' => $nombre, 
                ':co' => $costo, 
                ':d' => $desc, 
                ':o' => $orden
            ]);
            
            $new_id = $conn->lastInsertId();
            
            // Agregar requisitos
            if (!empty($requisitos)) {
                $req_stmt = $conn->prepare("INSERT INTO investigaciones_requisitos (id_investigacion, id_investigacion_requerida) VALUES (:i, :r)");
                foreach ($requisitos as $req_id) {
                    $req_stmt->execute([':i' => $new_id, ':r' => intval($req_id)]);
                }
            }
            
            $message = "Investigación agregada correctamente.";
        }
    }
    
    // Eliminar Investigación
    if (isset($_POST['delete_research'])) {
        $id = intval($_POST['id_investigacion']);
        $stmt = $conn->prepare("DELETE FROM investigaciones WHERE id_investigacion = :id");
        $stmt->execute([':id' => $id]);
        $message = "Investigación eliminada.";
    }
    
    // Editar Investigación
    if (isset($_POST['edit_research'])) {
        $id = intval($_POST['id_investigacion']);
        $nombre = trim($_POST['nombre_investigacion']);
        $costo = intval($_POST['costo_it']);
        $desc = trim($_POST['descripcion']);
        $requisitos = isset($_POST['requisitos']) ? $_POST['requisitos'] : [];
        
        $stmt = $conn->prepare("
            UPDATE investigaciones 
            SET nombre_investigacion = :n, costo_it = :c, descripcion = :d 
            WHERE id_investigacion = :id
        ");
        $stmt->execute([':n' => $nombre, ':c' => $costo, ':d' => $desc, ':id' => $id]);
        
        // Actualizar requisitos
        $conn->prepare("DELETE FROM investigaciones_requisitos WHERE id_investigacion = :id")->execute([':id' => $id]);
        if (!empty($requisitos)) {
            $req_stmt = $conn->prepare("INSERT INTO investigaciones_requisitos (id_investigacion, id_investigacion_requerida) VALUES (:i, :r)");
            foreach ($requisitos as $req_id) {
                if ($req_id != $id) { // Evitar auto-requisito
                    $req_stmt->execute([':i' => $id, ':r' => intval($req_id)]);
                }
            }
        }
        
        $message = "Investigación actualizada.";
    }
    
    // Eliminar Categoría
    if (isset($_POST['delete_category'])) {
        $id = intval($_POST['id_categoria']);
        $stmt = $conn->prepare("DELETE FROM categorias_investigacion WHERE id_categoria = :id");
        $stmt->execute([':id' => $id]);
        $message = "Categoría eliminada (y todas sus investigaciones).";
    }
    
    // Eliminar Subcategoría
    if (isset($_POST['delete_subcategory'])) {
        $id = intval($_POST['id_subcategoria']);
        $stmt = $conn->prepare("DELETE FROM subcategorias_investigacion WHERE id_subcategoria = :id");
        $stmt->execute([':id' => $id]);
        $message = "Subcategoría eliminada.";
    }
}

// ==================== OBTENER DATOS ====================

// Categorías
$categorias = $conn->query("SELECT * FROM categorias_investigacion ORDER BY orden, nombre_categoria")->fetchAll(PDO::FETCH_ASSOC);

// Subcategorías agrupadas por categoría
$subcategorias_map = [];
$subcats = $conn->query("SELECT * FROM subcategorias_investigacion ORDER BY id_categoria, orden, nombre_subcategoria")->fetchAll(PDO::FETCH_ASSOC);
foreach ($subcats as $sc) {
    $subcategorias_map[$sc['id_categoria']][] = $sc;
}

// Investigaciones
$investigaciones = $conn->query("
    SELECT i.*, c.nombre_categoria, s.nombre_subcategoria
    FROM investigaciones i
    JOIN categorias_investigacion c ON i.id_categoria = c.id_categoria
    LEFT JOIN subcategorias_investigacion s ON i.id_subcategoria = s.id_subcategoria
    ORDER BY c.orden, c.nombre_categoria, s.orden, i.orden, i.nombre_investigacion
")->fetchAll(PDO::FETCH_ASSOC);

// Obtener requisitos para cada investigación
$requisitos_map = [];
$req_query = $conn->query("
    SELECT ir.id_investigacion, ir.id_investigacion_requerida, i.nombre_investigacion
    FROM investigaciones_requisitos ir
    JOIN investigaciones i ON ir.id_investigacion_requerida = i.id_investigacion
");
foreach ($req_query->fetchAll(PDO::FETCH_ASSOC) as $req) {
    $requisitos_map[$req['id_investigacion']][] = $req;
}

include 'includes/header.php';
?>

<main class="container-fluid my-4">
    
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- SIDEBAR: Gestión de Estructura -->
        <div class="col-lg-3">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-gear-fill"></i> Estructura</h5>
                </div>
                <div class="card-body">
                    
                    <!-- Agregar Categoría -->
                    <button class="btn btn-primary btn-sm w-100 mb-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-circle"></i> Nueva Categoría
                    </button>
                    
                    <!-- Agregar Subcategoría -->
                    <button class="btn btn-secondary btn-sm w-100 mb-3" data-bs-toggle="modal" data-bs-target="#addSubcategoryModal">
                        <i class="bi bi-plus-circle"></i> Nueva Subcategoría
                    </button>
                    
                    <hr>
                    
                    <!-- Lista de Categorías -->
                    <h6 class="text-muted">Categorías Actuales</h6>
                    <div class="list-group list-group-flush">
                        <?php foreach ($categorias as $cat): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center p-2">
                                <div>
                                    <strong><?= htmlspecialchars($cat['nombre_categoria']) ?></strong>
                                    <small class="text-muted d-block">
                                        <?= count($subcategorias_map[$cat['id_categoria']] ?? []) ?> subcategorías
                                    </small>
                                </div>
                                <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar esta categoría y todas sus investigaciones?')">
                                    <input type="hidden" name="id_categoria" value="<?= $cat['id_categoria'] ?>">
                                    <button type="submit" name="delete_category" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                            
                            <?php if (!empty($subcategorias_map[$cat['id_categoria']])): ?>
                                <?php foreach ($subcategorias_map[$cat['id_categoria']] as $subcat): ?>
                                    <div class="list-group-item ps-4 py-1 d-flex justify-content-between align-items-center">
                                        <small class="text-muted">↳ <?= htmlspecialchars($subcat['nombre_subcategoria']) ?></small>
                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar esta subcategoría?')">
                                            <input type="hidden" name="id_subcategoria" value="<?= $subcat['id_subcategoria'] ?>">
                                            <button type="submit" name="delete_subcategory" class="btn btn-sm btn-link text-danger p-0">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- MAIN: Investigaciones -->
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="bi bi-lightbulb-fill"></i> Investigaciones</h4>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addResearchModal">
                        <i class="bi bi-plus-lg"></i> Nueva Investigación
                    </button>
                </div>
                <div class="card-body">
                    
                    <?php if (empty($investigaciones)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No hay investigaciones registradas. Agrega la primera.
                        </div>
                    <?php else: ?>
                        
                        <?php 
                        $current_cat = '';
                        $current_subcat = '';
                        foreach ($investigaciones as $inv): 
                            // Nueva categoría
                            if ($inv['nombre_categoria'] != $current_cat):
                                if ($current_cat != '') echo '</tbody></table></div>';
                                $current_cat = $inv['nombre_categoria'];
                                $current_subcat = '';
                        ?>
                                <h5 class="mt-4 mb-3 text-primary border-bottom pb-2">
                                    <i class="bi bi-folder-fill"></i> <?= htmlspecialchars($current_cat) ?>
                                </h5>
                                <div class="table-responsive">
                                <table class="table table-hover table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 35%;">Investigación</th>
                                            <th style="width: 12%;">Costo IT</th>
                                            <th style="width: 25%;">Requisitos</th>
                                            <th style="width: 18%;">Descripción</th>
                                            <th style="width: 10%;" class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        <?php 
                            endif;
                            
                            // Nueva subcategoría
                            if ($inv['nombre_subcategoria'] && $inv['nombre_subcategoria'] != $current_subcat):
                                $current_subcat = $inv['nombre_subcategoria'];
                        ?>
                                <tr class="table-secondary">
                                    <td colspan="4"><strong><?= htmlspecialchars($current_subcat) ?></strong></td>
                                </tr>
                        <?php endif; ?>
                        
                        <!-- Fila de investigación -->
                        <tr>
                            <td>
                                <?php if ($inv['nombre_subcategoria']): ?>
                                    <span class="text-muted me-2">↳</span>
                                <?php endif; ?>
                                <?= htmlspecialchars($inv['nombre_investigacion']) ?>
                            </td>
                            <td><span class="badge bg-info"><?= number_format($inv['costo_it']) ?> IT</span></td>
                            <td>
                                <?php if (!empty($requisitos_map[$inv['id_investigacion']])): ?>
                                    <small class="text-muted">
                                        <?php foreach ($requisitos_map[$inv['id_investigacion']] as $req): ?>
                                            <span class="badge bg-secondary mb-1">
                                                <i class="bi bi-arrow-right-short"></i>
                                                <?= htmlspecialchars($req['nombre_investigacion']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted">Sin requisitos</small>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?= htmlspecialchars($inv['descripcion'] ?: '-') ?></small></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $inv['id_investigacion'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar esta investigación?')">
                                    <input type="hidden" name="id_investigacion" value="<?= $inv['id_investigacion'] ?>">
                                    <button type="submit" name="delete_research" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        
                        <?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modales de Edición (fuera del loop para evitar conflictos de backdrop) -->
<?php foreach ($investigaciones as $inv): ?>
<div class="modal fade" id="editModal<?= $inv['id_investigacion'] ?>" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Investigación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_investigacion" value="<?= $inv['id_investigacion'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre_investigacion" class="form-control" value="<?= htmlspecialchars($inv['nombre_investigacion']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Costo IT</label>
                        <input type="number" name="costo_it" class="form-control" value="<?= $inv['costo_it'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($inv['descripcion']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Requisitos Previos</label>
                        <small class="text-muted d-block mb-2">Selecciona las investigaciones que deben completarse antes de esta</small>
                        <select name="requisitos[]" class="form-select" multiple size="5">
                            <?php 
                            $current_reqs = array_column($requisitos_map[$inv['id_investigacion']] ?? [], 'id_investigacion_requerida');
                            foreach ($investigaciones as $other): 
                                if ($other['id_investigacion'] != $inv['id_investigacion']):
                            ?>
                                <option value="<?= $other['id_investigacion'] ?>" 
                                        <?= in_array($other['id_investigacion'], $current_reqs) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($other['nombre_investigacion']) ?> (<?= $other['costo_it'] ?> IT)
                                </option>
                            <?php endif; endforeach; ?>
                        </select>
                        <small class="text-muted">Mantén Ctrl/Cmd para seleccionar múltiples</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="edit_research" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Modal: Agregar Categoría -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre_categoria" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Orden</label>
                        <input type="number" name="orden_categoria" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="add_category" class="btn btn-primary">Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Agregar Subcategoría -->
<div class="modal fade" id="addSubcategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Subcategoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Categoría</label>
                        <select name="id_categoria" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre_categoria']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre_subcategoria" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Orden</label>
                        <input type="number" name="orden_subcategoria" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="add_subcategory" class="btn btn-primary">Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Agregar Investigación -->
<div class="modal fade" id="addResearchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Investigación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Categoría *</label>
                            <select name="id_categoria" id="cat_select" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre_categoria']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subcategoría (opcional)</label>
                            <select name="id_subcategoria" id="subcat_select" class="form-select">
                                <option value="">Ninguna</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre de Investigación *</label>
                        <input type="text" name="nombre_investigacion" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Costo IT *</label>
                            <input type="number" name="costo_it" class="form-control" value="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Orden</label>
                            <input type="number" name="orden_investigacion" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Requisitos Previos</label>
                        <small class="text-muted d-block mb-2">Selecciona las investigaciones que deben completarse antes</small>
                        <select name="requisitos[]" class="form-select" multiple size="5">
                            <?php foreach ($investigaciones as $other): ?>
                                <option value="<?= $other['id_investigacion'] ?>">
                                    <?= htmlspecialchars($other['nombre_investigacion']) ?> (<?= $other['costo_it'] ?> IT)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Mantén Ctrl/Cmd para seleccionar múltiples</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="add_research" class="btn btn-primary">Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Dynamic subcategory loading
const subcatsData = <?= json_encode($subcategorias_map) ?>;

document.getElementById('cat_select').addEventListener('change', function() {
    const catId = this.value;
    const subcatSelect = document.getElementById('subcat_select');
    subcatSelect.innerHTML = '<option value="">Ninguna</option>';
    
    if (catId && subcatsData[catId]) {
        subcatsData[catId].forEach(sc => {
            const option = document.createElement('option');
            option.value = sc.id_subcategoria;
            option.textContent = sc.nombre_subcategoria;
            subcatSelect.appendChild(option);
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
