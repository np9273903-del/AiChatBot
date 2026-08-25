<?php
/**
 * Shared helper for running short-lived, sandboxed shell commands and
 * capturing their output with a hard timeout. Used by api/code/run.php
 * (and reused by run_java.php's pattern) for every supported language.
 */

function code_runner_exec($command, $workDir, $timeoutSeconds = 8) {
    $pipes = [];
    $process = proc_open($command, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes, $workDir);

    if (!is_resource($process)) {
        return ['output' => 'Could not start the runtime for this language.', 'timed_out' => false, 'exit_code' => 1];
    }

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
        usleep(80000);
    }

    $stdout .= stream_get_contents($pipes[1]);
    $stderr .= stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    return [
        'output' => trim($stdout . ($stderr !== '' ? "\n" . $stderr : '')),
        'timed_out' => false,
        'exit_code' => $exitCode,
    ];
}

function code_runner_workdir($prefix) {
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $prefix . '_' . bin2hex(random_bytes(8));
    if (!mkdir($dir, 0700, true)) {
        return null;
    }
    return $dir;
}

function code_runner_cleanup($dir) {
    if (!$dir || !is_dir($dir)) return;
    array_map('unlink', glob($dir . DIRECTORY_SEPARATOR . '*') ?: []);
    rmdir($dir);
}

/**
 * Language registry: how to write the source file and which command compiles/runs it.
 * `binary_check` is what we probe for with `command -v` before attempting anything,
 * so a missing runtime on the host fails with a clear message instead of a confusing one.
 */
function code_runner_languages() {
    return [
        'javascript' => [
            'label' => 'JavaScript (Node.js)',
            'filename' => 'main.js',
            'binary_check' => 'node',
            'run' => 'node ' . escapeshellarg('main.js'),
        ],
        'python' => [
            'label' => 'Python 3',
            'filename' => 'main.py',
            'binary_check' => 'python3',
            'run' => 'python3 ' . escapeshellarg('main.py'),
        ],
        'php' => [
            'label' => 'PHP',
            'filename' => 'main.php',
            'binary_check' => 'php',
            'run' => 'php ' . escapeshellarg('main.php'),
        ],
        'c' => [
            'label' => 'C',
            'filename' => 'main.c',
            'binary_check' => 'gcc',
            'compile' => 'gcc -O0 -o main main.c',
            'run' => './main',
        ],
        'cpp' => [
            'label' => 'C++',
            'filename' => 'main.cpp',
            'binary_check' => 'g++',
            'compile' => 'g++ -O0 -o main main.cpp',
            'run' => './main',
        ],
    ];
}
