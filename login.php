<?php
include 'db.php';
session_start();

$error = ""; // Initialize to avoid "Undefined variable" notices

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // ✅ FIXED: Using Prepared Statements to prevent SQL Injection
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
        $error = "Oh no! Invalid login.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to the Study Room</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kosugi+Maru&family=Patrick+Hand&display=swap');

        body {
            margin: 0;
            font-family: 'Kosugi Maru', serif;
            color: #4a3e2e;
            background-color: #f4ede4; /* ✅ FIXED: Changed black fallback to a soft tan */
            height: 100vh;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        #bgVideo {
            position: fixed;
            right: 0;
            bottom: 0;
            min-width: 100%;
            min-height: 100%;
            z-index: -1;
            object-fit: cover;
            filter: brightness(0.6); 
        }

        .card {
            background: rgba(255, 255, 255, 0.7) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px); /* Safari support */
        }

        h3 {
            font-family: 'Patrick Hand', cursive;
            color: #6f5841;
            font-size: 2.2rem;
        }

        .btn-primary {
            background: #6f5841;
            border: none;
            border-radius: 25px;
            padding: 12px;
            font-family: 'Patrick Hand', cursive;
            font-size: 1.3rem;
        }

        .btn-primary:hover { background: #5d4a36; }

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
        <div class="col-md-4">
            <div class="card p-4">
                <div class="text-center mb-3">
                    <h3>🕯️ Study Room</h3>
                    <p style="font-size: 0.9rem; color: #5d4a36;">Enter the room to begin your journey.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <p class="text-danger text-center" style="font-family: 'Patrick Hand';"><?php echo $error; ?></p>
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

                <p class="text-center mt-4" style="font-size: 0.95rem;">
                    New student? <a href="register.php" style="color: #6d9dc5; font-weight: bold; text-decoration: none;">Register here</a>
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
            music.play().catch(e => console.log("Audio play blocked until user interacts."));
            btn.innerHTML = "⏸️";
        } else {
            music.pause();
            btn.innerHTML = "🎵";
        }
    }
</script>

</body>
</html>   