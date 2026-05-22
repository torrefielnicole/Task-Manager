<?php
ob_start();
session_start();
include 'db.php';

$error = "";

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $raw_password = $_POST['password'];

    if (empty($username) || empty($raw_password)) {
        $error = "Username and password cannot be empty!";
    } else {
        $password = password_hash($raw_password, PASSWORD_DEFAULT);

        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "This username already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $password);
            if ($stmt->execute()) {
                ob_end_clean();
                header("Location: login.php");
                exit();
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
        $check->close();
    }
}
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Room — Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Outfit', sans-serif;
            background: #1a1f6e;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
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
            0%   { opacity: 1;    filter: hue-rotate(0deg); }
            100% { opacity: 0.85; filter: hue-rotate(18deg); }
        }

        .orb { position: fixed; border-radius: 50%; filter: blur(65px); opacity: 0.15; pointer-events: none; z-index: 0; animation: orbFloat linear infinite; }
        .orb1 { width: 320px; height: 320px; background: #4fc3f7; top: -80px; left: 8%; animation-duration: 18s; }
        .orb2 { width: 260px; height: 260px; background: #7c6ef7; bottom: 0; right: 6%; animation-duration: 23s; animation-delay: -7s; }
        @keyframes orbFloat {
            0%,100% { transform: translateY(0) scale(1); }
            33%      { transform: translateY(-40px) scale(1.08); }
            66%      { transform: translateY(20px) scale(0.94); }
        }

        #bgVideo {
            position: fixed; inset: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: 0;
            filter: brightness(0.12) saturate(0.6);
        }

        .page-wrap {
            position: relative; z-index: 2;
            width: 100%; max-width: 400px;
            padding: 20px 16px;
        }

        .auth-card {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 20px;
            padding: 32px 28px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .music-bar {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 10px;
            margin-bottom: 24px;
        }
        .music-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: #00e5a0; flex-shrink: 0;
        }
        .music-dot.playing { animation: pulse 1.6s ease-in-out infinite; }
        @keyframes pulse { 0%,100%{opacity:.3} 50%{opacity:1} }
        .music-name { font-size: 11px; color: rgba(255,255,255,0.4); flex: 1; }
        .music-toggle {
            font-size: 11px; color: #4fc3f7; font-weight: 700;
            background: none; border: none; cursor: pointer;
            font-family: 'Outfit', sans-serif; padding: 0;
        }

        .auth-icon {
            width: 46px; height: 46px;
            background: linear-gradient(135deg, #00e5a0, #4fc3f7);
            border-radius: 13px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; margin-bottom: 14px;
            box-shadow: 0 6px 20px rgba(0,229,160,0.35);
            animation: iconPulse 3s ease-in-out infinite;
        }
        @keyframes iconPulse {
            0%,100% { box-shadow: 0 6px 20px rgba(0,229,160,0.3); }
            50%      { box-shadow: 0 6px 30px rgba(0,229,160,0.6); }
        }

        .auth-title { font-family: 'Nunito', sans-serif; font-size: 24px; font-weight: 900; color: #fff; letter-spacing: -0.4px; margin-bottom: 3px; }
        .auth-sub   { font-size: 12px; color: rgba(255,255,255,0.45); margin-bottom: 24px; }

        .error-box {
            background: rgba(255,82,82,0.12);
            border: 1px solid rgba(255,82,82,0.3);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 12px; color: #ff8a80;
            margin-bottom: 16px;
        }

        .field-label {
            display: block;
            font-size: 10px; font-weight: 700;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase; letter-spacing: .08em;
            margin-bottom: 6px;
        }
        .field-wrap { margin-bottom: 14px; }

        input[type=text], input[type=password] {
            width: 100%; height: 42px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px;
            padding: 0 14px;
            font-size: 13px; color: #fff;
            font-family: 'Outfit', sans-serif;
            outline: none;
            transition: border-color .2s, background .2s;
        }
        input::placeholder { color: rgba(255,255,255,0.25); }
        input:focus {
            border-color: rgba(0,229,160,0.5);
            background: rgba(0,229,160,0.05);
        }

        .submit-btn {
            width: 100%; height: 44px;
            background: linear-gradient(135deg, #00e5a0 0%, #4fc3f7 100%);
            border: none; border-radius: 50px;
            color: #0a1a14;
            font-family: 'Nunito', sans-serif;
            font-size: 15px; font-weight: 800;
            cursor: pointer; margin-top: 6px;
            box-shadow: 0 6px 24px rgba(0,229,160,0.35);
            transition: transform .15s, box-shadow .15s;
        }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,229,160,0.5); }

        .divider { border: none; border-top: 1px solid rgba(255,255,255,0.08); margin: 20px 0; }

        .link-row { font-size: 12px; color: rgba(255,255,255,0.4); text-align: center; }
        .link-row a { color: #4fc3f7; font-weight: 700; text-decoration: none; }
        .link-row a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="orb orb1"></div>
<div class="orb orb2"></div>

<video autoplay muted loop id="bgVideo">
    <source src="assets/video/ghibli-study.mp4" type="video/mp4">
</video>

<audio id="studyMusic" loop>
    <source src="assets/music/ghibli_lofi.mp3" type="audio/mpeg">
</audio>

<div class="page-wrap">
    <div class="auth-card">

        <div class="music-bar">
            <span class="music-dot" id="musicDot"></span>
            <span class="music-name" id="musicName">ghibli_lofi.mp3</span>
            <button class="music-toggle" onclick="toggleMusic()" id="musicBtn">play</button>
        </div>

        <div class="auth-icon">🌿</div>
        <h1 class="auth-title">New Student</h1>
        <p class="auth-sub">Sign up to join the Study Room.</p>

        <?php if (!empty($error)): ?>
            <div class="error-box">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="field-wrap">
                <label class="field-label" for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Choose a username"
                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
            </div>
            <div class="field-wrap">
                <label class="field-label" for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Choose a password" required>
            </div>
            <button type="submit" name="register" class="submit-btn">Create Account</button>
        </form>

        <hr class="divider">
        <p class="link-row">Already studying? <a href="login.php">Log in here</a></p>
    </div>
</div>

<script>
    const music = document.getElementById('studyMusic');
    const dot   = document.getElementById('musicDot');
    const name  = document.getElementById('musicName');
    const btn   = document.getElementById('musicBtn');
    music.volume = 0.3;

    function toggleMusic() {
        if (music.paused) {
            music.play().catch(() => {});
            dot.classList.add('playing');
            name.textContent = 'now playing';
            btn.textContent  = 'pause';
        } else {
            music.pause();
            dot.classList.remove('playing');
            name.textContent = 'ghibli_lofi.mp3';
            btn.textContent  = 'play';
        }
    }
</script>
</body>
</html>