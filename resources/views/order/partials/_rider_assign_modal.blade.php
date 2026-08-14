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
                    <input type="text" id="rider_modal_search" class="pds-dispatch-input" placeholder="{{ __('message.search') }}" autocomplete="off">
                </div>
                <div class="pds-dispatch-table-wrap">
                    <table class="pds-dispatch-table" id="riderAssignTable">
                        <thead>
                            <tr>
                                <th style="width:60px">{{ __('message.no') }}</th>
                                <th>{{ __('message.delivery_man') }}</th>
                                <th style="width:140px">{{ __('message.city_name') }}</th>
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
