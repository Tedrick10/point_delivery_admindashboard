<x-master-layout>
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <div class="row">
            <div class="col-lg-12">
                <div class="card pds-page-card">
                    <div class="card-header pds-page-header">
                        <h4 class="card-title pds-page-title mb-0">{{ $pageTitle ?? '' }}</h4>
                    </div>

                    <div class="card-body pds-page-body">
                        @include('report.reportofuserfilter')

                        <div class="pds-table-shell pds-report-table-shell">
                            <table id="basic-table" class="table w-100 pds-datatable pds-report-table text-center mb-0" role="grid">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('message.id') }}</th>
                                        <th scope="col">{{ __('message.order_id') }}</th>
                                        <th scope="col">{{ __('message.client') }}</th>
                                        <th scope="col">{{ __('message.delivery_man') }}</th>
                                        <th scope="col">{{ __('message.total_amount') }}</th>
                                        <th scope="col">{{ __('message.pickup_date_time') }}</th>
                                        <th scope="col">{{ __('message.delivery_date_time') }}</th>
                                        <th scope="col">{{ __('message.commission_type') }}</th>
                                        <th scope="col">{{ __('message.admin_commission') }}</th>
                                        <th scope="col">{{ __('message.delivery_man_commission') }}</th>
                                        <th scope="col">{{ __('message.created_at') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($orders as $order)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td><a href="{{ route('order.show', $order->id) }}">{{ $order->id }}</a></td>
                                            <td><a href="{{ route('users.show', $order->client_id) }}">{{ optional($order->client)->name ?? '-' }}</a></td>
                                            <td><a href="{{ route('deliveryman-view.show', $order->delivery_man_id) }}">{{ optional($order->delivery_man)->name ?? '-' }}</a></td>
                                            <td>{{ getPriceFormat($order->total_amount) ?? 0 }}</td>
                                            <td>{{ dateAgoFormate($order->pickup_datetime) ?? '-' }}</td>
                                            <td>{{ dateAgoFormate($order->delivery_datetime) ?? '-' }}</td>
                                            @php
                                                $commission_type = optional($order)->city->commission_type ?? '-';
                                                $deliveryman_commission = optional($order)->payment->delivery_man_commission ?? 0;
                                                $admin_commission = optional($order)->payment->admin_commission ?? 0;
                                            @endphp
                                            <td class="text-capitalize">{{ $commission_type }}</td>
                                            <td>{{ getPriceFormat($admin_commission) }}</td>
                                            <td>{{ getPriceFormat($deliveryman_commission) }}</td>
                                            <td>{{ dateAgoFormate($order->created_at) ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11" class="pds-report-empty">{{ __('message.no_record_found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="pds-report-total-row">
                                        <td class="font-weight-bold">{{ __('message.total_amount') }}</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td class="font-weight-bold">{{ getPriceFormat($totalAmountorder) }}</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td class="font-weight-bold">{{ getPriceFormat($totalAdminSum) }}</td>
                                        <td class="font-weight-bold">{{ getPriceFormat($totaldeliverymanSum) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @section('bottom_script')
        <script>
            $(document).ready(function() {
                $.validator.addMethod('greaterThanEqual', function(value, element, param) {
                    var fromDateValue = $(param).val();
                    if (!value || !fromDateValue) return true;
                    return new Date(value) >= new Date(fromDateValue);
                });

                $('#filter-form').validate({
                    rules: { to_date: { greaterThanEqual: '#from_date_main' } },
                    messages: { to_date: { greaterThanEqual: "{{ __('message.to_date_must_be_greater_than_from_date') }}" } },
                    errorPlacement: function(error, element) {
                        error.addClass('text-danger small');
                        if (element.attr('name') === 'from_date') {
                            $('#form_validation_from_date').html(error);
                        } else if (element.attr('name') === 'to_date') {
                            $('#form_validation_to_date').html(error);
                        } else {
                            error.insertAfter(element);
                        }
                    },
                    highlight: function(element) { $(element).addClass('is-invalid'); },
                    unhighlight: function(element) { $(element).removeClass('is-invalid'); }
                });

                var table = $('#basic-table').DataTable({
                    dom: 'rt<"pds-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3" <"pds-dt-length" l><"pds-dt-info" i><"pds-dt-paginate" p>>',
                    language: {
                        search: '',
                        searchPlaceholder: "{{ __('pagination.search') }}",
                        lengthMenu: "{{ __('pagination.show') . ' _MENU_ ' . __('pagination.entries') }}",
                        zeroRecords: "{{ __('pagination.no_records_found') }}",
                        info: "{{ __('pagination.showing') . ' _START_ ' . __('pagination.to') . ' _END_ ' . __('pagination.of') . ' _TOTAL_ ' . __('pagination.entries') }}",
                        infoFiltered: "{{ __('pagination.filtered_from_total') . ' _MAX_ ' . __('pagination.entries') }}",
                        infoEmpty: "{{ __('pagination.showing_entries') }}",
                        paginate: {
                            previous: "{{ __('pagination.__previous') }}",
                            next: "{{ __('pagination.__next') }}"
                        }
                    },
                    order: [[0, 'desc']],
                    pageLength: 10
                });

                $('#report-table-search').on('keyup search input', function() {
                    table.search(this.value).draw();
                });
            });
        </script>
    @endsection
</x-master-layout>
