<?php


namespace App\Traits;

use Yajra\DataTables\Services\DataTable;

trait DataTableTrait {

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\Datatables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->ajax('')
            ->parameters($this->getBuilderParameters());
    }

    /**
     * Shared DataTables builder defaults (frozen header/footer via scrollY/scrollX).
     * Child classes that override getBuilderParameters() should call this — NOT parent:: —
     * because parent resolves to Yajra\DataTables\Services\DataTable.
     */
    protected function pdsDefaultBuilderParameters(): array
    {
        return [
            'lengthMenu'   => [[10, 50, 100, 500, -1], [10, 50, 100, 500, "All"]],
            // Freeze header/footer while body scrolls (all admin DataTables).
            'scrollX'      => true,
            'scrollY'      => 'calc(100vh - 320px)',
            'scrollCollapse' => true,
            'autoWidth'    => false,
            'ordering'     => false,
            'order'        => [],
           'sDom'          => '<"row align-items-center"<"col-md-2"><"col-md-6" B><"col-md-4"f>><"table-responsive my-3 pds-dt-scroll-host" rt><"d-flex" <"flex-grow-1" l><"p-2" i><"mt-4" p>><"clear">',
            'buttons' => [
                [
                    'extend' => 'print',
                    'text' => '<i class="fa fa-print"></i> Print',
                    'className' => 'btn btn-primary btn-sm',
                ],
                [
                    'extend' => 'csv',
                    'text' => '<i class="fa fa-file"></i> CSV',
                    'className' => 'btn btn-primary btn-sm',
                ]
            ],
            'drawCallback' => "function () {
                $('.dataTables_paginate > .pagination').addClass('justify-content-end mb-0');
                $('#dataTableBuilder th:first-child').removeClass('sorting_asc');
                if (this.api) {
                    try { this.api().columns.adjust(); } catch (e) {}
                }
                if (typeof window.pdsSyncDataTableFreeze === 'function') {
                    try { window.pdsSyncDataTableFreeze(this); } catch (e) {}
                }
                if($('.image-popup-vertical-fit').length > 0){
                    $('.image-popup-vertical-fit').magnificPopup({
                        type: 'image',
                        closeOnContentClick: true,
                        closeBtnInside: false,
                        mainClass: 'mfp-with-zoom',
                        zoom: {
                            enabled: true,
                            duration: 350,
                        }
                    });
                }
            }",
            'language' => [
                'search' => '',
                'searchPlaceholder' => 'Search',
            ],
            'initComplete' => "function () {
                if (window.jQuery && jQuery.fn.dataTable) {
                    jQuery.fn.dataTable.ext.errMode = function (settings, helpPage, message) {
                        if (window.console && console.warn) {
                            console.warn('DataTables:', message);
                        }
                    };
                }
                $('#dataTableBuilder_wrapper .dt-buttons button').removeClass('btn-secondary');
                var api = this.api();
                api.columns.adjust();
                if (typeof window.pdsSyncDataTableFreeze === 'function') {
                    try { window.pdsSyncDataTableFreeze(this); } catch (e) {}
                }
                $(window).on('resize', function () {
                    api.columns.adjust();
                    if (typeof window.pdsSyncDataTableFreeze === 'function') {
                        try { window.pdsSyncDataTableFreeze(api.table().node()); } catch (e) {}
                    }
                });
            }",
            'createdRow' => "function (row, data) {
                if (data.deleted_at) {
                    $(row).css('background-color', '#ffe6e6');
                    $(row).css('color', '#000000');
                }
            }"
        ];
    }

    public function getBuilderParameters(): array
    {
        return $this->pdsDefaultBuilderParameters();
    }
}
