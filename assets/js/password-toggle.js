/**
 * Show / hide password: wraps .login-split__input--password-toggle and binds existing .login-split__password-wrap.
 */
(function () {
    const BTN_HTML =
        '<button type="button" class="login-split__password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false">' +
        '<span class="login-split__password-toggle-icon login-split__password-toggle-icon--show" aria-hidden="true">' +
        '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">' +
        '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />' +
        '<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />' +
        '</svg></span>' +
        '<span class="login-split__password-toggle-icon login-split__password-toggle-icon--hide" hidden aria-hidden="true">' +
        '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">' +
        '<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21M12 12h.01" />' +
        '</svg></span></button>';

    function bindWrap(wrap) {
        const input = wrap.querySelector('input');
        const btn = wrap.querySelector('[data-password-toggle]');
        if (!input || !btn) {
            return;
        }
        const showIc = btn.querySelector('.login-split__password-toggle-icon--show');
        const hideIc = btn.querySelector('.login-split__password-toggle-icon--hide');

        function sync() {
            const isHidden = input.type === 'password';
            btn.setAttribute('aria-label', isHidden ? 'Show password' : 'Hide password');
            btn.setAttribute('aria-pressed', isHidden ? 'false' : 'true');
            if (showIc) {
                showIc.hidden = !isHidden;
            }
            if (hideIc) {
                hideIc.hidden = isHidden;
            }
        }

        btn.addEventListener('click', function () {
            input.type = input.type === 'password' ? 'text' : 'password';
            sync();
        });
        sync();
    }

    function wrapLooseInputs() {
        document.querySelectorAll('input.login-split__input--password-toggle').forEach(function (input) {
            if (input.closest('.login-split__password-wrap')) {
                return;
            }
            const wrap = document.createElement('div');
            wrap.className = 'login-split__password-wrap';
            input.classList.add('login-split__input--with-toggle');
            input.parentNode.insertBefore(wrap, input);
            wrap.appendChild(input);
            wrap.insertAdjacentHTML('beforeend', BTN_HTML);
            if (input.id) {
                wrap.querySelector('[data-password-toggle]').setAttribute('aria-controls', input.id);
            }
            bindWrap(wrap);
        });
    }

    document.querySelectorAll('.login-split__password-wrap').forEach(bindWrap);
    wrapLooseInputs();
})();
