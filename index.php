<?php
ini_set('display_errors', 0);
error_reporting(0);
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$category = $_GET['category'] ?? '';
$filter = $_GET['filter'] ?? 'all';

// TOTAL
if ($category != '') {
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM task WHERE category = ?");
    $stmt->bind_param("s", $category);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM task");
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['c'];

// PENDING
if ($category != '') {
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM task WHERE status='pending' AND category = ?");
    $stmt->bind_param("s", $category);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM task WHERE status='pending'");
}
$stmt->execute();
$pending = $stmt->get_result()->fetch_assoc()['c'];

// COMPLETED
if ($category != '') {
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM task WHERE status='completed' AND category = ?");
    $stmt->bind_param("s", $category);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM task WHERE status='completed'");
}
$stmt->execute();
$completed = $stmt->get_result()->fetch_assoc()['c'];

$progressPercent = ($total > 0) ? round(($completed / $total) * 100) : 0;

// TASKS PER CATEGORY
function fetchByCategory($conn, $cat) {
    $stmt = $conn->prepare("SELECT * FROM task WHERE category = ? ORDER BY due_date ASC");
    $stmt->bind_param("s", $cat);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$academicTasks = fetchByCategory($conn, 'academic');
$personalTasks = fetchByCategory($conn, 'personal');
$projectTasks  = fetchByCategory($conn, 'project');

$acadTotal  = count($academicTasks);
$persTotal  = count($personalTasks);
$projTotal  = count($projectTasks);

$acadDone   = count(array_filter($academicTasks, fn($t) => $t['status'] === 'completed'));
$persDone   = count(array_filter($personalTasks, fn($t) => $t['status'] === 'completed'));
$projDone   = count(array_filter($projectTasks,  fn($t) => $t['status'] === 'completed'));

// FILTERED TASKS FOR ATTENTION TABLE
if ($filter === 'pending') {
    $stmt = $conn->prepare("SELECT * FROM task WHERE status='pending' ORDER BY due_date ASC LIMIT 20");
} elseif ($filter === 'completed') {
    $stmt = $conn->prepare("SELECT * FROM task WHERE status='completed' ORDER BY due_date ASC LIMIT 20");
} elseif ($filter === 'overdue') {
    $stmt = $conn->prepare("SELECT * FROM task WHERE status='pending' AND due_date < CURDATE() AND due_date != '0000-00-00' ORDER BY due_date ASC LIMIT 20");
} else {
    $stmt = $conn->prepare("SELECT * FROM task ORDER BY due_date ASC LIMIT 20");
}

$stmt->execute();
$attentionTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// OVERDUE
$today = date('Y-m-d');
$overdue = array_filter($attentionTasks, fn($t) => $t['due_date'] < $today && $t['due_date'] !== '0000-00-00');
$overdueCount = count($overdue);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>To Do List — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --bg:        #1a1f6e;
    --bg2:       #2232b0;
    --surface:   rgba(255,255,255,0.10);
    --surface2:  rgba(255,255,255,0.06);
    --border:    rgba(255,255,255,0.14);
    --accent:    #4fc3f7;
    --accent2:   #ffb830;
    --done:      #00e5a0;
    --pending:   #ffb830;
    --danger:    #ff5252;
    --text:      #ffffff;
    --text2:     rgba(255,255,255,0.6);
    --sidebar-w: 230px;
    --radius:    18px;
    --radius-sm: 12px;
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

/* ── ANIMATED BG ── */
body::before {
    content: '';
    position: fixed;
    inset: 0;
    background:
        radial-gradient(ellipse 80% 60% at 20% 10%, rgba(72,92,230,0.55) 0%, transparent 60%),
        radial-gradient(ellipse 60% 50% at 85% 80%, rgba(100,60,210,0.4) 0%, transparent 55%),
        radial-gradient(ellipse 40% 40% at 60% 30%, rgba(30,180,255,0.18) 0%, transparent 60%);
    z-index: 0;
    pointer-events: none;
    animation: bgShift 12s ease-in-out infinite alternate;
}
@keyframes bgShift {
    0%   { opacity: 1; filter: hue-rotate(0deg); }
    100% { opacity: 0.85; filter: hue-rotate(20deg); }
}

/* ── ORBS ── */
.orb { position:fixed;border-radius:50%;filter:blur(60px);opacity:0.18;pointer-events:none;z-index:0;animation:orbFloat linear infinite; }
.orb1 { width:320px;height:320px;background:#4fc3f7;top:-80px;left:10%;animation-duration:18s; }
.orb2 { width:260px;height:260px;background:#7c6ef7;bottom:5%;right:8%;animation-duration:22s;animation-delay:-6s; }
.orb3 { width:200px;height:200px;background:#00e5a0;top:40%;left:60%;animation-duration:26s;animation-delay:-12s; }
.orb4 { width:180px;height:180px;background:#ffb830;bottom:20%;left:30%;animation-duration:20s;animation-delay:-4s; }
@keyframes orbFloat {
    0%,100% { transform:translateY(0) scale(1); }
    33%      { transform:translateY(-40px) scale(1.08); }
    66%      { transform:translateY(20px) scale(0.95); }
}

#particleCanvas { position:fixed;inset:0;z-index:0;pointer-events:none;opacity:0.55; }
.cursor-glow { position:fixed;width:320px;height:320px;border-radius:50%;background:radial-gradient(circle,rgba(79,195,247,0.08) 0%,transparent 70%);pointer-events:none;z-index:1;transform:translate(-50%,-50%); }
.ripple-effect { position:absolute;border-radius:50%;background:rgba(79,195,247,0.25);transform:scale(0);animation:rippleAnim 0.55s ease-out forwards;pointer-events:none; }
@keyframes rippleAnim { to { transform:scale(4);opacity:0; } }

/* ── ENTRANCE ANIMATIONS ── */
@keyframes slideUp { from{opacity:0;transform:translateY(28px)} to{opacity:1;transform:translateY(0)} }
@keyframes scaleIn { from{opacity:0;transform:scale(0.9)} to{opacity:1;transform:scale(1)} }
@keyframes fadeIn  { from{opacity:0} to{opacity:1} }

/* ── SIDEBAR ── */
.sidebar {
    position:fixed;left:0;top:0;width:var(--sidebar-w);height:100vh;
    background:rgba(15,22,90,0.92);border-right:1px solid var(--border);
    display:flex;flex-direction:column;z-index:100;backdrop-filter:blur(18px);
}
.sidebar-brand { padding:28px 22px 22px;border-bottom:1px solid var(--border); }
.brand-logo { display:flex;align-items:center;gap:10px;margin-bottom:4px; }
.brand-icon { width:36px;height:36px;background:linear-gradient(135deg,#4fc3f7,#7c6ef7);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;animation:iconPulse 3s ease-in-out infinite; }
@keyframes iconPulse {
    0%,100% { box-shadow:0 4px 14px rgba(79,195,247,0.35); }
    50%      { box-shadow:0 4px 22px rgba(79,195,247,0.65); }
}
.brand-name { font-family:'Nunito',sans-serif;font-size:20px;font-weight:900;color:#fff;letter-spacing:-0.3px; }
.brand-user { font-size:12px;color:var(--text2);padding-left:4px; }
.nav-section { padding:14px 0 0; }
.nav-label { font-size:10px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.28);padding:8px 22px 4px; }
.nav-link { display:flex;align-items:center;gap:12px;padding:10px 22px;font-size:13.5px;font-weight:600;color:var(--text2);text-decoration:none;position:relative;transition:color 0.18s,background 0.18s,padding-left 0.18s; }
.nav-link::before { content:'';position:absolute;left:0;top:4px;bottom:4px;width:3px;border-radius:0 3px 3px 0;background:transparent;transition:background 0.18s; }
.nav-link:hover { color:#fff;background:rgba(255,255,255,0.05);padding-left:28px; }
.nav-link.active { color:#fff;background:rgba(79,195,247,0.12);padding-left:28px; }
.nav-link.active::before { background:var(--accent); }
.nav-icon { width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0; }
.icon-teal   { background:rgba(0,229,160,0.15); }
.icon-blue   { background:rgba(79,195,247,0.15); }
.icon-purple { background:rgba(167,139,250,0.15); }
.icon-yellow { background:rgba(255,184,48,0.15); }
.icon-gray   { background:rgba(255,255,255,0.07); }
.icon-red    { background:rgba(255,80,80,0.12); }
.sidebar-footer { margin-top:auto;padding:16px 22px 24px;border-top:1px solid var(--border); }
.nav-link.logout { color:rgba(255,100,100,0.65)!important; }
.nav-link.logout:hover { color:rgba(255,100,100,0.9)!important;background:rgba(255,60,60,0.07)!important; }

/* ── MAIN ── */
.main { margin-left:var(--sidebar-w);flex:1;padding:28px 32px;position:relative;z-index:1; }

/* ── TOPBAR ── */
.topbar { display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;animation:slideUp 0.5s ease both; }
.topbar-left h1 { font-family:'Nunito',sans-serif;font-size:26px;font-weight:900;color:#fff;line-height:1.1; }
.date-badge { display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,0.1);border:1px solid var(--border);border-radius:20px;padding:4px 12px;font-size:12px;color:var(--text2);margin-top:6px; }
.btn-add { background:linear-gradient(135deg,#4fc3f7 0%,#7c6ef7 100%);border:none;color:#fff;border-radius:50px;padding:11px 22px;font-size:13.5px;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:7px;box-shadow:0 6px 24px rgba(79,195,247,0.35);transition:transform 0.15s,box-shadow 0.15s;animation:btnPulse 3s ease-in-out infinite; }
.btn-add:hover { transform:translateY(-2px);box-shadow:0 10px 30px rgba(79,195,247,0.45);color:#fff; }
@keyframes btnPulse {
    0%,100% { box-shadow:0 6px 24px rgba(79,195,247,0.35); }
    50%      { box-shadow:0 6px 36px rgba(79,195,247,0.65),0 0 0 6px rgba(79,195,247,0.08); }
}

/* ══ PASSPORTAL-STYLE STAT CARDS (top row) ══ */
.stat-row { display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;animation:slideUp 0.5s ease 0.05s both; }

.pcard {
    border-radius:14px;padding:18px 20px;position:relative;overflow:hidden;
    display:flex;align-items:flex-start;justify-content:space-between;
    transition:transform 0.18s,box-shadow 0.18s;cursor:default;
}
.pcard:hover { transform:translateY(-4px) scale(1.02); }

.pcard-blue   { background:linear-gradient(135deg,rgba(79,195,247,0.25),rgba(79,195,247,0.1));border:1px solid rgba(79,195,247,0.3); }
.pcard-yellow { background:linear-gradient(135deg,rgba(255,184,48,0.25),rgba(255,184,48,0.1));border:1px solid rgba(255,184,48,0.3); }
.pcard-green  { background:linear-gradient(135deg,rgba(0,229,160,0.25),rgba(0,229,160,0.1));border:1px solid rgba(0,229,160,0.3); }
.pcard-red    { background:linear-gradient(135deg,rgba(255,82,82,0.25),rgba(255,82,82,0.1));border:1px solid rgba(255,82,82,0.3); }

.pcard:hover.pcard-blue   { box-shadow:0 12px 36px rgba(79,195,247,0.25); }
.pcard:hover.pcard-yellow { box-shadow:0 12px 36px rgba(255,184,48,0.25); }
.pcard:hover.pcard-green  { box-shadow:0 12px 36px rgba(0,229,160,0.25); }
.pcard:hover.pcard-red    { box-shadow:0 12px 36px rgba(255,82,82,0.25); }

.pcard::before { content:'';position:absolute;top:0;left:-80%;width:60%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.06),transparent);transform:skewX(-15deg);transition:left 0.55s ease; }
.pcard:hover::before { left:140%; }

.pcard-left { display:flex;flex-direction:column;gap:4px; }
.pcard-label { font-size:10px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.6);margin-bottom:4px; }
.pcard-num { font-family:'Nunito',sans-serif;font-size:38px;font-weight:900;line-height:1;color:#fff; }
.pcard-sub { font-size:11px;color:rgba(255,255,255,0.5);margin-top:4px; }

.pcard-icon-wrap { width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;opacity:0.85;transition:transform 0.2s; }
.pcard:hover .pcard-icon-wrap { transform:scale(1.15) rotate(-8deg); }
.pcard-blue   .pcard-icon-wrap { background:rgba(79,195,247,0.2); }
.pcard-yellow .pcard-icon-wrap { background:rgba(255,184,48,0.2); }
.pcard-green  .pcard-icon-wrap { background:rgba(0,229,160,0.2); }
.pcard-red    .pcard-icon-wrap { background:rgba(255,82,82,0.2); }

.pcard-detail { font-size:10px;font-weight:700;color:rgba(255,255,255,0.45);text-decoration:none;letter-spacing:0.05em;margin-top:10px;display:inline-flex;align-items:center;gap:4px;transition:color 0.15s; }
.pcard-detail:hover { color:#fff; }

/* ══ DONUT ROW ══ */
.donut-row { display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px;animation:slideUp 0.5s ease 0.1s both; }

.donut-card {
    background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
    padding:20px 22px;backdrop-filter:blur(10px);display:flex;align-items:center;gap:18px;
    transition:transform 0.18s,box-shadow 0.18s;
}
.donut-card:hover { transform:translateY(-3px); }
.donut-card.dc-blue:hover   { box-shadow:0 12px 32px rgba(79,195,247,0.18);border-color:rgba(79,195,247,0.35); }
.donut-card.dc-purple:hover { box-shadow:0 12px 32px rgba(167,139,250,0.18);border-color:rgba(167,139,250,0.35); }
.donut-card.dc-yellow:hover { box-shadow:0 12px 32px rgba(255,184,48,0.18);border-color:rgba(255,184,48,0.35); }

.donut-wrap { position:relative;flex-shrink:0; }
.donut-wrap canvas { display:block; }
.donut-center { position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center; }
.donut-pct { font-family:'Nunito',sans-serif;font-size:17px;font-weight:900;color:#fff;line-height:1; }
.donut-done-label { font-size:9px;color:var(--text2);margin-top:1px; }

.donut-info { flex:1;min-width:0; }
.donut-title { font-family:'Nunito',sans-serif;font-size:15px;font-weight:800;color:#fff;margin-bottom:10px; }
.donut-stat { display:flex;align-items:center;justify-content:space-between;margin-bottom:5px; }
.donut-stat-label { font-size:11px;color:var(--text2); }
.donut-stat-val { font-size:12px;font-weight:700;color:#fff; }
.donut-bar-track { height:4px;background:rgba(255,255,255,0.1);border-radius:99px;overflow:hidden;margin-top:8px; }
.donut-bar-fill { height:100%;border-radius:99px;transition:width 1.2s cubic-bezier(0.4,0,0.2,1); }

/* ══ BOTTOM TWO-COL ══ */
.bottom-row { display:grid;grid-template-columns:1fr 340px;gap:16px;animation:slideUp 0.5s ease 0.15s both; }

/* ── ATTENTION TABLE ── */
.panel {
    background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
    overflow:hidden;backdrop-filter:blur(10px);
}
.panel-header {
    display:flex;align-items:center;justify-content:space-between;
    padding:14px 18px;border-bottom:1px solid var(--border);
    background:rgba(255,255,255,0.04);
}
.panel-title { font-family:'Nunito',sans-serif;font-size:14px;font-weight:800;color:#fff; }
.panel-badge { font-size:10px;font-weight:700;padding:3px 10px;border-radius:99px;background:rgba(255,82,82,0.15);color:#ff8a80;border:1px solid rgba(255,82,82,0.3); }
.panel-badge.ok { background:rgba(0,229,160,0.12);color:var(--done);border-color:rgba(0,229,160,0.28); }

/* ── SEARCH & DELETE ALL BAR ── */
.table-toolbar {
    display:flex;align-items:center;gap:10px;
    padding:12px 18px;border-bottom:1px solid var(--border);
    background:rgba(255,255,255,0.02);
}
.search-wrap {
    display:flex;align-items:center;gap:8px;flex:1;
    background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);
    border-radius:10px;padding:7px 12px;transition:border-color 0.2s;
}
.search-wrap:focus-within { border-color:rgba(79,195,247,0.5); }
.search-wrap svg { flex-shrink:0;opacity:0.45; }
.search-input {
    background:none;border:none;outline:none;
    color:#fff;font-size:13px;font-family:'Outfit',sans-serif;width:100%;
}
.search-input::placeholder { color:rgba(255,255,255,0.3); }
.search-count { font-size:11px;font-weight:700;color:var(--text2);white-space:nowrap;padding:0 4px; }
.btn-delete-all {
    display:inline-flex;align-items:center;gap:6px;
    padding:7px 14px;border-radius:8px;
    background:rgba(255,82,82,0.1);border:1px solid rgba(255,82,82,0.25);
    color:#ff8a80;font-size:12px;font-weight:700;
    font-family:'Outfit',sans-serif;cursor:pointer;
    transition:background 0.15s,border-color 0.15s,transform 0.12s;white-space:nowrap;
}
.btn-delete-all:hover {
    background:rgba(255,82,82,0.22);border-color:rgba(255,82,82,0.5);
    color:#ff5252;transform:translateY(-1px);
}
.btn-delete-all:disabled { opacity:0.35;cursor:not-allowed;transform:none; }
.modal-danger { border-color:rgba(255,82,82,0.3) !important; }

.attn-table { width:100%;border-collapse:collapse; }
.attn-table th { font-size:10px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--text2);padding:10px 18px 8px;text-align:left;background:rgba(255,255,255,0.02);border-bottom:1px solid var(--border); }
.attn-table td { padding:11px 18px;font-size:12.5px;border-bottom:1px solid rgba(255,255,255,0.04); }
.attn-table tr:last-child td { border-bottom:none; }
.attn-table tr { transition:background 0.14s;cursor:default; }
.attn-table tr:hover td { background:rgba(255,255,255,0.04); }

.attn-name { font-weight:700;color:#fff;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.attn-cat { display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;text-transform:uppercase; }
.cat-academic { background:rgba(79,195,247,0.15);color:#4fc3f7; }
.cat-personal { background:rgba(167,139,250,0.15);color:#a78bfa; }
.cat-project  { background:rgba(255,184,48,0.15);color:#ffb830; }

.attn-due { font-size:11px; }
.attn-due.overdue  { color:#ff5252;font-weight:700; }
.attn-due.due-soon { color:var(--pending);font-weight:600; }
.attn-due.ok       { color:var(--text2); }

.attn-actions { display:flex;gap:5px; }
.kbtn { width:26px;height:26px;border-radius:7px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;font-size:12px;cursor:pointer;text-decoration:none;transition:background 0.14s,transform 0.12s;padding:0; }
.kbtn:hover { transform:scale(1.15); }
.kbtn-edit:hover { background:rgba(79,195,247,0.2);border-color:rgba(79,195,247,0.4); }
.kbtn-del:hover  { background:rgba(255,80,80,0.2);border-color:rgba(255,80,80,0.4); }

.no-attn { text-align:center;padding:32px;color:var(--text2);font-size:13px; }
.no-attn span { font-size:28px;display:block;margin-bottom:8px;opacity:0.4; }

/* ── SIDE PANEL ── */
.side-panel { display:flex;flex-direction:column;gap:14px; }

.mini-panel {
    background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
    overflow:hidden;backdrop-filter:blur(10px);
}
.mini-panel-header { display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--border);background:rgba(255,255,255,0.03); }
.mini-panel-title { font-family:'Nunito',sans-serif;font-size:13px;font-weight:800;color:#fff; }

/* progress mini */
.prog-mini { padding:16px; }
.prog-mini-row { display:flex;align-items:center;justify-content:space-between;margin-bottom:6px; }
.prog-mini-label { font-size:12px;font-weight:600;color:#fff; }
.prog-mini-pct { font-size:12px;font-weight:700; }
.prog-mini-track { height:6px;background:rgba(255,255,255,0.1);border-radius:99px;overflow:hidden;margin-bottom:14px; }
.prog-mini-fill { height:100%;border-radius:99px;transition:width 1.2s cubic-bezier(0.4,0,0.2,1);animation:progPulse 3s ease-in-out infinite; }
@keyframes progPulse {
    0%,100% { box-shadow:0 0 0 rgba(79,195,247,0); }
    50%      { box-shadow:0 0 10px rgba(79,195,247,0.5); }
}

/* quick links */
.quick-links { padding:12px; }
.quick-link {
    display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;
    text-decoration:none;color:#fff;font-size:13px;font-weight:600;
    transition:background 0.15s,transform 0.15s;margin-bottom:4px;
}
.quick-link:last-child { margin-bottom:0; }
.quick-link:hover { background:rgba(255,255,255,0.08);transform:translateX(4px); }
.quick-link-icon { width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0; }

/* recent done */
.recent-done { padding:12px; }
.done-item { display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.05); }
.done-item:last-child { border-bottom:none; }
.done-check { width:22px;height:22px;border-radius:50%;background:rgba(0,229,160,0.15);border:2px solid var(--done);display:flex;align-items:center;justify-content:center;font-size:10px;color:var(--done);flex-shrink:0; }
.done-name { font-size:12px;font-weight:600;color:var(--text2);text-decoration:line-through;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.done-cat-dot { width:6px;height:6px;border-radius:50%;flex-shrink:0; }

/* ── DELETE MODAL ── */
.modal-overlay { display:none;position:fixed;inset:0;background:rgba(5,10,40,0.75);backdrop-filter:blur(6px);z-index:999;align-items:center;justify-content:center; }
.modal-overlay.show { display:flex; }
.modal-box { background:#1a2070;border:1px solid rgba(79,195,247,0.25);border-radius:20px;padding:32px 28px;width:360px;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,0.5);animation:popIn 0.2s cubic-bezier(0.34,1.56,0.64,1); }
@keyframes popIn { from{transform:scale(0.88);opacity:0} to{transform:scale(1);opacity:1} }
.modal-icon { font-size:42px;margin-bottom:14px; }
.modal-title { font-family:'Nunito',sans-serif;font-size:20px;font-weight:900;color:#fff;margin-bottom:8px; }
.modal-sub { font-size:13px;color:var(--text2);margin-bottom:24px; }
.modal-btns { display:flex;gap:10px;justify-content:center; }
.modal-btn { padding:10px 26px;border-radius:50px;font-size:13.5px;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer;border:none;transition:transform 0.12s,opacity 0.12s; }
.modal-btn:hover { transform:translateY(-1px);opacity:0.9; }
.modal-cancel { background:rgba(255,255,255,0.1);color:var(--text2);border:1px solid var(--border)!important; }
.modal-confirm { background:linear-gradient(135deg,#ff5252,#c62828);color:#fff;box-shadow:0 4px 16px rgba(255,80,80,0.35); }

::-webkit-scrollbar { width:5px; }
::-webkit-scrollbar-track { background:transparent; }
::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.12);border-radius:99px; }
</style>
</head>
<body>

<!-- ORBS -->
<div class="orb orb1"></div>
<div class="orb orb2"></div>
<div class="orb orb3"></div>
<div class="orb orb4"></div>
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
        <a href="index.php" class="nav-link active"><span class="nav-icon icon-teal">🏠</span> Dashboard</a>
    </nav>
    <nav class="nav-section">
        <div class="nav-label">Categories</div>
        <a href="academic.php" class="nav-link"><span class="nav-icon icon-blue">📚</span> Academic</a>
        <a href="personal.php" class="nav-link"><span class="nav-icon icon-purple">🎨</span> Personal</a>
        <a href="project.php"  class="nav-link"><span class="nav-icon icon-yellow">🚀</span> Project</a>
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
            <h1>📊 My Dashboard</h1>
            <div class="date-badge">📅 <?= date('l, F j, Y') ?></div>
        </div>
        <a href="add.php" class="btn-add"><span>＋</span> Add New Task</a>
    </div>

    <!-- ══ PASSPORTAL STAT CARDS ══ -->
    <div class="stat-row">

        <div class="pcard pcard-blue">
            <div class="pcard-left">
                <div class="pcard-label">Total Tasks</div>
                <div class="pcard-num" data-target="<?= $total ?>"><?= $total ?></div>
                <div class="pcard-sub">All categories</div>
                <a href="index.php?filter=all" class="pcard-detail">View All →</a>
            </div>
            <div class="pcard-icon-wrap">📋</div>
        </div>

        <div class="pcard pcard-yellow">
            <div class="pcard-left">
                <div class="pcard-label">Pending</div>
                <div class="pcard-num" data-target="<?= $pending ?>"><?= $pending ?></div>
                <div class="pcard-sub">Need attention</div>
                <a href="index.php?filter=pending" class="pcard-detail">View All →</a>
            </div>
            <div class="pcard-icon-wrap">⏳</div>
        </div>

        <div class="pcard pcard-green">
            <div class="pcard-left">
                <div class="pcard-label">Completed</div>
                <div class="pcard-num" data-target="<?= $completed ?>"><?= $completed ?></div>
                <div class="pcard-sub"><?= $progressPercent ?>% of total</div>
                <a href="index.php?filter=completed" class="pcard-detail">View All →</a>
            </div>
            <div class="pcard-icon-wrap">✅</div>
        </div>

        <div class="pcard pcard-red">
            <div class="pcard-left">
                <div class="pcard-label">Overdue</div>
                <div class="pcard-num" data-target="<?= $overdueCount ?>"><?= $overdueCount ?></div>
                <div class="pcard-sub">Past due date</div>
                <a href="index.php?filter=overdue" class="pcard-detail">View All →</a>
            </div>
            <div class="pcard-icon-wrap">⚠️</div>
        </div>

    </div>

    <!-- ══ DONUT CHARTS ROW ══ -->
    <div class="donut-row">

        <!-- Academic -->
        <div class="donut-card dc-blue">
            <div class="donut-wrap">
                <canvas id="donutAcademic" width="80" height="80"></canvas>
                <div class="donut-center">
                    <div class="donut-pct" id="pctAcademic">0%</div>
                    <div class="donut-done-label">done</div>
                </div>
            </div>
            <div class="donut-info">
                <div class="donut-title">📚 Academic</div>
                <div class="donut-stat">
                    <span class="donut-stat-label">Total</span>
                    <span class="donut-stat-val"><?= $acadTotal ?></span>
                </div>
                <div class="donut-stat">
                    <span class="donut-stat-label">Completed</span>
                    <span class="donut-stat-val" style="color:var(--done)"><?= $acadDone ?></span>
                </div>
                <div class="donut-stat">
                    <span class="donut-stat-label">Pending</span>
                    <span class="donut-stat-val" style="color:var(--pending)"><?= $acadTotal - $acadDone ?></span>
                </div>
                <div class="donut-bar-track">
                    <div class="donut-bar-fill" style="background:#4fc3f7;width:0%" data-w="<?= $acadTotal > 0 ? round(($acadDone/$acadTotal)*100) : 0 ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Personal -->
        <div class="donut-card dc-purple">
            <div class="donut-wrap">
                <canvas id="donutPersonal" width="80" height="80"></canvas>
                <div class="donut-center">
                    <div class="donut-pct" id="pctPersonal">0%</div>
                    <div class="donut-done-label">done</div>
                </div>
            </div>
            <div class="donut-info">
                <div class="donut-title">🎨 Personal</div>
                <div class="donut-stat">
                    <span class="donut-stat-label">Total</span>
                    <span class="donut-stat-val"><?= $persTotal ?></span>
                </div>
                <div class="donut-stat">
                    <span class="donut-stat-label">Completed</span>
                    <span class="donut-stat-val" style="color:var(--done)"><?= $persDone ?></span>
                </div>
                <div class="donut-stat">
                    <span class="donut-stat-label">Pending</span>
                    <span class="donut-stat-val" style="color:var(--pending)"><?= $persTotal - $persDone ?></span>
                </div>
                <div class="donut-bar-track">
                    <div class="donut-bar-fill" style="background:#a78bfa;width:0%" data-w="<?= $persTotal > 0 ? round(($persDone/$persTotal)*100) : 0 ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Project -->
        <div class="donut-card dc-yellow">
            <div class="donut-wrap">
                <canvas id="donutProject" width="80" height="80"></canvas>
                <div class="donut-center">
                    <div class="donut-pct" id="pctProject">0%</div>
                    <div class="donut-done-label">done</div>
                </div>
            </div>
            <div class="donut-info">
                <div class="donut-title">🚀 Project</div>
                <div class="donut-stat">
                    <span class="donut-stat-label">Total</span>
                    <span class="donut-stat-val"><?= $projTotal ?></span>
                </div>
                <div class="donut-stat">
                    <span class="donut-stat-label">Completed</span>
                    <span class="donut-stat-val" style="color:var(--done)"><?= $projDone ?></span>
                </div>
                <div class="donut-stat">
                    <span class="donut-stat-label">Pending</span>
                    <span class="donut-stat-val" style="color:var(--pending)"><?= $projTotal - $projDone ?></span>
                </div>
                <div class="donut-bar-track">
                    <div class="donut-bar-fill" style="background:#ffb830;width:0%" data-w="<?= $projTotal > 0 ? round(($projDone/$projTotal)*100) : 0 ?>%"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- ══ BOTTOM ROW ══ -->
    <div class="bottom-row">

        <!-- ITEMS REQUIRING ATTENTION TABLE -->
        <div class="panel">
            <div class="panel-header">
                <?php
                $panelTitle = "📋 All Tasks";
                if ($filter === 'pending')        $panelTitle = "⏳ Pending Tasks";
                elseif ($filter === 'completed')  $panelTitle = "✅ Completed Tasks";
                elseif ($filter === 'overdue')    $panelTitle = "⚠️ Overdue Tasks";
                ?>
                <span class="panel-title"><?= $panelTitle ?></span>
                <?php if($overdueCount > 0): ?>
                    <span class="panel-badge"><?= $overdueCount ?> overdue</span>
                <?php else: ?>
                    <span class="panel-badge ok">All on track ✓</span>
                <?php endif; ?>
            </div>

            <!-- TOOLBAR: Search + Delete All -->
            <div class="table-toolbar">
                <div class="search-wrap">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" class="search-input" id="tableSearch" placeholder="Search tasks..." oninput="filterTable()">
                </div>
                <span class="search-count" id="searchCount"><?= count($attentionTasks) ?> tasks</span>
                <button class="btn-delete-all" id="deleteAllBtn"
                    onclick="confirmDeleteAll()"
                    <?= count($attentionTasks) === 0 ? 'disabled' : '' ?>>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    Delete All
                </button>
            </div>

            <?php if (count($attentionTasks) > 0): ?>
            <table class="attn-table" id="attnTable">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Category</th>
                        <th>Due Date</th>
                        <th>Priority</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="attnBody">
                <?php foreach ($attentionTasks as $row):
                    $tid = intval($row['task_id'] ?? $row['id'] ?? 0);
                    $tsafe = addslashes($row['task_name'] ?? '');
                    $due = $row['due_date'];
                    $diff = $due !== '0000-00-00' ? (strtotime($due) - strtotime($today)) / 86400 : 999;
                    $dueClass = $diff < 0 ? 'overdue' : ($diff <= 3 ? 'due-soon' : 'ok');
                    $dueLabel = $diff < 0 ? '⚠️ ' . $due : ($due === '0000-00-00' ? '—' : '📅 ' . $due);
                    $pri = (int)($row['priority'] ?? 1);
                    $priLabel = $pri === 3 ? '🔴 High' : ($pri === 2 ? '🟡 Med' : '🟢 Low');
                    $cat = $row['category'] ?? 'default';
                ?>
                <tr data-name="<?= strtolower(htmlspecialchars($row['task_name'])) ?>">
                    <td><div class="attn-name" title="<?= htmlspecialchars($row['task_name']) ?>"><?= htmlspecialchars($row['task_name']) ?></div></td>
                    <td><span class="attn-cat cat-<?= $cat ?>"><?= ucfirst($cat) ?></span></td>
                    <td><span class="attn-due <?= $dueClass ?>"><?= $dueLabel ?></span></td>
                    <td><span style="font-size:12px"><?= $priLabel ?></span></td>
                    <td>
                        <div class="attn-actions">
                            <a href="edit.php?id=<?= $tid ?>" class="kbtn kbtn-edit" title="Edit">✏️</a>
                            <button class="kbtn kbtn-del" title="Delete" onclick="confirmDelete(<?= $tid ?>,'<?= $tsafe ?>')">🗑️</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="no-attn"><span>🎉</span>No tasks here! All clear.</div>
            <?php endif; ?>
        </div>

        <!-- SIDE PANEL -->
        <div class="side-panel">

            <!-- Overall Progress -->
            <div class="mini-panel">
                <div class="mini-panel-header">
                    <span class="mini-panel-title">📈 Overall Progress</span>
                    <span style="font-family:'Nunito',sans-serif;font-size:16px;font-weight:900;color:#fff"><?= $progressPercent ?>%</span>
                </div>
                <div class="prog-mini">
                    <div class="prog-mini-row">
                        <span class="prog-mini-label">Academic</span>
                        <span class="prog-mini-pct" style="color:#4fc3f7"><?= $acadTotal > 0 ? round(($acadDone/$acadTotal)*100) : 0 ?>%</span>
                    </div>
                    <div class="prog-mini-track">
                        <div class="prog-mini-fill" style="background:#4fc3f7;width:0%" data-w="<?= $acadTotal > 0 ? round(($acadDone/$acadTotal)*100) : 0 ?>%"></div>
                    </div>
                    <div class="prog-mini-row">
                        <span class="prog-mini-label">Personal</span>
                        <span class="prog-mini-pct" style="color:#a78bfa"><?= $persTotal > 0 ? round(($persDone/$persTotal)*100) : 0 ?>%</span>
                    </div>
                    <div class="prog-mini-track">
                        <div class="prog-mini-fill" style="background:#a78bfa;width:0%" data-w="<?= $persTotal > 0 ? round(($persDone/$persTotal)*100) : 0 ?>%"></div>
                    </div>
                    <div class="prog-mini-row">
                        <span class="prog-mini-label">Project</span>
                        <span class="prog-mini-pct" style="color:#ffb830"><?= $projTotal > 0 ? round(($projDone/$projTotal)*100) : 0 ?>%</span>
                    </div>
                    <div class="prog-mini-track" style="margin-bottom:0">
                        <div class="prog-mini-fill" style="background:#ffb830;width:0%" data-w="<?= $projTotal > 0 ? round(($projDone/$projTotal)*100) : 0 ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="mini-panel">
                <div class="mini-panel-header">
                    <span class="mini-panel-title">⚡ Quick Access</span>
                </div>
                <div class="quick-links">
                    <a href="add.php" class="quick-link">
                        <span class="quick-link-icon" style="background:rgba(79,195,247,0.15)">➕</span>
                        Add New Task
                    </a>
                    <a href="academic.php" class="quick-link">
                        <span class="quick-link-icon" style="background:rgba(79,195,247,0.15)">📚</span>
                        Academic Board
                    </a>
                    <a href="personal.php" class="quick-link">
                        <span class="quick-link-icon" style="background:rgba(167,139,250,0.15)">🎨</span>
                        Personal Board
                    </a>
                    <a href="project.php" class="quick-link">
                        <span class="quick-link-icon" style="background:rgba(255,184,48,0.15)">🚀</span>
                        Project Board
                    </a>
                </div>
            </div>

            <!-- Recently Completed -->
            <?php
            $stmt = $conn->prepare("SELECT * FROM task WHERE status='completed' ORDER BY due_date DESC LIMIT 4");
            $stmt->execute();
            $recentDone = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $catDots = ['academic'=>'#4fc3f7','personal'=>'#a78bfa','project'=>'#ffb830'];
            ?>
            <div class="mini-panel">
                <div class="mini-panel-header">
                    <span class="mini-panel-title">✅ Recently Done</span>
                    <span style="font-size:10px;color:var(--text2)"><?= count($recentDone) ?> tasks</span>
                </div>
                <div class="recent-done">
                    <?php if (count($recentDone) > 0): ?>
                        <?php foreach ($recentDone as $d): ?>
                        <div class="done-item">
                            <div class="done-check">✓</div>
                            <div class="done-name" title="<?= htmlspecialchars($d['task_name']) ?>"><?= htmlspecialchars($d['task_name']) ?></div>
                            <div class="done-cat-dot" style="background:<?= $catDots[$d['category']] ?? '#fff' ?>"></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align:center;padding:16px;color:var(--text2);font-size:12px">No completed tasks yet</div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /side-panel -->
    </div><!-- /bottom-row -->

</main>

<!-- DELETE SINGLE MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-icon">🗑️</div>
        <div class="modal-title">Delete Task?</div>
        <div class="modal-sub" id="modalTaskName">This will permanently remove the task.</div>
        <div class="modal-btns">
            <button class="modal-btn modal-cancel" onclick="closeModal()">Cancel</button>
            <a class="modal-btn modal-confirm" id="modalConfirmBtn" href="#">Delete</a>
        </div>
    </div>
</div>

<!-- DELETE ALL MODAL -->
<div class="modal-overlay" id="deleteAllModal">
    <div class="modal-box modal-danger">
        <div class="modal-icon">⚠️</div>
        <div class="modal-title">Delete All Shown?</div>
        <div class="modal-sub" id="deleteAllSub">This will permanently delete all tasks currently shown.</div>
        <div class="modal-btns">
            <button class="modal-btn modal-cancel" onclick="closeAllModal()">Cancel</button>
            <a class="modal-btn modal-confirm" id="deleteAllConfirmBtn" href="#">Delete All</a>
        </div>
    </div>
</div>

<script>
/* ── MODAL (single) ── */
function confirmDelete(id, name) {
    document.getElementById('modalTaskName').textContent = 'Delete "' + name + '"? This cannot be undone.';
    document.getElementById('modalConfirmBtn').href = 'delete.php?id=' + id;
    document.getElementById('deleteModal').classList.add('show');
}
function closeModal() { document.getElementById('deleteModal').classList.remove('show'); }
document.getElementById('deleteModal').addEventListener('click', function(e) { if(e.target===this) closeModal(); });

/* ── MODAL (delete all) ── */
function confirmDeleteAll() {
    const visibleRows = [...document.querySelectorAll('#attnBody tr')].filter(r => r.style.display !== 'none');
    const count = visibleRows.length;
    if (count === 0) return;
    const ids = visibleRows.map(r => {
        const delBtn = r.querySelector('.kbtn-del');
        if (!delBtn) return null;
        const match = delBtn.getAttribute('onclick').match(/confirmDelete\((\d+)/);
        return match ? match[1] : null;
    }).filter(Boolean);
    document.getElementById('deleteAllSub').textContent =
        'This will permanently delete ' + count + ' task' + (count !== 1 ? 's' : '') + '. This cannot be undone.';
    document.getElementById('deleteAllConfirmBtn').href = 'delete_all.php?ids=' + ids.join(',') + '&redirect=index.php?filter=<?= $filter ?>';
    document.getElementById('deleteAllModal').classList.add('show');
}
function closeAllModal() { document.getElementById('deleteAllModal').classList.remove('show'); }
document.getElementById('deleteAllModal').addEventListener('click', function(e) { if(e.target===this) closeAllModal(); });

/* ── SEARCH ── */
function filterTable() {
    const q = document.getElementById('tableSearch').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#attnBody tr');
    let visible = 0;
    rows.forEach(row => {
        const name = row.dataset.name || '';
        const show = name.includes(q);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('searchCount').textContent = visible + ' task' + (visible !== 1 ? 's' : '');
    document.getElementById('deleteAllBtn').disabled = visible === 0;
}

/* ── CURSOR GLOW ── */
const glow = document.getElementById('cursorGlow');
document.addEventListener('mousemove', e => { glow.style.left=e.clientX+'px'; glow.style.top=e.clientY+'px'; });

/* ── PARTICLES ── */
const canvas = document.getElementById('particleCanvas');
const ctx = canvas.getContext('2d');
let W, H;
function resize() { W=canvas.width=window.innerWidth; H=canvas.height=window.innerHeight; }
resize(); window.addEventListener('resize', resize);
const COLORS = ['#4fc3f7','#7c6ef7','#00e5a0','#ffb830','#ffffff'];
let pts = Array.from({length:80}, () => ({
    x:Math.random()*window.innerWidth, y:Math.random()*window.innerHeight,
    r:Math.random()*1.8+0.4, vx:(Math.random()-0.5)*0.4, vy:-Math.random()*0.6-0.2,
    alpha:Math.random()*0.5+0.15, color:COLORS[Math.floor(Math.random()*5)], life:1
}));
function draw() {
    ctx.clearRect(0,0,W,H);
    pts.forEach((p,i)=>{
        p.x+=p.vx; p.y+=p.vy; p.life-=0.003;
        if(p.life<=0||p.y<-10) pts[i]={x:Math.random()*W,y:H+10,r:Math.random()*1.8+0.4,vx:(Math.random()-0.5)*0.4,vy:-Math.random()*0.6-0.2,alpha:Math.random()*0.5+0.15,color:COLORS[Math.floor(Math.random()*5)],life:1};
        ctx.beginPath(); ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
        ctx.fillStyle=p.color; ctx.globalAlpha=p.alpha*p.life; ctx.fill();
    });
    ctx.globalAlpha=1; requestAnimationFrame(draw);
}
draw();

/* ── COUNT-UP ── */
document.querySelectorAll('.pcard-num[data-target]').forEach(el => {
    const target = parseInt(el.dataset.target) || 0;
    if (!target) return;
    let n = 0;
    const step = Math.max(1, Math.ceil(target / 50));
    el.textContent = '0';
    const t = setInterval(() => { n=Math.min(n+step,target); el.textContent=n; if(n>=target) clearInterval(t); }, 16);
});

/* ── PROGRESS BARS ANIMATE ── */
setTimeout(() => {
    document.querySelectorAll('[data-w]').forEach(el => {
        el.style.transition = 'width 1.2s cubic-bezier(0.4,0,0.2,1)';
        el.style.width = el.dataset.w;
    });
}, 400);

/* ── DONUT CHARTS ── */
function drawDonut(id, pct, color, trackColor) {
    const canvas = document.getElementById(id);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const cx = 40, cy = 40, r = 32, lw = 8;
    const start = -Math.PI / 2;
    const end = start + (Math.PI * 2 * (pct / 100));
    ctx.clearRect(0, 0, 80, 80);
    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, Math.PI * 2);
    ctx.strokeStyle = trackColor || 'rgba(255,255,255,0.08)';
    ctx.lineWidth = lw;
    ctx.stroke();
    if (pct > 0) {
        ctx.beginPath();
        ctx.arc(cx, cy, r, start, end);
        ctx.strokeStyle = color;
        ctx.lineWidth = lw;
        ctx.lineCap = 'round';
        ctx.shadowColor = color;
        ctx.shadowBlur = 8;
        ctx.stroke();
        ctx.shadowBlur = 0;
    }
}

const acadPct = <?= $acadTotal > 0 ? round(($acadDone/$acadTotal)*100) : 0 ?>;
const persPct = <?= $persTotal > 0 ? round(($persDone/$persTotal)*100) : 0 ?>;
const projPct = <?= $projTotal > 0 ? round(($projDone/$projTotal)*100) : 0 ?>;

function animateDonut(id, pctEl, targetPct, color) {
    let current = 0;
    const step = Math.max(1, targetPct / 40);
    const t = setInterval(() => {
        current = Math.min(current + step, targetPct);
        drawDonut(id, current, color);
        if (pctEl) pctEl.textContent = Math.round(current) + '%';
        if (current >= targetPct) clearInterval(t);
    }, 18);
    if (targetPct === 0) drawDonut(id, 0, color);
}

setTimeout(() => {
    animateDonut('donutAcademic', document.getElementById('pctAcademic'), acadPct, '#4fc3f7');
    animateDonut('donutPersonal', document.getElementById('pctPersonal'), persPct, '#a78bfa');
    animateDonut('donutProject',  document.getElementById('pctProject'),  projPct, '#ffb830');
}, 500);

/* ── NAV GLOW ── */
document.querySelectorAll('.nav-link').forEach(l => {
    l.addEventListener('mouseenter', () => l.style.textShadow='0 0 12px rgba(79,195,247,0.4)');
    l.addEventListener('mouseleave', () => l.style.textShadow='');
});
</script>
</body>
</html>