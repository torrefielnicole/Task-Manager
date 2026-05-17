<?php $current = basename($_SERVER['PHP_SELF']); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Task Manager</title>
  <link rel="stylesheet" href="dashboard.css" />
</head>
<body>

<div class="sidebar">
  <h4>🌿 Menu</h4>

  <div class="menu-title">Main</div>
  <a href="index.php" class="<?= $current === 'index.php' ? 'active' : '' ?>">🏠 Dashboard</a>

  <div class="menu-title">Task Types</div>
  <a href="index.php?category=academic" class="<?= isset($_GET['category']) && $_GET['category'] === 'academic' ? 'active' : '' ?>">🎓 Academic</a>
  <a href="index.php?category=personal" class="<?= isset($_GET['category']) && $_GET['category'] === 'personal' ? 'active' : '' ?>">🧘 Personal</a>
  <a href="index.php?category=project"  class="<?= isset($_GET['category']) && $_GET['category'] === 'project'  ? 'active' : '' ?>">💼 Project</a>

  <div class="menu-title">System</div>
  <a href="system.php" class="<?= $current === 'system.php' ? 'active' : '' ?>">💻 System Info</a>

  <div class="menu-title">Developer</div>
  <a href="developer.php" class="<?= $current === 'developer.php' ? 'active' : '' ?>">👩‍💻 Developer</a>

  <div class="menu-title">Utilities</div>
  <a href="logout.php" class="<?= $current === 'logout.php' ? 'active' : '' ?>">🚪 Logout</a>
</div>