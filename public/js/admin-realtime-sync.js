/**
 * Teach Me admin — long-poll sync so staff pages update when mobile/parent data changes.
 * Refreshes any region with data-tm-sync-partial-url (no full page reload).
 */
(function () {
    const STORAGE_KEY = 'tm_admin_sync_token';

    function getToken() {
        try {
            return sessionStorage.getItem(STORAGE_KEY) || '';
        } catch (e) {
            return '';
        }
    }

    function setToken(token) {
        try {
            sessionStorage.setItem(STORAGE_KEY, token || '');
        } catch (e) {
            /* ignore */
        }
    }

    function flashUpdated(root) {
        root.classList.add('tm-sync-flash');
        window.setTimeout(() => root.classList.remove('tm-sync-flash'), 1200);
    }

    async function refreshPartial(root) {
        const url = root.getAttribute('data-tm-sync-partial-url');
        if (!url) {
            return;
        }
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!response.ok) {
            return;
        }
        const html = await response.text();
        root.innerHTML = html;
        flashUpdated(root);

        const badgeHost = document.getElementById('tm-enrollment-status-badge');
        const statusEl = root.querySelector('[data-tm-sync-status]');
        if (badgeHost && statusEl) {
            badgeHost.innerHTML = statusEl.outerHTML;
        }
    }

    async function onSyncChanged() {
        const roots = document.querySelectorAll('[data-tm-sync-partial-url]');
        await Promise.all(Array.from(roots).map((root) => refreshPartial(root)));

        document.dispatchEvent(new CustomEvent('tm-admin-sync-changed'));
    }

    async function waitLoop() {
        if (document.hidden) {
            await new Promise((r) => setTimeout(r, 2000));
            return waitLoop();
        }

        const token = getToken();
        const params = new URLSearchParams({ token, timeout: '25' });

        try {
            const response = await fetch('/admin/sync/wait?' + params.toString(), {
                credentials: 'same-origin',
            });
            if (!response.ok) {
                await new Promise((r) => setTimeout(r, 3000));
                return waitLoop();
            }

            const data = await response.json();
            if (data.syncToken) {
                setToken(data.syncToken);
            }

            if (data.changed) {
                await onSyncChanged();
            }
        } catch (e) {
            await new Promise((r) => setTimeout(r, 3000));
        }

        return waitLoop();
    }

    if (document.body && document.body.dataset.tmRealtime !== 'off') {
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                onSyncChanged();
            }
        });
        waitLoop();
    }
})();
