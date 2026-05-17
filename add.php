<?php
ini_set('display_errors', 0);
error_reporting(0);
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$error   = '';
$success = '';

if (isset($_POST['submit'])) {
    $task_name   = htmlspecialchars($_POST['task_name']);
    $description = htmlspecialchars($_POST['description']);
    $due_date    = $_POST['due_date'];
    $status      = $_POST['status'];
    $priority    = (int) $_POST['priority'];
    $category    = $_POST['category'] ?? '';

    if ($category === '') {
        $error = "Please select a category.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO task (task_name, description, due_date, status, priority, category)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssss", $task_name, $description, $due_date, $status, $priority, $category);
        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        } else {
            $error = $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Task — To-Do List</title>
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
.icon-green  { background:rgba(79,195,247,0.12); }
.icon-gray   { background:rgba(255,255,255,0.07); }
.icon-red    { background:rgba(255,80,80,0.12); }
.sidebar-footer { margin-top:auto;padding:16px 22px 24px;border-top:1px solid var(--border); }
.nav-link.logout { color:rgba(255,100,100,0.65)!important; }
.nav-link.logout:hover { color:rgba(255,100,100,0.9)!important;background:rgba(255,60,60,0.07)!important; }

/* ══ MAIN ══ */
.main {
    margin-left:var(--sidebar-w);flex:1;padding:32px 36px;
    position:relative;z-index:2;min-height:100vh;
    display:flex;flex-direction:column;
}

/* ── TOPBAR ── */
.topbar { display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;animation:slideUp 0.5s ease both; }
.topbar-left h1 { font-family:'Nunito',sans-serif;font-size:26px;font-weight:900; }
.date-badge { display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,0.1);border:1px solid var(--border);border-radius:20px;padding:4px 12px;font-size:12px;color:var(--text2);margin-top:6px; }

/* ── FORM CARD ── */
.form-wrap {
    max-width:600px;width:100%;margin:0 auto;
    animation:slideUp 0.5s ease 0.1s both;
}

