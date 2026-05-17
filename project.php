<?php
ini_set('display_errors', 0);
error_reporting(0);
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Fetch project tasks from DB
$stmt = $conn->prepare("SELECT * FROM task WHERE category = 'project' ORDER BY due_date ASC");
$stmt->execute();
$allTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total     = count($allTasks);
$completed = count(array_filter($allTasks, fn($t) => $t['status'] === 'completed'));
$pending   = $total - $completed;
$progress  = $total > 0 ? round(($completed / $total) * 100) : 0;

// Group by status
$todo       = array_filter($allTasks, fn($t) => $t['status'] === 'pending');
$doneTasks  = array_filter($allTasks, fn($t) => $t['status'] === 'completed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Project — To-Do List</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Outfit:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root {
    --bg:       #1a1f6e;
    --surface:  rgba(255,255,255,0.06);
    --border:   rgba(255,255,255,0.13);
    --accent:   #4fc3f7;
    --done:     #00e5a0;
    --warn:     #ffb830;
    --danger:   #ff5252;
    --purple:   #a78bfa;
    --text:     #ffffff;
    --text2:    rgba(255,255,255,0.55);
    --sidebar-w:240px;
    --radius:   18px;
    --mono:     'JetBrains Mono', monospace;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Outfit', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    overflow-x: hidden;
}

body::before {
    content: '';
    position: fixed; inset: 0;
    background:
        radial-gradient(ellipse 80% 60% at 15% 10%, rgba(72,92,230,0.55) 0%, transparent 60%),
        radial-gradient(ellipse 60% 50% at 88% 78%, rgba(100,60,210,0.4) 0%, transparent 55%),
        radial-gradient(ellipse 40% 40% at 55% 35%, rgba(30,180,255,0.15) 0%, transparent 60%);
    z-index: 0; pointer-events: none;
    animation: bgShift 12s ease-in-out infinite alternate;
}
@keyframes bgShift {
    0%   { filter: hue-rotate(0deg);  opacity: 1; }
    100% { filter: hue-rotate(18deg); opacity: 0.88; }
}

body::after {
    content: '';
    position: fixed; inset: 0;
    background-image: radial-gradient(circle, rgba(79,195,247,0.28) 1px, transparent 1px);
    background-size: 88px 88px;
    opacity: 0.1;
    animation: floatMotes 40s linear infinite;
    z-index: 0; pointer-events: none;
}
@keyframes floatMotes { from{transform:translateY(0)} to{transform:translateY(-900px)} }

