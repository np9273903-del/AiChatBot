<?php
require_once __DIR__ . '/includes/auth_check.php';
if (current_user()) { header('Location: home.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Reset password - AIChatPHP</title><meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap">
<link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<nav class="app-nav"><a class="brand" href="index.php">AIChatPHP</a><div class="app-nav-actions"><button class="theme-toggle" data-theme-toggle type="button">Theme</button><a href="login.html">Log in</a></div></nav>
<div class="auth-wrap"><div class="auth-card">
    <h1>Reset your password</h1>
        <p class="sub">Enter your account email and choose a new password.</p>
    <div class="error-msg" id="resetError"></div><div class="success-msg" id="resetSuccess"></div>
    <form id="resetForm">
        <div class="field"><label for="resetEmail">Email</label><input type="email" id="resetEmail" required autocomplete="email"></div>
        <div class="field"><label for="newPassword">New password</label><div class="field-input-wrap"><input type="password" id="newPassword" minlength="8" required autocomplete="new-password"><button class="password-toggle" type="button" data-password-toggle="newPassword">Show</button></div><div class="strength"><span id="resetStrength"></span></div></div>
        <div class="field"><label for="confirmPassword">Confirm new password</label><div class="field-input-wrap"><input type="password" id="confirmPassword" minlength="8" required autocomplete="new-password"><button class="password-toggle" type="button" data-password-toggle="confirmPassword">Show</button></div></div>
        <button class="btn" id="resetSubmit" type="submit">Update password</button>
    </form>
    <div class="switch-link"><a href="login.html">Back to login</a></div>
</div></div>
<script src="assets/js/app.js"></script><script src="assets/js/reset.js"></script>
</body></html>
