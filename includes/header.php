<?php
// Header — Georol V 1.0 Alpha 1 | Compilación 260517
define('GEOROL_VERSION', 'V 1.0 Alpha 1');
define('GEOROL_BUILD',   '260517');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT u.username, u.id_rol, u.debe_cambiar_password, u.avatar_url, r.nombre_rol, p.nombre_pais, u.id_pais
    FROM usuarios u
    JOIN roles r ON u.id_rol = r.id_rol
    LEFT JOIN paises p ON u.id_pais = p.id_pais
    WHERE u.id_usuario = :id
");
$stmt->execute([':id' => $_SESSION['user_id']]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($current_user['debe_cambiar_password'] && basename($_SERVER['PHP_SELF']) !== 'change_password.php') {
    header("Location: change_password.php");
    exit;
}

$page_title  = $page_title ?? 'Panel de Control';
$current_page = basename($_SERVER['PHP_SELF']);

// Badges de rol
$rol_badges = [
    1 => ['label' => 'Admin',        'class' => 'nav-badge-admin'],
    2 => ['label' => 'Game Master',  'class' => 'nav-badge-gm'],
    3 => ['label' => 'Auditor',      'class' => 'bg-warning text-dark'],
    4 => ['label' => 'Participante', 'class' => 'bg-secondary'],
];
$rol_badge = $rol_badges[$current_user['id_rol']] ?? ['label' => 'Usuario', 'class' => 'bg-secondary'];

// Avatar: foto real o inicial
$avatar_url     = $current_user['avatar_url'] ?? '';
$avatar_inicial = strtoupper(substr($current_user['username'], 0, 1));
$is_external_avatar = !empty($avatar_url) && (strpos($avatar_url, 'http://') === 0 || strpos($avatar_url, 'https://') === 0);
$has_avatar     = !empty($avatar_url) && ($is_external_avatar || file_exists(__DIR__ . '/../' . $avatar_url));
$avatar_bust    = ($has_avatar && !$is_external_avatar) ? '?v=' . filemtime(__DIR__ . '/../' . $avatar_url) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Georol — Sistema de Gestión Geopolítica para comunidades Discord">
    <title><?= htmlspecialchars($page_title) ?> · Georol</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= str_repeat('../', substr_count($current_page, '/')) ?>assets/css/style.css">
    <script src="<?= str_repeat('../', substr_count($current_page, '/')) ?>assets/js/theme.js"></script>
    <script src="<?= str_repeat('../', substr_count($current_page, '/')) ?>assets/js/color-extract.js"></script>
</head>
<body>

<!-- ── Capa de fondo con blur ── -->
<div class="page-bg-layer" id="pageBgLayer"></div>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container-fluid px-3 px-lg-4">

        <!-- ── Brand ── -->
        <a class="navbar-brand" href="index.php">
            <span class="brand-icon"><i class="bi bi-globe2"></i></span>
            <span>Georol</span>
            <span class="brand-version"><?= GEOROL_VERSION ?></span>
        </a>

        <!-- ── Toggler mobile ── -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- ── Links ── -->
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto align-items-lg-center">

                <!-- Inicio -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'index.php' ? 'active-link' : '' ?>" href="index.php">
                        <i class="bi bi-house-door"></i> Inicio
                    </a>
                </li>

                <!-- Mi País (si tiene asignado) -->
                <?php if ($current_user['id_pais']): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'view_country.php' ? 'active-link' : '' ?>"
                       href="view_country.php?id=<?= intval($current_user['id_pais']) ?>">
                        <i class="bi bi-flag"></i> Mi País
                    </a>
                </li>
                <?php endif; ?>

                <!-- Enfoques (solo con país) -->
                <?php if ($current_user['id_pais']): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'enfoques.php' ? 'active-link' : '' ?>"
                       href="enfoques.php">
                        <i class="bi bi-crosshair2"></i> Enfoques
                    </a>
                </li>
                <?php endif; ?>

                <!-- Alianzas -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'alianzas.php' ? 'active-link' : '' ?>"
                       href="alianzas.php">
                        <i class="bi bi-people-fill"></i> Alianzas
                    </a>
                </li>

                <!-- Documentos del sitio -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'reglas.php' ? 'active-link' : '' ?>"
                       href="reglas.php">
                        <i class="bi bi-journal-text"></i> Reglas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'politicas_privacidad.php' ? 'active-link' : '' ?>"
                       href="politicas_privacidad.php">
                        <i class="bi bi-shield-lock"></i> Privacidad
                    </a>
                </li>

                <!-- Historial (solo participantes con país) -->
                <?php if ($current_user['id_rol'] == 4 && $current_user['id_pais']): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'profile_history.php' ? 'active-link' : '' ?>"
                       href="profile_history.php">
                        <i class="bi bi-clock-history"></i> Historial
                    </a>
                </li>
                <?php endif; ?>

                <!-- ─ Separador GM/Admin ─ -->
                <?php if (in_array($current_user['id_rol'], [1, 2])): ?>
                <li class="nav-item d-none d-lg-flex align-items-center">
                    <span class="nav-section-divider"></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'gm_dashboard.php' ? 'active-link' : '' ?>"
                       href="gm_dashboard.php">
                        <i class="bi bi-shield-check"></i> GM
                        <span class="nav-badge-gm" style="font-size:.55rem;padding:1px 4px;border-radius:3px;background:#0d6efd;color:#fff;">GM</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'gm_turnos.php' ? 'active-link' : '' ?>"
                       href="gm_turnos.php">
                        <i class="bi bi-calendar-event"></i> Turnos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'gm_research.php' ? 'active-link' : '' ?>"
                       href="gm_research.php">
                        <i class="bi bi-lightbulb"></i> Research
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'gm_documentos.php' ? 'active-link' : '' ?>"
                       href="gm_documentos.php">
                        <i class="bi bi-file-earmark-text"></i> Documentos
                    </a>
                </li>
                <?php endif; ?>

                <!-- ─ Separador Admin ─ -->
                <?php if ($current_user['id_rol'] == 1): ?>
                <li class="nav-item d-none d-lg-flex align-items-center">
                    <span class="nav-section-divider"></span>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-shield-lock-fill"></i> Admin
                        <span class="nav-badge-admin" style="font-size:.55rem;padding:1px 4px;border-radius:3px;">ADM</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="admin_users.php">
                                <i class="bi bi-people"></i> Usuarios
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="admin_countries.php">
                                <i class="bi bi-flag"></i> Países
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="admin_cartilla.php">
                                <i class="bi bi-clipboard2-data-fill"></i> Cartillas
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="admin_cartilla_tipos.php">
                                <i class="bi bi-sliders"></i> Tipos de Cartilla
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="gm_documentos.php">
                                <i class="bi bi-file-earmark-text"></i> Documentos
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

            </ul>

            <!-- ── Derecha: usuario ── -->
            <ul class="navbar-nav align-items-lg-center gap-2 mt-2 mt-lg-0">
                <li class="nav-item dropdown">
                    <a class="user-btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" style="border:none">
                        <?php if ($has_avatar): ?>
                            <img src="<?= htmlspecialchars($avatar_url . $avatar_bust) ?>"
                                 alt="<?= htmlspecialchars($current_user['username']) ?>"
                                 class="user-avatar-img">
                        <?php else: ?>
                            <span class="user-avatar"><?= $avatar_inicial ?></span>
                        <?php endif; ?>
                        <span class="d-none d-sm-inline"><?= htmlspecialchars($current_user['username']) ?></span>
                        <span class="badge <?= $rol_badge['class'] ?> ms-1" style="font-size:.58rem">
                            <?= $rol_badge['label'] ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if ($current_user['nombre_pais']): ?>
                        <li>
                            <span class="dropdown-item text-muted" style="cursor:default;font-size:.78rem">
                                <i class="bi bi-globe2"></i> <?= htmlspecialchars($current_user['nombre_pais']) ?>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li>
                            <a class="dropdown-item" href="profile.php">
                                <i class="bi bi-person-circle"></i> Mi Perfil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="change_password.php">
                                <i class="bi bi-key"></i> Cambiar Contraseña
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div><!-- /collapse -->
    </div>
</nav>
