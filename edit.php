<?php
ini_set('display_errors', 0);
error_reporting(0);
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM task WHERE task_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    header("Location: index.php");
    exit();
}

$pkVal = $row['task_id'];

$error = '';
if (isset($_POST['update'])) {
    $task_name   = trim($_POST['task_name']);
    $description = trim($_POST['description']);
    $due_date    = $_POST['due_date'];
    $status      = $_POST['status'];
    $category    = $_POST['category'];

    $updateStmt = $conn->prepare("UPDATE task SET task_name=?, description=?, due_date=?, status=?, category=? WHERE task_id=?");
    $updateStmt->bind_param("sssssi", $task_name, $description, $due_date, $status, $category, $pkVal);

    if ($updateStmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        $error = "Update failed. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Task - To-Do List</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --bg:      #1a1f6e;
    --surface: rgba(255,255,255,0.07);
    --border:  rgba(255,255,255,0.14);
    --accent:  #4fc3f7;
    --done:    #00e5a0;
    --pending: #ffb830;
    --text:    #ffffff;
    --text2:   rgba(255,255,255,0.55);
    --radius:  18px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Outfit', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
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

.card {
    position: relative;
    z-index: 1;
    background: rgba(20, 28, 100, 0.85);
    border: 1px solid var(--border);
    border-radius: 24px;
    width: 100%;
    max-width: 520px;
    backdrop-filter: blur(20px);
    box-shadow: 0 24px 60px rgba(0,0,0,0.4);
    overflow: hidden;
    animation: popIn 0.3s cubic-bezier(0.34,1.4,0.64,1);
}

@keyframes popIn {
    from { opacity: 0; transform: translateY(24px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

.card-header {
    padding: 28px 30px 22px;
    border-bottom: 1px solid var(--border);
    background: linear-gradient(135deg, rgba(79,195,247,0.1), rgba(124,110,247,0.1));
}

.card-header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-title {
    font-family: 'Nunito', sans-serif;
    font-size: 22px;
    font-weight: 900;
    color: #fff;
}

.card-subtitle {
    font-size: 12px;
    color: var(--text2);
    margin-top: 4px;
}

.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--text2);
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    padding: 7px 14px;
    border-radius: 99px;
    border: 1px solid var(--border);
    background: var(--surface);
    transition: color 0.15s, background 0.15s;
}

.back-btn:hover {
    color: #fff;
    background: rgba(255,255,255,0.1);
}

.card-body {
    padding: 28px 30px 30px;
}

.form-group {
    margin-bottom: 18px;
}

.form-label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--text2);
    margin-bottom: 7px;
}

.form-control,
.form-select {
    width: 100%;
    background: rgba(255,255,255,0.07);
    border: 1px solid var(--border);
    border-radius: 12px;
    color: #fff;
    font-family: 'Outfit', sans-serif;
    font-size: 14px;
    padding: 11px 14px;
    outline: none;
    transition: border-color 0.18s, background 0.18s;
    appearance: none;
}

.form-control:focus,
.form-select:focus {
    border-color: rgba(79,195,247,0.5);
    background: rgba(79,195,247,0.07);
}

textarea.form-control {
    resize: vertical;
    min-height: 90px;
}

.form-select option {
    background: #1a2070;
    color: #fff;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.error-msg {
    background: rgba(255,80,80,0.12);
    border: 1px solid rgba(255,80,80,0.3);
    border-radius: 10px;
    color: #ff6b6b;
    font-size: 13px;
    padding: 10px 14px;
    margin-bottom: 18px;
    text-align: center;
}

.form-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
}

.btn-cancel {
    padding: 10px 20px;
    border-radius: 99px;
    font-size: 13.5px;
    font-weight: 600;
    font-family: 'Outfit', sans-serif;
    color: var(--text2);
    background: var(--surface);
    border: 1px solid var(--border);
    cursor: pointer;
    text-decoration: none;
    transition: color 0.15s, background 0.15s;
}

.btn-cancel:hover {
    color: #fff;
    background: rgba(255,255,255,0.1);
}

.btn-save {
    padding: 11px 28px;
    border-radius: 99px;
    font-size: 13.5px;
    font-weight: 700;
    font-family: 'Outfit', sans-serif;
    color: #fff;
    background: linear-gradient(135deg, #4fc3f7, #7c6ef7);
    border: none;
    cursor: pointer;
    box-shadow: 0 6px 20px rgba(79,195,247,0.35);
    transition: transform 0.15s, box-shadow 0.15s;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(79,195,247,0.45);
}

/* Color indicator for category */
.cat-dot {
    display: inline-block;
    width: 8px; height: 8px;
    border-radius: 50%;
    margin-right: 6px;
}
</style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <div class="card-header-top">
            <div>
                <div class="card-title">✏️ Edit Task</div>
                <div class="card-subtitle">Update your task details below</div>
            </div>
            <a href="index.php" class="back-btn">← Back</a>
        </div>
    </div>

    <div class="card-body">
        <?php if ($error): ?>
            <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="task_id" value="<?= intval($pkVal) ?>">

            <div class="form-group">
                <label class="form-label">Task Title</label>
                <input type="text" name="task_name" class="form-control"
                       value="<?= htmlspecialchars($row['task_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control"><?= htmlspecialchars($row['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-control"
                           value="<?= htmlspecialchars($row['due_date'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="academic"  <?= ($row['category'] ?? '') == 'academic'  ? 'selected' : '' ?>>📚 Academic</option>
                        <option value="personal"  <?= ($row['category'] ?? '') == 'personal'  ? 'selected' : '' ?>>🎨 Personal</option>
                        <option value="project"   <?= ($row['category'] ?? '') == 'project'   ? 'selected' : '' ?>>🚀 Project</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="pending"   <?= ($row['status'] ?? '') == 'pending'   ? 'selected' : '' ?>>⏳ Pending</option>
                    <option value="completed" <?= ($row['status'] ?? '') == 'completed' ? 'selected' : '' ?>>✅ Completed</option>
                </select>
            </div>

            <div class="form-footer">
                <a href="index.php" class="btn-cancel">Cancel</a>
                <button type="submit" name="update" class="btn-save">💾 Save Changes</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>