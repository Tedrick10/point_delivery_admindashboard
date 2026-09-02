<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-os-list-page">
        <div class="pds-os-list-screen">
            <div class="pds-os-list-hero">
                <div class="pds-os-list-hero__copy">
                    <div class="pds-os-list-hero__eyebrow">
                        <i class="fas fa-store" aria-hidden="true"></i>
                        <span>{{ __('message.online_shop') }}</span>
                    </div>
                    <h4 class="pds-os-list-hero__title">{{ $pageTitle ?? __('message.online_shop') }}</h4>
                    <p class="pds-os-list-hero__subtitle">{{ __('message.online_shop_list_subtitle') }}</p>
                </div>
                <div class="pds-os-list-hero__actions">
                    @if(isset($export))
                        {!! $export !!}
                    @endif
                    @if(isset($button))
                        {!! $button !!}
                    @endif
                </div>
            </div>

            <div class="pds-os-list-body">
                @php
                    $approvalTab = $approvalTab ?? 'pending';
                    $approvalCounts = $approvalCounts ?? ['pending' => 0, 'approved' => 0, 'rejected' => 0];
                    $tabQuery = request()->except('status');
                @endphp
                <div class="pds-os-list-tabs" role="tablist" aria-label="{{ __('message.approval_status') }}">
                    @foreach([
                        'pending' => ['label' => __('message.pending'), 'icon' => 'fas fa-clock'],
                        'approved' => ['label' => __('message.approved'), 'icon' => 'fas fa-check-circle'],
                        'rejected' => ['label' => __('message.rejected'), 'icon' => 'fas fa-times-circle'],
                    ] as $tabKey => $tabMeta)
                        <a
                            href="{{ route('users.index', array_merge($tabQuery, ['status' => $tabKey])) }}"
                            class="pds-os-list-tab {{ $approvalTab === $tabKey ? 'is-active' : '' }}"
                            data-tab="{{ $tabKey }}"
                            role="tab"
                            aria-selected="{{ $approvalTab === $tabKey ? 'true' : 'false' }}"
                        >
                            <i class="{{ $tabMeta['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $tabMeta['label'] }}</span>
                            <em>{{ $approvalCounts[$tabKey] ?? 0 }}</em>
                        </a>
                    @endforeach
                </div>

                @include('global.user-datatable')

                @if(isset($multi_checkbox_delete))
                    <div class="pds-os-list-bulk">
                        {!! $multi_checkbox_delete !!}
                    </div>
                @endif

                <div class="pds-os-list-table-shell">
                    {{ $dataTable->table(['class' => 'table w-100 pds-datatable pds-os-list-table'], false) }}
                </div>
            </div>
        </div>
    </div>

    @section('bottom_script')
       {{ $dataTable->scripts() }}
       <script>
           $(document).on('change', '.js-os-approval-status', function () {
               var $el = $(this);
               var url = $el.data('url');
               var value = $el.val();
               $el.prop('disabled', true);
               $el.attr('data-tone', value);
               $.ajax({
                   url: url,
                   method: 'POST',
                   data: {
                       _token: '{{ csrf_token() }}',
                       approval_status: value
                   }
               }).done(function (res) {
                   if (typeof SnackBar === 'function') {
                       SnackBar({ message: res.message || 'Updated', status: 'success' });
                   } else if (window.toastr) {
                       toastr.success(res.message || 'Updated');
                   }
                   if ($.fn.DataTable && $.fn.DataTable.isDataTable('.dataTable')) {
                       $('.dataTable').DataTable().ajax.reload(null, false);
                   }
               }).fail(function (xhr) {
                   var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Update failed';
                   if (typeof SnackBar === 'function') {
                       SnackBar({ message: msg, status: 'error' });
                   } else if (window.toastr) {
                       toastr.error(msg);
                   } else {
                       alert(msg);
                   }
               }).always(function () {
                   $el.prop('disabled', false);
               });
           });
       </script>
    @endsection
</x-master-layout>
