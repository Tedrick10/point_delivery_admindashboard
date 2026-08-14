<!-- Modal -->

<div class="modal-dialog" role="document">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">{{ __('message.export') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="exportForm" method="get">
                    <div class="form-group">
                        <label for="date_range" class="d-flex mb-3">{{ __('message.select_date') }}</label>
                        <input type="text" class="form-control" id="date_range" name="date_range"
                            placeholder="Select Date Range">

                        <input type="hidden" id="from_date" name="from_date" value="{{ request('from_date') }}">
                        <input type="hidden" id="to_date" name="to_date" value="{{ request('to_date') }}">
                    </div>
                    <div class="d-flex justify-content-center mb-3">
                        <div class="btn-group btn-group-toggle" data-toggle="buttons">
                            <label class="btn btn-outline-primary">
                                <input type="radio" name="duration" id="duration3" value="3months"> 3 Months
                            </label>
                            <label class="btn btn-outline-primary">
                                <input type="radio" name="duration" id="duration6" value="6months"> 6 Months
                            </label>
                            <label class="btn btn-outline-primary">
                                <input type="radio" name="duration" id="duration12" value="1year"> 1 Year
                            </label>
                        </div>
                    </div>
                    <div class="d-flex justify-content-center mb-3">
                        <div class="btn-group btn-group-toggle" data-toggle="buttons">
                            <label class="btn btn-secondary active">
                                <input type="radio" name="options" id="option1" value="xlsx" checked>
                                {{ __('message.xlsx') }}
                            </label>
                            <label class="btn btn-secondary">
                                <input type="radio" name="options" id="option2" value="xls">
                                {{ __('message.xls') }}
                            </label>
                            <label class="btn btn-secondary">
                                <input type="radio" name="options" id="option3" value="ods">
                                {{ __('message.ods') }}
                            </label>
                            <label class="btn btn-secondary">
                                <input type="radio" name="options" id="option4" value="csv">
                                {{ __('message.csv') }}
                            </label>
                            <label class="btn btn-secondary">
                                <input type="radio" name="options" id="option5" value="pdf">
                                {{ __('message.pdfs') }}
                            </label>
                            <label class="btn btn-secondary">
                                <input type="radio" name="options" id="option6" value="html">
                                {{ __('message.html') }}
                            </label>
                        </div>
                    </div>
                    <hr>
                    <h6 class="d-flex mb-3">{{ __('message.select_columns') }}</h6>
                    <div class="row">
                        <div class="col-md-6 mr-4">
                            @foreach (['id', 'name', 'email', 'username', 'address', 'contact_number', 'country', 'city', 'status', 'app_version', 'app_source', 'referral_code', 'created_at'] as $column)
                                <div class="d-flex">
                                    <input type="checkbox" name="columns[]" value="{{ $column }}" checked>
                                    <label class="form-check-label ml-2">{{ __('message.' . $column) }}</label>
                                </div>
                            @endforeach
                        </div>
                        <div class="col-md-6">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('message.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="downloadBtn">{{ __('message.download') }}</button>
            </div>
            {!! html()->form()->close() !!}
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        flatpickr("#date_range", {
            mode: "range",
            dateFormat: "Y-m-d",
            onChange: function(selectedDates) {
                if (selectedDates.length === 2) {
                    var fromDate = selectedDates[0].toISOString().split('T')[0];
                    var toDate = selectedDates[1].toISOString().split('T')[0];
                    $('#from_date').val(fromDate);
                    $('#to_date').val(toDate);
                    $('input[name="duration"]').prop('checked', false); // reset duration selection
                }
            }
        });
        $('input[name="duration"]').on('change', function() {
            var today = new Date();
            var toDate = today.toISOString().split('T')[0];
            var fromDate = new Date();

            if (this.value === '3months') {
                fromDate.setMonth(today.getMonth() - 3);
            } else if (this.value === '6months') {
                fromDate.setMonth(today.getMonth() - 6);
            } else if (this.value === '1year') {
                fromDate.setFullYear(today.getFullYear() - 1);
            }

            var fromDateStr = fromDate.toISOString().split('T')[0];
            $('#from_date').val(fromDateStr);
            $('#to_date').val(toDate);
            $('#date_range').val(fromDateStr + " to " + toDate);
        });

        $('#downloadBtn').on('click', function() {
            var fileType = $('input[name="options"]:checked').val();
            var columns = $('input[name="columns[]"]:checked').map(function() {
                return $(this).val();
            }).get();

            var baseUrl = '{{ url('/') }}';
            var url = fileType === 'pdf' ?
                baseUrl + '/download-deliveryman-pdf' :
                baseUrl + '/download-deliveryman/' + fileType;

            var fromDate = $('#from_date').val();
            var toDate = $('#to_date').val();
            if (!fromDate || !toDate) {
                alert("Please select a date range or duration before downloading.");
                return;
            }
            var queryString = $.param({
                columns: columns,
                from_date: fromDate,
                to_date: toDate,
            });

            window.location.href = url + '?' + queryString;
            setTimeout(function() {
                location.reload();
            }, 1000);
        });
    });
    $('#parent_id').select2({
        width: '100%',
        placeholder: "{{ __('message.select_name', ['select' => __('message.parent_permission')]) }}",
    });
</script>
