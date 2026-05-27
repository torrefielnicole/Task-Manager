<?php
ini_set('display_errors', 0);
error_reporting(0);
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// ── HANDLE ACTIONS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name     = trim($_POST['task_name'] ?? '');
        $due      = $_POST['due_date'] ?? '';
        $category = 'academic';
        $status   = 'pending';

        if ($name !== '') {
            $stmt = $conn->prepare("INSERT INTO task (task_name, due_date, category, status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $due, $category, $status);
            $stmt->execute();
        }
    }

    if ($action === 'edit') {
        $id   = intval($_POST['id'] ?? 0);
        $name = trim($_POST['task_name'] ?? '');
        $due  = $_POST['due_date'] ?? '';
        $stat = $_POST['status'] ?? 'pending';

        if ($id && $name !== '') {
            $stmt = $conn->prepare("UPDATE task SET task_name=?, due_date=?, status=? WHERE task_id=? AND category='academic'");
            $stmt->bind_param("sssi", $name, $due, $stat, $id);
            $stmt->execute();
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);

        if ($id) {
            $stmt = $conn->prepare("DELETE FROM task WHERE task_id=? AND category='academic'");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
    }

    header("Location: academic.php");
    exit();
}

// ── FETCH ──
$stmt = $conn->prepare("SELECT * FROM task WHERE category='academic' ORDER BY due_date ASC");
$stmt->execute();

$allTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($allTasks as &$task) {
    if (isset($task['task_id'])) {
        $task['id'] = $task['task_id'];
    }
}
unset($task);

$total     = count($allTasks);
$completed = count(array_filter($allTasks, fn($t) => $t['status'] === 'completed'));
$pending   = $total - $completed;
$progress  = $total > 0 ? round(($completed / $total) * 100) : 0;

$pendingTasks   = array_values(array_filter($allTasks, fn($t) => $t['status'] === 'pending'));
$completedTasks = array_values(array_filter($allTasks, fn($t) => $t['status'] === 'completed'));

