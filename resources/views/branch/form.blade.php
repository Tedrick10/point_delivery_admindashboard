<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <?php $id = $id ?? null; ?>
        @if(isset($id))
            {{ html()->modelForm($data, 'PATCH', route('branch.update', $id))->id('branch_form')->open() }}
        @else
            {{ html()->form('POST', route('branch.store'))->id('branch_form')->open() }}
        @endif

        <div class="row">
            <div class="col-lg-12">
                <div class="card pds-page-card">
                    <div class="card-header pds-page-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title pds-page-title mb-0">{{ $pageTitle }}</h4>
                        <a href="{{ route('branch.index') }}" class="btn btn-sm btn-primary">{{ __('message.back') }}</a>
                    </div>

                    <div class="card-body pds-page-body">
                        <div class="row">
                            <div class="form-group col-md-6">
                                {{ html()->label(__('message.branch_name').' <span class="text-danger">*</span>')->class('form-control-label') }}
                                {{ html()->text('name', old('name'))->placeholder(__('message.branch_name'))->class('form-control')->required() }}
                            </div>
                            <div class="form-group col-md-3">
                                {{ html()->label(__('message.code'))->class('form-control-label') }}
                                {{ html()->text('code', old('code'))->placeholder('MDY')->class('form-control') }}
                            </div>
                            <div class="form-group col-md-3">
                                {{ html()->label(__('message.city_name'))->class('form-control-label') }}
                                {{ html()->text('city_name', old('city_name'))->placeholder('Mandalay')->class('form-control') }}
                            </div>
                            <div class="form-group col-md-6">
                                {{ html()->label(__('message.address'))->class('form-control-label') }}
                                {{ html()->text('address', old('address'))->class('form-control') }}
                            </div>
                            <div class="form-group col-md-3">
                                {{ html()->label(__('message.phone'))->class('form-control-label') }}
                                {{ html()->text('phone', old('phone'))->class('form-control') }}
                            </div>
                            <div class="form-group col-md-6">
                                {{ html()->label(__('message.status').' <span class="text-danger">*</span>')->class('form-control-label') }}
                                {{ html()->select('status', ['1' => __('message.enable'), '0' => __('message.disable')], old('status', 1))
                                    ->class('form-control select2js')
                                    ->required() }}
                            </div>
                        </div>
                        <hr>
                        {{ html()->submit(isset($id) ? __('message.update') : __('message.save'))->class('btn btn-md btn-primary float-right') }}
                    </div>
                </div>
            </div>
        </div>
        {{ html()->form()->close() }}
    </div>
</x-master-layout>
