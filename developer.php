<?php
ini_set('display_errors', 0);
error_reporting(0);
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$current = basename($_SERVER['PHP_SELF']);

// Fetch real counts from the DB for the tech stack section
$totalTasks = $conn->query("SELECT COUNT(*) as c FROM task")->fetch_assoc()['c'];
$totalUsers = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Developer — To-Do List</title>
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

.orb { position:fixed; border-radius:50%; filter:blur(65px); opacity:0.15; pointer-events:none; z-index:0; animation:orbFloat linear infinite; }
.orb1 { width:320px;height:320px;background:#4fc3f7;top:-80px;left:8%;animation-duration:18s; }
.orb2 { width:260px;height:260px;background:#7c6ef7;bottom:0;right:6%;animation-duration:23s;animation-delay:-7s; }
.orb3 { width:200px;height:200px;background:#00e5a0;top:38%;left:58%;animation-duration:27s;animation-delay:-13s; }
@keyframes orbFloat {
    0%,100% { transform:translateY(0) scale(1); }
    33%      { transform:translateY(-40px) scale(1.08); }
    66%      { transform:translateY(20px) scale(0.94); }
}

#particleCanvas { position:fixed; inset:0; z-index:0; pointer-events:none; opacity:0.45; }
.cursor-glow { position:fixed; width:320px; height:320px; border-radius:50%; background:radial-gradient(circle,rgba(79,195,247,0.07) 0%,transparent 70%); pointer-events:none; z-index:1; transform:translate(-50%,-50%); }

/* ── SIDEBAR ── */
.sidebar {
    position:fixed; left:0; top:0;
    width:var(--sidebar-w); height:100vh;
    background:rgba(10,15,65,0.93);
    border-right:1px solid var(--border);
    display:flex; flex-direction:column;
    z-index:100; backdrop-filter:blur(20px);
}
.sidebar-brand { padding:26px 22px 20px; border-bottom:1px solid var(--border); }
.brand-logo { display:flex; align-items:center; gap:10px; margin-bottom:4px; }
.brand-icon { width:36px;height:36px;background:linear-gradient(135deg,#4fc3f7,#7c6ef7);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;animation:iconPulse 3s ease-in-out infinite; }
@keyframes iconPulse {
    0%,100% { box-shadow:0 4px 14px rgba(79,195,247,0.35); }
    50%      { box-shadow:0 4px 22px rgba(79,195,247,0.6); }
}
.brand-name { font-family:'Nunito',sans-serif; font-size:20px; font-weight:900; color:#fff; }
.brand-user { font-size:12px; color:var(--text2); padding-left:4px; }
.nav-section { padding:14px 0 0; }
.nav-label { font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:rgba(255,255,255,0.28); padding:8px 22px 4px; }
.nav-link { display:flex; align-items:center; gap:12px; padding:10px 22px; font-size:13.5px; font-weight:600; color:var(--text2); text-decoration:none; position:relative; transition:color 0.18s,background 0.18s,padding-left 0.2s; }
.nav-link::before { content:''; position:absolute; left:0; top:4px; bottom:4px; width:3px; border-radius:0 3px 3px 0; background:transparent; transition:background 0.18s; }
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

/* ── MAIN ── */
.main { margin-left:var(--sidebar-w); flex:1; padding:32px 36px; position:relative; z-index:2; }

/* ── TOPBAR ── */
.topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:32px; animation:slideUp 0.5s ease both; }
.topbar-left h1 { font-family:'Nunito',sans-serif; font-size:26px; font-weight:900; }
.date-badge { display:inline-flex; align-items:center; gap:5px; background:rgba(255,255,255,0.1); border:1px solid var(--border); border-radius:20px; padding:4px 12px; font-size:12px; color:var(--text2); margin-top:6px; }
@keyframes slideUp {
    from { opacity:0; transform:translateY(24px); }
    to   { opacity:1; transform:translateY(0); }
}

.section-label {
    font-size:10px; font-weight:700; letter-spacing:0.12em;
    text-transform:uppercase; color:var(--text2); margin-bottom:12px;
    display:flex; align-items:center; gap:8px;
}
.section-label::after { content:''; flex:1; height:1px; background:var(--border); }

/* ── INFO TOGGLE ── */
.info-toggle-btn {
    display:inline-flex; align-items:center; gap:8px;
    padding:10px 20px; border:none; border-radius:50px;
    background:linear-gradient(135deg,#4fc3f7,#7c6ef7);
    color:#fff; font-weight:700; font-size:13.5px;
    font-family:'Outfit',sans-serif; cursor:pointer;
    box-shadow:0 6px 20px rgba(79,195,247,0.3);
    transition:transform 0.15s, box-shadow 0.15s;
    margin-bottom:20px;
}
.info-toggle-btn:hover { transform:translateY(-2px); box-shadow:0 10px 28px rgba(79,195,247,0.45); }

.personal-card {
    display:none;
    background:rgba(79,195,247,0.07);
    border:1px solid rgba(79,195,247,0.2);
    border-radius:var(--radius);
    padding:22px 24px;
    margin-bottom:24px;
    backdrop-filter:blur(10px);
    animation:slideUp 0.3s ease both;
}
.personal-card h2 { font-family:'Nunito',sans-serif; font-size:17px; font-weight:800; color:#fff; margin-bottom:14px; }
.personal-row { display:flex; gap:8px; padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.06); font-size:13px; }
.personal-row:last-of-type { border-bottom:none; }
.personal-key { color:var(--text2); font-weight:600; min-width:80px; }
.personal-val { color:#fff; font-weight:500; }
.personal-quote { margin-top:14px; font-style:italic; font-size:12px; color:var(--text2); padding-top:12px; border-top:1px solid rgba(255,255,255,0.07); }

/* ── TECH STACK CARDS ── */
.tech-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:24px; }
.tech-card {
    background:var(--surface); border:1px solid var(--border);
    border-radius:14px; padding:18px 20px;
    backdrop-filter:blur(10px);
    transition:transform 0.18s;
}
.tech-card:hover { transform:translateY(-3px); }
.tech-card .tc-icon { font-size:26px; margin-bottom:10px; }
.tech-card .tc-name { font-family:'Nunito',sans-serif; font-size:15px; font-weight:800; color:#fff; margin-bottom:3px; }
.tech-card .tc-desc { font-size:12px; color:var(--text2); line-height:1.5; }
.tc-badge {
    display:inline-block; font-size:10px; font-weight:700;
    padding:2px 8px; border-radius:20px; margin-top:8px;
    font-family:var(--mono);
}
.badge-green  { background:rgba(0,229,160,0.15); color:var(--done); }
.badge-blue   { background:rgba(79,195,247,0.15); color:var(--accent); }
.badge-yellow { background:rgba(255,184,48,0.15); color:var(--warn); }

/* ── DB STATS ── */
.db-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:24px; }
.db-card {
    background:var(--surface); border:1px solid var(--border);
    border-radius:14px; padding:18px 20px;
    display:flex; align-items:center; gap:14px;
    backdrop-filter:blur(10px);
}
.db-icon { font-size:28px; }
.db-label { font-size:11px; color:var(--text2); text-transform:uppercase; letter-spacing:0.07em; margin-bottom:4px; }
.db-num { font-family:'Nunito',sans-serif; font-size:32px; font-weight:900; color:#fff; line-height:1; }

/* ── HOW IT WORKS ── */
.how-panel {
    background:rgba(5,8,30,0.6);
    border:1px solid var(--border);
    border-radius:var(--radius);
    padding:20px 24px;
    font-family:var(--mono);
    font-size:13px;
    line-height:2;
    color:var(--text2);
}
.how-panel .hl-green  { color:var(--done); }
.how-panel .hl-blue   { color:var(--accent); }
.how-panel .hl-yellow { color:var(--warn); }
.how-panel .hl-white  { color:#fff; font-weight:500; }
.how-panel .comment   { color:rgba(255,255,255,0.25); }

::-webkit-scrollbar { width:4px; }
::-webkit-scrollbar-track { background:transparent; }
::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.1); border-radius:99px; }
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
        <div class="brand-logo">
            <div class="brand-icon">📋</div>
            <span class="brand-name">To-Do List</span>
        </div>
        <div class="brand-user">👋 <?= htmlspecialchars($_SESSION['user']) ?></div>
    </div>

    <nav class="nav-section">
        <div class="nav-label">Main</div>
        <a href="index.php" class="nav-link <?= $current === 'index.php' ? 'active' : '' ?>">
            <span class="nav-icon icon-teal">🏠</span> Dashboard
        </a>
    </nav>

    <nav class="nav-section">
        <div class="nav-label">Categories</div>
        <a href="academic.php" class="nav-link <?= $current === 'academic.php' ? 'active' : '' ?>">
            <span class="nav-icon icon-blue">📚</span> Academic
        </a>
        <a href="personal.php" class="nav-link <?= $current === 'personal.php' ? 'active' : '' ?>">
            <span class="nav-icon icon-purple">🎨</span> Personal
        </a>
        <a href="project.php" class="nav-link <?= $current === 'project.php' ? 'active' : '' ?>">
            <span class="nav-icon icon-yellow">🚀</span> Project
        </a>
    </nav>

    <nav class="nav-section">
        <div class="nav-label">More</div>
        <a href="system.php" class="nav-link <?= $current === 'system.php' ? 'active' : '' ?>">
            <span class="nav-icon icon-gray">⚙️</span> System Info
        </a>
        <a href="developer.php" class="nav-link <?= $current === 'developer.php' ? 'active' : '' ?>">
            <span class="nav-icon icon-gray">👨‍💻</span> Developer
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link logout">
            <span class="nav-icon icon-red">🚪</span> Logout
        </a>
    </div>
</aside>

<!-- MAIN -->
<main class="main">

    <div class="topbar">
        <div class="topbar-left">
            <h1>👨‍💻 Developer</h1>
            <div class="date-badge">📅 <?= date('l, F j, Y') ?></div>
        </div>
    </div>

    <!-- PERSONAL INFO -->
    <button class="info-toggle-btn" onclick="toggleInfo()">
        👤 View Personal Information
    </button>

    <div class="personal-card" id="infoCard">
        <h2>👤 Personal Information</h2>
        <div class="personal-row"><span class="personal-key">Name</span><span class="personal-val">Nicole Torrefiel</span></div>
        <div class="personal-row"><span class="personal-key">Age</span><span class="personal-val">21 years old</span></div>
        <div class="personal-row"><span class="personal-key">Address</span><span class="personal-val">Ilaud, Inabanga, Bohol</span></div>
        <div class="personal-quote">"The best way to predict the future is to invent it." — Alan Kay</div>
    </div>

    <!-- LIVE DB STATS -->
    <div class="section-label">Live Database Stats</div>
    <div class="db-row">
        <div class="db-card">
            <div class="db-icon">📋</div>
            <div>
                <div class="db-label">Total Tasks</div>
                <div class="db-num"><?= $totalTasks ?></div>
            </div>
        </div>
        <div class="db-card">
            <div class="db-icon">👤</div>
            <div>
                <div class="db-label">Registered Users</div>
                <div class="db-num"><?= $totalUsers ?></div>
            </div>
        </div>
    </div>

    <!-- TECH STACK -->
    <div class="section-label">Tech Stack</div>
    <div class="tech-grid">
        <div class="tech-card">
            <div class="tc-icon">🐘</div>
            <div class="tc-name">PHP</div>
            <div class="tc-desc">Server-side logic, session handling, prepared statements for all DB queries.</div>
            <span class="tc-badge badge-blue">Backend</span>
        </div>
        <div class="tech-card">
            <div class="tc-icon">🗄️</div>
            <div class="tc-name">MySQL</div>
            <div class="tc-desc">Stores users and tasks. Uses MySQLi with prepared statements to prevent SQL injection.</div>
            <span class="tc-badge badge-yellow">Database</span>
        </div>
        <div class="tech-card">
            <div class="tc-icon">🎨</div>
            <div class="tc-name">HTML / CSS / JS</div>
            <div class="tc-desc">Pure vanilla frontend — no frameworks. Animated canvas particles, donut charts, and modals built from scratch.</div>
            <span class="tc-badge badge-green">Frontend</span>
        </div>
    </div>

    <!-- HOW IT WORKS -->
    <div class="section-label">How This App Works</div>
    <div class="how-panel">
        <span class="comment">// No external APIs — everything runs on your server</span><br>
        <span class="hl-green">Authentication</span> &nbsp;→ &nbsp;<span class="hl-white">PHP sessions + password_hash() / password_verify()</span><br>
        <span class="hl-green">Database</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;→ &nbsp;<span class="hl-white">MySQLi prepared statements (no raw queries)</span><br>
        <span class="hl-green">Task CRUD</span> &nbsp;&nbsp;&nbsp;→ &nbsp;<span class="hl-white">add.php · edit.php · delete.php · delete_all.php</span><br>
        <span class="hl-green">Categories</span> &nbsp;&nbsp;&nbsp;→ &nbsp;<span class="hl-white">academic · personal · project (stored in task table)</span><br>
        <span class="hl-green">Charts</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;→ &nbsp;<span class="hl-white">Canvas API donut charts drawn in vanilla JS</span><br>
        <span class="hl-green">Routing</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;→ &nbsp;<span class="hl-white">PHP file-based (index.php · academic.php · etc.)</span><br>
        <span class="hl-yellow">No REST API</span> &nbsp;→ &nbsp;<span class="hl-white">pages render server-side, no fetch() calls to backend</span>
    </div>

</main>

<script>
function toggleInfo() {
    const card = document.getElementById('infoCard');
    card.style.display = card.style.display === 'block' ? 'none' : 'block';
}

const glow = document.getElementById('cursorGlow');
document.addEventListener('mousemove', e => {
    glow.style.left = e.clientX + 'px';
    glow.style.top  = e.clientY + 'px';
});

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

document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('mouseenter', () => link.style.textShadow='0 0 12px rgba(79,195,247,0.4)');
    link.addEventListener('mouseleave', () => link.style.textShadow='');
});
</script>
</body>
</html>