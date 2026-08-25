<?php
require_once __DIR__ . '/../config/config.php';

// Optional local developer config (gitignored)
if (file_exists(__DIR__ . '/../config/config.local.php')) {
    require_once __DIR__ . '/../config/config.local.php';
}

/**
 * Generates an AI reply for any given prompt using:
 * 1. Google Gemini API (if key is set)
 * 2. OpenAI API (if key is set)
 * 3. Free Live Cloud AI Engine (text.pollinations.ai - Real AI, zero-key required)
 * 4. Comprehensive Multi-Language Dynamic Generator (Offline safety fallback)
 */
function generate_ai_result($prompt) {
    $prompt = trim($prompt);
    if (!$prompt) return "Please provide a prompt after `@ai`. For example: `@ai create a Java Student management program`";

    // 1. Try Google Gemini API (if user configured a real key)
    if (defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY) && GEMINI_API_KEY !== 'YOUR_GEMINI_API_KEY_HERE') {
        $geminiReply = generate_with_gemini($prompt);
        if ($geminiReply) return $geminiReply;
    }

    // 2. Try OpenAI API (if user configured a key)
    if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY) && OPENAI_API_KEY !== 'YOUR_OPENAI_API_KEY_HERE') {
        $openAiReply = generate_with_openai($prompt);
        if ($openAiReply) return $openAiReply;
    }

    // 3. Try Free Live Cloud AI (Real live AI responses for ANY prompt with zero API key required)
    $liveReply = generate_with_free_live_ai($prompt);
    if ($liveReply) return $liveReply;

    // 4. Offline Multi-Language Engine (Java, Python, C++, PHP, JS, SQL, HTML, etc.)
    return generate_offline_multilang_code($prompt);
}

function get_soen_system_instruction() {
    return "You are an expert full-stack developer and computer science engineer with 10+ years of experience in Java, Python, C++, C, JavaScript, Node.js, Express, PHP, MySQL, HTML/CSS, React, and algorithms.

Strict formatting rules:
1. If the user asks a question or greeting, answer conversationally with clear explanations.
2. In EVERY code block, specify the file name on the very first line as a comment or path (e.g. `// Main.java`, `// server.js`, `// index.html`, `/* style.css */`, `// app.js`, `# script.py`, `// api.php`).
3. Break projects into modular files whenever appropriate.
4. Provide complete, working, runnable code with no missing placeholders.
5. Provide a brief explanation of how to compile, run, or preview the code.";
}

/**
 * Free Live Cloud AI Engine (Real AI generating responses for any language or topic)
 */
