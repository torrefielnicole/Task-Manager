<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$ids = array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')));

foreach ($ids as $id) {
    $stmt = $conn->prepare("DELETE FROM task WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header('Location: ' . ($_GET['redirect'] ?? 'index.php'));
exit;
?>