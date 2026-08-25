<?php
require_once __DIR__ . '/includes/auth_check.php';
header('Location: ' . (current_user() ? 'home.php' : 'login.html'));
exit;
