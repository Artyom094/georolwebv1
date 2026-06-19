<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

require_once 'config/db.php';

$page_title = 'Panel de Control';
$role_id    = $_SESSION['id_rol'];
$user_id    = $_SESSION['user_id'];

// Turno global
$turno_data   = $conn->query("SELECT turno_actual, ultima_actualizacion FROM turno_global WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$turno_actual = $turno_data['turno_actual'] ?? 1;

// Stats generales
$stats = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM paises)                                    as total_paises,
        (SELECT COUNT(*) FROM usuarios WHERE id_pais IS NOT NULL)        as paises_asignados,
        (SELECT COUNT(*) FROM alianzas WHERE aprobada = 1)               as alianzas_activas,
        (SELECT COUNT(*) FROM usuarios WHERE activo = 1)                 as total_usuarios
")->fetch(PDO::FETCH_ASSOC);

require_once 'includes/header.php';

// Colores/íconos por rol
$role_meta = [
    1 => ['color' => 'danger',    'icon' => 'shield-lock-fill', 'label' => 'Administrador'],
    2 => ['color' => 'primary',   'icon' => 'dice-5-fill',      'label' => 'Game Master'],
    3 => ['color' => 'warning',   'icon' => 'eye-fill',         'label' => 'Auditor'],
    4 => ['color' => 'success',   'icon' => 'person-fill',      'label' => 'Participante'],
];
$rm = $role_meta[$role_id] ?? ['color' => 'secondary', 'icon' => 'person', 'label' => 'Usuario'];
?>