$dueSoon = array_filter($pendingTasks, function($t){
    if(empty($t['due_date'])) return false;

    $diff = (strtotime($t['due_date']) - time()) / 86400;
    return $diff >= 0 && $diff <= 7;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Academic — To-Do List</title>

<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Outfit:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>

:root{
    --bg:#1a1f6e;
    --surface:rgba(255,255,255,0.06);
    --border:rgba(255,255,255,0.13);
    --accent:#4fc3f7;
    --done:#00e5a0;
    --warn:#ffb830;
    --danger:#ff5252;
    --purple:#a78bfa;
    --text:#ffffff;
    --text2:rgba(255,255,255,0.55);
    --sidebar-w:240px;
    --radius:18px;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

html{
    scroll-behavior:smooth;
}

body{
    font-family:'Outfit',sans-serif;
    background:var(--bg);
    color:var(--text);
    min-height:100vh;
    display:flex;
    overflow-x:hidden;
}

/* BACKGROUND */

body::before{
    content:'';
    position:fixed;
    inset:0;
    background:
    radial-gradient(ellipse 80% 60% at 15% 10%,rgba(72,92,230,0.55) 0%,transparent 60%),
    radial-gradient(ellipse 60% 50% at 88% 78%,rgba(100,60,210,0.4) 0%,transparent 55%),
    radial-gradient(ellipse 40% 40% at 55% 35%,rgba(30,180,255,0.15) 0%,transparent 60%);
    z-index:0;
    pointer-events:none;
}

body::after{
    content:'';
    position:fixed;
    inset:0;
    background-image:radial-gradient(circle,rgba(79,195,247,0.28) 1px,transparent 1px);
    background-size:88px 88px;
    opacity:.1;
    z-index:0;
    pointer-events:none;
}

.orb{
    position:fixed;
    border-radius:50%;
    filter:blur(65px);
    opacity:.15;
    pointer-events:none;
    z-index:0;
}

.orb1{
    width:320px;
    height:320px;
    background:#4fc3f7;
    top:-80px;
    left:8%;
}

.orb2{
    width:260px;
    height:260px;
    background:#7c6ef7;
    bottom:0;
    right:6%;
}

.orb3{
    width:200px;
    height:200px;
    background:#00e5a0;
    top:40%;
    left:55%;
}

#particleCanvas{
    position:fixed;
    inset:0;
    z-index:0;
    pointer-events:none;
    opacity:.45;
}

.cursor-glow{
    position:fixed;
    width:320px;
    height:320px;
    border-radius:50%;
    background:radial-gradient(circle,rgba(79,195,247,0.07) 0%,transparent 70%);
    pointer-events:none;
    z-index:1;
    transform:translate(-50%,-50%);
}

/* SIDEBAR */

.sidebar{
    position:fixed;
    left:0;
    top:0;
    width:var(--sidebar-w);
    height:100vh;
    background:rgba(10,15,65,0.93);
    border-right:1px solid var(--border);
    display:flex;
    flex-direction:column;
    z-index:100;
    backdrop-filter:blur(20px);
    transition:.3s;
}

.sidebar-brand{
    padding:26px 22px 20px;
    border-bottom:1px solid var(--border);
}

.brand-logo{
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:4px;
}

.brand-icon{
    width:36px;
    height:36px;
    background:linear-gradient(135deg,#4fc3f7,#7c6ef7);
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:18px;
}

.brand-name{
    font-family:'Nunito',sans-serif;
    font-size:20px;
    font-weight:900;
}

.brand-user{
    font-size:12px;
    color:var(--text2);
    padding-left:4px;
}

.nav-section{
    padding-top:14px;
}

.nav-label{
    font-size:10px;
    font-weight:700;
    letter-spacing:.1em;
    text-transform:uppercase;
    color:rgba(255,255,255,.28);
    padding:8px 22px 4px;
}

.nav-link{
    display:flex;
    align-items:center;
    gap:12px;
    padding:10px 22px;
    font-size:13px;
    font-weight:600;
    color:var(--text2);
    text-decoration:none;
    transition:.2s;
}

.nav-link:hover,
.nav-link.active{
    background:rgba(255,255,255,.06);
    color:#fff;
    padding-left:28px;
}

.nav-icon{
    width:32px;
    height:32px;
    border-radius:9px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:15px;
    flex-shrink:0;
}

.icon-teal{background:rgba(0,229,160,.15);}
.icon-blue{background:rgba(79,195,247,.15);}
.icon-purple{background:rgba(167,139,250,.15);}
.icon-yellow{background:rgba(255,184,48,.15);}
.icon-gray{background:rgba(255,255,255,.07);}
.icon-red{background:rgba(255,80,80,.12);}

.sidebar-footer{
    margin-top:auto;
    padding:16px 22px 24px;
    border-top:1px solid var(--border);
}

/* MAIN */

.main{
    margin-left:var(--sidebar-w);
    flex:1;
    padding:32px 36px;
    position:relative;
    z-index:2;
    min-height:100vh;
}

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    margin-bottom:28px;
    flex-wrap:wrap;
}

.topbar-left h1{
    font-size:28px;
    font-weight:900;
}

.date-badge{
    display:inline-flex;
    align-items:center;
    gap:5px;
    background:rgba(255,255,255,.1);
    border:1px solid var(--border);
    border-radius:20px;
    padding:4px 12px;
    font-size:12px;
    color:var(--text2);
    margin-top:6px;
}

.acad-chip{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:rgba(79,195,247,.12);
    border:1px solid rgba(79,195,247,.3);
    border-radius:50px;
    padding:10px 18px;
    font-size:13px;
    font-weight:700;
    color:var(--accent);
}

.acad-dot{
    width:8px;
    height:8px;
    border-radius:50%;
    background:var(--accent);
}

/* SECTION */

.section-label{
    font-size:10px;
    font-weight:700;
    letter-spacing:.12em;
    text-transform:uppercase;
    color:var(--text2);
    margin-bottom:14px;
    display:flex;
    align-items:center;
    gap:8px;
}

.section-label::after{
    content:'';
    flex:1;
    height:1px;
    background:var(--border);
}

/* STATS */

.stats-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:14px;
    margin-bottom:22px;
}

.stat-card{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:var(--radius);
    padding:20px 18px;
    backdrop-filter:blur(10px);
}

.stat-icon{
    width:40px;
    height:40px;
    border-radius:11px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:18px;
    margin-bottom:12px;
}

.si-blue{background:rgba(79,195,247,.15);}
.si-yellow{background:rgba(255,184,48,.15);}
.si-green{background:rgba(0,229,160,.15);}
.si-red{background:rgba(255,82,82,.15);}

.stat-label{
    font-size:10px;
    font-weight:700;
    letter-spacing:.07em;
    text-transform:uppercase;
    color:var(--text2);
    margin-bottom:4px;
}

.stat-num{
    font-size:32px;
    font-weight:900;
}

.c-blue{color:var(--accent);}
.c-yellow{color:var(--warn);}
.c-green{color:var(--done);}
.c-red{color:var(--danger);}

/* PROGRESS */

.progress-band{
    background:linear-gradient(135deg,rgba(79,195,247,.1),rgba(0,229,160,.1));
    border:1px solid rgba(79,195,247,.2);
    border-radius:var(--radius);
    padding:18px 24px;
    display:flex;
    align-items:center;
    gap:24px;
    margin-bottom:22px;
    flex-wrap:wrap;
}

.pb-info{
    flex:1;
    min-width:220px;
}

.pb-label{
    font-size:13px;
    font-weight:700;
    margin-bottom:8px;
}

.pb-track{
    height:12px;
    background:rgba(255,255,255,.1);
    border-radius:99px;
    overflow:hidden;
}

.pb-fill{
    height:100%;
    background:linear-gradient(90deg,#4fc3f7,#00e5a0);
    width:0%;
    transition:1s;
}

.pb-sub{
    font-size:11px;
    color:var(--text2);
    margin-top:6px;
}

.pb-pct{
    font-size:42px;
    font-weight:900;
}

/* BUTTON */

.add-task-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:linear-gradient(135deg,#4fc3f7,#00e5a0);
    border:none;
    border-radius:50px;
    padding:10px 22px;
    font-size:13px;
    font-weight:700;
    color:#0a1040;
    cursor:pointer;
}

/* TWO COLUMN */

.two-col{
    display:grid;
    grid-template-columns:1.4fr 1fr;
    gap:18px;
    margin-bottom:22px;
}

/* PANELS */

.task-panel,
.deadline-panel,
.chart-card{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:var(--radius);
    padding:20px 22px;
    backdrop-filter:blur(10px);
}

.panel-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:14px;
}

.panel-title,
.chart-title{
    font-size:16px;
    font-weight:800;
    margin-bottom:14px;
}

/* TASK ITEM */

.task-item{
    display:flex;
    align-items:center;
    gap:12px;
    padding:11px 13px;
    border-radius:11px;
    border:1px solid rgba(255,255,255,.08);
    background:rgba(255,255,255,.04);
    margin-bottom:8px;
    flex-wrap:wrap;
}

.task-dot{
    width:8px;
    height:8px;
    border-radius:50%;
}

.dot-pending{background:var(--warn);}
.dot-done{background:var(--done);}

.task-body{
    flex:1;
    min-width:200px;
}

.task-name{
    font-size:13px;
    font-weight:700;
}

.striked{
    text-decoration:line-through;
    color:var(--text2);
}

.task-due{
    font-size:11px;
    color:var(--text2);
    margin-top:2px;
}

.task-actions{
    display:flex;
    gap:6px;
}

.task-badge{
    font-size:10px;
    font-weight:700;
    padding:3px 10px;
    border-radius:99px;
}

.badge-pending{
    background:rgba(255,184,48,.15);
    color:var(--warn);
}

.badge-completed{
    background:rgba(0,229,160,.15);
    color:var(--done);
}

.badge-urgent{
    background:rgba(255,82,82,.15);
    color:var(--danger);
}

.icon-btn{
    width:28px;
    height:28px;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

.btn-edit{
    background:rgba(79,195,247,.15);
    color:var(--accent);
}

.btn-del{
    background:rgba(255,82,82,.12);
    color:var(--danger);
}

/* DEADLINES */

.dl-item{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    padding:10px 0;
    border-bottom:1px solid rgba(255,255,255,.05);
    flex-wrap:wrap;
}

.dl-item:last-child{
    border-bottom:none;
}

.dl-name{
    font-size:13px;
    font-weight:600;
}

.dl-sub{
    font-size:11px;
    color:var(--text2);
    margin-top:2px;
}

.dl-chip{
    font-size:11px;
    font-weight:700;
    padding:4px 12px;
    border-radius:99px;
}

.chip-red{
    background:rgba(255,82,82,.15);
    color:var(--danger);
}

.chip-yellow{
    background:rgba(255,184,48,.15);
    color:var(--warn);
}

.chip-green{
    background:rgba(0,229,160,.15);
    color:var(--done);
}

/* MODALS */

.modal-overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(5,10,40,.8);
    z-index:999;
    align-items:center;
    justify-content:center;
    padding:20px;
}

.modal-overlay.show{
    display:flex;
}

.modal-box{
    background:#111850;
    border:1px solid rgba(79,195,247,.2);
    border-radius:22px;
    padding:30px 24px;
    width:100%;
    max-width:420px;
}

.modal-icon{
    font-size:36px;
    margin-bottom:10px;
    text-align:center;
}

.modal-title{
    font-size:20px;
    font-weight:900;
    text-align:center;
    margin-bottom:18px;
}

.modal-field{
    margin-bottom:14px;
}

.modal-label{
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    color:var(--text2);
    margin-bottom:6px;
    display:block;
}

.modal-input,
.modal-select{
    width:100%;
    background:rgba(255,255,255,.07);
    border:1px solid rgba(79,195,247,.2);
    border-radius:10px;
    padding:11px 14px;
    color:#fff;
    font-size:13px;
    outline:none;
}

.modal-btns{
    display:flex;
    gap:10px;
    margin-top:22px;
}

.modal-btn{
    flex:1;
    padding:11px;
    border-radius:50px;
    border:none;
    font-size:13px;
    font-weight:700;
    cursor:pointer;
}

.modal-cancel{
    background:rgba(255,255,255,.1);
    color:var(--text2);
}

.modal-confirm-add{
    background:linear-gradient(135deg,#4fc3f7,#00e5a0);
    color:#0a1040;
}

.modal-confirm-edit{
    background:linear-gradient(135deg,#a78bfa,#7c6ef7);
    color:#fff;
}

.modal-confirm-del{
    background:linear-gradient(135deg,#ff5252,#c62828);
    color:#fff;
}

/* EMPTY */

.empty-state{
    text-align:center;
    padding:30px 0;
    color:var(--text2);
    font-size:13px;
}

.empty-state span{
    display:block;
    font-size:32px;
    margin-bottom:8px;
}

/* MOBILE MENU */

.mobile-menu-btn{
    display:none;
    position:fixed;
    top:16px;
    left:16px;
    z-index:1001;
    width:45px;
    height:45px;
    border:none;
    border-radius:12px;
    background:rgba(15,20,70,.95);
    color:#fff;
    font-size:20px;
    backdrop-filter:blur(10px);
    cursor:pointer;
}

.sidebar-overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.5);
    z-index:99;
}

/* RESPONSIVE */

@media(max-width:1100px){

    .stats-grid{
        grid-template-columns:repeat(2,1fr);
    }

    .two-col{
        grid-template-columns:1fr;
    }
}

@media(max-width:768px){

    .mobile-menu-btn{
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .sidebar{
        transform:translateX(-100%);
    }

    .sidebar.show{
        transform:translateX(0);
    }

    .sidebar-overlay.show{
        display:block;
    }

    .main{
        margin-left:0;
        padding:85px 18px 24px;
    }

    .stats-grid{
        grid-template-columns:1fr;
    }

    .topbar{
        flex-direction:column;
        align-items:flex-start;
    }

    .progress-band{
        flex-direction:column;
        align-items:flex-start;
    }

    .pb-pct{
        font-size:34px;
    }

    .task-item{
        align-items:flex-start;
    }

    .task-actions{
        width:100%;
        justify-content:flex-end;
    }

    .modal-btns{
        flex-direction:column;
    }
}

@media(max-width:480px){

    .main{
        padding:80px 14px 20px;
    }

    .topbar-left h1{
        font-size:24px;
    }

    .stat-num{
        font-size:26px;
    }

    .pb-pct{
        font-size:28px;
    }

    .task-body{
        min-width:100%;
    }
}

::-webkit-scrollbar{
    width:4px;
}

::-webkit-scrollbar-thumb{
    background:rgba(255,255,255,.15);
    border-radius:99px;
}

</style>
</head>

<body>

<button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="orb orb1"></div>
<div class="orb orb2"></div>
<div class="orb orb3"></div>

<canvas id="particleCanvas"></canvas>

<div class="cursor-glow" id="cursorGlow"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <div class="brand-logo">
            <div class="brand-icon">📋</div>
            <span class="brand-name">To-Do List</span>
        </div>

        <div class="brand-user">
            👋 <?= htmlspecialchars($_SESSION['user']) ?>
        </div>
    </div>

    <nav class="nav-section">
        <div class="nav-label">Main</div>

        <a href="index.php" class="nav-link">
            <span class="nav-icon icon-teal">🏠</span>
            Dashboard
        </a>
    </nav>

    <nav class="nav-section">
        <div class="nav-label">Categories</div>

        <a href="academic.php" class="nav-link active">
            <span class="nav-icon icon-blue">📚</span>
            Academic
        </a>

        <a href="personal.php" class="nav-link">
            <span class="nav-icon icon-purple">🎨</span>
            Personal
        </a>

        <a href="project.php" class="nav-link">
            <span class="nav-icon icon-yellow">🚀</span>
            Project
        </a>
    </nav>

    <nav class="nav-section">
        <div class="nav-label">More</div>

        <a href="system.php" class="nav-link">
            <span class="nav-icon icon-gray">⚙️</span>
            System Info
        </a>

        <a href="developer.php" class="nav-link">
            <span class="nav-icon icon-gray">👨‍💻</span>
            Developer
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link">
            <span class="nav-icon icon-red">🚪</span>
            Logout
        </a>
    </div>
</aside>

<!-- MAIN -->
<main class="main">

    <div class="topbar">
        <div class="topbar-left">
            <h1>📚 Academic</h1>
            <div class="date-badge">
                📅 <?= date('l, F j, Y') ?>
            </div>
        </div>

        <div class="acad-chip">
            <span class="acad-dot"></span>
            <?= $total ?> tasks · <?= count($dueSoon) ?> due soon
        </div>
    </div>

    <!-- STATS -->
    <div class="section-label">Overview</div>

    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon si-blue">📋</div>
            <div class="stat-label">Total Tasks</div>
            <div class="stat-num c-blue"><?= $total ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-icon si-yellow">⏳</div>
            <div class="stat-label">Pending</div>
            <div class="stat-num c-yellow"><?= $pending ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-icon si-green">✅</div>
            <div class="stat-label">Completed</div>
            <div class="stat-num c-green"><?= $completed ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-icon si-red">🔥</div>
            <div class="stat-label">Due Soon</div>
            <div class="stat-num c-red"><?= count($dueSoon) ?></div>
        </div>

    </div>

    <!-- PROGRESS -->
    <div class="progress-band">

        <div class="pb-info">
            <div class="pb-label">Academic Progress</div>

            <div class="pb-track">
                <div class="pb-fill" id="progBar"></div>
            </div>

            <div class="pb-sub">
                <?= $completed ?> of <?= $total ?> tasks completed
            </div>
        </div>

        <div class="pb-pct">
            <?= $progress ?>%
        </div>

    </div>

    <!-- TASKS -->
    <div class="section-label">Tasks & Deadlines</div>

    <div class="two-col">

        <!-- TASK PANEL -->
        <div class="task-panel">

            <div class="panel-header">

                <div class="panel-title" style="margin-bottom:0">
                    📌 All Academic Tasks
                </div>

                <button class="add-task-btn" onclick="openAddModal()">
                    ＋ Add Task
                </button>

            </div>

            <?php if($total > 0): ?>

                <?php foreach($allTasks as $t):

                    $isDone = $t['status'] === 'completed';

                    $daysLeft = !empty($t['due_date'])
                    ? ceil((strtotime($t['due_date']) - time()) / 86400)
                    : null;

                    $isUrgent = $daysLeft !== null && $daysLeft <= 3 && !$isDone;

                ?>

                <div class="task-item">

                    <div class="task-dot <?= $isDone ? 'dot-done' : 'dot-pending' ?>"></div>

                    <div class="task-body">

                        <div class="task-name <?= $isDone ? 'striked' : '' ?>">
                            <?= htmlspecialchars($t['task_name']) ?>
                        </div>

                        <div class="task-due">
                            📅 <?= $t['due_date'] ?>

                            <?php if($daysLeft !== null && !$isDone): ?>
                                · <?= $daysLeft <= 0 ? 'Overdue!' : $daysLeft . 'd left' ?>
                            <?php endif; ?>
                        </div>

                    </div>

                    <?php if($isDone): ?>
                        <span class="task-badge badge-completed">Done</span>
                    <?php elseif($isUrgent): ?>
                        <span class="task-badge badge-urgent">Urgent</span>
                    <?php else: ?>
                        <span class="task-badge badge-pending">Pending</span>
                    <?php endif; ?>

                    <div class="task-actions">

                        <button class="icon-btn btn-edit"
                            onclick="openEditModal(
                            <?= $t['id'] ?>,
                            '<?= addslashes(htmlspecialchars($t['task_name'])) ?>',
                            '<?= $t['due_date'] ?>',
                            '<?= $t['status'] ?>'
                        )">
                            ✏️
                        </button>

                        <button class="icon-btn btn-del"
                            onclick="openDeleteModal(
                            <?= $t['id'] ?>,
                            '<?= addslashes(htmlspecialchars($t['task_name'])) ?>'
                        )">
                            🗑️
                        </button>

                    </div>

                </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-state">
                    <span>📭</span>
                    No academic tasks yet.
                </div>

            <?php endif; ?>

        </div>

        <!-- DEADLINES -->
        <div class="deadline-panel">

            <div class="panel-title">
                ⏰ Upcoming Deadlines
            </div>

            <?php

            $upcoming = array_filter(
                $pendingTasks,
                fn($t)=>!empty($t['due_date'])
            );

            usort($upcoming, fn($a,$b)=>
                strtotime($a['due_date']) - strtotime($b['due_date'])
            );

            $upcoming = array_slice($upcoming,0,6);

            if(count($upcoming) > 0):

                foreach($upcoming as $t):

                    $days = ceil((strtotime($t['due_date']) - time()) / 86400);

                    $chipClass = $days <= 3
                    ? 'chip-red'
                    : ($days <= 7 ? 'chip-yellow' : 'chip-green');

                    $label = $days <= 0
                    ? 'Overdue'
                    : $days . 'd left';
            ?>

            <div class="dl-item">

                <div>
                    <div class="dl-name">
                        <?= htmlspecialchars($t['task_name']) ?>
                    </div>

                    <div class="dl-sub">
                        📅 <?= $t['due_date'] ?>
                    </div>
                </div>

                <span class="dl-chip <?= $chipClass ?>">
                    <?= $label ?>
                </span>

            </div>

            <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-state">
                    <span>🎉</span>
                    No upcoming deadlines!
                </div>

            <?php endif; ?>

        </div>

    </div>

    <!-- CHART -->
    <div class="chart-card">

        <div class="chart-title">
            📊 Task Status Breakdown
        </div>

        <canvas id="acadChart" height="110"></canvas>

    </div>

</main>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addModal">

    <div class="modal-box">

        <div class="modal-icon">📝</div>

        <div class="modal-title">
            Add Academic Task
        </div>

        <form method="POST">

            <input type="hidden" name="action" value="add">

            <div class="modal-field">
                <label class="modal-label">Task Name</label>

                <input type="text"
                name="task_name"
                class="modal-input"
                required>
            </div>

            <div class="modal-field">
                <label class="modal-label">Due Date</label>

                <input type="date"
                name="due_date"
                class="modal-input">
            </div>

            <div class="modal-btns">
                <button type="button"
                class="modal-btn modal-cancel"
                onclick="closeModals()">
                    Cancel
                </button>

                <button type="submit"
                class="modal-btn modal-confirm-add">
                    Add Task
                </button>
            </div>

        </form>

    </div>

</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">

    <div class="modal-box">

        <div class="modal-icon">✏️</div>

        <div class="modal-title">
            Edit Task
        </div>

        <form method="POST">

            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">

            <div class="modal-field">
                <label class="modal-label">Task Name</label>

                <input type="text"
                name="task_name"
                id="editName"
                class="modal-input"
                required>
            </div>

            <div class="modal-field">
                <label class="modal-label">Due Date</label>

                <input type="date"
                name="due_date"
                id="editDue"
                class="modal-input">
            </div>

            <div class="modal-field">
                <label class="modal-label">Status</label>

                <select name="status"
                id="editStatus"
                class="modal-select">

                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>

                </select>
            </div>

            <div class="modal-btns">

                <button type="button"
                class="modal-btn modal-cancel"
                onclick="closeModals()">
                    Cancel
                </button>

                <button type="submit"
                class="modal-btn modal-confirm-edit">
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>

<!-- DELETE MODAL -->
<div class="modal-overlay" id="deleteModal">

    <div class="modal-box">

        <div class="modal-icon">🗑️</div>

        <div class="modal-title">
            Delete Task?
        </div>

        <p id="deleteTaskName"
        style="text-align:center;color:var(--text2);font-size:13px;">
        </p>

        <form method="POST">

            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteId">

            <div class="modal-btns">

                <button type="button"
                class="modal-btn modal-cancel"
                onclick="closeModals()">
                    Cancel
                </button>

                <button type="submit"
                class="modal-btn modal-confirm-del">
                    Delete
                </button>

            </div>

        </form>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

/* MOBILE SIDEBAR */

function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}

