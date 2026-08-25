<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/twilio_service.php';
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$email = trim($input['email'] ?? ''); $phone = trim($input['phone'] ?? ''); $code = trim($input['code'] ?? ''); $password = $input['password'] ?? '';
if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Password needs 8 characters, uppercase, number, and special character']); exit; }
$conn = get_db(); $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND phone = ?'); $stmt->bind_param('ss', $email, $phone); $stmt->execute(); $user = $stmt->get_result()->fetch_assoc(); $stmt->close();
$verification = TWILIO_VERIFY_SERVICE_SID
	? twilio_verify_request($phone, $code)
	: (function () use ($conn, $email, $code) {
		$stmt = $conn->prepare('SELECT code_hash, expires_at FROM password_resets WHERE email = ?'); $stmt->bind_param('s', $email); $stmt->execute(); $reset = $stmt->get_result()->fetch_assoc(); $stmt->close();
		return ['success' => $reset && strtotime($reset['expires_at']) >= time() && password_verify($code, $reset['code_hash'])];
	})();
if (!$user || !$verification['success']) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid or expired verification code']); exit; }
$hash = password_hash($password, PASSWORD_DEFAULT); $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?'); $stmt->bind_param('si', $hash, $user['id']); $stmt->execute(); $stmt->close();
$stmt = $conn->prepare('DELETE FROM password_resets WHERE email = ?'); $stmt->bind_param('s', $email); $stmt->execute(); $stmt->close();
echo json_encode(['success'=>true]);
