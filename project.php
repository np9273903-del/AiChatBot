<?php
require_once __DIR__ . '/includes/auth_check.php';
$user = require_auth_page();

$projectId = intval($_GET['id'] ?? 0);
if (!$projectId) {
    header('Location: home.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Soen AI Workspace - AIChatPHP</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:ital,wght@0,400;0,500;0,600;1,400&display=swap">
<link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
<!-- Highlight.js for rich syntax highlighting -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark-reasonable.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<!-- Marked.js for markdown rendering -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/marked/9.1.6/marked.min.js"></script>
<!-- JSZip & FileSaver for 1-click ZIP Project Export & Import -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
</head>
<body class="workspace-body">

<div class="project-screen" id="projectScreen" data-project-id="<?= $projectId ?>" data-user-id="<?= $user['id'] ?>" data-user-email="<?= htmlspecialchars($user['email']) ?>" data-username="<?= htmlspecialchars($user['username']) ?>">

    <!-- LEFT / MAIN: AI CHAT & COLLABORATION FEED -->
    <div class="chat-panel" id="chatPanel">
        <div class="chat-header">
            <div class="header-left">
                <button class="icon-btn header-btn" id="backBtn" title="Back to Projects">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <div class="header-title-box">
                    <div class="project-badge">
                        <span class="pulse-dot"></span>
                        <h2 id="projectTitle">Loading project…</h2>
                    </div>
                    <div class="header-meta">
                        <span class="ai-status-pill">✦ Soen AI Active</span>
                        <span class="meta-dot">·</span>
                        <span id="memberCountBadge" class="members-badge">1 member</span>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <button class="btn small secondary" id="inviteHeaderBtn" title="Invite Collaborators">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                    <span>Invite</span>
                </button>
                <button class="btn small studio-toggle-btn" id="toggleStudioBtn" title="Toggle Code Studio">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M15 3v18"/></svg>
                    <span id="toggleStudioText">Studio</span>
                </button>
            </div>
        </div>

        <!-- Chat messages container -->
        <div class="messages" id="messages">
            <!-- Messages injected dynamically -->
        </div>

        <!-- AI Thinking / Streaming Indicator -->
        <div class="ai-typing-indicator" id="aiTypingIndicator">
            <div class="ai-typing-avatar">✦</div>
            <div class="ai-typing-content">
                <div class="ai-typing-text">Soen AI is writing code & organizing files…</div>
                <div class="ai-typing-dots"><span></span><span></span><span></span></div>
            </div>
        </div>

        <!-- Quick Prompt Suggestion Chips -->
        <div class="quick-chips-bar" id="quickChipsBar">
            <button class="quick-chip" data-prompt="@ai create an express server with routes and package.json">
                <span>⚡</span> Express Server
            </button>
            <button class="quick-chip" data-prompt="@ai Build a modern responsive HTML/CSS landing card component">
                <span>🎨</span> Web UI Component
            </button>
            <button class="quick-chip" data-prompt="@ai Write a clean Python script to parse JSON and calculate statistics">
                <span>🐍</span> Python Script
            </button>
            <button class="quick-chip" id="quickZipChip" title="Export all project files as a ZIP archive">
                <span>📦</span> Download ZIP
            </button>
        </div>

        <!-- Voice recording bar -->
        <div class="recording-bar" id="recordingBar">
            <div class="rec-status">
                <span class="rec-dot"></span>
                <span class="rec-label">Recording Voice Message</span>
                <span class="rec-timer" id="recTimer">0:00</span>
            </div>
            <div class="rec-actions">
                <button class="btn small secondary" id="cancelRecording">Cancel</button>
                <button class="btn small btn-primary" id="stopRecording">Send Voice ➤</button>
            </div>
        </div>

        <!-- Message Composer with all tools -->
        <div class="composer-container">
            <div class="composer-wrap">
                <div class="composer-tools-left">
                    <button class="composer-icon-btn" id="openCodeRunnerBtn" title="New Code Snippet in Studio">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    </button>
                    <button class="composer-icon-btn" id="attachBtn" title="Attach File or Image">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                    </button>
                    <input type="file" id="fileInput" hidden>
                    <button class="composer-icon-btn" id="emojiBtn" title="Add Emoji">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>
                    </button>
                </div>
                <textarea id="messageInput" class="composer-textarea" rows="1" placeholder="Ask Soen AI to code, refactor, or debug… (e.g. @ai build a navbar)"></textarea>
                <div class="composer-tools-right">
                    <button class="composer-icon-btn mic-btn" id="micBtn" title="Record Voice Note">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/></svg>
                    </button>
                    <button class="send-btn" id="sendBtn" title="Send (Enter)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" x2="11" y1="2" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </button>
                </div>
            </div>
            <div class="emoji-picker" id="emojiPicker"></div>
            <div class="composer-footer-hint">
                <span>Tip: Type <strong>@ai</strong> to code with AI · Mention <strong>@user</strong> to alert teammate · Shift+Enter for new line</span>
            </div>
        </div>
    </div>

    <!-- DRAGGABLE DIVIDER -->
    <div class="pane-divider" id="paneDivider" title="Drag to resize workspace">
        <div class="divider-handle"></div>
    </div>

    <!-- RIGHT: SOEN AI CODE STUDIO & WORKSPACE -->
    <div class="studio-panel" id="codeStudio">
        <!-- Studio Header & Navigation Tabs -->
        <div class="studio-header">
            <div class="studio-tabs">
                <button class="studio-tab-btn active" data-tab="code" id="tabCodeBtn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    <span>Editor</span>
                </button>
                <button class="studio-tab-btn" data-tab="preview" id="tabPreviewBtn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span>Preview</span>
                    <span class="tab-badge-dot" id="previewReadyDot" style="display:none;"></span>
                </button>
                <button class="studio-tab-btn" data-tab="terminal" id="tabTerminalBtn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 17 10 11 4 5"/><line x1="12" x2="20" y1="19" y2="19"/></svg>
                    <span>Console</span>
                </button>
                <button class="studio-tab-btn" data-tab="artifacts" id="tabArtifactsBtn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>Files (<span id="artifactCount">0</span>)</span>
                </button>
                <button class="studio-tab-btn" data-tab="members" id="tabMembersBtn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>Team</span>
                </button>
            </div>
            <div class="studio-header-actions">
                <button class="btn small btn-zip" id="exportZipBtn" title="Export entire workspace as ZIP">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" x2="12" y1="22.08" y2="12"/></svg>
                    <span>Export ZIP</span>
                </button>
                <button class="icon-btn studio-action-icon" id="fullscreenStudioBtn" title="Fullscreen Studio">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" x2="14" y1="3" y2="10"/><line x1="3" x2="10" y1="21" y2="14"/></svg>
                </button>
                <button class="icon-btn studio-action-icon" id="closeStudioBtn" title="Minimize Studio">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
                </button>
            </div>
        </div>

        <!-- TAB 1: CODE EDITOR -->
        <div class="studio-content-pane active" id="paneCode">
            <div class="studio-subbar">
                <!-- File Tabs Bar -->
                <div class="studio-file-tabs" id="studioFileTabs">
                    <div class="studio-file-tab active" id="defaultFileTab" data-id="active">
                        <span class="file-icon">📄</span>
                        <span class="file-name" id="currentFileName">snippet.js</span>
                    </div>
                </div>
                <!-- Action Tools -->
                <div class="studio-tools">
                    <select id="codeLanguageSelect" class="studio-lang-select" title="Change Language">
                        <option value="javascript">JavaScript</option>
                        <option value="html">HTML / CSS</option>
                        <option value="python">Python 3</option>
                        <option value="php">PHP</option>
                        <option value="java">Java</option>
                        <option value="c">C</option>
                        <option value="cpp">C++</option>
                        <option value="sql">SQL</option>
                        <option value="json">JSON</option>
                    </select>
                    <button class="studio-tool-btn btn-run" id="studioRunCodeBtn" title="Run Code">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        <span>Run</span>
                    </button>
                    <button class="studio-tool-btn" id="studioLivePreviewBtn" title="Live Web Preview" style="display:none;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <span>Preview</span>
                    </button>
                    <button class="studio-tool-btn" id="studioCopyBtn" title="Copy Code to Clipboard">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                        <span id="copyBtnText">Copy</span>
                    </button>
                    <button class="studio-tool-btn" id="studioDownloadBtn" title="Download File">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                    </button>
                    <button class="studio-tool-btn" id="studioShareBtn" title="Share Snippet into Chat">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/></svg>
                    </button>
                </div>
            </div>

            <!-- Code Editor Area with Line Numbers -->
            <div class="editor-container" id="editorContainer">
                <div class="editor-gutter" id="editorGutter">
                    <div>1</div>
                </div>
                <textarea id="studioCodeEditor" class="studio-code-textarea" spellcheck="false" placeholder="// Write code or prompt @ai in chat to generate code here..."></textarea>
            </div>
        </div>

        <!-- TAB 2: LIVE WEB PREVIEW -->
        <div class="studio-content-pane" id="panePreview">
            <div class="preview-toolbar">
                <div class="preview-status">
                    <span class="preview-dot"></span>
                    <span>Interactive Sandbox</span>
                </div>
                <div class="preview-viewport-controls">
                    <button class="viewport-btn active" data-width="100%" title="Desktop View">🖥️ Full</button>
                    <button class="viewport-btn" data-width="768px" title="Tablet View">📱 Tablet</button>
                    <button class="viewport-btn" data-width="375px" title="Mobile View">📲 Mobile</button>
                </div>
                <div class="preview-actions">
                    <button class="studio-tool-btn" id="refreshPreviewBtn" title="Reload Preview">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
                        <span>Reload</span>
                    </button>
                    <button class="studio-tool-btn" id="openPreviewNewTabBtn" title="Open in New Tab">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
                    </button>
                </div>
            </div>
            <div class="preview-frame-wrapper" id="previewFrameWrapper">
                <iframe id="previewIframe" class="preview-iframe" sandbox="allow-scripts allow-modals allow-same-origin"></iframe>
            </div>
        </div>

        <!-- TAB 3: CONSOLE / CODE RUNNER -->
        <div class="studio-content-pane" id="paneTerminal">
            <div class="terminal-toolbar">
                <div class="terminal-meta">
                    <span class="terminal-tag">Execution Output</span>
                    <span class="terminal-exec-time" id="terminalExecTime"></span>
                </div>
                <div class="terminal-actions">
                    <button class="studio-tool-btn" id="terminalClearBtn">Clear</button>
                    <button class="studio-tool-btn btn-run" id="terminalReRunBtn">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        <span>Run Again</span>
                    </button>
                </div>
            </div>
            <div class="terminal-body" id="terminalOutput">
                <div class="terminal-placeholder">
                    <span class="term-dim">Terminal ready. Click "▶ Run" in the Code Editor to execute Python, JavaScript, PHP, Java, C, or C++ code.</span>
                </div>
            </div>
        </div>

        <!-- TAB 4: ARTIFACTS / GENERATED FILES HISTORY -->
        <div class="studio-content-pane" id="paneArtifacts">
            <div class="artifacts-head">
                <div class="artifacts-head-info">
                    <h3>Project Files & Artifacts</h3>
                    <p>All project files created by AI or edited in this workspace.</p>
                </div>
                <div class="artifacts-head-actions">
                    <button class="btn small btn-zip" id="exportAllZipBtn">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" x2="12" y1="22.08" y2="12"/></svg>
                        <span>Download ZIP</span>
                    </button>
                    <button class="btn small secondary" id="importZipBtn">
                        <span>📂 Import ZIP</span>
                    </button>
                    <input type="file" id="zipFileInput" accept=".zip" hidden>
                </div>
            </div>
            <div class="artifacts-list" id="artifactsList">
                <div class="empty-state-card">
                    <div class="empty-icon">📁</div>
                    <div class="empty-title">No Artifacts Yet</div>
                    <div class="empty-sub">Ask <strong>@ai</strong> to generate code in chat to populate your workspace files.</div>
                </div>
            </div>
        </div>

        <!-- TAB 5: TEAM MEMBERS -->
        <div class="studio-content-pane" id="paneMembers">
            <div class="members-pane-head">
                <div>
                    <h3>Collaborators</h3>
                    <p class="sub-text">Team members in this project</p>
                </div>
                <button class="btn small btn-primary" id="openAddMemberModalBtn">+ Add Member</button>
            </div>
            <div class="members-list-modern" id="membersList">
                <!-- Dynamically injected -->
            </div>
        </div>
    </div>
</div>

<!-- MODAL: ADD MEMBER -->
<div class="modal-backdrop" id="addMemberModal">
    <div class="modal">
        <div class="modal-head">
            <h3>Invite Collaborator</h3>
            <button class="icon-btn modal-close-btn" id="closeAddMemberModal">✕</button>
        </div>
        <p class="modal-sub">Add a team member by email to collaborate on code and chat in real time.</p>
        <div class="error-msg" id="addMemberError"></div>
        <div class="field">
            <label for="memberEmail">Teammate's Email</label>
            <input type="email" id="memberEmail" placeholder="colleague@example.com" autocomplete="off">
        </div>
        <div class="modal-actions">
            <button class="btn secondary" id="cancelAddMember">Cancel</button>
            <button class="btn btn-primary" id="confirmAddMember">Send Invite</button>
        </div>
    </div>
</div>

<script src="assets/js/project.js?v=<?= time() ?>"></script>
</body>
</html>
