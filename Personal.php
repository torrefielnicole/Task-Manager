<?php
ini_set('display_errors', 0);
error_reporting(0);
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user  = $_SESSION['user'];
$today = date('Y-m-d');

// ── HANDLE TASK ACTIONS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_task') {
        $name     = trim($_POST['task_name'] ?? '');
        $due      = $_POST['due_date'] ?? '';
        $category = 'personal';
        $status   = 'pending';
        if ($name !== '') {
            $stmt = $conn->prepare("INSERT INTO task (task_name, due_date, category, status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $due, $category, $status);
            $stmt->execute();
        }
    }

    if ($action === 'edit_task') {
        $id   = intval($_POST['id'] ?? 0);
        $name = trim($_POST['task_name'] ?? '');
        $due  = $_POST['due_date'] ?? '';
        $stat = $_POST['status'] ?? 'pending';
        if ($id && $name !== '') {
            $stmt = $conn->prepare("UPDATE task SET task_name=?, due_date=?, status=? WHERE task_id=? AND category='personal'");
            $stmt->bind_param("sssi", $name, $due, $stat, $id);
            $stmt->execute();
        }
    }

    if ($action === 'delete_task') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM task WHERE task_id=? AND category='personal'");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
    }

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            $stmt = $conn->prepare("INSERT INTO habits (user, name) VALUES (?, ?)");
            $stmt->bind_param("ss", $user, $name);
            $stmt->execute();
        }
    }
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM habits WHERE id = ? AND user = ?");
        $stmt->bind_param("is", $id, $user);
        $stmt->execute();
    }
    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT done_today, last_done, streak FROM habits WHERE id = ? AND user = ?");
        $stmt->bind_param("is", $id, $user);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            $wasDoneToday = $row['last_done'] === $today;
            if ($wasDoneToday) {
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                $newStreak = max(0, $row['streak'] - 1);
                $stmt = $conn->prepare("UPDATE habits SET done_today=0, last_done=?, streak=? WHERE id=? AND user=?");
                $stmt->bind_param("siis", $yesterday, $newStreak, $id, $user);
            } else {
                $newStreak = $row['streak'] + 1;
                $stmt = $conn->prepare("UPDATE habits SET done_today=1, last_done=?, streak=? WHERE id=? AND user=?");
                $stmt->bind_param("siis", $today, $newStreak, $id, $user);
            }
            $stmt->execute();
        }
    }

    header("Location: personal.php");
    exit();
}

// ── FETCH HABITS ──
$stmt = $conn->prepare("SELECT * FROM habits WHERE user = ? ORDER BY created_at ASC");
$stmt->bind_param("s", $user);
$stmt->execute();
$habits = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($habits as &$h) { $h['done_today'] = ($h['last_done'] === $today); }
unset($h);

