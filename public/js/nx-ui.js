/*
 * nx-ui: the small bits of behaviour Tailwind pages need (instead of
 * Bootstrap's JavaScript).
 *
 *   Dropdown  <div data-nx-dropdown>
 *               <button data-nx-toggle="dropdown">...</button>
 *               <div data-nx-menu>...</div>          (hidden until opened)
 *             </div>
 *   Alert     <div data-nx-alert> ... <button data-nx-dismiss="alert"> </div>
 *
 * Alerts (also Bootstrap ".alert" ones) close by themselves after 5 s,
 * unless they have data-persist-alert="true".
 */
(function () {
    'use strict';

    function closeMenus(except) {
        document.querySelectorAll('[data-nx-dropdown] [data-nx-menu][data-open]').forEach(function (menu) {
            if (menu !== except) {
                menu.removeAttribute('data-open');
                var toggle = menu.closest('[data-nx-dropdown]').querySelector('[data-nx-toggle="dropdown"]');
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-nx-toggle="dropdown"]');

        if (toggle) {
            var menu = toggle.closest('[data-nx-dropdown]').querySelector('[data-nx-menu]');
            var open = !menu.hasAttribute('data-open');
            closeMenus(menu);
            menu.toggleAttribute('data-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            return;
        }

        if (!event.target.closest('[data-nx-menu]')) {
            closeMenus(null);
        }

        var dismiss = event.target.closest('[data-nx-dismiss="alert"]');
        if (dismiss) {
            fadeOut(dismiss.closest('[data-nx-alert]'));
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeMenus(null);
    });

    function fadeOut(el) {
        if (!el || !el.isConnected) return;
        el.style.transition = 'opacity .15s linear';
        el.style.opacity = '0';
        setTimeout(function () { el.remove(); }, 160);
    }

    function autoClose() {
        document.querySelectorAll('[data-nx-alert], .alert').forEach(function (alert) {
            if (alert.getAttribute('data-persist-alert') === 'true') return;
            setTimeout(function () { fadeOut(alert); }, 5000);
        });
    }

    // Loaded at the end of <body> (before page scripts): the alerts on the
    // page at that moment close after 5 s, as before
    autoClose();

    window.NxUi = { closeMenus: closeMenus, fadeOut: fadeOut };
})();
