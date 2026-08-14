<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch card-height pds-page-card">
                    <div class="card-header d-flex justify-content-between align-items-center pds-page-header">
                        <div class="header-title">
                            <h4 class="card-title mb-0 pds-page-title">{{ $pageTitle ?? ''}}</h4>
                        </div>

                        <div class="card-header-toolbar pds-page-actions">
                            @if(isset($button))
                            {!! $button !!}
                            @endif
                            @if(isset($helpbutton))
                            {!! $helpbutton !!}
                            @endif
                        </div>
                    </div>
                    <div class="card-body pds-page-body">
                        <div class="card-header-toolbar">
                            @if(isset($multi_checkbox_delete))
                               {!! $multi_checkbox_delete !!}
                            @endif
                        </div>
                        <div class="pds-table-shell">
                            {{ $dataTable->table(['class' => 'table w-100 pds-datatable'],false) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @section('bottom_script')
       {{ $dataTable->scripts() }}
    @endsection
</x-master-layout>
