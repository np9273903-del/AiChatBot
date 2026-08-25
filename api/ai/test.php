<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/ai_service.php';

header('Content-Type: text/html; charset=utf-8');

$apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
$isKeySet = (!empty($apiKey) && $apiKey !== 'YOUR_GEMINI_API_KEY_HERE');

echo "<!DOCTYPE html><html><head><title>AI Diagnostic</title>
<style>
body { font-family: -apple-system, sans-serif; background: #0f172a; color: #f8fafc; padding: 32px; line-height: 1.6; }
h2 { color: #38bdf8; }
.card { background: #1e293b; padding: 22px 24px; border-radius: 10px; border: 1px solid #334155; max-width: 720px; margin-bottom: 20px; }
.ok  { color: #34d399; font-weight: 700; }
.err { color: #f87171; font-weight: 700; }
.warn{ color: #fbbf24; font-weight: 700; }
code { background: #0f172a; padding: 2px 7px; border-radius: 4px; font-family: monospace; font-size: 13px; }
pre  { background: #0b0f19; padding: 14px; border-radius: 6px; overflow-x: auto; color: #38bdf8; font-family: monospace; font-size: 12.5px; white-space: pre-wrap; word-break: break-all; }
</style></head><body>";

echo "<h2>⚡ Soen AI Diagnostic</h2>";

// 1. API Key status
echo "<div class='card'><h3>1. Gemini API Key</h3>";
if ($isKeySet) {
    $masked = substr($apiKey, 0, 6) . '...' . substr($apiKey, -4);
    echo "<p class='ok'>✓ Key loaded: <code>{$masked}</code></p>";
} else {
    echo "<p class='err'>✗ Key NOT set.</p>";
    echo "<p>Add to <code>config/config.local.php</code>:<pre>&lt;?php\ndefine('GEMINI_API_KEY', 'AIzaSy...');</pre></p>";
}
echo "</div>";

// 2. Live Gemini API Test
echo "<div class='card'><h3>2. Live Gemini API Test</h3>";
if ($isKeySet) {
    $model = defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-3-flash-preview';
    $url   = 'https://generativelanguage.googleapis.com/v1beta/models/'
           . urlencode($model) . ':generateContent?key=' . urlencode($apiKey);
    $payload = [
        'contents' => [['parts' => [['text' => 'Say: GEMINI_IS_WORKING_PERFECTLY']]]],
        'generationConfig' => ['temperature' => 0.0, 'maxOutputTokens' => 50]
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-goog-api-key: ' . $apiKey],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "<p>Tested Model: <code>{$model}</code> | HTTP Code: <code>{$code}</code></p>";
    if ($code === 200) {
        $d    = json_decode($body, true);
        $text = $d['candidates'][0]['content']['parts'][0]['text'] ?? '(no text)';
        echo "<p class='ok'>✓ Gemini response: <code>" . htmlspecialchars(trim($text)) . "</code></p>";
    } else {
        echo "<p class='err'>✗ Gemini returned HTTP {$code}</p>";
        echo "<pre>" . htmlspecialchars(substr($body, 0, 600)) . "</pre>";
    }
} else {
    echo "<p class='warn'>⚠ Skipped — no API key.</p>";
}
echo "</div>";

// 3. Full pipeline test with conversation history
echo "<div class='card'><h3>3. Full AI Pipeline Test</h3>";
$t0     = microtime(true);
$result = generate_ai_result('write a java hello world program');
$ms     = round((microtime(true) - $t0) * 1000);
echo "<p>Response time: <code>{$ms} ms</code></p>";
echo "<pre>" . htmlspecialchars(substr($result, 0, 1000)) . "</pre>";
echo "</div>";

echo "<p><a href='../../home.php' style='color:#38bdf8;'>← Back to Dashboard</a></p>";
echo "</body></html>";