function closeSidebar(){
    document.getElementById('sidebar').classList.remove('show');
    document.getElementById('sidebarOverlay').classList.remove('show');
}

/* CURSOR GLOW */

const glow = document.getElementById('cursorGlow');

document.addEventListener('mousemove', e => {
    glow.style.left = e.clientX + 'px';
    glow.style.top = e.clientY + 'px';
});

/* PARTICLES */

const canvas = document.getElementById('particleCanvas');
const ctx = canvas.getContext('2d');

let W,H;

function resize(){
    W = canvas.width = window.innerWidth;
    H = canvas.height = window.innerHeight;
}

resize();
window.addEventListener('resize', resize);

const COLORS = ['#4fc3f7','#7c6ef7','#00e5a0','#ffb830'];

let pts = Array.from({length:65},()=>({
    x:Math.random()*window.innerWidth,
    y:Math.random()*window.innerHeight,
    r:Math.random()*1.6+.4,
    vx:(Math.random()-.5)*.35,
    vy:-Math.random()*.5-.15,
    alpha:Math.random()*.4+.1,
    color:COLORS[Math.floor(Math.random()*4)],
    life:1
}));

function draw(){

    ctx.clearRect(0,0,W,H);

    pts.forEach((p,i)=>{

        p.x += p.vx;
        p.y += p.vy;
        p.life -= .003;

        if(p.life<=0 || p.y<-10){
            pts[i]={
                x:Math.random()*W,
                y:H+10,
                r:Math.random()*1.6+.4,
                vx:(Math.random()-.5)*.35,
                vy:-Math.random()*.5-.15,
                alpha:Math.random()*.4+.1,
                color:COLORS[Math.floor(Math.random()*4)],
                life:1
            };
        }

        ctx.beginPath();
        ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
        ctx.fillStyle=p.color;
        ctx.globalAlpha=p.alpha*p.life;
        ctx.fill();
    });

    ctx.globalAlpha=1;

    requestAnimationFrame(draw);
}

