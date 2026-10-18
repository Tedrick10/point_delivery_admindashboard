<style>
    /* Keep rider list header in normal flow — sticky th was hiding rows 1–2 when scrolling back up */
    #riderAssignModal .pds-dispatch-table-wrap {
        overscroll-behavior: contain;
    }
    #riderAssignModal .pds-dispatch-table thead th {
        position: static !important;
        top: auto !important;
        z-index: auto !important;
    }
    #riderAssignTableBody .pds-dispatch-table-row.is-keyboard-active > td {
        background: #ffedd5 !important;
        color: #9a3412 !important;
        font-weight: 700 !important;
        box-shadow: inset 0 -1px 0 #fb923c !important;
    }
    #riderAssignTableBody .pds-dispatch-table-row.is-keyboard-active > td:first-child {
        box-shadow: inset 5px 0 0 #ea580c, inset 0 -1px 0 #fb923c !important;
        position: relative;
        padding-left: 1.7rem !important;
    }
    #riderAssignTableBody .pds-dispatch-table-row.is-keyboard-active > td:first-child::before {
        content: '✓';
        position: absolute;
        left: 0.4rem;
        top: 50%;
        transform: translateY(-50%);
        color: #ea580c;
        font-weight: 900;
        font-size: 1rem;
        line-height: 1;
    }
</style>
<div class="modal fade pds-dispatch-modal" id="riderAssignModal" tabindex="-1" role="dialog" aria-labelledby="riderAssignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content pds-dispatch-modal-content">
            <div class="pds-dispatch-modal-header">
                <h5 class="pds-dispatch-modal-title" id="riderAssignModalLabel">{{ __('message.to_find_delivery_man') }}</h5>
                <button type="button" class="pds-dispatch-modal-close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="pds-dispatch-modal-body">
                <div class="pds-dispatch-field mb-4">
                    <label for="rider_modal_search">{{ __('message.search') }}</label>
                    <input type="text" id="rider_modal_search" class="pds-dispatch-input" placeholder="{{ __('message.delivery_man') }} / {{ __('message.phone') }}" autocomplete="off">
                </div>
                <div class="pds-dispatch-table-wrap" id="riderAssignTableWrap">
                    <table class="pds-dispatch-table" id="riderAssignTable">
                        <thead>
                            <tr>
                                <th style="width:60px">{{ __('message.no') }}</th>
                                <th>{{ __('message.delivery_man') }}</th>
                                <th style="width:140px">{{ __('message.branch') }}</th>
                                <th style="width:140px">{{ __('message.phone') }}</th>
                            </tr>
                        </thead>
                        <tbody id="riderAssignTableBody">
                            <tr class="pds-dispatch-empty-row">
                                <td colspan="4">{{ __('message.no_record_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
