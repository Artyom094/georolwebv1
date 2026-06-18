<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

require_once 'config/db.php';

$page_title = 'Panel de Control';
$role_id = $_SESSION['id_rol'];
$user_id = $_SESSION['user_id'];
$roles_map = [1=>'Administrador', 2=>'Game Master', 3=>'Auditor', 4=>'Participante'];
$role_name = $roles_map[$role_id] ?? 'Usuario';
$role_badge = [1=>'danger', 2=>'primary', 3=>'warning', 4=>'success'];

require_once 'includes/header.php';
?>

    <div class="container-fluid py-4">
        
        <!-- Turno Actual Global -->
        <?php 
            $turno_global_stmt = $conn->query("SELECT turno_actual FROM turno_global WHERE id = 1");
            $turno_global_data = $turno_global_stmt->fetch(PDO::FETCH_ASSOC);
            $turno_actual_global = $turno_global_data['turno_actual'] ?? 1;
        ?>
        <div class="alert alert-primary alert-dismissible fade show mb-4" role="alert">
            <h5 class="alert-heading">
                <i class="bi bi-calendar-event"></i> Turno Actual del Juego: <strong>#<?= $turno_actual_global ?></strong>
            </h5>
            <p class="mb-0">Todos los países están en el turno <?= $turno_actual_global ?>. Los usuarios pueden editar sus cartillas según las reglas establecidas.</p>
            <?php if(in_array($role_id, [1, 2])): ?>
                <hr>
                <a href="gm_turnos.php" class="btn btn-sm btn-light">
                    <i class="bi bi-gear"></i> Gestionar Turnos
                </a>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        
        <!-- Alertas / Notificaciones -->
        <?php if($role_id == 2 || $role_id == 1): 
            $sqlNotif = "SELECT n.mensaje, u.username, n.fecha_creacion FROM notificaciones n 
                         JOIN usuarios u ON n.id_usuario_emisor = u.id_usuario 
                         WHERE n.id_rol_receptor = 2 AND n.leido = 0 LIMIT 5";
            $resNotif = $conn->query($sqlNotif);
            if($resNotif && $resNotif->rowCount() > 0):
        ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Reportes de Auditoría Pendientes</h5>
            <ul class="mb-0">
                <?php while($row = $resNotif->fetch(PDO::FETCH_ASSOC)): ?>
                    <li>
                        <strong><?php echo $row['username']; ?>:</strong> 
                        <?php echo htmlspecialchars($row['mensaje']); ?> 
                        <small class="text-muted">(<?php echo $row['fecha_creacion']; ?>)</small>
                    </li>
                <?php endwhile; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; endif; ?>

        <!-- Dashboard Grid -->
        <div class="row g-4">
            
            <!-- PANEL ADMINISTRATIVO -->
            <?php if ($role_id == 1): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="bi bi-shield-lock"></i> Administración del Sistema
                    </div>
                    <div class="card-body">
                        <p class="card-text">Control total del sistema y configuraciones globales.</p>
                        <div class="d-grid gap-2">
                            <a href="admin_users.php" class="btn btn-primary">
                                <i class="bi bi-people"></i> Gestión de Usuarios
                            </a>
                            <a href="admin_countries.php" class="btn btn-primary">
                                <i class="bi bi-flag"></i> Gestión de Países
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- PANEL GM -->
            <?php if ($role_id == 1 || $role_id == 2): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="bi bi-dice-5"></i> Gestión de Juego (GM)
                    </div>
                    <div class="card-body">
                        <p class="card-text">Administración de participantes y asignación de países.</p>
                        <div class="d-grid gap-2">
                            <a href="gm_dashboard.php" class="btn btn-primary">
                                <i class="bi bi-arrow-left-right"></i> Asignar Países
                            </a>
                            <button class="btn btn-outline-secondary" disabled>
                                <i class="bi bi-diagram-3"></i> Alianzas (Próximamente)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- PANEL PARTICIPANTE -->
            <?php if ($id_pais = $_SESSION['id_pais']): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="bi bi-geo-alt"></i> Mi País Asignado
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $conn->prepare("SELECT * FROM paises WHERE id_pais = ?");
                        $stmt->execute([$id_pais]);
                        $miPais = $stmt->fetch();
                        if ($miPais):
                        ?>
                            <h5 class="card-title"><?php echo htmlspecialchars($miPais['nombre_pais']); ?></h5>
                            <div class="text-center my-3">
                                <img src="<?php echo htmlspecialchars($miPais['bandera_url'] ?? 'assets/img/missing_flag.png'); ?>" 
                                     class="img-fluid rounded" style="max-height: 120px;" alt="Bandera">
                            </div>
                            <div class="d-grid">
                                <a href="view_country.php?id=<?php echo $id_pais; ?>" class="btn btn-success">
                                    <i class="bi bi-clipboard-data"></i> Ver Cartilla
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php elseif($role_id == 4): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="bi bi-hourglass-split"></i> Estado
                    </div>
                    <div class="card-body">
                        <p class="text-muted">No tienes un país asignado actualmente. Contacte a un GM.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- LISTA DE PAÍSES CON TURNOS -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-globe2"></i> Estado de Países</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $paises_query = $conn->query("
                            SELECT p.id_pais, p.nombre_pais, p.turno_actual, p.bandera_url, u.username, a.nombre_alianza, a.logo_url
                            FROM paises p
                            LEFT JOIN usuarios u ON p.id_pais = u.id_pais
                            LEFT JOIN alianzas a ON p.id_alianza = a.id_alianza
                            ORDER BY p.turno_actual DESC, p.nombre_pais
                        ");
                        $paises = $paises_query->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;"></th>
                                        <th>País</th>
                                        <th>Usuario</th>
                                        <th>Alianza</th>
                                        <th class="text-center">Turno Actual</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paises as $pais): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($pais['bandera_url'])): ?>
                                                    <img src="<?= htmlspecialchars($pais['bandera_url']) ?>" 
                                                         alt="Bandera" 
                                                         class="img-thumbnail" 
                                                         style="max-width: 40px; max-height: 30px;">
                                                <?php else: ?>
                                                    <div class="bg-secondary text-white rounded text-center" style="width: 40px; height: 30px;">
                                                        <i class="bi bi-flag"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><strong><?= htmlspecialchars($pais['nombre_pais']) ?></strong></td>
                                            <td>
                                                <?php if ($pais['username']): ?>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($pais['username']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin asignar</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($pais['nombre_alianza']): ?>
                                                    <span class="d-flex align-items-center gap-2">
                                                        <?php if (!empty($pais['logo_url'])): ?>
                                                            <img src="<?= htmlspecialchars($pais['logo_url']) ?>" 
                                                                 alt="Logo" style="width: 25px; height: 25px;" class="rounded">
                                                        <?php endif; ?>
                                                        <span class="badge bg-success"><?= htmlspecialchars($pais['nombre_alianza']) ?></span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Neutral</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-dark" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                                    Turno <?= $pais['turno_actual'] ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="view_country.php?id=<?= $pais['id_pais'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i> Ver
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- PANEL AUDITOR -->
            <?php if ($role_id == 1 || $role_id == 3): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="bi bi-file-earmark-text"></i> Auditoría
                    </div>
                    <div class="card-body">
                        <p class="card-text">Herramientas de revisión y reporte de anomalías.</p>
                        <div class="d-grid gap-2">
                            <a href="auditor_list.php" class="btn btn-warning">
                                <i class="bi bi-list-check"></i> Lista de Países
                            </a>
                            <a href="auditor_report.php" class="btn btn-outline-warning">
                                <i class="bi bi-file-plus"></i> Crear Reporte
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>
