/*
 * nx-ui: the small bits of behaviour Tailwind pages need (instead of
 * Bootstrap's JavaScript).
 *
 *   Dropdown  <div data-nx-dropdown>
 *               <button data-nx-toggle="dropdown">...</button>
 *               <div data-nx-menu>...</div>          (hidden until opened)
 *             </div>
 *             or the ui-kit markup:
 *             <div class="ui-dropdown"><button data-nx-toggle="dropdown">
 *               <ul class="ui-dropdown-menu">...</ul></div>
 *   Alert     <div data-nx-alert> (or class ui-alert) ... <button data-nx-dismiss="alert">
 *   Modal     <button data-nx-toggle="modal" data-nx-target="#id">
 *             <div class="ui-modal ui-fade" id="id"> ... <button data-nx-dismiss="modal">
 *             JS: NxUi.Modal.getOrCreateInstance(el).show() / .hide()
 *             events on the modal: show.nx.modal shown.nx.modal hide.nx.modal hidden.nx.modal
 *             data-nx-backdrop="static" / data-nx-keyboard="false" as in Bootstrap
 *   Tabs      <button data-nx-toggle="tab" data-nx-target="#pane"> (or href="#pane")
 *             panes: <div class="ui-tab-content"><div class="ui-tab-pane ui-fade" id="pane">
 *
 * Alerts (also Bootstrap ".alert" ones) close by themselves after 5 s,
 * unless they have data-persist-alert="true".
 */
