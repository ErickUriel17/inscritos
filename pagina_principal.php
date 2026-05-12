<?php
// ============================================================
// CRM Universitario — Panel Principal (Dashboard)
// CAMBIOS: Reconstrucción completa. Se reemplazó el iframe y
// los enlaces placeholder por un dashboard funcional con:
// pipeline de aspirantes, tabla con filtros, historial de
// interacciones, agenda/recordatorios y formulario de registro.
// ============================================================
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . "/assets/sentenciasSQL/Aspirante.php";
require_once __DIR__ . "/assets/sentenciasSQL/Historial.php";
require_once __DIR__ . "/assets/sentenciasSQL/Agenda.php";
require_once __DIR__ . "/assets/sentenciasSQL/Carrera.php";

// --- Logout ---
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$aspiranteModel = new Aspirante();
$historialModel = new Historial();
$agendaModel    = new Agenda();
$carreraModel   = new Carrera();
$becaModel      = new Beca();

// --- Datos iniciales ---
$pipeline    = $aspiranteModel->contarPorEtapa();
$semana      = $aspiranteModel->contarNuevosEstaSemana();
$aspirantes  = $aspiranteModel->listar();
$historial   = $historialModel->obtenerRecientes(8);
$proximosTmp = $agendaModel->obtenerProximos(6);
$carreras    = $carreraModel->listarActivas();
$becas       = $becaModel->listarActivas();

$usuario_nombre = $_SESSION['nombre'] ?? $_SESSION['usuario'];

// iconos por tipo historial
function iconoTipo(string $tipo): string {
    return match($tipo) {
        'llamada' => '📞',
        'correo'  => '✉️',
        'visita'  => '🏢',
        default   => '📝',
    };
}

function iconoAgenda(string $tipo): string {
    return match($tipo) {
        'llamada' => '📞',
        'correo'  => '✉️',
        'reunion' => '🤝',
        default   => '✅',
    };
}

