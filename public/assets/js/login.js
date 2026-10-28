(() => {
    const password = document.getElementById('senha');
    const toggle = document.querySelector('.password-toggle');
    const warning = document.getElementById('caps-warning');
    if (!password || !toggle) return;
    toggle.hidden = false;
    toggle.addEventListener('click', () => {
        const visible = password.type === 'password';
        password.type = visible ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', String(visible));
        toggle.setAttribute('aria-label', visible ? 'Ocultar senha' : 'Mostrar senha');
    });
    const updateCapsLock = (event) => {
        if (event.getModifierState) warning.hidden = !event.getModifierState('CapsLock');
    };
    password.addEventListener('keydown', updateCapsLock);
    password.addEventListener('keyup', updateCapsLock);
    password.addEventListener('blur', () => { warning.hidden = true; });
})();
