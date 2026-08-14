<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ $pageTitle }}</h4>
                </div>
                <div class="card-body">
                    {{ html()->modelForm($data, 'PATCH', route('welcome-promotion.update', $data->id))->open() }}
                    <div class="row">
                        <div class="form-group col-md-6">
                            {!! html()->label(__('message.title'))->class('form-control-label') !!}
                            {!! html()->text('title', old('title', $data->title))->class('form-control')->required() !!}
                        </div>
                        <div class="form-group col-md-6">
                            {!! html()->label(__('message.max_orders'))->class('form-control-label') !!}
                            {!! html()->number('max_orders', old('max_orders', $data->max_orders))->class('form-control')->attribute('min', 1)->required() !!}
                            <small class="text-muted">{{ __('message.welcome_promotion_desc') }}</small>
                        </div>
                        <div class="form-group col-md-4">
                            {!! html()->label(__('message.discount_type'))->class('form-control-label') !!}
                            {!! html()->select('discount_type', ['percentage' => __('message.percentage'), 'fixed' => __('message.fixed')], old('discount_type', $data->discount_type))->class('form-control select2js') !!}
                        </div>
                        <div class="form-group col-md-4">
                            {!! html()->label(__('message.discount_value'))->class('form-control-label') !!}
                            {!! html()->number('discount_value', old('discount_value', $data->discount_value))->class('form-control')->attribute('step', 'any')->attribute('min', 0)->required() !!}
                        </div>
                        <div class="form-group col-md-4">
                            {!! html()->label(__('message.status'))->class('form-control-label') !!}
                            {!! html()->select('status', ['1' => __('message.enable'), '0' => __('message.disable')], old('status', $data->status))->class('form-control select2js') !!}
                        </div>
                    </div>
                    <hr>
                    {!! html()->submit(__('message.update'))->class('btn btn-md btn-primary float-right') !!}
                    {{ html()->form()->close() }}
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
