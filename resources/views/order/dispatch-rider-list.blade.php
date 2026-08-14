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
                <div class="pds-rider-hero__stat">
                    <span class="pds-rider-hero__stat-value">{{ $riders->count() }}</span>
                    <span class="pds-rider-hero__stat-label">{{ __('message.delivery_man') }}</span>
                </div>
            </div>

            <form method="GET" action="{{ route('order.dispatch.rider-list') }}" class="pds-rider-toolbar" id="riderListFilterForm">
                <div class="pds-rider-toolbar__fields">
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="rider_list_status">{{ __('message.status') }}</label>
                        <select name="status" id="rider_list_status" class="pds-dispatch-input pds-dispatch-select">
                            <option value="active" @selected($statusFilter === 'active')>{{ __('message.active') }}</option>
                            <option value="all" @selected($statusFilter === 'all')>{{ __('message.all') }}</option>
                        </select>
                    </div>
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
                        <input type="text" name="from_date" id="rider_list_from_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $filterFromDate }}" autocomplete="off">
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="rider_list_to_date">{{ __('message.to') }}</label>
                        <input type="text" name="to_date" id="rider_list_to_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $filterToDate }}" autocomplete="off">
                    </div>
                </div>
                <button type="submit" class="pds-rider-check-btn">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <span>{{ __('message.check') }}</span>
                </button>
            </form>

            <div class="pds-rider-body">
                @if($riders->isEmpty())
                    <div class="pds-rider-empty">
                        <div class="pds-rider-empty__icon"><i class="fas fa-motorcycle"></i></div>
                        <p>{{ __('message.no_record_found') }}</p>
                    </div>
                @else
                    <div class="pds-rider-table-shell">
                        <table class="table pds-rider-list-table">
                            <thead>
                                <tr>
                                    <th class="pds-rider-col-no">{{ __('message.no') }}</th>
                                    <th class="pds-rider-col-rider">{{ __('message.delivery_man') }}</th>
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
                                        $detailParams = [
                                            'from_date' => $filterFromDate,
                                            'to_date' => $filterToDate,
                                        ];
                                        $initial = mb_strtoupper(mb_substr(trim($rider->name) ?: 'R', 0, 1));
                                    @endphp
                                    <tr>
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
                                        @foreach([
                                            'courier_assigned' => ['label' => __('message.follow_up_status_assigned'), 'tone' => 'assigned', 'status' => 'courier_assigned'],
                                            'courier_departed' => ['label' => __('message.follow_up_status_on_way'), 'tone' => 'onway', 'status' => 'courier_departed'],
                                            'delivered' => ['label' => __('message.follow_up_status_delivered'), 'tone' => 'delivered', 'status' => 'delivered'],
                                            'pending' => ['label' => __('message.follow_up_status_pending'), 'tone' => 'pending', 'status' => 'pending'],
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

    @section('bottom_script')
        <script>
            $(document).ready(function () {
                if (typeof flatpickr !== 'undefined') {
                    flatpickr('.dispatch-datepicker', {
                        dateFormat: 'd-m-Y',
                        allowInput: true,
                    });
                }
            });
        </script>
    @endsection
</x-master-layout>
