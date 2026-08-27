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

    // 🌟 Jitter Navigation Slider Indicator
    const navMenu = document.getElementById('jitterNavMenu');
    const navIndicator = document.getElementById('navIndicator');
    const navItems = document.querySelectorAll('.jitter-nav-item');

    function updateNavIndicator(element) {
        if (!element || !navIndicator || !navMenu) return;
        const menuRect = navMenu.getBoundingClientRect();
        const itemRect = element.getBoundingClientRect();

        const offsetLeft = itemRect.left - menuRect.left;
        const width = itemRect.width;

        navIndicator.style.opacity = '1';
        navIndicator.style.transform = `translateX(${offsetLeft}px)`;
        navIndicator.style.width = `${width}px`;
    }

    if (navItems.length > 0) {
        const activeItem = document.querySelector('.jitter-nav-item.active') || navItems[0];
        setTimeout(() => updateNavIndicator(activeItem), 50);

        navItems.forEach(item => {
            item.addEventListener('mouseenter', () => updateNavIndicator(item));
            item.addEventListener('click', () => {
                navItems.forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                updateNavIndicator(item);
            });
        });

        if (navMenu) {
            navMenu.addEventListener('mouseleave', () => {
                const currentActive = document.querySelector('.jitter-nav-item.active') || navItems[0];
                if (currentActive) updateNavIndicator(currentActive);
            });
        }
    }

    // 🌟 Exact Jitter Toggle Switch Interaction
    const aiModeSwitch = document.getElementById('aiModeSwitch');
    const lblFast = document.getElementById('lblFast');
    const lblPro = document.getElementById('lblPro');

    if (aiModeSwitch) {
        function syncToggleLabels() {
            if (aiModeSwitch.checked) {
                if (lblFast) lblFast.classList.remove('active');
                if (lblPro) lblPro.classList.add('active');
            } else {
                if (lblFast) lblFast.classList.add('active');
                if (lblPro) lblPro.classList.remove('active');
            }
        }
        aiModeSwitch.addEventListener('change', syncToggleLabels);
        if (lblFast) lblFast.addEventListener('click', () => { aiModeSwitch.checked = false; syncToggleLabels(); });
        if (lblPro)  lblPro.addEventListener('click',  () => { aiModeSwitch.checked = true;  syncToggleLabels(); });
        syncToggleLabels();
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
                data.projects.forEach((p, idx) => {
                    const card = document.createElement('div');
                    card.className = 'project-card jitter-card-in';
                    card.style.animationDelay = `${idx * 0.07}s`;
                    card.innerHTML = `
                        <div class="project-card-header">
                            <div class="project-icon-box">⚡</div>
                            <span class="project-member-badge">${p.member_count} member${p.member_count == 1 ? '' : 's'}</span>
                        </div>
                        <h3 class="project-title">${escapeHtml(p.name)}</h3>
                        <div class="project-meta-bottom">
                            <span class="meta-status">✦ AI Workspace Ready</span>
                            <span class="open-arrow">Open →</span>
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
