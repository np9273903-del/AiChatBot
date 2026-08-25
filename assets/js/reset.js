const resetForm = document.getElementById('resetForm');
const resetEmail = document.getElementById('resetEmail');
const newPassword = document.getElementById('newPassword');
const confirmPassword = document.getElementById('confirmPassword');
const resetSubmit = document.getElementById('resetSubmit');
const resetError = document.getElementById('resetError');
const resetSuccess = document.getElementById('resetSuccess');
function resetMessage(element, message) { element.textContent = message; element.classList.toggle('show', Boolean(message)); }
async function resetRequest(url, body) {
    const response = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Request failed');
    return data;
}

resetForm.addEventListener('submit', async event => {
    event.preventDefault(); resetMessage(resetError, ''); resetMessage(resetSuccess, '');
    try {
        if (newPassword.value !== confirmPassword.value) throw new Error('Passwords do not match');
        await resetRequest('api/auth/reset_password_direct.php', { email: resetEmail.value.trim(), password: newPassword.value });
        resetMessage(resetSuccess, 'Password updated. Redirecting to login...'); resetSubmit.disabled = true;
        setTimeout(() => { window.location.href = 'login.html'; }, 900);
    } catch (error) { resetMessage(resetError, error.message); }
});

newPassword.addEventListener('input', () => {
    const score = [newPassword.value.length >= 8, /[A-Z]/.test(newPassword.value), /[0-9]/.test(newPassword.value), /[^A-Za-z0-9]/.test(newPassword.value)].filter(Boolean).length;
    const strength = document.getElementById('resetStrength'); strength.style.width = `${score * 25}%`; strength.style.background = score < 2 ? 'var(--danger)' : score < 4 ? 'var(--warning)' : 'var(--success)';
});
