const adminStats = document.getElementById('adminStats');
const adminProjectList = document.getElementById('adminProjectList');

async function loadAdminDashboard() {
    const response = await fetch('api/admin/stats.php');
    const data = await response.json();
    if (!response.ok || !data.success) {
        document.querySelector('.admin-dashboard').innerHTML = '<div class="chat-error">Only a group admin can access this dashboard.</div>';
        return;
    }
    adminStats.innerHTML = `<div class="stat-card"><strong>${data.stats.projects}</strong><span>Projects</span></div><div class="stat-card"><strong>${data.stats.members}</strong><span>Team members</span></div><div class="stat-card"><strong>${data.stats.messages}</strong><span>Messages</span></div>`;
    adminProjectList.innerHTML = data.projects.length ? data.projects.map(project => `<a class="admin-project-card" href="project.php?id=${project.id}"><strong>${escapeHtml(project.name)}</strong><span>${project.member_count} members · ${project.message_count} messages</span></a>`).join('') : '<p class="sub">You do not own any projects yet.</p>';
}
function escapeHtml(value) { const div = document.createElement('div'); div.textContent = value; return div.innerHTML; }
loadAdminDashboard();
