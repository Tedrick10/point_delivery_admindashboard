<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-to-assign-page pds-dispatch-rider-list-page">
        <div class="pds-dispatch-to-assign-screen pds-rider-screen">
            <div class="pds-rider-hero">
                <div class="pds-rider-hero__copy">
                    <div class="pds-rider-hero__eyebrow">
                        <i class="fas fa-motorcycle" aria-hidden="true"></i>
                        <span>{{ __('message.order') }}</span>
                    </div>
                    <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                    <p class="pds-rider-hero__subtitle">{{ __('message.rider_list_subtitle') }}</p>
                </div>
                <div class="pds-rider-hero__actions">
                    <button type="button" class="pds-rider-of-month-btn" id="pds-rider-of-month-btn">
                        <i class="fas fa-trophy" aria-hidden="true"></i>
                        <span>{{ __('message.rider_of_the_month') }}</span>
                    </button>
                    <div class="pds-rider-hero__stat">
                        <span class="pds-rider-hero__stat-value">{{ $riders->count() }}</span>
                        <span class="pds-rider-hero__stat-label">{{ __('message.rider_list') }}</span>
                    </div>
                </div>
            </div>

            @include('partials._branch-tabs', [
                'branchTabs' => $branchTabs ?? collect(),
                'selectedBranchId' => $selectedBranchId ?? null,
                'branchTabCounts' => $branchTabCounts ?? [],
                'includeAll' => false,
                'routeName' => 'order.dispatch.rider-list',
                'routeQuery' => array_filter([
                    'from_date' => $filterFromDate ?? null,
                    'to_date' => $filterToDate ?? null,
                    'rider_id' => ($riderFilter ?? 'all') !== 'all' ? $riderFilter : null,
                ]),
            ])

            <form method="GET" action="{{ route('order.dispatch.rider-list') }}" class="pds-rider-toolbar" id="riderListFilterForm">
                <input type="hidden" name="branch_id" value="{{ $branchFilter ?? '' }}">
                <div class="pds-rider-toolbar__fields">
                    <div class="pds-dispatch-field pds-dispatch-field-sm pds-rider-toolbar__grow">
                        <label for="rider_list_rider">{{ __('message.delivery_man') }}</label>
                        <select name="rider_id" id="rider_list_rider" class="pds-dispatch-input pds-dispatch-select">
                            <option value="all" @selected($riderFilter === 'all')>{{ __('message.all') }}</option>
                            @foreach($riderOptions as $option)
                                <option value="{{ $option->id }}" @selected((string) $riderFilter === (string) $option->id)>{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="rider_list_from_date">{{ __('message.from') }}</label>
                        <div class="pds-rider-date-wrap">
                            <input
                                type="text"
                                name="from_date"
                                id="rider_list_from_date"
                                class="pds-dispatch-input dispatch-datepicker"
                                value="{{ $filterFromDate }}"
                                placeholder="{{ __('message.select_date') }}"
                                autocomplete="off"
                                data-lpignore="true"
                                data-form-type="other"
                                readonly
                            >
                            <button type="button" class="pds-rider-date-clear" data-clear-date="rider_list_from_date" title="{{ __('message.reset') }}" aria-label="{{ __('message.reset') }}">
                                <i class="fas fa-times" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="rider_list_to_date">{{ __('message.to') }}</label>
                        <div class="pds-rider-date-wrap">
                            <input
                                type="text"
                                name="to_date"
                                id="rider_list_to_date"
                                class="pds-dispatch-input dispatch-datepicker"
                                value="{{ $filterToDate }}"
                                placeholder="{{ __('message.select_date') }}"
                                autocomplete="off"
                                data-lpignore="true"
                                data-form-type="other"
                                readonly
                            >
                            <button type="button" class="pds-rider-date-clear" data-clear-date="rider_list_to_date" title="{{ __('message.reset') }}" aria-label="{{ __('message.reset') }}">
                                <i class="fas fa-times" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="pds-rider-toolbar__actions">
                    <a href="{{ route('order.dispatch.rider-list', array_filter(['branch_id' => $selectedBranchId ?? null])) }}" class="pds-rider-reset-btn">
                        <i class="fas fa-eraser" aria-hidden="true"></i>
                        <span>{{ __('message.reset') }}</span>
                    </a>
                    <button type="submit" class="pds-rider-check-btn">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <span>{{ __('message.check') }}</span>
                    </button>
                </div>
            </form>

            <div class="pds-rider-body">
                @if($riders->isEmpty())
                    <div class="pds-rider-empty">
                        <div class="pds-rider-empty__icon"><i class="fas fa-motorcycle"></i></div>
                        <p>{{ __('message.no_record_found') }}</p>
                    </div>
                @else
                    <div class="pds-rider-table-shell pds-no-freeze">
                        <table class="table pds-rider-list-table">
                            <thead>
                                <tr>
                                    <th class="pds-rider-col-no">{{ __('message.no') }}</th>
                                    <th class="pds-rider-col-rider">{{ __('message.delivery_man') }}</th>
                                    <th class="pds-rider-col-rating">{{ __('message.rating') }}</th>
                                    <th class="pds-rider-col-count">
                                        <span class="pds-rider-status-chip pds-rider-status-chip--assigned">{{ __('message.follow_up_status_assigned') }}</span>
                                    </th>
                                    <th class="pds-rider-col-count">
                                        <span class="pds-rider-status-chip pds-rider-status-chip--onway">{{ __('message.follow_up_status_on_way') }}</span>
                                    </th>
                                    <th class="pds-rider-col-count">
                                        <span class="pds-rider-status-chip pds-rider-status-chip--delivered">{{ __('message.follow_up_status_delivered') }}</span>
                                    </th>
                                    <th class="pds-rider-col-count">
                                        <span class="pds-rider-status-chip pds-rider-status-chip--pending">{{ __('message.follow_up_status_pending') }}</span>
                                    </th>
                                    <th class="pds-rider-col-count">
                                        <span class="pds-rider-status-chip pds-rider-status-chip--return">{{ __('message.follow_up_status_return') }}</span>
                                    </th>
                                    <th class="pds-rider-col-count">
                                        <span class="pds-rider-status-chip pds-rider-status-chip--os-return">{{ __('message.follow_up_status_os_returned') }}</span>
                                    </th>
                                    <th class="pds-rider-col-count">
                                        <span class="pds-rider-status-chip pds-rider-status-chip--completed">{{ __('message.follow_up_status_completed') }}</span>
                                    </th>
                                    <th class="pds-rider-col-count">
                                        <span class="pds-rider-status-chip pds-rider-status-chip--finished">{{ __('message.follow_up_status_finished') }}</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($riders as $index => $rider)
                                    @php
                                        $counts = $rider->counts;
                                        $detailParams = array_filter([
                                            'from_date' => $filterFromDate ?: null,
                                            'to_date' => $filterToDate ?: null,
                                            'branch_id' => $selectedBranchId ?? null,
                                        ]);
                                        $initial = mb_strtoupper(mb_substr(trim($rider->name) ?: 'R', 0, 1));
                                        $avg = (float) ($rider->average_rating ?? 0);
                                        $ratingCount = (int) ($rider->ratings_count ?? 0);
                                    @endphp
                                    <tr data-rider-id="{{ $rider->id }}" data-rider-name="{{ $rider->name }}">
                                        <td class="pds-rider-col-no">{{ $index + 1 }}</td>
                                        <td class="pds-rider-col-rider">
                                            <div class="pds-rider-person">
                                                <span class="pds-rider-avatar" aria-hidden="true">{{ $initial }}</span>
                                                <div class="pds-rider-person__meta">
                                                    <div class="pds-dispatch-rider-list-name">{{ $rider->name }}</div>
                                                    <div class="pds-dispatch-rider-list-phone">{{ $rider->phone }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="pds-rider-col-rating">
                                            @if($ratingCount > 0)
                                                @php
                                                    $fullStars = (int) floor($avg);
                                                    $frac = $avg - $fullStars;
                                                    $halfStar = $frac >= 0.25 && $frac < 0.75;
                                                    if ($frac >= 0.75) {
                                                        $fullStars = min(5, $fullStars + 1);
                                                        $halfStar = false;
                                                    }
                                                    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                                                @endphp
                                                <button
                                                    type="button"
                                                    class="pds-rider-rating-chip js-rider-reviews"
                                                    data-rider-id="{{ $rider->id }}"
                                                    title="{{ __('message.reviews') }}"
                                                >
                                                    <div class="pds-rider-rating-chip__score">
                                                        <span>{{ number_format($avg, 2) }}</span>
                                                        <i class="fas fa-star" aria-hidden="true"></i>
                                                    </div>
                                                    <div class="pds-rider-rating-chip__stars" aria-hidden="true">
                                                        @for($s = 0; $s < $fullStars; $s++)
                                                            <i class="fas fa-star"></i>
                                                        @endfor
                                                        @if($halfStar)
                                                            <i class="fas fa-star-half-alt"></i>
                                                        @endif
                                                        @for($s = 0; $s < $emptyStars; $s++)
                                                            <i class="far fa-star"></i>
                                                        @endfor
                                                    </div>
                                                    <div class="pds-rider-rating-chip__count">{{ $ratingCount }} · {{ __('message.reviews') }}</div>
                                                </button>
                                            @else
                                                <span class="pds-rider-rating-empty">—</span>
                                            @endif
                                        </td>
                                        @foreach([
                                            'courier_assigned' => ['label' => __('message.follow_up_status_assigned'), 'tone' => 'assigned', 'status' => 'courier_assigned'],
                                            'courier_departed' => ['label' => __('message.follow_up_status_on_way'), 'tone' => 'onway', 'status' => 'courier_departed'],
                                            'delivered' => ['label' => __('message.follow_up_status_delivered'), 'tone' => 'delivered', 'status' => 'delivered'],
                                            'pending' => ['label' => __('message.follow_up_status_pending'), 'tone' => 'pending', 'status' => 'pending'],
                                            'return' => ['label' => __('message.follow_up_status_return'), 'tone' => 'return', 'status' => 'return'],
                                            'os_returned' => ['label' => __('message.follow_up_status_os_returned'), 'tone' => 'os-return', 'status' => 'os_returned'],
                                            'completed' => ['label' => __('message.follow_up_status_completed'), 'tone' => 'completed', 'status' => 'completed'],
                                            'finished' => ['label' => __('message.follow_up_status_finished'), 'tone' => 'finished', 'status' => 'finished'],
                                        ] as $meta)
                                            @php
                                                $count = (int) ($counts[$meta['status']] ?? 0);
                                                $linkStatus = $meta['status'];
                                            @endphp
                                            <td class="pds-rider-col-count">
                                                @if($count > 0)
                                                    <a
                                                        href="{{ route('order.dispatch.rider-items', array_merge(['riderId' => $rider->id, 'status' => $linkStatus], $detailParams)) }}"
                                                        class="pds-rider-count pds-rider-count--{{ $meta['tone'] }} is-link"
                                                        title="{{ $meta['label'] }}"
                                                    >{{ $count }}</a>
                                                @else
                                                    <span class="pds-rider-count pds-rider-count--muted">0</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="pds-os-receive-modal pds-rotm-modal" id="pdsRiderReviewsModal" hidden>
        <div class="pds-os-receive-modal__backdrop" data-close="reviews"></div>
        <div class="pds-os-receive-modal__dialog pds-rotm-dialog pds-rider-reviews-dialog" role="dialog" aria-modal="true">
            <header class="pds-rotm-header">
                <div class="pds-rotm-header__badge"><i class="fas fa-comments" aria-hidden="true"></i></div>
                <div>
                    <p class="pds-rotm-header__eyebrow">{{ __('message.reviews') }}</p>
                    <h5 id="pds-reviews-title">{{ __('message.reviews') }}</h5>
                </div>
            </header>
            <div id="pds-reviews-summary" class="pds-rider-reviews-summary"></div>
            <div id="pds-reviews-body" class="pds-rider-reviews-body"></div>
            <footer class="pds-os-receive-modal__footer">
                <button type="button" class="pds-os-receive-modal__btn is-ghost" id="pds-reviews-close">{{ __('message.close') }}</button>
            </footer>
        </div>
    </div>

    <div class="pds-os-receive-modal pds-rotm-modal" id="pdsRiderOfMonthModal" hidden>
        <div class="pds-os-receive-modal__backdrop" data-close="rotm"></div>
        <div class="pds-os-receive-modal__dialog pds-rotm-dialog" role="dialog" aria-modal="true">
            <header class="pds-rotm-header">
                <div class="pds-rotm-header__badge"><i class="fas fa-trophy" aria-hidden="true"></i></div>
                <div>
                    <p class="pds-rotm-header__eyebrow">{{ __('message.order') }}</p>
                    <h5 id="pds-rotm-title">{{ __('message.rider_of_the_month') }}</h5>
                </div>
            </header>
            <div id="pds-rotm-body" class="pds-rotm-body">
                <p class="pds-os-receive-modal__text">...</p>
            </div>
            <footer class="pds-os-receive-modal__footer">
                <button type="button" class="pds-os-receive-modal__btn is-ghost" id="pds-rotm-close">{{ __('message.close') }}</button>
            </footer>
        </div>
    </div>

    @section('bottom_script')
        <script>
            $(document).ready(function () {
                var hasDateFilter = @json((bool) ($hasDateFilter ?? false));
                var riderDatePickers = {};

                function bindRiderDatePicker(selector) {
                    var el = document.querySelector(selector);
                    if (!el || typeof flatpickr === 'undefined') {
                        return null;
                    }

                    var picker = flatpickr(el, {
                        dateFormat: 'd-m-Y',
                        allowInput: true,
                        defaultDate: null,
                        disableMobile: true,
                        onOpen: function (selectedDates, dateStr, instance) {
                            instance.input.removeAttribute('readonly');
                        },
                        onClose: function (selectedDates, dateStr, instance) {
                            if (!$.trim(instance.input.value || '')) {
                                instance.clear();
                            }
                            instance.input.setAttribute('readonly', 'readonly');
                        },
                    });

                    if (!hasDateFilter) {
                        picker.clear();
                        el.value = '';
                    }

                    riderDatePickers[el.id] = picker;
                    return picker;
                }

                bindRiderDatePicker('#rider_list_from_date');
                bindRiderDatePicker('#rider_list_to_date');

                // Defeat browser autofill that rewrites empty From/To fields.
                if (!hasDateFilter) {
                    [0, 50, 200, 500].forEach(function (delay) {
                        setTimeout(function () {
                            Object.keys(riderDatePickers).forEach(function (id) {
                                var picker = riderDatePickers[id];
                                if (!picker) return;
                                picker.clear();
                                picker.input.value = '';
                            });
                        }, delay);
                    });
                }

                $(document).on('click', '[data-clear-date]', function () {
                    var id = $(this).attr('data-clear-date');
                    var picker = riderDatePickers[id];
                    if (picker) {
                        picker.clear();
                        picker.input.value = '';
                        picker.input.focus();
                    } else {
                        $('#' + id).val('').focus();
                    }
                });

                var riderOfMonthUrl = @json($riderOfMonthUrl ?? route('deliveryman.rider-of-month'));
                var reviewsUrlTpl = @json(url('deliveryman/__ID__/reviews'));
                var $rotmModal = $('#pdsRiderOfMonthModal');
                var $reviewsModal = $('#pdsRiderReviewsModal');
                var emptyRotm = @json(__('message.rider_of_the_month_empty'));
                var ratingLabel = @json(__('message.rider_of_the_month_rating'));
                var finishedLabel = @json(__('message.rider_of_the_month_finished'));
                var rotmTitle = @json(__('message.rider_of_the_month'));
                var reviewsTitle = @json(__('message.reviews'));
                var noReviews = @json(__('message.no_record_found'));

                function closeRotm() { $rotmModal.attr('hidden', true); }
                function closeReviews() { $reviewsModal.attr('hidden', true); }

                function starsHtml(avg) {
                    var full = Math.floor(avg);
                    var frac = avg - full;
                    var half = frac >= 0.25 && frac < 0.75;
                    if (frac >= 0.75) { full = Math.min(5, full + 1); half = false; }
                    var empty = 5 - full - (half ? 1 : 0);
                    var html = '';
                    for (var i = 0; i < full; i++) html += '<i class="fas fa-star"></i>';
                    if (half) html += '<i class="fas fa-star-half-alt"></i>';
                    for (var j = 0; j < empty; j++) html += '<i class="far fa-star"></i>';
                    return html;
                }

                function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

                function openRotm() {
                    $('#pds-rotm-body').html('<div class="pds-rotm-loading"><i class="fas fa-spinner fa-spin"></i></div>');
                    $rotmModal.removeAttr('hidden');
                    $.getJSON(riderOfMonthUrl)
                        .done(function (res) {
                            var month = res.month_label ? String(res.month_label) : '';
                            $('#pds-rotm-title').text(rotmTitle + (month ? ' · ' + month : ''));
                            if (!res.rider) {
                                $('#pds-rotm-body').html('<p class="pds-rotm-empty">' + (res.message || emptyRotm) + '</p>');
                                return;
                            }
                            var r = res.rider;
                            var avg = Number(r.average_rating || 0);
                            var finished = Number(r.finished_ways || 0);
                            var name = esc(r.name || '-');
                            var img = r.profile_image
                                ? '<img class="pds-rotm-avatar" src="' + r.profile_image + '" alt="">'
                                : '<div class="pds-rotm-avatar pds-rotm-avatar--fallback"><i class="fas fa-user"></i></div>';
                            $('#pds-rotm-body').html(
                                '<div class="pds-rotm-winner">'
                                + img
                                + '<p class="pds-rotm-name">' + name + '</p>'
                                + '<div class="pds-rotm-stars">' + starsHtml(avg) + '</div>'
                                + '</div>'
                                + '<div class="pds-rotm-metrics">'
                                + '<div class="pds-rotm-metric pds-rotm-metric--rating">'
                                + '<div class="pds-rotm-metric__icon"><i class="fas fa-star"></i></div>'
                                + '<div class="pds-rotm-metric__copy"><span>' + ratingLabel + '</span><strong>' + avg.toFixed(2) + '</strong></div>'
                                + '</div>'
                                + '<div class="pds-rotm-metric pds-rotm-metric--ways">'
                                + '<div class="pds-rotm-metric__icon"><i class="fas fa-route"></i></div>'
                                + '<div class="pds-rotm-metric__copy"><span>' + finishedLabel + '</span><strong>' + finished.toLocaleString() + '</strong></div>'
                                + '</div>'
                                + '</div>'
                            );
                        })
                        .fail(function () {
                            $('#pds-rotm-body').html('<p class="pds-rotm-empty">' + emptyRotm + '</p>');
                        });
                }

                function openReviews(riderId) {
                    $('#pds-reviews-title').text(reviewsTitle);
                    $('#pds-reviews-summary').html('');
                    $('#pds-reviews-body').html('<div class="pds-rotm-loading"><i class="fas fa-spinner fa-spin"></i></div>');
                    $reviewsModal.removeAttr('hidden');
                    $.getJSON(reviewsUrlTpl.replace('__ID__', String(riderId)))
                        .done(function (res) {
                            var r = res.rider || {};
                            var avg = Number(r.average_rating || 0);
                            $('#pds-reviews-title').text((r.name || reviewsTitle));
                            $('#pds-reviews-summary').html(
                                '<div class="pds-rider-reviews-summary__score">'
                                + '<strong>' + avg.toFixed(2) + '</strong>'
                                + '<div class="pds-rotm-stars">' + starsHtml(avg) + '</div>'
                                + '<span>' + Number(r.ratings_count || 0) + ' ' + reviewsTitle + '</span>'
                                + '</div>'
                            );
                            var list = res.reviews || [];
                            if (!list.length) {
                                $('#pds-reviews-body').html('<p class="pds-rotm-empty">' + noReviews + '</p>');
                                return;
                            }
                            var html = '<div class="pds-rider-reviews-list">';
                            list.forEach(function (row) {
                                var comment = (row.comment || '').trim();
                                html += '<article class="pds-rider-review-card">'
                                    + '<header class="pds-rider-review-card__head">'
                                    + '<div><strong>' + esc(row.reviewer_name || '-') + '</strong>'
                                    + (row.item_code ? '<span class="pds-rider-review-card__meta">#' + esc(row.item_code) + '</span>' : '')
                                    + '</div>'
                                    + '<div class="pds-rider-review-card__stars">' + starsHtml(Number(row.rating || 0)) + '</div>'
                                    + '</header>'
                                    + (comment ? '<p class="pds-rider-review-card__comment">' + esc(comment) + '</p>' : '<p class="pds-rider-review-card__comment is-muted">—</p>')
                                    + '<footer class="pds-rider-review-card__foot">' + esc(row.created_at || '') + '</footer>'
                                    + '</article>';
                            });
                            html += '</div>';
                            $('#pds-reviews-body').html(html);
                        })
                        .fail(function () {
                            $('#pds-reviews-body').html('<p class="pds-rotm-empty">' + noReviews + '</p>');
                        });
                }

                $('#pds-rider-of-month-btn').on('click', openRotm);
                $('#pds-rotm-close, #pdsRiderOfMonthModal [data-close="rotm"]').on('click', closeRotm);
                $('#pds-reviews-close, #pdsRiderReviewsModal [data-close="reviews"]').on('click', closeReviews);
                $(document).on('click', '.js-rider-reviews', function () {
                    openReviews($(this).data('rider-id'));
                });
                $(document).on('keydown', function (e) {
                    if (e.key !== 'Escape') return;
                    if (!$rotmModal.is('[hidden]')) closeRotm();
                    if (!$reviewsModal.is('[hidden]')) closeReviews();
                });
            });
        </script>
    @endsection
</x-master-layout>
