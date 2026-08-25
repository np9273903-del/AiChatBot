document.addEventListener('DOMContentLoaded', () => {
    const projectGrid = document.getElementById('projectGrid');
    const modalBackdrop = document.getElementById('modalBackdrop');
    const newProjectCard = document.getElementById('newProjectCard');
    const openNewProjectBtn = document.getElementById('openNewProjectBtn');
    const closeNewProjectModal = document.getElementById('closeNewProjectModal');
    const cancelModal = document.getElementById('cancelModal');
    const createProjectBtn = document.getElementById('createProjectBtn');
    const projectNameInput = document.getElementById('projectName');
    const modalError = document.getElementById('modalError');
    const logoutBtn = document.getElementById('logoutBtn');

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    async function loadProjects() {
        if (!projectGrid) return;
        try {
            const res = await fetch('api/projects/list.php');
            const data = await res.json();
            if (!data.success) return;

            // Clear everything except the "+" card
            [...projectGrid.querySelectorAll('.project-card')].forEach(el => el.remove());

            if (data.projects && data.projects.length > 0) {
                data.projects.forEach(p => {
                    const card = document.createElement('div');
                    card.className = 'project-card';
                    card.innerHTML = `
                        <div class="project-card-header">
                            <div class="project-icon-box">⚡</div>
                            <span class="project-member-badge">${p.member_count} member${p.member_count == 1 ? '' : 's'}</span>
                        </div>
                        <h3 class="project-title">${escapeHtml(p.name)}</h3>
                        <div class="project-meta-bottom">
                            <span class="meta-status">✦ AI Ready</span>
                            <span class="open-arrow">Open Workspace →</span>
                        </div>
                    `;
                    card.addEventListener('click', () => {
                        window.location.href = `project.php?id=${p.id}`;
                    });
                    projectGrid.appendChild(card);
                });
            }
        } catch (e) {
            console.error('Error loading projects:', e);
        }
    }

    function openModal() {
        if (!modalBackdrop) return;
        if (modalError) modalError.classList.remove('show');
        if (projectNameInput) {
            projectNameInput.value = '';
            modalBackdrop.classList.add('show');
            setTimeout(() => projectNameInput.focus(), 50);
        }
    }

    function closeModal() {
        if (modalBackdrop) modalBackdrop.classList.remove('show');
    }

    if (newProjectCard) newProjectCard.addEventListener('click', openModal);
    if (openNewProjectBtn) openNewProjectBtn.addEventListener('click', openModal);
    if (closeNewProjectModal) closeNewProjectModal.addEventListener('click', closeModal);
    if (cancelModal) cancelModal.addEventListener('click', closeModal);

    async function handleCreateProject() {
        if (!projectNameInput) return;
        const name = projectNameInput.value.trim();
        if (name.length < 2) {
            if (modalError) {
                modalError.textContent = 'Project name must be at least 2 characters';
                modalError.classList.add('show');
            }
            return;
        }

        try {
            const res = await fetch('api/projects/create.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name }),
            });
            const data = await res.json();
            if (!data.success) {
                if (modalError) {
                    modalError.textContent = data.message || 'Could not create project';
                    modalError.classList.add('show');
                }
                return;
            }
            closeModal();
            window.location.href = `project.php?id=${data.project.id}`;
        } catch (e) {
            if (modalError) {
                modalError.textContent = 'Server error: ' + e.message;
                modalError.classList.add('show');
            }
        }
    }

    if (createProjectBtn) createProjectBtn.addEventListener('click', handleCreateProject);

    if (projectNameInput) {
        projectNameInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleCreateProject();
            }
        });
    }

    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            await fetch('api/logout.php', { method: 'POST' });
            window.location.href = 'login.html';
        });
    }

    loadProjects();
});
