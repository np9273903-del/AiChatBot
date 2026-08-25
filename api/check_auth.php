<?php
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');

$user = current_user();
if ($user) {
    echo json_encode(['success' => true, 'user' => $user]);
} else {
    echo json_encode(['success' => false, 'user' => null]);
}
