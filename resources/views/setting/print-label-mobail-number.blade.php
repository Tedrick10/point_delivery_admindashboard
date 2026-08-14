    {!! html()->modelForm($setting_value, 'POST', route('settingUpdate'))->open() !!}
    {!! html()->hidden('id')->class('form-control') !!}
    {!! html()->hidden('page', $page)->class('form-control') !!}

    <div class="card shadow mb-10">
        <div class="card-header">
            <h4>{{ $pageTitle }}</h4>
        </div>
        <div class="card-body">
            <div class="row">
                @php $valueIndex = 0; @endphp
                @foreach($setting as $key => $value)
                    @foreach($value as $sub_key => $sub_value)
                        @php
                            $data = $setting_value->firstWhere('key', $sub_key);
                        @endphp
                        <div class="col-md-6 col-sm-12 mb-3">
                            <div class="form-group mb-0">
                                {!! html()->hidden('type[]', $key) !!}
                                {!! html()->hidden('key[]', $sub_key) !!}
                                <div class="custom-switch custom-switch-color custom-control-inline">
                                    {!! html()->hidden('value['.$valueIndex.']', 0) !!}
                                    {!! html()->checkbox('value['.$valueIndex.']', 1)
                                        ->checked(isset($data) && $data->value == 1)
                                        ->class('custom-control-input bg-success float-right')
                                        ->id($sub_key) !!}
                                    {!! html()->label(__('message.' . $sub_key))->for($sub_key)->class('custom-control-label') !!}
                                </div>
                            </div>
                        </div>
                        @php $valueIndex++; @endphp
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
    {!! html()->submit(__('message.save'))->class('btn btn-md btn-primary float-md-right') !!}
    {!! html()->form()->close() !!}
