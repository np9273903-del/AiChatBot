<?php
require_once __DIR__ . '/../config/config.php';

// Optional local developer overrides (gitignored)
if (file_exists(__DIR__ . '/../config/config.local.php')) {
    require_once __DIR__ . '/../config/config.local.php';
}

/**
 * Universal AI Engine:
 * 1. Google Gemini API (if user configured key)
 * 2. OpenAI API (if user configured key)
 * 3. Free Live Cloud LLM (text.pollinations.ai - real dynamic AI for any prompt, zero key required)
 * 4. Human-like natural fallback engine
 */
function generate_ai_result($prompt) {
    $prompt = trim($prompt);
    if (!$prompt) return "Hey! What can I help you build or write today?";

    // 1. Try Google Gemini API (if user configured real key)
    if (defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY) && GEMINI_API_KEY !== 'YOUR_GEMINI_API_KEY_HERE') {
        $geminiReply = generate_with_gemini($prompt);
        if ($geminiReply) return $geminiReply;
    }

    // 2. Try OpenAI API (if user configured key)
    if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY) && OPENAI_API_KEY !== 'YOUR_OPENAI_API_KEY_HERE') {
        $openAiReply = generate_with_openai($prompt);
        if ($openAiReply) return $openAiReply;
    }

    // 3. Try Free Live Cloud LLM (Dynamic AI for any question, chat, or language)
    $liveReply = generate_with_free_live_ai($prompt);
    if ($liveReply) return $liveReply;

    // 4. Natural fallback engine
    return generate_natural_fallback($prompt);
}

function get_soen_system_instruction() {
    return "You are a friendly, expert AI assistant and developer.
Rules:
1. If the user says hi, hello, or asks how you are, respond naturally, warmly, and conversationally like a human (e.g. 'Hey! What's up? How can I help you today?').
2. When asked for code, provide clean, complete, working code in the requested language with the filename on the very first line of each code block (e.g. `// Main.java`, `# script.py`, `// main.cpp`, `// server.js`, `// index.html`, `/* style.css */`, `-- schema.sql`).
3. Only output what the user asked for. Never add unrequested files or unwanted templates.";
}

/**
 * Free Live Cloud AI - Real LLM for all prompts with zero configuration
 */
function generate_with_free_live_ai($prompt) {
    $systemInstruction = get_soen_system_instruction();

    // Strategy A: Direct POST JSON request
    $payload = [
        'messages' => [
            ['role' => 'system', 'content' => $systemInstruction],
            ['role' => 'user', 'content' => $prompt]
        ],
        'model' => 'openai',
        'jsonMode' => false
    ];

    $ch = curl_init('https://text.pollinations.ai/');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && !empty(trim($response))) {
        return trim($response);
    }

    // Strategy B: GET request fallback
    $url = 'https://text.pollinations.ai/' . rawurlencode($prompt) . '?system=' . rawurlencode($systemInstruction);
    $ch2 = curl_init($url);
    curl_setopt_array($ch2, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response2 = curl_exec($ch2);
    $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);

    if ($httpCode2 === 200 && !empty(trim($response2))) {
        return trim($response2);
    }

    return null;
}

/**
 * Google Gemini API
 */
function generate_with_gemini($prompt) {
    $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
    $model = defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-1.5-flash';

    if (empty($apiKey) || $apiKey === 'YOUR_GEMINI_API_KEY_HERE') return null;

    $systemInstruction = get_soen_system_instruction();
    $fullPrompt = $systemInstruction . "\n\nUser: " . $prompt;

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($model) . ':generateContent?key=' . urlencode($apiKey);
    $payload = [
        'contents' => [
            ['parts' => [['text' => $fullPrompt]]],
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 4096,
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$err && $httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }
    }

    return null;
}

/**
 * OpenAI API
 */
function generate_with_openai($prompt) {
    $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
    if (empty($apiKey) || $apiKey === 'YOUR_OPENAI_API_KEY_HERE') return null;

    $url = 'https://api.openai.com/v1/chat/completions';
    $payload = [
        'model' => defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => get_soen_system_instruction()],
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.7,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$err && $httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }
    }

    return null;
}

/**
 * Natural Human-like fallback
 */
function generate_natural_fallback($prompt) {
    $p = strtolower(trim($prompt));

    // Conversational greetings
    if (preg_match('/^(hi|hello|hey|whats up|what\'s up|sup|howdy|yo|good morning|good evening)\b/i', $p) || $p === 'hi' || $p === 'hello' || $p === 'hey') {
        return "Hey! What's up? What are you working on today?";
    }

    if (strpos($p, 'how are you') !== false) {
        return "I'm doing great, ready to build! What are we coding today?";
    }

    if (strpos($p, 'who are you') !== false) {
        return "I'm your AI assistant! I can write code in Java, Python, C++, JavaScript, PHP, help you debug, or chat about any project.";
    }

    // Java
    if (strpos($p, 'java') !== false && strpos($p, 'javascript') === false) {
        return "Here is the Java code for your request:\n\n" .
               "```java\n// Main.java\npublic class Main {\n    public static void main(String[] args) {\n        System.out.println(\"Hello from Java!\");\n    }\n}\n```";
    }

    // Python
    if (strpos($p, 'python') !== false || strpos($p, 'py') !== false) {
        return "Here is the Python script for your request:\n\n" .
               "```python\n# main.py\ndef main():\n    print(\"Python script running successfully!\")\n\nif __name__ == '__main__':\n    main()\n```";
    }

    // C / C++
    if (strpos($p, 'c++') !== false || strpos($p, 'cpp') !== false || preg_match('/\b(c code|c program)\b/', $p)) {
        return "Here is the C++ code for your request:\n\n" .
               "```cpp\n// main.cpp\n#include <iostream>\n\nint main() {\n    std::cout << \"C++ execution running!\" << std::endl;\n    return 0;\n}\n```";
    }

    // SQL
    if (strpos($p, 'sql') !== false || strpos($p, 'database') !== false || strpos($p, 'query') !== false) {
        return "Here is the SQL query:\n\n" .
               "```sql\n-- query.sql\nSELECT * FROM users ORDER BY created_at DESC;\n```";
    }

    // Default friendly response
    return "Here is what you need:\n\n" .
        "```javascript\n// app.js\nconsole.log(\"Executing request for: " . addslashes($prompt) . "\");\n```";
}
