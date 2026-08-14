<div class="modal fade pds-dispatch-modal" id="osSearchModal" tabindex="-1" role="dialog" aria-labelledby="osSearchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content pds-dispatch-modal-content">
            <div class="pds-dispatch-modal-header">
                <h5 class="pds-dispatch-modal-title" id="osSearchModalLabel">{{ __('message.to_find_os_name') }}</h5>
                <button type="button" class="pds-dispatch-modal-close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="pds-dispatch-modal-body">
                <div class="pds-dispatch-field mb-4">
                    <label for="os_modal_search">{{ __('message.search') }}</label>
                    <input type="text" id="os_modal_search" class="pds-dispatch-input" placeholder="{{ __('message.search') }}" autocomplete="off">
                </div>
                <div class="pds-dispatch-table-wrap">
                    <table class="pds-dispatch-table" id="osSearchTable">
                        <thead>
                            <tr>
                                <th style="width:60px">{{ __('message.no') }}</th>
                                <th>{{ __('message.name') }}</th>
                                <th style="width:160px">{{ __('message.phone') }}</th>
                            </tr>
                        </thead>
                        <tbody id="osSearchTableBody">
                            <tr class="pds-dispatch-empty-row">
                                <td colspan="3">{{ __('message.no_record_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
