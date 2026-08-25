<?php
require_once __DIR__ . '/includes/auth_check.php';
$user = require_auth_page();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Projects Dashboard - Soen AI</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap">
<link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>
<body class="dashboard-body">

<div class="topbar">
    <div class="brand">
        <span class="dot"></span>
        <span class="brand-title">Soen AI Workspace</span>
    </div>
    <div class="user-chip">
        <div class="avatar"><?= strtoupper(substr($user['username'] ?: $user['email'], 0, 1)) ?></div>
        <span class="user-name"><?= htmlspecialchars($user['username']) ?></span>
        <button class="btn small secondary" id="logoutBtn" title="Log out of Soen">Log out</button>
    </div>
</div>

<div class="container dashboard-container">
    <div class="section-head dashboard-head">
        <div>
            <h2>Your AI Projects</h2>
            <p class="section-subtitle">Collaborate with your team, code with Soen AI, and run projects live.</p>
        </div>
        <button class="btn btn-primary btn-new-proj" id="openNewProjectBtn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>New Project</span>
        </button>
    </div>

    <div class="project-grid" id="projectGrid">
        <div class="new-project-card" id="newProjectCard">
            <div class="new-proj-icon">+</div>
            <div class="new-proj-text">Create New Project</div>
        </div>
        <!-- Project cards injected dynamically -->
    </div>
</div>

<!-- MODAL: NEW PROJECT -->
<div class="modal-backdrop" id="modalBackdrop">
    <div class="modal">
        <div class="modal-head">
            <h3>Create New AI Project</h3>
            <button class="icon-btn modal-close-btn" id="closeNewProjectModal">✕</button>
        </div>
        <p class="modal-sub">Create a project space to collaborate on code with AI and teammates.</p>
        <div class="error-msg" id="modalError"></div>
        <div class="field">
            <label for="projectName">Project Name</label>
            <input type="text" id="projectName" placeholder="e.g. FullStack AI Dashboard, Express API" autocomplete="off">
        </div>
        <div class="modal-actions">
            <button class="btn secondary" id="cancelModal">Cancel</button>
            <button class="btn btn-primary" id="createProjectBtn">Create Project</button>
        </div>
    </div>
</div>

<script src="assets/js/home.js"></script>
</body>
</html>
