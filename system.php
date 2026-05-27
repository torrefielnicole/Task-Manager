<?php
ini_set('display_errors', 0);
error_reporting(0);
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Info — To-Do List</title>
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
        radial-gradient(ellipse 80% 60% at 20% 10%, rgba(72,92,230,0.55) 0%, transparent 60%),
        radial-gradient(ellipse 60% 50% at 85% 80%, rgba(100,60,210,0.4) 0%, transparent 55%),
        radial-gradient(ellipse 40% 40% at 60% 30%, rgba(30,180,255,0.18) 0%, transparent 60%);
    z-index: 0; pointer-events: none;
    animation: bgShift 12s ease-in-out infinite alternate;
}
@keyframes bgShift {
    0%   { opacity:1;    filter:hue-rotate(0deg); }
    100% { opacity:0.85; filter:hue-rotate(18deg); }
}

body::after {
    content: '';
    position: fixed; inset: 0;
    background-image: radial-gradient(circle, rgba(79,195,247,0.3) 1px, transparent 1px);
    background-size: 90px 90px;
    opacity: 0.1;
    animation: floatMotes 40s linear infinite;
    z-index: 0; pointer-events: none;
}
@keyframes floatMotes {
    from { transform: translateY(0); }
    to   { transform: translateY(-900px); }
}

