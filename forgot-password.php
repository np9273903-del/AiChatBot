<?php
require_once __DIR__ . '/includes/auth_check.php';
if (current_user()) { header('Location: home.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password | Soen AI</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700&display=swap">
<link rel="stylesheet" href="assets/css/auth-clean.css?v=20.0">
</head>
<body class="auth-clean-body">

<!-- Header Logo -->
<header class="auth-clean-header">
    <a href="login.html" class="auth-clean-logo">
        <span class="logo-word">Soen</span><span class="logo-badge">AI</span>
    </a>
</header>

<div class="auth-clean-wrapper">
    <main class="auth-clean-container">
        <!-- LinkedIn-style warm terracotta serif heading -->
        <h1 class="auth-clean-title">Forgot your<br>password?</h1>
        <p class="auth-clean-sub">Enter your email and new password to recover access.</p>

        <div class="error-msg" id="resetError"></div>
        <div class="success-msg" id="resetSuccess"></div>

        <form id="resetForm" class="auth-clean-form">
            <div class="auth-field">
                <label for="resetEmail">Email address</label>
                <input type="email" id="resetEmail" required autocomplete="email" autofocus>
            </div>

            <div class="auth-field">
                <label for="newPassword">New password (8+ characters)</label>
                <div class="auth-pw-wrap">
                    <input type="password" id="newPassword" minlength="8" required autocomplete="new-password">
                    <button type="button" class="auth-pw-toggle" data-target="newPassword">Show</button>
                </div>
                <div class="strength"><span id="resetStrength"></span></div>
            </div>

            <div class="auth-field">
                <label for="confirmPassword">Confirm new password</label>
                <div class="auth-pw-wrap">
                    <input type="password" id="confirmPassword" minlength="8" required autocomplete="new-password">
                    <button type="button" class="auth-pw-toggle" data-target="confirmPassword">Show</button>
                </div>
            </div>

            <button class="btn-auth-primary" id="resetSubmit" type="submit">Reset password</button>

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
