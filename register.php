<?php
include 'db.php';

$error = "";

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // STEP 1: CHECK IF USERNAME EXISTS
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        // Username already exists
        $error = "This username already exists!";
    } else {

        // STEP 2: INSERT NEW USER
        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $password);

        if ($stmt->execute()) {
            header("Location: login.php");
            exit();
        } else {
            $error = "Something went wrong. Try again.";
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

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kosugi+Maru&family=Patrick+Hand&display=swap');

        body {
            margin: 0;
            font-family: 'Kosugi Maru', serif;
            color: #4a3e2e;
            background-color: #f4ede4;
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
        }

        h3 {
            font-family: 'Patrick Hand', cursive;
            color: #6f5841;
            font-size: 2.2rem;
        }

        .error-text {
            font-family: 'Patrick Hand', cursive;
            color: #dc3545;
        }
    </style>
</head>
<body>

<video autoplay muted loop id="bgVideo">
    <source src="assets/video/ghibli-study.mp4" type="video/mp4">
</video>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">

            <div class="card p-4">

                <div class="text-center mb-3">
                    <h3>🌿 New Student</h3>
                    <p style="font-size: 0.9rem; color: #5d4a36;">
                        Sign up to join the Study Room
                    </p>
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

                <p class="text-center mt-4">
                    Already studying? <a href="login.php">Log in here</a>
                </p>

            </div>

        </div>
    </div>
</div>

</body>
</html>