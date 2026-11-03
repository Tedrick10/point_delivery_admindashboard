@php
    $saCssPath = public_path('css/super-admin-panel.css');
    $saCssVersion = file_exists($saCssPath) ? (string) filemtime($saCssPath) : (string) time();
    $saCssUrl = url('/css/super-admin-panel.css').'?v='.$saCssVersion;
    $saScreenKey = (string) request()->route('screen');
    $saChatMode = request()->routeIs([
        'inquiry.inbox',
        'complaint.inbox',
        'customersupport.show',
        'customersupport.*',
        'inquiry-auto-replies.*',
        'users.show',
        'users-view.show',
    ]);
    $saChatSupportType = null;
    if (request()->routeIs('customersupport.show')) {
        $saChatSupportId = (int) (request()->route('customersupport') ?? request()->route('id') ?? 0);
        if ($saChatSupportId > 0) {
            $saChatSupportType = \App\Models\CustomerSupport::query()->whereKey($saChatSupportId)->value('support_type');
        }
    }
    $saBodyClass = 'sa-panel'
        .(request()->routeIs('super-admin.screens.show') ? ' sa-page-module' : '')
        .(request()->routeIs('super-admin.screens.hub') ? ' sa-page-hub' : '')
        .($saScreenKey !== '' ? ' sa-screen-'.$saScreenKey : '')
        .($saChatMode ? ' pds-admin sa-chat-mode' : '');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('message.sa_super_admin')) — {{ appCopy('admin', 'title') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ getSingleMedia(appSettingData('get'), 'site_favicon', null) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Outfit:wght@400;500;600;700;800&family=Noto+Sans+Myanmar:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ $saCssUrl }}">
    <link rel="stylesheet" href="{{ url('/vendor/@fortawesome/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ public_asset_ver('css/image-drop-upload.css') }}">
    @if(!empty($saChatMode))
        @include('super-admin.partials.admin-chat-assets')
    @endif
    @stack('styles')
