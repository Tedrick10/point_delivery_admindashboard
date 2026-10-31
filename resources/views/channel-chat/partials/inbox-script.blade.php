<script>
    (function bootChannelInbox() {
        function withJquery(cb) {
            if (window.jQuery) return cb(window.jQuery);
            setTimeout(function () { withJquery(cb); }, 40);
        }

        withJquery(function ($) {
            $(document).ready(function () {
                var allowedMsgTabs = ['unread', 'unanswered', 'answered'];
                var channel = @json($channel ?? 'Inquiry');
                var storageKey = 'pdsChannelMsgTab_' + channel;
                var liveUrl = @json(($channel ?? '') === 'Complaint' ? route('complaint.inbox.live') : route('inquiry.inbox.live'));
                var savedMsgTab = '';
                try { savedMsgTab = String(sessionStorage.getItem(storageKey) || ''); } catch (e) {}
                var activeMsgTab = allowedMsgTabs.indexOf(savedMsgTab) !== -1 ? savedMsgTab : 'unread';
                var lastVersion = String($('#adminLiveRoot').attr('data-inbox-version') || '');
                var pollTimer = null;
                var inFlight = false;

                function applyFilters() {
                    var visible = 0;
                    var $cards = $('#channelTableShell .pds-msg-thread-card');
                    if (!$cards.length) {
                        $('#channelFilterEmptyState').addClass('d-none');
                        $('#channelEmptyState').removeClass('d-none');
                        $('#channelTableShell').addClass('d-none');
                        return;
                    }
                    $cards.each(function () {
                        var $row = $(this);
                        var isUnread = String($row.data('unread') || '0') === '1';
                        var isUnanswered = String($row.data('unanswered') || '0') === '1';
                        var isAnswered = String($row.data('answered') || '0') === '1';
                        var matchTab = (activeMsgTab === 'unread' && isUnread)
                            || (activeMsgTab === 'unanswered' && isUnanswered)
                            || (activeMsgTab === 'answered' && isAnswered);
                        $row.toggleClass('d-none', !matchTab);
                        if (matchTab) visible += 1;
                    });
                    $('#channelEmptyState').addClass('d-none');
                    $('#channelFilterEmptyState').toggleClass('d-none', visible !== 0);
                    $('#channelTableShell').toggleClass('d-none', visible === 0);
                }

                function setTopbarBadge(selector, count) {
                    var $el = $(selector);
                    if (!$el.length) return;
                    count = Number(count) || 0;
                    if (count > 0) {
                        $el.text(count >= 100 ? '99+' : String(count)).removeClass('d-none');
                    } else {
                        $el.text('').addClass('d-none');
                    }
                }

                function applyTabCounts(counts) {
                    if (!counts) return;
                    Object.keys(counts).forEach(function (key) {
                        $('[data-count-for="' + key + '"]').text(String(Number(counts[key]) || 0));
                    });
                }

                function pollLive() {
                    if (document.hidden || inFlight || !liveUrl) return;
                    inFlight = true;
                    $.ajax({
                        url: liveUrl,
                        method: 'GET',
                        dataType: 'json',
                        cache: false
                    }).done(function (res) {
                        if (!res || !res.ok) return;
                        applyTabCounts(res.tab_counts);
                        setTopbarBadge('#inquiryChatCount', res.badge_count);
                        if (channel === 'Complaint') {
                            setTopbarBadge('#saComplaintChatCount', res.badge_count);
                        } else {
                            setTopbarBadge('#saInquiryChatCount', res.badge_count);
                        }
                        var version = String(res.version || '');
                        if (!version) return;
                        if (!lastVersion) {
                            lastVersion = version;
                            return;
                        }
                        if (version === lastVersion) return;
                        lastVersion = version;
                        if (typeof res.html === 'string') {
                            $('#channelInboxBody').html(res.html);
                            $('#adminLiveRoot').attr('data-inbox-version', version);
                            applyFilters();
                        }
                    }).always(function () {
                        inFlight = false;
                    });
                }

                $('#channelMsgTabs').on('click', '[data-msg-tab]', function () {
                    var tab = String($(this).data('msg-tab') || 'unread');
                    if (allowedMsgTabs.indexOf(tab) === -1) tab = 'unread';
                    activeMsgTab = tab;
                    try { sessionStorage.setItem(storageKey, tab); } catch (e) {}
                    $('#channelMsgTabs .pds-msg-tabs__btn').removeClass('is-active');
                    $(this).addClass('is-active');
                    applyFilters();
                });

                $('#channelMsgTabs .pds-msg-tabs__btn').removeClass('is-active');
                $('#channelMsgTabs [data-msg-tab="' + activeMsgTab + '"]').addClass('is-active');
                applyFilters();

                pollLive();
                pollTimer = setInterval(pollLive, 4000);
                document.addEventListener('visibilitychange', function () {
                    if (!document.hidden) pollLive();
                });
                $(window).on('beforeunload.channelInbox', function () {
                    if (pollTimer) clearInterval(pollTimer);
                });
            });
        });
    })();
</script>
