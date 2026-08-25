<?php
require_once __DIR__ . '/../config/config.php';

session_name(SESSION_NAME);
session_start();

/**
 * Returns the logged-in user's array (id, email, username) or null.
 */
function current_user() {
    if (!empty($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'username' => $_SESSION['user_username'],
        ];
    }
    return null;
}

/**
 * Call at the top of any PHP API endpoint that requires login.
 * Sends a 401 JSON response and stops execution if not authenticated.
 */
function require_auth_api() {
    $user = current_user();
    if (!$user) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    return $user;
}

/**
 * Call at the top of any protected HTML/PHP page.
 * Redirects to login.html if not authenticated.
 */
function require_auth_page() {
    $user = current_user();
    if (!$user) {
        header('Location: login.html');
        exit;
    }
    return $user;
}
