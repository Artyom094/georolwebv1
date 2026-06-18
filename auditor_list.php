<?php
session_start();

// Auditor o Admin pueden ver todos los países
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['id_rol'], [1, 3])) {
    header("Location: /index.php");
    exit;
}

require_once 'config/db.php';

$stmt = $conn->query("SELECT p.id_pais, p.nombre_pais, u.username as gobernador 
                      FROM paises p 
                      LEFT JOIN usuarios u ON u.id_pais = p.id_pais");
$countries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Auditoría - Lista de Países';
require_once 'includes/header.php';
?>

    <div class="container my-4">
        <div class="card shadow">
            <div class="card-header">
                <h2 class="mb-0"><i class="bi bi-clipboard-check"></i> Auditoría de Países</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>País</th>
                                <th>Gobernador</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($countries as $c): ?>
                            <tr>
                                <td><?php echo $c['id_pais']; ?></td>
                                <td><strong><?php echo htmlspecialchars($c['nombre_pais']); ?></strong></td>
                                <td>
                                    <?php if($c['gobernador']): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($c['gobernador']); ?></span>
                                    <?php else: ?>
                                        <em class="text-muted">Sin Asignar</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="view_country.php?id=<?php echo $c['id_pais']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-eye"></i> Inspeccionar
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

<?php require_once 'includes/footer.php'; ?>
