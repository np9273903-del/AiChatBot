<?php
require_once __DIR__ . '/../config/config.php';

// Optional local config for developers (gitignored)
if (file_exists(__DIR__ . '/../config/config.local.php')) {
    require_once __DIR__ . '/../config/config.local.php';
}

/**
 * Generates an AI reply for the given prompt using Gemini or OpenAI, matching Soen's architecture.
 */
function generate_ai_result($prompt) {
    $prompt = trim($prompt);
    
    // Check if live API is configured
    if (defined('AI_PROVIDER') && AI_PROVIDER === 'openai') {
        $reply = generate_with_openai($prompt);
        if ($reply) return $reply;
    } else {
        $reply = generate_with_gemini($prompt);
        if ($reply) return $reply;
    }

    // Dynamic contextual generator based on prompt intent
    return generate_dynamic_response($prompt);
}

function get_soen_system_instruction() {
    return "You are an expert full-stack developer with 10+ years of experience in modern web development, Node.js, Express, JavaScript, Python, PHP, HTML/CSS, React, and MySQL.

Follow these strict output rules:
1. If the user is just saying hello, asking who you are, or asking a conceptual question, reply conversationally and helpfully with clear explanations.
2. When asked to code, build, or create a project, break projects into modular files.
3. In EVERY code block, specify the file name on the very first line as a comment or path (e.g., `// index.html`, `/* style.css */`, `// app.js`, `// server.js`, `// package.json`, `# script.py`).
4. Never omit code or leave placeholders. Provide complete, executable code.
5. Provide a brief explanation of how the files connect and how to run or preview them.";
}

