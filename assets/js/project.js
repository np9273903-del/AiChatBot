document.addEventListener('DOMContentLoaded', () => {
    const screenEl = document.getElementById('projectScreen');
    if (!screenEl) return;

    const projectId = screenEl.dataset.projectId;
    const myUserId = screenEl.dataset.userId;
    const myEmail = screenEl.dataset.userEmail || '';
    const myUsername = screenEl.dataset.username || (myEmail.split('@')[0] || 'You');

    const messagesEl = document.getElementById('messages');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const projectTitle = document.getElementById('projectTitle');
    const membersList = document.getElementById('membersList');
    const memberCountBadge = document.getElementById('memberCountBadge');
    const aiTypingIndicator = document.getElementById('aiTypingIndicator');

    // Studio UI Elements
    const codeStudio = document.getElementById('codeStudio');
    const toggleStudioBtn = document.getElementById('toggleStudioBtn');
    const closeStudioBtn = document.getElementById('closeStudioBtn');
    const fullscreenStudioBtn = document.getElementById('fullscreenStudioBtn');
    const studioCodeEditor = document.getElementById('studioCodeEditor');
    const editorGutter = document.getElementById('editorGutter');
    const codeLanguageSelect = document.getElementById('codeLanguageSelect');
    const currentFileName = document.getElementById('currentFileName');
    const studioFileTabs = document.getElementById('studioFileTabs');
    const studioRunCodeBtn = document.getElementById('studioRunCodeBtn');
    const studioLivePreviewBtn = document.getElementById('studioLivePreviewBtn');
    const studioCopyBtn = document.getElementById('studioCopyBtn');
    const copyBtnText = document.getElementById('copyBtnText');
    const studioDownloadBtn = document.getElementById('studioDownloadBtn');
    const studioShareBtn = document.getElementById('studioShareBtn');
    const exportZipBtn = document.getElementById('exportZipBtn');
    const exportAllZipBtn = document.getElementById('exportAllZipBtn');
    const importZipBtn = document.getElementById('importZipBtn');
    const zipFileInput = document.getElementById('zipFileInput');
    const quickZipChip = document.getElementById('quickZipChip');

    const previewIframe = document.getElementById('previewIframe');
    const previewReadyDot = document.getElementById('previewReadyDot');
    const terminalOutput = document.getElementById('terminalOutput');
    const terminalExecTime = document.getElementById('terminalExecTime');
    const terminalClearBtn = document.getElementById('terminalClearBtn');
    const terminalReRunBtn = document.getElementById('terminalReRunBtn');
    const artifactsList = document.getElementById('artifactsList');
    const artifactCount = document.getElementById('artifactCount');

    // Tab ID deduplication
    const clientId = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : ('c' + Date.now() + Math.random().toString(16).slice(2));

    let lastMessageId = 0;
    let eventSource = null;
    const renderedIds = new Set();
    let tempIdCounter = -1;

    // Session Artifacts State
    let artifacts = []; // [{ id, filename, language, code, timestamp, isWeb }]
    let activeArtifactId = null;

    const languageExtensions = {
        javascript: 'js',
        typescript: 'ts',
        jsx: 'jsx',
        tsx: 'tsx',
        html: 'html',
        css: 'css',
        python: 'py',
        php: 'php',
        java: 'java',
        c: 'c',
        cpp: 'cpp',
        rust: 'rs',
        go: 'go',
        golang: 'go',
        kotlin: 'kt',
        swift: 'swift',
        csharp: 'cs',
        cs: 'cs',
        ruby: 'rb',
        sql: 'sql',
        json: 'json'
    };

    const languageStarters = {
        javascript: '// app.js\nconsole.log("🚀 Welcome to Soen AI Workspace!");\n',
        html: '<!DOCTYPE html>\n<html>\n<head>\n  <meta charset="utf-8">\n  <title>Soen App</title>\n  <style>\n    body { font-family: -apple-system, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }\n    .card { background: #1e293b; padding: 32px; border-radius: 12px; border: 1px solid #334155; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.4); max-width: 420px; }\n    h1 { color: #38bdf8; margin-top: 0; }\n    button { background: #6366f1; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }\n  </style>\n</head>\n<body>\n  <div class="card">\n    <h1>⚡ Soen Live Preview</h1>\n    <p>Render HTML, CSS & JavaScript in real-time or export as ZIP!</p>\n    <button onclick="alert(\'Soen Preview Working!\')">Click Me</button>\n  </div>\n</body>\n</html>',
        python: '# main.py\ndef greet(name):\n    return f"Hello, {name}! Soen AI Python is ready."\n\nprint(greet("Developer"))\n',
        php: '<?php\n// api.php\necho json_encode(["status" => "success", "message" => "Hello from PHP API!"]);\n',
        java: 'public class Main {\n    public static void main(String[] args) {\n        System.out.println("Hello from Java!");\n    }\n}\n',
        c: '#include <stdio.h>\n\nint main() {\n    printf("Hello from C!\\n");\n    return 0;\n}\n',
        cpp: '#include <iostream>\n\nint main() {\n    std::cout << "Hello from C++!" << std::endl;\n    return 0;\n}\n',
        sql: '-- schema.sql\nSELECT * FROM projects;\n',
        json: '{\n  "name": "soen-project",\n  "version": "1.0.0",\n  "description": "Created with Soen AI Workspace"\n}\n'
    };

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatTime(dateStr) {
        const d = dateStr ? new Date(String(dateStr).replace(' ', 'T')) : new Date();
        if (isNaN(d.getTime())) return '';
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function initialsOf(label) {
        return (label || '?').trim().slice(0, 1).toUpperCase();
    }

    function updateGutter() {
        if (!studioCodeEditor || !editorGutter) return;
        const lines = (studioCodeEditor.value || '').split('\n').length;
        let gutterHtml = '';
        for (let i = 1; i <= Math.max(lines, 1); i++) {
            gutterHtml += `<div>${i}</div>`;
        }
        editorGutter.innerHTML = gutterHtml;
    }

    if (studioCodeEditor) {
        studioCodeEditor.addEventListener('input', () => {
            updateGutter();
            if (activeArtifactId) {
                const art = artifacts.find(a => a.id === activeArtifactId);
                if (art) {
                    art.code = studioCodeEditor.value;
                    if (art.isWeb) updateLivePreview(art.code);
                }
            }
        });

        studioCodeEditor.addEventListener('scroll', () => {
            if (editorGutter) editorGutter.scrollTop = studioCodeEditor.scrollTop;
        });

        studioCodeEditor.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = studioCodeEditor.selectionStart;
                const end = studioCodeEditor.selectionEnd;
                studioCodeEditor.value = studioCodeEditor.value.substring(0, start) + '    ' + studioCodeEditor.value.substring(end);
                studioCodeEditor.selectionStart = studioCodeEditor.selectionEnd = start + 4;
                updateGutter();
            }
        });
    }

    function normalizeLanguage(lang) {
        if (!lang) return 'javascript';
        lang = lang.toLowerCase().trim();
        if (lang === 'js' || lang === 'node') return 'javascript';
        if (lang === 'py') return 'python';
        if (lang === 'htm') return 'html';
        if (lang === 'c++') return 'cpp';
        if (languageExtensions[lang]) return lang;
        return 'javascript';
    }

    function createOrSelectArtifact(filename, language, code, shouldFocus = true) {
        language = normalizeLanguage(language);
        const isWeb = language === 'html' || code.includes('<!DOCTYPE') || code.includes('<html');
        
        if (!filename) {
            const ext = languageExtensions[language] || 'txt';
            filename = `snippet_${artifacts.length + 1}.${ext}`;
        }

        // Check if artifact with identical filename already exists; update it if so
        let existing = artifacts.find(a => a.filename.toLowerCase() === filename.toLowerCase());
        if (existing) {
            existing.code = (code || '').trim();
            existing.language = language;
            existing.isWeb = isWeb;
            existing.timestamp = new Date();
            renderArtifactsList();
            renderFileTabs();
            if (shouldFocus) {
                loadArtifactIntoStudio(existing.id);
                openStudio();
            }
            return existing;
        }

        const newArtifact = {
            id: 'art_' + Date.now() + '_' + Math.random().toString(36).substring(2, 6),
            filename,
            language,
            code: (code || '').trim(),
            timestamp: new Date(),
            isWeb
        };

        artifacts.unshift(newArtifact);
        renderArtifactsList();
        renderFileTabs();

        if (shouldFocus) {
            loadArtifactIntoStudio(newArtifact.id);
            openStudio();
        }

        return newArtifact;
    }

    function loadArtifactIntoStudio(artifactId) {
        const art = artifacts.find(a => a.id === artifactId);
        if (!art || !studioCodeEditor) return;

        activeArtifactId = art.id;
        studioCodeEditor.value = art.code;
        if (codeLanguageSelect) codeLanguageSelect.value = art.language;
        if (currentFileName) currentFileName.textContent = art.filename;
        updateGutter();

        if (art.isWeb || art.language === 'html') {
            if (studioLivePreviewBtn) studioLivePreviewBtn.style.display = 'inline-flex';
            if (previewReadyDot) previewReadyDot.style.display = 'inline-block';
            updateLivePreview(art.code);
        } else {
            if (studioLivePreviewBtn) studioLivePreviewBtn.style.display = 'none';
            if (previewReadyDot) previewReadyDot.style.display = 'none';
        }

        renderFileTabs();
    }

    function renderFileTabs() {
        if (!studioFileTabs) return;
        studioFileTabs.innerHTML = '';
        const recent = artifacts.slice(0, 7);
        recent.forEach(art => {
            const tab = document.createElement('div');
            tab.className = 'studio-file-tab' + (art.id === activeArtifactId ? ' active' : '');
            const icon = art.isWeb ? '🌐' : (art.language === 'python' ? '🐍' : (art.language === 'json' ? '📦' : '📄'));
            tab.innerHTML = `<span class="file-icon">${icon}</span><span class="file-name">${escapeHtml(art.filename)}</span>`;
            tab.addEventListener('click', () => loadArtifactIntoStudio(art.id));
            studioFileTabs.appendChild(tab);
        });
    }

    function renderArtifactsList() {
        if (artifactCount) artifactCount.textContent = artifacts.length;
        if (!artifactsList) return;

        if (artifacts.length === 0) {
            artifactsList.innerHTML = `
                <div class="empty-state-card">
                    <div class="empty-icon">📁</div>
                    <div class="empty-title">No Artifacts Yet</div>
                    <div class="empty-sub">Ask <strong>@ai</strong> in chat to generate full code or import a ZIP archive.</div>
                </div>`;
            return;
        }

        artifactsList.innerHTML = '';
        artifacts.forEach(art => {
            const item = document.createElement('div');
            item.className = 'artifact-history-item';
            const icon = art.isWeb ? '🌐' : (art.language === 'python' ? '🐍' : (art.language === 'json' ? '📦' : '📄'));
            const lines = (art.code || '').split('\n').length;
            item.innerHTML = `
                <div class="artifact-history-info">
                    <span class="artifact-history-icon">${icon}</span>
                    <div>
                        <div class="artifact-history-title">${escapeHtml(art.filename)}</div>
                        <div class="artifact-history-meta">${escapeHtml(art.language.toUpperCase())} · ${lines} lines · ${formatTime(art.timestamp.toISOString())}</div>
                    </div>
                </div>
                <div class="artifact-item-actions">
                    <button class="artifact-btn btn-studio-open">Open</button>
                </div>
            `;
            item.querySelector('.btn-studio-open').addEventListener('click', (e) => {
                e.stopPropagation();
                loadArtifactIntoStudio(art.id);
                switchStudioTab('code');
            });
            item.addEventListener('click', () => {
                loadArtifactIntoStudio(art.id);
                switchStudioTab('code');
            });
            artifactsList.appendChild(item);
        });
    }

    // -------------------------------------------------------------------------
    // ZIP EXPORT & IMPORT ENGINE
    // -------------------------------------------------------------------------

    async function exportProjectAsZip() {
        const projTitle = (projectTitle ? projectTitle.textContent : 'project').trim().replace(/[^a-zA-Z0-9_-]/g, '_');
        const zipName = `${projTitle}_soen.zip`;

        if (window.JSZip) {
            const zip = new JSZip();

            // Collect all current artifacts or starter code
            if (artifacts.length === 0) {
                zip.file('index.html', studioCodeEditor ? studioCodeEditor.value : languageStarters.html);
                zip.file('README.md', `# ${projTitle}\n\nExported from Soen AI Workspace.`);
            } else {
                artifacts.forEach(art => {
                    zip.file(art.filename, art.code);
                });
                if (!artifacts.some(a => a.filename.toLowerCase() === 'readme.md')) {
                    zip.file('README.md', `# ${projTitle}\n\nGenerated with Soen AI Workspace.\n\n## Files\n${artifacts.map(a => `- \`${a.filename}\` (${a.language})`).join('\n')}`);
                }
            }

            try {
                const content = await zip.generateAsync({ type: 'blob' });
                if (window.saveAs) {
                    saveAs(content, zipName);
                } else {
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(content);
                    link.download = zipName;
                    link.click();
                    URL.revokeObjectURL(link.href);
                }
                return;
            } catch (err) {
                console.warn('JSZip client generation failed, falling back to server:', err);
            }
        }

        // Server-side fallback
        window.location.href = `api/projects/export_zip.php?id=${projectId}`;
    }

    async function importZipFile(file) {
        if (!file || !window.JSZip) return;

        try {
            const zip = await JSZip.loadAsync(file);
            let importedCount = 0;
            let firstImported = null;

            for (const [filename, zipEntry] of Object.entries(zip.files)) {
                if (zipEntry.dir || filename.startsWith('__MACOSX') || filename.startsWith('.')) continue;

                const text = await zipEntry.async('text');
                const ext = filename.split('.').pop().toLowerCase();
                const lang = Object.keys(languageExtensions).find(k => languageExtensions[k] === ext) || 'javascript';
                
                const art = createOrSelectArtifact(filename, lang, text, false);
                if (!firstImported) firstImported = art;
                importedCount++;
            }

            if (firstImported) {
                loadArtifactIntoStudio(firstImported.id);
                switchStudioTab('code');
                openStudio();
                alert(`✅ Successfully imported ${importedCount} files from "${file.name}"!`);
            }
        } catch (e) {
            alert('Could not parse ZIP file: ' + e.message);
        }
    }

    if (exportZipBtn) exportZipBtn.addEventListener('click', exportProjectAsZip);
    if (exportAllZipBtn) exportAllZipBtn.addEventListener('click', exportProjectAsZip);
    if (quickZipChip) quickZipChip.addEventListener('click', exportProjectAsZip);

    if (importZipBtn && zipFileInput) {
        importZipBtn.addEventListener('click', () => zipFileInput.click());
        zipFileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) importZipFile(file);
            zipFileInput.value = '';
        });
    }

    // -------------------------------------------------------------------------
    // LIVE WEB PREVIEW ENGINE
    // -------------------------------------------------------------------------

    function updateLivePreview(htmlCode) {
        if (!previewIframe || !htmlCode) return;
        
        let fullDoc = htmlCode;
        if (!htmlCode.includes('<html') && !htmlCode.includes('<!DOCTYPE')) {
            // Find if there is external css/js artifact in the session
            const cssArt = artifacts.find(a => a.language === 'css');
            const jsArt = artifacts.find(a => a.language === 'javascript' && !a.code.includes('import '));

            fullDoc = `<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 20px; }</style>
  ${cssArt ? `<style>${cssArt.code}</style>` : ''}
</head>
<body>
${htmlCode}
${jsArt ? `<script>${jsArt.code}</script>` : ''}
</body>
</html>`;
        }

        previewIframe.srcdoc = fullDoc;
    }

    // Preview Toolbar
    document.querySelectorAll('.viewport-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.viewport-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            if (previewIframe) previewIframe.style.width = btn.dataset.width;
        });
    });

    const refreshPreviewBtn = document.getElementById('refreshPreviewBtn');
    if (refreshPreviewBtn) {
        refreshPreviewBtn.addEventListener('click', () => {
            if (studioCodeEditor) updateLivePreview(studioCodeEditor.value);
        });
    }

    const openPreviewNewTabBtn = document.getElementById('openPreviewNewTabBtn');
    if (openPreviewNewTabBtn) {
        openPreviewNewTabBtn.addEventListener('click', () => {
            if (!studioCodeEditor) return;
            const blob = new Blob([studioCodeEditor.value], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            window.open(url, '_blank');
        });
    }

    // -------------------------------------------------------------------------
    // CODE RUNNER
    // -------------------------------------------------------------------------

    async function executeCode(code, language) {
        if (!code || !code.trim()) return;

        switchStudioTab('terminal');
        if (terminalOutput) {
            terminalOutput.textContent = '⏳ Executing code on sandbox…\n';
            terminalOutput.classList.remove('error');
        }
        if (terminalExecTime) terminalExecTime.textContent = '';

        const startTime = performance.now();
        const endpoint = language === 'java' ? 'api/code/run_java.php' : 'api/code/run.php';

        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code, language }),
            });
            const duration = Math.round(performance.now() - startTime);
            if (terminalExecTime) terminalExecTime.textContent = `${duration}ms`;

            const data = await res.json();
            if (terminalOutput) {
                terminalOutput.textContent = data.output || (data.message || '(Program finished with no output)');
                if (!data.success) terminalOutput.classList.add('error');
            }
        } catch (e) {
            if (terminalOutput) {
                terminalOutput.textContent = '❌ Failed to reach code runner: ' + e.message;
                terminalOutput.classList.add('error');
            }
        }
    }

    if (studioRunCodeBtn) {
        studioRunCodeBtn.addEventListener('click', () => {
            if (!studioCodeEditor || !codeLanguageSelect) return;
            const code = studioCodeEditor.value;
            const lang = codeLanguageSelect.value;
            if (lang === 'html') {
                switchStudioTab('preview');
                updateLivePreview(code);
            } else {
                executeCode(code, lang);
            }
        });
    }

    if (studioLivePreviewBtn) {
        studioLivePreviewBtn.addEventListener('click', () => {
            switchStudioTab('preview');
            if (studioCodeEditor) updateLivePreview(studioCodeEditor.value);
        });
    }

    if (terminalClearBtn) {
        terminalClearBtn.addEventListener('click', () => {
            if (terminalOutput) {
                terminalOutput.textContent = 'Terminal cleared.';
                terminalOutput.classList.remove('error');
            }
            if (terminalExecTime) terminalExecTime.textContent = '';
        });
    }

    if (terminalReRunBtn) {
        terminalReRunBtn.addEventListener('click', () => {
            if (studioCodeEditor && codeLanguageSelect) {
                executeCode(studioCodeEditor.value, codeLanguageSelect.value);
            }
        });
    }

    if (studioCopyBtn) {
        studioCopyBtn.addEventListener('click', async () => {
            if (!studioCodeEditor) return;
            const code = studioCodeEditor.value;
            if (!code) return;
            try {
                await navigator.clipboard.writeText(code);
                if (copyBtnText) copyBtnText.textContent = 'Copied!';
                studioCopyBtn.style.borderColor = 'var(--ai-accent)';
                setTimeout(() => {
                    if (copyBtnText) copyBtnText.textContent = 'Copy';
                    studioCopyBtn.style.borderColor = '';
                }, 2000);
            } catch (e) {
                alert('Could not copy code.');
            }
        });
    }

    if (studioDownloadBtn) {
        studioDownloadBtn.addEventListener('click', () => {
            if (!studioCodeEditor) return;
            const code = studioCodeEditor.value;
            if (!code) return;
            const filename = (currentFileName ? currentFileName.textContent : 'snippet.txt') || 'snippet.txt';
            const blob = new Blob([code], { type: 'text/plain;charset=utf-8' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.click();
            URL.revokeObjectURL(link.href);
        });
    }

    if (studioShareBtn) {
        studioShareBtn.addEventListener('click', () => {
            if (!studioCodeEditor || !messageInput || !codeLanguageSelect) return;
            const code = studioCodeEditor.value.trim();
            if (!code) return;
            const lang = codeLanguageSelect.value;
            const name = (currentFileName ? currentFileName.textContent : 'snippet.txt') || 'snippet.txt';
            messageInput.value = `Here is my \`${name}\` code:\n\n\`\`\`${lang}\n${code}\n\`\`\``;
            sendMessage();
        });
    }

    if (codeLanguageSelect) {
        codeLanguageSelect.addEventListener('change', () => {
            const lang = codeLanguageSelect.value;
            const ext = languageExtensions[lang] || 'txt';
            if (currentFileName) currentFileName.textContent = `snippet.${ext}`;
            if (studioCodeEditor && !studioCodeEditor.value.trim()) {
                studioCodeEditor.value = languageStarters[lang] || '';
                updateGutter();
            }
            if (studioLivePreviewBtn) {
                studioLivePreviewBtn.style.display = lang === 'html' ? 'inline-flex' : 'none';
            }
        });
    }

    function switchStudioTab(tabName) {
        document.querySelectorAll('.studio-tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabName);
        });

        document.querySelectorAll('.studio-content-pane').forEach(pane => {
            pane.classList.remove('active');
        });

        const targetPane = document.getElementById(`pane${tabName.charAt(0).toUpperCase() + tabName.slice(1)}`);
        if (targetPane) targetPane.classList.add('active');

        if (tabName === 'preview' && studioCodeEditor) {
            updateLivePreview(studioCodeEditor.value);
        }
    }

    document.querySelectorAll('.studio-tab-btn').forEach(btn => {
        btn.addEventListener('click', () => switchStudioTab(btn.dataset.tab));
    });

    function openStudio() {
        if (!codeStudio) return;
        codeStudio.classList.remove('collapsed');
        if (toggleStudioBtn) toggleStudioBtn.classList.add('active');
    }

    function closeStudio() {
        if (!codeStudio) return;
        codeStudio.classList.add('collapsed');
        if (toggleStudioBtn) toggleStudioBtn.classList.remove('active');
    }

    if (toggleStudioBtn) {
        toggleStudioBtn.addEventListener('click', () => {
            if (!codeStudio) return;
            if (codeStudio.classList.contains('collapsed')) {
                openStudio();
            } else {
                closeStudio();
            }
        });
    }

    if (closeStudioBtn) closeStudioBtn.addEventListener('click', closeStudio);

    if (fullscreenStudioBtn) {
        fullscreenStudioBtn.addEventListener('click', () => {
            if (codeStudio) codeStudio.classList.toggle('fullscreen');
        });
    }

    // -------------------------------------------------------------------------
    // INTELLIGENT CODE & MULTI-FILE EXTRACTION
    // -------------------------------------------------------------------------

    function processCodeBlocks(rawText) {
        const codeBlockRegex = /```([a-zA-Z0-9_-]*)\n([\s\S]*?)```/g;
        const extracted = [];

        const cleanText = rawText.replace(codeBlockRegex, (fullMatch, lang, code) => {
            const cleanLang = normalizeLanguage(lang);
            const lines = (code || '').trim().split('\n');
            const firstLine = (lines[0] || '').trim();
            const ext = languageExtensions[cleanLang] || 'txt';
            
            // Extract filename from comment or path (e.g. // index.html, /* style.css */, # app.py)
            let filename = '';
            const fileMatch = firstLine.match(/^(?:\/\/|\/\*|#)\s*([a-zA-Z0-9_.\-\/]+\.[a-zA-Z0-9]+)/);
            if (fileMatch && fileMatch[1]) {
                filename = fileMatch[1];
            } else {
                filename = `code_${artifacts.length + extracted.length + 1}.${ext}`;
            }

            extracted.push({
                language: cleanLang,
                code: (code || '').trim(),
                filename: filename
            });

            return `\n[[ARTIFACT_CARD_${extracted.length - 1}]]\n`;
        });

        return { cleanText, extracted };
    }

    function renderMessage(row) {
        if (!row || !messagesEl) return;
        if (renderedIds.has(row.id)) return;
        renderedIds.add(row.id);

        if (Number(row.is_ai) === 1 && aiTypingIndicator) {
            aiTypingIndicator.classList.remove('show');
        }

        const isMine = String(row.user_id) === String(myUserId);
        const isAi = Number(row.is_ai) === 1;

        const wrap = document.createElement('div');
        wrap.className = 'msg-row ' + (isAi ? 'ai' : (isMine ? 'mine' : 'other'));

        const bubbleCol = document.createElement('div');
        bubbleCol.className = 'msg-col';

        // Sender label above the bubble (Matches Soen Video)
        const meta = document.createElement('div');
        meta.className = 'msg-meta';
        const senderLabel = isAi ? 'AI' : (row.sender_label || (isMine ? (myUsername || myUserEmail || 'You') : 'User'));
        meta.innerHTML = `<span class="sender-name">${escapeHtml(senderLabel)}</span>`;
        bubbleCol.appendChild(meta);

        const bubble = document.createElement('div');
        bubble.className = 'msg ' + (isAi ? 'ai' : (isMine ? 'mine' : 'other'));

        const msgText = row.message || '';

        if (row.attachment_type === 'image' && row.attachment_url) {
            const img = document.createElement('img');
            img.src = row.attachment_url;
            img.className = 'msg-image';
            img.alt = msgText || 'Attached image';
            img.addEventListener('click', () => window.open(row.attachment_url, '_blank'));
            bubble.appendChild(img);
            if (msgText && msgText !== '📷 Photo') {
                const cap = document.createElement('div');
                cap.style.marginTop = '6px';
                cap.textContent = msgText;
                bubble.appendChild(cap);
            }
        } else if (row.attachment_type === 'audio' && row.attachment_url) {
            const audio = document.createElement('audio');
            audio.src = row.attachment_url;
            audio.controls = true;
            audio.className = 'msg-audio';
            bubble.appendChild(audio);
        } else if (row.attachment_type === 'file' && row.attachment_url) {
            const link = document.createElement('a');
            link.href = row.attachment_url;
            link.target = '_blank';
            link.className = 'msg-file-link';
            link.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg> ${escapeHtml(msgText.replace(/^📎 /, ''))}`;
            bubble.appendChild(link);
        } else if (msgText && /```/.test(msgText)) {
            const { cleanText, extracted } = processCodeBlocks(msgText);
            bubble.innerHTML = window.marked ? marked.parse(cleanText) : escapeHtml(cleanText);

            extracted.forEach((item, idx) => {
                const artObj = createOrSelectArtifact(item.filename, item.language, item.code, isAi);
                const placeholder = `[[ARTIFACT_CARD_${idx}]]`;
                
                const cardEl = document.createElement('div');
                cardEl.className = 'artifact-card';
                const lines = item.code.split('\n').length;
                const snippetPreview = item.code.split('\n').slice(0, 3).join('\n');
                const icon = artObj.isWeb ? '🌐' : (item.language === 'python' ? '🐍' : (item.language === 'java' ? '☕' : (item.language === 'cpp' || item.language === 'c' ? '⚡' : (item.language === 'json' ? '📦' : '📄'))));

                cardEl.innerHTML = `
                    <div class="artifact-card-head">
                        <div class="artifact-title-box">
                            <span class="artifact-icon">${icon}</span>
                            <span class="artifact-filename">${escapeHtml(artObj.filename)}</span>
                            <span class="artifact-lang-tag">${escapeHtml(item.language)}</span>
                        </div>
                    </div>
                    <div class="artifact-snippet-preview">${escapeHtml(snippetPreview)}</div>
                    <div class="artifact-card-footer">
                        <span class="artifact-meta-text">${lines} lines of code</span>
                        <div class="artifact-actions">
                            <button class="artifact-btn btn-studio-open" data-art-id="${artObj.id}">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" x2="14" y1="3" y2="10"/><line x1="3" x2="10" y1="21" y2="14"/></svg>
                                Open in Editor
                            </button>
                            ${artObj.isWeb ? `
                            <button class="artifact-btn btn-preview-open" data-art-id="${artObj.id}">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                Preview
                            </button>` : `
                            <button class="artifact-btn btn-code-run" data-art-id="${artObj.id}">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                Run
                            </button>`}
                        </div>
                    </div>
                `;

                const studioBtn = cardEl.querySelector('.btn-studio-open');
                if (studioBtn) {
                    studioBtn.addEventListener('click', () => {
                        loadArtifactIntoStudio(artObj.id);
                        switchStudioTab('code');
                        openStudio();
                    });
                }

                const previewBtn = cardEl.querySelector('.btn-preview-open');
                if (previewBtn) {
                    previewBtn.addEventListener('click', () => {
                        loadArtifactIntoStudio(artObj.id);
                        switchStudioTab('preview');
                        openStudio();
                    });
                }

                const runBtn = cardEl.querySelector('.btn-code-run');
                if (runBtn) {
                    runBtn.addEventListener('click', () => {
                        loadArtifactIntoStudio(artObj.id);
                        executeCode(artObj.code, artObj.language);
                        openStudio();
                    });
                }

                const pTags = bubble.querySelectorAll('p');
                pTags.forEach(p => {
                    if (p.textContent.includes(placeholder)) {
                        p.replaceWith(cardEl);
                    }
                });
            });

            // If an HTML/web file was extracted, auto-preview it
            const webArt = extracted.find(e => e.language === 'html');
            if (webArt && isAi) {
                updateLivePreview(webArt.code);
            }
        } else {
            bubble.innerHTML = window.marked ? marked.parse(msgText) : escapeHtml(msgText);
        }

        bubbleCol.appendChild(bubble);
        wrap.appendChild(bubbleCol);
        messagesEl.appendChild(wrap);
        messagesEl.scrollTop = messagesEl.scrollHeight;

        if (row.id > lastMessageId) lastMessageId = row.id;
    }

    async function loadProjectInfo() {
        try {
            const res = await fetch(`api/projects/get.php?id=${projectId}`);
            const data = await res.json();
            if (!data.success) {
                alert(data.message || 'Could not load project');
                window.location.href = 'home.php';
                return;
            }
            if (projectTitle) projectTitle.textContent = data.project.name;
            document.title = (data.project.name || 'Soen Workspace') + ' - Soen AI Workspace';
            if (memberCountBadge && data.project.members) {
                memberCountBadge.textContent = `${data.project.members.length} member${data.project.members.length === 1 ? '' : 's'}`;
            }

            if (membersList && data.project.members) {
                membersList.innerHTML = '';
                data.project.members.forEach(m => {
                    const item = document.createElement('div');
                    item.className = 'member-item-card';
                    const name = m.username || m.email || 'Member';
                    item.innerHTML = `
                        <div class="member-info-left">
                            <div class="member-avatar-box">${escapeHtml(name.slice(0, 1).toUpperCase())}</div>
                            <div>
                                <div class="member-name">${escapeHtml(name)}</div>
                                <div class="member-email">${escapeHtml(m.email || '')}</div>
                            </div>
                        </div>
                        <span class="member-role-badge">${m.id == myUserId ? 'You' : 'Member'}</span>
                    `;
                    membersList.appendChild(item);
                });
            }
        } catch (e) {
            console.error('Error loading project info:', e);
        }
    }

    async function loadInitialMessages() {
        try {
            const res = await fetch(`api/messages/list.php?project_id=${projectId}&after_id=0`);
            const data = await res.json();
            if (data.success && data.messages) {
                data.messages.forEach(renderMessage);
            }
        } catch (e) {
            console.error('Error loading initial messages:', e);
        }
        startStream(lastMessageId);
    }

    function startStream(afterId) {
        if (eventSource) eventSource.close();
        eventSource = new EventSource(`api/messages/stream.php?project_id=${projectId}&after_id=${afterId}&client_id=${encodeURIComponent(clientId)}`);

        eventSource.addEventListener('message', (e) => {
            try {
                const row = JSON.parse(e.data);
                renderMessage(row);
            } catch (err) {
                console.error(err);
            }
        });

        eventSource.onerror = () => {
            eventSource.close();
            setTimeout(() => startStream(lastMessageId), 1000);
        };
    }

    function stripos(haystack, needle) {
        return (haystack + '').toLowerCase().indexOf((needle + '').toLowerCase()) !== -1;
    }

    async function sendMessage(overrideText = null) {
        if (!messageInput) return;
        const text = (overrideText !== null ? overrideText : messageInput.value).trim();
        if (!text) return;
        messageInput.value = '';
        messageInput.style.height = 'auto';

        const isAiQuery = stripos(text, '@ai');

        renderMessage({
            id: tempIdCounter--,
            user_id: myUserId,
            sender_label: myUsername || myEmail,
            message: text,
            is_ai: 0,
            created_at: null,
        });

        if (isAiQuery && aiTypingIndicator) {
            aiTypingIndicator.classList.add('show');
            if (messagesEl) messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        try {
            const res = await fetch('api/messages/send.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ project_id: projectId, message: text, client_id: clientId }),
            });
            const data = await res.json().catch(() => ({}));
            
            if (isAiQuery) {
                if (aiTypingIndicator) aiTypingIndicator.classList.remove('show');
                if (data && data.ai_reply) {
                    renderMessage({
                        id: tempIdCounter--,
                        user_id: null,
                        sender_label: 'AI',
                        message: data.ai_reply,
                        is_ai: 1,
                        created_at: new Date().toISOString()
                    });
                }
            }
        } catch (e) {
            console.error('Send error:', e);
            if (aiTypingIndicator) aiTypingIndicator.classList.remove('show');
        }
    }

    if (sendBtn) sendBtn.addEventListener('click', () => sendMessage());

    if (messageInput) {
        messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        messageInput.addEventListener('input', () => {
            messageInput.style.height = 'auto';
            messageInput.style.height = Math.min(messageInput.scrollHeight, 120) + 'px';
        });
    }

    document.querySelectorAll('.quick-chip').forEach(chip => {
        if (chip.id === 'quickZipChip') return;
        chip.addEventListener('click', () => {
            const prompt = chip.dataset.prompt;
            if (messageInput) {
                messageInput.value = prompt;
                messageInput.focus();
            }
            sendMessage();
        });
    });

    const backBtn = document.getElementById('backBtn');
    if (backBtn) {
        backBtn.addEventListener('click', () => {
            window.location.href = 'home.php';
        });
    }

    // Modal Add Member
    const addMemberModal = document.getElementById('addMemberModal');
    const addMemberError = document.getElementById('addMemberError');

    function showAddMemberModal() {
        if (!addMemberModal) return;
        if (addMemberError) addMemberError.classList.remove('show');
        const emailInput = document.getElementById('memberEmail');
        if (emailInput) emailInput.value = '';
        addMemberModal.classList.add('show');
        if (emailInput) emailInput.focus();
    }

    const inviteHeaderBtn = document.getElementById('inviteHeaderBtn');
    if (inviteHeaderBtn) inviteHeaderBtn.addEventListener('click', showAddMemberModal);

    const openAddMemberModalBtn = document.getElementById('openAddMemberModalBtn');
    if (openAddMemberModalBtn) openAddMemberModalBtn.addEventListener('click', showAddMemberModal);

    const cancelAddMember = document.getElementById('cancelAddMember');
    if (cancelAddMember) cancelAddMember.addEventListener('click', () => addMemberModal && addMemberModal.classList.remove('show'));

    const closeAddMemberModal = document.getElementById('closeAddMemberModal');
    if (closeAddMemberModal) closeAddMemberModal.addEventListener('click', () => addMemberModal && addMemberModal.classList.remove('show'));

    const confirmAddMember = document.getElementById('confirmAddMember');
    if (confirmAddMember) {
        confirmAddMember.addEventListener('click', async () => {
            const emailInput = document.getElementById('memberEmail');
            const email = emailInput ? emailInput.value.trim() : '';
            if (!email) return;

            try {
                const res = await fetch('api/projects/add_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ project_id: projectId, email }),
                });
                const data = await res.json();
                if (!data.success) {
                    if (addMemberError) {
                        addMemberError.textContent = data.message || 'Could not add user';
                        addMemberError.classList.add('show');
                    }
                    return;
                }
                if (addMemberModal) addMemberModal.classList.remove('show');
                loadProjectInfo();
            } catch (e) {
                if (addMemberError) {
                    addMemberError.textContent = 'Server error: ' + e.message;
                    addMemberError.classList.add('show');
                }
            }
        });
    }

    const openCodeRunnerBtn = document.getElementById('openCodeRunnerBtn');
    if (openCodeRunnerBtn) {
        openCodeRunnerBtn.addEventListener('click', () => {
            openStudio();
            switchStudioTab('code');
            const lang = codeLanguageSelect ? codeLanguageSelect.value : 'javascript';
            createOrSelectArtifact(`snippet_${artifacts.length + 1}.${languageExtensions[lang] || 'js'}`, lang, languageStarters[lang] || '');
        });
    }

    // Emoji Picker
    const EMOJI_SET = ['😀','🚀','🔥','💻','💡','✅','⚡','🎉','👍','🙌','✨','❤️','🐛','⏱️','🎨','👀','🤔','🤖','📦','🌐'];
    const emojiPicker = document.getElementById('emojiPicker');
    const emojiBtn = document.getElementById('emojiBtn');

    if (emojiPicker) {
        EMOJI_SET.forEach(e => {
            const span = document.createElement('span');
            span.textContent = e;
            span.addEventListener('click', () => {
                if (messageInput) {
                    messageInput.value += e;
                    messageInput.focus();
                }
            });
            emojiPicker.appendChild(span);
        });
    }

    if (emojiBtn && emojiPicker) {
        emojiBtn.addEventListener('click', () => emojiPicker.classList.toggle('show'));
        document.addEventListener('click', (e) => {
            if (!emojiPicker.contains(e.target) && e.target !== emojiBtn) emojiPicker.classList.remove('show');
        });
    }

    // File Input
    const fileInput = document.getElementById('fileInput');
    const attachBtn = document.getElementById('attachBtn');
    if (attachBtn && fileInput) attachBtn.addEventListener('click', () => fileInput.click());

    if (fileInput) {
        fileInput.addEventListener('change', async () => {
            const file = fileInput.files[0];
            fileInput.value = '';
            if (!file) return;

            const form = new FormData();
            form.append('file', file);
            form.append('project_id', projectId);
            form.append('client_id', clientId);

            try {
                const res = await fetch('api/messages/send_attachment.php', { method: 'POST', body: form });
                const data = await res.json();
                if (!data.success) { alert(data.message || 'Could not send attachment'); return; }
                renderMessage({
                    id: data.id, user_id: myUserId, sender_label: myUsername || myEmail,
                    message: data.attachment_type === 'image' ? '📷 Photo' : ('📎 ' + data.file_name),
                    attachment_url: data.attachment_url, attachment_type: data.attachment_type,
                    is_ai: 0, created_at: null,
                });
            } catch (e) {
                alert('Could not upload attachment: ' + e.message);
            }
        });
    }

    // Voice Recorder
    const micBtn = document.getElementById('micBtn');
    const recordingBar = document.getElementById('recordingBar');
    const recTimer = document.getElementById('recTimer');
    let mediaRecorder = null;
    let recordedChunks = [];
    let recordingStartedAt = 0;
    let recordingTimerHandle = null;

    function updateRecTimer() {
        if (!recTimer) return;
        const secs = Math.floor((Date.now() - recordingStartedAt) / 1000);
        const m = Math.floor(secs / 60);
        const s = String(secs % 60).padStart(2, '0');
        recTimer.textContent = `${m}:${s}`;
    }

    async function startRecording() {
        if (mediaRecorder) return;
        if (!navigator.mediaDevices || !window.MediaRecorder) {
            alert('Voice recording is not supported in this browser.');
            return;
        }
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            recordedChunks = [];
            mediaRecorder = new MediaRecorder(stream);
            mediaRecorder.ondataavailable = (e) => { if (e.data.size > 0) recordedChunks.push(e.data); };
            mediaRecorder.start();
            recordingStartedAt = Date.now();
            if (recordingBar) recordingBar.classList.add('show');
            if (micBtn) micBtn.classList.add('recording');
            recordingTimerHandle = setInterval(updateRecTimer, 250);
        } catch (e) {
            alert('Microphone access was denied or is unavailable.');
        }
    }

    function stopRecordingTracks() {
        if (mediaRecorder && mediaRecorder.stream) {
            mediaRecorder.stream.getTracks().forEach(t => t.stop());
        }
        clearInterval(recordingTimerHandle);
        if (recordingBar) recordingBar.classList.remove('show');
        if (micBtn) micBtn.classList.remove('recording');
    }

    async function finishRecording(send) {
        if (!mediaRecorder) return;
        const recorder = mediaRecorder;
        mediaRecorder = null;

        const blob = await new Promise((resolve) => {
            recorder.addEventListener('stop', () => resolve(new Blob(recordedChunks, { type: recorder.mimeType || 'audio/webm' })), { once: true });
            recorder.stop();
        });
        stopRecordingTracks();
        if (!send || blob.size === 0) return;

        const form = new FormData();
        form.append('voice', blob, 'voice.webm');
        form.append('project_id', projectId);
        form.append('client_id', clientId);

        try {
            const res = await fetch('api/messages/send_voice.php', { method: 'POST', body: form });
            const data = await res.json();
            if (!data.success) { alert(data.message || 'Could not send voice message'); return; }
            renderMessage({
                id: data.id, user_id: myUserId, sender_label: myUsername || myEmail,
                message: '🎤 Voice message', attachment_url: data.attachment_url, attachment_type: 'audio',
                is_ai: 0, created_at: null,
            });
        } catch (e) {
            alert('Could not upload voice message: ' + e.message);
        }
    }

    if (micBtn) micBtn.addEventListener('click', startRecording);
    const stopRecordingBtn = document.getElementById('stopRecording');
    if (stopRecordingBtn) stopRecordingBtn.addEventListener('click', () => finishRecording(true));
    const cancelRecordingBtn = document.getElementById('cancelRecording');
    if (cancelRecordingBtn) cancelRecordingBtn.addEventListener('click', () => finishRecording(false));

    // Resizable Split Pane
    const chatPanel = document.getElementById('chatPanel');
    const paneDivider = document.getElementById('paneDivider');
    const PANE_WIDTH_KEY = 'aichatphp-chat-pane-width-v2';

    function clampPaneWidth(px) {
        const min = 320;
        const max = Math.round(window.innerWidth * 0.75);
        return Math.min(Math.max(px, min), max);
    }

    const savedWidth = parseInt(localStorage.getItem(PANE_WIDTH_KEY), 10);
    if (savedWidth && window.innerWidth > 900 && chatPanel) {
        chatPanel.style.width = clampPaneWidth(savedWidth) + 'px';
    }

    if (paneDivider && chatPanel) {
        paneDivider.addEventListener('mousedown', (e) => {
            e.preventDefault();
            paneDivider.classList.add('dragging');
            document.body.classList.add('pane-resizing');

            const onMove = (moveEvent) => {
                const rect = chatPanel.getBoundingClientRect();
                const newWidth = clampPaneWidth(moveEvent.clientX - rect.left);
                chatPanel.style.width = newWidth + 'px';
            };
            const onUp = () => {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                paneDivider.classList.remove('dragging');
                document.body.classList.remove('pane-resizing');
                localStorage.setItem(PANE_WIDTH_KEY, parseInt(chatPanel.style.width, 10));
            };
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });
    }

    // Initialize default files in workspace
    createOrSelectArtifact('app.js', 'javascript', languageStarters.javascript, false);
    createOrSelectArtifact('index.html', 'html', languageStarters.html, false);
    updateGutter();
    loadProjectInfo();
    loadInitialMessages();
});