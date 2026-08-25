(function () {
    const root = document.documentElement;
    const savedTheme = localStorage.getItem('soen-theme');
    if (savedTheme) root.dataset.theme = savedTheme;

    document.querySelectorAll('[data-theme-toggle]').forEach(button => {
        button.addEventListener('click', () => {
            const nextTheme = root.dataset.theme === 'light' ? 'dark' : 'light';
            root.dataset.theme = nextTheme;
            localStorage.setItem('soen-theme', nextTheme);
            button.setAttribute('aria-label', `Switch to ${nextTheme === 'light' ? 'dark' : 'light'} mode`);
        });
    });

    document.querySelectorAll('[data-password-toggle]').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            button.textContent = input.type === 'password' ? 'Show' : 'Hide';
        });
    });
})();
