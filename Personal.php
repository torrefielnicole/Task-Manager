<?php
ini_set('display_errors', 0);
error_reporting(0);
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$today = date('Y-m-d');

// ── HANDLE ACTIONS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add habit
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            $stmt = $conn->prepare("INSERT INTO habits (user, name) VALUES (?, ?)");
            $stmt->bind_param("ss", $user, $name);
            $stmt->execute();
        }
    }

    // Delete habit
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM habits WHERE id = ? AND user = ?");
        $stmt->bind_param("is", $id, $user);
        $stmt->execute();
    }

    // Toggle done
    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT done_today, last_done, streak FROM habits WHERE id = ? AND user = ?");
        $stmt->bind_param("is", $id, $user);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row) {
            $wasDoneToday = $row['last_done'] === $today;
            if ($wasDoneToday) {
                // Untoggle
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                $newStreak = max(0, $row['streak'] - 1);
                $stmt = $conn->prepare("UPDATE habits SET done_today=0, last_done=?, streak=? WHERE id=? AND user=?");
                $stmt->bind_param("siis", $yesterday, $newStreak, $id, $user);
            } else {
                // Mark done, increment streak
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

// Mark done_today based on last_done
foreach ($habits as &$h) {
    $h['done_today'] = ($h['last_done'] === $today);
}
unset($h);

// ── FETCH TASKS ──
$stmt = $conn->prepare("SELECT * FROM task WHERE category = 'personal' ORDER BY due_date ASC");
$stmt->execute();
$allTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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
body{font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;overflow-x:hidden;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 80% 60% at 15% 10%,rgba(100,60,210,0.55) 0%,transparent 60%),radial-gradient(ellipse 60% 50% at 88% 78%,rgba(167,139,250,0.35) 0%,transparent 55%),radial-gradient(ellipse 40% 40% at 55% 35%,rgba(72,92,230,0.2) 0%,transparent 60%);z-index:0;pointer-events:none;animation:bgShift 12s ease-in-out infinite alternate;}
@keyframes bgShift{0%{filter:hue-rotate(0deg);opacity:1}100%{filter:hue-rotate(20deg);opacity:.9}}
body::after{content:'';position:fixed;inset:0;background-image:radial-gradient(circle,rgba(167,139,250,0.25) 1px,transparent 1px);background-size:88px 88px;opacity:.1;animation:floatMotes 40s linear infinite;z-index:0;pointer-events:none;}
@keyframes floatMotes{from{transform:translateY(0)}to{transform:translateY(-900px)}}
.orb{position:fixed;border-radius:50%;filter:blur(65px);opacity:.15;pointer-events:none;z-index:0;animation:orbFloat linear infinite;}
.orb1{width:320px;height:320px;background:#a78bfa;top:-80px;left:8%;animation-duration:18s;}
.orb2{width:260px;height:260px;background:#7c6ef7;bottom:0;right:6%;animation-duration:23s;animation-delay:-7s;}
.orb3{width:200px;height:200px;background:#f472b6;top:40%;left:55%;animation-duration:27s;animation-delay:-13s;}
@keyframes orbFloat{0%,100%{transform:translateY(0) scale(1)}33%{transform:translateY(-42px) scale(1.08)}66%{transform:translateY(20px) scale(.94)}}
#particleCanvas{position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.45;}
.cursor-glow{position:fixed;width:320px;height:320px;border-radius:50%;background:radial-gradient(circle,rgba(167,139,250,0.08) 0%,transparent 70%);pointer-events:none;z-index:1;transform:translate(-50%,-50%);}

/* SIDEBAR */
.sidebar{position:fixed;left:0;top:0;width:var(--sidebar-w);height:100vh;background:rgba(10,15,65,0.93);border-right:1px solid var(--border);display:flex;flex-direction:column;z-index:100;backdrop-filter:blur(20px);}
.sidebar-brand{padding:26px 22px 20px;border-bottom:1px solid var(--border);}
.brand-logo{display:flex;align-items:center;gap:10px;margin-bottom:4px;}
.brand-icon{width:36px;height:36px;background:linear-gradient(135deg,#a78bfa,#7c6ef7);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;animation:iconPulse 3s ease-in-out infinite;}
@keyframes iconPulse{0%,100%{box-shadow:0 4px 14px rgba(167,139,250,.35)}50%{box-shadow:0 4px 24px rgba(167,139,250,.65)}}
.brand-name{font-family:'Nunito',sans-serif;font-size:20px;font-weight:900;color:#fff;}
.brand-user{font-size:12px;color:var(--text2);padding-left:4px;}
.nav-section{padding:14px 0 0;}
.nav-label{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.28);padding:8px 22px 4px;}
.nav-link{display:flex;align-items:center;gap:12px;padding:10px 22px;font-size:13.5px;font-weight:600;color:var(--text2);text-decoration:none;position:relative;transition:color .18s,background .18s,padding-left .2s;}
.nav-link::before{content:'';position:absolute;left:0;top:4px;bottom:4px;width:3px;border-radius:0 3px 3px 0;background:transparent;transition:background .18s;}
.nav-link:hover{color:#fff;background:rgba(255,255,255,.06);padding-left:28px;}
.nav-link.active{color:#fff;background:rgba(167,139,250,.12);padding-left:28px;}
.nav-link.active::before{background:var(--purple);box-shadow:0 0 8px rgba(167,139,250,.6);}
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
.pers-chip{display:inline-flex;align-items:center;gap:8px;background:rgba(167,139,250,.12);border:1px solid rgba(167,139,250,.3);border-radius:50px;padding:9px 20px;font-size:13px;font-weight:700;color:var(--purple);}
.pers-dot{width:8px;height:8px;border-radius:50%;background:var(--purple);animation:pulseDot 1.5s ease-in-out infinite;}
@keyframes pulseDot{0%,100%{box-shadow:0 0 0 0 rgba(167,139,250,.6);transform:scale(1)}50%{box-shadow:0 0 0 5px rgba(167,139,250,0);transform:scale(1.2)}}
.section-label{font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--text2);margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.section-label::after{content:'';flex:1;height:1px;background:var(--border);}

/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;animation:slideUp .5s ease .1s both;}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px 18px;backdrop-filter:blur(10px);position:relative;overflow:hidden;transition:transform .2s,box-shadow .2s,border-color .2s;}
.stat-card:hover{transform:translateY(-5px) scale(1.02);}
.stat-card.sc-purple:hover{border-color:rgba(167,139,250,.5);box-shadow:0 14px 36px rgba(167,139,250,.2);}
.stat-card.sc-yellow:hover{border-color:rgba(255,184,48,.5);box-shadow:0 14px 36px rgba(255,184,48,.2);}
.stat-card.sc-green:hover{border-color:rgba(0,229,160,.5);box-shadow:0 14px 36px rgba(0,229,160,.2);}
.stat-card.sc-pink:hover{border-color:rgba(244,114,182,.5);box-shadow:0 14px 36px rgba(244,114,182,.2);}
.stat-card::before{content:'';position:absolute;top:0;left:-80%;width:60%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.05),transparent);transform:skewX(-15deg);transition:left .55s ease;}
.stat-card:hover::before{left:140%;}
.stat-icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:18px;margin-bottom:12px;transition:transform .2s;}
.stat-card:hover .stat-icon{transform:scale(1.2) rotate(-8deg);}
.si-purple{background:rgba(167,139,250,.15)}.si-yellow{background:rgba(255,184,48,.15)}.si-green{background:rgba(0,229,160,.15)}.si-pink{background:rgba(244,114,182,.15)}
.stat-label{font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--text2);margin-bottom:4px;}
.stat-num{font-family:'Nunito',sans-serif;font-size:32px;font-weight:900;line-height:1;color:#fff;}
.stat-num.c-purple{color:var(--purple)}.stat-num.c-yellow{color:var(--warn)}.stat-num.c-green{color:var(--done)}.stat-num.c-pink{color:#f472b6}

/* PROGRESS */
.progress-band{background:linear-gradient(135deg,rgba(167,139,250,.1),rgba(244,114,182,.08));border:1px solid rgba(167,139,250,.2);border-radius:var(--radius);padding:18px 24px;display:flex;align-items:center;gap:24px;margin-bottom:22px;animation:slideUp .5s ease .15s both;}
.pb-info{flex:1}.pb-label{font-size:13px;font-weight:700;color:#fff;margin-bottom:8px;}
.pb-track{height:12px;background:rgba(255,255,255,.1);border-radius:99px;overflow:hidden;}
.pb-fill{height:100%;background:linear-gradient(90deg,#a78bfa,#f472b6);border-radius:99px;transition:width 1.2s cubic-bezier(.4,0,.2,1);animation:progPulse 3s ease-in-out infinite;}
@keyframes progPulse{0%,100%{box-shadow:0 0 0px rgba(167,139,250,0)}50%{box-shadow:0 0 16px rgba(167,139,250,.6)}}
.pb-sub{font-size:11px;color:var(--text2);margin-top:6px;}
.pb-pct{font-family:'Nunito',sans-serif;font-size:44px;font-weight:900;color:#fff;text-shadow:0 0 24px rgba(167,139,250,.5);white-space:nowrap;}

/* THREE COL */
.three-col{display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:18px;margin-bottom:22px;animation:slideUp .5s ease .2s both;}

/* TASK LIST */
.panel{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px 22px;backdrop-filter:blur(10px);}
.panel-title{font-family:'Nunito',sans-serif;font-size:15px;font-weight:800;color:#fff;margin-bottom:14px;}
.task-item{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:11px;border:1px solid rgba(255,255,255,.07);background:rgba(255,255,255,.04);margin-bottom:8px;position:relative;overflow:hidden;transition:transform .18s,background .18s;cursor:pointer;}
.task-item:hover{transform:translateX(4px);background:rgba(255,255,255,.08);}
.task-item:last-child{margin-bottom:0;}
.task-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.dot-pending{background:var(--warn)}.dot-done{background:var(--done)}
.task-body{flex:1;min-width:0;}
.task-name{font-size:13px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.task-name.striked{text-decoration:line-through;color:var(--text2);}
.task-due{font-size:11px;color:var(--text2);margin-top:2px;}
.task-badge{font-size:10px;font-weight:700;padding:3px 9px;border-radius:99px;white-space:nowrap;}
.badge-pending{background:rgba(255,184,48,.15);color:var(--warn)}.badge-done{background:rgba(0,229,160,.15);color:var(--done)}
.empty-state{text-align:center;padding:24px 0;color:var(--text2);font-size:13px;}
.empty-state span{font-size:28px;display:block;margin-bottom:8px;opacity:.4;}

/* HABITS */
.habit-panel-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.habit-add-btn{width:28px;height:28px;border-radius:8px;background:rgba(167,139,250,.15);border:1px solid rgba(167,139,250,.3);color:var(--purple);font-size:18px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s,transform .15s;}
.habit-add-btn:hover{background:rgba(167,139,250,.3);transform:scale(1.1);}
.habit-add-form{display:none;margin-bottom:12px;gap:8px;flex-direction:column;}
.habit-add-form.show{display:flex;}
.habit-input{background:rgba(255,255,255,.07);border:1px solid rgba(167,139,250,.3);border-radius:10px;padding:9px 14px;color:#fff;font-size:13px;font-family:'Outfit',sans-serif;outline:none;transition:border-color .2s;}
.habit-input:focus{border-color:var(--purple);}
.habit-input::placeholder{color:var(--text2);}
.habit-submit{background:linear-gradient(135deg,#a78bfa,#7c6ef7);border:none;border-radius:10px;padding:9px;color:#fff;font-size:13px;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer;transition:opacity .15s;}
.habit-submit:hover{opacity:.85;}
.habit-item{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid rgba(255,255,255,.05);transition:background .15s;}
.habit-item:last-child{border-bottom:none;}
.habit-item:hover{background:rgba(255,255,255,.03);border-radius:8px;padding-left:6px;}
.habit-check{width:28px;height:28px;border-radius:50%;border:2px solid rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;cursor:pointer;transition:transform .2s,background .2s,border-color .2s;}
.habit-check.done{background:rgba(0,229,160,.15);border-color:var(--done);color:var(--done);}
.habit-check:hover{transform:scale(1.15);}
.habit-name{flex:1;font-size:13px;font-weight:600;color:#fff;}
.habit-streak{font-size:12px;font-weight:700;color:var(--warn);white-space:nowrap;margin-right:4px;}
.habit-del{width:24px;height:24px;border-radius:6px;background:transparent;border:none;color:rgba(255,80,80,.4);font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s,color .15s;flex-shrink:0;}
.habit-del:hover{background:rgba(255,80,80,.15);color:#ff5252;}
.no-habits{text-align:center;padding:20px 0;color:var(--text2);font-size:12px;}

/* MOOD */
.mood-panel{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px 22px;backdrop-filter:blur(10px);}
.mood-title{font-family:'Nunito',sans-serif;font-size:15px;font-weight:800;color:#fff;margin-bottom:6px;}
.mood-sub{font-size:12px;color:var(--text2);margin-bottom:18px;}
.mood-row{display:flex;gap:10px;justify-content:space-around;margin-bottom:16px;}
.mood-btn{font-size:28px;cursor:pointer;border-radius:50%;width:52px;height:52px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.05);border:2px solid transparent;transition:transform .2s,background .2s,border-color .2s;}
.mood-btn:hover{transform:scale(1.2) rotate(-5deg);background:rgba(255,255,255,.1);}
.mood-btn.selected{background:rgba(167,139,250,.2);border-color:var(--purple);transform:scale(1.15);box-shadow:0 0 16px rgba(167,139,250,.4);}
.mood-label{text-align:center;font-size:12px;font-weight:700;color:var(--purple);min-height:18px;}

/* QUOTE */
.quote-card{background:linear-gradient(135deg,rgba(167,139,250,.1),rgba(244,114,182,.08));border:1px solid rgba(167,139,250,.2);border-radius:var(--radius);padding:22px 26px;animation:slideUp .5s ease .25s both;}
.quote-text{font-family:'Nunito',sans-serif;font-size:16px;font-weight:700;color:#fff;line-height:1.5;margin-bottom:8px;}
.quote-author{font-size:12px;color:var(--text2);}

/* DELETE MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(5,10,40,.75);backdrop-filter:blur(6px);z-index:999;align-items:center;justify-content:center;}
.modal-overlay.show{display:flex;}
.modal-box{background:#1a2070;border:1px solid rgba(167,139,250,.25);border-radius:20px;padding:32px 28px;width:340px;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.5);animation:popIn .2s cubic-bezier(.34,1.56,.64,1);}
@keyframes popIn{from{transform:scale(.88);opacity:0}to{transform:scale(1);opacity:1}}
.modal-icon{font-size:40px;margin-bottom:12px;}
.modal-title{font-family:'Nunito',sans-serif;font-size:18px;font-weight:900;color:#fff;margin-bottom:6px;}
.modal-sub{font-size:12px;color:var(--text2);margin-bottom:22px;}
.modal-btns{display:flex;gap:10px;justify-content:center;}
.modal-btn{padding:9px 24px;border-radius:50px;font-size:13px;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer;border:none;transition:transform .12s;}
.modal-btn:hover{transform:translateY(-1px);}
.modal-cancel{background:rgba(255,255,255,.1);color:var(--text2);border:1px solid var(--border)!important;}
.modal-confirm{background:linear-gradient(135deg,#ff5252,#c62828);color:#fff;}

@keyframes slideUp{from{opacity:0;transform:translateY(26px)}to{opacity:1;transform:translateY(0)}}
::-webkit-scrollbar{width:4px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:rgba(255,255,255,.12);border-radius:99px}
</style>
</head>
<body>

<div class="orb orb1"></div>
<div class="orb orb2"></div>
<div class="orb orb3"></div>
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
        <a href="academic.php" class="nav-link"><span class="nav-icon icon-blue">📚</span> Academic</a>
        <a href="personal.php" class="nav-link active"><span class="nav-icon icon-purple">🎨</span> Personal</a>
        <a href="project.php"  class="nav-link"><span class="nav-icon icon-yellow">🚀</span> Project</a>
    </nav>
    <nav class="nav-section">
        <div class="nav-label">More</div>
        <a href="system.php"    class="nav-link"><span class="nav-icon icon-gray">⚙️</span> System Info</a>
        <a href="developer.php" class="nav-link"><span class="nav-icon icon-gray">👨‍💻</span> Developer</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link logout"><span class="nav-icon icon-red">🚪</span> Logout</a>
    </div>
</aside>

<!-- MAIN -->
<main class="main">

    <div class="topbar">
        <div class="topbar-left">
            <h1>🎨 Personal</h1>
            <div class="date-badge">📅 <?= date('l, F j, Y') ?></div>
        </div>
        <div class="pers-chip"><span class="pers-dot"></span> Habits · Goals · Wellbeing</div>
    </div>

    <!-- STATS -->
    <div class="section-label">Overview</div>
    <div class="stats-grid">
        <div class="stat-card sc-purple">
            <div class="stat-icon si-purple">🎨</div>
            <div class="stat-label">Total Tasks</div>
            <div class="stat-num c-purple" data-target="<?= $total ?>"><?= $total ?></div>
        </div>
        <div class="stat-card sc-yellow">
            <div class="stat-icon si-yellow">⏳</div>
            <div class="stat-label">Pending</div>
            <div class="stat-num c-yellow" data-target="<?= $pending ?>"><?= $pending ?></div>
        </div>
        <div class="stat-card sc-green">
            <div class="stat-icon si-green">✅</div>
            <div class="stat-label">Completed</div>
            <div class="stat-num c-green" data-target="<?= $completed ?>"><?= $completed ?></div>
        </div>
        <div class="stat-card sc-pink">
            <div class="stat-icon si-pink">💜</div>
            <div class="stat-label">Progress</div>
            <div class="stat-num c-pink" id="pctNum">0%</div>
        </div>
    </div>

    <!-- PROGRESS -->
    <div class="progress-band">
        <div class="pb-info">
            <div class="pb-label">Personal Progress</div>
            <div class="pb-track"><div class="pb-fill" id="progBar" style="width:0%"></div></div>
            <div class="pb-sub"><?= $completed ?> of <?= $total ?> tasks done</div>
        </div>
        <div class="pb-pct"><?= $progress ?>%</div>
    </div>

    <!-- THREE COLUMNS -->
    <div class="section-label">Tasks, Habits & Mood</div>
    <div class="three-col">

        <!-- TASK LIST -->
        <div class="panel">
            <div class="panel-title">📌 Personal Tasks</div>
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
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state"><span>🎨</span>No personal tasks yet</div>
            <?php endif; ?>
        </div>

        <!-- HABITS -->
        <div class="panel">
            <div class="habit-panel-header">
                <div class="panel-title" style="margin-bottom:0">🔥 Daily Habits</div>
                <button class="habit-add-btn" onclick="toggleAddForm()" title="Add habit">+</button>
            </div>

            <!-- ADD FORM -->
            <form method="POST" class="habit-add-form" id="habitAddForm">
                <input type="hidden" name="action" value="add">
                <input type="text" name="name" class="habit-input" placeholder="New habit name..." required maxlength="100">
                <button type="submit" class="habit-submit">Add Habit</button>
            </form>

            <!-- HABIT LIST -->
            <?php if (count($habits) > 0): ?>
                <?php foreach ($habits as $h): ?>
                <div class="habit-item">
                    <!-- Toggle done -->
                    <form method="POST" style="display:contents">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $h['id'] ?>">
                        <button type="submit" class="habit-check <?= $h['done_today'] ? 'done' : '' ?>" title="Mark done">
                            <?= $h['done_today'] ? '✓' : '' ?>
                        </button>
                    </form>
                    <div class="habit-name"><?= htmlspecialchars($h['name']) ?></div>
                    <div class="habit-streak"><?= $h['streak'] ?>d <?= $h['streak'] >= 5 ? '🔥' : '' ?></div>
                    <!-- Delete -->
                    <button class="habit-del" onclick="confirmDeleteHabit(<?= $h['id'] ?>, '<?= addslashes($h['name']) ?>')" title="Delete">✕</button>
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
                <div class="mood-btn" data-mood="Exhausted"   onclick="selectMood(this)">😫</div>
                <div class="mood-btn" data-mood="Sad"         onclick="selectMood(this)">😕</div>
                <div class="mood-btn" data-mood="Neutral"     onclick="selectMood(this)">😐</div>
                <div class="mood-btn selected" data-mood="Good" onclick="selectMood(this)">🙂</div>
                <div class="mood-btn" data-mood="Amazing"     onclick="selectMood(this)">😄</div>
            </div>
            <div class="mood-label" id="moodLabel">Feeling Good ✨</div>
            <div style="margin-top:20px;border-top:1px solid var(--border);padding-top:16px;">
                <div class="panel-title" style="font-size:13px;margin-bottom:12px;">📅 Mood This Week</div>
                <div style="display:flex;gap:8px;justify-content:space-between;">
                    <?php
                    $days  = ['M','T','W','T','F','S','S'];
                    $moods = ['😄','🙂','😐','😄','🙂','😄','😄'];
                    foreach ($days as $i => $d): ?>
                    <div style="text-align:center;flex:1;">
                        <div style="font-size:16px;margin-bottom:4px;"><?= $moods[$i] ?></div>
                        <div style="font-size:10px;color:var(--text2);font-weight:700;"><?= $d ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- QUOTE -->
    <div class="quote-card">
        <div class="quote-text" id="quoteText">"The secret of getting ahead is getting started."</div>
        <div class="quote-author" id="quoteAuthor">— Mark Twain</div>
    </div>

</main>

<!-- DELETE HABIT MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-icon">🗑️</div>
        <div class="modal-title">Delete Habit?</div>
        <div class="modal-sub" id="modalHabitName">This will permanently remove the habit.</div>
        <div class="modal-btns">
            <button class="modal-btn modal-cancel" onclick="closeModal()">Cancel</button>
            <form method="POST" style="display:contents" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteHabitId">
                <button type="submit" class="modal-btn modal-confirm">Delete</button>
            </form>
        </div>
    </div>
</div>

<script>
/* CURSOR */
const glow = document.getElementById('cursorGlow');
document.addEventListener('mousemove', e => { glow.style.left=e.clientX+'px'; glow.style.top=e.clientY+'px'; });

/* PARTICLES */
const canvas = document.getElementById('particleCanvas');
const ctx = canvas.getContext('2d');
let W,H;
function resize(){W=canvas.width=window.innerWidth;H=canvas.height=window.innerHeight;}
resize(); window.addEventListener('resize',resize);
const COLORS=['#a78bfa','#7c6ef7','#f472b6','#c084fc'];
let pts=Array.from({length:65},()=>({x:Math.random()*window.innerWidth,y:Math.random()*window.innerHeight,r:Math.random()*1.6+.4,vx:(Math.random()-.5)*.35,vy:-Math.random()*.5-.15,alpha:Math.random()*.4+.1,color:COLORS[Math.floor(Math.random()*4)],life:1}));
function draw(){ctx.clearRect(0,0,W,H);pts.forEach((p,i)=>{p.x+=p.vx;p.y+=p.vy;p.life-=.003;if(p.life<=0||p.y<-10)pts[i]={x:Math.random()*W,y:H+10,r:Math.random()*1.6+.4,vx:(Math.random()-.5)*.35,vy:-Math.random()*.5-.15,alpha:Math.random()*.4+.1,color:COLORS[Math.floor(Math.random()*4)],life:1};ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);ctx.fillStyle=p.color;ctx.globalAlpha=p.alpha*p.life;ctx.fill();});ctx.globalAlpha=1;requestAnimationFrame(draw);}
draw();

/* COUNT-UP */
document.querySelectorAll('.stat-num[data-target]').forEach(el=>{
    const target=parseInt(el.dataset.target)||0;
    if(!target)return;
    let n=0;el.textContent='0';
    const step=Math.ceil(target/50);
    const t=setInterval(()=>{n=Math.min(n+step,target);el.textContent=n;if(n>=target)clearInterval(t);},16);
});
const pEl=document.getElementById('pctNum');
let pn=0; const pTarget=<?= $progress ?>;
const pT=setInterval(()=>{pn=Math.min(pn+1,pTarget);pEl.textContent=pn+'%';if(pn>=pTarget)clearInterval(pT);},18);

/* PROGRESS BAR */
setTimeout(()=>{ document.getElementById('progBar').style.width='<?= $progress ?>%'; },400);

/* HABIT ADD FORM TOGGLE */
function toggleAddForm() {
    const form = document.getElementById('habitAddForm');
    form.classList.toggle('show');
    if (form.classList.contains('show')) form.querySelector('input[name="name"]').focus();
}

/* DELETE MODAL */
function confirmDeleteHabit(id, name) {
    document.getElementById('modalHabitName').textContent = 'Delete "' + name + '"? This cannot be undone.';
    document.getElementById('deleteHabitId').value = id;
    document.getElementById('deleteModal').classList.add('show');
}
function closeModal() { document.getElementById('deleteModal').classList.remove('show'); }
document.getElementById('deleteModal').addEventListener('click', function(e){ if(e.target===this) closeModal(); });

/* MOOD */
const moodLabels = {'Exhausted':'Feeling exhausted... rest up 💙','Sad':'Hang in there 💜','Neutral':'Just getting through it 😌','Good':'Feeling good ✨','Amazing':'You\'re crushing it! 🔥'};
function selectMood(btn){
    document.querySelectorAll('.mood-btn').forEach(b=>b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('moodLabel').textContent = moodLabels[btn.dataset.mood] || '';
}

/* QUOTES */
const quotes = [
    {text:'"The secret of getting ahead is getting started."', author:'— Mark Twain'},
    {text:'"Do something today that your future self will thank you for."', author:'— Unknown'},
    {text:'"Small steps every day lead to big results."', author:'— Unknown'},
    {text:'"You don\'t have to be great to start, but you have to start to be great."', author:'— Zig Ziglar'},
    {text:'"Believe you can and you\'re halfway there."', author:'— Theodore Roosevelt'},
];
let qi=0;
function rotateQuote(){
    qi=(qi+1)%quotes.length;
    const qEl=document.getElementById('quoteText'),aEl=document.getElementById('quoteAuthor');
    qEl.style.opacity='0';aEl.style.opacity='0';
    setTimeout(()=>{qEl.textContent=quotes[qi].text;aEl.textContent=quotes[qi].author;qEl.style.transition='opacity .6s';aEl.style.transition='opacity .6s';qEl.style.opacity='1';aEl.style.opacity='1';},400);
}
setInterval(rotateQuote,8000);

/* NAV GLOW */
document.querySelectorAll('.nav-link').forEach(l=>{
    l.addEventListener('mouseenter',()=>{l.style.textShadow='0 0 12px rgba(167,139,250,.4)';});
    l.addEventListener('mouseleave',()=>{l.style.textShadow='';});
});
</script>
</body>
</html>