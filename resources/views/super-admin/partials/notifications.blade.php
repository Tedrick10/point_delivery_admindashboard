@php
    $saNotifyCount = function_exists('adminNotificationUnreadCount')
        ? adminNotificationUnreadCount(auth()->user(), 'super_admin')
        : 0;
@endphp
<div class="sa-notify" id="saNotify">
    <button type="button"
            class="sa-notify__btn"
            id="saNotifyToggle"
            aria-haspopup="true"
            aria-expanded="false"
            title="{{ __('message.notification') }}">
        <i class="fas fa-bell" aria-hidden="true"></i>
        <span class="sa-notify__badge{{ $saNotifyCount > 0 ? '' : ' is-empty' }}" id="saNotifyBadge">
            {{ $saNotifyCount > 99 ? '99+' : ($saNotifyCount > 0 ? $saNotifyCount : '') }}
        </span>
    </button>
    <div class="sa-notify__panel" id="saNotifyPanel" hidden>
        <div class="sa-notify__head">
            <strong>{{ __('message.notification') }}</strong>
            <div class="sa-notify__head-actions">
                <button type="button" class="sa-notify__mark" id="saNotifyMarkRead" hidden>
                    {{ __('message.mark_all_as_read') }}
                </button>
                <button type="button" class="sa-notify__close" id="saNotifyClose" aria-label="{{ __('message.close') }}">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="sa-notify__meta" id="saNotifyMeta">
            {{ __('message.you_have_unread_notification', ['number' => $saNotifyCount]) }}
        </div>
        <div class="sa-notify__body" id="saNotifyBody">
            <div class="sa-notify__loading">…</div>
        </div>
    </div>
</div>
<script>
(function () {
    var root = document.getElementById('saNotify');
    var toggle = document.getElementById('saNotifyToggle');
    var panel = document.getElementById('saNotifyPanel');
    var body = document.getElementById('saNotifyBody');
    var badge = document.getElementById('saNotifyBadge');
    var meta = document.getElementById('saNotifyMeta');
    var markBtn = document.getElementById('saNotifyMarkRead');
    var closeBtn = document.getElementById('saNotifyClose');
    if (!root || !toggle || !panel || !body) return;

    var listUrl = @json(route('notification.list'));
    var countsUrl = @json(route('notification.counts'));
    var readUrlTpl = @json(url('/notification/__ID__/read'));
    var unreadTpl = @json(__('message.you_have_unread_notification', ['number' => '__N__']));
    var resUnreadText = '';
    var open = false;
    var loading = false;

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function setBadge(n) {
        var count = parseInt(n, 10) || 0;
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.classList.remove('is-empty');
            if (markBtn) markBtn.hidden = false;
        } else {
            badge.textContent = '';
            badge.classList.add('is-empty');
            if (markBtn) markBtn.hidden = true;
        }
        if (meta && typeof resUnreadText === 'string' && resUnreadText) {
            meta.textContent = resUnreadText;
        } else if (meta) {
            meta.textContent = unreadTpl.replace('__N__', String(count));
        }
    }

    function openPanel() {
        open = true;
        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        root.classList.add('is-open');
        loadList();
    }

    function closePanel() {
        open = false;
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        root.classList.remove('is-open');
    }

    function loadList(type) {
        if (loading) return;
        loading = true;
        body.innerHTML = '<div class="sa-notify__loading">…</div>';
        var url = listUrl + '?panel=super_admin&category=all' + (type ? '&type=' + encodeURIComponent(type) : '');
        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (res) {
            body.innerHTML = res.data || '<div class="sa-notify__empty">{{ __('message.no_notification') }}</div>';
            if (res.unread_text) resUnreadText = res.unread_text;
            if (typeof res.unread_count !== 'undefined') setBadge(res.unread_count);
            bindOpenLinks();
        }).catch(function () {
            body.innerHTML = '<div class="sa-notify__empty">{{ __('message.no_notification') }}</div>';
        }).finally(function () { loading = false; });
    }

    function refreshCount() {
        fetch(countsUrl + '?panel=super_admin', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (res) {
            var n = res.unread_total_count ?? res.counts ?? 0;
            setBadge(n);
        }).catch(function () {});
    }

    function bindOpenLinks() {
        body.querySelectorAll('.js-notify-open').forEach(function (el) {
            el.addEventListener('click', function () {
                var id = el.getAttribute('data-notification-id');
                if (!id) return;
                fetch(readUrlTpl.replace('__ID__', encodeURIComponent(id)) + '?panel=super_admin', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf()
                    },
                    credentials: 'same-origin'
                }).then(function (r) { return r.json(); }).then(function (res) {
                    if (typeof res.unread_count !== 'undefined') setBadge(res.unread_count);
                }).catch(function () {});
            });
        });
    }

    toggle.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (open) closePanel(); else openPanel();
    });
    if (closeBtn) closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        closePanel();
    });
    if (markBtn) markBtn.addEventListener('click', function (e) {
        e.preventDefault();
        loadList('markas_read');
    });
    document.addEventListener('click', function (e) {
        if (open && !root.contains(e.target)) closePanel();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && open) closePanel();
    });

    refreshCount();
    setInterval(refreshCount, 20000);
})();
</script>
