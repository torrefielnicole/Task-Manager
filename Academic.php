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
            $stmt = $conn->prepare("UPDATE task SET task_name=?, due_date=?, status=? WHERE id=? AND category='academic'");
            $stmt->bind_param("sssi", $name, $due, $stat, $id);
            $stmt->execute();
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM task WHERE id=? AND category='academic'");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
    }

    header("Location: academic.php");
    exit();
}

// ── FETCH ──
$stmt = $conn->prepare("SELECT * FROM task WHERE category = 'academic' ORDER BY due_date ASC");
$stmt->execute();
$allTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total     = count($allTasks);
$completed = count(array_filter($allTasks, fn($t) => $t['status'] === 'completed'));
$pending   = $total - $completed;
$progress  = $total > 0 ? round(($completed / $total) * 100) : 0;

$pendingTasks   = array_values(array_filter($allTasks, fn($t) => $t['status'] === 'pending'));
$completedTasks = array_values(array_filter($allTasks, fn($t) => $t['status'] === 'completed'));

$dueSoon = array_filter($pendingTasks, function($t) {
    if (empty($t['due_date'])) return false;
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
:root {
    --bg:#1a1f6e;--surface:rgba(255,255,255,0.06);--border:rgba(255,255,255,0.13);
    --accent:#4fc3f7;--done:#00e5a0;--warn:#ffb830;--danger:#ff5252;--purple:#a78bfa;
    --text:#ffffff;--text2:rgba(255,255,255,0.55);--sidebar-w:240px;--radius:18px;
    --mono:'JetBrains Mono',monospace;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;overflow-x:hidden;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 80% 60% at 15% 10%,rgba(72,92,230,0.55) 0%,transparent 60%),radial-gradient(ellipse 60% 50% at 88% 78%,rgba(100,60,210,0.4) 0%,transparent 55%),radial-gradient(ellipse 40% 40% at 55% 35%,rgba(30,180,255,0.15) 0%,transparent 60%);z-index:0;pointer-events:none;animation:bgShift 12s ease-in-out infinite alternate;}
@keyframes bgShift{0%{filter:hue-rotate(0deg);opacity:1}100%{filter:hue-rotate(18deg);opacity:.88}}
body::after{content:'';position:fixed;inset:0;background-image:radial-gradient(circle,rgba(79,195,247,0.28) 1px,transparent 1px);background-size:88px 88px;opacity:.1;animation:floatMotes 40s linear infinite;z-index:0;pointer-events:none;}
@keyframes floatMotes{from{transform:translateY(0)}to{transform:translateY(-900px)}}
.orb{position:fixed;border-radius:50%;filter:blur(65px);opacity:.15;pointer-events:none;z-index:0;animation:orbFloat linear infinite;}
.orb1{width:320px;height:320px;background:#4fc3f7;top:-80px;left:8%;animation-duration:18s;}
.orb2{width:260px;height:260px;background:#7c6ef7;bottom:0;right:6%;animation-duration:23s;animation-delay:-7s;}
.orb3{width:200px;height:200px;background:#00e5a0;top:40%;left:55%;animation-duration:27s;animation-delay:-13s;}
@keyframes orbFloat{0%,100%{transform:translateY(0) scale(1)}33%{transform:translateY(-42px) scale(1.08)}66%{transform:translateY(20px) scale(.94)}}
#particleCanvas{position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.45;}
.cursor-glow{position:fixed;width:320px;height:320px;border-radius:50%;background:radial-gradient(circle,rgba(79,195,247,0.07) 0%,transparent 70%);pointer-events:none;z-index:1;transform:translate(-50%,-50%);}

/* SIDEBAR */
.sidebar{position:fixed;left:0;top:0;width:var(--sidebar-w);height:100vh;background:rgba(10,15,65,0.93);border-right:1px solid var(--border);display:flex;flex-direction:column;z-index:100;backdrop-filter:blur(20px);}
.sidebar-brand{padding:26px 22px 20px;border-bottom:1px solid var(--border);}
.brand-logo{display:flex;align-items:center;gap:10px;margin-bottom:4px;}
.brand-icon{width:36px;height:36px;background:linear-gradient(135deg,#4fc3f7,#7c6ef7);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;animation:iconPulse 3s ease-in-out infinite;}
@keyframes iconPulse{0%,100%{box-shadow:0 4px 14px rgba(79,195,247,.35)}50%{box-shadow:0 4px 24px rgba(79,195,247,.65)}}
.brand-name{font-family:'Nunito',sans-serif;font-size:20px;font-weight:900;color:#fff;}
.brand-user{font-size:12px;color:var(--text2);padding-left:4px;}
.nav-section{padding:14px 0 0;}
.nav-label{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.28);padding:8px 22px 4px;}
.nav-link{display:flex;align-items:center;gap:12px;padding:10px 22px;font-size:13.5px;font-weight:600;color:var(--text2);text-decoration:none;position:relative;transition:color .18s,background .18s,padding-left .2s;}
.nav-link::before{content:'';position:absolute;left:0;top:4px;bottom:4px;width:3px;border-radius:0 3px 3px 0;background:transparent;transition:background .18s;}
.nav-link:hover{color:#fff;background:rgba(255,255,255,.06);padding-left:28px;}
.nav-link.active{color:#fff;background:rgba(79,195,247,.12);padding-left:28px;}
.nav-link.active::before{background:var(--accent);box-shadow:0 0 8px rgba(79,195,247,.6);}
.nav-icon{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;transition:transform .2s;}
.nav-link:hover .nav-icon{transform:scale(1.15) rotate(-5deg);}
.icon-teal{background:rgba(0,229,160,.15)}.icon-blue{background:rgba(79,195,247,.15)}.icon-purple{background:rgba(167,139,250,.15)}.icon-yellow{background:rgba(255,184,48,.15)}.icon-gray{background:rgba(255,255,255,.07)}.icon-red{background:rgba(255,80,80,.12)}
.sidebar-footer{margin-top:auto;padding:16px 22px 24px;border-top:1px solid var(--border);}
.nav-link.logout{color:rgba(255,100,100,.65)!important}.nav-link.logout:hover{color:rgba(255,100,100,.9)!important;background:rgba(255,60,60,.07)!important}

/* MAIN */
.main{margin-left:var(--sidebar-w);flex:1;padding:32px 36px;position:relative;z-index:2;min-height:100vh;}
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;animation:slideUp .5s ease both;}
.topbar-left h1{font-family:'Nunito',sans-serif;font-size:26px;font-weight:900;}
.date-badge{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.1);border:1px solid var(--border);border-radius:20px;padding:4px 12px;font-size:12px;color:var(--text2);margin-top:6px;}
.acad-chip{display:inline-flex;align-items:center;gap:8px;background:rgba(79,195,247,.12);border:1px solid rgba(79,195,247,.3);border-radius:50px;padding:9px 20px;font-size:13px;font-weight:700;color:var(--accent);}
.acad-dot{width:8px;height:8px;border-radius:50%;background:var(--accent);animation:pulseDot 1.5s ease-in-out infinite;}
@keyframes pulseDot{0%,100%{box-shadow:0 0 0 0 rgba(79,195,247,.6);transform:scale(1)}50%{box-shadow:0 0 0 5px rgba(79,195,247,0);transform:scale(1.2)}}
.section-label{font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--text2);margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.section-label::after{content:'';flex:1;height:1px;background:var(--border);}

/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;animation:slideUp .5s ease .1s both;}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px 18px;backdrop-filter:blur(10px);position:relative;overflow:hidden;transition:transform .2s,box-shadow .2s,border-color .2s;}
.stat-card:hover{transform:translateY(-5px) scale(1.02);}
.stat-card.sc-blue:hover{border-color:rgba(79,195,247,.5);box-shadow:0 14px 36px rgba(79,195,247,.2);}
.stat-card.sc-yellow:hover{border-color:rgba(255,184,48,.5);box-shadow:0 14px 36px rgba(255,184,48,.2);}
.stat-card.sc-green:hover{border-color:rgba(0,229,160,.5);box-shadow:0 14px 36px rgba(0,229,160,.2);}
.stat-card.sc-red:hover{border-color:rgba(255,82,82,.5);box-shadow:0 14px 36px rgba(255,82,82,.2);}
.stat-card::before{content:'';position:absolute;top:0;left:-80%;width:60%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.05),transparent);transform:skewX(-15deg);transition:left .55s ease;}
.stat-card:hover::before{left:140%;}
.stat-icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:18px;margin-bottom:12px;transition:transform .2s;}
.stat-card:hover .stat-icon{transform:scale(1.2) rotate(-8deg);}
.si-blue{background:rgba(79,195,247,.15)}.si-yellow{background:rgba(255,184,48,.15)}.si-green{background:rgba(0,229,160,.15)}.si-red{background:rgba(255,82,82,.15)}
.stat-label{font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--text2);margin-bottom:4px;}
.stat-num{font-family:'Nunito',sans-serif;font-size:32px;font-weight:900;line-height:1;}
.stat-num.c-blue{color:var(--accent)}.stat-num.c-yellow{color:var(--warn)}.stat-num.c-green{color:var(--done)}.stat-num.c-red{color:var(--danger)}

