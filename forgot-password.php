<?php
require_once __DIR__ . '/includes/auth_check.php';
if (current_user()) { header('Location: home.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password - Soen AI</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap">
<link rel="stylesheet" href="assets/css/style.css?v=14.0">
</head>
<body class="auth-clean-body">

<!-- Clean Top Header with Logo -->
<header class="auth-clean-header">
    <a href="login.html" class="auth-clean-logo">
        <span class="auth-logo-badge">⚡</span>
        <span class="auth-logo-text">Soen<strong>AI</strong></span>
    </a>
</header>

<div class="auth-clean-wrapper">
    <main class="auth-clean-container">
        <h1 class="auth-clean-title">Reset your workspace password</h1>
        <p class="auth-clean-sub">Enter your account email and choose a new password.</p>

        <div class="error-msg" id="resetError"></div>
        <div class="success-msg" id="resetSuccess"></div>

        <form id="resetForm" class="auth-clean-form">
            <div class="auth-field">
                <label for="resetEmail">Email address</label>
                <input type="email" id="resetEmail" required autocomplete="email" autofocus>
            </div>

            <div class="auth-field">
                <div class="auth-field-row">
                    <label for="newPassword">New password (8+ characters)</label>
                    <button type="button" class="auth-pw-toggle" data-target="newPassword">Show</button>
                </div>
                <input type="password" id="newPassword" minlength="8" required autocomplete="new-password">
                <div class="strength"><span id="resetStrength"></span></div>
            </div>

            <div class="auth-field">
                <div class="auth-field-row">
                    <label for="confirmPassword">Confirm new password</label>
                    <button type="button" class="auth-pw-toggle" data-target="confirmPassword">Show</button>
                </div>
                <input type="password" id="confirmPassword" minlength="8" required autocomplete="new-password">
            </div>

            <button class="btn-auth-primary" id="resetSubmit" type="submit">Update password</button>

            <div class="auth-divider">
                <span>or</span>
            </div>

            <a href="login.html" class="btn-auth-secondary">Back to Sign in</a>
        </form>
    </main>
</div>

<script src="assets/js/reset.js"></script>
<script>
document.querySelectorAll('.auth-pw-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (input) {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.textContent = isPassword ? 'Hide' : 'Show';
        }
    });
});
</script>
</body>
</html>
