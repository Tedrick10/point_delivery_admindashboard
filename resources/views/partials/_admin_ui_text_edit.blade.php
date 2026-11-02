@php
    $canEditAdminUi = function_exists('isSuperAdmin') && isSuperAdmin(auth()->user());
@endphp
@if($canEditAdminUi)
<style>
/* Soft text cue only — color change, no layout/display overrides */
body.admin-panel-editing [data-admin-text-key][contenteditable="true"],
body.admin-panel-editing .admin-text-edit {
    outline: none !important;
    cursor: text;
    user-select: text;
    -webkit-user-select: text;
    caret-color: #FE6F07;
    text-decoration: none !important;
    box-shadow: none !important;
    color: #C2410C !important;
}
body.admin-panel-editing [data-admin-text-key][contenteditable="true"]:hover,
body.admin-panel-editing .admin-text-edit:hover {
    color: #EA580C !important;
}
body.admin-panel-editing [data-admin-text-key][contenteditable="true"]:focus,
body.admin-panel-editing .admin-text-edit:focus {
    color: #9A3412 !important;
    background: rgba(254, 111, 7, 0.08);
    border-radius: 4px;
    box-shadow: 0 0 0 3px rgba(254, 111, 7, 0.12) !important;
}
/* Keep table cells as table-cells while editing */
body.admin-panel-editing th[data-admin-text-key][contenteditable="true"],
body.admin-panel-editing td[data-admin-text-key][contenteditable="true"] {
    display: table-cell !important;
}
body.admin-panel-editing th [data-admin-text-key][contenteditable="true"] {
    display: inline !important;
}
/* Hero / titles: keep wash tight to text */
body.admin-panel-editing .pds-dashboard-hero [data-admin-text-key][contenteditable="true"],
body.admin-panel-editing .pds-dashboard-hero .admin-text-edit,
body.admin-panel-editing .pds-section-title[contenteditable="true"] {
    width: max-content;
    max-width: 100%;
}
/* On orange hero, use readable light text instead of brown */
body.admin-panel-editing .pds-dashboard-hero [data-admin-text-key][contenteditable="true"],
body.admin-panel-editing .pds-dashboard-hero .admin-text-edit {
    color: #fff !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.18);
}
body.admin-panel-editing .pds-dashboard-hero [data-admin-text-key][contenteditable="true"]:focus,
body.admin-panel-editing .pds-dashboard-hero .admin-text-edit:focus {
    color: #fff !important;
    background: rgba(255, 255, 255, 0.18) !important;
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.22) !important;
}
body.admin-panel-editing .pds-main-nav__link,
body.admin-panel-editing .pds-main-nav__sublink {
    cursor: default;
}
body.admin-panel-editing .pds-kyo-shin-metric__label[contenteditable="true"] {
    cursor: text;
}
.pds-admin-ui-editbar {
    position: fixed;
    right: 20px;
    bottom: 20px;
    z-index: 1080;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.88);
    border: 1px solid rgba(20, 17, 15, 0.08);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    box-shadow:
        0 12px 28px rgba(20, 17, 15, 0.12),
        0 2px 6px rgba(20, 17, 15, 0.06);
}
.pds-admin-ui-editbar__btn {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    border: 0 !important;
    border-radius: 999px !important;
    min-height: 40px;
    padding: 0 1.15rem !important;
    background: linear-gradient(135deg, #FF8A3D 0%, #FE6F07 55%, #F05A00 100%) !important;
    color: #fff !important;
    font: inherit;
    font-size: 0.84rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.01em;
    line-height: 1 !important;
    cursor: pointer;
    box-shadow: 0 8px 18px rgba(254, 111, 7, 0.28);
    transition: transform .12s ease, filter .12s ease, box-shadow .12s ease, background .12s ease;
}
.pds-admin-ui-editbar__btn i,
.pds-admin-ui-editbar__btn span,
.pds-admin-ui-editbar__btn [data-admin-edit-label] {
    color: #fff !important;
}
.pds-admin-ui-editbar__btn i {
    font-size: 0.72rem;
    opacity: 0.95;
}
.pds-admin-ui-editbar__btn:hover {
    filter: brightness(1.04);
    transform: translateY(-1px);
    color: #fff !important;
}
.pds-admin-ui-editbar__btn:active {
    transform: translateY(0);
}
.pds-admin-ui-editbar__btn.is-on {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.22);
}
.pds-admin-ui-editbar__btn.is-on,
.pds-admin-ui-editbar__btn.is-on i,
.pds-admin-ui-editbar__btn.is-on span,
.pds-admin-ui-editbar__btn.is-on [data-admin-edit-label] {
    color: #fff !important;
}
.pds-admin-ui-editbar__btn--ghost {
    background: #fff !important;
    color: #475569 !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: none !important;
}
.pds-admin-ui-editbar__btn--ghost i,
.pds-admin-ui-editbar__btn--ghost span {
    color: #475569 !important;
}
.pds-admin-ui-editbar__btn--ghost:hover {
    background: #f8fafc !important;
    border-color: #cbd5e1 !important;
    color: #0f172a !important;
    filter: none;
    transform: none;
}
.pds-admin-ui-editbar__btn--ghost:hover i,
.pds-admin-ui-editbar__btn--ghost:hover span {
    color: #0f172a !important;
}
</style>
<div class="pds-admin-ui-editbar" data-admin-edit-bar>
    <button type="button"
            class="pds-admin-ui-editbar__btn"
            data-admin-edit-toggle
            aria-pressed="false">
        <i class="fas fa-pen" aria-hidden="true"></i>
        <span data-admin-edit-label>{{ __('message.sa_edit') }}</span>
    </button>
    <button type="button"
            class="pds-admin-ui-editbar__btn pds-admin-ui-editbar__btn--ghost"
            data-admin-edit-reset
            hidden>
        <i class="fas fa-undo" aria-hidden="true"></i>
        <span>{{ __('message.reset') }}</span>
    </button>
