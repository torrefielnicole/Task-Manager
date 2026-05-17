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
<title>Sign Out — Taskflow</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --bg:#0f1447;--border:rgba(255,255,255,0.11);--accent:#4fc3f7;
    --done:#00e5a0;--danger:#ff5252;--text:#ffffff;--text2:rgba(255,255,255,0.5);
    --sidebar-w:220px;--topbar-h:56px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;overflow-x:hidden;}

body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 20% 0%,rgba(72,92,230,0.5) 0%,transparent 60%),radial-gradient(ellipse 50% 40% at 85% 90%,rgba(100,60,210,0.4) 0%,transparent 55%);z-index:0;pointer-events:none;animation:bgShift 14s ease-in-out infinite alternate;}
@keyframes bgShift{0%{filter:hue-rotate(0deg)}100%{filter:hue-rotate(15deg);opacity:.9}}
body::after{content:'';position:fixed;inset:0;background-image:radial-gradient(circle,rgba(79,195,247,0.2) 1px,transparent 1px);background-size:80px 80px;opacity:.07;animation:floatMotes 45s linear infinite;z-index:0;pointer-events:none;}
@keyframes floatMotes{from{transform:translateY(0)}to{transform:translateY(-800px)}}

.orb{position:fixed;border-radius:50%;filter:blur(70px);opacity:.13;pointer-events:none;z-index:0;animation:orbFloat linear infinite;}
.orb1{width:300px;height:300px;background:#4fc3f7;top:-60px;left:12%;animation-duration:20s;}
.orb2{width:240px;height:240px;background:#ff5252;bottom:5%;right:8%;animation-duration:25s;animation-delay:-8s;}
.orb3{width:180px;height:180px;background:#7c6ef7;top:45%;left:55%;animation-duration:30s;animation-delay:-14s;}
@keyframes orbFloat{0%,100%{transform:translateY(0) scale(1)}33%{transform:translateY(-38px) scale(1.07)}66%{transform:translateY(18px) scale(.94)}}

#particleCanvas{position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.4;}
.cursor-glow{position:fixed;width:280px;height:280px;border-radius:50%;background:radial-gradient(circle,rgba(255,82,82,0.06) 0%,transparent 70%);pointer-events:none;z-index:1;transform:translate(-50%,-50%);}

/* SIDEBAR */
.sidebar{position:fixed;left:0;top:0;bottom:0;width:var(--sidebar-w);background:rgba(8,12,55,0.97);border-right:1px solid var(--border);display:flex;flex-direction:column;z-index:200;backdrop-filter:blur(24px);}
.sidebar-brand{height:var(--topbar-h);display:flex;align-items:center;gap:10px;padding:0 18px;border-bottom:1px solid var(--border);flex-shrink:0;}
.brand-icon{width:30px;height:30px;background:linear-gradient(135deg,#4fc3f7,#7c6ef7);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;animation:iconPulse 3s ease-in-out infinite;}
@keyframes iconPulse{0%,100%{box-shadow:0 0 0 0 rgba(79,195,247,0)}50%{box-shadow:0 0 0 5px rgba(79,195,247,0.12)}}
.brand-name{font-family:'Nunito',sans-serif;font-size:17px;font-weight:900;color:#fff;}
.nav-body{flex:1;overflow-y:auto;padding:10px 0;}
.nav-section{margin-bottom:4px;}
.nav-label{font-size:9.5px;font-weight:700;letter-spacing:.11em;text-transform:uppercase;color:rgba(255,255,255,0.25);padding:10px 18px 4px;}
.nav-link{display:flex;align-items:center;gap:10px;padding:9px 18px;font-size:13px;font-weight:600;color:rgba(255,255,255,0.5);text-decoration:none;position:relative;transition:color .15s,background .15s,padding-left .18s;}
.nav-link::before{content:'';position:absolute;left:0;top:5px;bottom:5px;width:3px;border-radius:0 3px 3px 0;background:transparent;transition:background .15s;}
.nav-link:hover{color:#fff;background:rgba(255,255,255,0.05);padding-left:24px;}
.nav-link.active{color:#fff;background:rgba(255,82,82,0.1);padding-left:24px;}
.nav-link.active::before{background:var(--danger);}
.nav-link:hover .nav-icon{transform:scale(1.12) rotate(-5deg);}
.nav-icon{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;transition:transform .18s;}
.ni-teal{background:rgba(0,229,160,0.14)}.ni-blue{background:rgba(79,195,247,0.14)}.ni-purple{background:rgba(167,139,250,0.14)}.ni-yellow{background:rgba(255,184,48,0.14)}.ni-gray{background:rgba(255,255,255,0.06)}.ni-red{background:rgba(255,80,80,0.12)}
.sidebar-footer{padding:12px 18px 18px;border-top:1px solid var(--border);flex-shrink:0;}
.nav-link.logout{color:rgba(255,100,100,0.7)!important;}
.nav-link.logout.active{background:rgba(255,60,60,0.12)!important;}

/* TOPBAR */
.topnav{position:fixed;left:var(--sidebar-w);right:0;top:0;height:var(--topbar-h);background:rgba(10,15,65,0.9);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 24px;gap:14px;z-index:100;backdrop-filter:blur(20px);}
.topnav-title{font-family:'Nunito',sans-serif;font-size:17px;font-weight:800;color:#fff;margin-right:auto;}
.topnav-breadcrumb{font-size:11px;color:var(--text2);margin-left:4px;font-weight:400;}

/* MAIN */
.main{margin-left:var(--sidebar-w);margin-top:var(--topbar-h);flex:1;display:flex;align-items:center;justify-content:center;position:relative;z-index:2;min-height:calc(100vh - var(--topbar-h));padding:24px;}

/* LOGOUT CARD */
.logout-card {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,82,82,0.2);
    border-radius: 24px;
    padding: 48px 44px;
    width: 100%;
    max-width: 420px;
    text-align: center;
    backdrop-filter: blur(20px);
    box-shadow: 0 32px 80px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,82,82,0.1);
    animation: popIn .4s cubic-bezier(.34,1.4,.64,1) both;
    position: relative;
    overflow: hidden;
}

.logout-card::before {
    content:'';position:absolute;top:0;left:0;right:0;height:3px;
    background:linear-gradient(90deg,#ff5252,#ff1744,#ff5252);
    background-size:200% 100%;
    animation:gradientSlide 2s linear infinite;
}
@keyframes gradientSlide{0%{background-position:0% 0%}100%{background-position:200% 0%}}

@keyframes popIn{from{opacity:0;transform:scale(.9) translateY(20px)}to{opacity:1;transform:scale(1) translateY(0)}}

.logout-icon-wrap {
    width: 80px; height: 80px;
    border-radius: 50%;
    background: rgba(255,82,82,0.12);
    border: 2px solid rgba(255,82,82,0.25);
    display: flex; align-items: center; justify-content: center;
    font-size: 36px;
    margin: 0 auto 24px;
    animation: iconBounce 2s ease-in-out infinite;
}
@keyframes iconBounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}

.logout-title {
    font-family:'Nunito',sans-serif;
    font-size:26px;font-weight:900;
    color:#fff;margin-bottom:10px;
}

.logout-user {
    display:inline-flex;align-items:center;gap:7px;
    background:rgba(79,195,247,0.1);
    border:1px solid rgba(79,195,247,0.2);
    border-radius:99px;padding:5px 14px;
    font-size:13px;font-weight:700;color:var(--accent);
    margin-bottom:16px;
}

.logout-desc {
    font-size:14px;color:var(--text2);
    line-height:1.6;margin-bottom:32px;
}

.logout-btns {
    display:flex;gap:12px;justify-content:center;
}

.btn-cancel {
    flex:1;
    padding:12px 20px;
    border-radius:99px;
    font-size:14px;font-weight:700;
    font-family:'Outfit',sans-serif;
    color:var(--text2);
    background:rgba(255,255,255,0.08);
    border:1px solid var(--border);
    text-decoration:none;
    display:flex;align-items:center;justify-content:center;gap:6px;
    transition:background .18s,color .18s,transform .15s;
}
.btn-cancel:hover{background:rgba(255,255,255,0.14);color:#fff;transform:translateY(-2px);}

.btn-signout {
    flex:1;
    padding:12px 20px;
    border-radius:99px;
    font-size:14px;font-weight:700;
    font-family:'Outfit',sans-serif;
    color:#fff;
    background:linear-gradient(135deg,#ff5252,#c62828);
    border:none;
    text-decoration:none;
    display:flex;align-items:center;justify-content:center;gap:6px;
    box-shadow:0 6px 20px rgba(255,82,82,0.35);
    transition:transform .15s,box-shadow .15s;
    cursor:pointer;
}
.btn-signout:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(255,82,82,0.5);color:#fff;}

/* Countdown ring */
.countdown-wrap{margin-bottom:24px;}
.countdown-text{font-size:12px;color:var(--text2);margin-bottom:8px;}
.countdown-bar-track{height:4px;background:rgba(255,255,255,0.08);border-radius:99px;overflow:hidden;}
.countdown-bar{height:100%;background:linear-gradient(90deg,#ff5252,#ff1744);border-radius:99px;width:100%;transition:width .1s linear;}

::-webkit-scrollbar{width:4px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.1);border-radius:99px}
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
        <div class="brand-icon">📋</div>
        <span class="brand-name">Taskflow</span>
    </div>
    <div class="nav-body">
        <div class="nav-section">
            <div class="nav-label">Main</div>
            <a href="index.php" class="nav-link"><span class="nav-icon ni-teal">🏠</span> Dashboard</a>
        </div>
        <div class="nav-section">
            <div class="nav-label">Categories</div>
            <a href="academic.php" class="nav-link"><span class="nav-icon ni-blue">📚</span> Academic</a>
            <a href="personal.php" class="nav-link"><span class="nav-icon ni-purple">🎨</span> Personal</a>
            <a href="project.php"  class="nav-link"><span class="nav-icon ni-yellow">🚀</span> Project</a>
        </div>
        <div class="nav-section">
            <div class="nav-label">More</div>
            <a href="system.php" class="nav-link"><span class="nav-icon ni-gray">⚙️</span> System Info</a>
            <a href="#"          class="nav-link"><span class="nav-icon ni-gray">👨‍💻</span> Developer</a>
        </div>
    </div>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link logout active"><span class="nav-icon ni-red">🚪</span> Logout</a>
    </div>
</aside>

<!-- TOPBAR -->
<nav class="topnav">
    <div class="topnav-title">Sign Out <span class="topnav-breadcrumb">/ Account</span></div>
</nav>

<!-- MAIN -->
<main class="main">
    <div class="logout-card">

        <div class="logout-icon-wrap">🚪</div>

        <div class="logout-title">Sign Out?</div>

        <div class="logout-user">
            👤 <?= htmlspecialchars($_SESSION['user']) ?>
        </div>

        <div class="logout-desc">
            You'll be signed out of your Taskflow account.<br>
            All your tasks and progress are saved automatically.
        </div>

        <div class="countdown-wrap">
            <div class="countdown-text" id="countdownText">Auto-cancelling in <strong id="countNum">10</strong>s</div>
            <div class="countdown-bar-track">
                <div class="countdown-bar" id="countBar"></div>
            </div>
        </div>

        <div class="logout-btns">
            <a href="index.php" class="btn-cancel" id="cancelBtn">← Stay</a>
            <a href="logout_confirm.php" class="btn-signout" id="signoutBtn">🚪 Sign Out</a>
        </div>

    </div>
</main>

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
const COLS=['#ff5252','#7c6ef7','#4fc3f7','#ffb830'];
let pts=Array.from({length:50},()=>({x:Math.random()*window.innerWidth,y:Math.random()*window.innerHeight,r:Math.random()*1.5+.3,vx:(Math.random()-.5)*.3,vy:-Math.random()*.5-.15,alpha:Math.random()*.4+.1,color:COLS[Math.floor(Math.random()*4)],life:1}));
function draw(){ctx.clearRect(0,0,W,H);pts.forEach((p,i)=>{p.x+=p.vx;p.y+=p.vy;p.life-=.003;if(p.life<=0||p.y<-10)pts[i]={x:Math.random()*W,y:H+10,r:Math.random()*1.5+.3,vx:(Math.random()-.5)*.3,vy:-Math.random()*.5-.15,alpha:Math.random()*.4+.1,color:COLS[Math.floor(Math.random()*4)],life:1};ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);ctx.fillStyle=p.color;ctx.globalAlpha=p.alpha*p.life;ctx.fill();});ctx.globalAlpha=1;requestAnimationFrame(draw);}
draw();

/* AUTO-CANCEL COUNTDOWN */
let secs = 10;
const bar = document.getElementById('countBar');
const num = document.getElementById('countNum');
const txt = document.getElementById('countdownText');
let cancelled = false;

const timer = setInterval(() => {
    if (cancelled) return;
    secs--;
    num.textContent = secs;
    bar.style.width = (secs / 10 * 100) + '%';
    if (secs <= 0) {
        clearInterval(timer);
        window.location.href = 'index.php';
    }
}, 1000);

/* Cancel btn stops the countdown */
document.getElementById('cancelBtn').addEventListener('click', () => {
    cancelled = true;
    clearInterval(timer);
});

/* NAV GLOW */
document.querySelectorAll('.nav-link').forEach(l=>{
    l.addEventListener('mouseenter',()=>l.style.textShadow='0 0 10px rgba(79,195,247,.4)');
    l.addEventListener('mouseleave',()=>l.style.textShadow='');
});
</script>
</body>
</html>