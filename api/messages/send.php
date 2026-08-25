<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/ai_service.php';
require_once __DIR__ . '/../../services/email_service.php';

header('Content-Type: application/json');
$user = require_auth_api();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$projectId = intval($input['project_id'] ?? 0);
$message = trim($input['message'] ?? '');
$clientId = substr(trim((string) ($input['client_id'] ?? '')), 0, 40);

if (!$projectId || $message === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'project_id and message are required']);
    exit;
}

$conn = get_db();

// verify membership
$stmt = $conn->prepare('SELECT p.name FROM project_users pu JOIN projects p ON p.id = pu.project_id WHERE pu.project_id = ? AND pu.user_id = ?');
$stmt->bind_param('ii', $projectId, $user['id']);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
if (!$project) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You are not a member of this project']);
    exit;
}
$stmt->close();

// 1) store the user's message
$stmt = $conn->prepare('INSERT INTO messages (project_id, user_id, client_id, sender_label, message, is_ai) VALUES (?, ?, ?, ?, ?, 0)');
$stmt->bind_param('iisss', $projectId, $user['id'], $clientId, $user['email'], $message);
$stmt->execute();
$stmt->close();

// 2) real-time @mention email notifications (excluding @ai)
if (preg_match_all('/@([\w.+-]+)/', $message, $matches)) {
    $mentions = array_unique($matches[1]);
    $stmtM = $conn->prepare('
        SELECT u.email FROM project_users pu JOIN users u ON u.id = pu.user_id
        WHERE pu.project_id = ? AND (u.username = ? OR u.email LIKE CONCAT(?, "@%")) AND u.id != ?
    ');
    foreach ($mentions as $handle) {
        if (strtolower($handle) === 'ai') continue;
        $stmtM->bind_param('issi', $projectId, $handle, $handle, $user['id']);
        $stmtM->execute();
        $res = $stmtM->get_result();
        while ($row = $res->fetch_assoc()) {
            send_mention_email($row['email'], $project['name'], $user['email'], $message);
        }
    }
    $stmtM->close();
}

// 3) @ai bot reply with conversation history context
$aiReply = null;
if (stripos($message, '@ai') !== false) {
    $prompt = trim(str_ireplace('@ai', '', $message));

    // FIX #1: Fetch last 10 messages for conversation context
    $history = [];
    $histStmt = $conn->prepare(
        'SELECT sender_label, message, is_ai FROM messages
         WHERE project_id = ? AND attachment_url IS NULL
         ORDER BY id DESC LIMIT 10'
    );
    $histStmt->bind_param('i', $projectId);
    $histStmt->execute();
    $histRows = $histStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $histStmt->close();

    // Reverse so oldest is first, then build role array
    foreach (array_reverse($histRows) as $row) {
        $role = $row['is_ai'] ? 'assistant' : 'user';
        // Skip the current @ai message itself (it was just inserted)
        if ($role === 'user' && trim($row['message']) === $message) continue;
        $history[] = ['role' => $role, 'content' => $row['message']];
    }

    $aiReply = generate_ai_result($prompt, $history);

    $stmt = $conn->prepare('INSERT INTO messages (project_id, user_id, sender_label, message, is_ai) VALUES (?, NULL, "AI", ?, 1)');
    $stmt->bind_param('is', $projectId, $aiReply);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => true, 'ai_reply' => $aiReply]);
