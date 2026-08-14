
<?php
$auth_user = authSession();
?>
<div class="d-flex justify-content-end align-items-center">
    @if ($auth_user->can('delivery-man-payout-reports-edit'))
        <a class="mr-2 loadRemoteModel" href="{{ route('deliveryman-payout-reports.edit', $id) }}"
            title="{{ __('message.update_form_title', ['form' => __('message.delivery_man_payout_reports')]) }}"><i
                class="fas fa-edit text-primary"></i></a>
    @endif
</div>
