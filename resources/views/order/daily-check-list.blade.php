<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-to-assign-page pds-daily-check-page">
        <div class="pds-dispatch-to-assign-screen pds-rider-screen">
            <div class="pds-rider-hero">
                <div class="pds-rider-hero__copy">
                    <div class="pds-rider-hero__eyebrow">
                        <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                        <span>{{ __('message.order') }}</span>
                    </div>
                    <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                    <p class="pds-rider-hero__subtitle">{{ __('message.daily_check_list_subtitle') }}</p>
                </div>
                <div class="pds-rider-hero__stat">
                    <span class="pds-rider-hero__stat-value">{{ $rows->count() }}</span>
                    <span class="pds-rider-hero__stat-label">{{ __('message.invoice') }}</span>
                </div>
            </div>

            @include('order.partials._settlement-tabs', ['activeTab' => 'daily-check'])

            <form method="GET" action="{{ route('order.daily-checklist') }}" class="pds-daily-check-toolbar is-mode-{{ $mode }}" id="dailyCheckFilterForm">
                <div class="pds-daily-check-toolbar__grid">
                    <div class="pds-daily-check-field pds-daily-check-field--date">
                        <label for="daily_check_from_date">{{ __('message.from') }}</label>
                        <input type="text" name="from_date" id="daily_check_from_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $filterFromDate }}" autocomplete="off">
                    </div>
                    <div class="pds-daily-check-field pds-daily-check-field--date">
                        <label for="daily_check_to_date">{{ __('message.to') }}</label>
                        <input type="text" name="to_date" id="daily_check_to_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $filterToDate }}" autocomplete="off">
                    </div>
                    <div class="pds-daily-check-field pds-daily-check-field--branch">
                        <label for="daily_check_branch">{{ __('message.branch') }}</label>
                        <select name="branch_id" id="daily_check_branch" class="pds-dispatch-input pds-dispatch-select">
                            @if($branches->count() !== 1)
                                <option value="all" @selected($branchFilter === 'all')>{{ __('message.all') }}</option>
                            @endif
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) $branchFilter === (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pds-daily-check-field pds-daily-check-field--mode">
                        <label>{{ __('message.filter') }}</label>
                        <div class="pds-daily-check-seg" role="radiogroup" aria-label="{{ __('message.filter') }}">
                            <label class="pds-daily-check-seg__item {{ $mode === 'rider' ? 'is-active' : '' }}">
                                <input type="radio" name="mode" value="rider" @checked($mode === 'rider')>
                                <span>Rider</span>
                            </label>
                            <label class="pds-daily-check-seg__item {{ $mode === 'os' ? 'is-active' : '' }}">
                                <input type="radio" name="mode" value="os" @checked($mode === 'os')>
                                <span>OS</span>
                            </label>
                            <label class="pds-daily-check-seg__item {{ $mode === 'all' ? 'is-active' : '' }}">
                                <input type="radio" name="mode" value="all" @checked($mode === 'all')>
                                <span>{{ __('message.all') }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="pds-daily-check-field pds-daily-check-field--party {{ $mode === 'os' ? '' : 'd-none' }}" id="dailyCheckOsWrap">
                        <label for="daily_check_os">{{ __('message.online_shopping') }}</label>
                        <select name="os_id" id="daily_check_os" class="pds-dispatch-input pds-dispatch-select">
                            <option value="all" @selected($osFilter === 'all')>{{ __('message.all') }}</option>
                            <option value="0" @selected((string) $osFilter === '0')>{{ __('message.no_os') }}</option>
                            @foreach($osOptions as $option)
                                <option value="{{ $option->id }}" @selected((string) $osFilter === (string) $option->id)>{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pds-daily-check-field pds-daily-check-field--party {{ $mode === 'rider' ? '' : 'd-none' }}" id="dailyCheckRiderWrap">
                        <label for="daily_check_rider">{{ __('message.delivery_man') }}</label>
                        <select name="rider_id" id="daily_check_rider" class="pds-dispatch-input pds-dispatch-select">
                            <option value="all" @selected($riderFilter === 'all')>{{ __('message.all') }}</option>
                            @foreach($riderOptions as $option)
                                <option value="{{ $option->id }}" @selected((string) $riderFilter === (string) $option->id)>{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pds-daily-check-toolbar__actions">
                        <button type="submit" class="pds-daily-check-search-btn" title="{{ __('message.check') }}">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <span>{{ __('message.check') }}</span>
                        </button>
                        @if($mode !== 'all')
                            <a class="pds-daily-check-icon-btn"
                               href="{{ route('order.daily-checklist.export-excel', request()->query()) }}"
                               title="{{ __('message.export') }}">
                                <i class="fas fa-cloud-download-alt" aria-hidden="true"></i>
                            </a>
                        @endif
                        <a class="pds-daily-check-icon-btn"
                           href="{{ route('order.daily-checklist.export-excel-overall', request()->query()) }}"
                           title="{{ __('message.daily_check_overall_excel') }}">
                            <i class="fas fa-file-excel" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </form>

            <div class="pds-rider-body">
                @if($rows->isEmpty())
                    <div class="pds-rider-empty">
                        <div class="pds-rider-empty__icon"><i class="fas fa-clipboard-check"></i></div>
                        <p>{{ __('message.no_record_found') }}</p>
                    </div>
                @else
                    <div class="pds-frozen-table pds-daily-check-shell" id="dailyCheckFrozen">
                        @php
                            $dcCols = [
                                ['w' => '56', 'class' => '', 'label' => __('message.no')],
                                ['w' => '130', 'class' => '', 'label' => __('message.received_date')],
                                ['w' => '220', 'class' => '', 'label' => __('message.name')],
                                ['w' => '160', 'class' => '', 'label' => __('message.invoice_number')],
                                ['w' => '110', 'class' => '', 'label' => __('message.item_count')],
                                ['w' => '120', 'class' => 'text-right', 'label' => __('message.advance_paid')],
                                ['w' => '110', 'class' => 'text-right', 'label' => __('message.amount')],
                                ['w' => '170', 'class' => 'text-right', 'label' => __('message.os_to_pay')],
                                ['w' => '120', 'class' => 'text-right', 'label' => __('message.deli_amount')],
                                ['w' => '90', 'class' => 'text-right', 'label' => __('message.gate')],
                                ['w' => '90', 'class' => '', 'label' => __('message.user')],
                                ['w' => '170', 'class' => '', 'label' => __('message.date')],
                                ['w' => '130', 'class' => '', 'label' => __('message.remitted_date')],
                            ];
                            if ($mode !== 'all') {
                                $dcCols[] = ['w' => '200', 'class' => '', 'label' => __('message.action')];
                            }
                        @endphp
                        <div class="pds-frozen-table__head">
                            <table class="table pds-rider-list-table pds-daily-check-table">
                                <colgroup>
                                    @foreach($dcCols as $col)
                                        <col style="width: {{ $col['w'] }}px">
                                    @endforeach
                                </colgroup>
                                <thead>
                                    <tr>
                                        @foreach($dcCols as $col)
                                            <th class="{{ $col['class'] }}">{{ $col['label'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        <div class="pds-frozen-table__body">
                        <table class="table pds-rider-list-table pds-daily-check-table" id="dailyCheckTable">
                            <colgroup>
                                @foreach($dcCols as $col)
                                    <col style="width: {{ $col['w'] }}px">
                                @endforeach
                            </colgroup>
                            <tbody>
                                @foreach($rows as $index => $row)
                                    <tr data-invoice-id="{{ $row->id }}" data-party-type="{{ $row->party_type }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $row->received_date }}</td>
                                        <td>{{ $row->name }}</td>
                                        <td><span class="pds-daily-check-invoice-no">{{ $row->invoice_no }}</span></td>
                                        <td>
                                            <button type="button"
                                                    class="pds-daily-check-count-btn js-daily-check-detail"
                                                    data-url="{{ route('order.daily-checklist.detail', $row->id) }}"
                                                    title="{{ __('message.check_detail') }}">
                                                {{ $row->item_count }}
                                            </button>
                                        </td>
                                        <td class="text-right">{{ number_format($row->advance_paid) }}</td>
                                        <td class="text-right js-daily-check-amount {{ $row->payment_matched ? 'pds-daily-check-pay-ok' : 'pds-daily-check-pay-miss' }}">
                                            {{ number_format($row->amount) }}
                                        </td>
                                        <td class="text-right pds-daily-check-ostopay {{ $row->os_to_pay < 0 ? 'pds-os-to-pay-negative' : '' }}">
                                            {!! $row->os_to_pay_html ?? e(number_format($row->os_to_pay)) !!}
                                        </td>
                                        <td class="text-right">{{ number_format($row->deli_amount) }}</td>
                                        <td class="text-right">{{ number_format($row->gate ?? 0) }}</td>
                                        <td>{{ $row->user_name }}</td>
                                        <td>{{ $row->modified_date }}</td>
                                        <td class="js-remitted-date-cell">{{ $row->remitted_date ?: '—' }}</td>
                                        @if($mode !== 'all')
                                            <td class="pds-daily-check-actions">
                                                @if($row->party_type === 'os')
                                                    <button type="button"
                                                            class="pds-daily-check-action-btn js-daily-check-remit-date"
                                                            data-url="{{ route('order.daily-checklist.remit-date', $row->id) }}"
                                                            data-date="{{ $row->remitted_date }}"
                                                            title="{{ __('message.remitted_date') }}">
                                                        <i class="fas fa-calendar-alt"></i>
                                                    </button>
                                                    <button type="button"
                                                            class="pds-daily-check-action-btn js-daily-check-remit-photo"
                                                            data-url="{{ route('order.daily-checklist.remit-photo', $row->id) }}"
                                                            data-photo="{{ $row->remitted_photo_url }}"
                                                            title="{{ __('message.remitted_photo') }}">
                                                        <i class="fas fa-camera"></i>
                                                    </button>
                                                @endif
                                                <button type="button"
                                                        class="pds-daily-check-action-btn js-daily-check-slip"
                                                        data-url="{{ route('order.daily-checklist.slip', $row->id) }}"
                                                        title="{{ __('message.show_slip') }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <a class="pds-daily-check-action-btn"
                                                   href="{{ route('order.daily-checklist.excel-invoice', $row->id) }}"
                                                   title="Excel">
                                                    <i class="fas fa-file-excel"></i>
                                                </a>
                                                <a class="pds-daily-check-action-btn"
                                                   href="{{ route('order.daily-checklist.pdf', $row->id) }}"
                                                   title="PDF">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                                @if($row->party_type === 'os')
                                                    <a class="pds-daily-check-action-btn"
                                                       href="{{ route('order.daily-checklist.remitted-pdf', $row->id) }}"
                                                       title="{{ __('message.remitted_invoice') }}">
                                                        <i class="fas fa-file-download"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                            @php
                                $totalItemCount = (int) $rows->sum('item_count');
                                $totalAdvance = (float) $rows->sum('advance_paid');
                                $totalDeli = (float) $rows->sum('deli_amount');
                                $totalOsPaid = (float) $rows->sum('os_paid');
                                $totalGate = (float) $rows->sum('gate');
                                $totalOsToPay = (float) $rows->sum('os_to_pay');
                                $totalAmount = (float) $rows->sum('amount');
                                $colspanTail = $mode !== 'all' ? 4 : 3;
                            @endphp
                            <tfoot>
                                <tr class="pds-daily-check-total-row">
                                    <td colspan="4" class="pds-daily-check-total-label">{{ __('message.total') }}</td>
                                    <td class="text-center">{{ number_format($totalItemCount) }}</td>
                                    <td class="text-right">{{ number_format($totalAdvance) }}</td>
                                    <td class="text-right {{ $totalAmount < 0 ? 'pds-os-to-pay-negative' : '' }}">{{ number_format($totalAmount) }}</td>
                                    <td class="text-right {{ $totalOsToPay < 0 ? 'pds-os-to-pay-negative' : '' }}">{{ number_format($totalOsToPay) }}</td>
                                    <td class="text-right">{{ number_format($totalDeli) }}</td>
                                    <td class="text-right">{{ number_format($totalGate) }}</td>
                                    <td colspan="{{ $colspanTail }}"></td>
                                </tr>
                            </tfoot>
                        </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="dailyCheckDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable pds-check-detail-dialog" role="document">
            <div class="modal-content pds-check-detail-modal">
                <div class="modal-body p-0" id="dailyCheckDetailBody"></div>
            </div>
        </div>
    </div>

    <div id="dailyCheckSlipModalHost"></div>

    <div class="modal fade" id="dailyCheckRemitDateModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('message.remitted_date') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="text" id="dailyCheckRemitDateInput" class="pds-dispatch-input dispatch-datepicker form-control" autocomplete="off">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('message.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="dailyCheckRemitDateSave">{{ __('message.save') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="dailyCheckRemitPhotoModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('message.remitted_photo') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="pds-daily-check-photo-preview mb-3">
                        <img id="dailyCheckRemitPhotoPreview" src="" alt="" class="d-none">
                        <div id="dailyCheckRemitPhotoEmpty" class="text-muted">{{ __('message.no_photo_uploaded') }}</div>
                    </div>
                    <input type="file" id="dailyCheckRemitPhotoInput" accept="image/*" class="form-control-file">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('message.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="dailyCheckRemitPhotoSave">{{ __('message.save') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="dailyCheckItemMediaModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content pds-user-photo-modal">
                <div class="modal-header pds-user-photo-modal__header">
                    <h5 class="modal-title mb-0" id="dailyCheckItemMediaTitle">{{ __('message.user_photo') }}</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="pds-daily-check-photo-preview mb-3">
                        <img id="dailyCheckItemMediaPreview" src="" alt="" class="d-none">
                        <div id="dailyCheckItemMediaEmpty" class="pds-user-photo-empty">
                            <i class="far fa-image" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div id="dailyCheckItemMediaUploadWrap">
                        <input type="file" id="dailyCheckItemMediaInput" accept="image/*" class="form-control-file">
                    </div>
                </div>
                <div class="modal-footer pds-user-photo-modal__footer justify-content-start">
                    <button type="button" class="pds-user-photo-btn pds-user-photo-btn--cancel" data-dismiss="modal">CANCEL</button>
                    <button type="button" class="pds-user-photo-btn pds-user-photo-btn--save" id="dailyCheckItemMediaSave">SAVE</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Inline boot so Item Count click works even if @section/@push is skipped by the layout component --}}
    <script>
        (function bootDailyCheckList() {
            if (!window.jQuery) {
                return setTimeout(bootDailyCheckList, 40);
            }
            var $ = window.jQuery;
            if (window.__pdsDailyCheckBound) return;
            window.__pdsDailyCheckBound = true;

            (function bindFrozenDailyCheckHeader() {
                var root = document.getElementById('dailyCheckFrozen');
                if (!root) return;
                var headWrap = root.querySelector('.pds-frozen-table__head');
                var bodyWrap = root.querySelector('.pds-frozen-table__body');
                if (!headWrap || !bodyWrap) return;
                bodyWrap.addEventListener('scroll', function () {
                    headWrap.scrollLeft = bodyWrap.scrollLeft;
                });
            })();

            var remitDateUrl = null;
            var remitPhotoUrl = null;
            var $activeRemitRow = null;
            var itemMediaUrl = null;
            var itemMediaType = null;
            var $activeItemMediaBtn = null;

            if (typeof flatpickr !== 'undefined') {
                flatpickr('.dispatch-datepicker', {
                    dateFormat: 'd-m-Y',
                    allowInput: true,
                });
            }

            function togglePartyFilters() {
                var mode = $('input[name="mode"]:checked').val();
                $('#dailyCheckOsWrap').toggleClass('d-none', mode !== 'os');
                $('#dailyCheckRiderWrap').toggleClass('d-none', mode !== 'rider');
                $('#dailyCheckFilterForm')
                    .removeClass('is-mode-rider is-mode-os is-mode-all')
                    .addClass('is-mode-' + mode);
                $('.pds-daily-check-seg__item').removeClass('is-active');
                $('.pds-daily-check-seg__item').has('input[value="' + mode + '"]').addClass('is-active');
            }

            function setItemMediaPreview(src) {
                if (src) {
                    $('#dailyCheckItemMediaPreview').attr('src', src).removeClass('d-none');
                    $('#dailyCheckItemMediaEmpty').addClass('d-none');
                } else {
                    $('#dailyCheckItemMediaPreview').attr('src', '').addClass('d-none');
                    $('#dailyCheckItemMediaEmpty').removeClass('d-none');
                }
            }

            $(document).on('change', '#dailyCheckFilterForm input[name="mode"]', togglePartyFilters);

            $(document).on('click', '.js-daily-check-detail', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var url = $(this).attr('data-url') || $(this).data('url');
                if (!url) return;
                $('#dailyCheckDetailBody').html('<div class="p-5 text-center text-muted">Loading...</div>');
                $('#dailyCheckDetailModal').modal('show');
                $.ajax({ url: url, method: 'GET', dataType: 'json' })
                    .done(function (res) {
                        $('#dailyCheckDetailBody').html((res && res.html) ? res.html : '<div class="p-4 text-muted">No data</div>');
                    })
                    .fail(function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to load check detail';
                        $('#dailyCheckDetailBody').html('<div class="p-4 text-danger text-center">' + msg + '</div>');
                    });
            });

            $(document).on('click', '.js-daily-check-item-media', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var $btn = $(this);
                $activeItemMediaBtn = $btn;
                itemMediaUrl = $btn.attr('data-url') || $btn.data('url');
                itemMediaType = $btn.attr('data-type') || $btn.data('type');
                var title = '{{ __('message.user_photo') }}';
                var photo = $btn.attr('data-photo') || $btn.data('photo') || '';
                var canEdit = String($btn.attr('data-can-edit') || $btn.data('can-edit') || '0') === '1';

                $('#dailyCheckItemMediaTitle').text(title);
                setItemMediaPreview(photo);
                $('#dailyCheckItemMediaInput').val('');
                $('#dailyCheckItemMediaUploadWrap').toggleClass('d-none', !canEdit);
                $('#dailyCheckItemMediaSave').toggleClass('d-none', !canEdit);
                $('#dailyCheckItemMediaModal').modal('show');
            });

            $('#dailyCheckItemMediaInput').on('change', function () {
                var file = this.files && this.files[0];
                if (!file) return;
                var reader = new FileReader();
                reader.onload = function (ev) {
                    setItemMediaPreview(ev.target.result);
                };
                reader.readAsDataURL(file);
            });

            $('#dailyCheckItemMediaSave').on('click', function () {
                if (!itemMediaUrl || !itemMediaType) return;
                var fileInput = document.getElementById('dailyCheckItemMediaInput');
                if (!fileInput.files || !fileInput.files[0]) {
                    alert('{{ __('message.please_select_image') }}');
                    return;
                }
                var fd = new FormData();
                fd.append('photo', fileInput.files[0]);
                fd.append('type', itemMediaType);
                fd.append('_token', '{{ csrf_token() }}');
                $.ajax({
                    url: itemMediaUrl,
                    method: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false
                }).done(function (res) {
                    if ($activeItemMediaBtn && res.url) {
                        $activeItemMediaBtn.attr('data-photo', res.url).data('photo', res.url).addClass('has-media');
                    }
                    $('#dailyCheckItemMediaModal').modal('hide');
                }).fail(function (xhr) {
                    alert((xhr.responseJSON && xhr.responseJSON.message) || 'Fail');
                });
            });

            $(document).on('click', '.js-daily-check-slip', function () {
                var url = $(this).data('url');
                $.get(url).done(function (res) {
                    $('#dailyCheckSlipModalHost').html(res.html || '');
                    $('#dailyCheckSlipModalHost #dailyCheckShowSlipModal').modal('show');
                }).fail(function (xhr) {
                    alert((xhr.responseJSON && xhr.responseJSON.message) || 'Fail');
                });
            });

            $(document).on('click', '.js-daily-check-remit-date', function () {
                $activeRemitRow = $(this).closest('tr');
                remitDateUrl = $(this).data('url');
                $('#dailyCheckRemitDateInput').val($(this).data('date') || '');
                $('#dailyCheckRemitDateModal').modal('show');
                if (typeof flatpickr !== 'undefined') {
                    flatpickr('#dailyCheckRemitDateInput', { dateFormat: 'd-m-Y', allowInput: true });
                }
            });

            $('#dailyCheckRemitDateSave').on('click', function () {
                if (!remitDateUrl) return;
                $.ajax({
                    url: remitDateUrl,
                    method: 'POST',
                    data: {
                        remitted_date: $('#dailyCheckRemitDateInput').val(),
                        _token: '{{ csrf_token() }}',
                        _method: 'PUT'
                    }
                }).done(function (res) {
                    if ($activeRemitRow) {
                        $activeRemitRow.find('.js-remitted-date-cell').text(res.remitted_date || '—');
                        $activeRemitRow.find('.js-daily-check-remit-date').data('date', res.remitted_date || '');
                        $activeRemitRow.find('.js-daily-check-amount')
                            .removeClass('pds-daily-check-pay-miss')
                            .addClass('pds-daily-check-pay-ok');
                    }
                    $('#dailyCheckRemitDateModal').modal('hide');
                }).fail(function (xhr) {
                    alert((xhr.responseJSON && xhr.responseJSON.message) || 'Fail');
                });
            });

            $(document).on('click', '.js-daily-check-remit-photo', function () {
                $activeRemitRow = $(this).closest('tr');
                remitPhotoUrl = $(this).data('url');
                var photo = $(this).data('photo');
                if (photo) {
                    $('#dailyCheckRemitPhotoPreview').attr('src', photo).removeClass('d-none');
                    $('#dailyCheckRemitPhotoEmpty').addClass('d-none');
                } else {
                    $('#dailyCheckRemitPhotoPreview').attr('src', '').addClass('d-none');
                    $('#dailyCheckRemitPhotoEmpty').removeClass('d-none');
                }
                $('#dailyCheckRemitPhotoInput').val('');
                $('#dailyCheckRemitPhotoModal').modal('show');
            });

            $('#dailyCheckRemitPhotoInput').on('change', function () {
                var file = this.files && this.files[0];
                if (!file) return;
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#dailyCheckRemitPhotoPreview').attr('src', e.target.result).removeClass('d-none');
                    $('#dailyCheckRemitPhotoEmpty').addClass('d-none');
                };
                reader.readAsDataURL(file);
            });

            $('#dailyCheckRemitPhotoSave').on('click', function () {
                if (!remitPhotoUrl) return;
                var fileInput = document.getElementById('dailyCheckRemitPhotoInput');
                if (!fileInput.files || !fileInput.files[0]) {
                    alert('{{ __('message.please_select_image') }}');
                    return;
                }
                var fd = new FormData();
                fd.append('photo', fileInput.files[0]);
                fd.append('_token', '{{ csrf_token() }}');
                $.ajax({
                    url: remitPhotoUrl,
                    method: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false
                }).done(function (res) {
                    if ($activeRemitRow && res.remitted_photo_url) {
                        $activeRemitRow.find('.js-daily-check-remit-photo').data('photo', res.remitted_photo_url);
                    }
                    $('#dailyCheckRemitPhotoModal').modal('hide');
                }).fail(function (xhr) {
                    alert((xhr.responseJSON && xhr.responseJSON.message) || 'Fail');
                });
            });
        })();
    </script>
</x-master-layout>
