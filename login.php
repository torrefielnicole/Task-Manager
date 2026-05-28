<?php
include 'db.php';
session_start();

$error = "";

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user['username'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg: #1a1f6e;
            --surface: rgba(255,255,255,0.08);
            --text: #ffffff;
            --text2: rgba(255,255,255,0.72);
            --border: rgba(255,255,255,0.14);
            --accent: #4fc3f7;
            --accent2: #00e5a0;
            --radius: 22px;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100%;
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

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
        }

        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image: radial-gradient(circle, rgba(79,195,247,0.22) 1px, transparent 1px);
            background-size: 90px 90px;
            opacity: 0.12;
            z-index: 0;
            pointer-events: none;
        }

        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(65px);
            opacity: 0.14;
            pointer-events: none;
            z-index: 0;
        }
        .orb1 { width: 320px; height: 320px; background: #4fc3f7; top: -80px; left: 10%; }
        .orb2 { width: 260px; height: 260px; background: #7c6ef7; bottom: 0; right: 8%; }
        .orb3 { width: 220px; height: 220px; background: #00e5a0; top: 40%; left: 58%; }

        .card {
            background: rgba(10,15,65,0.88) !important;
            border: 1px solid rgba(255,255,255,0.12) !important;
            border-radius: var(--radius) !important;
            box-shadow: 0 24px 80px rgba(0,0,0,0.25) !important;
            backdrop-filter: blur(18px);
            position: relative;
            z-index: 1;
        }

        h3 {
            color: #ffffff;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            margin-bottom: 0.4rem;
        }

        .subtitle {
            color: rgba(255,255,255,0.88);
            margin-bottom: 1.5rem;
            font-size: 0.98rem;
            line-height: 1.5;
        }

        .form-control {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.18);
            color: var(--text);
            font-weight: 500;
        }

        .form-control::placeholder {
            color: rgba(255,255,255,0.72);
            opacity: 1;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(79,195,247,0.28);
            border-color: rgba(79,195,247,0.65);
        }

        .btn-primary {
            background: linear-gradient(135deg, #4fc3f7, #00e5a0);
            border: none;
            box-shadow: 0 12px 30px rgba(79,195,247,0.22);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #3bb0f3, #00ce85);
        }

        .error-text {
            color: #ff7a7a;
        }

        .link-light {
            color: rgba(255,255,255,0.9);
            font-weight: 600;
        }
        .link-light:hover {
            color: #ffffff;
            text-decoration: underline;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            width: 100%;
        }

        @media (max-width: 576px) {
            .card { border-radius: 18px; }
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
            border-color: rgba(79,195,247,0.5);
            background: rgba(79,195,247,0.06);
        }

        .submit-btn {
            width: 100%; height: 44px;
            background: linear-gradient(135deg, #4fc3f7 0%, #7c6ef7 100%);
            border: none; border-radius: 50px;
            color: #fff;
            font-family: 'Nunito', sans-serif;
            font-size: 15px; font-weight: 800;
            cursor: pointer; margin-top: 6px;
            box-shadow: 0 6px 24px rgba(79,195,247,0.35);
            transition: transform .15s, box-shadow .15s;
            animation: btnPulse 3s ease-in-out infinite;
        }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(79,195,247,0.5); }
        @keyframes btnPulse {
            0%,100% { box-shadow: 0 6px 24px rgba(79,195,247,0.35); }
            50%      { box-shadow: 0 6px 36px rgba(79,195,247,0.6), 0 0 0 6px rgba(79,195,247,0.07); }
        }

        .divider { border: none; border-top: 1px solid rgba(255,255,255,0.08); margin: 20px 0; }

        .link-row { font-size: 12px; color: rgba(255,255,255,0.4); text-align: center; }
        .link-row a { color: #4fc3f7; font-weight: 700; text-decoration: none; }
        .link-row a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="orb orb1"></div>
<div class="orb orb2"></div>
<div class="orb orb3"></div>

<div class="login-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card p-4">
                    <div class="text-center mb-4">
                        <h3>Welcome Back</h3>
                        <p class="subtitle">Sign in to access your dashboard and continue your tasks.</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <p class="text-center error-text"><?= $error ?></p>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <input type="text" name="username" class="form-control" placeholder="Username" required autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" class="form-control" placeholder="Password" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary w-100">Open Door</button>
                    </form>
                    <p class="text-center mt-4 link-light">
                        New student? <a href="register.php" class="link-light">Register here</a>
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>