</div>
<script>
(function () {
    var toggleBtn = document.querySelector('[data-admin-edit-toggle]');
    var resetBtn = document.querySelector('[data-admin-edit-reset]');
    var labelEl = document.querySelector('[data-admin-edit-label]');
    if (!toggleBtn) return;

    var lang = @json(\App\Services\AdminUiTextService::normalizeLocale(app()->getLocale()));
    var pathname = location.pathname || '/home';
    var saveUrl = '/super-admin/admin-ui-text';
    var resetUrl = '/super-admin/admin-ui-text/reset';
    var editLabel = @json(__('message.sa_edit'));
    var doneLabel = @json(__('message.done'));
    var editing = false;
    var remote = @json(\App\Services\AdminUiTextService::forPage(request()->getPathInfo() ?: '/home', app()->getLocale())) || {};
    remote.shell = (remote.shell && typeof remote.shell === 'object' && !Array.isArray(remote.shell)) ? remote.shell : {};
    remote.page = (remote.page && typeof remote.page === 'object' && !Array.isArray(remote.page)) ? remote.page : {};

    function readCookie(name) {
        var match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : '';
    }

    function csrfHeaders() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        var token = meta ? (meta.getAttribute('content') || '') : '';
        var xsrf = readCookie('XSRF-TOKEN');
        var headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
        if (token) headers['X-CSRF-TOKEN'] = token;
        if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;
        return headers;
    }

    function textKey(el) {
        return (el.getAttribute('data-admin-text-key') || '').trim();
    }

    function isShellKey(key) {
        return key.indexOf('nav.') === 0
            || key.indexOf('stat.') === 0
            || key.indexOf('brand.') === 0
            || key.indexOf('table.') === 0;
    }

    function textTargets() {
        var out = [];
        try {
            document.querySelectorAll('[data-admin-text-key]').forEach(function (el) {
                if (el.closest('[data-admin-edit-bar]')) return;
                if (!textKey(el)) return;
                out.push(el);
            });
        } catch (err) {
            console.warn('admin-ui-text targets', err);
        }
        return out;
    }

    function collectMaps() {
        var shell = {};
        var page = {};
        document.querySelectorAll('[data-admin-text-key]').forEach(function (el) {
            if (el.closest('[data-admin-edit-bar]')) return;
            var key = textKey(el);
            if (!key) return;
            var val = (el.textContent || '').replace(/\s+/g, ' ').trim();
            if (!val) return;
            // First non-empty wins — duplicate keys (sidebar + header) must not overwrite edits
            if (isShellKey(key)) {
                if (!Object.prototype.hasOwnProperty.call(shell, key)) shell[key] = val;
            } else if (!Object.prototype.hasOwnProperty.call(page, key)) {
                page[key] = val;
            }
        });
        return { shell: shell, page: page };
    }

    function applySaved() {
        try {
            var shell = remote.shell || {};
            var page = remote.page || {};
            textTargets().forEach(function (el) {
                var key = textKey(el);
                if (!key) return;
                if (Object.prototype.hasOwnProperty.call(page, key)) {
                    el.textContent = page[key];
                } else if (Object.prototype.hasOwnProperty.call(shell, key)) {
                    el.textContent = shell[key];
                }
            });
        } catch (err) {
            console.warn('admin-ui-text apply', err);
        }
    }

    function postJson(url, body) {
        var headers = csrfHeaders();
        headers['Content-Type'] = 'application/json';
        return fetch(url, {
            method: 'POST',
            headers: headers,
            credentials: 'same-origin',
            body: JSON.stringify(body)
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (json) {
                if (!res.ok || json.ok === false || json.status === false) {
                    var msg = (json && (json.message || json.error)) ? (json.message || json.error) : ('HTTP ' + res.status);
                    throw new Error(typeof msg === 'string' ? msg : 'Save failed');
                }
                return json;
            });
        });
    }

    function saveMaps() {
        return new Promise(function (resolve) {
            try {
                var maps = collectMaps();
                if (!Object.keys(maps.shell).length && !Object.keys(maps.page).length) {
                    resolve(false);
                    return;
                }
                remote.shell = Object.assign({}, remote.shell, maps.shell);
                remote.page = Object.assign({}, remote.page, maps.page);
                postJson(saveUrl, {
                    locale: lang,
                    pathname: pathname,
                    shell: maps.shell,
                    page: maps.page
                }).then(function (json) {
                    if (json && json.data) {
                        remote.shell = json.data.shell || remote.shell;
                        remote.page = json.data.page || remote.page;
                    }
                    resolve(true);
                }).catch(function (err) {
                    console.warn('admin-ui-text save failed', err);
                    alert('Text save failed: ' + (err && err.message ? err.message : 'please try again'));
                    resolve(false);
                });
            } catch (err) {
                alert('Text save failed. Please try again.');
                resolve(false);
            }
        });
    }

    function resetMaps() {
        return new Promise(function (resolve) {
            postJson(resetUrl, {
                locale: lang,
                pathname: pathname,
                all: true
            }).then(function () {
                window.location.reload();
                resolve(true);
            }).catch(function (err) {
                alert('Reset failed: ' + (err && err.message ? err.message : 'please try again'));
                resolve(false);
            });
        });
    }

    function freezeNav(on) {
        // Freeze navigation links only — keep More / dropdown toggles clickable so headers inside can be edited
        document.querySelectorAll('.pds-main-nav a').forEach(function (el) {
            if (el.closest('[data-admin-edit-bar]')) return;
            if (on) {
                if (!el.hasAttribute('data-admin-href')) {
                    el.setAttribute('data-admin-href', el.getAttribute('href') || '');
                }
                el.removeAttribute('href');
                el.setAttribute('data-admin-edit-frozen', '1');
            } else if (el.getAttribute('data-admin-edit-frozen') === '1' || el.hasAttribute('data-admin-href')) {
                var href = el.getAttribute('data-admin-href');
                if (href) el.setAttribute('href', href);
                el.removeAttribute('data-admin-href');
                el.removeAttribute('data-admin-edit-frozen');
            }
        });
    }

    function syncDuplicateKeys(source) {
        var key = textKey(source);
        if (!key) return;
        var val = (source.textContent || '').replace(/\s+/g, ' ').trim();
        document.querySelectorAll('[data-admin-text-key="' + key + '"]').forEach(function (el) {
            if (el === source) return;
            if ((el.textContent || '').replace(/\s+/g, ' ').trim() !== val) {
                el.textContent = val;
            }
        });
    }

    function setEditable(on) {
        try {
            freezeNav(on);
            textTargets().forEach(function (el) {
                if (on) {
                    el.setAttribute('contenteditable', 'true');
                    el.setAttribute('spellcheck', 'true');
                    el.classList.add('admin-text-edit');
                    if (!el.__adminUiSyncBound) {
                        el.addEventListener('input', function () { syncDuplicateKeys(el); });
                        el.__adminUiSyncBound = true;
                    }
                } else {
                    el.removeAttribute('contenteditable');
                    el.classList.remove('admin-text-edit');
                }
            });
        } catch (err) {
            console.warn('admin-ui-text setEditable', err);
        }
        document.body.classList.toggle('admin-panel-editing', on);
    }

    // More-menu overflow rebuilds DOM — re-apply editable state while Edit is on
    window.__pdsAdminUiRefreshEdit = function () {
        if (!editing) return;
        try {
            applySaved();
            setEditable(true);
        } catch (err) {
            console.warn('admin-ui-text refresh', err);
        }
    };

    function setMode(on) {
        editing = !!on;
        toggleBtn.disabled = false;
        toggleBtn.setAttribute('aria-pressed', editing ? 'true' : 'false');
        toggleBtn.classList.toggle('is-on', editing);
        if (labelEl) labelEl.textContent = editing ? doneLabel : editLabel;
        if (resetBtn) {
            resetBtn.hidden = !editing;
            resetBtn.disabled = false;
        }
        setEditable(editing);
        if (!editing) {
            toggleBtn.disabled = true;
            saveMaps().finally(function () {
                toggleBtn.disabled = false;
            });
        }
    }

    function guardNav(e) {
        if (!editing) return;
        if (e.target.closest && e.target.closest('[data-admin-edit-bar]')) return;
        if (e.target.closest && e.target.closest('[contenteditable="true"], [data-admin-edit-text]')) {
            var deadLink = e.target.closest('a');
            if (deadLink) e.preventDefault();
            return;
        }
        // Allow More / dropdown toggles so nested headers stay reachable while editing
        var dropdownToggle = e.target.closest
            ? e.target.closest('.pds-main-nav__link--dropdown, .pds-main-nav__mobile-toggle, [data-admin-edit-toggle], [data-admin-edit-reset]')
            : null;
        if (dropdownToggle) return;
        var link = e.target.closest ? e.target.closest('a[href], a[data-admin-href]') : null;
        if (!link) return;
        e.preventDefault();
        e.stopPropagation();
    }

    document.addEventListener('click', guardNav, true);
    document.addEventListener('auxclick', guardNav, true);

    toggleBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        setMode(!editing);
    });

    if (resetBtn) {
        resetBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            resetBtn.disabled = true;
            resetMaps().finally(function () { resetBtn.disabled = false; });
        });
    }

    document.addEventListener('keydown', function (e) {
        if (!editing) return;
        if (e.key === 'Escape') {
            e.preventDefault();
            setMode(false);
            return;
        }
        if (e.key === 'Enter') {
            var t = e.target;
            if (t && (t.tagName === 'TEXTAREA' || (t.isContentEditable && t.getAttribute('data-admin-multiline') === '1'))) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            if (t && t.blur) t.blur();
            setMode(false);
        }
    }, true);

    try {
        textTargets();
        applySaved();
    } catch (err) {
        console.warn('admin-ui-text init', err);
    }
})();
</script>
@endif
