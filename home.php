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
<body class="dashboard-body jitter-motion-page">

<!-- 🌟 AMBIENT GLOWING AURORA BACKGROUND -->
<div class="animated-bg">
    <div class="glow-orb orb-1"></div>
    <div class="glow-orb orb-2"></div>
    <div class="glow-orb orb-3"></div>
</div>

<!-- 🌟 JITTER FLOATING ISLAND NAVIGATION -->
<nav class="jitter-nav-wrapper">
    <div class="jitter-nav-island">
        <a href="home.php" class="jitter-logo">
            <span class="logo-dot"></span>
            <span class="logo-text">Soen AI</span>
        </a>

        <div class="jitter-nav-menu" id="jitterNavMenu">
            <div class="jitter-indicator" id="navIndicator"></div>
            <a href="#projects" class="jitter-nav-item active" data-tab="projects">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                <span>Projects</span>
            </a>
            <a href="api/ai/test.php" class="jitter-nav-item" data-tab="ai-status" target="_blank">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                <span>AI Health</span>
            </a>
        </div>

        <div class="jitter-user-section">
            <div class="user-chip jitter-chip">
                <div class="avatar"><?= strtoupper(substr($user['username'] ?: $user['email'], 0, 1)) ?></div>
                <span class="user-name"><?= htmlspecialchars($user['username']) ?></span>
            </div>
            <button class="btn-jitter-logout" id="logoutBtn" title="Log out">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </button>
        </div>
    </div>
</nav>

<!-- 🌟 MAIN CONTENT CONTAINER -->
<div class="container dashboard-container jitter-main-content">
    <div class="dashboard-hero-banner">
        <div class="hero-text-content">
            <div class="hero-top-row">
                <div class="jitter-badge">
                    <span class="pulse-dot"></span>
                    <span>Soen AI Active · Gemini 3.0 Engine</span>
                </div>

                <!-- 🌟 CLEAR FAST / PRO TOGGLE WIDGET -->
                <div class="jitter-toggle-widget" title="Toggle AI Engine Mode (Fast vs Pro Deep Reasoning)">
                    <button type="button" class="toggle-label left-lbl" id="lblFast">⚡ Fast</button>
                    <label class="jitter-switch-exact" for="aiModeSwitch">
                        <input type="checkbox" id="aiModeSwitch" checked>
                        <span class="jitter-switch-track">
                            <span class="jitter-switch-thumb"></span>
                        </span>
                    </label>
                    <button type="button" class="toggle-label right-lbl active" id="lblPro">Pro ✦</button>
                </div>
            </div>

            <h1 class="hero-title">Welcome back, <span class="highlight-name"><?= htmlspecialchars($user['username']) ?></span></h1>
            <p class="section-subtitle">Collaborate in real-time, generate full-stack code with AI, and launch workspaces instantly.</p>
        </div>

        <!-- 🌟 CLEAN JITTER BUTTON WITH SMOOTH HOVER -->
        <button class="jitter-btn-primary" id="openNewProjectBtn">
            <span class="btn-icon-plus">+</span>
            <span class="btn-label">New AI Project</span>
        </button>
    </div>

    <!-- PROJECT GRID -->
    <div class="project-grid" id="projectGrid">
        <div class="new-project-card jitter-add-card" id="newProjectCard">
            <div class="new-proj-icon-wrapper">
                <div class="new-proj-icon">+</div>
            </div>
            <div class="new-proj-text">Create New Project</div>
            <div class="new-proj-sub">Start with AI assistance</div>
        </div>
        <!-- Project cards injected dynamically -->
    </div>
</div>

<!-- MODAL: NEW PROJECT -->
<div class="modal-backdrop" id="modalBackdrop">
    <div class="modal jitter-modal">
        <div class="modal-head">
            <h3>Create New AI Project</h3>
            <button class="icon-btn modal-close-btn" id="closeNewProjectModal">✕</button>
        </div>
        <p class="modal-sub">Create a collaborative workspace with live AI code generation and instant previews.</p>
        <div class="error-msg" id="modalError"></div>
        <div class="field">
            <label for="projectName">Project Name</label>
            <input type="text" id="projectName" placeholder="e.g. Java Spring API, Python AI Bot, React App" autocomplete="off">
        </div>
        <div class="modal-actions">
            <button class="btn secondary" id="cancelModal">Cancel</button>
            <button class="jitter-btn-primary modal-create-btn" id="createProjectBtn">
                <span>Create Workspace</span>
            </button>
        </div>
    </div>
</div>

<script src="assets/js/home.js?v=<?= time() ?>"></script>
</body>
</html>
