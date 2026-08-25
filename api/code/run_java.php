<?php
require_once __DIR__ . '/../../includes/auth_check.php';

header('Content-Type: application/json');
require_auth_api();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$code = trim((string) ($input['code'] ?? ''));
if ($code === '' || strlen($code) > 50000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Java code is required and must be under 50 KB']);
    exit;
}

if (!preg_match('/\bpublic\s+class\s+([A-Za-z_$][A-Za-z0-9_$]*)\b/', $code, $match)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Java code must contain one public class']);
    exit;
}

$className = $match[1];
$workDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'soen_java_' . bin2hex(random_bytes(8));
if (!mkdir($workDir, 0700, true)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not create a temporary Java workspace']);
    exit;
}

$sourcePath = $workDir . DIRECTORY_SEPARATOR . $className . '.java';
file_put_contents($sourcePath, $code);

function run_java_command($command, $workDir, $timeoutSeconds = 8) {
    $pipes = [];
    $process = proc_open($command, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes, $workDir);
    if (!is_resource($process)) return ['output' => 'Could not start Java runtime.', 'timed_out' => false, 'exit_code' => 1];

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $startedAt = microtime(true);
    $stdout = '';
    $stderr = '';

    while (true) {
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);
        $status = proc_get_status($process);
        if (!$status['running']) break;
        if (microtime(true) - $startedAt >= $timeoutSeconds) {
            proc_terminate($process);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            return ['output' => "Execution timed out after {$timeoutSeconds} seconds.", 'timed_out' => true, 'exit_code' => 124];
        }
        usleep(100000);
    }

    $stdout .= stream_get_contents($pipes[1]);
    $stderr .= stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    return ['output' => trim($stdout . ($stderr !== '' ? "\n" . $stderr : '')), 'timed_out' => false, 'exit_code' => $exitCode];
}

$compiler = run_java_command('javac -encoding UTF-8 ' . escapeshellarg(basename($sourcePath)), $workDir);
if ($compiler['timed_out'] || $compiler['exit_code'] !== 0) {
    $result = $compiler['output'] !== '' ? $compiler['output'] : 'Java compilation failed.';
    array_map('unlink', glob($workDir . DIRECTORY_SEPARATOR . '*') ?: []);
    rmdir($workDir);
    echo json_encode(['success' => false, 'stage' => 'compile', 'output' => $result]);
    exit;
}

$runner = run_java_command('java -cp ' . escapeshellarg($workDir) . ' ' . escapeshellarg($className), $workDir);
array_map('unlink', glob($workDir . DIRECTORY_SEPARATOR . '*') ?: []);
rmdir($workDir);

echo json_encode([
    'success' => !$runner['timed_out'],
    'stage' => 'run',
    'output' => $runner['output'] !== '' ? $runner['output'] : '(program finished with no output)',
]);
