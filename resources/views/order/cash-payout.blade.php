<x-master-layout :assets="$assets ?? []">
    
<div id="adminLiveRoot" data-live-page="auto">
<style>
        .pds-kyo-shin-row-badge {
            display: inline-flex; align-items: center; margin-left: 6px;
            padding: 2px 7px; border-radius: 999px; font-size: 10px; font-weight: 800;
            background: rgba(var(--brand-rgb), 0.16); color: var(--site-color);
        }
        .pds-cash-payout-page .pds-cash-payout-photo-stack { max-width: 220px; }
    </style>
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-to-assign-page pds-cash-payout-page">
        <div class="pds-dispatch-to-assign-screen pds-rider-screen">
            <div class="pds-rider-hero">
                <div class="pds-rider-hero__copy">
                    <div class="pds-rider-hero__eyebrow">
                        <i class="fas fa-money-bill-wave" aria-hidden="true"></i>
                        <span>{{ __('message.order') }}</span>
                    </div>
                    <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                    <p class="pds-rider-hero__subtitle">{{ __('message.cash_payout_subtitle') }}</p>
                </div>
                <div class="pds-rider-hero__stat">
                    <span class="pds-rider-hero__stat-value">{{ $counts[$status] ?? 0 }}</span>
                    <span class="pds-rider-hero__stat-label">{{ __('message.invoice') }}</span>
                </div>
            </div>

            @include('partials._branch-tabs', [
                'branchTabs' => $branchTabs ?? collect(),
                'selectedBranchId' => $selectedBranchId ?? null,
                'branchTabCounts' => $branchTabCounts ?? [],
                'allCount' => $allBranchCount ?? null,
                'includeAll' => false,
                'routeName' => 'order.cash-payout',
                'routeQuery' => ['status' => $status],
            ])

            <div class="pds-cash-payout-tabs" role="tablist" aria-label="{{ __('message.status') }}">
                @foreach([
                    'unassigned' => ['label' => __('message.cash_payout_assign'), 'icon' => 'fa-user-plus'],
                    'assigned' => ['label' => __('message.cash_payout_assigned'), 'icon' => 'fa-motorcycle'],
                    'pending' => ['label' => __('message.pending'), 'icon' => 'fa-clock'],
                    'done' => ['label' => __('message.done'), 'icon' => 'fa-check-circle'],
                ] as $key => $meta)
                    <a href="{{ route('order.cash-payout', array_filter(['status' => $key, 'branch_id' => $selectedBranchId ?? null])) }}"
                       class="pds-cash-payout-tab pds-cash-payout-tab--{{ $key }} {{ $status === $key ? 'is-active' : '' }}"
                       data-tab="{{ $key }}"
                       role="tab"
                       aria-selected="{{ $status === $key ? 'true' : 'false' }}">
                        <i class="fas {{ $meta['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $meta['label'] }}</span>
                        <em>{{ (int) ($counts[$key] ?? 0) }}</em>
                    </a>
                @endforeach
            </div>

            <div class="pds-rider-body">
                @if($items->isEmpty())
                    <div class="pds-money-transfer-empty">
                        <div class="pds-money-transfer-empty__icon"><i class="fas fa-inbox"></i></div>
                        <p>{{ __('message.no_record_found') }}</p>
                    </div>
                @else
                    <div class="pds-rider-table-shell pds-rider-table-shell--scroll pds-cash-payout-shell">
                        <table class="table pds-rider-list-table pds-cash-payout-table">
                            <thead>
                                <tr>
                                    <th class="pds-cash-payout-col-no">{{ __('message.no') }}</th>
                                    <th>{{ __('message.os_name') }}</th>
                                    <th class="text-right">{{ __('message.amount') }}</th>
                                    <th class="pds-cash-payout-col-photo">{{ __('message.finish_image') }}</th>
                                    <th class="pds-cash-payout-col-photo">{{ __('message.pending_image') }}</th>
                                    <th class="pds-cash-payout-col-photo">{{ __('message.delivered_image') }}</th>
                                    <th>{{ __('message.delivery_man') }}</th>
                                    <th>{{ __('message.status') }}</th>
                                    <th class="pds-cash-payout-col-action">{{ __('message.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $index => $item)
                                    @php
                                        $os = $item->osUser;
                                        $osName = $os->name ?? ('OS #'.$item->os_user_id);
                                        $slipPhotos = $item->slipPhotoUrls();
                                        $pendingPhoto = $item->pendingPhotoUrl();
                                        $donePhoto = $item->donePhotoUrl();
                                        $statusClass = match ($item->status) {
                                            'pending' => 'is-pending',
                                            'done' => 'is-done',
                                            'assigned' => 'is-assigned',
                                            default => 'is-unassigned',
                                        };
                                        $statusLabel = match ($item->status) {
                                            'pending' => __('message.pending'),
                                            'done' => __('message.done'),
                                            'assigned' => __('message.cash_payout_assigned'),
                                            default => __('message.unassigned'),
                                        };
                                    @endphp
                                    <tr data-id="{{ $item->id }}" data-status="{{ $item->status }}">
                                        <td class="pds-cash-payout-no">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="pds-cash-payout-os__name">
                                                {{ $osName }}
                                                @if(!empty($item->kyo_shin_batch_id))
                                                    <span class="pds-kyo-shin-row-badge">{{ __('message.kyo_shin_title') }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-right pds-cash-payout-due">
                                            {{ number_format((float) $item->amount) }}
                                        </td>
                                        <td class="pds-cash-payout-photo-cell">
                                            <div class="pds-cash-payout-photo-stack">
                                                @if($slipPhotos !== [])
                                                    <div class="pds-cash-payout-photos">
                                                        @foreach($slipPhotos as $slipPhoto)
                                                            <a href="{{ $slipPhoto }}" target="_blank" rel="noopener" class="pds-cash-payout-photo" title="{{ __('message.finish_image') }} {{ $loop->iteration }}">
                                                                <img src="{{ $slipPhoto }}" alt="{{ __('message.finish_image') }} {{ $loop->iteration }}">
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="pds-cash-payout-photo-empty" aria-hidden="true"><i class="far fa-image"></i></span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="pds-cash-payout-photo-cell">
                                            <div class="pds-cash-payout-photo-stack">
                                                @if($pendingPhoto)
                                                    <a href="{{ $pendingPhoto }}" target="_blank" rel="noopener" class="pds-cash-payout-photo" title="{{ __('message.pending_image') }}">
                                                        <img src="{{ $pendingPhoto }}" alt="{{ __('message.pending_image') }}">
                                                    </a>
                                                @else
                                                    <span class="pds-cash-payout-photo-empty" aria-hidden="true"><i class="far fa-image"></i></span>
                                                @endif
                                                @if(filled($item->pending_note))
                                                    <div class="pds-cash-payout-photo-caption">{{ $item->pending_note }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="pds-cash-payout-photo-cell">
                                            <div class="pds-cash-payout-photo-stack">
                                                @if($donePhoto)
                                                    <a href="{{ $donePhoto }}" target="_blank" rel="noopener" class="pds-cash-payout-photo" title="{{ __('message.delivered_image') }}">
                                                        <img src="{{ $donePhoto }}" alt="{{ __('message.delivered_image') }}">
                                                    </a>
                                                @else
                                                    <span class="pds-cash-payout-photo-empty" aria-hidden="true"><i class="far fa-image"></i></span>
                                                @endif
                                                @if(filled($item->pending_note))
                                                    <div class="pds-cash-payout-photo-caption">{{ $item->pending_note }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($item->deliveryMan)
                                                <div class="pds-cash-payout-rider__name">{{ $item->deliveryMan->name }}</div>
                                            @else
                                                <span class="pds-cash-payout-empty">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="pds-cash-payout-status {{ $statusClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td class="pds-cash-payout-actions">
                                            @if($canEdit && in_array($item->status, ['unassigned', 'assigned'], true))
                                                <div class="pds-cash-payout-assign">
                                                    <label class="pds-cash-payout-assign__label" for="cash-rider-{{ $item->id }}">
                                                        <i class="fas fa-motorcycle" aria-hidden="true"></i>
                                                        <span>{{ __('message.delivery_man') }}</span>
                                                    </label>
                                                    <select id="cash-rider-{{ $item->id }}"
                                                            class="pds-cash-payout-select js-cash-rider"
                                                            data-id="{{ $item->id }}"
                                                            data-prev="{{ $item->delivery_man_id ?? '' }}"
                                                            data-placeholder="{{ __('message.delivery_man') }}">
                                                        <option value="">{{ __('message.delivery_man') }}</option>
                                                        @foreach($riders as $rider)
                                                            <option value="{{ $rider->id }}" @selected((int) $item->delivery_man_id === (int) $rider->id)>{{ $rider->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif

                                            @if($canEdit && $item->status === 'assigned')
                                                <button type="button" class="pds-cash-payout-btn pds-cash-payout-btn--warn js-cash-status" data-id="{{ $item->id }}" data-next="pending">
                                                    {{ __('message.pending') }}
                                                </button>
                                                <button type="button" class="pds-cash-payout-btn pds-cash-payout-btn--ok js-cash-status" data-id="{{ $item->id }}" data-next="done">
                                                    {{ __('message.done') }}
                                                </button>
                                            @elseif($canEdit && $item->status === 'pending')
                                                <button type="button" class="pds-cash-payout-btn pds-cash-payout-btn--ok js-cash-status" data-id="{{ $item->id }}" data-next="done">
                                                    {{ __('message.done') }}
                                                </button>
                                            @elseif(! ($canEdit && in_array($item->status, ['unassigned', 'assigned'], true)))
                                                <span class="pds-cash-payout-empty">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="cashPayoutStatusModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content pds-cash-payout-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="cashPayoutStatusTitle">Update status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cashPayoutStatusId">
                    <input type="hidden" id="cashPayoutStatusNext">
                    <div class="form-group" id="cashPayoutNoteWrap">
                        <label for="cashPayoutNote">{{ __('message.remark_label') }} <span class="text-danger">*</span></label>
                        <textarea id="cashPayoutNote" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group mb-0" id="cashPayoutPhotoWrap">
                        <label for="cashPayoutPhoto">Photo <span class="text-danger">*</span></label>
                        <input type="file" id="cashPayoutPhoto" class="form-control-file" accept="image/*">
                        <div class="mt-2">
                            <img id="cashPayoutPhotoPreview" src="" alt="" style="display:none;max-width:100%;max-height:180px;border-radius:8px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('message.cancel') ?? 'Cancel' }}</button>
                    <button type="button" class="btn btn-primary" id="cashPayoutStatusSubmit">{{ __('message.save') ?? 'Submit' }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Inline boot: @section is skipped by x-master-layout; SPA evals this from #adminSpaContent.
     Custom searchable select (no Select2) — production-safe. --}}
<script>
(function bootCashPayoutPage() {
    var riderPlaceholder = @json(__('message.delivery_man'));
    var assignUrl = @json(url('cash-payout/__ID__/assign'));
    var statusUrl = @json(url('cash-payout/__ID__/status'));
    var openCombo = null;

    function withJquery(cb) {
        if (window.jQuery) return cb(window.jQuery);
        setTimeout(function () { withJquery(cb); }, 40);
    }

    function closeCombo() {
        if (!openCombo) return;
        var wrap = openCombo;
        var panel = wrap._pdsPanel;
        wrap.classList.remove('is-open');
        if (panel) panel.classList.remove('is-open');
        openCombo = null;
    }

    function placePanel(wrap) {
        var panel = wrap._pdsPanel;
        var toggle = wrap.querySelector('.pds-cash-combo__toggle');
        if (!panel || !toggle) return;
        var rect = toggle.getBoundingClientRect();
        panel.style.position = 'fixed';
        panel.style.left = Math.max(8, rect.left) + 'px';
        panel.style.width = Math.max(rect.width, 220) + 'px';
        panel.style.zIndex = '10050';
        var spaceBelow = window.innerHeight - rect.bottom;
        if (spaceBelow < 220 && rect.top > spaceBelow) {
            panel.style.top = 'auto';
            panel.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
        } else {
            panel.style.top = (rect.bottom + 4) + 'px';
            panel.style.bottom = 'auto';
        }
    }

    function optionList(select) {
        var out = [];
        for (var i = 0; i < select.options.length; i++) {
            var opt = select.options[i];
            out.push({ value: opt.value, text: (opt.text || '').trim() });
        }
        return out;
    }

    function syncLabel(wrap, select) {
        var label = wrap.querySelector('.pds-cash-combo__label');
        if (!label) return;
        var val = select.value;
        var text = riderPlaceholder;
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].value === val && val !== '') {
                text = (select.options[i].text || '').trim();
                break;
            }
        }
        label.textContent = text;
        label.classList.toggle('is-placeholder', !val);
    }

    function renderResults(wrap, select, query) {
        var list = wrap.querySelector('.pds-cash-combo__list');
        if (!list) return;
        var q = String(query || '').toLowerCase().trim();
        var opts = optionList(select).filter(function (o) { return o.value !== ''; });
        list.innerHTML = '';
        var shown = 0;
        opts.forEach(function (o) {
            if (q && o.text.toLowerCase().indexOf(q) === -1) return;
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pds-cash-combo__option' + (o.value === select.value ? ' is-selected' : '');
            btn.setAttribute('data-value', o.value);
            btn.textContent = o.text;
            list.appendChild(btn);
            shown += 1;
        });
        if (!shown) {
            var empty = document.createElement('div');
            empty.className = 'pds-cash-combo__empty';
            empty.textContent = 'No match';
            list.appendChild(empty);
        }
    }

    function enhanceSelect(select) {
        if (!select || select.getAttribute('data-pds-combo') === '1') return;
        // Destroy leftover Select2 if any previous deploy applied it
        try {
            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2 &&
                select.classList.contains('select2-hidden-accessible')) {
                window.jQuery(select).select2('destroy');
            }
        } catch (e) {}
        var existing = select.parentElement && select.parentElement.classList.contains('pds-cash-combo')
            ? select.parentElement : null;
        if (existing) {
            existing.parentNode.insertBefore(select, existing);
            existing.remove();
        }

        select.setAttribute('data-pds-combo', '1');
        select.classList.add('pds-cash-combo__native');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        var wrap = document.createElement('div');
        wrap.className = 'pds-cash-combo';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'pds-cash-combo__toggle';
        toggle.innerHTML = '<span class="pds-cash-combo__label is-placeholder"></span><i class="fas fa-chevron-down" aria-hidden="true"></i>';
        wrap.appendChild(toggle);

        var panel = document.createElement('div');
        panel.className = 'pds-cash-combo__panel';
        panel.innerHTML = '<div class="pds-cash-combo__search-wrap"><input type="text" class="pds-cash-combo__search" placeholder="Search…" autocomplete="off"></div><div class="pds-cash-combo__list" role="listbox"></div>';
        document.body.appendChild(panel);
        wrap._pdsPanel = panel;
        panel._pdsWrap = wrap;

        syncLabel(wrap, select);

        toggle.addEventListener('click', function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            if (select.disabled) return;
            var opening = !wrap.classList.contains('is-open');
            closeCombo();
            if (!opening) return;
            wrap.classList.add('is-open');
            panel.classList.add('is-open');
            openCombo = wrap;
            var input = panel.querySelector('.pds-cash-combo__search');
            if (input) {
                input.value = '';
                renderResults(panel, select, '');
                placePanel(wrap);
                setTimeout(function () { input.focus(); }, 10);
            }
        });

        panel.addEventListener('click', function (ev) {
            ev.stopPropagation();
            var opt = ev.target.closest('.pds-cash-combo__option');
            if (!opt) return;
            var val = opt.getAttribute('data-value') || '';
            select.value = val;
            syncLabel(wrap, select);
            closeCombo();
            if (window.jQuery) window.jQuery(select).trigger('change');
            else select.dispatchEvent(new Event('change', { bubbles: true }));
        });

        var search = panel.querySelector('.pds-cash-combo__search');
        if (search) {
            search.addEventListener('input', function () {
                renderResults(panel, select, search.value);
            });
            search.addEventListener('keydown', function (ev) {
                if (ev.key === 'Escape') closeCombo();
            });
        }
    }

    function initCashRiderSelects() {
        // Drop orphan panels from previous soft-replace
        document.querySelectorAll('.pds-cash-combo__panel').forEach(function (p) {
            if (!p._pdsWrap || !document.body.contains(p._pdsWrap)) p.remove();
        });
        var root = document.getElementById('adminLiveRoot') || document;
        root.querySelectorAll('select.js-cash-rider').forEach(enhanceSelect);
    }
    window.__pdsCashPayoutInitSelects = initCashRiderSelects;

    function bindHandlers($) {
        if (window.__pdsCashPayoutHandlersBound) return;
        window.__pdsCashPayoutHandlersBound = true;
        var csrf = $('meta[name="csrf-token"]').attr('content');

        document.addEventListener('click', function () { closeCombo(); });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') closeCombo();
        });

        $(document).on('change', '.js-cash-rider', function () {
            var $sel = $(this);
            var id = $sel.data('id');
            var riderId = $sel.val();
            var prev = String($sel.attr('data-prev') || '');
            if (!riderId || String(riderId) === prev) {
                $sel.val(prev);
                var wrap = $sel.closest('.pds-cash-combo')[0];
                if (wrap) syncLabel(wrap, $sel[0]);
                return;
            }
            $sel.prop('disabled', true);
            $.ajax({
                url: assignUrl.replace('__ID__', id),
                type: 'POST',
                data: { _token: csrf, delivery_man_id: riderId },
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    if (typeof showMessage === 'function') showMessage(res.message || 'OK');
                    typeof window.adminLiveReloadPage === 'function' ? window.adminLiveReloadPage() : window.location.reload();
                },
                error: function (xhr) {
                    $sel.val(prev).prop('disabled', false);
                    var wrap = $sel.closest('.pds-cash-combo')[0];
                    if (wrap) syncLabel(wrap, $sel[0]);
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : @json(__('message.something_went_wrong'));
                    if (typeof errorMessage === 'function') errorMessage(msg);
                }
            });
        });

        $(document).on('click', '.js-cash-status', function () {
            var id = $(this).data('id');
            var next = $(this).data('next');
            $('#cashPayoutStatusId').val(id);
            $('#cashPayoutStatusNext').val(next);
            $('#cashPayoutNote').val('');
            $('#cashPayoutPhoto').val('');
            $('#cashPayoutPhotoPreview').hide().attr('src', '');
            if (next === 'pending') $('#cashPayoutNoteWrap').removeClass('d-none');
            else $('#cashPayoutNoteWrap').addClass('d-none');
            $('#cashPayoutPhotoWrap').removeClass('d-none');
            $('#cashPayoutStatusTitle').text(next === 'pending'
                ? @json(__('message.pending'))
                : @json(__('message.done')));
            $('#cashPayoutStatusModal').modal('show');
        });

        $(document).on('change', '#cashPayoutPhoto', function () {
            var file = this.files && this.files[0];
            if (!file) {
                $('#cashPayoutPhotoPreview').hide().attr('src', '');
                return;
            }
            $('#cashPayoutPhotoPreview').attr('src', URL.createObjectURL(file)).show();
        });

        $(document).on('click', '#cashPayoutStatusSubmit', function () {
            var id = $('#cashPayoutStatusId').val();
            var next = $('#cashPayoutStatusNext').val();
            var formData = new FormData();
            formData.append('_token', csrf);
            formData.append('status', next);
            var note = $.trim($('#cashPayoutNote').val() || '');
            var fileInput = document.getElementById('cashPayoutPhoto');
            var file = fileInput && fileInput.files && fileInput.files[0];
            if (!file) {
                if (typeof SnackBar === 'function') SnackBar({ message: 'Photo is required', status: 'error' });
                return;
            }
            if (next === 'pending' && !note) {
                if (typeof SnackBar === 'function') SnackBar({ message: 'Remark is required', status: 'error' });
                return;
            }
            if (next === 'pending') formData.append('note', note);
            formData.append('photo', file);
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: statusUrl.replace('__ID__', id),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    $('#cashPayoutStatusModal').modal('hide');
                    if (typeof showMessage === 'function') showMessage(res.message || 'OK');
                    typeof window.adminLiveReloadPage === 'function' ? window.adminLiveReloadPage() : window.location.reload();
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : @json(__('message.something_went_wrong'));
                    if (typeof errorMessage === 'function') errorMessage(msg);
                    else if (typeof SnackBar === 'function') SnackBar({ message: msg, status: 'error' });
                },
                complete: function () { $btn.prop('disabled', false); }
            });
        });

        $(document).on('admin-live:replaced pds:live-reloaded adminLiveReloaded', function () {
            setTimeout(initCashRiderSelects, 50);
        });
    }

    function boot() {
        withJquery(function ($) {
            bindHandlers($);
            initCashRiderSelects();
            setTimeout(initCashRiderSelects, 300);
            setTimeout(initCashRiderSelects, 1000);
        });
    }

    boot();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    }
    window.addEventListener('load', boot);
})();
</script>
</x-master-layout>
