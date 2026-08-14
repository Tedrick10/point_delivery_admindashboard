<div class="modal fade" id="dispatchItemGateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content pds-gate-modal">
            <div class="modal-header pds-gate-modal__header">
                <h5 class="modal-title mb-0">{{ __('message.update_gate') }}</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <h5 class="mb-0" id="dispatchItemGateCustomer">—</h5>
                </div>
                <div class="form-group">
                    <label for="dispatchItemGateAmount">{{ __('message.gate_amount') }}</label>
                    <input type="number" min="0" step="1" class="form-control pds-dispatch-input" id="dispatchItemGateAmount">
                </div>
                <div class="form-group">
                    <label for="dispatchItemGateOsPaid">{{ __('message.os_paid_for_gate') }}</label>
                    <input type="number" min="0" step="1" class="form-control pds-dispatch-input" id="dispatchItemGateOsPaid">
                </div>
                <p class="small text-muted mb-3" id="dispatchItemGateHint">{{ __('message.gate_deduction_hint') }}</p>
                <div class="form-group mb-0">
                    <label for="dispatchItemGateRemark">{{ __('message.remark') }}</label>
                    <input type="text" class="form-control pds-dispatch-input" id="dispatchItemGateRemark">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('message.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="dispatchItemGateSave">{{ __('message.update_gate') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function bootDispatchItemGate() {
        if (!window.jQuery) {
            return setTimeout(bootDispatchItemGate, 40);
        }
        var $ = window.jQuery;
        if (window.__pdsDispatchItemGateBound) return;
        window.__pdsDispatchItemGateBound = true;

        var gateUrl = null;
        var $activeGateBtn = null;

        $(document).on('click', '.js-dispatch-item-gate', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $btn = $(this);
            $activeGateBtn = $btn;
            gateUrl = $btn.attr('data-url') || $btn.data('url');
            $('#dispatchItemGateCustomer').text($btn.attr('data-customer') || $btn.data('customer') || '—');
            $('#dispatchItemGateAmount').val($btn.attr('data-gate-amount') || $btn.data('gate-amount') || 0);
            $('#dispatchItemGateOsPaid').val($btn.attr('data-gate-os') || $btn.data('gate-os') || 0);
            $('#dispatchItemGateRemark').val($btn.attr('data-remark') || $btn.data('remark') || '');
            $('#dispatchItemGateModal').modal('show');
        });

        $('#dispatchItemGateSave').on('click', function () {
            if (!gateUrl) return;
            var $save = $(this);
            $save.prop('disabled', true);
            $.ajax({
                url: gateUrl,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    gate_amount: $('#dispatchItemGateAmount').val() || 0,
                    gate_os_paid: $('#dispatchItemGateOsPaid').val() || 0,
                    remark: $('#dispatchItemGateRemark').val() || ''
                }
            }).done(function (res) {
                if ($activeGateBtn) {
                    $activeGateBtn
                        .attr('data-gate-amount', res.gate_amount)
                        .attr('data-gate-os', res.gate_os_paid)
                        .attr('data-remark', res.remark || '')
                        .data('gate-amount', res.gate_amount)
                        .data('gate-os', res.gate_os_paid)
                        .data('remark', res.remark || '');
                    var $row = $activeGateBtn.closest('tr');
                    $row.find('.js-item-gate-amount').text(Number(res.gate_amount || 0).toLocaleString());
                    var $osCell = $row.find('.js-item-os-to-pay');
                    if ($osCell.length) {
                        if ($osCell.hasClass('js-item-os-to-pay-signed')) {
                            var signed = Number(res.os_to_pay || 0);
                            $osCell
                                .text(signed.toLocaleString())
                                .toggleClass('pds-os-to-pay-negative', signed < 0);
                            $row.attr('data-os-to-pay', signed);
                        } else {
                            $osCell
                                .text(res.os_to_pay_display != null ? res.os_to_pay_display : Number(res.os_to_pay_slip || 0).toLocaleString())
                                .toggleClass('pds-os-to-pay-negative', !!res.os_to_pay_is_receive);
                            $row.attr('data-os-to-pay', res.os_to_pay_slip != null ? res.os_to_pay_slip : 0);
                        }
                    }
                }
                $('#dispatchItemGateModal').modal('hide');
                if (window.toastr && res.message) {
                    toastr.success(res.message);
                }
            }).fail(function (xhr) {
                alert((xhr.responseJSON && xhr.responseJSON.message) || 'Fail');
            }).always(function () {
                $save.prop('disabled', false);
            });
        });
    })();
</script>
