<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-os-list-page">
        <div class="pds-os-list-screen">
            <div class="pds-os-list-hero">
                <div class="pds-os-list-hero__copy">
                    <div class="pds-os-list-hero__eyebrow">
                        <i class="fas fa-store" aria-hidden="true"></i>
                        <span>{{ __('message.online_shop') }}</span>
                    </div>
                    <h4 class="pds-os-list-hero__title">{{ $pageTitle ?? __('message.online_shop') }}</h4>
                </div>
                <div class="pds-os-list-hero__actions">
                    @if(isset($export))
                        {!! $export !!}
                    @endif
                    @if(isset($button))
                        {!! $button !!}
                    @endif
                </div>
            </div>

            <div class="pds-os-list-body">
                @php
                    $approvalTab = $approvalTab ?? 'pending';
                    $approvalCounts = $approvalCounts ?? ['pending' => 0, 'approved' => 0, 'rejected' => 0];
                    $tabQuery = request()->except('status');
                @endphp
                @include('partials._branch-tabs', [
                    'branchTabs' => $branchTabs ?? collect(),
                    'selectedBranchId' => ($selectedBranchId ?? 0) > 0 ? $selectedBranchId : null,
                    'branchTabCounts' => $branchTabCounts ?? [],
                    'includeAll' => false,
                    'routeName' => 'users.index',
                    'routeQuery' => array_filter(['status' => $approvalTab]),
                ])
                <div class="pds-os-list-tabs" role="tablist" aria-label="{{ __('message.approval_status') }}">
                    @foreach([
                        'pending' => ['label' => __('message.pending'), 'icon' => 'fas fa-clock'],
                        'approved' => ['label' => __('message.approved'), 'icon' => 'fas fa-check-circle'],
                        'rejected' => ['label' => __('message.rejected'), 'icon' => 'fas fa-times-circle'],
                        'kyo_shin' => ['label' => __('message.kyo_shin_title'), 'icon' => 'fas fa-hand-holding-usd'],
                    ] as $tabKey => $tabMeta)
                        <a
                            href="{{ route('users.index', array_merge($tabQuery, ['status' => $tabKey])) }}"
                            class="pds-os-list-tab {{ $approvalTab === $tabKey ? 'is-active' : '' }}{{ $tabKey === 'kyo_shin' ? ' is-kyo-shin' : '' }}"
                            data-tab="{{ $tabKey }}"
                            role="tab"
                            aria-selected="{{ $approvalTab === $tabKey ? 'true' : 'false' }}"
                        >
                            <i class="{{ $tabMeta['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $tabMeta['label'] }}</span>
                            <em>{{ $approvalCounts[$tabKey] ?? 0 }}</em>
                        </a>
                    @endforeach
                </div>

                <div class="pds-os-list-toolbar">
                    <div class="pds-os-list-toolbar__search">
                        @include('global.user-datatable')
                    </div>
                    @if(isset($multi_checkbox_delete))
                        <div class="pds-os-list-toolbar__bulk pds-os-list-bulk">
                            {!! $multi_checkbox_delete !!}
                        </div>
                    @endif
                </div>

                <div class="pds-os-list-table-shell">
                    {{ $dataTable->table(['class' => 'table w-100 pds-datatable pds-os-list-table'], false) }}
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="osRejectReasonModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('message.rejected') }} — {{ __('message.os_rejection_reason_label') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-2">{{ __('message.os_rejection_reason_prompt') }}</p>
                    <label class="font-weight-bold" for="osRejectReasonInput">{{ __('message.os_rejection_reason_label') }} <span class="text-danger">*</span></label>
                    <textarea id="osRejectReasonInput" class="form-control" rows="4" maxlength="1000" placeholder="{{ __('message.os_rejection_reason_prompt') }}"></textarea>
                    <div class="text-danger small mt-2 d-none" id="osRejectReasonError">{{ __('message.os_rejection_reason_required') }}</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="osRejectReasonCancel" data-dismiss="modal">{{ __('message.cancel') }}</button>
                    <button type="button" class="btn btn-danger" id="osRejectReasonSubmit">{{ __('message.reject') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Inline: @section is skipped by x-master-layout component. --}}
    {{ $dataTable->scripts() }}
    <script>
    (function bootOsApprovalList() {
        function withJquery(cb) {
            if (window.jQuery) return cb(window.jQuery);
            setTimeout(function () { withJquery(cb); }, 40);
        }
        withJquery(function ($) {
            if (window.__pdsOsApprovalHandlersBound) return;
            window.__pdsOsApprovalHandlersBound = true;

            var pendingReject = null;

            function notify(msg, status) {
                if (!msg) return;
                if (typeof SnackBar === 'function') SnackBar({ message: msg, status: status || 'error' });
                else if (window.toastr) status === 'success' ? toastr.success(msg) : toastr.error(msg);
            }

            function currentApprovalTab() {
                try {
                    return new URLSearchParams(window.location.search).get('status') || 'pending';
                } catch (e) {
                    return 'pending';
                }
            }

            function goToApprovalTab(tone) {
                try {
                    var url = new URL(window.location.href);
                    url.searchParams.set('status', tone);
                    window.location.href = url.pathname + '?' + url.searchParams.toString();
                } catch (e) {
                    window.location.search = 'status=' + encodeURIComponent(tone);
                }
            }

            function postApproval($el, value, reason) {
                $el.prop('disabled', true);
                $el.attr('data-tone', value);
                var data = {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    approval_status: value
                };
                if (reason) data.approval_rejection_reason = reason;
                $.ajax({
                    url: $el.data('url'),
                    method: 'POST',
                    data: data
                }).done(function (res) {
                    var msg = res.message || 'Updated';
                    if (res.username && res.password) {
                        msg += '  Username: ' + res.username + '  Password: ' + res.password;
                    }
                    notify(msg, 'success');
                    var tone = (res.approval_status || value || '').toString();
                    var tab = currentApprovalTab();
                    // Leaving Rejected (or any tab) → jump so the shop stays visible.
                    if (tone && tab !== 'kyo_shin' && tab !== tone) {
                        goToApprovalTab(tone);
                        return;
                    }
                    if ($.fn.DataTable && $.fn.DataTable.isDataTable('.dataTable')) {
                        $('.dataTable').DataTable().ajax.reload(null, false);
                    }
                }).fail(function (xhr) {
                    var prev = $el.data('prev') || 'pending';
                    $el.val(prev).attr('data-tone', prev);
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Update failed';
                    notify(msg, 'error');
                }).always(function () {
                    $el.prop('disabled', false);
                });
            }

            $(document).on('focus', '.js-os-approval-status', function () {
                $(this).data('prev', $(this).val());
            });

            $(document).on('change', '.js-os-approval-status', function () {
                var $el = $(this);
                var value = $el.val();
                if (value === 'rejected') {
                    pendingReject = $el;
                    $('#osRejectReasonInput').val('');
                    $('#osRejectReasonError').addClass('d-none');
                    $('#osRejectReasonModal').modal('show');
                    return;
                }
                postApproval($el, value, null);
            });

            $(document).on('click', '#osRejectReasonCancel', function () {
                if (pendingReject) {
                    var prev = pendingReject.data('prev') || 'pending';
                    pendingReject.val(prev).attr('data-tone', prev);
                    pendingReject = null;
                }
            });

            $('#osRejectReasonModal').on('hidden.bs.modal', function () {
                if (pendingReject) {
                    var prev = pendingReject.data('prev') || 'pending';
                    pendingReject.val(prev).attr('data-tone', prev);
                    pendingReject = null;
                }
            });

            $(document).on('click', '#osRejectReasonSubmit', function () {
                var reason = $.trim($('#osRejectReasonInput').val() || '');
                if (!reason) {
                    $('#osRejectReasonError').removeClass('d-none');
                    return;
                }
                $('#osRejectReasonError').addClass('d-none');
                var $el = pendingReject;
                pendingReject = null;
                $('#osRejectReasonModal').modal('hide');
                if ($el) postApproval($el, 'rejected', reason);
            });

            $(document).on('change', '.js-os-kyo-shin-flag', function () {
                var $el = $(this);
                var $wrap = $el.closest('.pds-os-kyo-shin-switch');
                var enabled = $el.is(':checked') ? 1 : 0;
                $wrap.toggleClass('is-on', !!enabled);
                $el.prop('disabled', true);
                $.ajax({
                    url: $el.data('url'),
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        is_kyo_shin: enabled
                    }
                }).done(function (res) {
                    notify(res.message || 'Updated', 'success');
                    if ($.fn.DataTable && $.fn.DataTable.isDataTable('.dataTable')) {
                        $('.dataTable').DataTable().ajax.reload(null, false);
                    }
                }).fail(function (xhr) {
                    $el.prop('checked', !enabled);
                    $wrap.toggleClass('is-on', !enabled);
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Update failed';
                    notify(msg, 'error');
                }).always(function () {
                    $el.prop('disabled', false);
                });
            });
        });
    })();
    </script>
</x-master-layout>
