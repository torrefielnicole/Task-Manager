<?php
include 'db.php';

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Using Prepared Statement for Security
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password);

    if ($stmt->execute()) {
        header("Location: login.php");
        exit();
    } else {
        $error = "Username already taken!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Join the Study Room</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* 🎨 GHIBLI REGISTRATION AESTHETIC */
        @import url('https://fonts.googleapis.com/css2?family=Kosugi+Maru&family=Patrick+Hand&display=swap');

        body {
            margin: 0;
            font-family: 'Kosugi Maru', serif;
            color: #4a3e2e;
            background-color: #000;
            height: 100vh;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        /* 🎬 VIDEO BACKGROUND SETUP */
        #bgVideo {
            position: fixed;
            right: 0;
            bottom: 0;
            min-width: 100%;
            min-height: 100%;
            z-index: -1;
            object-fit: cover;
            filter: brightness(0.7);
        }

        .card {
            background: rgba(255, 255, 255, 0.7) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 20px;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(12px);
            position: relative;
            z-index: 1;
        }

        h3 {
            font-family: 'Patrick Hand', cursive;
            color: #6f5841;
            font-size: 2.2rem;
            margin-bottom: 10px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #dcd0b8;
            border-radius: 12px;
            padding: 12px;
            color: #4a3e2e;
        }

        .btn-primary {
            background: #6f5841;
            border: none;
            border-radius: 25px;
            padding: 12px;
            font-family: 'Patrick Hand', cursive;
            font-size: 1.3rem;
            transition: transform 0.2s, background 0.3s;
        }

        .btn-primary:hover {
            background: #5d4a36;
            transform: scale(1.02);
        }

        .music-btn {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 100;
            background: #6f5841;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        .error-text {
            font-family: 'Patrick Hand', cursive;
            color: #dc3545;
            background: rgba(255, 255, 255, 0.5);
            padding: 5px;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<video autoplay muted loop id="bgVideo">
    <source src="assets/video/ghibli-study.mp4" type="video/mp4">
</video>

<audio id="studyMusic" loop>
    <source src="assets/music/ghibli_lofi.mp3" type="audio/mpeg">
</audio>
<button onclick="toggleMusic()" class="music-btn" id="musicBtn">🎵</button>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card p-4">
                <div class="text-center mb-4">
                    <h3>🌿 New Student</h3>
                    <p style="font-size: 0.9rem; color: #5d4a36;">Sign up to join the Study Room</p>
                </div>

                <?php if (!empty($error)) echo "<p class='error-text text-center'>$error</p>"; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small" style="color: #6f5841;">Choose Username</label>
                        <input type="text" name="username" class="form-control" placeholder="e.g. TotoroStudy" required autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small" style="color: #6f5841;">Choose Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <button type="submit" name="register" class="btn btn-primary w-100 mt-2">Create Account</button>
                </form>

                <p class="text-center mt-4" style="font-size: 0.95rem;">
                    Already studying? <a href="login.php" style="color: #6d9dc5; font-weight: bold; text-decoration: none;">Log in here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    const music = document.getElementById("studyMusic");
    const btn = document.getElementById("musicBtn");
    music.volume = 0.3;

    function toggleMusic() {
        if (music.paused) {
            music.play();
            btn.innerHTML = "⏸️";
        } else {
            music.pause();
            btn.innerHTML = "🎵";
        }
    }
</script>

</body>
</html>