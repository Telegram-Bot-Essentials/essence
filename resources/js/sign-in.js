// Signs a Mini App user in from Telegram's initData. Only used on the sign-in
// page; the panels themselves are plain Filament.
(function () {
    const el = document.querySelector('[data-tbe-signin]');
    const app = window.Telegram && window.Telegram.WebApp;
    if (!el || !app || !app.initData) return;

    app.ready();
    app.expand();

    // Landing here again right after signing in means the session cookie did
    // not stick. Never loop.
    const last = Number(sessionStorage.getItem('tbe.signin') || 0);
    if (Date.now() - last < 15000) {
        el.textContent = el.dataset.failed;
        return;
    }
    sessionStorage.setItem('tbe.signin', String(Date.now()));

    fetch(el.dataset.url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        },
        body: JSON.stringify({ init_data: app.initData, panel: el.dataset.panel }),
    }).then(async (response) => {
        const body = await response.json();
        response.ok ? location.replace(body.redirect) : (el.textContent = body.message);
    });
})();
