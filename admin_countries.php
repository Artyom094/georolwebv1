<?php
session_start();

// Solo Admin
if (!isset($_SESSION['user_id']) || $_SESSION['id_rol'] != 1) {
    header("Location: /index.php");
    exit;
}

require_once 'config/db.php';
require_once 'includes/functions.php';

$message = '';
$message_type = 'info'; // info, success, danger

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ── Crear País ──────────────────────────────────────
    if (isset($_POST['add_country'])) {
        $name = trim($_POST['country_name'] ?? '');
        if (!empty($name)) {
            try {
                $stmt = $conn->prepare("INSERT INTO paises (nombre_pais) VALUES (:n)");
                $stmt->execute([':n' => $name]);
                $id = $conn->lastInsertId();
                $conn->exec("INSERT INTO cartillas (id_pais) VALUES ($id)");
                $message = "País <strong>$name</strong> creado exitosamente.";
                $message_type = 'success';
            } catch(PDOException $e) {
                $message = "Error: " . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }

    // ── Subir/Reemplazar Bandera ────────────────────────
    if (isset($_POST['id_pais_bandera'])
        && isset($_FILES['upload_bandera'])
        && $_FILES['upload_bandera']['error'] !== UPLOAD_ERR_NO_FILE)
    {
        $id_pais = intval($_POST['id_pais_bandera']);

        $country_stmt = $conn->prepare("SELECT nombre_pais FROM paises WHERE id_pais = :id");
        $country_stmt->execute([':id' => $id_pais]);
        $country_data = $country_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$country_data) {
            $message = "Error: País no encontrado.";
            $message_type = 'danger';
        } elseif ($_FILES['upload_bandera']['error'] === UPLOAD_ERR_OK) {
            $result = processAndSaveFlagImage(
                $_FILES['upload_bandera'],
                $id_pais,  // usar id_pais como prefijo del archivo
                $country_data['nombre_pais']
            );

            if ($result['success']) {
                // Eliminar bandera anterior si existe
                $old = $conn->prepare("SELECT bandera_url FROM paises WHERE id_pais = :id");
                $old->execute([':id' => $id_pais]);
                $old_url = $old->fetchColumn();
                if (!empty($old_url)) {
                    $old_path = __DIR__ . '/' . $old_url;
                    if (file_exists($old_path) && is_file($old_path)) {
                        @unlink($old_path);
                    }
                }

                $update = $conn->prepare("UPDATE paises SET bandera_url = :url WHERE id_pais = :id");
                $update->execute([':url' => $result['path'], ':id' => $id_pais]);
                $message = "✅ Bandera de <strong>" . htmlspecialchars($country_data['nombre_pais']) . "</strong> actualizada correctamente.";
                $message_type = 'success';
            } else {
                $message = "❌ Error al subir bandera: " . $result['error'];
                $message_type = 'danger';
            }
        } else {
            // Mapear código de error PHP a mensaje legible
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE   => 'Archivo demasiado grande (límite PHP: ' . ini_get('upload_max_filesize') . ')',
                UPLOAD_ERR_FORM_SIZE  => 'Archivo demasiado grande (límite formulario)',
                UPLOAD_ERR_PARTIAL    => 'Archivo subido parcialmente, intenta de nuevo',
                UPLOAD_ERR_NO_TMP_DIR => 'Error servidor: falta directorio temporal',
                UPLOAD_ERR_CANT_WRITE => 'Error servidor: no se puede escribir en disco',
                UPLOAD_ERR_EXTENSION  => 'Una extensión PHP bloqueó la subida',
            ];
            $err_code = $_FILES['upload_bandera']['error'];
            $message = "❌ " . ($upload_errors[$err_code] ?? "Error de subida desconocido (código $err_code)");
            $message_type = 'danger';
        }
    }

    // ── Editar nombre de país ───────────────────────────
    if (isset($_POST['update_country_name'])) {
        $id_pais = intval($_POST['id_pais_edit']);
        $new_name = trim($_POST['edit_country_name'] ?? '');
        if (!empty($new_name)) {
            try {
                $stmt = $conn->prepare("UPDATE paises SET nombre_pais = :n WHERE id_pais = :id");
                $stmt->execute([':n' => $new_name, ':id' => $id_pais]);
                $message = "Nombre del país actualizado a <strong>$new_name</strong>.";
                $message_type = 'success';
            } catch(PDOException $e) {
                $message = "Error: " . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }

    // ── Eliminar país ───────────────────────────────────
    if (isset($_POST['delete_country_id'])) {
        $id = intval($_POST['delete_country_id']);
        try {
            $owner_stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE id_pais = :id ORDER BY id_usuario ASC LIMIT 1");
            $owner_stmt->execute([':id' => $id]);
            $owner_id = $owner_stmt->fetchColumn();

            archiveCountrySnapshotToHistory(
                $conn,
                $owner_id !== false ? intval($owner_id) : $_SESSION['user_id'],
                $id,
                'País eliminado del sistema'
            );

            // Eliminar bandera del disco
            $old = $conn->prepare("SELECT bandera_url FROM paises WHERE id_pais = :id");
            $old->execute([':id' => $id]);
            $old_url = $old->fetchColumn();
            if (!empty($old_url)) {
                $old_path = __DIR__ . '/' . $old_url;
                if (file_exists($old_path)) @unlink($old_path);
            }

            $conn->prepare("UPDATE usuarios SET id_pais = NULL WHERE id_pais = ?")->execute([$id]);
            $conn->prepare("DELETE FROM cartilla_valores WHERE id_pais = ?")->execute([$id]);
            $conn->prepare("DELETE FROM cartillas WHERE id_pais = ?")->execute([$id]);
            $conn->prepare("DELETE FROM paises WHERE id_pais = ?")->execute([$id]);
            $message = "País eliminado.";
            $message_type = 'success';
        } catch(PDOException $e) {
            $message = "Error al eliminar: " . $e->getMessage();
            $message_type = 'danger';
        }
    }

}

$countries = $conn->query("SELECT id_pais, nombre_pais, bandera_url FROM paises ORDER BY nombre_pais")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Gestión de Países';
require_once 'includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">

    <!-- HERO -->
    <div class="card flag-glow mb-4" style="border-radius:20px;overflow:hidden">
        <div style="background:linear-gradient(135deg,#fd7e14,#dc3545);color:#fff;padding:2rem 2.5rem;position:relative;overflow:hidden">
            <div style="position:absolute;top:-50px;right:-50px;width:200px;height:200px;background:rgba(255,255,255,.08);border-radius:50%;filter:blur(25px)"></div>
            <div class="d-flex align-items-center gap-3 position-relative">
                <div style="width:56px;height:56px;background:rgba(255,255,255,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.7rem;backdrop-filter:blur(8px)">
                    <i class="bi bi-flag-fill"></i>
                </div>
                <div>
                    <h1 class="mb-0" style="font-size:1.6rem;font-weight:800">Gestión de Países</h1>
                    <p class="mb-0 mt-1" style="opacity:.8;font-size:.875rem"><?= count($countries) ?> países registrados</p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show mb-4">
        <?= $message ?><button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- AGREGAR PAÍS -->
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2"
             style="background:linear-gradient(135deg,rgba(253,126,20,.12),rgba(253,126,20,.04))">
            <i class="bi bi-plus-circle-fill text-warning"></i>
            <strong>Agregar Nuevo País</strong>
        </div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="country_name" class="form-control" placeholder="Nombre del País" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" name="add_country" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle me-1"></i>Crear País
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLA DE PAÍSES -->
    <div class="card">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-table"></i><strong>Países Registrados</strong>
            <span class="badge ms-auto" style="background:var(--glass-bg);color:var(--text-primary);border:1px solid var(--border-color)"><?= count($countries) ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:44px">#</th>
                            <th style="width:80px">Bandera</th>
                            <th>País</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($countries as $c): ?>
                    <tr>
                        <td class="text-muted" style="font-size:.8rem"><?= $c['id_pais'] ?></td>
                        <td>
                            <?php if(!empty($c['bandera_url'])): ?>
                                <img src="<?= htmlspecialchars($c['bandera_url']) ?>" alt="Bandera"
                                     style="max-height:28px;max-width:54px;border-radius:4px;object-fit:cover">
                            <?php else: ?>
                                <span class="text-muted"><i class="bi bi-image"></i></span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($c['nombre_pais']) ?></strong></td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $c['id_pais'] ?>">
                                    <i class="bi bi-pencil me-1"></i>Renombrar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#uploadModal<?= $c['id_pais'] ?>">
                                    <i class="bi bi-flag me-1"></i>Bandera
                                </button>
                                <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar <?= addslashes($c['nombre_pais']) ?> permanentemente?')">
                                    <input type="hidden" name="delete_country_id" value="<?= $c['id_pais'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                            
                            <!-- Edit Name Modal -->
                            <div class="modal fade" id="editModal<?php echo $c['id_pais']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Editar Nombre del País</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id_pais_edit" value="<?php echo $c['id_pais']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Nuevo Nombre</label>
                                                    <input type="text" name="edit_country_name" class="form-control" value="<?php echo htmlspecialchars($c['nombre_pais']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" name="update_country_name" class="btn btn-warning">Guardar Cambios</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Upload Flag Modal -->
                            <div class="modal fade" id="uploadModal<?= $c['id_pais'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <form method="post" enctype="multipart/form-data" id="flagForm<?= $c['id_pais'] ?>">
                                            <div class="modal-header" style="background:linear-gradient(135deg,#0d6efd,#6610f2);color:#fff">
                                                <h5 class="modal-title">
                                                    <i class="bi bi-upload"></i>
                                                    Bandera — <?= htmlspecialchars($c['nombre_pais']) ?>
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id_pais_bandera" value="<?= $c['id_pais'] ?>">

                                                <!-- Bandera actual -->
                                                <?php if (!empty($c['bandera_url'])): ?>
                                                <div class="text-center mb-3">
                                                    <img src="<?= htmlspecialchars($c['bandera_url']) ?>"
                                                         alt="Bandera actual"
                                                         style="max-height:70px;max-width:160px;border-radius:8px;border:2px solid var(--border-color)">
                                                    <div class="text-muted mt-1" style="font-size:.75rem">Bandera actual</div>
                                                </div>
                                                <?php endif; ?>

                                                <!-- Zona de drop / preview -->
                                                <div id="dropZone<?= $c['id_pais'] ?>"
                                                     onclick="document.getElementById('fileInput<?= $c['id_pais'] ?>').click()"
                                                     style="border:2px dashed var(--border-color);border-radius:12px;padding:2rem;text-align:center;cursor:pointer;transition:all .2s ease;position:relative">
                                                    <img id="previewImg<?= $c['id_pais'] ?>" src=""
                                                         style="display:none;max-height:100px;max-width:200px;border-radius:8px;margin-bottom:.75rem">
                                                    <div id="dropText<?= $c['id_pais'] ?>">
                                                        <i class="bi bi-cloud-arrow-up" style="font-size:2rem;color:var(--text-secondary)"></i>
                                                        <div class="mt-2" style="font-size:.875rem;color:var(--text-secondary)">
                                                            Haz clic o arrastra tu imagen aquí
                                                        </div>
                                                        <div style="font-size:.75rem;color:var(--text-secondary);margin-top:.25rem">
                                                            JPG, PNG, GIF, WEBP · Máx 10 MB
                                                        </div>
                                                    </div>
                                                    <input type="file"
                                                           id="fileInput<?= $c['id_pais'] ?>"
                                                           name="upload_bandera"
                                                           accept="image/jpeg,image/png,image/gif,image/webp,image/bmp"
                                                           style="display:none"
                                                           onchange="previewFlag(this, <?= $c['id_pais'] ?>)">
                                                </div>
                                                <div id="fileInfo<?= $c['id_pais'] ?>" class="mt-2 text-center" style="font-size:.78rem;color:var(--text-secondary)"></div>

                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-primary" id="submitBtn<?= $c['id_pais'] ?>" disabled>
                                                    <i class="bi bi-cloud-upload"></i> Subir Bandera
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- IT Production Modal -->
                            <div class="modal fade" id="itModal<?php echo $c['id_pais']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post">
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title"><i class="bi bi-lightning-charge-fill"></i> Asignar IT de Producción - <?php echo htmlspecialchars($c['nombre_pais']); ?></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id_pais_it" value="<?php echo $c['id_pais']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label"><i class="bi bi-gear-fill"></i> <strong>Producción IT Turno Actual</strong></label>
                                                    <input type="number" name="produccion_it_turno" class="form-control" 
                                                           value="<?php echo $c['produccion_it_turno'] ?? 0; ?>" 
                                                           min="0" step="1" required>
                                                    <small class="text-muted">Producción de IT (Tecnología) que el país genera este turno</small>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label"><i class="bi bi-arrow-repeat"></i> <strong>IT Sobrante Turno Anterior (50%)</strong></label>
                                                    <input type="number" name="it_sobrante_turno_anterior" class="form-control" 
                                                           value="<?php echo $c['it_sobrante_turno_anterior'] ?? 0; ?>" 
                                                           min="0" step="1" required>
                                                    <small class="text-muted">IT arrastrado del turno anterior (pendiente de uso)</small>
                                                </div>
                                                
                                                <div class="alert alert-info">
                                                    <strong>IT Total Disponible:</strong> 
                                                    <?php echo number_format(($c['produccion_it_turno'] ?? 0) + ($c['it_sobrante_turno_anterior'] ?? 0)); ?> IT
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" name="update_it_production" class="btn btn-success">
                                                    <i class="bi bi-save"></i> Guardar Cambios
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div><!-- /card-body -->
    </div><!-- /card países -->

</div><!-- /container-fluid -->

<?php require_once 'includes/footer.php'; ?>


<script>
function previewFlag(input, id) {
    const file = input.files[0];
    if (!file) return;

    const preview  = document.getElementById('previewImg'  + id);
    const dropText = document.getElementById('dropText'    + id);
    const fileInfo = document.getElementById('fileInfo'    + id);
    const submitBtn= document.getElementById('submitBtn'   + id);
    const dropZone = document.getElementById('dropZone'    + id);

    // Validate size client-side
    if (file.size > 10 * 1024 * 1024) {
        fileInfo.innerHTML = '<span class="text-danger">⚠ Archivo muy grande (máx 10 MB)</span>';
        submitBtn.disabled = true;
        return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
        dropText.style.display = 'none';
        dropZone.style.borderColor = '#0d6efd';
        dropZone.style.background  = 'rgba(13,110,253,.04)';

        const kb = (file.size / 1024).toFixed(1);
        fileInfo.innerHTML = `<i class="bi bi-check-circle text-success"></i> ${file.name} <span class="text-muted">(${kb} KB)</span>`;
        submitBtn.disabled = false;
    };
    reader.readAsDataURL(file);
}

// Drag & Drop support
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[id^="dropZone"]').forEach(function (zone) {
        const idNum = zone.id.replace('dropZone', '');
        const fileInput = document.getElementById('fileInput' + idNum);

        zone.addEventListener('dragover',  function (e) { e.preventDefault(); zone.style.borderColor='#0d6efd'; zone.style.background='rgba(13,110,253,.06)'; });
        zone.addEventListener('dragleave', function () { zone.style.borderColor=''; zone.style.background=''; });
        zone.addEventListener('drop', function (e) {
            e.preventDefault();
            zone.style.borderColor = '';
            zone.style.background  = '';
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                previewFlag(fileInput, idNum);
            }
        });
    });
});
</script>