/* ── ORBS ── */
.orb { position:fixed; border-radius:50%; filter:blur(65px); opacity:0.15; pointer-events:none; z-index:0; animation:orbFloat linear infinite; }
.orb1 { width:320px;height:320px;background:#4fc3f7;top:-80px;left:8%;    animation-duration:18s; }
.orb2 { width:260px;height:260px;background:#ffb830;bottom:0; right:6%;   animation-duration:23s;animation-delay:-7s; }
.orb3 { width:200px;height:200px;background:#a78bfa;top:40%;  left:55%;   animation-duration:27s;animation-delay:-13s; }
@keyframes orbFloat {
    0%,100% { transform:translateY(0)    scale(1); }
    33%      { transform:translateY(-42px) scale(1.08); }
    66%      { transform:translateY(20px)  scale(0.94); }
}

#particleCanvas { position:fixed;inset:0;z-index:0;pointer-events:none;opacity:0.45; }
.cursor-glow    { position:fixed;width:320px;height:320px;border-radius:50%;background:radial-gradient(circle,rgba(79,195,247,0.07) 0%,transparent 70%);pointer-events:none;z-index:1;transform:translate(-50%,-50%); }

/* ══ SIDEBAR ══ */
.sidebar {
    position:fixed;left:0;top:0;width:var(--sidebar-w);height:100vh;
    background:rgba(10,15,65,0.93);border-right:1px solid var(--border);
    display:flex;flex-direction:column;z-index:100;backdrop-filter:blur(20px);
}
.sidebar-brand { padding:26px 22px 20px;border-bottom:1px solid var(--border); }
.brand-logo    { display:flex;align-items:center;gap:10px;margin-bottom:4px; }
.brand-icon    { width:36px;height:36px;background:linear-gradient(135deg,#4fc3f7,#7c6ef7);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;animation:iconPulse 3s ease-in-out infinite; }
@keyframes iconPulse {
    0%,100% { box-shadow:0 4px 14px rgba(79,195,247,0.35); }
    50%      { box-shadow:0 4px 24px rgba(79,195,247,0.65); }
}
.brand-name { font-family:'Nunito',sans-serif;font-size:20px;font-weight:900;color:#fff; }
.brand-user { font-size:12px;color:var(--text2);padding-left:4px; }
.nav-section { padding:14px 0 0; }
.nav-label   { font-size:10px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.28);padding:8px 22px 4px; }
.nav-link {
    display:flex;align-items:center;gap:12px;padding:10px 22px;
    font-size:13.5px;font-weight:600;color:var(--text2);text-decoration:none;
    position:relative;transition:color 0.18s,background 0.18s,padding-left 0.2s;
}
.nav-link::before { content:'';position:absolute;left:0;top:4px;bottom:4px;width:3px;border-radius:0 3px 3px 0;background:transparent;transition:background 0.18s; }
.nav-link:hover { color:#fff;background:rgba(255,255,255,0.06);padding-left:28px; }
.nav-link.active { color:#fff;background:rgba(79,195,247,0.12);padding-left:28px; }
.nav-link.active::before { background:var(--accent);box-shadow:0 0 8px rgba(79,195,247,0.6); }
.nav-icon { width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;transition:transform 0.2s; }
.nav-link:hover .nav-icon { transform:scale(1.15) rotate(-5deg); }
.icon-teal   { background:rgba(0,229,160,0.15); }
.icon-blue   { background:rgba(79,195,247,0.15); }
.icon-purple { background:rgba(167,139,250,0.15); }
.icon-yellow { background:rgba(255,184,48,0.15); }
.icon-gray   { background:rgba(255,255,255,0.07); }
.icon-red    { background:rgba(255,80,80,0.12); }
.sidebar-footer { margin-top:auto;padding:16px 22px 24px;border-top:1px solid var(--border); }
.nav-link.logout { color:rgba(255,100,100,0.65)!important; }
.nav-link.logout:hover { color:rgba(255,100,100,0.9)!important;background:rgba(255,60,60,0.07)!important; }

/* ══ MAIN ══ */
.main { margin-left:var(--sidebar-w);flex:1;padding:32px 36px;position:relative;z-index:2;min-height:100vh; }

/* ── TOPBAR ── */
.topbar { display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;animation:slideUp 0.5s ease both; }
.topbar-left h1 { font-family:'Nunito',sans-serif;font-size:26px;font-weight:900; }
.date-badge { display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,0.1);border:1px solid var(--border);border-radius:20px;padding:4px 12px;font-size:12px;color:var(--text2);margin-top:6px; }

.sprint-chip {
    display:inline-flex;align-items:center;gap:8px;
    background:rgba(167,139,250,0.12);border:1px solid rgba(167,139,250,0.3);
    border-radius:50px;padding:9px 20px;font-size:13px;font-weight:700;color:var(--purple);
}
.sprint-dot { width:8px;height:8px;border-radius:50%;background:var(--purple);animation:pulseDot 1.5s ease-in-out infinite; }
@keyframes pulseDot {
    0%,100% { box-shadow:0 0 0 0 rgba(167,139,250,0.6);transform:scale(1); }
    50%      { box-shadow:0 0 0 5px rgba(167,139,250,0);transform:scale(1.2); }
}

/* ── SECTION LABEL ── */
.section-label {
    font-size:10px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;
    color:var(--text2);margin-bottom:14px;display:flex;align-items:center;gap:8px;
}
.section-label::after { content:'';flex:1;height:1px;background:var(--border); }

/* ── STAT CARDS ── */
.stats-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;animation:slideUp 0.5s ease 0.1s both; }

.stat-card {
    background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
    padding:20px 18px;backdrop-filter:blur(10px);position:relative;overflow:hidden;
    transition:transform 0.2s,box-shadow 0.2s,border-color 0.2s;
}
.stat-card:hover { transform:translateY(-5px) scale(1.02); }
.stat-card.sc-total:hover   { border-color:rgba(79,195,247,0.5); box-shadow:0 14px 36px rgba(79,195,247,0.2); }
.stat-card.sc-pending:hover { border-color:rgba(255,184,48,0.5); box-shadow:0 14px 36px rgba(255,184,48,0.2); }
.stat-card.sc-done:hover    { border-color:rgba(0,229,160,0.5);  box-shadow:0 14px 36px rgba(0,229,160,0.2); }
.stat-card.sc-pct:hover     { border-color:rgba(167,139,250,0.5);box-shadow:0 14px 36px rgba(167,139,250,0.2); }

.stat-card::before { content:'';position:absolute;top:0;left:-80%;width:60%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.05),transparent);transform:skewX(-15deg);transition:left 0.55s ease; }
.stat-card:hover::before { left:140%; }

.stat-icon { width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:18px;margin-bottom:12px;transition:transform 0.2s; }
.stat-card:hover .stat-icon { transform:scale(1.2) rotate(-8deg); }
.si-blue   { background:rgba(79,195,247,0.15); }
.si-yellow { background:rgba(255,184,48,0.15); }
.si-green  { background:rgba(0,229,160,0.15); }
.si-purple { background:rgba(167,139,250,0.15); }

.stat-label { font-size:10px;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:var(--text2);margin-bottom:4px; }
.stat-num   { font-family:'Nunito',sans-serif;font-size:32px;font-weight:900;line-height:1;color:#fff; }
.stat-num.c-blue   { color:var(--accent); }
.stat-num.c-yellow { color:var(--warn); }
.stat-num.c-green  { color:var(--done); }
.stat-num.c-purple { color:var(--purple); }

/* ── PROGRESS BAND ── */
.progress-band {
    background:linear-gradient(135deg,rgba(79,195,247,0.1),rgba(167,139,250,0.1));
    border:1px solid rgba(79,195,247,0.2);border-radius:var(--radius);
    padding:18px 24px;display:flex;align-items:center;gap:24px;
    margin-bottom:22px;animation:slideUp 0.5s ease 0.15s both;
}
.pb-info { flex:1; }
.pb-label { font-size:13px;font-weight:700;color:#fff;margin-bottom:8px; }
.pb-track { height:12px;background:rgba(255,255,255,0.1);border-radius:99px;overflow:hidden; }
.pb-fill  { height:100%;background:linear-gradient(90deg,#4fc3f7,#a78bfa);border-radius:99px;transition:width 1.2s cubic-bezier(0.4,0,0.2,1);animation:progPulse 3s ease-in-out infinite; }
@keyframes progPulse {
    0%,100% { box-shadow:0 0 0px rgba(79,195,247,0); }
    50%      { box-shadow:0 0 16px rgba(79,195,247,0.6); }
}
.pb-sub  { font-size:11px;color:var(--text2);margin-top:6px; }
.pb-pct  { font-family:'Nunito',sans-serif;font-size:44px;font-weight:900;color:#fff;text-shadow:0 0 24px rgba(79,195,247,0.5);white-space:nowrap; }

/* ── TWO COL ── */
.two-col { display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:22px;animation:slideUp 0.5s ease 0.2s both; }

/* ── KANBAN ── */
.kanban-wrap { animation:slideUp 0.5s ease 0.25s both; }
.kanban-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:22px; }

.k-col {
    background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
    overflow:hidden;backdrop-filter:blur(10px);
    transition:box-shadow 0.25s;
}
.k-col:hover { box-shadow:0 18px 48px rgba(0,0,0,0.3); }

.k-col-header {
    padding:14px 16px;border-bottom:1px solid var(--border);
    display:flex;align-items:center;justify-content:space-between;
}
.k-col-title { font-family:'Nunito',sans-serif;font-size:14px;font-weight:800; }
.k-col-count { font-size:11px;font-weight:700;padding:3px 10px;border-radius:99px;border:1px solid; }

.todo-col    .k-col-title { color:var(--accent);  }
.todo-col    .k-col-count { background:rgba(79,195,247,0.12); color:var(--accent);  border-color:rgba(79,195,247,0.3); }
.prog-col    .k-col-title { color:var(--warn);    }
.prog-col    .k-col-count { background:rgba(255,184,48,0.12); color:var(--warn);    border-color:rgba(255,184,48,0.3); }
.done-col    .k-col-title { color:var(--done);    }
.done-col    .k-col-count { background:rgba(0,229,160,0.12);  color:var(--done);    border-color:rgba(0,229,160,0.3);  }

.k-body { padding:12px;display:flex;flex-direction:column;gap:8px;min-height:100px; }

.k-card {
    background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);
    border-radius:11px;padding:12px 13px;position:relative;overflow:hidden;
    cursor:pointer;transition:transform 0.18s,background 0.18s,box-shadow 0.18s;
}
.k-card::after { content:'';position:absolute;top:0;left:-100%;width:60%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.07),transparent);transform:skewX(-20deg);transition:left 0.5s ease; }
.k-card:hover::after { left:160%; }
.k-card:hover { transform:translateY(-3px) translateX(2px);background:rgba(255,255,255,0.09);box-shadow:0 8px 24px rgba(0,0,0,0.25); }

.k-card.border-todo { border-left:3px solid var(--accent); }
.k-card.border-prog { border-left:3px solid var(--warn); }
.k-card.border-done { border-left:3px solid var(--done);opacity:0.7; }

.k-card-name { font-size:13px;font-weight:700;color:#fff;margin-bottom:7px;line-height:1.4; }
.k-card-name.striked { text-decoration:line-through;color:var(--text2); }

.k-card-footer { display:flex;align-items:center;justify-content:space-between;gap:6px;flex-wrap:wrap; }

.k-tag { font-size:10px;font-weight:700;padding:3px 9px;border-radius:99px;text-transform:uppercase;letter-spacing:0.04em; }
.tag-ui       { background:rgba(79,195,247,0.15); color:var(--accent); }
.tag-qa       { background:rgba(255,82,82,0.15);  color:var(--danger); }
.tag-backend  { background:rgba(255,184,48,0.15); color:var(--warn); }
.tag-frontend { background:rgba(167,139,250,0.15);color:var(--purple); }
.tag-db       { background:rgba(0,229,160,0.15);  color:var(--done); }
.tag-default  { background:rgba(255,255,255,0.1); color:var(--text2); }

.k-due { font-size:10px;color:var(--text2);display:flex;align-items:center;gap:3px; }

.k-empty { text-align:center;padding:24px 0;color:var(--text2);font-size:12px; }
.k-empty span { font-size:28px;display:block;margin-bottom:8px;opacity:0.4; }

/* ── CHART CARD ── */
.chart-card {
    background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
    padding:22px 24px;backdrop-filter:blur(10px);
}
.chart-title { font-family:'Nunito',sans-serif;font-size:15px;font-weight:800;color:#fff;margin-bottom:18px; }

#burnChart { width:100%!important; }

/* ── TEAM CARD ── */
.team-card {
    background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
    padding:22px 24px;backdrop-filter:blur(10px);
}
.team-title { font-family:'Nunito',sans-serif;font-size:15px;font-weight:800;color:#fff;margin-bottom:16px; }

.member-row {
    display:flex;align-items:center;gap:12px;padding:9px 0;
    border-bottom:1px solid rgba(255,255,255,0.05);transition:background 0.15s;
}
.member-row:last-child { border-bottom:none; }
.member-row:hover { background:rgba(255,255,255,0.03);border-radius:9px;padding-left:6px; }

.member-av {
    width:36px;height:36px;border-radius:50%;display:flex;align-items:center;
    justify-content:center;font-size:16px;flex-shrink:0;
    transition:transform 0.2s;
}
.member-row:hover .member-av { transform:scale(1.15) rotate(-5deg); }

.member-info { flex:1; }
.member-name { font-size:13px;font-weight:700;color:#fff; }
.member-role { font-size:11px;color:var(--text2); }

.member-tasks {
    font-family:var(--mono);font-size:11px;font-weight:500;
    padding:3px 10px;border-radius:99px;
}

/* ── RIPPLE ── */
.ripple { position:absolute;border-radius:50%;background:rgba(79,195,247,0.22);transform:scale(0);animation:rippleAnim 0.6s ease-out forwards;pointer-events:none; }
@keyframes rippleAnim { to { transform:scale(4);opacity:0; } }

/* ── ANIMATIONS ── */
@keyframes slideUp {
    from { opacity:0;transform:translateY(26px); }
    to   { opacity:1;transform:translateY(0); }
}
@keyframes scaleIn {
    from { opacity:0;transform:scale(0.9); }
    to   { opacity:1;transform:scale(1); }
}
.k-col:nth-child(1) { animation:scaleIn 0.5s ease 0.3s both; }
.k-col:nth-child(2) { animation:scaleIn 0.5s ease 0.4s both; }
.k-col:nth-child(3) { animation:scaleIn 0.5s ease 0.5s both; }

::-webkit-scrollbar { width:4px; }
::-webkit-scrollbar-track { background:transparent; }
::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.12);border-radius:99px; }
</style>
</head>
<body>

<!-- ORBS -->
<div class="orb orb1"></div>
<div class="orb orb2"></div>
<div class="orb orb3"></div>
<canvas id="particleCanvas"></canvas>
<div class="cursor-glow" id="cursorGlow"></div>

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">
            <div class="brand-icon">📋</div>
            <span class="brand-name">To-Do List</span>
        </div>
        <div class="brand-user">👋 <?= htmlspecialchars($_SESSION['user']) ?></div>
    </div>
    <nav class="nav-section">
        <div class="nav-label">Main</div>
        <a href="index.php" class="nav-link"><span class="nav-icon icon-teal">🏠</span> Dashboard</a>
    </nav>
    <nav class="nav-section">
        <div class="nav-label">Categories</div>
        <a href="index.php?category=academic" class="nav-link"><span class="nav-icon icon-blue">📚</span> Academic</a>
        <a href="index.php?category=personal" class="nav-link"><span class="nav-icon icon-purple">🎨</span> Personal</a>
        <a href="index.php?category=project"  class="nav-link active"><span class="nav-icon icon-yellow">🚀</span> Project</a>
    </nav>
    <nav class="nav-section">
        <div class="nav-label">More</div>
        <a href="system.php" class="nav-link"><span class="nav-icon icon-gray">⚙️</span> System Info</a>
        <a href="#"          class="nav-link"><span class="nav-icon icon-gray">👨‍💻</span> Developer</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link logout"><span class="nav-icon icon-red">🚪</span> Logout</a>
    </div>
</aside>

<!-- ══ MAIN ══ -->
<main class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <h1>🚀 Project Board</h1>
            <div class="date-badge">📅 <?= date('l, F j, Y') ?></div>
        </div>
        <div class="sprint-chip">
            <span class="sprint-dot"></span> Sprint Active
        </div>
    </div>

    <!-- STAT CARDS -->
    <div class="section-label">Sprint Overview</div>
    <div class="stats-grid">
        <div class="stat-card sc-total">
            <div class="stat-icon si-blue">📋</div>
            <div class="stat-label">Total Tasks</div>
            <div class="stat-num c-blue" data-target="<?= $total ?>"><?= $total ?></div>
        </div>
        <div class="stat-card sc-pending">
            <div class="stat-icon si-yellow">⏳</div>
            <div class="stat-label">Pending</div>
            <div class="stat-num c-yellow" data-target="<?= $pending ?>"><?= $pending ?></div>
        </div>
        <div class="stat-card sc-done">
            <div class="stat-icon si-green">✅</div>
            <div class="stat-label">Completed</div>
            <div class="stat-num c-green" data-target="<?= $completed ?>"><?= $completed ?></div>
        </div>
        <div class="stat-card sc-pct">
            <div class="stat-icon si-purple">📈</div>
            <div class="stat-label">Progress</div>
            <div class="stat-num c-purple" id="pctNum">0%</div>
        </div>
    </div>

    <!-- PROGRESS BAND -->
    <div class="progress-band">
        <div class="pb-info">
            <div class="pb-label">Sprint Progress</div>
            <div class="pb-track">
                <div class="pb-fill" id="sprintBar" style="width:0%"></div>
            </div>
            <div class="pb-sub"><?= $completed ?> of <?= $total ?> tasks completed</div>
        </div>
        <div class="pb-pct"><?= $progress ?>%</div>
    </div>

    <!-- KANBAN -->
    <div class="kanban-wrap">
        <div class="section-label">Kanban Board</div>
        <div class="kanban-grid">

            <!-- TO DO -->
            <div class="k-col todo-col">
                <div class="k-col-header">
                    <span class="k-col-title">📌 To Do</span>
                    <span class="k-col-count"><?= count($todo) ?></span>
                </div>
                <div class="k-body">
                    <?php if (count($todo) > 0): ?>
                        <?php foreach ($todo as $t): ?>
                        <div class="k-card border-todo">
                            <div class="k-card-name"><?= htmlspecialchars($t['task_name']) ?></div>
                            <div class="k-card-footer">
                                <span class="k-tag tag-default">Pending</span>
                                <span class="k-due">📅 <?= $t['due_date'] ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="k-empty"><span>🎉</span>No pending tasks!</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- IN PROGRESS (tasks due soonest from pending) -->
            <div class="k-col prog-col">
                <div class="k-col-header">
                    <span class="k-col-title">⚡ In Progress</span>
                    <span class="k-col-count"><?= min(2, count($todo)) ?></span>
                </div>
                <div class="k-body">
                    <?php
                    $inProg = array_slice($todo, 0, 2);
                    if (count($inProg) > 0):
                        foreach ($inProg as $t):
                    ?>
                    <div class="k-card border-prog">
                        <div class="k-card-name"><?= htmlspecialchars($t['task_name']) ?></div>
                        <div class="k-card-footer">
                            <span class="k-tag tag-backend">Active</span>
                            <span class="k-due">📅 <?= $t['due_date'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; else: ?>
                        <div class="k-empty"><span>✨</span>Nothing in progress</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- DONE -->
            <div class="k-col done-col">
                <div class="k-col-header">
                    <span class="k-col-title">✅ Done</span>
                    <span class="k-col-count"><?= count($doneTasks) ?></span>
                </div>
                <div class="k-body">
                    <?php if (count($doneTasks) > 0): ?>
                        <?php foreach ($doneTasks as $t): ?>
                        <div class="k-card border-done">
                            <div class="k-card-name striked"><?= htmlspecialchars($t['task_name']) ?></div>
                            <div class="k-card-footer">
                                <span class="k-tag tag-db" style="background:rgba(0,229,160,0.15);color:var(--done)">Done</span>
                                <span class="k-due">📅 <?= $t['due_date'] ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="k-empty"><span>🚀</span>Start completing tasks!</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- BURN CHART + TEAM -->
    <div class="two-col">

        <!-- BURN DOWN CHART -->
        <div class="chart-card">
            <div class="chart-title">📉 Burn Down Chart</div>
            <canvas id="burnChart" height="200"></canvas>
        </div>

        <!-- TEAM -->
        <div class="team-card">
            <div class="team-title">👥 Team</div>
            <?php
            $members = [
                ['emoji'=>'🧑‍💻','name'=>'Lead Dev','role'=>'Full Stack','tasks'=>3,'color'=>'rgba(79,195,247,0.15)','tc'=>'rgba(79,195,247,0.2)','tv'=>'#4fc3f7'],
                ['emoji'=>'🎨','name'=>'UI Designer','role'=>'Frontend','tasks'=>2,'color'=>'rgba(167,139,250,0.15)','tc'=>'rgba(167,139,250,0.2)','tv'=>'#a78bfa'],
                ['emoji'=>'🛠️','name'=>'Backend Dev','role'=>'API / DB','tasks'=>2,'color'=>'rgba(255,184,48,0.15)','tc'=>'rgba(255,184,48,0.2)','tv'=>'#ffb830'],
                ['emoji'=>'🧪','name'=>'QA Tester','role'=>'Testing','tasks'=>1,'color'=>'rgba(0,229,160,0.15)','tc'=>'rgba(0,229,160,0.2)','tv'=>'#00e5a0'],
            ];
            foreach ($members as $m): ?>
            <div class="member-row">
                <div class="member-av" style="background:<?= $m['color'] ?>"><?= $m['emoji'] ?></div>
                <div class="member-info">
                    <div class="member-name"><?= $m['name'] ?></div>
                    <div class="member-role"><?= $m['role'] ?></div>
                </div>
                <span class="member-tasks" style="background:<?= $m['tc'] ?>;color:<?= $m['tv'] ?>">
                    <?= $m['tasks'] ?> tasks
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
/* ── CURSOR GLOW ── */
const glow = document.getElementById('cursorGlow');
document.addEventListener('mousemove', e => { glow.style.left=e.clientX+'px'; glow.style.top=e.clientY+'px'; });

/* ── PARTICLES ── */
const canvas = document.getElementById('particleCanvas');
const ctx = canvas.getContext('2d');
let W, H;
function resize() { W=canvas.width=window.innerWidth; H=canvas.height=window.innerHeight; }
resize(); window.addEventListener('resize', resize);
const COLORS = ['#4fc3f7','#a78bfa','#ffb830','#00e5a0'];
let pts = Array.from({length:65}, () => ({
    x:Math.random()*window.innerWidth, y:Math.random()*window.innerHeight,
    r:Math.random()*1.6+0.4, vx:(Math.random()-0.5)*0.35, vy:-Math.random()*0.5-0.15,
    alpha:Math.random()*0.4+0.1, color:COLORS[Math.floor(Math.random()*4)], life:1
}));
function draw() {
    ctx.clearRect(0,0,W,H);
    pts.forEach((p,i)=>{
        p.x+=p.vx; p.y+=p.vy; p.life-=0.003;
        if(p.life<=0||p.y<-10) pts[i]={x:Math.random()*W,y:H+10,r:Math.random()*1.6+0.4,vx:(Math.random()-0.5)*0.35,vy:-Math.random()*0.5-0.15,alpha:Math.random()*0.4+0.1,color:COLORS[Math.floor(Math.random()*4)],life:1};
        ctx.beginPath(); ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
        ctx.fillStyle=p.color; ctx.globalAlpha=p.alpha*p.life; ctx.fill();
    });
    ctx.globalAlpha=1; requestAnimationFrame(draw);
}
draw();

/* ── STAT COUNT-UP ── */
document.querySelectorAll('.stat-num[data-target]').forEach(el => {
    const target = parseInt(el.dataset.target) || 0;
    if (!target) return;
    let n = 0;
    el.textContent = '0';
    const step = Math.ceil(target / (800/16));
    const t = setInterval(() => {
        n = Math.min(n+step, target);
        el.textContent = n;
        if(n>=target) clearInterval(t);
    }, 16);
});

/* Progress % count-up */
const pctEl = document.getElementById('pctNum');
let pn = 0;
const pTarget = <?= $progress ?>;
const pStep = Math.max(1, Math.ceil(pTarget / (800/16)));
const pTimer = setInterval(()=>{
    pn = Math.min(pn+pStep, pTarget);
    pctEl.textContent = pn + '%';
    if(pn>=pTarget) clearInterval(pTimer);
}, 16);

/* Sprint bar */
setTimeout(()=>{
    document.getElementById('sprintBar').style.width = '<?= $progress ?>%';
}, 400);

/* ── RIPPLE ON KCARD ── */
document.querySelectorAll('.k-card').forEach(card => {
    card.addEventListener('click', e => {
        const r = document.createElement('span');
        r.className = 'ripple';
        const size = Math.max(card.offsetWidth, card.offsetHeight);
        r.style.cssText = `width:${size}px;height:${size}px;left:${e.offsetX-size/2}px;top:${e.offsetY-size/2}px`;
        card.appendChild(r);
        setTimeout(()=>r.remove(), 650);
    });
});

/* ── 3D TILT ON KANBAN COLS ── */
document.querySelectorAll('.k-col').forEach(col => {
    col.addEventListener('mousemove', e => {
        const rect = col.getBoundingClientRect();
        const x = (e.clientX-rect.left)/rect.width  - 0.5;
        const y = (e.clientY-rect.top) /rect.height - 0.5;
        col.style.transform = `perspective(600px) rotateY(${x*5}deg) rotateX(${-y*5}deg) translateY(-4px)`;
    });
    col.addEventListener('mouseleave', ()=>{ col.style.transform=''; });
});

/* ── BURN DOWN CHART ── */
const labels = ['Day 1','Day 2','Day 3','Day 4','Day 5','Day 6','Day 7','Day 8','Day 9','Day 10'];
const ideal  = [<?= $total ?>,<?= round($total*0.9) ?>,<?= round($total*0.8) ?>,<?= round($total*0.7) ?>,<?= round($total*0.6) ?>,<?= round($total*0.5) ?>,<?= round($total*0.4) ?>,<?= round($total*0.3) ?>,<?= round($total*0.15) ?>,0];
const actual = [<?= $total ?>,<?= $total ?>,<?= max(0,$total-1) ?>,<?= max(0,$total-1) ?>,<?= max(0,$total-2) ?>,<?= max(0,$pending+1) ?>,<?= $pending ?>,null,null,null];

new Chart(document.getElementById('burnChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [
            {
                label: 'Ideal',
                data: ideal,
                borderColor: 'rgba(79,195,247,0.5)',
                borderDash: [6,4],
                borderWidth: 2,
                pointRadius: 0,
                tension: 0.3,
                fill: false,
            },
            {
                label: 'Actual',
                data: actual,
                borderColor: '#a78bfa',
                borderWidth: 2.5,
                backgroundColor: 'rgba(167,139,250,0.08)',
                pointBackgroundColor: '#a78bfa',
                pointRadius: 4,
                pointHoverRadius: 7,
                tension: 0.3,
                fill: true,
                spanGaps: false,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { labels: { color: 'rgba(255,255,255,0.6)', font:{size:12}, boxWidth:16, padding:16 } },
            tooltip: {
                backgroundColor: 'rgba(20,25,80,0.95)',
                borderColor: 'rgba(79,195,247,0.3)',
                borderWidth: 1,
                titleColor: '#fff',
                bodyColor: 'rgba(255,255,255,0.7)',
                callbacks: { label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y} tasks remaining` }
            }
        },
        scales: {
            x: { grid:{color:'rgba(255,255,255,0.05)'}, ticks:{color:'rgba(255,255,255,0.4)',font:{size:11}} },
            y: { grid:{color:'rgba(255,255,255,0.05)'}, ticks:{color:'rgba(255,255,255,0.4)',font:{size:11}}, beginAtZero:true }
        }
    }
});

/* ── NAV GLOW ── */
document.querySelectorAll('.nav-link').forEach(l => {
    l.addEventListener('mouseenter',()=>{ l.style.textShadow='0 0 12px rgba(79,195,247,0.4)'; });
    l.addEventListener('mouseleave',()=>{ l.style.textShadow=''; });
});
</script>
</body>
</html>