/* PROGRESS */
.progress-band{background:linear-gradient(135deg,rgba(79,195,247,.1),rgba(0,229,160,.1));border:1px solid rgba(79,195,247,.2);border-radius:var(--radius);padding:18px 24px;display:flex;align-items:center;gap:24px;margin-bottom:22px;animation:slideUp .5s ease .15s both;}
.pb-info{flex:1}.pb-label{font-size:13px;font-weight:700;color:#fff;margin-bottom:8px;}
.pb-track{height:12px;background:rgba(255,255,255,.1);border-radius:99px;overflow:hidden;}
.pb-fill{height:100%;background:linear-gradient(90deg,#4fc3f7,#00e5a0);border-radius:99px;transition:width 1.2s cubic-bezier(.4,0,.2,1);animation:progPulse 3s ease-in-out infinite;}
@keyframes progPulse{0%,100%{box-shadow:0 0 0px rgba(79,195,247,0)}50%{box-shadow:0 0 16px rgba(79,195,247,.6)}}
.pb-sub{font-size:11px;color:var(--text2);margin-top:6px;}
.pb-pct{font-family:'Nunito',sans-serif;font-size:44px;font-weight:900;color:#fff;text-shadow:0 0 24px rgba(79,195,247,.5);white-space:nowrap;}

/* ADD TASK BTN */
.add-task-btn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#4fc3f7,#00e5a0);border:none;border-radius:50px;padding:10px 22px;font-size:13px;font-weight:700;font-family:'Outfit',sans-serif;color:#0a1040;cursor:pointer;transition:transform .15s,box-shadow .15s;box-shadow:0 4px 18px rgba(79,195,247,.3);}
.add-task-btn:hover{transform:translateY(-2px) scale(1.04);box-shadow:0 8px 28px rgba(79,195,247,.45);}

/* TWO COL */
.two-col{display:grid;grid-template-columns:1.4fr 1fr;gap:18px;margin-bottom:22px;animation:slideUp .5s ease .2s both;}

/* TASK PANEL */
.task-panel{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px 22px;backdrop-filter:blur(10px);}
.panel-title{font-family:'Nunito',sans-serif;font-size:15px;font-weight:800;color:#fff;margin-bottom:14px;}
.panel-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}

.task-item{display:flex;align-items:center;gap:12px;padding:11px 13px;border-radius:11px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.04);margin-bottom:8px;position:relative;overflow:hidden;transition:transform .18s,background .18s;}
.task-item:hover{transform:translateX(4px);background:rgba(255,255,255,.08);}
.task-item:last-child{margin-bottom:0;}
.task-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.dot-pending{background:var(--warn)}.dot-done{background:var(--done)}
.task-body{flex:1;min-width:0;}
.task-name{font-size:13.5px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.task-name.striked{text-decoration:line-through;color:var(--text2);}
.task-due{font-size:11px;color:var(--text2);margin-top:2px;}
.task-actions{display:flex;gap:6px;flex-shrink:0;}
.task-badge{font-size:10px;font-weight:700;padding:3px 10px;border-radius:99px;white-space:nowrap;}
.badge-pending{background:rgba(255,184,48,.15);color:var(--warn)}.badge-completed{background:rgba(0,229,160,.15);color:var(--done)}.badge-urgent{background:rgba(255,82,82,.15);color:var(--danger)}

.icon-btn{width:28px;height:28px;border-radius:8px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;transition:background .15s,transform .15s;}
.icon-btn:hover{transform:scale(1.15);}
.btn-edit{background:rgba(79,195,247,.15);color:var(--accent);}
.btn-edit:hover{background:rgba(79,195,247,.3);}
.btn-del{background:rgba(255,82,82,.12);color:var(--danger);}
.btn-del:hover{background:rgba(255,82,82,.28);}

.empty-state{text-align:center;padding:30px 0;color:var(--text2);font-size:13px;}
.empty-state span{font-size:30px;display:block;margin-bottom:8px;opacity:.4;}

/* DEADLINES */
.deadline-panel{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px 22px;backdrop-filter:blur(10px);}
.dl-item{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.05);}
.dl-item:last-child{border-bottom:none;}
.dl-name{font-size:13px;font-weight:600;color:#fff;}
.dl-sub{font-size:11px;color:var(--text2);margin-top:2px;}
.dl-chip{font-size:11px;font-weight:700;padding:4px 12px;border-radius:99px;white-space:nowrap;}
.chip-red{background:rgba(255,82,82,.15);color:var(--danger);border:1px solid rgba(255,82,82,.3);}
.chip-yellow{background:rgba(255,184,48,.15);color:var(--warn);border:1px solid rgba(255,184,48,.3);}
.chip-green{background:rgba(0,229,160,.15);color:var(--done);border:1px solid rgba(0,229,160,.3);}

/* CHART */
.chart-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:22px 24px;backdrop-filter:blur(10px);animation:slideUp .5s ease .25s both;}
.chart-title{font-family:'Nunito',sans-serif;font-size:15px;font-weight:800;color:#fff;margin-bottom:18px;}

/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(5,10,40,.8);backdrop-filter:blur(8px);z-index:999;align-items:center;justify-content:center;}
.modal-overlay.show{display:flex;}
.modal-box{background:#111850;border:1px solid rgba(79,195,247,.2);border-radius:22px;padding:32px 28px;width:420px;box-shadow:0 28px 70px rgba(0,0,0,.6);animation:popIn .22s cubic-bezier(.34,1.56,.64,1);}
@keyframes popIn{from{transform:scale(.88);opacity:0}to{transform:scale(1);opacity:1}}
.modal-icon{font-size:36px;margin-bottom:10px;text-align:center;}
.modal-title{font-family:'Nunito',sans-serif;font-size:18px;font-weight:900;color:#fff;margin-bottom:18px;text-align:center;}
.modal-field{margin-bottom:14px;}
.modal-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text2);margin-bottom:6px;display:block;}
.modal-input,.modal-select{width:100%;background:rgba(255,255,255,.07);border:1px solid rgba(79,195,247,.2);border-radius:10px;padding:10px 14px;color:#fff;font-size:13px;font-family:'Outfit',sans-serif;outline:none;transition:border-color .2s;}
.modal-input:focus,.modal-select:focus{border-color:var(--accent);}
.modal-input::placeholder{color:var(--text2);}
.modal-select option{background:#111850;color:#fff;}
.modal-btns{display:flex;gap:10px;margin-top:22px;}
.modal-btn{flex:1;padding:11px;border-radius:50px;font-size:13px;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer;border:none;transition:transform .12s,box-shadow .12s;}
.modal-btn:hover{transform:translateY(-1px);}
.modal-cancel{background:rgba(255,255,255,.1);color:var(--text2);border:1px solid var(--border)!important;}
.modal-confirm-add{background:linear-gradient(135deg,#4fc3f7,#00e5a0);color:#0a1040;}
.modal-confirm-edit{background:linear-gradient(135deg,#a78bfa,#7c6ef7);color:#fff;}
.modal-confirm-del{background:linear-gradient(135deg,#ff5252,#c62828);color:#fff;}

@keyframes slideUp{from{opacity:0;transform:translateY(26px)}to{opacity:1;transform:translateY(0)}}
::-webkit-scrollbar{width:4px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:rgba(255,255,255,.12);border-radius:99px}
</style>
</head>
<body>
<div class="orb orb1"></div><div class="orb orb2"></div><div class="orb orb3"></div>
<canvas id="particleCanvas"></canvas>
<div class="cursor-glow" id="cursorGlow"></div>

<!-- SIDEBAR -->
<aside class="sidebar">
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
        <a href="academic.php" class="nav-link active"><span class="nav-icon icon-blue">📚</span> Academic</a>
        <a href="personal.php" class="nav-link"><span class="nav-icon icon-purple">🎨</span> Personal</a>
        <a href="project.php"  class="nav-link"><span class="nav-icon icon-yellow">🚀</span> Project</a>
    </nav>
    <nav class="nav-section">
        <div class="nav-label">More</div>
        <a href="system.php" class="nav-link"><span class="nav-icon icon-gray">⚙️</span> System Info</a>
        <a href="developer.php" class="nav-link"><span class="nav-icon icon-gray">👨‍💻</span> Developer</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link logout"><span class="nav-icon icon-red">🚪</span> Logout</a>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <div class="topbar-left">
            <h1>📚 Academic</h1>
            <div class="date-badge">📅 <?= date('l, F j, Y') ?></div>
        </div>
        <div class="acad-chip"><span class="acad-dot"></span> <?= $total ?> tasks · <?= count($dueSoon) ?> due soon</div>
    </div>

    <div class="section-label">Overview</div>
    <div class="stats-grid">
        <div class="stat-card sc-blue"><div class="stat-icon si-blue">📋</div><div class="stat-label">Total Tasks</div><div class="stat-num c-blue" data-target="<?= $total ?>"><?= $total ?></div></div>
        <div class="stat-card sc-yellow"><div class="stat-icon si-yellow">⏳</div><div class="stat-label">Pending</div><div class="stat-num c-yellow" data-target="<?= $pending ?>"><?= $pending ?></div></div>
        <div class="stat-card sc-green"><div class="stat-icon si-green">✅</div><div class="stat-label">Completed</div><div class="stat-num c-green" data-target="<?= $completed ?>"><?= $completed ?></div></div>
        <div class="stat-card sc-red"><div class="stat-icon si-red">🔥</div><div class="stat-label">Due Soon</div><div class="stat-num c-red" data-target="<?= count($dueSoon) ?>"><?= count($dueSoon) ?></div></div>
    </div>

    <div class="progress-band">
        <div class="pb-info">
            <div class="pb-label">Academic Progress</div>
            <div class="pb-track"><div class="pb-fill" id="progBar" style="width:0%"></div></div>
            <div class="pb-sub"><?= $completed ?> of <?= $total ?> tasks completed</div>
        </div>
        <div class="pb-pct"><?= $progress ?>%</div>
    </div>

    <div class="section-label">Tasks & Deadlines</div>
    <div class="two-col">
        <div class="task-panel">
            <div class="panel-header">
                <div class="panel-title" style="margin-bottom:0">📌 All Academic Tasks</div>
                <button class="add-task-btn" onclick="openAddModal()">＋ Add Task</button>
            </div>
            <?php if ($total > 0): ?>
                <?php foreach ($allTasks as $t):
                    $isDone   = $t['status'] === 'completed';
                    $daysLeft = !empty($t['due_date']) ? ceil((strtotime($t['due_date']) - time()) / 86400) : null;
                    $isUrgent = $daysLeft !== null && $daysLeft <= 3 && !$isDone;
                ?>
                <div class="task-item">
                    <div class="task-dot <?= $isDone ? 'dot-done' : 'dot-pending' ?>"></div>
                    <div class="task-body">
                        <div class="task-name <?= $isDone ? 'striked' : '' ?>"><?= htmlspecialchars($t['task_name']) ?></div>
                        <div class="task-due">📅 <?= $t['due_date'] ?><?= $daysLeft !== null && !$isDone ? ' · ' . ($daysLeft <= 0 ? 'Overdue!' : $daysLeft . 'd left') : '' ?></div>
                    </div>
                    <?php if ($isDone): ?>
                        <span class="task-badge badge-completed">Done</span>
                    <?php elseif ($isUrgent): ?>
                        <span class="task-badge badge-urgent">Urgent</span>
                    <?php else: ?>
                        <span class="task-badge badge-pending">Pending</span>
                    <?php endif; ?>
                    <div class="task-actions">
                        <button class="icon-btn btn-edit" onclick="openEditModal(<?= $t['id'] ?>, '<?= addslashes(htmlspecialchars($t['task_name'])) ?>', '<?= $t['due_date'] ?>', '<?= $t['status'] ?>')" title="Edit">✏️</button>
                        <button class="icon-btn btn-del" onclick="openDeleteModal(<?= $t['id'] ?>, '<?= addslashes(htmlspecialchars($t['task_name'])) ?>')" title="Delete">🗑️</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state"><span>📭</span>No academic tasks yet.<br>Click <b>+ Add Task</b> to get started!</div>
            <?php endif; ?>
        </div>

        <div class="deadline-panel">
            <div class="panel-title">⏰ Upcoming Deadlines</div>
            <?php
            $upcoming = array_filter($pendingTasks, fn($t) => !empty($t['due_date']));
            usort($upcoming, fn($a,$b) => strtotime($a['due_date']) - strtotime($b['due_date']));
            $upcoming = array_slice($upcoming, 0, 6);
            if (count($upcoming) > 0):
                foreach ($upcoming as $t):
                    $days = ceil((strtotime($t['due_date']) - time()) / 86400);
                    $chipClass = $days <= 3 ? 'chip-red' : ($days <= 7 ? 'chip-yellow' : 'chip-green');
                    $label = $days <= 0 ? 'Overdue' : $days . 'd left';
            ?>
            <div class="dl-item">
                <div><div class="dl-name"><?= htmlspecialchars($t['task_name']) ?></div><div class="dl-sub">📅 <?= $t['due_date'] ?></div></div>
                <span class="dl-chip <?= $chipClass ?>"><?= $label ?></span>
            </div>
            <?php endforeach; else: ?>
                <div class="empty-state"><span>🎉</span>No upcoming deadlines!</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-title">📊 Task Status Breakdown</div>
        <canvas id="acadChart" height="110"></canvas>
    </div>
</main>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <div class="modal-icon">📝</div>
        <div class="modal-title">Add Academic Task</div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-field">
                <label class="modal-label">Task Name</label>
                <input type="text" name="task_name" class="modal-input" placeholder="e.g. Submit essay draft" required maxlength="255">
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

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <div class="modal-icon">✏️</div>
        <div class="modal-title">Edit Task</div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
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

<!-- DELETE MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-icon">🗑️</div>
        <div class="modal-title">Delete Task?</div>
        <p style="text-align:center;color:var(--text2);font-size:13px;margin-bottom:0;" id="deleteTaskName"></p>
        <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteId">
            <div class="modal-btns">
                <button type="button" class="modal-btn modal-cancel" onclick="closeModals()">Cancel</button>
                <button type="submit" class="modal-btn modal-confirm-del">Delete</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const glow = document.getElementById('cursorGlow');
document.addEventListener('mousemove', e => { glow.style.left=e.clientX+'px'; glow.style.top=e.clientY+'px'; });

const canvas = document.getElementById('particleCanvas');
const ctx = canvas.getContext('2d');
let W,H;
function resize(){W=canvas.width=window.innerWidth;H=canvas.height=window.innerHeight;}
resize(); window.addEventListener('resize',resize);
const COLORS=['#4fc3f7','#7c6ef7','#00e5a0','#ffb830'];
let pts=Array.from({length:65},()=>({x:Math.random()*window.innerWidth,y:Math.random()*window.innerHeight,r:Math.random()*1.6+.4,vx:(Math.random()-.5)*.35,vy:-Math.random()*.5-.15,alpha:Math.random()*.4+.1,color:COLORS[Math.floor(Math.random()*4)],life:1}));
function draw(){ctx.clearRect(0,0,W,H);pts.forEach((p,i)=>{p.x+=p.vx;p.y+=p.vy;p.life-=.003;if(p.life<=0||p.y<-10)pts[i]={x:Math.random()*W,y:H+10,r:Math.random()*1.6+.4,vx:(Math.random()-.5)*.35,vy:-Math.random()*.5-.15,alpha:Math.random()*.4+.1,color:COLORS[Math.floor(Math.random()*4)],life:1};ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);ctx.fillStyle=p.color;ctx.globalAlpha=p.alpha*p.life;ctx.fill();});ctx.globalAlpha=1;requestAnimationFrame(draw);}
draw();

document.querySelectorAll('.stat-num[data-target]').forEach(el=>{
    const target=parseInt(el.dataset.target)||0;if(!target)return;let n=0;el.textContent='0';
    const step=Math.ceil(target/(800/16));const t=setInterval(()=>{n=Math.min(n+step,target);el.textContent=n;if(n>=target)clearInterval(t);},16);
});
setTimeout(()=>{ document.getElementById('progBar').style.width='<?= $progress ?>%'; },400);

// MODAL FUNCTIONS
function openAddModal(){ document.getElementById('addModal').classList.add('show'); }
function openEditModal(id, name, due, status){
    document.getElementById('editId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editDue').value = due;
    document.getElementById('editStatus').value = status;
    document.getElementById('editModal').classList.add('show');
}
function openDeleteModal(id, name){
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteTaskName').textContent = 'Delete "' + name + '"? This cannot be undone.';
    document.getElementById('deleteModal').classList.add('show');
}
function closeModals(){
    document.querySelectorAll('.modal-overlay').forEach(m=>m.classList.remove('show'));
}
document.querySelectorAll('.modal-overlay').forEach(m=>{
    m.addEventListener('click', e=>{ if(e.target===m) closeModals(); });
});
document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeModals(); });

document.querySelectorAll('.nav-link').forEach(l=>{
    l.addEventListener('mouseenter',()=>{l.style.textShadow='0 0 12px rgba(79,195,247,.4)';});
    l.addEventListener('mouseleave',()=>{l.style.textShadow='';});
});

new Chart(document.getElementById('acadChart'),{
    type:'bar',
    data:{
        labels:['Completed','Pending','Due Soon','Total'],
        datasets:[{data:[<?= $completed ?>,<?= $pending ?>,<?= count($dueSoon) ?>,<?= $total ?>],backgroundColor:['rgba(0,229,160,.25)','rgba(255,184,48,.25)','rgba(255,82,82,.25)','rgba(79,195,247,.25)'],borderColor:['#00e5a0','#ffb830','#ff5252','#4fc3f7'],borderWidth:2,borderRadius:10}]
    },
    options:{responsive:true,plugins:{legend:{display:false},tooltip:{backgroundColor:'rgba(20,25,80,.95)',borderColor:'rgba(79,195,247,.3)',borderWidth:1,titleColor:'#fff',bodyColor:'rgba(255,255,255,.7)'}},scales:{x:{grid:{color:'rgba(255,255,255,.05)'},ticks:{color:'rgba(255,255,255,.5)',font:{size:12}}},y:{grid:{color:'rgba(255,255,255,.05)'},ticks:{color:'rgba(255,255,255,.5)',font:{size:12}},beginAtZero:true}}}
});
</script>
</body>
</html>