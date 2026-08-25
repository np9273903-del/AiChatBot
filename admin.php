<?php
require_once __DIR__ . '/includes/auth_check.php';
$user = require_auth_page();
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Admin dashboard - AIChatPHP</title><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<nav class="app-nav"><a class="brand" href="home.php">AIChatPHP</a><div class="app-nav-actions"><a href="home.php">Projects</a><span class="user-avatar"><?= htmlspecialchars(strtoupper(substr($user['username'], 0, 1))) ?></span><button class="theme-toggle" data-theme-toggle type="button">Theme</button></div></nav>
<div class="breadcrumbs"><a href="home.php">Home</a> / Admin dashboard</div>
<main class="container admin-dashboard"><div class="section-head"><div><span class="eyebrow">Workspace control</span><h1>Admin dashboard</h1><p class="sub">Manage the projects you own and monitor team activity.</p></div></div>
<div class="admin-stats" id="adminStats"><div class="stat-card"><strong>--</strong><span>Projects</span></div><div class="stat-card"><strong>--</strong><span>Team members</span></div><div class="stat-card"><strong>--</strong><span>Messages</span></div></div>
<section class="admin-projects"><h2>Your projects</h2><div id="adminProjectList" class="admin-project-list"></div></section></main>
<script src="assets/js/app.js"></script><script src="assets/js/admin.js"></script>
</body></html>
