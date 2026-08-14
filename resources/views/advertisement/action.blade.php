<div class="d-flex justify-content-end align-items-center">
    @if($row->approval_status === 'pending')
        <a href="{{ route('advertisement.approve', $id) }}" class="btn btn-sm btn-success mr-1" title="{{ __('message.approve') }}"><i class="fa fa-check"></i></a>
        <a href="{{ route('advertisement.reject', $id) }}" class="btn btn-sm btn-warning mr-1" title="{{ __('message.reject') }}"><i class="fa fa-times"></i></a>
    @endif
    @can('advertisement-edit')
        <a href="{{ route('advertisement.edit', $id) }}" class="btn btn-sm btn-primary mr-1"><i class="fa fa-edit"></i></a>
    @endcan
    @can('advertisement-delete')
        <a href="javascript:void(0)" class="btn btn-sm btn-danger" onclick="confirmActionAjax('{{ route('advertisement.destroy', $id) }}', 'DELETE')"><i class="fa fa-trash"></i></a>
    @endcan
</div>
