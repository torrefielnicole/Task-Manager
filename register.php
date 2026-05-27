<?php
include 'db.php';

$error = "";

if (isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = "Please enter both username and password.";
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // STEP 1: CHECK IF USERNAME EXISTS
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "This username already exists!";
        } else {
            // STEP 2: INSERT NEW USER
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $passwordHash);

            if ($stmt->execute()) {
                header("Location: login.php");
                exit();
            } else {
                $error = "Something went wrong. Try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join the Study Room</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #1a1f6e;
            --surface: rgba(255,255,255,0.08);
            --text: #ffffff;
            --text2: rgba(255,255,255,0.7);
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
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            margin-bottom: 0.4rem;
        }

        p.subtitle {
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
            box-shadow: 0 12px 30px rgba(79,195,247,0.18);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #3bb0f3, #00ce85);
        }

        .error-text {
            color: #ff7a7a;
        }

        .link-light {
            color: rgba(255,255,255,0.92);
            font-weight: 600;
        }
        .link-light:hover {
            color: #ffffff;
            text-decoration: underline;
        }

        .register-container {
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
    </style>
</head>
<body>
<div class="orb orb1"></div>
<div class="orb orb2"></div>
<div class="orb orb3"></div>

<div class="register-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card p-4">
                    <div class="text-center mb-4">
                        <h3>Welcome to the Study Room</h3>
                        <p class="subtitle">Create your account and manage tasks in a clean, modern dashboard style.</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <p class="text-center error-text">
                            <?= $error ?>
                        </p>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <input type="text" name="username" class="form-control" placeholder="Choose Username" required>
                        </div>

                        <div class="mb-3">
                            <input type="password" name="password" class="form-control" placeholder="Choose Password" required>
                        </div>

                        <button type="submit" name="register" class="btn btn-primary w-100">
                            Create Account
                        </button>
                    </form>

                    <p class="text-center mt-4 link-light">
                        Already have an account? <a href="login.php" class="link-light">Log in here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>