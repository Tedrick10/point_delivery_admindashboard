<div class="pds-report-toolbar">
    {!! html()->form('GET', route('report-adminEarning'))->id('filter-form')->class('pds-report-toolbar__form')->open() !!}
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
            <a href="{{ route('report-adminEarning') }}" class="btn btn-sm btn-outline-primary">
                <i class="ri-repeat-line"></i> {{ __('message.reset_filter') }}
            </a>
            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#exportModal">
                <i class="ri-download-line"></i> {{ __('message.export') }}
            </button>
        </div>
    {!! html()->form()->close() !!}

    <div class="pds-report-toolbar__search">
        <label class="pds-report-toolbar__label" for="report-table-search">{{ __('pagination.search') }}</label>
        <input type="search" id="report-table-search" class="form-control pds-report-toolbar__search-input" placeholder="{{ __('pagination.search') }}">
    </div>
</div>

@include('report.adminexportmodel')
