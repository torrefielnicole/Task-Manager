<?php include 'db.php'; ?>

<?php
if (isset($_POST['update'])) {
    $id = $_POST['task_id'];
    $task_name = $_POST['task_name'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];

    $conn->query("UPDATE tasks SET 
        task_name='$task_name',
        description='$description',
        due_date='$due_date',
        status='$status'
        WHERE task_id=$id");

    header("Location: index.php");
}
?>