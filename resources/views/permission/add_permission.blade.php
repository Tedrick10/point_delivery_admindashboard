<!-- Modal -->

<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">{{ $title }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        {{ html()->form('POST', route('permission.save'))->attribute('data-toggle', 'validator')->open() }}

        <div class="modal-body">
           {{ html()->hidden('type',$type) }}
           {{ html()->hidden('id',-1) }}
           <div class="row">
                <div class="col-md-12 form-group">
                    {!! html()->label(__('message.name').' <span class="text-danger">*</span>')->class('form-control-label') !!}
                    {!! html()->text('name', null)->placeholder(__('message.name'))->class('form-control')->required() !!}
                </div>
            </div>
            @if( $type == 'role' )
                <div class="row">
                    <div class="col-md-12 form-group">
                        {!! html()->label(__('message.employee_type').' <span class="text-danger">*</span>')->class('form-control-label') !!}
                        <select name="employee_type_id" id="employee_type_id" class="form-control select2js">
                            <option value="">{{ __('message.select_name',['select' => __('message.employee_type')]) }}</option>
                            @foreach($employeeTypes as $employeeType)
                                <option value="{{ $employeeType->id }}">{{ $employeeType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 form-group">
                        {!! html()->label(__('message.new_employee_type'))->class('form-control-label') !!}
                        {!! html()->text('employee_type_name', null)->placeholder(__('message.new_employee_type_placeholder'))->class('form-control') !!}
                        <small class="text-muted">{{ __('message.new_employee_type_hint') }}</small>
                    </div>
                </div>
            @endif
            @if( $type == 'permission' )
                <div class="row">
                    <div class="col-md-12 form-group">
                    {!! html()->label(__('message.parent_permission'))->for('parent_id')->class('form-control-label') !!}
                    <select name="parent_id" id="parent_id" class="select2js form-control" data-ajax--url="{{ route('ajax-list', ['type' => 'permission']) }}" data-ajax--cache = "true">

                    </select>
                    </div>
                </div>
            @endif
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-md btn-secondary" data-dismiss="modal">{{ __('message.close') }}</button>
            <button type="submit" class="btn btn-md btn-primary" id="btn_submit" data-form="ajax" >{{ __('message.save') }}</button>
        </div>
        {{ html()->form()->close() }}
    </div>
</div>
<script>
    @if( $type == 'role' )
    $('#employee_type_id').select2({
        width: '100%',
        placeholder: "{{ __('message.select_name',['select' => __('message.employee_type')]) }}",
        dropdownParent: $('#remoteModelData')
    });
    @endif
    @if( $type == 'permission' )
    $('#parent_id').select2({
        width: '100%',
        placeholder: "{{ __('message.select_name',['select' => __('message.parent_permission')]) }}",
    });
    @endif
</script>

