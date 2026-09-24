@php
    $dueValue = trim((string) ($dueValue ?? ''));
    $overdueDays = (int) ($overdueDays ?? 0);
    $canEditDue = (bool) ($canEditDue ?? false);
    $saveUrl = (string) ($saveUrl ?? '');
    $osId = isset($osId) ? (int) $osId : null;
    $itemId = isset($itemId) ? (int) $itemId : null;
@endphp
@if($canEditDue && $saveUrl !== '')
    <div class="pds-kyo-shin-due-cell {{ $overdueDays > 0 ? 'is-overdue' : '' }}">
        <label class="pds-kyo-shin-due-editor">
            <i class="far fa-calendar-alt" aria-hidden="true"></i>
            <input type="text"
                   class="pds-kyo-shin-due-input"
                   value="{{ $dueValue }}"
                   placeholder="{{ __('message.kyo_shin_due_date') }}"
                   data-due-url="{{ $saveUrl }}"
                   @if($osId !== null) data-os-id="{{ $osId }}" @endif
                   @if($itemId !== null) data-item-id="{{ $itemId }}" @endif
                   autocomplete="off"
                   aria-label="{{ __('message.kyo_shin_due_date') }}">
            <span class="pds-kyo-shin-due-edit-icon" title="{{ __('message.kyo_shin_edit_due') }}">
                <i class="fas fa-pen" aria-hidden="true"></i>
            </span>
        </label>
        @if($overdueDays > 0)
            <small>{{ __('message.kyo_shin_overdue_days', ['days' => $overdueDays]) }}</small>
        @endif
    </div>
@else
    <span class="{{ $overdueDays > 0 ? 'pds-kyo-shin-overdue' : '' }}">
        {{ $dueValue !== '' ? $dueValue : '-' }}
        @if($overdueDays > 0)
            <small>{{ __('message.kyo_shin_overdue_days', ['days' => $overdueDays]) }}</small>
        @endif
    </span>
@endif