// ── FETCH TASKS ──
$stmt = $conn->prepare("SELECT * FROM task WHERE category = 'personal' ORDER BY due_date ASC");
$stmt->execute();
$allTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);foreach ($allTasks as &$task) {
    if (isset($task['task_id'])) {
        $task['id'] = $task['task_id'];
    }
}
unset($task);
$total     = count($allTasks);
$completed = count(array_filter($allTasks, fn($t) => $t['status'] === 'completed'));
$pending   = $total - $completed;
$progress  = $total > 0 ? round(($completed / $total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Personal — To-Do List</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --bg:#1a1f6e;--surface:rgba(255,255,255,0.06);--border:rgba(255,255,255,0.13);
    --accent:#4fc3f7;--done:#00e5a0;--warn:#ffb830;--danger:#ff5252;--purple:#a78bfa;
    --text:#ffffff;--text2:rgba(255,255,255,0.55);--sidebar-w:240px;--radius:18px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
body{font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;overflow-x:hidden;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 80% 60% at 15% 10%,rgba(100,60,210,0.55) 0%,transparent 60%),radial-gradient(ellipse 60% 50% at 88% 78%,rgba(167,139,250,0.35) 0%,transparent 55%),radial-gradient(ellipse 40% 40% at 55% 35%,rgba(72,92,230,0.2) 0%,transparent 60%);z-index:0;pointer-events:none;}
body::after{content:'';position:fixed;inset:0;background-image:radial-gradient(circle,rgba(167,139,250,0.25) 1px,transparent 1px);background-size:88px 88px;opacity:.1;z-index:0;pointer-events:none;}
.orb{position:fixed;border-radius:50%;filter:blur(65px);opacity:.15;pointer-events:none;z-index:0;}
.orb1{width:320px;height:320px;background:#a78bfa;top:-80px;left:8%;}
.orb2{width:260px;height:260px;background:#7c6ef7;bottom:0;right:6%;}
.orb3{width:200px;height:200px;background:#f472b6;top:40%;left:55%;}
#particleCanvas{position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.45;}
.cursor-glow{position:fixed;width:320px;height:320px;border-radius:50%;background:radial-gradient(circle,rgba(167,139,250,0.08) 0%,transparent 70%);pointer-events:none;z-index:1;transform:translate(-50%,-50%);}

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
    align-items:center;
    justify-content:center;
}
.sidebar-overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.5);
    z-index:99;
}

/* SIDEBAR */
.sidebar{position:fixed;left:0;top:0;width:var(--sidebar-w);height:100vh;background:rgba(10,15,65,0.93);border-right:1px solid var(--border);display:flex;flex-direction:column;z-index:100;backdrop-filter:blur(20px);transition:.3s;}
.sidebar-brand{padding:26px 22px 20px;border-bottom:1px solid var(--border);}
.brand-logo{display:flex;align-items:center;gap:10px;margin-bottom:4px;}
.brand-icon{width:36px;height:36px;background:linear-gradient(135deg,#a78bfa,#7c6ef7);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;}
.brand-name{font-family:'Nunito',sans-serif;font-size:20px;font-weight:900;color:#fff;}
.brand-user{font-size:12px;color:var(--text2);padding-left:4px;}
.nav-section{padding:14px 0 0;}
.nav-label{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.28);padding:8px 22px 4px;}
.nav-link{display:flex;align-items:center;gap:12px;padding:10px 22px;font-size:13px;font-weight:600;color:var(--text2);text-decoration:none;transition:.2s;}
.nav-link:hover,.nav-link.active{background:rgba(255,255,255,.06);color:#fff;padding-left:28px;}
.nav-link.active{background:rgba(167,139,250,.12);}
.nav-icon{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;}
.icon-teal{background:rgba(0,229,160,.15)}.icon-blue{background:rgba(79,195,247,.15)}.icon-purple{background:rgba(167,139,250,.15)}.icon-yellow{background:rgba(255,184,48,.15)}.icon-gray{background:rgba(255,255,255,.07)}.icon-red{background:rgba(255,80,80,.12)}
.sidebar-footer{margin-top:auto;padding:16px 22px 24px;border-top:1px solid var(--border);}

.main{margin-left:var(--sidebar-w);flex:1;padding:32px 36px;position:relative;z-index:2;min-height:100vh;}
.topbar{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:28px;flex-wrap:wrap;}
.topbar-left h1{font-family:'Nunito',sans-serif;font-size:26px;font-weight:900;}
.date-badge{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.1);border:1px solid var(--border);border-radius:20px;padding:4px 12px;font-size:12px;color:var(--text2);margin-top:6px;}
.pers-chip{display:inline-flex;align-items:center;gap:8px;background:rgba(167,139,250,.12);border:1px solid rgba(167,139,250,.3);border-radius:50px;padding:9px 20px;font-size:13px;font-weight:700;color:var(--purple);}
.pers-dot{width:8px;height:8px;border-radius:50%;background:var(--purple);}
.section-label{font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--text2);margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.section-label::after{content:'';flex:1;height:1px;background:var(--border);}

/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px 18px;backdrop-filter:blur(10px);}
.stat-icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:18px;margin-bottom:12px;}
.si-purple{background:rgba(167,139,250,.15)}.si-yellow{background:rgba(255,184,48,.15)}.si-green{background:rgba(0,229,160,.15)}.si-pink{background:rgba(244,114,182,.15)}
.stat-label{font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--text2);margin-bottom:4px;}
.stat-num{font-family:'Nunito',sans-serif;font-size:32px;font-weight:900;line-height:1;}
.c-purple{color:var(--purple)}.c-yellow{color:var(--warn)}.c-green{color:var(--done)}.c-pink{color:#f472b6}

/* PROGRESS */
.progress-band{background:linear-gradient(135deg,rgba(167,139,250,.1),rgba(244,114,182,.08));border:1px solid rgba(167,139,250,.2);border-radius:var(--radius);padding:18px 24px;display:flex;align-items:center;gap:24px;margin-bottom:22px;flex-wrap:wrap;}
.pb-info{flex:1;min-width:220px;}
.pb-label{font-size:13px;font-weight:700;color:#fff;margin-bottom:8px;}
.pb-track{height:12px;background:rgba(255,255,255,.1);border-radius:99px;overflow:hidden;}
.pb-fill{height:100%;background:linear-gradient(90deg,#a78bfa,#f472b6);border-radius:99px;width:0%;transition:1s;}
.pb-sub{font-size:11px;color:var(--text2);margin-top:6px;}
.pb-pct{font-family:'Nunito',sans-serif;font-size:42px;font-weight:900;color:#fff;}

/* BUTTON */
.add-task-btn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#a78bfa,#f472b6);border:none;border-radius:50px;padding:10px 22px;font-size:13px;font-weight:700;font-family:'Outfit',sans-serif;color:#fff;cursor:pointer;}

/* THREE COLUMN */
.three-col{display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:18px;margin-bottom:22px;}

/* PANELS */
.panel{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px 22px;backdrop-filter:blur(10px);}
.panel-title{font-family:'Nunito',sans-serif;font-size:15px;font-weight:800;color:#fff;margin-bottom:14px;}
.panel-header{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;}

/* TASK ITEM */
.task-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:11px;border:1px solid rgba(255,255,255,.07);background:rgba(255,255,255,.04);margin-bottom:8px;flex-wrap:wrap;}
.task-item:last-child{margin-bottom:0;}
.task-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.dot-pending{background:var(--warn)}.dot-done{background:var(--done)}
.task-body{flex:1;min-width:200px;}
.task-name{font-size:13px;font-weight:700;color:#fff;}
.task-name.striked{text-decoration:line-through;color:var(--text2);}
.task-due{font-size:11px;color:var(--text2);margin-top:2px;}
.task-actions{display:flex;gap:5px;}
.task-badge{font-size:10px;font-weight:700;padding:3px 9px;border-radius:99px;white-space:nowrap;}
.badge-pending{background:rgba(255,184,48,.15);color:var(--warn)}.badge-done{background:rgba(0,229,160,.15);color:var(--done)}
.icon-btn{width:26px;height:26px;border-radius:7px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:12px;}
.btn-edit{background:rgba(167,139,250,.15);color:var(--purple);}
.btn-del{background:rgba(255,82,82,.12);color:var(--danger);}
.empty-state{text-align:center;padding:24px 0;color:var(--text2);font-size:13px;}
.empty-state span{font-size:28px;display:block;margin-bottom:8px;}

/* HABITS */
.habit-panel-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.habit-add-btn{width:28px;height:28px;border-radius:8px;background:rgba(167,139,250,.15);border:1px solid rgba(167,139,250,.3);color:var(--purple);font-size:18px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;}
.habit-add-form{display:none;margin-bottom:12px;gap:8px;flex-direction:column;}
.habit-add-form.show{display:flex;}
.habit-input{background:rgba(255,255,255,.07);border:1px solid rgba(167,139,250,.3);border-radius:10px;padding:9px 14px;color:#fff;font-size:13px;font-family:'Outfit',sans-serif;outline:none;}
.habit-input::placeholder{color:var(--text2);}
.habit-submit{background:linear-gradient(135deg,#a78bfa,#7c6ef7);border:none;border-radius:10px;padding:9px;color:#fff;font-size:13px;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer;}
.habit-item{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid rgba(255,255,255,.05);}
.habit-item:last-child{border-bottom:none;}
.habit-check{width:28px;height:28px;border-radius:50%;border:2px solid rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;cursor:pointer;transition:transform .2s,background .2s,border-color .2s;}
.habit-check.done{background:rgba(0,229,160,.15);border-color:var(--done);color:var(--done);}
.habit-name{flex:1;font-size:13px;font-weight:600;color:#fff;}
.habit-streak{font-size:12px;font-weight:700;color:var(--warn);white-space:nowrap;margin-right:4px;}
.habit-del{width:24px;height:24px;border-radius:6px;background:transparent;border:none;color:rgba(255,80,80,.4);font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;}
.no-habits{text-align:center;padding:20px 0;color:var(--text2);font-size:12px;}

/* MOOD */
.mood-panel{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px 22px;backdrop-filter:blur(10px);}
.mood-title{font-family:'Nunito',sans-serif;font-size:15px;font-weight:800;color:#fff;margin-bottom:6px;}
.mood-sub{font-size:12px;color:var(--text2);margin-bottom:18px;}
.mood-row{display:flex;gap:10px;justify-content:space-around;margin-bottom:16px;}
.mood-btn{font-size:28px;cursor:pointer;border-radius:50%;width:52px;height:52px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.05);border:2px solid transparent;transition:transform .2s,background .2s,border-color .2s;}
.mood-btn:hover{transform:scale(1.2);}
.mood-btn.selected{background:rgba(167,139,250,.2);border-color:var(--purple);transform:scale(1.15);}
.mood-label{text-align:center;font-size:12px;font-weight:700;color:var(--purple);min-height:18px;}

/* QUOTE */
.quote-card{background:linear-gradient(135deg,rgba(167,139,250,.1),rgba(244,114,182,.08));border:1px solid rgba(167,139,250,.2);border-radius:var(--radius);padding:22px 26px;margin-bottom:22px;}
.quote-text{font-family:'Nunito',sans-serif;font-size:16px;font-weight:700;color:#fff;line-height:1.5;margin-bottom:8px;}
.quote-author{font-size:12px;color:var(--text2);}

/* MODALS */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(5,10,40,.8);z-index:999;align-items:center;justify-content:center;padding:20px;}
.modal-overlay.show{display:flex;}
.modal-box{background:#14105c;border:1px solid rgba(167,139,250,.2);border-radius:22px;padding:30px 24px;width:100%;max-width:420px;}
.modal-icon{font-size:36px;margin-bottom:10px;text-align:center;}
.modal-title{font-family:'Nunito',sans-serif;font-size:18px;font-weight:900;color:#fff;margin-bottom:18px;text-align:center;}
.modal-field{margin-bottom:14px;}
.modal-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text2);margin-bottom:6px;display:block;}
.modal-input,.modal-select{width:100%;background:rgba(255,255,255,.07);border:1px solid rgba(167,139,250,.2);border-radius:10px;padding:10px 14px;color:#fff;font-size:13px;font-family:'Outfit',sans-serif;outline:none;}
.modal-select option{background:#14105c;color:#fff;}
.modal-btns{display:flex;gap:10px;margin-top:22px;}
.modal-btn{flex:1;padding:11px;border-radius:50px;font-size:13px;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer;border:none;}
.modal-cancel{background:rgba(255,255,255,.1);color:var(--text2);}
.modal-confirm-add{background:linear-gradient(135deg,#a78bfa,#f472b6);color:#fff;}
.modal-confirm-edit{background:linear-gradient(135deg,#7c6ef7,#a78bfa);color:#fff;}
.modal-confirm-del{background:linear-gradient(135deg,#ff5252,#c62828);color:#fff;}
.modal-del-sub{text-align:center;color:var(--text2);font-size:13px;margin-bottom:4px;}

/* HABIT DELETE MODAL */
.hmodal-overlay{display:none;position:fixed;inset:0;background:rgba(5,10,40,.75);backdrop-filter:blur(6px);z-index:999;align-items:center;justify-content:center;padding:20px;}
.hmodal-overlay.show{display:flex;}
.hmodal-box{background:#14105c;border:1px solid rgba(167,139,250,.25);border-radius:20px;padding:32px 28px;width:100%;max-width:340px;text-align:center;}

/* ── RESPONSIVE ── */
@media(max-width:1200px){
    .three-col{grid-template-columns:1fr 1fr;}
    .mood-panel{grid-column:span 2;}
}

@media(max-width:1100px){
    .stats-grid{grid-template-columns:repeat(2,1fr);}
    .three-col{grid-template-columns:1fr 1fr;}
}

@media(max-width:768px){
    .mobile-menu-btn{
        display:flex;
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
        grid-template-columns:1fr 1fr;
    }
    .three-col{
        grid-template-columns:1fr;
    }
    .mood-panel{
        grid-column:span 1;
    }
    .topbar{
        flex-direction:column;
        align-items:flex-start;
    }
    .progress-band{
        flex-direction:column;
        align-items:flex-start;
    }
    .pb-pct{font-size:34px;}
    .task-item{align-items:flex-start;}
    .task-actions{width:100%;justify-content:flex-end;}
    .modal-btns{flex-direction:column;}
}

@media(max-width:480px){
    .main{padding:80px 14px 20px;}
    .topbar-left h1{font-size:22px;}
    .stats-grid{grid-template-columns:1fr;}
    .stat-num{font-size:26px;}
    .pb-pct{font-size:28px;}
    .task-body{min-width:100%;}
    .mood-row{gap:6px;}
    .mood-btn{width:44px;height:44px;font-size:24px;}
}

::-webkit-scrollbar{width:4px}
::-webkit-scrollbar-thumb{background:rgba(255,255,255,.15);border-radius:99px}
</style>
</head>
<body>

<button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="orb orb1"></div><div class="orb orb2"></div><div class="orb orb3"></div>
<canvas id="particleCanvas"></canvas>
<div class="cursor-glow" id="cursorGlow"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo"><div class="brand-icon">📋</div><span class="brand-name">To-Do List</span></div>
        <div class="brand-user">👋 <?= htmlspecialchars($_SESSION['user']) ?></div>
    </div>
    <nav class="nav-section">
        <div class="nav-label">Main</div>
        <a href="index.php" class="nav-link"><span class="nav-icon icon-teal">🏠</span> Dashboard</a>
    </nav>
    <nav class="nav-section">
        <div class="nav-label">Categories</div>
        <a href="academic.php" class="nav-link"><span class="nav-icon icon-blue">📚</span> Academic</a>
        <a href="personal.php" class="nav-link active"><span class="nav-icon icon-purple">🎨</span> Personal</a>
        <a href="project.php"  class="nav-link"><span class="nav-icon icon-yellow">🚀</span> Project</a>
    </nav>
    <nav class="nav-section">
        <div class="nav-label">More</div>
        <a href="system.php" class="nav-link"><span class="nav-icon icon-gray">⚙️</span> System Info</a>
        <a href="developer.php" class="nav-link"><span class="nav-icon icon-gray">👨‍💻</span> Developer</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link"><span class="nav-icon icon-red">🚪</span> Logout</a>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <div class="topbar-left">
            <h1>🎨 Personal</h1>
            <div class="date-badge">📅 <?= date('l, F j, Y') ?></div>
        </div>
        <div class="pers-chip"><span class="pers-dot"></span> Habits · Goals · Wellbeing</div>
    </div>

    <div class="section-label">Overview</div>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon si-purple">🎨</div><div class="stat-label">Total Tasks</div><div class="stat-num c-purple"><?= $total ?></div></div>
        <div class="stat-card"><div class="stat-icon si-yellow">⏳</div><div class="stat-label">Pending</div><div class="stat-num c-yellow"><?= $pending ?></div></div>
        <div class="stat-card"><div class="stat-icon si-green">✅</div><div class="stat-label">Completed</div><div class="stat-num c-green"><?= $completed ?></div></div>
        <div class="stat-card"><div class="stat-icon si-pink">💜</div><div class="stat-label">Progress</div><div class="stat-num c-pink"><?= $progress ?>%</div></div>
    </div>

    <div class="progress-band">
        <div class="pb-info">
            <div class="pb-label">Personal Progress</div>
            <div class="pb-track"><div class="pb-fill" id="progBar"></div></div>
            <div class="pb-sub"><?= $completed ?> of <?= $total ?> tasks done</div>
        </div>
        <div class="pb-pct"><?= $progress ?>%</div>
    </div>

    <div class="section-label">Tasks, Habits & Mood</div>
    <div class="three-col">
        <!-- TASK LIST -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title" style="margin-bottom:0">📌 Personal Tasks</div>
                <button class="add-task-btn" onclick="openAddModal()">＋ Add Task</button>
            </div>
            <?php if ($total > 0): ?>
                <?php foreach ($allTasks as $t):
                    $isDone = $t['status'] === 'completed'; ?>
                <div class="task-item">
                    <div class="task-dot <?= $isDone ? 'dot-done' : 'dot-pending' ?>"></div>
                    <div class="task-body">
                        <div class="task-name <?= $isDone ? 'striked' : '' ?>"><?= htmlspecialchars($t['task_name']) ?></div>
                        <?php if (!empty($t['due_date']) && $t['due_date'] !== '0000-00-00'): ?>
                        <div class="task-due">📅 <?= $t['due_date'] ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="task-badge <?= $isDone ? 'badge-done' : 'badge-pending' ?>"><?= $isDone ? 'Done' : 'Pending' ?></span>
                    <div class="task-actions">
                        <button class="icon-btn btn-edit" onclick="openEditModal(<?= $t['id'] ?>, '<?= addslashes(htmlspecialchars($t['task_name'])) ?>', '<?= $t['due_date'] ?>', '<?= $t['status'] ?>')">✏️</button>
                        <button class="icon-btn btn-del" onclick="openDeleteModal(<?= $t['id'] ?>, '<?= addslashes(htmlspecialchars($t['task_name'])) ?>')">🗑️</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state"><span>🎨</span>No personal tasks yet.<br>Click <b>+ Add Task</b>!</div>
            <?php endif; ?>
        </div>

        <!-- HABITS -->
        <div class="panel">
            <div class="habit-panel-header">
                <div class="panel-title" style="margin-bottom:0">🔥 Daily Habits</div>
                <button class="habit-add-btn" onclick="toggleAddForm()">+</button>
            </div>
            <form method="POST" class="habit-add-form" id="habitAddForm">
                <input type="hidden" name="action" value="add">
                <input type="text" name="name" class="habit-input" placeholder="New habit name..." required maxlength="100">
                <button type="submit" class="habit-submit">Add Habit</button>
            </form>
            <?php if (count($habits) > 0): ?>
                <?php foreach ($habits as $h): ?>
                <div class="habit-item">
                    <form method="POST" style="display:contents">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $h['id'] ?>">
                        <button type="submit" class="habit-check <?= $h['done_today'] ? 'done' : '' ?>"><?= $h['done_today'] ? '✓' : '' ?></button>
                    </form>
                    <div class="habit-name"><?= htmlspecialchars($h['name']) ?></div>
                    <div class="habit-streak"><?= $h['streak'] ?>d <?= $h['streak'] >= 5 ? '🔥' : '' ?></div>
                    <button class="habit-del" onclick="confirmDeleteHabit(<?= $h['id'] ?>, '<?= addslashes($h['name']) ?>')">✕</button>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-habits">No habits yet — add one above!</div>
            <?php endif; ?>
        </div>

        <!-- MOOD -->
        <div class="mood-panel">
            <div class="mood-title">😊 Mood Check</div>
            <div class="mood-sub">How are you feeling today?</div>
            <div class="mood-row">
                <div class="mood-btn" data-mood="Exhausted" onclick="selectMood(this)">😫</div>
                <div class="mood-btn" data-mood="Sad" onclick="selectMood(this)">😕</div>
                <div class="mood-btn" data-mood="Neutral" onclick="selectMood(this)">😐</div>
                <div class="mood-btn selected" data-mood="Good" onclick="selectMood(this)">🙂</div>
                <div class="mood-btn" data-mood="Amazing" onclick="selectMood(this)">😄</div>
            </div>
            <div class="mood-label" id="moodLabel">Feeling Good ✨</div>
            <div style="margin-top:20px;border-top:1px solid var(--border);padding-top:16px;">
                <div class="panel-title" style="font-size:13px;margin-bottom:12px;">📅 Mood This Week</div>
                <div style="display:flex;gap:8px;justify-content:space-between;">
                    <?php $days=['M','T','W','T','F','S','S'];$moods=['😄','🙂','😐','😄','🙂','😄','😄'];
                    foreach($days as $i=>$d): ?>
                    <div style="text-align:center;flex:1;">
                        <div style="font-size:16px;margin-bottom:4px;"><?= $moods[$i] ?></div>
                        <div style="font-size:10px;color:var(--text2);font-weight:700;"><?= $d ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="quote-card">
        <div class="quote-text" id="quoteText">"The secret of getting ahead is getting started."</div>
        <div class="quote-author" id="quoteAuthor">— Mark Twain</div>
    </div>
</main>

<!-- ADD TASK MODAL -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <div class="modal-icon">📝</div>
        <div class="modal-title">Add Personal Task</div>
        <form method="POST">
            <input type="hidden" name="action" value="add_task">
            <div class="modal-field">
                <label class="modal-label">Task Name</label>
                <input type="text" name="task_name" class="modal-input" placeholder="e.g. Go for a morning run" required maxlength="255">
            </div>
            <div class="modal-field">
                <label class="modal-label">Due Date</label>
                <input type="date" name="due_date" class="modal-input">
            </div>
            <div class="modal-btns">
                <button type="button" class="modal-btn modal-cancel" onclick="closeModals()">Cancel</button>
                <button type="submit" class="modal-btn modal-confirm-add">Add Task</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT TASK MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <div class="modal-icon">✏️</div>
        <div class="modal-title">Edit Task</div>
        <form method="POST">
            <input type="hidden" name="action" value="edit_task">
            <input type="hidden" name="id" id="editId">
            <div class="modal-field">
                <label class="modal-label">Task Name</label>
                <input type="text" name="task_name" id="editName" class="modal-input" required maxlength="255">
            </div>
            <div class="modal-field">
                <label class="modal-label">Due Date</label>
                <input type="date" name="due_date" id="editDue" class="modal-input">
            </div>
            <div class="modal-field">
                <label class="modal-label">Status</label>
                <select name="status" id="editStatus" class="modal-select">
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div class="modal-btns">
                <button type="button" class="modal-btn modal-cancel" onclick="closeModals()">Cancel</button>
                <button type="submit" class="modal-btn modal-confirm-edit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE TASK MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-icon">🗑️</div>
        <div class="modal-title">Delete Task?</div>
        <p class="modal-del-sub" id="deleteTaskName"></p>
        <form method="POST">
            <input type="hidden" name="action" value="delete_task">
            <input type="hidden" name="id" id="deleteId">
            <div class="modal-btns">
                <button type="button" class="modal-btn modal-cancel" onclick="closeModals()">Cancel</button>
                <button type="submit" class="modal-btn modal-confirm-del">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- HABIT DELETE MODAL -->
<div class="hmodal-overlay" id="habitDeleteModal">
    <div class="hmodal-box">
        <div style="font-size:40px;margin-bottom:12px;">🗑️</div>
        <div style="font-family:'Nunito',sans-serif;font-size:18px;font-weight:900;color:#fff;margin-bottom:6px;">Delete Habit?</div>
        <div style="font-size:12px;color:var(--text2);margin-bottom:22px;" id="modalHabitName">This will permanently remove the habit.</div>
        <div style="display:flex;gap:10px;justify-content:center;">
            <button class="modal-btn modal-cancel" onclick="closeHabitModal()" style="flex:1;padding:9px 24px;">Cancel</button>
            <form method="POST" style="display:contents" id="habitDeleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteHabitId">
                <button type="submit" class="modal-btn modal-confirm-del" style="flex:1;">Delete</button>
            </form>
        </div>
    </div>
</div>

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
document.addEventListener('mousemove', e => { glow.style.left=e.clientX+'px'; glow.style.top=e.clientY+'px'; });

const canvas = document.getElementById('particleCanvas');
const ctx = canvas.getContext('2d');
let W,H;
function resize(){W=canvas.width=window.innerWidth;H=canvas.height=window.innerHeight;}
resize(); window.addEventListener('resize',resize);
const COLORS=['#a78bfa','#7c6ef7','#f472b6','#c084fc'];
let pts=Array.from({length:65},()=>({x:Math.random()*window.innerWidth,y:Math.random()*window.innerHeight,r:Math.random()*1.6+.4,vx:(Math.random()-.5)*.35,vy:-Math.random()*.5-.15,alpha:Math.random()*.4+.1,color:COLORS[Math.floor(Math.random()*4)],life:1}));
function draw(){ctx.clearRect(0,0,W,H);pts.forEach((p,i)=>{p.x+=p.vx;p.y+=p.vy;p.life-=.003;if(p.life<=0||p.y<-10)pts[i]={x:Math.random()*W,y:H+10,r:Math.random()*1.6+.4,vx:(Math.random()-.5)*.35,vy:-Math.random()*.5-.15,alpha:Math.random()*.4+.1,color:COLORS[Math.floor(Math.random()*4)],life:1};ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);ctx.fillStyle=p.color;ctx.globalAlpha=p.alpha*p.life;ctx.fill();});ctx.globalAlpha=1;requestAnimationFrame(draw);}
draw();

/* PROGRESS */
setTimeout(()=>{ document.getElementById('progBar').style.width='<?= $progress ?>%'; },300);

/* TASK MODALS */
function openAddModal(){ document.getElementById('addModal').classList.add('show'); }
function openEditModal(id, name, due, status){
    document.getElementById('editId').value=id;
    document.getElementById('editName').value=name;
    document.getElementById('editDue').value=due;
    document.getElementById('editStatus').value=status;
    document.getElementById('editModal').classList.add('show');
}
function openDeleteModal(id, name){
    document.getElementById('deleteId').value=id;
    document.getElementById('deleteTaskName').textContent='Delete "'+name+'"? This cannot be undone.';
    document.getElementById('deleteModal').classList.add('show');
}
function closeModals(){ document.querySelectorAll('.modal-overlay').forEach(m=>m.classList.remove('show')); }
document.querySelectorAll('.modal-overlay').forEach(m=>{ m.addEventListener('click',e=>{ if(e.target===m)closeModals(); }); });

/* HABIT MODALS */
function toggleAddForm(){const f=document.getElementById('habitAddForm');f.classList.toggle('show');if(f.classList.contains('show'))f.querySelector('input[name="name"]').focus();}
function confirmDeleteHabit(id,name){
    document.getElementById('modalHabitName').textContent='Delete "'+name+'"? This cannot be undone.';
    document.getElementById('deleteHabitId').value=id;
    document.getElementById('habitDeleteModal').classList.add('show');
}
function closeHabitModal(){document.getElementById('habitDeleteModal').classList.remove('show');}
document.getElementById('habitDeleteModal').addEventListener('click',function(e){if(e.target===this)closeHabitModal();});

document.addEventListener('keydown',e=>{ if(e.key==='Escape'){closeModals();closeHabitModal();closeSidebar();} });

/* MOOD */
const moodLabels={'Exhausted':'Feeling exhausted... rest up 💙','Sad':'Hang in there 💜','Neutral':'Just getting through it 😌','Good':'Feeling good ✨','Amazing':'You\'re crushing it! 🔥'};
function selectMood(btn){document.querySelectorAll('.mood-btn').forEach(b=>b.classList.remove('selected'));btn.classList.add('selected');document.getElementById('moodLabel').textContent=moodLabels[btn.dataset.mood]||'';}

/* QUOTES */
const quotes=[
    {text:'"The secret of getting ahead is getting started."',author:'— Mark Twain'},
    {text:'"Do something today that your future self will thank you for."',author:'— Unknown'},
    {text:'"Small steps every day lead to big results."',author:'— Unknown'},
    {text:'"You don\'t have to be great to start, but you have to start to be great."',author:'— Zig Ziglar'},
    {text:'"Believe you can and you\'re halfway there."',author:'— Theodore Roosevelt'},
];
let qi=0;
function rotateQuote(){qi=(qi+1)%quotes.length;const qEl=document.getElementById('quoteText'),aEl=document.getElementById('quoteAuthor');qEl.style.opacity='0';aEl.style.opacity='0';setTimeout(()=>{qEl.textContent=quotes[qi].text;aEl.textContent=quotes[qi].author;qEl.style.transition='opacity .6s';aEl.style.transition='opacity .6s';qEl.style.opacity='1';aEl.style.opacity='1';},400);}
setInterval(rotateQuote,8000);
</script>
</body>
</html>