draw();

/* PROGRESS */

setTimeout(()=>{
    document.getElementById('progBar').style.width='<?= $progress ?>%';
},300);

/* MODALS */

function openAddModal(){
    document.getElementById('addModal').classList.add('show');
}

function openEditModal(id,name,due,status){

    document.getElementById('editId').value=id;
    document.getElementById('editName').value=name;
    document.getElementById('editDue').value=due;
    document.getElementById('editStatus').value=status;

    document.getElementById('editModal').classList.add('show');
}

function openDeleteModal(id,name){

    document.getElementById('deleteId').value=id;

    document.getElementById('deleteTaskName').textContent =
    'Delete "' + name + '"?';

    document.getElementById('deleteModal').classList.add('show');
}

function closeModals(){

    document.querySelectorAll('.modal-overlay')
    .forEach(m=>m.classList.remove('show'));
}

document.querySelectorAll('.modal-overlay').forEach(m=>{

    m.addEventListener('click',e=>{
        if(e.target===m){
            closeModals();
        }
    });

});

document.addEventListener('keydown',e=>{
    if(e.key==='Escape'){
        closeModals();
        closeSidebar();
    }
});

/* CHART */

new Chart(document.getElementById('acadChart'),{

    type:'bar',

    data:{
        labels:['Completed','Pending','Due Soon','Total'],

        datasets:[{
            data:[
                <?= $completed ?>,
                <?= $pending ?>,
                <?= count($dueSoon) ?>,
                <?= $total ?>
            ],

            backgroundColor:[
                'rgba(0,229,160,.25)',
                'rgba(255,184,48,.25)',
                'rgba(255,82,82,.25)',
                'rgba(79,195,247,.25)'
            ],

            borderColor:[
                '#00e5a0',
                '#ffb830',
                '#ff5252',
                '#4fc3f7'
            ],

            borderWidth:2,
            borderRadius:10
        }]
    },

    options:{
        responsive:true,
        maintainAspectRatio:false,

        plugins:{
            legend:{
                display:false
            }
        },

        scales:{
            x:{
                ticks:{
                    color:'rgba(255,255,255,.6)'
                },

                grid:{
                    color:'rgba(255,255,255,.05)'
                }
            },

            y:{
                beginAtZero:true,

                ticks:{
                    color:'rgba(255,255,255,.6)'
                },

                grid:{
                    color:'rgba(255,255,255,.05)'
                }
            }
        }
    }
});

</script>

</body>
</html>