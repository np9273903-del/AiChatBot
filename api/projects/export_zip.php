<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';

$user = require_auth_api();
$projectId = intval($_GET['id'] ?? 0);

if (!$projectId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Project ID is required']);
    exit;
}

$conn = get_db();

// Verify user is a member of the project
$stmt = $conn->prepare('SELECT p.name FROM project_users pu JOIN projects p ON p.id = pu.project_id WHERE pu.project_id = ? AND pu.user_id = ?');
$stmt->bind_param('ii', $projectId, $user['id']);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Fetch all messages to extract code artifacts
$stmt = $conn->prepare('SELECT message FROM messages WHERE project_id = ? ORDER BY id ASC');
$stmt->bind_param('i', $projectId);
$stmt->execute();
$res = $stmt->get_result();
$files = [];

while ($row = $res->fetch_assoc()) {
    $msg = $row['message'];
    if (preg_match_all('/```([a-zA-Z0-9_-]*)\n([\s\S]*?)```/', $msg, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $lang = strtolower(trim($m[1] ?? 'js'));
            $code = trim($m[2] ?? '');
            $lines = explode("\n", $code);
            $firstLine = trim($lines[0] ?? '');
            
            $filename = '';
            if (preg_match('/^(?:\/\/|\/\*|#)\s*([a-zA-Z0-9_.\-\/]+\.[a-zA-Z0-9]+)/', $firstLine, $fnameMatch)) {
                $filename = $fnameMatch[1];
            } else {
                $ext = $lang === 'html' ? 'html' : ($lang === 'css' ? 'css' : ($lang === 'python' ? 'py' : ($lang === 'json' ? 'json' : 'js')));
                $filename = 'snippet_' . (count($files) + 1) . '.' . $ext;
            }
            $files[$filename] = $code;
        }
    }
}
$stmt->close();

// If no files generated yet, provide starter files
if (empty($files)) {
    $files['index.html'] = "<!DOCTYPE html>\n<html>\n<head>\n  <meta charset=\"utf-8\">\n  <title>" . htmlspecialchars($project['name']) . "</title>\n  <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n  <h1>Welcome to " . htmlspecialchars($project['name']) . "</h1>\n  <script src=\"app.js\"></script>\n</body>\n</html>";
    $files['style.css'] = "body { font-family: sans-serif; background: #0f172a; color: #fff; padding: 32px; }\nh1 { color: #38bdf8; }";
    $files['app.js'] = "console.log('Project " . addslashes($project['name']) . " loaded successfully!');";
    $files['README.md'] = "# " . $project['name'] . "\n\nGenerated with AI Chat Workspace.";
}

// Create ZIP
$zipFileName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $project['name']) . '_project.zip';
$tmpZip = tempnam(sys_get_temp_dir(), 'zip_');

$zip = new ZipArchive();
if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    foreach ($files as $name => $content) {
        $zip->addFromString($name, $content);
    }
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
    header('Content-Length: ' . filesize($tmpZip));
    header('Pragma: no-cache');
    readfile($tmpZip);
    @unlink($tmpZip);
    exit;
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not create zip archive']);
    exit;
}