(function () {
    'use strict';

    // ------------------------------------------------------------ dropdown

    function menuOf(toggle) {
        var box = toggle.closest('[data-nx-dropdown]');
        if (box) return box.querySelector('[data-nx-menu]');
        var parent = toggle.parentElement;
        return parent ? parent.querySelector('.ui-dropdown-menu') : null;
    }

    function isOpen(menu) {
        return menu.hasAttribute('data-nx-menu') ? menu.hasAttribute('data-open') : menu.classList.contains('show');
    }

    function setOpen(menu, open) {
        if (menu.hasAttribute('data-nx-menu')) {
            menu.toggleAttribute('data-open', open);
        } else {
            // no Popper: below the button, as Bootstrap's static dropdowns
            menu.setAttribute('data-bs-popper', 'static');
            menu.classList.toggle('show', open);
        }
        var toggle = menu.parentElement && menu.parentElement.querySelector('[data-nx-toggle="dropdown"]');
        if (!toggle) {
            var box = menu.closest('[data-nx-dropdown]');
            toggle = box && box.querySelector('[data-nx-toggle="dropdown"]');
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.classList.toggle('show', open && !menu.hasAttribute('data-nx-menu'));
        }
    }

    function closeMenus(except) {
        document.querySelectorAll('[data-nx-dropdown] [data-nx-menu][data-open], .ui-dropdown-menu.show').forEach(function (menu) {
            if (menu !== except) setOpen(menu, false);
        });
    }

    // --------------------------------------------------------------- modal

    var instances = new WeakMap();
    var openModals = 0;

    function emit(el, name, related) {
        var event = new CustomEvent(name, { bubbles: true, cancelable: true, detail: { relatedTarget: related } });
        event.relatedTarget = related;
        return el.dispatchEvent(event);
    }

    function reflow(el) {
        return el.offsetHeight;
    }

    function Modal(el, options) {
        if (typeof el === 'string') el = document.querySelector(el);
        this._el = el;
        this._options = Object.assign({
            backdrop: el.getAttribute('data-nx-backdrop') === 'static' ? 'static' : el.getAttribute('data-nx-backdrop') !== 'false',
            keyboard: el.getAttribute('data-nx-keyboard') !== 'false',
        }, options || {});
        this._shown = false;
        this._busy = false;
        this._backdrop = null;
        instances.set(el, this);

        var self = this;
        el.addEventListener('mousedown', function (event) {
            self._downOnSelf = event.target === el;
        });
        el.addEventListener('click', function (event) {
            if (event.target !== el || !self._downOnSelf) return;
            if (self._options.backdrop === 'static') {
                self._bounce();
            } else if (self._options.backdrop) {
                self.hide();
            }
        });
    }

    Modal.prototype.toggle = function (related) {
        return this._shown ? this.hide() : this.show(related);
    };

    Modal.prototype.show = function (related) {
        if (this._shown || this._busy) return;
        if (!emit(this._el, 'show.nx.modal', related)) return;

        var el = this._el;
        var self = this;
        this._shown = true;
        this._busy = true;
        this._lastFocus = document.activeElement;

        if (openModals++ === 0) {
            var scrollbar = window.innerWidth - document.documentElement.clientWidth;
            var body = document.body;
            this._bodyStyle = { overflow: body.style.overflow, paddingRight: body.style.paddingRight };
            body.style.overflow = 'hidden';
            if (scrollbar > 0) {
                body.style.paddingRight = (parseFloat(getComputedStyle(body).paddingRight) + scrollbar) + 'px';
            }
            body.classList.add('modal-open');
        }

        if (this._options.backdrop) {
            var backdrop = document.createElement('div');
            backdrop.className = 'ui-modal-backdrop' + (el.classList.contains('ui-fade') ? ' ui-fade' : '');
            document.body.appendChild(backdrop);
            reflow(backdrop);
            backdrop.classList.add('show');
            this._backdrop = backdrop;
        }

        el.style.display = 'block';
        el.removeAttribute('aria-hidden');
        el.setAttribute('aria-modal', 'true');
        el.setAttribute('role', 'dialog');
        el.scrollTop = 0;
        var body = el.querySelector('.ui-modal-body');
        if (body) body.scrollTop = 0;
        reflow(el);
        el.classList.add('show');

        this._afterTransition(function () {
            self._busy = false;
            if (!el.contains(document.activeElement)) el.focus({ preventScroll: true });
            emit(el, 'shown.nx.modal', related);
        });
    };

    Modal.prototype.hide = function () {
        if (!this._shown || this._busy) return;
        if (!emit(this._el, 'hide.nx.modal')) return;

        var el = this._el;
        var self = this;
        this._shown = false;
        this._busy = true;
        el.classList.remove('show');
        if (this._backdrop) this._backdrop.classList.remove('show');

        this._afterTransition(function () {
            el.style.display = 'none';
            el.setAttribute('aria-hidden', 'true');
            el.removeAttribute('aria-modal');
            el.removeAttribute('role');
            if (self._backdrop) {
                self._backdrop.remove();
                self._backdrop = null;
            }
            if (--openModals === 0) {
                var b = document.body;
                b.classList.remove('modal-open');
                b.style.overflow = self._bodyStyle ? self._bodyStyle.overflow : '';
                b.style.paddingRight = self._bodyStyle ? self._bodyStyle.paddingRight : '';
            }
            self._busy = false;
            if (self._lastFocus && self._lastFocus.focus) self._lastFocus.focus({ preventScroll: true });
            emit(el, 'hidden.nx.modal');
        });
    };

    Modal.prototype.dispose = function () {
        instances.delete(this._el);
    };

    Modal.prototype._afterTransition = function (done) {
        var fade = this._el.classList.contains('ui-fade');
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (!fade || reduce) {
            done();
        } else {
            setTimeout(done, 300);
        }
    };

    Modal.prototype._bounce = function () {
        var el = this._el;
        el.classList.add('ui-modal-static');
        setTimeout(function () { el.classList.remove('ui-modal-static'); }, 300);
    };

    Modal.getInstance = function (el) {
        if (typeof el === 'string') el = document.querySelector(el);
        return el ? instances.get(el) || null : null;
    };

    Modal.getOrCreateInstance = function (el, options) {
        if (typeof el === 'string') el = document.querySelector(el);
        return Modal.getInstance(el) || new Modal(el, options);
    };

    function topModal() {
        var shown = document.querySelectorAll('.ui-modal.show');
        return shown.length ? Modal.getInstance(shown[shown.length - 1]) : null;
    }

    // ---------------------------------------------------------------- tabs

    function targetOf(toggle) {
        var sel = toggle.getAttribute('data-nx-target') || toggle.getAttribute('href');
        return sel && sel.charAt(0) === '#' ? document.querySelector(sel) : null;
    }

    function showTab(toggle) {
        if (toggle.classList.contains('active')) return;
        var nav = toggle.closest('.ui-nav, [role="tablist"]') || toggle.parentElement.parentElement;
        var previous = nav.querySelector('[data-nx-toggle="tab"].active, [data-nx-toggle="pill"].active');

        if (previous) {
            previous.classList.remove('active');
            previous.setAttribute('aria-selected', 'false');
            var old = targetOf(previous);
            if (old) old.classList.remove('active', 'show');
        }

        toggle.classList.add('active');
        toggle.setAttribute('aria-selected', 'true');
        var pane = targetOf(toggle);
        if (pane) {
            pane.classList.add('active');
            if (pane.classList.contains('ui-fade')) reflow(pane);
            pane.classList.add('show');
        }
        emit(toggle, 'shown.nx.tab', previous);
    }

    // -------------------------------------------------------------- alerts

    function fadeOut(el) {
        if (!el || !el.isConnected) return;
        el.style.transition = 'opacity .15s linear';
        el.style.opacity = '0';
        setTimeout(function () { el.remove(); }, 160);
    }

    function autoClose() {
        document.querySelectorAll('[data-nx-alert], .alert, .ui-alert').forEach(function (alert) {
            if (alert.getAttribute('data-persist-alert') === 'true') return;
            setTimeout(function () { fadeOut(alert); }, 5000);
        });
    }

    // -------------------------------------------------------------- events

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-nx-toggle]');
        var kind = toggle && toggle.getAttribute('data-nx-toggle');

        if (kind === 'dropdown') {
            var menu = menuOf(toggle);
            if (menu) {
                var open = !isOpen(menu);
                closeMenus(menu);
                setOpen(menu, open);
            }
            event.preventDefault();
            return;
        }

        if (!event.target.closest('[data-nx-menu], .ui-dropdown-menu')) {
            closeMenus(null);
        }

        if (kind === 'modal') {
            var target = targetOf(toggle);
            if (target) {
                event.preventDefault();
                Modal.getOrCreateInstance(target).show(toggle);
            }
            return;
        }

        if (kind === 'tab' || kind === 'pill') {
            event.preventDefault();
            showTab(toggle);
            return;
        }

        var dismiss = event.target.closest('[data-nx-dismiss]');
        if (dismiss) {
            var what = dismiss.getAttribute('data-nx-dismiss');
            if (what === 'alert') {
                fadeOut(dismiss.closest('[data-nx-alert], .ui-alert'));
            } else if (what === 'modal') {
                var modal = dismiss.closest('.ui-modal');
                if (modal) Modal.getOrCreateInstance(modal).hide();
            }
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        closeMenus(null);
        var modal = topModal();
        if (modal) {
            if (modal._options.keyboard && modal._options.backdrop !== 'static') {
                modal.hide();
            } else if (modal._options.backdrop === 'static') {
                modal._bounce();
            }
        }
    });

    // Loaded at the end of <body> (before page scripts): the alerts on the
    // page at that moment close after 5 s, as before
    autoClose();

    window.NxUi = { closeMenus: closeMenus, fadeOut: fadeOut, Modal: Modal, showTab: showTab };
})();
