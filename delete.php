<?php include 'db.php'; ?>

<?php
$id = $_GET['id'];

$conn->query("DELETE FROM task WHERE task_id=$id");

header("Location: index.php");
?>