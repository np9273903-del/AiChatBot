<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/ai_service.php';

header('Content-Type: text/html; charset=utf-8');

$apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
$isKeyConfigured = (!empty($apiKey) && $apiKey !== 'YOUR_GEMINI_API_KEY_HERE');

echo "<!DOCTYPE html><html><head><title>AI API Key Diagnostic</title>";
echo "<style>body { font-family: -apple-system, sans-serif; background: #0f172a; color: #f8fafc; padding: 30px; line-height: 1.6; }
.card { background: #1e293b; padding: 24px; border-radius: 10px; border: 1px solid #334155; max-width: 600px; margin-bottom: 20px; }
.ok { color: #34d399; font-weight: bold; }
.err { color: #f87171; font-weight: bold; }
.badge { background: #0f172a; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 13px; }
pre { background: #0b0f19; padding: 14px; border-radius: 6px; overflow-x: auto; color: #38bdf8; font-family: monospace; font-size: 13px; }
a { color: #38bdf8; }
</style></head><body>";

echo "<h2>⚡ Soen AI Diagnostic Tool</h2>";

echo "<div class='card'>";
echo "<h3>1. API Key Status</h3>";
if ($isKeyConfigured) {
    $maskedKey = substr($apiKey, 0, 6) . '...' . substr($apiKey, -4);
    echo "<p class='ok'>✓ Gemini API Key is configured in your project: <span class='badge'>{$maskedKey}</span></p>";
} else {
    echo "<p class='err'>✗ Gemini API Key is currently NOT SET (using default placeholder).</p>";
    echo "<p>To add your Gemini API Key:</p>";
    echo "<ol>";
    echo "<li>Open file: <span class='badge'>config/config.local.php</span></li>";
    echo "<li>Add this line:<br><pre>&lt;?php\ndefine('GEMINI_API_KEY', 'YOUR_ACTUAL_AIzaSy_KEY');</pre></li>";
    echo "<li>Save the file and refresh this page.</li>";
    echo "</ol>";
}
echo "</div>";

echo "<div class='card'>";
echo "<h3>2. Live AI Generation Test</h3>";
echo "<p>Testing prompt: <span class='badge'>@ai code for java</span></p>";

$startTime = microtime(true);
$result = generate_ai_result("code for java");
$duration = round(microtime(true) - $startTime, 2);

echo "<p>Response Time: <strong>{$duration}s</strong></p>";
echo "<p><strong>AI Response Output:</strong></p>";
echo "<pre>" . htmlspecialchars($result) . "</pre>";
echo "</div>";

echo "<p><a href='../../home.php'>← Back to Workspace</a></p>";
echo "</body></html>";