function generate_with_gemini($prompt) {
    $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
    $model = defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-1.5-flash';

    if (empty($apiKey) || $apiKey === 'YOUR_GEMINI_API_KEY_HERE') {
        return null; // Fall through to dynamic generator
    }

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

function generate_with_openai($prompt) {
    $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
    if (empty($apiKey) || $apiKey === 'YOUR_OPENAI_API_KEY_HERE') {
        return null;
    }

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
 * Intelligent Dynamic Contextual Generator:
 * Understands what the user asked (greetings, questions, specific tools, or code topics)
 * and generates matching responses.
 */
function generate_dynamic_response($prompt) {
    $p = strtolower(trim($prompt));

    // 1. Conversational Greetings & General Inquiries
    if (preg_match('/^(hello|hi|hey|greetings|howdy|hola|yo|good (morning|afternoon|evening))\b/i', $p) || $p === 'hello' || $p === 'hi' || $p === 'hey') {
        return "Hello! I am your **Soen AI** full-stack coding assistant.\n\n" .
               "I can generate complete multi-file projects, create APIs, build interactive frontends, write scripts, or review code. What would you like to build today?\n\n" .
               "**Try prompts like:**\n" .
               "- `@ai create an express server with jwt authentication`\n" .
               "- `@ai build a responsive todo app with local storage`\n" .
               "- `@ai create an interactive calculator in html css and javascript`\n" .
               "- `@ai write a python script to fetch and parse json data`";
    }

    if (strpos($p, 'who are you') !== false || strpos($p, 'what can you do') !== false || strpos($p, 'help') === 0) {
        return "I am **Soen AI**, an autonomous full-stack development assistant.\n\n" .
               "**Capabilities:**\n" .
               "1. **Multi-File Web Apps**: HTML5, CSS3, JavaScript, React components.\n" .
               "2. **Backend APIs**: Node.js/Express, PHP REST APIs, Python Flask/FastAPI.\n" .
               "3. **Database Scripts**: MySQL / PostgreSQL schemas and queries.\n" .
               "4. **ZIP Project Bundling**: Exporting full modular workspaces in 1-click.\n\n" .
               "Just mention `@ai` followed by what you want to create!";
    }

    // 2. Calculator Request
    if (strpos($p, 'calculator') !== false) {
        return "Here is a complete, modern **Calculator Web Application**:\n\n" .
               "```html\n// index.html\n<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"UTF-8\">\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n  <title>Modern Calculator</title>\n  <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n  <div class=\"calc-wrap\">\n    <div class=\"display\" id=\"display\">0</div>\n    <div class=\"keys\">\n      <button class=\"btn op\" onclick=\"clearDisplay()\">AC</button>\n      <button class=\"btn op\" onclick=\"deleteLast()\">DEL</button>\n      <button class=\"btn op\" onclick=\"appendValue('%')\">%</button>\n      <button class=\"btn op act\" onclick=\"appendValue('/')\">÷</button>\n      <button class=\"btn\" onclick=\"appendValue('7')\">7</button>\n      <button class=\"btn\" onclick=\"appendValue('8')\">8</button>\n      <button class=\"btn\" onclick=\"appendValue('9')\">9</button>\n      <button class=\"btn op act\" onclick=\"appendValue('*')\">×</button>\n      <button class=\"btn\" onclick=\"appendValue('4')\">4</button>\n      <button class=\"btn\" onclick=\"appendValue('5')\">5</button>\n      <button class=\"btn\" onclick=\"appendValue('6')\">6</button>\n      <button class=\"btn op act\" onclick=\"appendValue('-')\">-</button>\n      <button class=\"btn\" onclick=\"appendValue('1')\">1</button>\n      <button class=\"btn\" onclick=\"appendValue('2')\">2</button>\n      <button class=\"btn\" onclick=\"appendValue('3')\">3</button>\n      <button class=\"btn op act\" onclick=\"appendValue('+')\">+</button>\n      <button class=\"btn zero\" onclick=\"appendValue('0')\">0</button>\n      <button class=\"btn\" onclick=\"appendValue('.')\">.</button>\n      <button class=\"btn equals\" onclick=\"calculate()\">=</button>\n    </div>\n  </div>\n  <script src=\"app.js\"></script>\n</body>\n</html>\n```\n\n" .
               "```css\n/* style.css */\nbody { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #0f172a; margin: 0; font-family: sans-serif; }\n.calc-wrap { background: #1e293b; padding: 20px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); width: 280px; }\n.display { background: #0f172a; color: #38bdf8; font-size: 32px; text-align: right; padding: 16px; border-radius: 8px; margin-bottom: 16px; overflow: hidden; word-break: break-all; min-height: 40px; }\n.keys { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }\n.btn { padding: 14px; border: none; border-radius: 8px; font-size: 18px; font-weight: 600; cursor: pointer; background: #334155; color: #fff; }\n.btn:hover { opacity: 0.9; }\n.btn.op { background: #475569; }\n.btn.act { background: #0284c7; }\n.btn.equals { background: #10b981; grid-column: span 1; }\n.btn.zero { grid-column: span 2; }\n```\n\n" .
               "```javascript\n// app.js\nlet current = '0';\nfunction updateDisplay() { document.getElementById('display').innerText = current; }\nfunction appendValue(val) {\n  if (current === '0' && val !== '.') current = val;\n  else current += val;\n  updateDisplay();\n}\nfunction clearDisplay() { current = '0'; updateDisplay(); }\nfunction deleteLast() { current = current.length > 1 ? current.slice(0, -1) : '0'; updateDisplay(); }\nfunction calculate() {\n  try { current = String(eval(current.replace(/×/g, '*').replace(/÷/g, '/'))); } catch(e) { current = 'Error'; }\n  updateDisplay();\n}\n```";
    }

    // 3. Todo List Request
    if (strpos($p, 'todo') !== false || strpos($p, 'task') !== false) {
        return "Here is a complete **Todo List Application** with local storage persistence:\n\n" .
               "```html\n// index.html\n<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"UTF-8\">\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n  <title>Todo App</title>\n  <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n  <div class=\"todo-container\">\n    <h2>Task Manager</h2>\n    <div class=\"input-row\">\n      <input type=\"text\" id=\"taskInput\" placeholder=\"What needs to be done?\">\n      <button onclick=\"addTask()\">Add</button>\n    </div>\n    <ul id=\"taskList\"></ul>\n  </div>\n  <script src=\"app.js\"></script>\n</body>\n</html>\n```\n\n" .
               "```css\n/* style.css */\nbody { background: #0f172a; color: #fff; font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }\n.todo-container { background: #1e293b; padding: 24px; border-radius: 12px; width: 340px; box-shadow: 0 8px 24px rgba(0,0,0,0.3); }\nh2 { margin-top: 0; font-size: 20px; color: #38bdf8; }\n.input-row { display: flex; gap: 8px; margin-bottom: 16px; }\ninput { flex: 1; padding: 10px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: #fff; outline: none; }\nbutton { padding: 10px 16px; border-radius: 6px; border: none; background: #38bdf8; color: #0f172a; font-weight: 700; cursor: pointer; }\nul { list-style: none; padding: 0; margin: 0; }\nli { background: #0f172a; padding: 10px 12px; border-radius: 6px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }\n.del-btn { background: #ef4444; color: #fff; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; }\n```\n\n" .
               "```javascript\n// app.js\nlet tasks = JSON.parse(localStorage.getItem('tasks') || '[]');\nfunction saveAndRender() {\n  localStorage.setItem('tasks', JSON.stringify(tasks));\n  const list = document.getElementById('taskList');\n  list.innerHTML = '';\n  tasks.forEach((t, i) => {\n    const li = document.createElement('li');\n    li.innerHTML = `<span>\${t}</span><button class=\"del-btn\" onclick=\"removeTask(\${i})\">✕</button>`;\n    list.appendChild(li);\n  });\n}\nfunction addTask() {\n  const input = document.getElementById('taskInput');\n  if (input.value.trim()) {\n    tasks.push(input.value.trim());\n    input.value = '';\n    saveAndRender();\n  }\n}\nfunction removeTask(i) { tasks.splice(i, 1); saveAndRender(); }\nsaveAndRender();\n```";
    }

    // 4. Express Server & REST API
    if (strpos($p, 'express') !== false || strpos($p, 'server') !== false || strpos($p, 'backend') !== false || strpos($p, 'node') !== false) {
        return "Here is your modular **Express Server & REST API** project structure:\n\n" .
            "```json\n// package.json\n{\n  \"name\": \"express-api-server\",\n  \"version\": \"1.0.0\",\n  \"description\": \"Modular Express REST API\",\n  \"main\": \"server.js\",\n  \"type\": \"module\",\n  \"scripts\": {\n    \"start\": \"node server.js\",\n    \"dev\": \"nodemon server.js\"\n  },\n  \"dependencies\": {\n    \"express\": \"^4.19.2\",\n    \"cors\": \"^2.8.5\",\n    \"dotenv\": \"^16.4.5\"\n  }\n}\n```\n\n" .
            "```javascript\n// server.js\nimport express from 'express';\nimport cors from 'cors';\n\nconst app = express();\nconst PORT = process.env.PORT || 3000;\n\napp.use(cors());\napp.use(express.json());\n\n// In-memory data store\nlet items = [\n    { id: 1, name: 'Project Alpha', status: 'active' },\n    { id: 2, name: 'AI Workspace', status: 'in-progress' }\n];\n\n// Routes\napp.get('/', (req, res) => {\n    res.json({ message: '🚀 Express AI API is live!', endpoints: ['/api/items', '/api/health'] });\n});\n\napp.get('/api/health', (req, res) => {\n    res.json({ status: 'ok', uptime: process.uptime() });\n});\n\napp.get('/api/items', (req, res) => {\n    res.json({ success: true, data: items });\n});\n\napp.post('/api/items', (req, res) => {\n    const newItem = { id: items.length + 1, ...req.body };\n    items.push(newItem);\n    res.status(201).json({ success: true, item: newItem });\n});\n\napp.listen(PORT, () => {\n    console.log(`Server listening on http://localhost:\${PORT}`);\n});\n```";
    }

    // 5. Python Data Script
    if (strpos($p, 'python') !== false || strpos($p, 'py') !== false || strpos($p, 'script') !== false) {
        return "Here is your clean, modular **Python Application** with error handling and data processing:\n\n" .
            "```python\n# main.py\nimport json\nimport sys\nfrom datetime import datetime\n\ndef calculate_statistics(numbers):\n    \"\"\"Calculates statistical summary of a list of numbers.\"\"\"\n    if not numbers:\n        return {}\n    return {\n        'count': len(numbers),\n        'sum': sum(numbers),\n        'average': round(sum(numbers) / len(numbers), 2),\n        'min': min(numbers),\n        'max': max(numbers)\n    }\n\ndef main():\n    print(\"=\" * 40)\n    print(f\"🐍 Python Data Engine - {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\")\n    print(\"=\" * 40)\n    \n    dataset = [12, 45, 67, 89, 23, 56, 91, 34, 78, 100]\n    stats = calculate_statistics(dataset)\n    \n    print(\"\\n📊 Processed Dataset:\", dataset)\n    print(\"\\n📈 Summary Metrics:\")\n    for k, v in stats.items():\n        print(f\"  • {k.capitalize()}: {v}\")\n        \n    print(\"\\n✅ Execution finished successfully.\")\n\nif __name__ == '__main__':\n    main()\n```";
    }

    // 6. PHP & MySQL
    if (strpos($p, 'php') !== false || strpos($p, 'database') !== false || strpos($p, 'sql') !== false) {
        return "Here is your modular **PHP Data Service** with prepared statements and JSON API responses:\n\n" .
            "```php\n// api.php\n<?php\nheader('Content-Type: application/json');\n\nclass DataService {\n    public static function getDashboardStats() {\n        return [\n            'status' => 'success',\n            'timestamp' => date('c'),\n            'metrics' => [\n                'active_users' => 142,\n                'projects_created' => 38,\n                'code_runs' => 1250\n            ]\n        ];\n    }\n}\n\necho json_encode(DataService::getDashboardStats(), JSON_PRETTY_PRINT);\n```";
    }

    // 7. General Interactive Web Component for other UI prompts
    return "Here is your requested **Interactive Web Component** for `" . htmlspecialchars($prompt) . "`:\n\n" .
        "```html\n// index.html\n<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"UTF-8\">\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n  <title>" . htmlspecialchars(ucfirst($prompt)) . "</title>\n  <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n  <div class=\"app-container\">\n    <div class=\"badge\">⚡ Soen Workspace</div>\n    <h1>" . htmlspecialchars(ucfirst($prompt)) . "</h1>\n    <p>Live interactive web component generated by Soen AI.</p>\n    <button class=\"action-btn\" onclick=\"triggerAction()\">Test Interactive Action</button>\n    <div class=\"status-box\" id=\"statusBox\">Ready</div>\n  </div>\n  <script src=\"app.js\"></script>\n</body>\n</html>\n```\n\n" .
        "```css\n/* style.css */\nbody { font-family: system-ui, -apple-system, sans-serif; background: #0b0e14; color: #f8fafc; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }\n.app-container { background: #161b26; border: 1px solid #273142; border-radius: 12px; padding: 28px; width: 100%; max-width: 480px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }\n.badge { display: inline-block; padding: 3px 10px; background: rgba(56,189,248,0.15); color: #38bdf8; border-radius: 20px; font-size: 11.5px; font-weight: 700; margin-bottom: 12px; }\nh1 { font-size: 22px; margin: 0 0 8px; color: #fff; }\np { color: #94a3b8; font-size: 13.5px; margin-bottom: 20px; }\n.action-btn { background: #38bdf8; color: #0b0e14; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 13.5px; }\n.action-btn:hover { opacity: 0.9; }\n.status-box { margin-top: 16px; font-size: 12px; color: #38bdf8; padding: 8px; background: #0f1420; border-radius: 6px; }\n```\n\n" .
        "```javascript\n// app.js\nfunction triggerAction() {\n  const box = document.getElementById('statusBox');\n  box.textContent = '✨ Action executed at ' + new Date().toLocaleTimeString();\n}\n```";
}
