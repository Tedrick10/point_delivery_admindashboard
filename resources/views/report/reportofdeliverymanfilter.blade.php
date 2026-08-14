<div class="pds-report-toolbar">
    {!! html()->form('GET', route('report-of-deliveryman'))->id('filter-form')->class('pds-report-toolbar__form')->open() !!}
        <div class="pds-report-toolbar__field pds-report-toolbar__field--select">
            {!! html()->label(__('message.delivery_man') . ' <span class="text-danger">*</span>')->for('delivery_man_id')->class('pds-report-toolbar__label') !!}
            <span class="text-danger d-block small" id="form_validation_delivery_man_id"></span>
            {!! html()->select('delivery_man_id', $selectedDeliveryman, request('delivery_man_id'))
                ->class('select2js form-control')
                ->attribute('data-placeholder', __('message.delivery_man'))
                ->attribute('data-ajax--url', route('ajax-list', ['type' => 'deliveryman_name'])) !!}
        </div>

        <div class="pds-report-toolbar__field">
            {!! html()->label(__('message.from') . ' <span class="text-danger">*</span>')->for('from_date')->class('pds-report-toolbar__label') !!}
            <span class="text-danger d-block small" id="form_validation_from_date"></span>
            {!! html()->date('from_date', $params['from_date'] ?? request('from_date'))->class('form-control pds-report-toolbar__input')->id('from_date_main') !!}
        </div>

        <div class="pds-report-toolbar__field">
            {!! html()->label(__('message.to') . ' <span class="text-danger">*</span>')->for('to_date')->class('pds-report-toolbar__label') !!}
            <span class="text-danger d-block small" id="form_validation_to_date"></span>
            {!! html()->date('to_date', $params['to_date'] ?? request('to_date'))->class('form-control pds-report-toolbar__input')->id('to_date_main') !!}
        </div>

        <div class="pds-report-toolbar__actions">
            <button type="submit" class="btn btn-sm btn-primary">{{ __('message.apply_filter') }}</button>
            <a href="{{ route('report-of-deliveryman') }}" class="btn btn-sm btn-outline-primary">
                <i class="ri-repeat-line"></i> {{ __('message.reset_filter') }}
            </a>
            <button type="button" class="btn btn-sm btn-primary" id="export-button" data-toggle="modal" data-target="#exportModal">
                <i class="ri-download-line"></i> {{ __('message.export') }}
            </button>
        </div>
    {!! html()->form()->close() !!}

    <div class="pds-report-toolbar__search">
        <label class="pds-report-toolbar__label" for="report-table-search">{{ __('pagination.search') }}</label>
        <input type="search" id="report-table-search" class="form-control pds-report-toolbar__search-input" placeholder="{{ __('pagination.search') }}">
    </div>
</div>

@include('report.reportofdeliverymanexportmodel')

<script>
    $(document).ready(function() {
        $('#export-button').on('click', function(e) {
            if (!$('#filter-form select[name="delivery_man_id"]').val()) {
                e.preventDefault();
                $('#form_validation_delivery_man_id').text("{{ __('message.deliveryman_required') }}");
            } else {
                $('#form_validation_delivery_man_id').text('');
            }
        });
    });
</script>
