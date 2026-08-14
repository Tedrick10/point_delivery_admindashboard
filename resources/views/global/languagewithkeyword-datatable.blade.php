<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <div class="row">
            <div class="col-lg-12">
                <div class="card pds-page-card">
                    <div class="card-header pds-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="card-title pds-page-title mb-0">{{ $pageTitle ?? '' }}</h4>
                        <div class="pds-page-actions d-flex flex-wrap gap-2">
                            @if(!empty($button))
                                {!! $button !!}
                            @endif
                            @if(isset($pdfbutton))
                                {!! $pdfbutton !!}
                            @endif
                            @if(isset($import_file_button))
                                {!! $import_file_button !!}
                            @endif
                        </div>
                    </div>

                    <div class="card-body pds-page-body">
                        @include('app-language-setting.languagewithkeyword.languagewithkeyword-filter')

                        @if(isset($delete_checkbox_checkout))
                            <div class="mb-2">{!! $delete_checkbox_checkout !!}</div>
                        @endif

                        <div class="pds-table-shell">
                            {{ $dataTable->table(['class' => 'table w-100 pds-datatable'], false) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @section('bottom_script')
        {{ $dataTable->scripts() }}
        <script>
            $(document).ready(function() {
                $('.select2Clear').select2({ width: '100%', allowClear: true });
            });
        </script>
    @endsection
</x-master-layout>
