<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/twilio_service.php';
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Valid email is required']); exit; }
if (!preg_match('/^\+[1-9]\d{7,14}$/', $phone)) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Valid phone number is required in international format']); exit; }
$conn = get_db();
$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND phone = ?'); $stmt->bind_param('ss', $email, $phone); $stmt->execute(); $user = $stmt->get_result()->fetch_assoc(); $stmt->close();
if ($user) {
    if (TWILIO_VERIFY_SERVICE_SID) {
        $sent = twilio_verify_request($phone);
    } else {
        $code = (string) random_int(100000, 999999);
        $hash = password_hash($code, PASSWORD_DEFAULT);
        $expires = date('Y-m-d H:i:s', time() + 900);
        $stmt = $conn->prepare('DELETE FROM password_resets WHERE email = ?'); $stmt->bind_param('s', $email); $stmt->execute(); $stmt->close();
        $stmt = $conn->prepare('INSERT INTO password_resets (user_id, email, code_hash, expires_at) VALUES (?, ?, ?, ?)'); $stmt->bind_param('isss', $user['id'], $email, $hash, $expires); $stmt->execute(); $stmt->close();
        $sent = twilio_send_sms($phone, 'Your Soen verification code is ' . $code . '. It expires in 15 minutes.');
    }
    if (!$sent['success']) {
        http_response_code(503);
        echo json_encode(['success' => false, 'message' => $sent['message']]);
        exit;
    }
}
echo json_encode(['success'=>true, 'message'=>'Verification code sent by SMS']);