function generate_with_free_live_ai($prompt) {
    $systemInstruction = get_soen_system_instruction();
    
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
        CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$err && $httpCode === 200 && !empty(trim($response))) {
        return trim($response);
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
    $fullPrompt = $systemInstruction . "\n\nUser Request: " . $prompt;

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($model) . ':generateContent?key=' . urlencode($apiKey);
    $payload = [
        'contents' => [
            ['parts' => [['text' => $fullPrompt]]],
        ],
        'generationConfig' => [
            'temperature' => 0.4,
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
        'temperature' => 0.4,
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
 * Offline Multi-Language Engine (Handles Java, C++, Python, PHP, JS, SQL, etc.)
 */
function generate_offline_multilang_code($prompt) {
    $p = strtolower(trim($prompt));

    // Conversational greetings
    if (preg_match('/^(hello|hi|hey|greetings|howdy|yo)\b/i', $p) || $p === 'hello' || $p === 'hi') {
        return "Hello! I am your **Soen AI** development assistant.\n\n" .
               "I can generate code in **Java**, **Python**, **C++**, **JavaScript / Node.js**, **PHP**, **HTML/CSS**, **SQL**, and more.\n\n" .
               "What would you like to build? Try: `@ai code for java`, `@ai express server`, `@ai python data script`, or `@ai calculator web app`.";
    }

    // 1. JAVA
    if (strpos($p, 'java') !== false) {
        return "Here is a complete, runnable **Java Application** with object-oriented architecture and error handling:\n\n" .
               "```java\n// Main.java\nimport java.util.ArrayList;\nimport java.util.List;\n\nclass Student {\n    private int id;\n    private String name;\n    private double grade;\n\n    public Student(int id, String name, double grade) {\n        this.id = id;\n        this.name = name;\n        this.grade = grade;\n    }\n\n    public int getId() { return id; }\n    public String getName() { return name; }\n    public double getGrade() { return grade; }\n\n    @Override\n    public String toString() {\n        return String.format(\"Student[ID=%d, Name='%s', Grade=%.2f]\", id, name, grade);\n    }\n}\n\npublic class Main {\n    public static void main(String[] args) {\n        System.out.println(\"========================================\");\n        System.out.println(\"🚀 Java Student Management System\");\n        System.out.println(\"========================================\");\n\n        List<Student> students = new ArrayList<>();\n        students.add(new Student(101, \"Alice Johnson\", 92.5));\n        students.add(new Student(102, \"Bob Smith\", 88.0));\n        students.add(new Student(103, \"Charlie Brown\", 95.7));\n\n        System.out.println(\"\\nRegistered Students:\");\n        for (Student s : students) {\n            System.out.println(\"  • \" + s);\n        }\n\n        double avg = students.stream().mapToDouble(Student::getGrade).average().orElse(0.0);\n        System.out.printf(\"\\nClass Average: %.2f%%%n\", avg);\n        System.out.println(\"\\n✅ Execution finished successfully.\");\n    }\n}\n```";
    }

    // 2. C / C++
    if (strpos($p, 'c++') !== false || strpos($p, 'cpp') !== false || preg_match('/\b(c code|c program)\b/', $p)) {
        return "Here is a clean, modern **C++ Program** with memory management and standard library containers:\n\n" .
               "```cpp\n// main.cpp\n#include <iostream>\n#include <vector>\n#include <string>\n#include <numeric>\n#include <algorithm>\n\nstruct Record {\n    int id;\n    std::string title;\n    double score;\n};\n\nint main() {\n    std::cout << \"======================================\\n\";\n    std::cout << \"⚡ C++ Data Processing Engine\\n\";\n    std::cout << \"======================================\\n\\n\";\n\n    std::vector<Record> records = {\n        {1, \"Alpha Task\", 98.4},\n        {2, \"Beta Feature\", 87.2},\n        {3, \"Gamma Release\", 93.6}\n    };\n\n    for (const auto& r : records) {\n        std::cout << \"[ID: \" << r.id << \"] \" << r.title << \" - Score: \" << r.score << \"\\n\";\n    }\n\n    double total = std::accumulate(records.begin(), records.end(), 0.0,\n        [](double sum, const Record& r) { return sum + r.score; });\n    \n    std::cout << \"\\nAverage Score: \" << (total / records.size()) << \"\\n\";\n    std::cout << \"\\n✅ C++ Program executed successfully.\\n\";\n    return 0;\n}\n```";
    }

    // 3. PYTHON
    if (strpos($p, 'python') !== false || strpos($p, 'py') !== false) {
        return "Here is a clean, modular **Python Application** with data processing and summary statistics:\n\n" .
               "```python\n# main.py\nimport json\nfrom datetime import datetime\n\ndef calculate_metrics(data):\n    \"\"\"Calculate statistical summaries for a numeric dataset.\"\"\"\n    if not data:\n        return {}\n    return {\n        'total_count': len(data),\n        'total_sum': sum(data),\n        'average': round(sum(data) / len(data), 2),\n        'minimum': min(data),\n        'maximum': max(data)\n    }\n\ndef main():\n    print(\"=\" * 40)\n    print(f\"🐍 Python Analytics Engine - {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\")\n    print(\"=\" * 40)\n    \n    dataset = [15, 28, 42, 67, 89, 94, 53, 76, 100]\n    stats = calculate_metrics(dataset)\n    \n    print(\"\\n📊 Input Dataset:\", dataset)\n    print(\"\\n📈 Summary Metrics:\")\n    for k, v in stats.items():\n        print(f\"  • {k.replace('_', ' ').capitalize()}: {v}\")\n        \n    print(\"\\n✅ Python execution completed.\")\n\nif __name__ == '__main__':\n    main()\n```";
    }

    // 4. EXPRESS / NODE.JS
    if (strpos($p, 'express') !== false || strpos($p, 'node') !== false || strpos($p, 'server') !== false || strpos($p, 'backend') !== false) {
        return "Here is your modular **Express Server & REST API** project structure:\n\n" .
            "```json\n// package.json\n{\n  \"name\": \"express-api-server\",\n  \"version\": \"1.0.0\",\n  \"description\": \"Modular Express REST API\",\n  \"main\": \"server.js\",\n  \"type\": \"module\",\n  \"scripts\": {\n    \"start\": \"node server.js\",\n    \"dev\": \"nodemon server.js\"\n  },\n  \"dependencies\": {\n    \"express\": \"^4.19.2\",\n    \"cors\": \"^2.8.5\",\n    \"dotenv\": \"^16.4.5\"\n  }\n}\n```\n\n" .
            "```javascript\n// server.js\nimport express from 'express';\nimport cors from 'cors';\n\nconst app = express();\nconst PORT = process.env.PORT || 3000;\n\napp.use(cors());\napp.use(express.json());\n\n// In-memory data store\nlet items = [\n    { id: 1, name: 'Project Alpha', status: 'active' },\n    { id: 2, name: 'AI Workspace', status: 'in-progress' }\n];\n\n// Routes\napp.get('/', (req, res) => {\n    res.json({ message: '🚀 Express AI API is live!', endpoints: ['/api/items', '/api/health'] });\n});\n\napp.get('/api/health', (req, res) => {\n    res.json({ status: 'ok', uptime: process.uptime() });\n});\n\napp.get('/api/items', (req, res) => {\n    res.json({ success: true, data: items });\n});\n\napp.post('/api/items', (req, res) => {\n    const newItem = { id: items.length + 1, ...req.body };\n    items.push(newItem);\n    res.status(201).json({ success: true, item: newItem });\n});\n\napp.listen(PORT, () => {\n    console.log(`Server listening on http://localhost:\${PORT}`);\n});\n```";
    }

    // 5. PHP & MYSQL
    if (strpos($p, 'php') !== false || strpos($p, 'sql') !== false || strpos($p, 'database') !== false) {
        return "Here is your modular **PHP Data Service** with prepared statements and JSON API responses:\n\n" .
            "```php\n// api.php\n<?php\nheader('Content-Type: application/json');\n\nclass DataService {\n    public static function getDashboardStats() {\n        return [\n            'status' => 'success',\n            'timestamp' => date('c'),\n            'metrics' => [\n                'active_users' => 142,\n                'projects_created' => 38,\n                'code_runs' => 1250\n            ]\n        ];\n    }\n}\n\necho json_encode(DataService::getDashboardStats(), JSON_PRETTY_PRINT);\n```";
    }

    // 6. CALCULATOR
    if (strpos($p, 'calculator') !== false) {
        return "Here is a complete, modern **Calculator Web Application**:\n\n" .
               "```html\n// index.html\n<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"UTF-8\">\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n  <title>Modern Calculator</title>\n  <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n  <div class=\"calc-wrap\">\n    <div class=\"display\" id=\"display\">0</div>\n    <div class=\"keys\">\n      <button class=\"btn op\" onclick=\"clearDisplay()\">AC</button>\n      <button class=\"btn op\" onclick=\"deleteLast()\">DEL</button>\n      <button class=\"btn op\" onclick=\"appendValue('%')\">%</button>\n      <button class=\"btn op act\" onclick=\"appendValue('/')\">÷</button>\n      <button class=\"btn\" onclick=\"appendValue('7')\">7</button>\n      <button class=\"btn\" onclick=\"appendValue('8')\">8</button>\n      <button class=\"btn\" onclick=\"appendValue('9')\">9</button>\n      <button class=\"btn op act\" onclick=\"appendValue('*')\">×</button>\n      <button class=\"btn\" onclick=\"appendValue('4')\">4</button>\n      <button class=\"btn\" onclick=\"appendValue('5')\">5</button>\n      <button class=\"btn\" onclick=\"appendValue('6')\">6</button>\n      <button class=\"btn op act\" onclick=\"appendValue('-')\">-</button>\n      <button class=\"btn\" onclick=\"appendValue('1')\">1</button>\n      <button class=\"btn\" onclick=\"appendValue('2')\">2</button>\n      <button class=\"btn\" onclick=\"appendValue('3')\">3</button>\n      <button class=\"btn op act\" onclick=\"appendValue('+')\">+</button>\n      <button class=\"btn zero\" onclick=\"appendValue('0')\">0</button>\n      <button class=\"btn\" onclick=\"appendValue('.')\">.</button>\n      <button class=\"btn equals\" onclick=\"calculate()\">=</button>\n    </div>\n  </div>\n  <script src=\"app.js\"></script>\n</body>\n</html>\n```\n\n" .
               "```css\n/* style.css */\nbody { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #0f172a; margin: 0; font-family: sans-serif; }\n.calc-wrap { background: #1e293b; padding: 20px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); width: 280px; }\n.display { background: #0f172a; color: #38bdf8; font-size: 32px; text-align: right; padding: 16px; border-radius: 8px; margin-bottom: 16px; overflow: hidden; word-break: break-all; min-height: 40px; }\n.keys { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }\n.btn { padding: 14px; border: none; border-radius: 8px; font-size: 18px; font-weight: 600; cursor: pointer; background: #334155; color: #fff; }\n.btn:hover { opacity: 0.9; }\n.btn.op { background: #475569; }\n.btn.act { background: #0284c7; }\n.btn.equals { background: #10b981; grid-column: span 1; }\n.btn.zero { grid-column: span 2; }\n```\n\n" .
               "```javascript\n// app.js\nlet current = '0';\nfunction updateDisplay() { document.getElementById('display').innerText = current; }\nfunction appendValue(val) {\n  if (current === '0' && val !== '.') current = val;\n  else current += val;\n  updateDisplay();\n}\nfunction clearDisplay() { current = '0'; updateDisplay(); }\nfunction deleteLast() { current = current.length > 1 ? current.slice(0, -1) : '0'; updateDisplay(); }\nfunction calculate() {\n  try { current = String(eval(current.replace(/×/g, '*').replace(/÷/g, '/'))); } catch(e) { current = 'Error'; }\n  updateDisplay();\n}\n```";
    }

    // 7. General Interactive Web Component for other UI prompts
    return "Here is your requested **Interactive Web Component** for `" . htmlspecialchars($prompt) . "`:\n\n" .
        "```html\n// index.html\n<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"UTF-8\">\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n  <title>" . htmlspecialchars(ucfirst($prompt)) . "</title>\n  <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n  <div class=\"app-container\">\n    <div class=\"badge\">⚡ Soen Workspace</div>\n    <h1>" . htmlspecialchars(ucfirst($prompt)) . "</h1>\n    <p>Live interactive web component generated by Soen AI.</p>\n    <button class=\"action-btn\" onclick=\"triggerAction()\">Test Interactive Action</button>\n    <div class=\"status-box\" id=\"statusBox\">Ready</div>\n  </div>\n  <script src=\"app.js\"></script>\n</body>\n</html>\n```\n\n" .
        "```css\n/* style.css */\nbody { font-family: system-ui, -apple-system, sans-serif; background: #0b0e14; color: #f8fafc; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }\n.app-container { background: #161b26; border: 1px solid #273142; border-radius: 12px; padding: 28px; width: 100%; max-width: 480px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }\n.badge { display: inline-block; padding: 3px 10px; background: rgba(56,189,248,0.15); color: #38bdf8; border-radius: 20px; font-size: 11.5px; font-weight: 700; margin-bottom: 12px; }\nh1 { font-size: 22px; margin: 0 0 8px; color: #fff; }\np { color: #94a3b8; font-size: 13.5px; margin-bottom: 20px; }\n.action-btn { background: #38bdf8; color: #0b0e14; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 13.5px; }\n.action-btn:hover { opacity: 0.9; }\n.status-box { margin-top: 16px; font-size: 12px; color: #38bdf8; padding: 8px; background: #0f1420; border-radius: 6px; }\n```\n\n" .
        "```javascript\n// app.js\nfunction triggerAction() {\n  const box = document.getElementById('statusBox');\n  box.textContent = '✨ Action executed at ' + new Date().toLocaleTimeString();\n}\n```";
}
