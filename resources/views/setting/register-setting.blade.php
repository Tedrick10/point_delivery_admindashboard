{!! html()->modelForm($setting_value, 'POST', route('settingUpdate'))->open() !!}
{!! html()->hidden('id', null)->class('form-control') !!}
{!! html()->hidden('page', $page)->class('form-control') !!}
@php $valueIndex = 0; @endphp
<div class="row">
    @foreach($setting as $groupKey => $groupValues)
        <div class="col-md-12 col-sm-12 card shadow mb-4">
            <div class="card-header">
                <h4 class="mb-0">
                    @if($groupKey === 'user_registration_setting')
                        {{ __('message.users') }} — {{ __('message.register_setting') }}
                    @elseif($groupKey === 'driver_registration_setting')
                        {{ __('message.delivery_man') }} — {{ __('message.register_setting') }}
                    @else
                        {{ $pageTitle }}
                    @endif
                </h4>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($groupValues as $sub_key => $sub_value)
                        @php
                            $data = $setting_value->first(function ($item) use ($groupKey, $sub_key) {
                                return $item->type === $groupKey && $item->key === $sub_key;
                            }) ?? $setting_value->firstWhere('key', $sub_key);
                        @endphp
                        <div class="col-md-6 col-sm-12 mb-3">
                            <div class="custom-switch custom-switch-color custom-control-inline">
                                {!! html()->hidden('type[]', $groupKey) !!}
                                {!! html()->hidden('key[]', $sub_key) !!}
                                {!! html()->hidden('value['.$valueIndex.']', 0) !!}
                                {!! html()->checkbox('value['.$valueIndex.']', 1)
                                    ->checked(isset($data) && $data->value == 1)
                                    ->class('custom-control-input bg-success float-right')
                                    ->id($groupKey.'_'.$sub_key) !!}
                                {!! html()->label(__('message.' . $sub_key))->for($groupKey.'_'.$sub_key)->class('custom-control-label') !!}
                            </div>
                        </div>
                        @php $valueIndex++; @endphp
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
{!! html()->submit(__('message.save'))->class('btn btn-md btn-primary float-md-right') !!}
{!! html()->form()->close() !!}