</head>
<body class="{{ $saBodyClass }}">
    <aside class="sa-sidebar">
        <div class="sa-brand">
            @php $saLogo = getSingleMedia(appSettingData('get'), 'site_logo', null); @endphp
            @if($saLogo)
                <img class="sa-brand-logo" src="{{ $saLogo }}" alt="">
            @else
                <span class="sa-brand-mark">SA</span>
            @endif
            <div class="sa-brand-copy">
                <div class="sa-brand-name" data-sa-edit-text="1" data-sa-text-key="nav.brand.name">{{ saUiText('nav.brand.name', appCopy('admin', 'title')) }}</div>
                <div class="sa-brand-sub" data-sa-edit-text="1" data-sa-text-key="nav.brand.sub">{{ saUiText('nav.brand.sub', __('message.sa_super_admin')) }}</div>
            </div>
        </div>
        <nav class="sa-nav">
            @include('super-admin.partials.sidebar-nav')
        </nav>
        <div class="sa-sidebar-foot">
            <a href="{{ route('super-admin.profile.show') }}"
               class="sa-user sa-user--link{{ request()->routeIs('super-admin.profile.*') ? ' is-active' : '' }}"
               title="{{ __('message.my_profile') }}">
                @php
                    $saFootUser = Auth::user();
                    $saFootAvatar = getSingleMedia($saFootUser, 'profile_image', null);
                    $saFootInitial = mb_strtoupper(mb_substr((string) ($saFootUser->name ?? 'S'), 0, 1));
                @endphp
                <span class="sa-user__avatar" aria-hidden="true">
                    @if($saFootAvatar)
                        <img src="{{ $saFootAvatar }}" alt="">
                    @else
                        {{ $saFootInitial }}
                    @endif
                </span>
                <span class="sa-user__meta">
                    <strong class="sa-user__name">{{ $saFootUser->name }}</strong>
                    <em class="sa-user__action">{{ __('message.my_profile') }}</em>
                </span>
            </a>
            <form method="POST" action="{{ route('super-admin.logout') }}">
                @csrf
                <button type="submit" class="sa-logout"><i class="fas fa-sign-out-alt"></i> <span data-sa-edit-text="1" data-sa-text-key="nav.logout">{{ saUiText('nav.logout', __('message.logout')) }}</span></button>
            </form>
        </div>
    </aside>
    <main class="sa-main">
        <header class="sa-topbar">
            <div class="sa-topbar-tools">
                @php
                    $saInquiryCount = function_exists('unansweredInquiryChatCount') ? unansweredInquiryChatCount() : 0;
                    $saComplaintCount = function_exists('unansweredComplaintChatCount') ? unansweredComplaintChatCount() : 0;
                @endphp
                <a href="{{ route('inquiry.inbox') }}"
                   class="sa-topbar-chat sa-topbar-chat--inquiry{{ request()->routeIs('inquiry.inbox') || $saChatSupportType === 'Inquiry' ? ' is-active' : '' }}"
                   title="{{ __('message.inquiry_chat') }}"
                   aria-label="{{ __('message.inquiry_chat') }}">
                    <i class="fas fa-comments" aria-hidden="true"></i>
                    <span class="sa-topbar-chat__badge{{ $saInquiryCount > 0 ? '' : ' d-none' }}"
                          id="saInquiryChatCount"
                          data-live-badge="inquiryChat">{{ $saInquiryCount > 0 ? ($saInquiryCount > 99 ? '99+' : $saInquiryCount) : '' }}</span>
                </a>
                <a href="{{ route('complaint.inbox') }}"
                   class="sa-topbar-chat sa-topbar-chat--complaint{{ request()->routeIs('complaint.inbox') || $saChatSupportType === 'Complaint' ? ' is-active' : '' }}"
                   title="{{ __('message.complaint_chat') }}"
                   aria-label="{{ __('message.complaint_chat') }}">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    <span class="sa-topbar-chat__badge{{ $saComplaintCount > 0 ? '' : ' d-none' }}"
                          id="saComplaintChatCount"
                          data-live-badge="complaintChat">{{ $saComplaintCount > 0 ? ($saComplaintCount > 99 ? '99+' : $saComplaintCount) : '' }}</span>
                </a>
                @include('super-admin.partials.notifications')
                @include('super-admin.partials.language-switcher')
                <div class="sa-topbar-meta">{{ now('Asia/Yangon')->locale(app()->getLocale())->translatedFormat('D, d M Y') }}</div>
            </div>
        </header>

        <div class="sa-content" id="saContentPanel" data-sa-panel>
        <header class="sa-pagehead">
            @php
                $saPageTitleDefault = trim($__env->yieldContent('page_title', __('message.dashboard')));
                $saPageSubDefault = $__env->hasSection('page_sub') ? trim($__env->yieldContent('page_sub')) : '';
            @endphp
            <div class="sa-pagehead__copy">
                <p data-sa-edit-text="1" data-sa-text-key="page.eyebrow">{{ saUiText('page.eyebrow', __('message.sa_super_admin'), 'page') }}</p>
                <h1 data-sa-edit-text="1" data-sa-text-key="page.title">{{ saUiText('page.title', $saPageTitleDefault, 'page') }}</h1>
                @if($saPageSubDefault !== '')
                    <span data-sa-edit-text="1" data-sa-text-key="page.sub">{{ saUiText('page.sub', $saPageSubDefault, 'page') }}</span>
                @endif
            </div>
        </header>

        @if(request()->routeIs('super-admin.dashboard'))
            @include('super-admin.partials.date-filter')
        @endif

        @if(session('success') && ! request()->routeIs('super-admin.screens.show'))
            <div class="sa-toast sa-toast--ok" role="status">
                <span class="sa-toast__icon" aria-hidden="true"><i class="fas fa-check"></i></span>
                <span class="sa-toast__text">{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="sa-toast sa-toast--err" role="alert">
                <span class="sa-toast__icon" aria-hidden="true"><i class="fas fa-exclamation"></i></span>
                <span class="sa-toast__text">{{ session('error') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div class="sa-toast sa-toast--err" role="alert">
                <span class="sa-toast__icon" aria-hidden="true"><i class="fas fa-exclamation"></i></span>
                <div class="sa-toast__text">
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
        </div>
    </main>
    @php
        $saOkScreens = ['delivery-route', 'kyo-shin', 'rider-remit', 'office-salary', 'rider-salary', 'late-fine'];
        $saOkMessage = (request()->routeIs('super-admin.screens.show') && in_array($saScreenKey, $saOkScreens, true))
            ? session('success')
            : null;
    @endphp
    <div class="sa-ok-modal" id="saOkModal" hidden>
        <div class="sa-ok-modal__backdrop" data-sa-ok-close></div>
        <div class="sa-ok-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="saOkModalTitle">
            <div class="sa-ok-modal__icon" aria-hidden="true"><i class="fas fa-check"></i></div>
            <h3 id="saOkModalTitle"></h3>
            <button type="button" class="sa-ok-modal__ok" data-sa-ok-close>{{ __('message.close') }}</button>
        </div>
    </div>
    <script>
    (function () {
        var modal = document.getElementById('saOkModal');
        var title = document.getElementById('saOkModalTitle');
        var timer = null;
        window.saShowSuccess = function (message) {
            if (!modal) return;
            if (title) title.textContent = message || '';
            modal.hidden = false;
            clearTimeout(timer);
            timer = setTimeout(function () { window.saHideSuccess(); }, 2600);
        };
        window.saHideSuccess = function () {
            if (!modal) return;
            modal.hidden = true;
            clearTimeout(timer);
        };
        document.querySelectorAll('[data-sa-ok-close]').forEach(function (el) {
            el.addEventListener('click', function () { window.saHideSuccess(); });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') window.saHideSuccess();
        });
        @if($saOkMessage)
            window.saShowSuccess(@json($saOkMessage));
        @endif
    })();
    </script>
    <script>
    (function () {
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });

        var active = document.querySelector('.sa-nav a.active');
        if (active && active.scrollIntoView) {
            active.scrollIntoView({ block: 'nearest' });
        }
    })();
    </script>
    <div class="sa-ui-editbar" data-sa-edit-bar>
        <button type="button"
                class="sa-ui-editbar__btn"
                id="saPanelEditBtn"
                data-sa-edit-toggle
                aria-pressed="false">
            <i class="fas fa-pen" aria-hidden="true"></i>
            <span data-sa-edit-label>{{ __('message.sa_edit') }}</span>
        </button>
        <button type="button"
                class="sa-ui-editbar__btn sa-ui-editbar__btn--ghost"
                id="saPanelEditReset"
                data-sa-edit-reset
                hidden>
            <i class="fas fa-undo" aria-hidden="true"></i>
            <span>{{ __('message.reset') }}</span>
        </button>
    </div>
    <script>
    (function () {
        var toggleBtn = document.querySelector('[data-sa-edit-toggle]');
        var resetBtn = document.querySelector('[data-sa-edit-reset]');
        var labelEl = document.querySelector('[data-sa-edit-label]');
        if (!toggleBtn) return;

        var lang = @json(\App\Services\SaUiTextService::normalizeLocale(app()->getLocale()));
        var pathname = location.pathname || '/super-admin';
        // Relative URLs avoid APP_URL host mismatch (localhost vs 127.0.0.1)
        var saveUrl = '/super-admin/ui-text';
        var resetUrl = '/super-admin/ui-text/reset';
        var editLabel = @json(__('message.sa_edit'));
        var doneLabel = @json(__('message.done'));
        var editing = false;
        var remote = @json(\App\Services\SaUiTextService::forPage(request()->getPathInfo() ?: '/super-admin', app()->getLocale())) || {};
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

        function isShellEl(el) {
            return !!(el && el.closest && el.closest('.sa-sidebar'));
        }

        function textKey(el) {
            return (el.getAttribute('data-sa-text-key') || '').trim();
        }

        // Only elements with stable data-sa-text-key (sidebar + page head) — never table/action buttons
        function textTargets() {
            var out = [];
            try {
                document.querySelectorAll('[data-sa-text-key]').forEach(function (el) {
                    if (el.closest('[data-sa-edit-bar]')) return;
                    if (el.closest('.sa-user')) return;
                    if (!textKey(el)) return;
                    out.push(el);
                });
            } catch (err) {
                console.warn('sa-ui-text targets', err);
            }
            return out;
        }

        function collectMaps() {
            var shell = {};
            var page = {};
            // Only stable keys — these sync across accounts via DB
            document.querySelectorAll('[data-sa-text-key]').forEach(function (el) {
                if (el.closest('[data-sa-edit-bar]')) return;
                if (el.closest('.sa-user')) return;
                var key = (el.getAttribute('data-sa-text-key') || '').trim();
                if (!key) return;
                var val = (el.textContent || '').replace(/\s+/g, ' ').trim();
                if (!val) return;
                if (key.indexOf('nav.') === 0 || key.indexOf('table.') === 0 || isShellEl(el)) shell[key] = val;
                else page[key] = val;
            });
            return { shell: shell, page: page };
        }

        function applySaved() {
            // Server already renders saUiText() into HTML; JS apply is a safety net
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
                console.warn('sa-ui-text apply', err);
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
                        console.warn('sa-ui-text: nothing to save');
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
                        try {
                            localStorage.removeItem('sa-shell-text:' + lang);
                            localStorage.removeItem('sa-panel-text:' + pathname + ':' + lang);
                        } catch (e) {}
                        resolve(true);
                    }).catch(function (err) {
                        console.warn('sa-ui-text save failed', err);
                        alert('Text save failed: ' + (err && err.message ? err.message : 'please try again'));
                        resolve(false);
                    });
                } catch (err) {
                    console.warn('sa-ui-text save', err);
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
                    try {
                        localStorage.removeItem('sa-shell-text:' + lang);
                        localStorage.removeItem('sa-panel-text:' + pathname + ':' + lang);
                    } catch (e) {}
                    window.location.reload();
                    resolve(true);
                }).catch(function (err) {
                    alert('Reset failed: ' + (err && err.message ? err.message : 'please try again'));
                    resolve(false);
                });
            });
        }

        function guardNav(e) {
            if (!editing) return;
            if (e.target.closest && e.target.closest('[data-sa-edit-bar]')) return;
            // Let caret land on contenteditable text
            if (e.target.closest && e.target.closest('[contenteditable="true"], [data-sa-edit-text]')) {
                var deadLink = e.target.closest('a');
                if (deadLink) {
                    e.preventDefault();
                }
                return;
            }
            var link = e.target.closest ? e.target.closest('a[href], a[data-sa-href]') : null;
            var btn = e.target.closest ? e.target.closest('button, [type="submit"]') : null;
            if (!link && !btn) return;
            if (btn && btn.closest('[data-sa-edit-bar]')) return;
            e.preventDefault();
            e.stopPropagation();
        }

        function onDocSubmit(e) {
            if (!editing) return;
            if (e.target.closest && e.target.closest('[data-sa-edit-bar]')) return;
            e.preventDefault();
            e.stopPropagation();
        }

        function freezeSidebarLinks(on) {
            document.querySelectorAll('.sa-sidebar a').forEach(function (a) {
                if (on) {
                    if (!a.hasAttribute('data-sa-href')) {
                        a.setAttribute('data-sa-href', a.getAttribute('href') || '');
                    }
                    // Remove href entirely — "#" still navigates / reloads hash
                    a.removeAttribute('href');
                    a.setAttribute('data-sa-edit-frozen', '1');
                    a.setAttribute('tabindex', '-1');
                } else if (a.getAttribute('data-sa-edit-frozen') === '1') {
                    var href = a.getAttribute('data-sa-href');
                    if (href) a.setAttribute('href', href);
                    a.removeAttribute('data-sa-href');
                    a.removeAttribute('data-sa-edit-frozen');
                    a.removeAttribute('tabindex');
                }
            });
        }

        function setEditable(on) {
            try {
                freezeSidebarLinks(on);
                textTargets().forEach(function (el) {
                    if (on) {
                        el.setAttribute('contenteditable', 'true');
                        el.setAttribute('spellcheck', 'true');
                        el.classList.add('sa-text-edit');
                    } else {
                        el.removeAttribute('contenteditable');
                        el.classList.remove('sa-text-edit');
                    }
                });
            } catch (err) {
                console.warn('sa-ui-text setEditable', err);
            }
            document.body.classList.toggle('sa-panel-editing', on);
        }

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

        document.addEventListener('click', guardNav, true);
        document.addEventListener('auxclick', guardNav, true);
        document.addEventListener('submit', onDocSubmit, true);

        toggleBtn.disabled = false;
        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            try {
                setMode(!editing);
            } catch (err) {
                console.warn('sa-ui-text toggle', err);
                toggleBtn.disabled = false;
            }
        });

        if (resetBtn) {
            resetBtn.disabled = false;
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
            // Enter = save (same as Done), except inside multiline fields
            if (e.key === 'Enter') {
                var t = e.target;
                if (t && (t.tagName === 'TEXTAREA' || t.isContentEditable && t.getAttribute('data-sa-multiline') === '1')) {
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
            console.warn('sa-ui-text init', err);
        }
    })();
    </script>
    <script src="{{ public_asset_ver('js/rabbit.js') }}"></script>
    <script src="{{ public_asset_ver('js/myanmar-text.js') }}"></script>
    <script>window.PDS_IMAGE_DROP_LABEL = @json(__('message.image_drop_hint'));</script>
    <script src="{{ public_asset_ver('js/image-drop-upload.js') }}"></script>
    @if(!empty($saChatMode))
        <script src="{{ public_asset_ver('js/backend-bundle.min.js') }}"></script>
        <script src="{{ public_asset_ver('js/admin-live.js') }}"></script>
        <script type="module" src="{{ asset('vendor/emoji-picker-element/index.js') }}"></script>
        <script>
        (function bootSaChatBadges() {
            if (typeof window.jQuery === 'undefined' || typeof window.bootAdminGlobalLive !== 'function') return;
            window.bootAdminGlobalLive({
                stateUrl: @json(route('admin.live.state')),
                intervalMs: 4000,
                page: @json(optional(request()->route())->getName() ?? 'super-admin'),
                includeDashboard: false,
                refreshContent: false,
                hardReloadFallback: false
            });
        })();
        </script>
    @endif
    @stack('scripts')
    @yield('bottom_script')
    @stack('bottom_script')
</body>
</html>