<div class="container-fluid px-3 px-lg-4 py-4">

    <!-- ══════════════════════════════
         HERO ROW: Bienvenida + Turno
    ══════════════════════════════ -->
    <div class="row g-3 mb-4 fade-up">

        <!-- Bienvenida -->
        <div class="col-lg-8">
            <div class="card h-100" style="border-radius:16px;overflow:hidden">
                <div class="card-body d-flex align-items-center gap-4 p-4">
                    <!-- Avatar grande -->
                    <div style="width:72px;height:72px;background:linear-gradient(135deg,#0d6efd,#6610f2);border-radius:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 8px 24px rgba(13,110,253,.3);overflow:hidden;">
                        <?php if (!empty($avatar_url) && !empty($has_avatar)): ?>
                            <img src="<?= htmlspecialchars($avatar_url . $avatar_bust) ?>"
                                 alt="<?= htmlspecialchars($current_user['username']) ?>"
                                 style="width:100%;height:100%;object-fit:cover;display:block;">
                        <?php else: ?>
                            <span style="font-size:2rem;font-weight:800;color:#fff;letter-spacing:-1px;">
                                <?= strtoupper(substr($current_user['username'], 0, 1)) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h4 class="mb-0 fw-800" style="font-weight:800;letter-spacing:-.5px">
                                Bienvenido, <?= htmlspecialchars($current_user['username']) ?>
                            </h4>
                            <span class="badge bg-<?= $rm['color'] ?> d-flex align-items-center gap-1" style="font-size:.72rem">
                                <i class="bi bi-<?= $rm['icon'] ?>"></i> <?= $rm['label'] ?>
                            </span>
                        </div>
                        <?php if ($current_user['nombre_pais']): ?>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-globe2 text-muted"></i>
                            <span class="text-muted" style="font-size:.9rem"><?= htmlspecialchars($current_user['nombre_pais']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if ($current_user['id_pais']): ?>
                            <a href="view_country.php?id=<?= intval($current_user['id_pais']) ?>"
                               class="btn btn-primary btn-sm">
                                <i class="bi bi-clipboard-data"></i> Mi Cartilla
                            </a>
                            <a href="enfoques.php" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-crosshair2"></i> Enfoques
                            </a>
                            <?php endif; ?>
                            <a href="alianzas.php" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-people-fill"></i> Alianzas
                            </a>
                            <a href="profile.php" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-person-circle"></i> Perfil
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Turno Hero -->
        <div class="col-lg-4">
            <div class="turno-hero h-100">
                <div class="turno-label mb-1"><i class="bi bi-calendar-event"></i> TURNO GLOBAL ACTUAL</div>
                <div class="turno-number">#<?= $turno_actual ?></div>
                <div class="mt-2" style="font-size:.78rem;opacity:.65">
                    <i class="bi bi-clock"></i>
                    <?= $turno_data['ultima_actualizacion']
                        ? 'Act. ' . date('d/m/Y H:i', strtotime($turno_data['ultima_actualizacion']))
                        : 'Sin actualizaciones' ?>
                </div>
                <?php if (in_array($role_id, [1, 2])): ?>
                <div class="mt-3">
                    <a href="gm_turnos.php" class="btn btn-light btn-sm fw-600">
                        <i class="bi bi-gear"></i> Gestionar Turnos
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════
         STATS
    ══════════════════════════════ -->
    <div class="row g-3 mb-4 fade-up" style="animation-delay:.05s">
        <?php
        $stat_items = [
            ['icon' => 'globe2',        'color' => '#0d6efd', 'bg' => 'rgba(13,110,253,.1)',   'value' => $stats['total_paises'],    'label' => 'Países'],
            ['icon' => 'person-check',  'color' => '#198754', 'bg' => 'rgba(25,135,84,.1)',    'value' => $stats['paises_asignados'],'label' => 'Asignados'],
            ['icon' => 'shield-fill',   'color' => '#dc3545', 'bg' => 'rgba(220,53,69,.1)',    'value' => $stats['alianzas_activas'],'label' => 'Alianzas'],
            ['icon' => 'people-fill',   'color' => '#6f42c1', 'bg' => 'rgba(111,66,193,.1)',   'value' => $stats['total_usuarios'],  'label' => 'Usuarios'],
        ];
        foreach ($stat_items as $s):
        ?>
        <div class="col-6 col-md-3">
            <div class="card stat-card p-3">
                <div class="stat-icon" style="background:<?= $s['bg'] ?>;color:<?= $s['color'] ?>">
                    <i class="bi bi-<?= $s['icon'] ?>"></i>
                </div>
                <div>
                    <div class="stat-value" style="color:<?= $s['color'] ?>"><?= $s['value'] ?></div>
                    <div class="stat-label"><?= $s['label'] ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ══════════════════════════════
         PANELES DE ACCESO RÁPIDO
    ══════════════════════════════ -->
    <div class="row g-3 mb-4 fade-up" style="animation-delay:.1s">

        <!-- Mi País (participante con país) -->
        <?php
        $id_pais = $current_user['id_pais'] ?? null;
        if ($id_pais):
            $miPais = $conn->prepare("
                SELECT p.*, am.nombre_alianza as al_mil, ae.nombre_alianza as al_eco,
                       am.aprobada as mil_apro, ae.aprobada as eco_apro
                FROM paises p
                LEFT JOIN alianzas am ON p.id_alianza_militar  = am.id_alianza
                LEFT JOIN alianzas ae ON p.id_alianza_economica = ae.id_alianza
                WHERE p.id_pais = ?
            ");
            $miPais->execute([$id_pais]);
            $pais = $miPais->fetch(PDO::FETCH_ASSOC);
        ?>
        <div class="col-sm-6 col-xl-3">
            <div class="panel-card">
                <div class="panel-header" style="background:linear-gradient(135deg,#198754,#146c43);color:#fff">
                    <i class="bi bi-geo-alt-fill"></i> Mi País
                </div>
                <div class="panel-body">
                    <div class="text-center mb-3">
                        <?php if (!empty($pais['bandera_url']) && $pais['bandera_url'] !== 'default_flag.png'): ?>
                            <img src="<?= htmlspecialchars($pais['bandera_url']) ?>"
                                 class="rounded" style="max-height:60px;max-width:120px;object-fit:cover;border:1px solid var(--border-color)">
                        <?php else: ?>
                            <div style="width:80px;height:52px;background:var(--bg-tertiary);border-radius:8px;margin:0 auto;display:flex;align-items:center;justify-content:center;border:1px solid var(--border-color)">
                                <i class="bi bi-flag text-muted fs-4"></i>
                            </div>
                        <?php endif; ?>
                        <div class="fw-700 mt-2" style="font-size:.9rem"><?= htmlspecialchars($pais['nombre_pais']) ?></div>
                        <div class="text-muted" style="font-size:.75rem"><i class="bi bi-calendar3"></i> Turno #<?= $pais['turno_actual'] ?></div>
                    </div>
                    <?php if ($pais['al_mil'] || $pais['al_eco']): ?>
                    <div class="d-flex gap-1 flex-wrap justify-content-center mb-3">
                        <?php if ($pais['al_mil']): ?>
                        <span class="badge bg-danger" style="font-size:.68rem"><i class="bi bi-shield"></i> <?= htmlspecialchars($pais['al_mil']) ?></span>
                        <?php endif; ?>
                        <?php if ($pais['al_eco']): ?>
                        <span class="badge bg-success" style="font-size:.68rem"><i class="bi bi-cash-coin"></i> <?= htmlspecialchars($pais['al_eco']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="d-grid gap-1">
                        <a href="view_country.php?id=<?= $id_pais ?>" class="btn btn-success btn-sm">
                            <i class="bi bi-clipboard-data"></i> Ver Cartilla
                        </a>
                        <a href="enfoques.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-crosshair2"></i> Enfoques
                        </a>
                        <a href="polymarket.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-graph-up-arrow"></i> Polymarket
                        </a>
                        <?php if ($role_id == 4): ?>
                        <a href="profile_history.php" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-clock-history"></i> Historial
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif ($role_id == 4): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="panel-card">
                <div class="panel-header" style="background:var(--bg-tertiary);">
                    <i class="bi bi-geo-alt"></i> Mi País
                </div>
                <div class="panel-body text-center py-4">
                    <i class="bi bi-hourglass-split text-muted mb-2" style="font-size:2.5rem;display:block"></i>
                    <p class="text-muted small mb-0">Sin país asignado.<br>Contacta a un Game Master.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Panel Admin -->
        <?php if ($role_id == 1): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="panel-card">
                <div class="panel-header" style="background:linear-gradient(135deg,#dc3545,#b02a37);color:#fff">
                    <i class="bi bi-shield-lock-fill"></i> Administración
                    <span class="ms-auto badge" style="background:rgba(255,255,255,.2);font-size:.6rem">ADMIN</span>
                </div>
                <div class="panel-body">
                    <p class="text-muted small mb-2">Control total del sistema</p>
                    <div class="d-grid gap-1">
                        <a href="admin_users.php"         class="btn btn-outline-danger btn-sm"><i class="bi bi-people"></i> Usuarios</a>
                        <a href="admin_countries.php"     class="btn btn-outline-danger btn-sm"><i class="bi bi-flag"></i> Países</a>
                        <a href="admin_cartilla.php"      class="btn btn-outline-danger btn-sm"><i class="bi bi-clipboard2-data-fill"></i> Cartillas</a>
                        <a href="admin_cartilla_tipos.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-sliders"></i> Tipos</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Panel GM -->
        <?php if (in_array($role_id, [1, 2])): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="panel-card">
                <div class="panel-header" style="background:linear-gradient(135deg,#0d6efd,#0a58ca);color:#fff">
                    <i class="bi bi-dice-5-fill"></i> Game Master
                    <span class="ms-auto badge" style="background:rgba(255,255,255,.2);font-size:.6rem">GM</span>
                </div>
                <div class="panel-body">
                    <p class="text-muted small mb-2">Gestión y control del juego</p>
                    <div class="d-grid gap-1">
                        <a href="gm_dashboard.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left-right"></i> Asignar Países</a>
                        <a href="gm_turnos.php"    class="btn btn-outline-primary btn-sm"><i class="bi bi-calendar-event"></i> Turnos</a>
                        <a href="gm_research.php"  class="btn btn-outline-primary btn-sm"><i class="bi bi-lightbulb"></i> Investigaciones</a>
                        <a href="polymarket.php"   class="btn btn-outline-primary btn-sm"><i class="bi bi-graph-up-arrow"></i> Polymarket</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Panel Alianzas -->
        <div class="col-sm-6 col-xl-3">
            <div class="panel-card">
                <div class="panel-header" style="background:linear-gradient(135deg,#0dcaf0,#0aa2c0);color:#fff">
                    <i class="bi bi-people-fill"></i> Alianzas
                </div>
                <div class="panel-body">
                    <p class="text-muted small mb-2">Coaliciones militares y económicas</p>
                    <div class="d-grid gap-1">
                        <a href="alianzas.php" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-shield-fill"></i> Ver Alianzas
                        </a>
                        <?php if ($id_pais): ?>
                        <a href="alianzas.php" class="btn btn-info btn-sm">
                            <i class="bi bi-plus-lg"></i> Crear Alianza
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Polymarket -->
        <div class="col-sm-6 col-xl-3">
            <div class="panel-card">
                <div class="panel-header" style="background:linear-gradient(135deg,#ffc107,#d39e00);color:#000">
                    <i class="bi bi-graph-up-arrow"></i> Polymarket
                    <span class="ms-auto badge bg-success" style="font-size:.6rem">Activo</span>
                </div>
                <div class="panel-body text-center py-3">
                    <i class="bi bi-graph-up text-warning mb-2" style="font-size:2rem;display:block"></i>
                    <p class="text-muted small mb-2">Mercado de predicciones sobre eventos del georol</p>
                    <a href="polymarket.php" class="btn btn-warning btn-sm w-100 fw-600">
                        <i class="bi bi-box-arrow-in-right"></i> Entrar
                    </a>
                </div>
            </div>
        </div>

        <!-- Panel Pase de Batalla -->
        <div class="col-sm-6 col-xl-3">
            <div class="panel-card" style="opacity:.7">
                <div class="panel-header" style="background:linear-gradient(135deg,#198754,#146c43);color:#fff">
                    <i class="bi bi-trophy-fill"></i> Pase de Batalla
                    <span class="ms-auto badge bg-dark" style="font-size:.6rem">Próx.</span>
                </div>
                <div class="panel-body text-center py-4">
                    <i class="bi bi-lock-fill text-muted mb-2" style="font-size:2rem;display:block"></i>
                    <p class="text-muted small mb-2">Proximamente</p>
                    <button class="btn btn-success btn-sm w-100" disabled>
                        <i class="bi bi-hourglass-split"></i> En Desarrollo
                    </button>
                </div>
            </div>
        </div>

    </div><!-- /paneles -->

    <!-- ══════════════════════════════
         TABLA DE PAÍSES
    ══════════════════════════════ -->
    <div class="card fade-up" style="animation-delay:.15s">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-globe2"></i>
                <span>Estado Global de Países</span>
                <span class="badge bg-dark">Turno #<?= $turno_actual ?></span>
            </div>
            <div>
                <input type="text" id="searchPaises" class="form-control form-control-sm"
                       style="max-width:200px" placeholder="🔍 Buscar..."
                       oninput="filtrarPaises(this.value)">
            </div>
        </div>
        <?php
        $paises_list = $conn->query("
            SELECT p.id_pais, p.nombre_pais, p.turno_actual, p.bandera_url,
                   u.username,
                   am.nombre_alianza as alianza_militar, am.aprobada as mil_apro,
                   ae.nombre_alianza as alianza_economica, ae.aprobada as eco_apro,
                   ef.nombre_enfoque, ef.tipo_enfoque
            FROM paises p
            LEFT JOIN usuarios u  ON p.id_pais = u.id_pais
            LEFT JOIN alianzas am ON p.id_alianza_militar   = am.id_alianza
            LEFT JOIN alianzas ae ON p.id_alianza_economica = ae.id_alianza
            LEFT JOIN enfoques ef ON p.id_enfoque_activo    = ef.id_enfoque
            ORDER BY p.nombre_pais
        ")->fetchAll(PDO::FETCH_ASSOC);

        $enfoque_colors = [
            'Atacante' => 'danger',
            'Defensor' => 'info',
            'IC'       => 'warning',
            'IM'       => 'dark',
            'IT'       => 'secondary',
        ];
        ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:52px" class="text-center">Bandera</th>
                        <th>País</th>
                        <th>Jugador</th>
                        <th>Alianzas</th>
                        <th>Enfoque</th>
                        <th class="text-center" style="width:90px">Turno</th>
                        <th class="text-end" style="width:80px">Ver</th>
                    </tr>
                </thead>
                <tbody id="paisesTableBody">
                <?php foreach ($paises_list as $p):
                    $pFlag = !empty($p['bandera_url']) && $p['bandera_url'] !== 'default_flag.png';
                ?>
                <tr class="country-row"
                    data-nombre="<?= strtolower(htmlspecialchars($p['nombre_pais'])) ?>"
                    <?= $pFlag ? 'data-flag="'.htmlspecialchars($p['bandera_url']).'"' : '' ?>>
                    <td class="text-center">
                        <?php if ($pFlag): ?>
                            <img src="<?= htmlspecialchars($p['bandera_url']) ?>" class="flag-thumb" alt="">
                        <?php else: ?>
                            <div class="flag-placeholder mx-auto"><i class="bi bi-flag"></i></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="flag-dot"></span>
                            <strong><?= htmlspecialchars($p['nombre_pais']) ?></strong>
                        </div>
                    </td>

                    <td>
                        <?php if ($p['username']): ?>
                            <span class="badge bg-secondary" style="font-size:.7rem">
                                <i class="bi bi-person"></i> <?= htmlspecialchars($p['username']) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted small"><em>Sin asignar</em></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <?php if ($p['alianza_militar']): ?>
                                <span class="badge bg-danger" style="font-size:.67rem">
                                    <i class="bi bi-shield"></i> <?= htmlspecialchars($p['alianza_militar']) ?>
                                    <?= !$p['mil_apro'] ? ' <i class="bi bi-clock text-warning"></i>' : '' ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($p['alianza_economica']): ?>
                                <span class="badge bg-success" style="font-size:.67rem">
                                    <i class="bi bi-cash-coin"></i> <?= htmlspecialchars($p['alianza_economica']) ?>
                                    <?= !$p['eco_apro'] ? ' <i class="bi bi-clock text-warning"></i>' : '' ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!$p['alianza_militar'] && !$p['alianza_economica']): ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php if ($p['nombre_enfoque']): ?>
                            <span class="badge bg-<?= $enfoque_colors[$p['tipo_enfoque']] ?? 'secondary' ?>" style="font-size:.67rem">
                                <?= htmlspecialchars($p['nombre_enfoque']) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-dark" style="font-size:.8rem">#<?= $p['turno_actual'] ?></span>
                    </td>
                    <td class="text-end">
                        <a href="view_country.php?id=<?= $p['id_pais'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-header" style="border-top:1px solid var(--border-color);border-bottom:none;font-size:.78rem">
            <i class="bi bi-info-circle"></i>
            <span id="countInfo"><?= count($paises_list) ?> países registrados</span>
        </div>
    </div><!-- /card -->

</div><!-- /container -->

<script>
/* ── Search ── */
function filtrarPaises(q) {
    q = q.toLowerCase().trim();
    const rows = document.querySelectorAll('#paisesTableBody .country-row');
    let visible = 0;
    rows.forEach(tr => {
        const show = (tr.dataset.nombre || '').includes(q);
        tr.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('countInfo').textContent = visible + ' países mostrados';
}

/* ── Per-row flag colors + page background blur ── */
document.addEventListener('DOMContentLoaded', function () {
    const layer = document.getElementById('pageBgLayer');
    const rows  = document.querySelectorAll('#paisesTableBody .country-row[data-flag]');

    // Apply color to each row (dot indicator + hover tint)
    rows.forEach(function (tr) {
        const flagSrc = tr.dataset.flag;
        if (!flagSrc || typeof window.applyFlagColor !== 'function') return;

        window.applyFlagColor(flagSrc, tr, {
            pageBlur: false, // per-row only, not global
            onApply: function (r, g, b, h, s, l) {
                const dot = tr.querySelector('.flag-dot');
                if (dot) {
                    dot.style.background = `hsl(${h},${s}%,${l}%)`;
                    dot.style.boxShadow  = `0 0 8px hsla(${h},${s}%,${l}%,0.5)`;
                }
                tr.style.setProperty('--flag-color-dim',    `hsla(${h},${s}%,${l}%,0.08)`);
                tr.style.setProperty('--flag-color-border', `hsla(${h},${s}%,${l}%,0.40)`);
            }
        });

        // On hover → update the page background blur with this country's flag
        tr.addEventListener('mouseenter', function () {
            if (!layer) return;
            layer.style.backgroundImage = `url('${flagSrc}')`;
            layer.classList.add('active');
        });
    });

    // On mouse-leave from the whole table → fade out to default
    const tableBody = document.getElementById('paisesTableBody');
    if (tableBody && layer) {
        tableBody.addEventListener('mouseleave', function () {
            layer.classList.remove('active');
            // Keep a subtle ambient from the first flag
            setTimeout(function () {
                const firstFlag = tableBody.querySelector('.country-row[data-flag]');
                if (firstFlag) {
                    layer.style.backgroundImage = `url('${firstFlag.dataset.flag}')`;
                    layer.classList.add('active');
                }
            }, 800);
        });

        // Set initial blur from first available flag
        const firstFlag = tableBody.querySelector('.country-row[data-flag]');
        if (firstFlag) {
            layer.style.backgroundImage = `url('${firstFlag.dataset.flag}')`;
            layer.classList.add('active');
        }
    }
});
</script>


<?php require_once 'includes/footer.php'; ?>