function colorEtapa(string $etapa): string {
    return match($etapa) {
        'Inscrito'   => '#28a745',
        'Interesado' => '#e07000',
        default      => '#0077cc',
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Universitario — Panel</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap');
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-w: 220px;
            --blue-dark: #0f2d5e;
            --blue:      #1a4a8a;
            --blue-light:#eef3fb;
            --orange:    #f08c00;
            --green:     #28a745;
            --gray-bg:   #f3f5fb;
            --white:     #ffffff;
            --text:      #2d3748;
            --text-light:#718096;
            --border:    #e2e8f0;
            --radius:    12px;
            --shadow:    0 2px 12px rgba(0,0,0,0.08);
        }

        body {
            font-family: 'Nunito', Arial, sans-serif;
            background: var(--gray-bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* ---- SIDEBAR ---- */
        .sidebar {
            width: var(--sidebar-w);
            background: linear-gradient(180deg, var(--blue-dark) 0%, var(--blue) 100%);
            position: fixed;
            top: 0; left: 0; bottom: 0;
            display: flex;
            flex-direction: column;
            padding: 0;
            z-index: 100;
            box-shadow: 4px 0 16px rgba(0,0,0,0.18);
        }

        .sidebar-brand {
            padding: 22px 20px 18px;
            border-bottom: 1px solid rgba(255,255,255,0.12);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-brand .brand-icon {
            width: 38px; height: 38px;
            background: rgba(255,255,255,0.18);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .sidebar-brand .brand-text h2 {
            font-size: 14px; font-weight: 800; color: white; line-height: 1.2;
        }
        .sidebar-brand .brand-text span {
            font-size: 11px; color: rgba(255,255,255,0.55); font-weight: 600;
        }

        .sidebar-user {
            padding: 14px 20px;
            display: flex; align-items: center; gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.10);
        }
        .sidebar-user .avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; flex-shrink: 0;
        }
        .sidebar-user .uname { font-size: 13px; font-weight: 700; color: white; }
        .sidebar-user .urole { font-size: 11px; color: rgba(255,255,255,0.5); }

        .sidebar-menu { padding: 14px 12px; flex: 1; overflow-y: auto; }
        .sidebar-menu .menu-label {
            font-size: 10px; font-weight: 800; color: rgba(255,255,255,0.35);
            text-transform: uppercase; letter-spacing: 1px;
            padding: 0 8px; margin: 12px 0 6px;
        }

        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 10px;
            color: rgba(255,255,255,0.72); font-size: 14px; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
            margin-bottom: 2px; text-decoration: none;
        }
        .nav-item:hover { background: rgba(255,255,255,0.12); color: white; }
        .nav-item.active { background: rgba(255,255,255,0.18); color: white; font-weight: 700; }
        .nav-item .ni-icon { font-size: 17px; width: 22px; text-align: center; }

        .sidebar-bottom {
            padding: 14px 12px;
            border-top: 1px solid rgba(255,255,255,0.10);
        }
        .btn-logout {
            width: 100%; padding: 10px;
            background: rgba(255,80,80,0.2);
            color: #ff9898; border: 1px solid rgba(255,80,80,0.3);
            border-radius: 10px; font-size: 13px; font-weight: 700;
            font-family: inherit; cursor: pointer; transition: all 0.2s;
        }
        .btn-logout:hover { background: rgba(255,80,80,0.35); color: white; }

        /* ---- MAIN CONTENT ---- */
        .main-content {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Top bar */
        .topbar {
            background: white;
            padding: 14px 28px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 50;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .topbar h1 { font-size: 18px; font-weight: 800; color: var(--blue-dark); }
        .topbar .topbar-sub { font-size: 12px; color: var(--text-light); margin-top: 2px; }
        .topbar-actions { display: flex; align-items: center; gap: 10px; }
        .topbar-badge {
            background: var(--gray-bg); padding: 6px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 700; color: var(--text-light);
        }

        /* Content area */
        .content-area { padding: 24px 28px; flex: 1; }

        /* ---- PIPELINE METRICS ---- */
        .pipeline-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr) auto;
            gap: 14px;
            margin-bottom: 22px;
            align-items: center;
        }

        .metric-card {
            border-radius: var(--radius);
            padding: 16px 20px;
            color: white;
            display: flex; flex-direction: column;
            cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: var(--shadow);
        }
        .metric-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.15); }
        .metric-card.contacto  { background: linear-gradient(135deg, #f5a623, #e07b00); }
        .metric-card.interesado{ background: linear-gradient(135deg, #e07000, #b85a00); }
        .metric-card.inscrito  { background: linear-gradient(135deg, #2ca853, #1e7e3a); }

        .metric-num {
            font-size: 36px; font-weight: 800; line-height: 1;
            display: inline-block; margin-right: 8px;
        }
        .metric-label { font-size: 14px; font-weight: 700; opacity: 0.95; }
        .metric-delta { font-size: 12px; opacity: 0.80; margin-top: 4px; }

        .total-box {
            background: white; border-radius: var(--radius);
            padding: 16px 22px; text-align: right;
            box-shadow: var(--shadow);
        }
        .total-box .total-label { font-size: 13px; font-weight: 700; color: var(--text-light); }
        .total-box .total-num   { font-size: 40px; font-weight: 800; color: var(--blue-dark); line-height: 1.1; }

        /* ---- MAIN GRID (tabla + formulario) ---- */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 310px;
            gap: 18px;
        }

        /* Cards */
        .card {
            background: white; border-radius: var(--radius);
            box-shadow: var(--shadow); overflow: hidden;
        }
        .card-header {
            padding: 16px 20px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid var(--border);
        }
        .card-header h3 { font-size: 15px; font-weight: 800; color: var(--blue-dark); }
        .card-body { padding: 16px 20px; }

        /* Filters */
        .filters-row {
            display: flex; gap: 8px; align-items: center; flex-wrap: wrap;
            padding: 12px 16px;
            background: var(--gray-bg);
            border-bottom: 1px solid var(--border);
        }
        .filters-row select,
        .filters-row input {
            padding: 7px 12px; border: 1.5px solid var(--border);
            border-radius: 8px; font-size: 13px; font-family: inherit;
            background: white; color: var(--text); outline: none;
            transition: border-color 0.2s;
        }
        .filters-row select:focus,
        .filters-row input:focus { border-color: var(--blue); }
        .filters-row input { flex: 1; min-width: 160px; }
        .btn-sm {
            padding: 7px 14px; border-radius: 8px; font-size: 13px;
            font-weight: 700; font-family: inherit; cursor: pointer;
            border: none; transition: all 0.2s;
        }
        .btn-primary { background: var(--blue); color: white; }
        .btn-primary:hover { background: var(--blue-dark); }
        .btn-danger { background: #fff0f0; color: #c0392b; border: 1px solid #ffcccc; }
        .btn-danger:hover { background: #c0392b; color: white; }
        .btn-success { background: #e8f5e9; color: #1e7e3a; border: 1px solid #c3e6cb; }
        .btn-success:hover { background: #1e7e3a; color: white; }

        /* Table */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th {
            padding: 10px 14px; text-align: left;
            background: var(--gray-bg); color: var(--text-light);
            font-weight: 700; font-size: 12px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        td {
            padding: 11px 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafbff; }

        .etapa-badge {
            display: inline-block;
            padding: 4px 11px; border-radius: 20px;
            font-size: 12px; font-weight: 700; color: white;
        }

        .action-btns { display: flex; gap: 6px; }
        .action-btns button {
            border: none; border-radius: 6px; padding: 5px 10px;
            font-size: 12px; cursor: pointer; font-family: inherit; font-weight: 700;
        }
        .btn-edit { background: #e8f0fe; color: #1a4a8a; }
        .btn-edit:hover { background: #1a4a8a; color: white; }
        .btn-del  { background: #fff0f0; color: #c0392b; }
        .btn-del:hover  { background: #c0392b; color: white; }

        .empty-table { text-align: center; padding: 30px; color: var(--text-light); }

        /* Registration form */
        .form-group { margin-bottom: 13px; }
        .form-group label {
            display: block; font-size: 12px; font-weight: 700;
            color: var(--text-light); margin-bottom: 5px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%; padding: 9px 12px;
            border: 1.5px solid var(--border); border-radius: 8px;
            font-size: 13px; font-family: inherit; color: var(--text);
            outline: none; transition: border-color 0.2s; background: #fafbff;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus { border-color: var(--blue); background: white; }

        .form-group textarea { resize: vertical; min-height: 60px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

        .btn-guardar {
            width: 100%; padding: 12px;
            background: linear-gradient(135deg, var(--blue), var(--blue-dark));
            color: white; border: none; border-radius: 10px;
            font-size: 14px; font-weight: 700; font-family: inherit;
            cursor: pointer; transition: all 0.2s;
            box-shadow: 0 3px 10px rgba(15,45,94,0.25);
        }
        .btn-guardar:hover { transform: translateY(-1px); box-shadow: 0 5px 15px rgba(15,45,94,0.35); }

        /* Bottom panels */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-top: 18px;
        }

        /* Historial items */
        .hist-item {
            display: flex; gap: 10px; align-items: flex-start;
            padding: 10px 0; border-bottom: 1px solid var(--border);
        }
        .hist-item:last-child { border-bottom: none; }
        .hist-icon {
            width: 34px; height: 34px; border-radius: 50%;
            background: var(--gray-bg);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; flex-shrink: 0;
        }
        .hist-body { flex: 1; min-width: 0; }
        .hist-name { font-size: 13px; font-weight: 700; color: var(--text); }
        .hist-desc { font-size: 12px; color: var(--text-light); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .hist-date { font-size: 11px; color: var(--text-light); white-space: nowrap; }

        /* Agenda items */
        .agenda-item {
            display: flex; gap: 10px; align-items: center;
            padding: 10px 0; border-bottom: 1px solid var(--border);
        }
        .agenda-item:last-child { border-bottom: none; }
        .agenda-dot {
            width: 10px; height: 10px; border-radius: 50%;
            background: var(--orange); flex-shrink: 0;
        }
        .agenda-dot.green { background: var(--green); }
        .agenda-dot.blue  { background: var(--blue); }
        .agenda-body { flex: 1; min-width: 0; }
        .agenda-title { font-size: 13px; font-weight: 700; }
        .agenda-sub   { font-size: 12px; color: var(--text-light); }

        /* Toast notification */
        #toast {
            position: fixed; bottom: 24px; right: 24px;
            background: #1e7e3a; color: white;
            padding: 12px 22px; border-radius: 10px;
            font-size: 14px; font-weight: 700;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            opacity: 0; pointer-events: none;
            transition: opacity 0.3s;
            z-index: 999;
        }
        #toast.show { opacity: 1; }
        #toast.error { background: #c0392b; }

        /* Modal overlay */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.45); z-index: 200;
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: white; border-radius: 16px;
            padding: 28px; width: 440px; max-width: 95vw;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            animation: popIn 0.25s ease;
        }
        @keyframes popIn {
            from { opacity: 0; transform: scale(0.9); }
            to   { opacity: 1; transform: scale(1); }
        }
        .modal h3 { font-size: 17px; font-weight: 800; margin-bottom: 18px; color: var(--blue-dark); }

        /* Scroll helpers */
        .scroll-area { max-height: 260px; overflow-y: auto; }
    </style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">🎓</div>
        <div class="brand-text">
            <h2>CRM Universitario</h2>
            <span>Panel Administrativo</span>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="avatar">👤</div>
        <div>
            <div class="uname"><?= htmlspecialchars($usuario_nombre) ?></div>
            <div class="urole">Administrador</div>
        </div>
    </div>

    <nav class="sidebar-menu">
        <div class="menu-label">Principal</div>
        <a class="nav-item active" onclick="mostrarSeccion('dashboard')">
            <span class="ni-icon">🏠</span> Panel Principal
        </a>
        <a class="nav-item" onclick="mostrarSeccion('aspirantes')">
            <span class="ni-icon">👥</span> Aspirantes
        </a>
        <a class="nav-item" onclick="mostrarSeccion('historial')">
            <span class="ni-icon">📋</span> Historial
        </a>
        <a class="nav-item" onclick="mostrarSeccion('agenda')">
            <span class="ni-icon">📅</span> Agenda
        </a>

        <div class="menu-label">Catálogos</div>
        <a class="nav-item">
            <span class="ni-icon">🎓</span> Carreras
        </a>
        <a class="nav-item">
            <span class="ni-icon">💰</span> Becas
        </a>

        <div class="menu-label">Sistema</div>
        <a class="nav-item">
            <span class="ni-icon">📊</span> Reportes
        </a>
        <a class="nav-item">
            <span class="ni-icon">⚙️</span> Configuración
        </a>
    </nav>

    <div class="sidebar-bottom">
        <form method="post">
            <button type="submit" name="logout" class="btn-logout">🚪 Cerrar sesión</button>
        </form>
    </div>
</aside>

<!-- ===== MAIN ===== -->
<main class="main-content">

    <div class="topbar">
        <div>
            <h1 id="topbar-title">Panel Principal</h1>
            <div class="topbar-sub">Bienvenido, <?= htmlspecialchars($usuario_nombre) ?></div>
        </div>
        <div class="topbar-actions">
            <span class="topbar-badge">📅 <?= date('d M Y') ?></span>
        </div>
    </div>

    <div class="content-area">

        <!-- === PIPELINE METRICS === -->
        <div class="pipeline-row">
            <div class="metric-card contacto" onclick="filtrarEtapa('Contacto')">
                <div>
                    <span class="metric-num"><?= $pipeline['Contacto'] ?></span>
                    <span class="metric-label">En Contacto</span>
                </div>
                <div class="metric-delta">+<?= $semana['Contacto'] ?> esta semana</div>
            </div>
            <div class="metric-card interesado" onclick="filtrarEtapa('Interesado')">
                <div>
                    <span class="metric-num"><?= $pipeline['Interesado'] ?></span>
                    <span class="metric-label">Interesados</span>
                </div>
                <div class="metric-delta">+<?= $semana['Interesado'] ?> esta semana</div>
            </div>
            <div class="metric-card inscrito" onclick="filtrarEtapa('Inscrito')">
                <div>
                    <span class="metric-num"><?= $pipeline['Inscrito'] ?></span>
                    <span class="metric-label">Inscritos</span>
                </div>
                <div class="metric-delta">+<?= $semana['Inscrito'] ?> esta semana</div>
            </div>
            <div class="total-box">
                <div class="total-label">Aspirantes Totales</div>
                <div class="total-num"><?= $pipeline['total'] ?></div>
            </div>
        </div>

        <!-- === DASHBOARD GRID === -->
        <div class="dashboard-grid">

            <!-- Lista de Aspirantes -->
            <div class="card">
                <div class="card-header">
                    <h3>📋 Lista de Aspirantes</h3>
                    <button class="btn-sm btn-primary" onclick="abrirModalAgregar()">+ Agregar</button>
                </div>

                <div class="filters-row">
                    <select id="filtro-etapa" onchange="filtrarTabla()">
                        <option value="">Todas las etapas</option>
                        <option value="Contacto">Contacto</option>
                        <option value="Interesado">Interesado</option>
                        <option value="Inscrito">Inscrito</option>
                    </select>
                    <select id="filtro-carrera" onchange="filtrarTabla()">
                        <option value="">Todas las carreras</option>
                        <?php foreach ($carreras as $c): ?>
                            <option value="<?= $c['id_carrera'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" id="filtro-busqueda" placeholder="🔍 Buscar nombre o email..." oninput="filtrarTabla()">
                </div>

                <div class="table-wrap">
                    <table id="tabla-aspirantes">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Carrera</th>
                                <th>Etapa</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-aspirantes">
                            <?php if (empty($aspirantes)): ?>
                                <tr><td colspan="6" class="empty-table">No hay aspirantes registrados aún.</td></tr>
                            <?php else: ?>
                                <?php foreach ($aspirantes as $a): ?>
                                    <tr data-etapa="<?= $a['etapa'] ?>" data-carrera="<?= $a['id_carrera'] ?>"
                                        data-nombre="<?= strtolower($a['nombre']) ?>" data-email="<?= strtolower($a['email']) ?>">
                                        <td><strong><?= htmlspecialchars($a['nombre']) ?></strong></td>
                                        <td><?= htmlspecialchars($a['email']) ?></td>
                                        <td><?= htmlspecialchars($a['telefono'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($a['carrera'] ?? '—') ?></td>
                                        <td>
                                            <span class="etapa-badge" style="background:<?= colorEtapa($a['etapa']) ?>">
                                                <?= $a['etapa'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-btns">
                                                <button class="btn-edit" onclick="editarAspirante(<?= $a['id_aspirante'] ?>)">✏️ Editar</button>
                                                <button class="btn-del"  onclick="eliminarAspirante(<?= $a['id_aspirante'] ?>, '<?= addslashes($a['nombre']) ?>')">🗑️</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Formulario de Registro -->
            <div class="card">
                <div class="card-header">
                    <h3>📝 Formulario de Registro</h3>
                </div>
                <div class="card-body">
                    <form id="form-registro">
                        <input type="hidden" id="reg-id" name="id" value="">

                        <div class="form-group">
                            <label>Nombre completo</label>
                            <input type="text" id="reg-nombre" name="nombre" placeholder="Nombre del aspirante" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="reg-email" name="email" placeholder="correo@ejemplo.com" required>
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" id="reg-telefono" name="telefono" placeholder="555 000 0000">
                        </div>
                        <div class="form-group">
                            <label>Carrera de Interés</label>
                            <select id="reg-carrera" name="id_carrera">
                                <option value="">— Seleccione carrera —</option>
                                <?php foreach ($carreras as $c): ?>
                                    <option value="<?= $c['id_carrera'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Etapa</label>
                            <select id="reg-etapa" name="etapa">
                                <option value="Contacto">Contacto</option>
                                <option value="Interesado">Interesado</option>
                                <option value="Inscrito">Inscrito</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Asignar Beca</label>
                                <select id="reg-beca" name="id_beca">
                                    <option value="">No asignada</option>
                                    <?php foreach ($becas as $b): ?>
                                        <option value="<?= $b['id_beca'] ?>"><?= htmlspecialchars($b['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Descuento %</label>
                                <input type="number" id="reg-descuento" name="descuento" min="0" max="100" value="0" placeholder="0">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Notas</label>
                            <textarea id="reg-notas" name="notas" placeholder="Observaciones adicionales..."></textarea>
                        </div>
                        <button type="button" onclick="guardarAspirante()" class="btn-guardar">💾 Guardar Aspirante</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Bottom panels -->
        <div class="bottom-grid">

            <!-- Historial -->
            <div class="card">
                <div class="card-header">
                    <h3>🗂️ Historial de Interacciones</h3>
                    <button class="btn-sm btn-primary" onclick="abrirModalHistorial()">+ Agregar</button>
                </div>
                <div class="card-body scroll-area">
                    <?php if (empty($historial)): ?>
                        <p style="color:var(--text-light); text-align:center; padding:20px;">Sin interacciones recientes.</p>
                    <?php else: ?>
                        <?php foreach ($historial as $h): ?>
                            <div class="hist-item">
                                <div class="hist-icon"><?= iconoTipo($h['tipo']) ?></div>
                                <div class="hist-body">
                                    <div class="hist-name"><?= htmlspecialchars($h['aspirante_nombre']) ?></div>
                                    <div class="hist-desc"><?= htmlspecialchars($h['descripcion']) ?></div>
                                </div>
                                <div class="hist-date"><?= date('d M', strtotime($h['fecha'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Agenda -->
            <div class="card">
                <div class="card-header">
                    <h3>📅 Agenda y Recordatorios</h3>
                    <button class="btn-sm btn-primary" onclick="abrirModalAgenda()">+ Nuevo</button>
                </div>
                <div class="card-body scroll-area">
                    <?php if (empty($proximosTmp)): ?>
                        <p style="color:var(--text-light); text-align:center; padding:20px;">Sin eventos próximos.</p>
                    <?php else: ?>
                        <?php foreach ($proximosTmp as $ev): ?>
                            <div class="agenda-item">
                                <div class="agenda-dot <?= $ev['tipo'] === 'llamada' ? '' : ($ev['tipo'] === 'correo' ? 'blue' : 'green') ?>"></div>
                                <div class="agenda-body">
                                    <div class="agenda-title"><?= htmlspecialchars($ev['titulo']) ?></div>
                                    <div class="agenda-sub">
                                        <?= $ev['aspirante_nombre'] ? htmlspecialchars($ev['aspirante_nombre']) . ' · ' : '' ?>
                                        <?= date('d M H:i', strtotime($ev['fecha_hora'])) ?>
                                    </div>
                                </div>
                                <button class="btn-sm btn-success" onclick="completarAgenda(<?= $ev['id_agenda'] ?>, this)" title="Marcar completado">✓</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /content-area -->
</main>

<!-- ===== MODAL: Agregar/Editar (ya integrado en el form lateral) ===== -->

<!-- ===== MODAL: Nueva interacción de historial ===== -->
<div class="modal-overlay" id="modal-historial">
    <div class="modal">
        <h3>📝 Agregar Interacción</h3>
        <div class="form-group">
            <label>Aspirante</label>
            <select id="hist-aspirante">
                <?php foreach ($aspirantes as $a): ?>
                    <option value="<?= $a['id_aspirante'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Tipo</label>
            <select id="hist-tipo">
                <option value="nota">📝 Nota</option>
                <option value="llamada">📞 Llamada</option>
                <option value="correo">✉️ Correo</option>
                <option value="visita">🏢 Visita</option>
            </select>
        </div>
        <div class="form-group">
            <label>Descripción</label>
            <textarea id="hist-descripcion" placeholder="Describe la interacción..." style="height:80px"></textarea>
        </div>
        <div style="display:flex; gap:10px; margin-top:6px;">
            <button class="btn-sm btn-primary" style="flex:1; padding:11px" onclick="guardarHistorial()">Guardar</button>
            <button class="btn-sm" style="flex:1; padding:11px; background:var(--gray-bg);" onclick="cerrarModal('modal-historial')">Cancelar</button>
        </div>
    </div>
</div>

<!-- ===== MODAL: Nuevo evento de agenda ===== -->
<div class="modal-overlay" id="modal-agenda">
    <div class="modal">
        <h3>📅 Nuevo Recordatorio</h3>
        <div class="form-group">
            <label>Título</label>
            <input type="text" id="ag-titulo" placeholder="Descripción del evento">
        </div>
        <div class="form-group">
            <label>Tipo</label>
            <select id="ag-tipo">
                <option value="tarea">✅ Tarea</option>
                <option value="llamada">📞 Llamada</option>
                <option value="correo">✉️ Correo</option>
                <option value="reunion">🤝 Reunión</option>
            </select>
        </div>
        <div class="form-group">
            <label>Fecha y hora</label>
            <input type="datetime-local" id="ag-fecha">
        </div>
        <div class="form-group">
            <label>Aspirante (opcional)</label>
            <select id="ag-aspirante">
                <option value="">— Sin aspirante específico —</option>
                <?php foreach ($aspirantes as $a): ?>
                    <option value="<?= $a['id_aspirante'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex; gap:10px; margin-top:6px;">
            <button class="btn-sm btn-primary" style="flex:1; padding:11px" onclick="guardarAgenda()">Guardar</button>
            <button class="btn-sm" style="flex:1; padding:11px; background:var(--gray-bg);" onclick="cerrarModal('modal-agenda')">Cancelar</button>
        </div>
    </div>
</div>

<!-- ===== MODAL: Confirmar eliminación ===== -->
<div class="modal-overlay" id="modal-confirm">
    <div class="modal" style="max-width:360px; text-align:center">
        <div style="font-size:48px; margin-bottom:10px;">⚠️</div>
        <h3 id="confirm-msg" style="font-size:15px; margin-bottom:18px; font-weight:700;"></h3>
        <div style="display:flex; gap:10px;">
            <button class="btn-sm btn-danger" style="flex:1; padding:11px" id="confirm-yes">Eliminar</button>
            <button class="btn-sm" style="flex:1; padding:11px; background:var(--gray-bg);" onclick="cerrarModal('modal-confirm')">Cancelar</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast"></div>

<script>
// ============================================================
// JavaScript del Dashboard
// ============================================================

function toast(msg, tipo = 'ok') {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.className = tipo === 'error' ? 'error show' : 'show';
    setTimeout(() => el.className = '', 3000);
}

function cerrarModal(id) {
    document.getElementById(id).classList.remove('open');
}

function abrirModal(id) {
    document.getElementById(id).classList.add('open');
}

// Cerrar modales al click fuera
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.classList.remove('open');
    });
});

// ---- Filtros de tabla ----
function filtrarTabla() {
    const etapa    = document.getElementById('filtro-etapa').value.toLowerCase();
    const carrera  = document.getElementById('filtro-carrera').value;
    const busqueda = document.getElementById('filtro-busqueda').value.toLowerCase();

    document.querySelectorAll('#tbody-aspirantes tr[data-etapa]').forEach(row => {
        const ok = (!etapa    || row.dataset.etapa.toLowerCase() === etapa)
                && (!carrera  || row.dataset.carrera === carrera)
                && (!busqueda || row.dataset.nombre.includes(busqueda) || row.dataset.email.includes(busqueda));
        row.style.display = ok ? '' : 'none';
    });
}

function filtrarEtapa(etapa) {
    document.getElementById('filtro-etapa').value = etapa;
    filtrarTabla();
}

// ---- Formulario de registro ----
function limpiarForm() {
    document.getElementById('reg-id').value       = '';
    document.getElementById('reg-nombre').value   = '';
    document.getElementById('reg-email').value    = '';
    document.getElementById('reg-telefono').value = '';
    document.getElementById('reg-carrera').value  = '';
    document.getElementById('reg-etapa').value    = 'Contacto';
    document.getElementById('reg-beca').value     = '';
    document.getElementById('reg-descuento').value= '0';
    document.getElementById('reg-notas').value    = '';
}

function abrirModalAgregar() {
    limpiarForm();
    document.querySelector('.card-header h3').textContent = '📝 Nuevo Aspirante';
}

async function editarAspirante(id) {
    const resp = await fetch(`assets/api/aspirantes_api.php?accion=obtener&id=${id}`);
    const data = await resp.json();
    if (!data.ok) { toast('Error al cargar datos', 'error'); return; }
    const a = data.aspirante;
    document.getElementById('reg-id').value        = a.id_aspirante;
    document.getElementById('reg-nombre').value    = a.nombre;
    document.getElementById('reg-email').value     = a.email;
    document.getElementById('reg-telefono').value  = a.telefono || '';
    document.getElementById('reg-carrera').value   = a.id_carrera || '';
    document.getElementById('reg-etapa').value     = a.etapa;
    document.getElementById('reg-beca').value      = a.id_beca || '';
    document.getElementById('reg-descuento').value = a.descuento_aplicado || 0;
    document.getElementById('reg-notas').value     = a.notas || '';
    document.querySelector('.form-registro .card-header h3').textContent = '✏️ Editar Aspirante';
    document.getElementById('reg-nombre').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

async function guardarAspirante() {
    const id       = document.getElementById('reg-id').value;
    const nombre   = document.getElementById('reg-nombre').value.trim();
    const email    = document.getElementById('reg-email').value.trim();
    if (!nombre || !email) { toast('Nombre y email son obligatorios', 'error'); return; }

    const datos = {
        accion:    id ? 'actualizar' : 'crear',
        id,
        nombre,
        email,
        telefono:  document.getElementById('reg-telefono').value,
        id_carrera:document.getElementById('reg-carrera').value,
        etapa:     document.getElementById('reg-etapa').value,
        id_beca:   document.getElementById('reg-beca').value,
        descuento: document.getElementById('reg-descuento').value,
        notas:     document.getElementById('reg-notas').value,
    };

    const resp = await fetch('assets/api/aspirantes_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    });
    const data = await resp.json();
    if (data.ok) {
        toast(id ? '✅ Aspirante actualizado' : '✅ Aspirante registrado');
        limpiarForm();
        setTimeout(() => location.reload(), 1200);
    } else {
        toast(data.mensaje || 'Error al guardar', 'error');
    }
}

// ---- Eliminar aspirante ----
let pendingDeleteId = null;

function eliminarAspirante(id, nombre) {
    pendingDeleteId = id;
    document.getElementById('confirm-msg').textContent = `¿Eliminar a ${nombre}? Esta acción no se puede deshacer.`;
    abrirModal('modal-confirm');
    document.getElementById('confirm-yes').onclick = async () => {
        const resp = await fetch('assets/api/aspirantes_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'eliminar', id: pendingDeleteId })
        });
        const data = await resp.json();
        cerrarModal('modal-confirm');
        if (data.ok) {
            toast('🗑️ Aspirante eliminado');
            setTimeout(() => location.reload(), 1000);
        } else {
            toast('Error al eliminar', 'error');
        }
    };
}

// ---- Historial ----
function abrirModalHistorial() { abrirModal('modal-historial'); }

async function guardarHistorial() {
    const datos = {
        id_aspirante: document.getElementById('hist-aspirante').value,
        tipo:         document.getElementById('hist-tipo').value,
        descripcion:  document.getElementById('hist-descripcion').value.trim(),
    };
    if (!datos.descripcion) { toast('Escribe una descripción', 'error'); return; }
    const resp = await fetch('assets/api/historial_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    });
    const data = await resp.json();
    cerrarModal('modal-historial');
    if (data.ok) {
        toast('✅ Interacción registrada');
        setTimeout(() => location.reload(), 1000);
    } else {
        toast('Error al guardar', 'error');
    }
}

// ---- Agenda ----
function abrirModalAgenda() {
    // Fecha default: mañana a las 9am
    const tm = new Date(); tm.setDate(tm.getDate()+1); tm.setHours(9,0,0,0);
    document.getElementById('ag-fecha').value = tm.toISOString().slice(0,16);
    abrirModal('modal-agenda');
}

async function guardarAgenda() {
    const datos = {
        titulo:       document.getElementById('ag-titulo').value.trim(),
        tipo:         document.getElementById('ag-tipo').value,
        fecha_hora:   document.getElementById('ag-fecha').value,
        id_aspirante: document.getElementById('ag-aspirante').value,
    };
    if (!datos.titulo || !datos.fecha_hora) { toast('Completa los campos requeridos', 'error'); return; }
    const resp = await fetch('assets/api/agenda_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'crear', ...datos })
    });
    const data = await resp.json();
    cerrarModal('modal-agenda');
    if (data.ok) {
        toast('📅 Evento agregado');
        setTimeout(() => location.reload(), 1000);
    } else {
        toast('Error al guardar', 'error');
    }
}

async function completarAgenda(id, btn) {
    const resp = await fetch('assets/api/agenda_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'completar', id })
    });
    const data = await resp.json();
    if (data.ok) {
        btn.closest('.agenda-item').style.opacity = '0.4';
        btn.disabled = true;
        toast('✅ Marcado como completado');
    }
}

// ---- Navegación de secciones ----
function mostrarSeccion(seccion) {
    const titulos = {
        dashboard:  'Panel Principal',
        aspirantes: 'Aspirantes',
        historial:  'Historial de Interacciones',
        agenda:     'Agenda y Recordatorios',
    };
    document.getElementById('topbar-title').textContent = titulos[seccion] || 'Panel';

    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
    event.currentTarget.classList.add('active');
}
</script>
</body>
</html>
