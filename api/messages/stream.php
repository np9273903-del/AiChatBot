<?php
/**
 * Real-time message stream via Server-Sent Events.
 * This is the PHP-world equivalent of the original project's Socket.io 'project-message' event:
 * the browser opens one long-lived connection and the server pushes new rows the moment they exist,
 * instead of the client polling on a timer.
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';

$user = current_user();
if (!$user) {
    http_response_code(401);
    exit;
}
// release the session file lock immediately so other tabs/requests aren't blocked
// while this connection stays open.
session_write_close();

$projectId = intval($_GET['project_id'] ?? 0);
$lastId = intval($_GET['after_id'] ?? 0);
$clientId = substr(trim((string) ($_GET['client_id'] ?? '')), 0, 40);

if (!$projectId) {
    http_response_code(400);
    exit;
}

$conn = get_db();
$stmt = $conn->prepare('SELECT id FROM project_users WHERE project_id = ? AND user_id = ?');
$stmt->bind_param('ii', $projectId, $user['id']);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    http_response_code(403);
    exit;
}
$stmt->close();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no'); // disable nginx buffering so events flush immediately
header('Connection: keep-alive');

// keep the connection open for ~55s of polling; the JS client reconnects automatically after that
$deadline = time() + 55;

while (time() < $deadline) {
    if (connection_aborted()) {
        break;
    }

    // Exclude messages that originated from this exact browser tab (matched by
    // client_id) — that tab already rendered them optimistically the instant
    // "send" was pressed. Other tabs of the same account, and everyone else,
    // still receive the message normally. This also fixes the old bug where
    // two tabs of the same logged-in user would never see each other's messages
    // live (the previous version deduped by user_id, which both tabs share).
    $stmt = $conn->prepare("SELECT id, user_id, sender_label, message, attachment_url, attachment_type, is_ai, created_at FROM messages WHERE project_id = ? AND id > ? AND NOT (client_id IS NOT NULL AND client_id = ?) ORDER BY id ASC");
    $stmt->bind_param('iis', $projectId, $lastId, $clientId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $lastId = $row['id'];
        echo "event: message\n";
        echo 'data: ' . json_encode($row) . "\n\n";
    }
    $stmt->close();

    echo ": keep-alive\n\n"; // comment ping to keep proxies from closing the connection
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();

    sleep(1); // poll interval - the "realtime" cadence
}
