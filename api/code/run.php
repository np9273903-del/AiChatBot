<?php
/**
 * Generic "run this code" endpoint used by the Code Runner panel in project.php.
 * Java keeps its own dedicated endpoint (run_java.php) because it needs the
 * public-class-name-must-match-filename dance; everything else goes through here.
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/code_runner.php';

header('Content-Type: application/json');
require_auth_api();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$language = strtolower(trim((string) ($input['language'] ?? '')));
$code = (string) ($input['code'] ?? '');

if (strlen($code) > 50000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Code must be under 50 KB']);
    exit;
}
if (trim($code) === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Code is required']);
    exit;
}

$languages = code_runner_languages();
if (!isset($languages[$language])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Unsupported language. Supported: ' . implode(', ', array_keys($languages)) . ', java (use run_java.php).',
    ]);
    exit;
}
$lang = $languages[$language];

// Fail fast with a clear message if the runtime simply isn't installed on this host,
// rather than letting proc_open fail cryptically later.
$check = code_runner_exec('command -v ' . escapeshellarg($lang['binary_check']), sys_get_temp_dir(), 3);
if (trim($check['output']) === '' || $check['exit_code'] !== 0) {
    echo json_encode([
        'success' => false,
        'stage' => 'setup',
        'output' => "The '{$lang['binary_check']}' runtime isn't installed on this server, so {$lang['label']} code can't run here yet. Ask an admin to install it.",
    ]);
    exit;
}

$workDir = code_runner_workdir('soen_run');
if (!$workDir) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not create a temporary workspace']);
    exit;
}

file_put_contents($workDir . DIRECTORY_SEPARATOR . $lang['filename'], $code);

if (!empty($lang['compile'])) {
    $compile = code_runner_exec($lang['compile'], $workDir, 8);
    if ($compile['timed_out'] || $compile['exit_code'] !== 0) {
        $out = $compile['output'] !== '' ? $compile['output'] : 'Compilation failed.';
        code_runner_cleanup($workDir);
        echo json_encode(['success' => false, 'stage' => 'compile', 'output' => $out]);
        exit;
    }
}

$run = code_runner_exec($lang['run'], $workDir, 8);
code_runner_cleanup($workDir);

echo json_encode([
    'success' => !$run['timed_out'],
    'stage' => 'run',
    'output' => $run['output'] !== '' ? $run['output'] : '(program finished with no output)',
]);
