<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-to-assign-page pds-os-receive-page">
        <div class="pds-dispatch-to-assign-screen pds-rider-screen">
            <div class="pds-rider-hero">
                <div class="pds-rider-hero__copy">
                    <div class="pds-rider-hero__eyebrow">
                        <i class="fas fa-hand-holding-usd" aria-hidden="true"></i>
                        <span>{{ __('message.order') }}</span>
                    </div>
                    <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                    <p class="pds-rider-hero__subtitle">{{ __('message.os_receive_screen_subtitle') }}</p>
                </div>
                <div class="pds-rider-hero__stat">
                    <span class="pds-rider-hero__stat-value">{{ $statCount }}</span>
                    <span class="pds-rider-hero__stat-label">{{ __('message.invoice') }}</span>
                </div>
            </div>

            @php
                $tab = $tab ?? 'open';
                $openCount = $openCount ?? 0;
                $receivedCount = $receivedCount ?? 0;
            @endphp
            <div class="pds-os-settlement-tabs pds-os-receive-tabs" role="tablist" aria-label="{{ __('message.os_receive_screen_title') }}">
                <a
                    href="{{ route('order.os-receive', ['tab' => 'open']) }}"
                    class="pds-os-settlement-tab {{ $tab === 'open' ? 'is-active' : '' }}"
                    role="tab"
                    aria-selected="{{ $tab === 'open' ? 'true' : 'false' }}"
                >
                    <i class="fas fa-hand-holding-usd" aria-hidden="true"></i>
                    <span>{{ __('message.os_receive_open_tab') }}</span>
                    <em>{{ $openCount }}</em>
                </a>
                <a
                    href="{{ route('order.os-receive', ['tab' => 'received']) }}"
                    class="pds-os-settlement-tab {{ $tab === 'received' ? 'is-active' : '' }}"
                    data-tab="receive"
                    role="tab"
                    aria-selected="{{ $tab === 'received' ? 'true' : 'false' }}"
                >
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <span>{{ __('message.os_receive_done_tab') }}</span>
                    <em>{{ $receivedCount }}</em>
                </a>
            </div>

            <div class="pds-rider-body">
                @if($items->isEmpty())
                    <div class="pds-money-transfer-empty">
                        <div class="pds-money-transfer-empty__icon"><i class="fas fa-inbox"></i></div>
                        <p>
                            {{ $tab === 'received'
                                ? __('message.os_receive_done_empty')
                                : __('message.os_receive_open_empty') }}
                        </p>
                    </div>
                @else
                    <div class="pds-rider-table-shell pds-rider-table-shell--scroll">
                        <table class="table pds-rider-list-table pds-cash-payout-table">
                            <thead>
                                <tr>
                                    <th>{{ __('message.no') }}</th>
                                    <th>{{ __('message.os_name') }}</th>
                                    <th class="text-right">{{ __('message.amount') }}</th>
                                    <th>{{ __('message.os_settlement_qr') }}</th>
                                    <th>{{ __('message.os_receive_payslip') }}</th>
                                    <th>{{ __('message.status') }}</th>
                                    <th>{{ __('message.remark') }}</th>
                                    <th>{{ __('message.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowNo = 0; @endphp
                                @foreach($groupedItems as $finishedDay => $dayItems)
                                    <tr class="pds-os-receive-date-group">
                                        <td colspan="8">
                                            <div class="pds-os-receive-date-group__label">
                                                <i class="far fa-calendar-check" aria-hidden="true"></i>
                                                <span>
                                                    @if($finishedDay === 'unknown')
                                                        —
                                                    @else
                                                        {{ \Carbon\Carbon::parse($finishedDay)->timezone('Asia/Yangon')->format('jS M, Y') }}
                                                    @endif
                                                </span>
                                                <em>{{ $dayItems->count() }}</em>
                                            </div>
                                        </td>
                                    </tr>
                                    @foreach($dayItems as $item)
                                        @php
                                            $rowNo++;
                                            $os = $item->osUser;
                                            $osName = $os->name ?? ('OS #'.$item->os_user_id);
                                            $city = trim((string) ($os->city->name ?? ''));
                                            if ($city !== '') {
                                                $osName .= ' ('.$city.')';
                                            }
                                            $qr = $item->adminQrUrl();
                                            $payslipUrls = $item->osPayslipUrls();
                                            $statusLabel = match ($item->status) {
                                                'waiting' => __('message.os_receive_waiting'),
                                                'received' => __('message.os_receive_received'),
                                                'rejected' => __('message.rejected'),
                                                default => __('message.pending'),
                                            };
                                        @endphp
                                        <tr data-id="{{ $item->id }}">
                                            <td>{{ $rowNo }}</td>
                                            <td>
                                                <div class="pds-cash-payout-os__name">{{ $osName }}</div>
                                                @if($os?->contact_number)
                                                    <div class="pds-dispatch-rider-list-phone">{{ $os->contact_number }}</div>
                                                @endif
                                            </td>
                                            <td class="text-right pds-cash-payout-due">{{ number_format($item->displayAmount()) }}</td>
                                            <td class="pds-cash-payout-photo-cell">
                                                @if($qr)
                                                    <a href="{{ $qr }}" target="_blank" rel="noopener" class="pds-cash-payout-photo" title="{{ __('message.os_settlement_qr') }}">
                                                        <img src="{{ $qr }}" alt="{{ __('message.os_settlement_qr') }}">
                                                    </a>
                                                @else
                                                    <span class="pds-cash-payout-photo-empty"><i class="fas fa-qrcode"></i></span>
                                                @endif
                                            </td>
                                            <td class="pds-cash-payout-photo-cell">
                                                @if(count($payslipUrls) > 0)
                                                    <div class="pds-cash-payout-photos">
                                                        @foreach($payslipUrls as $payslipUrl)
                                                            <a href="{{ $payslipUrl }}" target="_blank" rel="noopener" class="pds-cash-payout-photo" title="{{ __('message.os_receive_payslip') }} {{ $loop->iteration }}">
                                                                <img src="{{ $payslipUrl }}" alt="{{ __('message.os_receive_payslip') }} {{ $loop->iteration }}">
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="pds-cash-payout-photo-empty"><i class="far fa-image"></i></span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="pds-cash-payout-status {{ $item->status === 'waiting' ? 'is-pending' : ($item->status === 'received' ? 'is-done' : ($item->status === 'rejected' ? 'is-unassigned' : 'is-assigned')) }}">
                                                    {{ $statusLabel }}
                                                </span>
                                            </td>
                                            <td>
                                                @if(filled($item->admin_remark))
                                                    <div class="pds-cash-payout-photo-caption">{{ $item->admin_remark }}</div>
                                                @else
                                                    <span class="pds-cash-payout-empty">—</span>
                                                @endif
                                            </td>
                                            <td class="pds-cash-payout-actions">
                                                @if($canEdit && $item->status === 'waiting')
                                                    <button type="button" class="pds-cash-payout-btn pds-cash-payout-btn--ok js-os-receive-approve" data-id="{{ $item->id }}">
                                                        {{ __('message.os_receive_approve_btn') }}
                                                    </button>
                                                    <button type="button" class="pds-cash-payout-btn pds-cash-payout-btn--warn js-os-receive-reject" data-id="{{ $item->id }}">
                                                        {{ __('message.os_receive_reject_btn') }}
                                                    </button>
                                                @else
                                                    <span class="pds-cash-payout-empty">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Approve confirm --}}
    <div class="pds-os-receive-modal" id="osReceiveApproveModal" hidden>
        <div class="pds-os-receive-modal__backdrop" data-close="approve"></div>
        <div class="pds-os-receive-modal__dialog" role="dialog" aria-modal="true">
            <header class="pds-os-receive-modal__header">
                <div class="pds-os-receive-modal__icon is-ok"><i class="fas fa-check-circle"></i></div>
                <h5>{{ __('message.os_receive_approve_btn') }}</h5>
            </header>
            <p class="pds-os-receive-modal__text">{{ __('message.os_receive_confirm_approve') }}</p>
            <footer class="pds-os-receive-modal__footer">
                <button type="button" class="pds-os-receive-modal__btn is-ghost" id="osReceiveApproveCancel">{{ __('message.cancel') }}</button>
                <button type="button" class="pds-os-receive-modal__btn is-ok" id="osReceiveApproveOk">{{ __('message.yes') }}</button>
            </footer>
        </div>
    </div>

    {{-- Reject remark --}}
    <div class="pds-os-receive-modal" id="osReceiveRejectModal" hidden>
        <div class="pds-os-receive-modal__backdrop" data-close="reject"></div>
        <div class="pds-os-receive-modal__dialog" role="dialog" aria-modal="true">
            <header class="pds-os-receive-modal__header">
                <div class="pds-os-receive-modal__icon is-warn"><i class="fas fa-times-circle"></i></div>
                <h5>{{ __('message.os_receive_reject_btn') }}</h5>
            </header>
            <p class="pds-os-receive-modal__text">{{ __('message.os_receive_remark_prompt') }}</p>
            <label class="pds-os-receive-modal__label" for="osReceiveRejectRemark">{{ __('message.remark') }}</label>
            <textarea
                id="osReceiveRejectRemark"
                class="pds-os-receive-modal__textarea"
                rows="4"
                maxlength="1000"
                placeholder="{{ __('message.os_receive_remark_prompt') }}"
            ></textarea>
            <div class="pds-os-receive-modal__error" id="osReceiveRejectError" hidden>
                {{ __('message.os_receive_remark_required') }}
            </div>
            <footer class="pds-os-receive-modal__footer">
                <button type="button" class="pds-os-receive-modal__btn is-ghost" id="osReceiveRejectCancel">{{ __('message.cancel') }}</button>
                <button type="button" class="pds-os-receive-modal__btn is-danger" id="osReceiveRejectOk">{{ __('message.os_receive_reject_btn') }}</button>
            </footer>
        </div>
    </div>

    @section('bottom_script')
        <script>
            $(document).ready(function () {
                var csrf = $('meta[name="csrf-token"]').attr('content');
                var approveUrl = @json(url('os-receive/__ID__/approve'));
                var rejectUrl = @json(url('os-receive/__ID__/reject'));
                var pendingApproveId = null;
                var pendingRejectId = null;
                var $approveModal = $('#osReceiveApproveModal');
                var $rejectModal = $('#osReceiveRejectModal');
                var $rejectRemark = $('#osReceiveRejectRemark');
                var $rejectError = $('#osReceiveRejectError');

                function notify(msg, status) {
                    if (!msg) return;
                    if (typeof SnackBar === 'function') {
                        SnackBar({ message: msg, status: status || 'error' });
                        return;
                    }
                    if (typeof toastr !== 'undefined') {
                        if (status === 'success') toastr.success(msg);
                        else toastr.error(msg);
                        return;
                    }
                    // No browser alert() — keep UI quiet.
                }

                function closeApproveModal() {
                    pendingApproveId = null;
                    $approveModal.attr('hidden', true);
                }

                function closeRejectModal() {
                    pendingRejectId = null;
                    $rejectRemark.val('');
                    $rejectError.attr('hidden', true);
                    $rejectModal.attr('hidden', true);
                }

                function openApproveModal(id) {
                    pendingApproveId = id;
                    $approveModal.removeAttr('hidden');
                }

                function openRejectModal(id) {
                    pendingRejectId = id;
                    $rejectRemark.val('');
                    $rejectError.attr('hidden', true);
                    $rejectModal.removeAttr('hidden');
                    setTimeout(function () { $rejectRemark.trigger('focus'); }, 50);
                }

                function submitApprove() {
                    if (!pendingApproveId) return;
                    var id = pendingApproveId;
                    closeApproveModal();
                    $.ajax({
                        url: approveUrl.replace('__ID__', id),
                        type: 'POST',
                        data: { _token: csrf },
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function (res) {
                            window.location.href = res.redirect || @json(route('order.os-receive', ['tab' => 'received']));
                        },
                        error: function (xhr) {
                            notify((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : @json(__('message.something_went_wrong')));
                        },
                    });
                }

                function submitReject() {
                    if (!pendingRejectId) return;
                    var remark = String($rejectRemark.val() || '').trim();
                    if (!remark) {
                        $rejectError.removeAttr('hidden');
                        $rejectRemark.trigger('focus');
                        return;
                    }
                    var id = pendingRejectId;
                    closeRejectModal();
                    $.ajax({
                        url: rejectUrl.replace('__ID__', id),
                        type: 'POST',
                        data: { _token: csrf, remark: remark },
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function () {
                            window.location.reload();
                        },
                        error: function (xhr) {
                            notify((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : @json(__('message.something_went_wrong')));
                        },
                    });
                }

                $(document).on('click', '.js-os-receive-approve', function () {
                    openApproveModal($(this).data('id'));
                });

                $(document).on('click', '.js-os-receive-reject', function () {
                    openRejectModal($(this).data('id'));
                });

                $('#osReceiveApproveCancel, #osReceiveApproveModal [data-close="approve"]').on('click', closeApproveModal);
                $('#osReceiveApproveOk').on('click', submitApprove);
                $('#osReceiveRejectCancel, #osReceiveRejectModal [data-close="reject"]').on('click', closeRejectModal);
                $('#osReceiveRejectOk').on('click', submitReject);
                $rejectRemark.on('input', function () {
                    if (String($(this).val() || '').trim()) {
                        $rejectError.attr('hidden', true);
                    }
                });
                $(document).on('keydown', function (e) {
                    if (e.key !== 'Escape') return;
                    if (!$approveModal.is('[hidden]')) closeApproveModal();
                    if (!$rejectModal.is('[hidden]')) closeRejectModal();
                });
            });
        </script>
    @endsection
</x-master-layout>
