function showError(msg) {
    const el = document.getElementById('errorMsg');
    el.textContent = msg;
    el.classList.add('show');
}

async function postJSON(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    });
    const data = await res.json();
    if (!res.ok || !data.success) {
        throw new Error(data.message || 'Something went wrong');
    }
    return data;
}

const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            await postJSON('api/login.php', {
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
            });
            window.location.href = 'home.php';
        } catch (err) {
            showError(err.message);
        }
    });
}

const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            await postJSON('api/register.php', {
                username: document.getElementById('username').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
            });
            window.location.href = 'home.php';
        } catch (err) {
            showError(err.message);
        }
    });
}