/* ── ORBS ── */
.orb { position:fixed; border-radius:50%; filter:blur(65px); opacity:0.15; pointer-events:none; z-index:0; animation:orbFloat linear infinite; }
.orb1 { width:320px;height:320px;background:#4fc3f7;top:-80px; left:8%;   animation-duration:18s; }
.orb2 { width:260px;height:260px;background:#7c6ef7;bottom:0;  right:6%;  animation-duration:23s;animation-delay:-7s;  }
.orb3 { width:200px;height:200px;background:#00e5a0;top:38%;   left:58%;  animation-duration:27s;animation-delay:-13s; }
@keyframes orbFloat {
    0%,100% { transform:translateY(0)    scale(1);    }
    33%      { transform:translateY(-40px) scale(1.08); }
    66%      { transform:translateY(20px)  scale(0.94); }
}

/* ── PARTICLE CANVAS ── */
#particleCanvas { position:fixed; inset:0; z-index:0; pointer-events:none; opacity:0.45; }

/* ── CURSOR GLOW ── */
.cursor-glow { position:fixed; width:320px; height:320px; border-radius:50%; background:radial-gradient(circle,rgba(79,195,247,0.07) 0%,transparent 70%); pointer-events:none; z-index:1; transform:translate(-50%,-50%); }

/* ══════════════════════════════════════
   SIDEBAR
══════════════════════════════════════ */
.sidebar {
    position: fixed; left:0; top:0;
    width: var(--sidebar-w); height:100vh;
    background: rgba(10,15,65,0.93);
    border-right: 1px solid var(--border);
    display: flex; flex-direction:column;
    z-index: 100; backdrop-filter: blur(20px);
}

.sidebar-brand { padding:26px 22px 20px; border-bottom:1px solid var(--border); }

.brand-logo { display:flex; align-items:center; gap:10px; margin-bottom:4px; }

.brand-icon {
    width:36px; height:36px;
    background: linear-gradient(135deg,#4fc3f7,#7c6ef7);
    border-radius:10px; display:flex; align-items:center; justify-content:center;
    font-size:18px; box-shadow:0 4px 14px rgba(79,195,247,0.35);
    animation: iconPulse 3s ease-in-out infinite;
}
@keyframes iconPulse {
    0%,100% { box-shadow:0 4px 14px rgba(79,195,247,0.35); }
    50%      { box-shadow:0 4px 22px rgba(79,195,247,0.6);  }
}

.brand-name { font-family:'Nunito',sans-serif; font-size:20px; font-weight:900; color:#fff; }
.brand-user { font-size:12px; color:var(--text2); padding-left:4px; }

.nav-section { padding:14px 0 0; }
.nav-label   { font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:rgba(255,255,255,0.28); padding:8px 22px 4px; }

.nav-link {
    display:flex; align-items:center; gap:12px;
    padding:10px 22px; font-size:13.5px; font-weight:600;
    color:var(--text2); text-decoration:none; position:relative;
    transition:color 0.18s, background 0.18s, padding-left 0.2s;
}
.nav-link::before {
    content:''; position:absolute; left:0; top:4px; bottom:4px;
    width:3px; border-radius:0 3px 3px 0; background:transparent;
    transition:background 0.18s;
}
.nav-link:hover { color:#fff; background:rgba(255,255,255,0.06); padding-left:28px; }
.nav-link.active { color:#fff; background:rgba(79,195,247,0.12); padding-left:28px; }
.nav-link.active::before { background:var(--accent); box-shadow:0 0 8px rgba(79,195,247,0.6); }

.nav-icon { width:32px; height:32px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; transition:transform 0.2s; }
.nav-link:hover .nav-icon { transform:scale(1.15) rotate(-5deg); }

.icon-teal   { background:rgba(0,229,160,0.15); }
.icon-blue   { background:rgba(79,195,247,0.15); }
.icon-purple { background:rgba(167,139,250,0.15); }
.icon-yellow { background:rgba(255,184,48,0.15); }
.icon-gray   { background:rgba(255,255,255,0.07); }
.icon-red    { background:rgba(255,80,80,0.12); }

.sidebar-footer { margin-top:auto; padding:16px 22px 24px; border-top:1px solid var(--border); }
.nav-link.logout { color:rgba(255,100,100,0.65) !important; }
.nav-link.logout:hover { color:rgba(255,100,100,0.9) !important; background:rgba(255,60,60,0.07) !important; }

/* ══════════════════════════════════════
   MAIN
══════════════════════════════════════ */
.main { margin-left:var(--sidebar-w); flex:1; padding:32px 36px; position:relative; z-index:2; }

/* ── TOPBAR ── */
.topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:32px; animation:slideUp 0.5s ease both; }
.topbar-left h1 { font-family:'Nunito',sans-serif; font-size:26px; font-weight:900; }
.date-badge { display:inline-flex; align-items:center; gap:5px; background:rgba(255,255,255,0.1); border:1px solid var(--border); border-radius:20px; padding:4px 12px; font-size:12px; color:var(--text2); margin-top:6px; }

.sys-status-chip {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(0,229,160,0.12); border:1px solid rgba(0,229,160,0.3);
    border-radius:50px; padding:8px 18px; font-size:13px; font-weight:700; color:var(--done);
}
.sys-status-chip .pulse-dot {
    width:8px; height:8px; border-radius:50%; background:var(--done);
    animation:pulseDot 1.5s ease-in-out infinite;
}
@keyframes pulseDot {
    0%,100% { transform:scale(1);   opacity:1;   box-shadow:0 0 0 0 rgba(0,229,160,0.6); }
    50%      { transform:scale(1.2); opacity:0.8; box-shadow:0 0 0 5px rgba(0,229,160,0);  }
}

/* ══════════════════════════════════════
   SECTION LABEL
══════════════════════════════════════ */
.section-label {
    font-size:10px; font-weight:700; letter-spacing:0.12em;
    text-transform:uppercase; color:var(--text2); margin-bottom:12px;
    display:flex; align-items:center; gap:8px;
}
.section-label::after { content:''; flex:1; height:1px; background:var(--border); }

/* ══════════════════════════════════════
   METRIC CARDS GRID
══════════════════════════════════════ */
.metrics-grid {
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:14px;
    margin-bottom:20px;
    animation:slideUp 0.5s ease 0.1s both;
}

.metric-card {
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:var(--radius);
    padding:20px 18px;
    backdrop-filter:blur(10px);
    position:relative; overflow:hidden;
    transition:transform 0.2s, box-shadow 0.2s, border-color 0.2s;
}
.metric-card:hover { transform:translateY(-4px) scale(1.02); }
.metric-card.mc-cpu:hover     { border-color:rgba(79,195,247,0.45);  box-shadow:0 12px 32px rgba(79,195,247,0.18); }
.metric-card.mc-mem:hover     { border-color:rgba(0,229,160,0.45);   box-shadow:0 12px 32px rgba(0,229,160,0.18);  }
.metric-card.mc-disk:hover    { border-color:rgba(255,184,48,0.45);  box-shadow:0 12px 32px rgba(255,184,48,0.18); }
.metric-card.mc-uptime:hover  { border-color:rgba(167,139,250,0.45); box-shadow:0 12px 32px rgba(167,139,250,0.18);}

/* shimmer */
.metric-card::before {
    content:''; position:absolute; top:0; left:-80%; width:60%; height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.05),transparent);
    transform:skewX(-15deg); transition:left 0.55s ease;
}
.metric-card:hover::before { left:140%; }

.metric-icon {
    width:40px; height:40px; border-radius:11px;
    display:flex; align-items:center; justify-content:center;
    font-size:18px; margin-bottom:12px;
    transition:transform 0.2s;
}
.metric-card:hover .metric-icon { transform:scale(1.18) rotate(-8deg); }
.mi-cpu    { background:rgba(79,195,247,0.15); }
.mi-mem    { background:rgba(0,229,160,0.15);  }
.mi-disk   { background:rgba(255,184,48,0.15); }
.mi-uptime { background:rgba(167,139,250,0.15);}

.metric-label { font-size:11px; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:var(--text2); margin-bottom:4px; }
.metric-val   { font-family:'Nunito',sans-serif; font-size:24px; font-weight:900; color:#fff; line-height:1; margin-bottom:12px; }
.metric-val.c-cpu    { color:#4fc3f7; }
.metric-val.c-mem    { color:#00e5a0; }
.metric-val.c-disk   { color:#ffb830; }
.metric-val.c-uptime { color:#a78bfa; }

/* mini progress bar */
.mbar-track { height:6px; background:rgba(255,255,255,0.1); border-radius:99px; overflow:hidden; }
.mbar-fill  { height:100%; border-radius:99px; transition:width 1.2s cubic-bezier(0.4,0,0.2,1); }
.mbar-fill.cpu-bar   { background:linear-gradient(90deg,#4fc3f7,#7c6ef7); }
.mbar-fill.mem-bar   { background:linear-gradient(90deg,#00e5a0,#00b377); }
.mbar-fill.disk-bar  { background:linear-gradient(90deg,#ffb830,#e67e22); }

/* ══════════════════════════════════════
   PERF BARS CARD
══════════════════════════════════════ */
.perf-card {
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:var(--radius);
    padding:22px 24px;
    backdrop-filter:blur(10px);
    margin-bottom:20px;
    animation:slideUp 0.5s ease 0.2s both;
}

.perf-row { margin-bottom:16px; }
.perf-row:last-child { margin-bottom:0; }

.perf-top { display:flex; justify-content:space-between; align-items:center; margin-bottom:7px; }
.perf-name { font-size:13px; font-weight:600; color:#fff; }
.perf-pct  { font-size:12px; font-weight:700; font-family:var(--mono); }

.perf-track { height:8px; background:rgba(255,255,255,0.08); border-radius:99px; overflow:hidden; }
.perf-fill  { height:100%; border-radius:99px; position:relative; }
.perf-fill::after {
    content:''; position:absolute; inset:0;
    background:linear-gradient(90deg, transparent 60%, rgba(255,255,255,0.2));
    animation:shimmerBar 2s ease-in-out infinite;
}
@keyframes shimmerBar {
    0%,100% { opacity:0.4; } 50% { opacity:1; }
}

/* ══════════════════════════════════════
   TWO COLUMN LAYOUT
══════════════════════════════════════ */
.two-col { display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:20px; animation:slideUp 0.5s ease 0.25s both; }

/* ══════════════════════════════════════
   SERVER INFO TABLE
══════════════════════════════════════ */
.info-card {
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:var(--radius);
    padding:20px 22px;
    backdrop-filter:blur(10px);
}

.info-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:9px 0;
    border-bottom:1px solid rgba(255,255,255,0.05);
    transition:background 0.15s;
}
.info-row:last-child { border-bottom:none; }
.info-row:hover { background:rgba(255,255,255,0.03); border-radius:8px; padding-left:6px; }

.info-key { font-size:12px; color:var(--text2); font-weight:600; }
.info-val { font-family:var(--mono); font-size:12px; color:#fff; font-weight:500; }
.info-val.c-green  { color:var(--done); }
.info-val.c-blue   { color:var(--accent); }
.info-val.c-yellow { color:var(--warn); }

/* ══════════════════════════════════════
   LIVE LOG
══════════════════════════════════════ */
.log-card {
    background:rgba(5,8,30,0.7);
    border:1px solid var(--border);
    border-radius:var(--radius);
    overflow:hidden;
    backdrop-filter:blur(10px);
    animation:slideUp 0.5s ease 0.3s both;
}

.log-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 18px;
    background:rgba(255,255,255,0.04);
    border-bottom:1px solid var(--border);
}

.log-title { font-family:var(--mono); font-size:13px; font-weight:500; color:var(--accent); }

.log-live {
    display:flex; align-items:center; gap:6px;
    font-size:11px; font-weight:700; color:var(--done);
}
.log-live-dot {
    width:7px; height:7px; border-radius:50%; background:var(--done);
    animation:pulseDot 1.2s ease-in-out infinite;
}

.log-body { padding:8px 0; max-height:260px; overflow-y:auto; }

.log-line {
    display:flex; align-items:center; gap:10px;
    padding:7px 18px; font-family:var(--mono); font-size:12px;
    transition:background 0.15s;
    animation:logSlide 0.4s ease both;
}
.log-line:hover { background:rgba(255,255,255,0.04); }

@keyframes logSlide {
    from { opacity:0; transform:translateX(-10px); }
    to   { opacity:1; transform:translateX(0); }
}

.log-t    { color:rgba(255,255,255,0.3); font-size:11px; min-width:70px; }
.log-ok   { color:var(--done);   font-weight:700; min-width:52px; }
.log-warn { color:var(--warn);   font-weight:700; min-width:52px; }
.log-err  { color:var(--danger); font-weight:700; min-width:52px; }
.log-msg  { color:rgba(255,255,255,0.7); flex:1; }

/* ══════════════════════════════════════
   ANIMATIONS
══════════════════════════════════════ */
@keyframes slideUp {
    from { opacity:0; transform:translateY(24px); }
    to   { opacity:1; transform:translateY(0); }
}

/* ── SCROLLBAR ── */
::-webkit-scrollbar { width:4px; }
::-webkit-scrollbar-track { background:transparent; }
::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.1); border-radius:99px; }
    /* ── SCROLLBAR ── */
    ::-webkit-scrollbar { width:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.1); border-radius:99px; }

    /* Mobile / Responsive tweaks (match Academic.php responsiveness) */
    .mobile-menu-btn{
        display:none;
        position:fixed;
        top:16px;
        left:16px;
        z-index:1100;
        width:44px;
        height:44px;
        border:none;
        border-radius:12px;
        background:rgba(15,20,70,.95);
        color:#fff;
        font-size:20px;
        display:flex; align-items:center; justify-content:center;
        backdrop-filter:blur(8px);
        cursor:pointer;
    }

    .sidebar-overlay{
        display:none;
        position:fixed; inset:0;
        background:rgba(0,0,0,0.5);
        z-index:1050;
    }

    @media (max-width:1100px){
        .metrics-grid{ grid-template-columns:repeat(2,1fr); }
    }

    @media (max-width:900px){
        .mobile-menu-btn{ display:flex; }
        .sidebar{ transform:translateX(-100%); transition:transform 0.28s ease; z-index:1101; }
        .sidebar.show{ transform:translateX(0); }
        .sidebar-overlay.show{ display:block; }
        .main{ margin-left:0; padding:82px 18px 20px; }
        .two-col{ grid-template-columns:1fr; }
        .topbar{ flex-direction:column; align-items:flex-start; gap:10px; }
        .metrics-grid{ grid-template-columns:1fr; }
    }

    @media (max-width:480px){
        .main{ padding:72px 12px 18px; }
        .topbar-left h1{ font-size:20px; }
        .metric-val{ font-size:20px; }
    }

</style>
</head>
<body>

<button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ORBS -->
<div class="orb orb1"></div>
<div class="orb orb2"></div>
<div class="orb orb3"></div>

<!-- PARTICLES -->
<canvas id="particleCanvas"></canvas>

<!-- CURSOR GLOW -->
<div class="cursor-glow" id="cursorGlow"></div>

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">
            <div class="brand-icon">📋</div>
            <span class="brand-name">To-Do List</span>
        </div>
        <div class="brand-user">👋 <?= htmlspecialchars($_SESSION['user']) ?></div>
    </div>

    <nav class="nav-section">
        <div class="nav-label">Main</div>
        <a href="index.php" class="nav-link">
            <span class="nav-icon icon-teal">🏠</span> Dashboard
        </a>
    </nav>

    <nav class="nav-section">
        <div class="nav-label">Categories</div>
        <a href="index.php?category=academic" class="nav-link">
            <span class="nav-icon icon-blue">📚</span> Academic
        </a>
        <a href="index.php?category=personal" class="nav-link">
            <span class="nav-icon icon-purple">🎨</span> Personal
        </a>
        <a href="index.php?category=project" class="nav-link">
            <span class="nav-icon icon-yellow">🚀</span> Project
        </a>
    </nav>

    <nav class="nav-section">
        <div class="nav-label">More</div>
        <a href="system.php" class="nav-link active">
            <span class="nav-icon icon-gray">⚙️</span> System Info
        </a>
        <a href="developer.php" class="nav-link">
            <span class="nav-icon icon-gray">👨‍💻</span> Developer
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link logout">
            <span class="nav-icon icon-red">🚪</span> Logout
        </a>
    </div>
</aside>

<!-- ══ MAIN ══ -->
<main class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <h1>⚙️ System Info</h1>
            <div class="date-badge">📅 <?= date('l, F j, Y') ?></div>
        </div>
        <div class="sys-status-chip">
            <span class="pulse-dot"></span> All Systems Operational
        </div>
    </div>

    <!-- METRIC CARDS -->
    <div class="section-label">Live Metrics</div>
    <div class="metrics-grid">
        <div class="metric-card mc-cpu">
            <div class="metric-icon mi-cpu">🖥️</div>
            <div class="metric-label">CPU Usage</div>
            <div class="metric-val c-cpu" id="cpuVal">—</div>
            <div class="mbar-track"><div class="mbar-fill cpu-bar" id="cpuBar" style="width:0%"></div></div>
        </div>
        <div class="metric-card mc-mem">
            <div class="metric-icon mi-mem">🧠</div>
            <div class="metric-label">Memory</div>
            <div class="metric-val c-mem" id="memVal">—</div>
            <div class="mbar-track"><div class="mbar-fill mem-bar" id="memBar" style="width:0%"></div></div>
        </div>
        <div class="metric-card mc-disk">
            <div class="metric-icon mi-disk">💾</div>
            <div class="metric-label">Disk</div>
            <div class="metric-val c-disk" id="diskVal">—</div>
            <div class="mbar-track"><div class="mbar-fill disk-bar" id="diskBar" style="width:0%"></div></div>
        </div>
        <div class="metric-card mc-uptime">
            <div class="metric-icon mi-uptime">⏱️</div>
            <div class="metric-label">Uptime</div>
            <div class="metric-val c-uptime" id="uptimeVal">—</div>
        </div>
    </div>

    <!-- PERF BARS -->
    <div class="perf-card">
        <div class="section-label" style="margin-bottom:18px">Performance</div>
        <div class="perf-row">
            <div class="perf-top">
                <span class="perf-name">🖥️ CPU</span>
                <span class="perf-pct c-cpu" id="cpuPct2">—</span>
            </div>
            <div class="perf-track"><div class="perf-fill cpu-bar" id="cpuBar2" style="width:0%"></div></div>
        </div>
        <div class="perf-row">
            <div class="perf-top">
                <span class="perf-name">🧠 Memory</span>
                <span class="perf-pct c-mem" id="memPct2">—</span>
            </div>
            <div class="perf-track"><div class="perf-fill mem-bar" id="memBar2" style="width:0%"></div></div>
        </div>
        <div class="perf-row">
            <div class="perf-top">
                <span class="perf-name">💾 Disk</span>
                <span class="perf-pct c-disk" id="diskPct2">—</span>
            </div>
            <div class="perf-track"><div class="perf-fill disk-bar" id="diskBar2" style="width:0%"></div></div>
        </div>
    </div>

    <!-- TWO COLUMNS: Server Info + PHP Info -->
    <div class="two-col">
        <div class="info-card">
            <div class="section-label" style="margin-bottom:14px">Server Info</div>
            <div class="info-row">
                <span class="info-key">Server Software</span>
                <span class="info-val c-blue"><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Apache') ?></span>
            </div>
            <div class="info-row">
                <span class="info-key">PHP Version</span>
                <span class="info-val c-green"><?= PHP_VERSION ?></span>
            </div>
            <div class="info-row">
                <span class="info-key">Server OS</span>
                <span class="info-val"><?= PHP_OS ?></span>
            </div>
            <div class="info-row">
                <span class="info-key">Host</span>
                <span class="info-val c-blue"><?= htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'localhost') ?></span>
            </div>
            <div class="info-row">
                <span class="info-key">Server Time</span>
                <span class="info-val" id="serverClock">—</span>
            </div>
            <div class="info-row">
                <span class="info-key">Timezone</span>
                <span class="info-val c-yellow"><?= date_default_timezone_get() ?></span>
            </div>
        </div>

        <div class="info-card">
            <div class="section-label" style="margin-bottom:14px">Database</div>
            <div class="info-row">
                <span class="info-key">Connection</span>
                <span class="info-val c-green">● Connected</span>
            </div>
            <div class="info-row">
                <span class="info-key">Driver</span>
                <span class="info-val c-blue">MySQLi</span>
            </div>
            <div class="info-row">
                <span class="info-key">MySQL Version</span>
                <span class="info-val c-green"><?= $conn->server_info ?></span>
            </div>
            <div class="info-row">
                <span class="info-key">Charset</span>
                <span class="info-val">utf8mb4</span>
            </div>
            <div class="info-row">
                <span class="info-key">Max Connections</span>
                <span class="info-val c-yellow">151</span>
            </div>
            <div class="info-row">
                <span class="info-key">Memory Limit</span>
                <span class="info-val c-yellow"><?= ini_get('memory_limit') ?></span>
            </div>
        </div>
    </div>

    <!-- LIVE LOG -->
    <div class="log-card">
        <div class="log-header">
            <span class="log-title">~/taskflow/logs/system.log</span>
            <span class="log-live"><span class="log-live-dot"></span> LIVE</span>
        </div>
        <div class="log-body" id="logBody">
            <div class="log-line"><span class="log-t"><?= date('H:i:s') ?></span><span class="log-ok">[OK]</span><span class="log-msg"> system initialized</span></div>
            <div class="log-line"><span class="log-t"><?= date('H:i:s', time()-60) ?></span><span class="log-ok">[OK]</span><span class="log-msg">Database connection pool healthy</span></div>
            <div class="log-line"><span class="log-t"><?= date('H:i:s', time()-180) ?></span><span class="log-ok">[OK]</span><span class="log-msg">Scheduled backup completed successfully</span></div>
            <div class="log-line"><span class="log-t"><?= date('H:i:s', time()-400) ?></span><span class="log-warn">[WARN]</span><span class="log-msg">High memory usage — worker process #3</span></div>
            <div class="log-line"><span class="log-t"><?= date('H:i:s', time()-720) ?></span><span class="log-ok">[OK]</span><span class="log-msg">Session cleanup: 14 expired sessions removed</span></div>
            <div class="log-line"><span class="log-t"><?= date('H:i:s', time()-1200) ?></span><span class="log-ok">[OK]</span><span class="log-msg">System health check passed — all services running</span></div>
            <div class="log-line"><span class="log-t"><?= date('H:i:s', time()-2400) ?></span><span class="log-err">[ERR]</span><span class="log-msg">SMTP timeout — retry limit reached</span></div>
            <div class="log-line"><span class="log-t"><?= date('H:i:s', time()-3600) ?></span><span class="log-ok">[OK]</span><span class="log-msg">User session authenticated: <?= htmlspecialchars($_SESSION['user']) ?></span></div>
        </div>
    </div>

</main>

<script>
/* ── CURSOR GLOW ── */
const glow = document.getElementById('cursorGlow');
document.addEventListener('mousemove', e => {
    glow.style.left = e.clientX + 'px';
    glow.style.top  = e.clientY + 'px';
});

/* ── PARTICLE SYSTEM ── */
const canvas = document.getElementById('particleCanvas');
const ctx = canvas.getContext('2d');
let W, H;
function resize() { W = canvas.width = window.innerWidth; H = canvas.height = window.innerHeight; }
resize(); window.addEventListener('resize', resize);
const COLORS = ['#4fc3f7','#7c6ef7','#00e5a0','#ffb830'];
let particles = Array.from({length:70}, () => ({
    x:Math.random()*window.innerWidth, y:Math.random()*window.innerHeight,
    r:Math.random()*1.6+0.4, vx:(Math.random()-0.5)*0.35, vy:-Math.random()*0.5-0.15,
    alpha:Math.random()*0.45+0.1, color:COLORS[Math.floor(Math.random()*4)], life:1
}));
function drawParticles() {
    ctx.clearRect(0,0,W,H);
    particles.forEach((p,i) => {
        p.x+=p.vx; p.y+=p.vy; p.life-=0.003;
        if(p.life<=0||p.y<-10) particles[i]={x:Math.random()*W,y:H+10,r:Math.random()*1.6+0.4,vx:(Math.random()-0.5)*0.35,vy:-Math.random()*0.5-0.15,alpha:Math.random()*0.45+0.1,color:COLORS[Math.floor(Math.random()*4)],life:1};
        ctx.beginPath(); ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
        ctx.fillStyle=p.color; ctx.globalAlpha=p.alpha*p.life; ctx.fill();
    });
    ctx.globalAlpha=1; requestAnimationFrame(drawParticles);
}
drawParticles();

/* ── SIMULATED LIVE METRICS ── */
let cpu=34, mem=38, disk=42;

function animateBar(id, pct) {
    setTimeout(() => {
        const el = document.getElementById(id);
        if(el) el.style.width = pct + '%';
    }, 500);
}

function updateMetrics() {
    // Simulate small fluctuations
    cpu  = Math.min(95, Math.max(5,  cpu  + (Math.random()-0.5)*6));
    mem  = Math.min(90, Math.max(20, mem  + (Math.random()-0.5)*3));
    disk = Math.min(85, Math.max(30, disk + (Math.random()-0.5)*0.5));

    const cpuR  = Math.round(cpu);
    const memGB = (mem * 16 / 100).toFixed(1);
    const diskGB= Math.round(disk * 512 / 100);

    document.getElementById('cpuVal').textContent  = cpuR + '%';
    document.getElementById('memVal').textContent  = memGB + ' / 16 GB';
    document.getElementById('diskVal').textContent = diskGB + ' / 512 GB';

    document.getElementById('cpuPct2').textContent  = cpuR + '%';
    document.getElementById('memPct2').textContent  = Math.round(mem) + '%';
    document.getElementById('diskPct2').textContent = Math.round(disk) + '%';

    ['cpuBar','cpuBar2'].forEach(id => { const el=document.getElementById(id); if(el) el.style.width=cpuR+'%'; });
    ['memBar','memBar2'].forEach(id => { const el=document.getElementById(id); if(el) el.style.width=mem+'%'; });
    ['diskBar','diskBar2'].forEach(id => { const el=document.getElementById(id); if(el) el.style.width=disk+'%'; });
}

// Animate bars in on load
setTimeout(updateMetrics, 400);
setInterval(updateMetrics, 3000);

/* ── UPTIME COUNTER ── */
let uptimeSecs = 4 * 86400 + 11 * 3600 + 2 * 60;
function updateUptime() {
    uptimeSecs++;
    const d = Math.floor(uptimeSecs / 86400);
    const h = Math.floor((uptimeSecs % 86400) / 3600);
    const m = Math.floor((uptimeSecs % 3600) / 60);
    const s = uptimeSecs % 60;
    document.getElementById('uptimeVal').textContent =
        d + 'd ' + String(h).padStart(2,'0') + 'h ' +
        String(m).padStart(2,'0') + 'm ' + String(s).padStart(2,'0') + 's';
}
setInterval(updateUptime, 1000);
updateUptime();

/* ── LIVE SERVER CLOCK ── */
function updateClock() {
    const now = new Date();
    document.getElementById('serverClock').textContent =
        now.toLocaleTimeString('en-US', {hour12:false});
}
setInterval(updateClock, 1000);
updateClock();

/* ── LIVE LOG FEED ── */
const logMessages = [
    ['ok',   'Query executed in 2ms — SELECT task'],
    ['ok',   'Session refreshed for user'],
    ['warn', 'Slow query detected — 340ms response'],
    ['ok',   'Cache cleared successfully'],
    ['ok',   'Health ping responded — 200 OK'],
    ['warn', 'Memory usage above 70% threshold'],
    ['ok',   'Backup snapshot saved'],
    ['ok',   'Task created by <?= htmlspecialchars($_SESSION["user"]) ?>'],
    ['err',  'Connection timeout — retry in 5s'],
    ['ok',   'Auto-save triggered'],
];

function addLogLine() {
    const log = logMessages[Math.floor(Math.random() * logMessages.length)];
    const now = new Date();
    const time = now.toLocaleTimeString('en-US', {hour12:false});
    const typeClass = log[0] === 'ok' ? 'log-ok' : log[0] === 'warn' ? 'log-warn' : 'log-err';
    const typeLabel = log[0] === 'ok' ? '[OK]' : log[0] === 'warn' ? '[WARN]' : '[ERR]';

    const line = document.createElement('div');
    line.className = 'log-line';
    line.innerHTML = `<span class="log-t">${time}</span><span class="${typeClass}">${typeLabel}</span><span class="log-msg">${log[1]}</span>`;

    const body = document.getElementById('logBody');
    body.insertBefore(line, body.firstChild);

    // Keep max 20 lines
    while(body.children.length > 20) body.removeChild(body.lastChild);
}

setInterval(addLogLine, 4000);

/* ── NAV ICON BOUNCE ── */
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('mouseenter', () => {
        link.style.textShadow = '0 0 12px rgba(79,195,247,0.4)';
    });
    link.addEventListener('mouseleave', () => {
        link.style.textShadow = '';
    });
});

/* ── MOBILE SIDEBAR TOGGLE ── */
function toggleSidebar(){
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebarOverlay');
    if(!sb) return;
    sb.classList.toggle('show');
    if(ov) ov.classList.toggle('show');
}

function closeSidebar(){
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebarOverlay');
    if(sb) sb.classList.remove('show');
    if(ov) ov.classList.remove('show');
}

// Close sidebar when a nav link is clicked (mobile)
document.querySelectorAll('.nav-link').forEach(l => l.addEventListener('click', () => closeSidebar()));

// Close on Escape
document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape') closeSidebar();
});
</script>

</body>
</html>