.form-card {
    background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
    padding:32px 36px;backdrop-filter:blur(14px);position:relative;overflow:hidden;
}
.form-card::before {
    content:'';position:absolute;top:0;left:0;right:0;height:3px;
    background:linear-gradient(90deg,#4fc3f7,#a78bfa,#ffb830);
}

.form-grid { display:grid;grid-template-columns:1fr 1fr;gap:16px; }
.form-grid .full { grid-column:1/-1; }

.field { display:flex;flex-direction:column;gap:6px; }
.field label {
    font-size:11px;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;
    color:var(--text2);
}

.field input,
.field textarea,
.field select {
    background:rgba(255,255,255,0.07);
    border:1px solid var(--border);
    border-radius:11px;
    color:#fff;
    font-family:'Outfit',sans-serif;
    font-size:14px;
    padding:11px 14px;
    outline:none;
    transition:border-color 0.2s,background 0.2s,box-shadow 0.2s;
    appearance:none;
    -webkit-appearance:none;
}
.field textarea { resize:vertical;min-height:90px; }
.field input:focus,
.field textarea:focus,
.field select:focus {
    border-color:rgba(79,195,247,0.5);
    background:rgba(79,195,247,0.07);
    box-shadow:0 0 0 3px rgba(79,195,247,0.12);
}
.field input::placeholder,
.field textarea::placeholder { color:rgba(255,255,255,0.25); }
.field select option { background:#1a1f6e;color:#fff; }

/* priority pills */
.priority-group { display:flex;gap:8px; }
.pri-btn {
    flex:1;padding:10px 6px;border-radius:10px;border:1px solid var(--border);
    background:rgba(255,255,255,0.05);color:var(--text2);font-size:12px;font-weight:700;
    cursor:pointer;text-align:center;transition:all 0.18s;
}
.pri-btn:hover { background:rgba(255,255,255,0.1);color:#fff; }
.pri-btn.active-low    { background:rgba(0,229,160,0.15);border-color:rgba(0,229,160,0.4);color:var(--done); }
.pri-btn.active-medium { background:rgba(255,184,48,0.15);border-color:rgba(255,184,48,0.4);color:var(--warn); }
.pri-btn.active-high   { background:rgba(255,82,82,0.15);border-color:rgba(255,82,82,0.4);color:var(--danger); }

/* category pills */
.cat-group { display:flex;gap:8px; }
.cat-btn {
    flex:1;padding:10px 6px;border-radius:10px;border:1px solid var(--border);
    background:rgba(255,255,255,0.05);color:var(--text2);font-size:12px;font-weight:700;
    cursor:pointer;text-align:center;transition:all 0.18s;
}
.cat-btn:hover { background:rgba(255,255,255,0.1);color:#fff; }
.cat-btn.active-academic { background:rgba(79,195,247,0.15);border-color:rgba(79,195,247,0.4);color:var(--accent); }
.cat-btn.active-personal { background:rgba(167,139,250,0.15);border-color:rgba(167,139,250,0.4);color:var(--purple); }
.cat-btn.active-project  { background:rgba(255,184,48,0.15);border-color:rgba(255,184,48,0.4);color:var(--warn); }

/* status toggle */
.status-group { display:flex;gap:8px; }
.stat-btn {
    flex:1;padding:10px 6px;border-radius:10px;border:1px solid var(--border);
    background:rgba(255,255,255,0.05);color:var(--text2);font-size:12px;font-weight:700;
    cursor:pointer;text-align:center;transition:all 0.18s;
}
.stat-btn:hover { background:rgba(255,255,255,0.1);color:#fff; }
.stat-btn.active-pending   { background:rgba(255,184,48,0.15);border-color:rgba(255,184,48,0.4);color:var(--warn); }
.stat-btn.active-completed { background:rgba(0,229,160,0.15);border-color:rgba(0,229,160,0.4);color:var(--done); }

/* hidden inputs */
.hidden-input { display:none; }

/* error / success */
.alert-box {
    border-radius:11px;padding:12px 16px;font-size:13px;font-weight:600;
    margin-bottom:20px;display:flex;align-items:center;gap:8px;
}
.alert-error   { background:rgba(255,82,82,0.12);border:1px solid rgba(255,82,82,0.3);color:#ff8a80; }
.alert-success { background:rgba(0,229,160,0.12);border:1px solid rgba(0,229,160,0.3);color:#00e5a0; }

/* submit button */
.btn-submit {
    width:100%;padding:14px;border-radius:12px;border:none;cursor:pointer;
    background:linear-gradient(135deg,#4fc3f7,#7c6ef7);
    color:#fff;font-family:'Nunito',sans-serif;font-size:16px;font-weight:800;
    letter-spacing:0.03em;position:relative;overflow:hidden;
    transition:transform 0.18s,box-shadow 0.18s;
    box-shadow:0 6px 24px rgba(79,195,247,0.25);
}
.btn-submit:hover { transform:translateY(-2px);box-shadow:0 10px 32px rgba(79,195,247,0.4); }
.btn-submit:active { transform:scale(0.98); }
.btn-submit::before {
    content:'';position:absolute;top:0;left:-80%;width:60%;height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.18),transparent);
    transform:skewX(-15deg);transition:left 0.5s ease;
}
.btn-submit:hover::before { left:140%; }

/* ── ANIMATIONS ── */
@keyframes slideUp {
    from { opacity:0;transform:translateY(26px); }
    to   { opacity:1;transform:translateY(0); }
}

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
        <a href="index.php?category=project"  class="nav-link"><span class="nav-icon icon-yellow">🚀</span> Project</a>
    </nav>
    <nav class="nav-section">
        <div class="nav-label">More</div>
        <a href="add.php" class="nav-link active"><span class="nav-icon icon-green">➕</span> Add Task</a>
        <a href="system.php" class="nav-link"><span class="nav-icon icon-gray">⚙️</span> System Info</a>
        <a href="#"          class="nav-link"><span class="nav-icon icon-gray">👨‍💻</span> Developer</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link logout"><span class="nav-icon icon-red">🚪</span> Logout</a>
    </div>
</aside>

<!-- ══ MAIN ══ -->
<main class="main">

    <div class="topbar">
        <div class="topbar-left">
            <h1>➕ Add Task</h1>
            <div class="date-badge">📅 <?= date('l, F j, Y') ?></div>
        </div>
    </div>

    <div class="form-wrap">
        <div class="form-card">

            <?php if ($error): ?>
                <div class="alert-box alert-error">⚠️ <?= $error ?></div>
            <?php endif; ?>

            <form method="POST" id="taskForm">

                <!-- Hidden real inputs -->
                <input type="hidden" name="category" id="categoryInput" value="academic">
                <input type="hidden" name="priority" id="priorityInput" value="1">
                <input type="hidden" name="status"   id="statusInput"   value="pending">

                <div class="form-grid">

                    <!-- Task Name -->
                    <div class="field full">
                        <label>Task Name</label>
                        <input type="text" name="task_name" placeholder="e.g. Build login page" required>
                    </div>

                    <!-- Description -->
                    <div class="field full">
                        <label>Description</label>
                        <textarea name="description" placeholder="Optional notes or details…"></textarea>
                    </div>

                    <!-- Category -->
                    <div class="field full">
                        <label>Category</label>
                        <div class="cat-group">
                            <div class="cat-btn active-academic" data-cat="academic" onclick="setCategory('academic')">📚 Academic</div>
                            <div class="cat-btn" data-cat="personal" onclick="setCategory('personal')">🎨 Personal</div>
                            <div class="cat-btn" data-cat="project"  onclick="setCategory('project')">🚀 Project</div>
                        </div>
                    </div>

                    <!-- Priority -->
                    <div class="field full">
                        <label>Priority</label>
                        <div class="priority-group">
                            <div class="pri-btn active-low" data-pri="1" onclick="setPriority(1,'low')">🟢 Low</div>
                            <div class="pri-btn" data-pri="2" onclick="setPriority(2,'medium')">🟡 Medium</div>
                            <div class="pri-btn" data-pri="3" onclick="setPriority(3,'high')">🔴 High</div>
                        </div>
                    </div>

                    <!-- Due Date -->
                    <div class="field full">
                        <label>Due Date</label>
                        <input type="date" name="due_date">
                    </div>

                    <!-- Status -->
                    <div class="field full">
                        <label>Status</label>
                        <div class="status-group">
                            <div class="stat-btn active-pending" data-stat="pending" onclick="setStatus('pending')">⏳ Pending</div>
                            <div class="stat-btn" data-stat="completed" onclick="setStatus('completed')">✅ Completed</div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="field full" style="margin-top:8px">
                        <button type="submit" name="submit" class="btn-submit">💾 Save Task</button>
                    </div>

                </div>
            </form>
        </div>
    </div>

</main>

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

/* ── NAV GLOW ── */
document.querySelectorAll('.nav-link').forEach(l => {
    l.addEventListener('mouseenter',()=>{ l.style.textShadow='0 0 12px rgba(79,195,247,0.4)'; });
    l.addEventListener('mouseleave',()=>{ l.style.textShadow=''; });
});

/* ── PILL SELECTORS ── */
function setCategory(val) {
    document.getElementById('categoryInput').value = val;
    document.querySelectorAll('.cat-btn').forEach(b => {
        b.className = 'cat-btn';
        if (b.dataset.cat === val) b.classList.add('active-' + val);
    });
}

function setPriority(val, label) {
    document.getElementById('priorityInput').value = val;
    document.querySelectorAll('.pri-btn').forEach(b => {
        b.className = 'pri-btn';
        if (parseInt(b.dataset.pri) === val) b.classList.add('active-' + label);
    });
}

function setStatus(val) {
    document.getElementById('statusInput').value = val;
    document.querySelectorAll('.stat-btn').forEach(b => {
        b.className = 'stat-btn';
        if (b.dataset.stat === val) b.classList.add('active-' + val);
    });
}

/* ── RIPPLE ON SUBMIT ── */
document.querySelector('.btn-submit').addEventListener('click', function(e) {
    const r = document.createElement('span');
    const size = Math.max(this.offsetWidth, this.offsetHeight);
    r.style.cssText = `position:absolute;border-radius:50%;background:rgba(255,255,255,0.2);width:${size}px;height:${size}px;left:${e.offsetX-size/2}px;top:${e.offsetY-size/2}px;transform:scale(0);animation:rippleAnim 0.6s ease-out forwards;pointer-events:none`;
    this.appendChild(r);
    setTimeout(()=>r.remove(), 650);
});

const style = document.createElement('style');
style.textContent = '@keyframes rippleAnim{to{transform:scale(4);opacity:0}}';
document.head.appendChild(style);
</script>
</body>
</html>