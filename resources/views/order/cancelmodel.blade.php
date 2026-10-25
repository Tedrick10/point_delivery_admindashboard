{{-- Cancelled Order modal — custom dropdown (Myanmar-safe, no clipping) --}}
@php
    $mm = static function (string $key, ?string $fallback = null): string {
        $text = trans('message.'.$key, [], 'my');
        if (! is_string($text) || $text === 'message.'.$key) {
            return $fallback ?? $key;
        }
        return $text;
    };
    $reasons = [
        'Place order by mistake' => $mm('order_by_mistake', 'မှားပြီး မှာယူမိခြင်း'),
        'Delivery time is too long' => $mm('time_too_long', 'ပို့ဆောင်ချိန် ကြာလွန်းခြင်း'),
        'Duplicate order' => $mm('duplicate_order', 'အော်ဒါ ထပ်နေခြင်း'),
        'Change of mind' => $mm('change_mind', 'စိတ်ပြောင်းသွားခြင်း'),
        'Change order' => $mm('change_order', 'အော်ဒါ ပြောင်းလိုခြင်း'),
        'Incorrect/incomplete address' => $mm('incomplated_address', 'လိပ်စာ မမှန် / မပြည့်စုံ'),
        'Other' => $mm('other', 'အခြား'),
    ];
    $selectedReason = old('reason', 'Place order by mistake');
    if (! array_key_exists($selectedReason, $reasons)) {
        $selectedReason = 'Place order by mistake';
    }
    $selectedLabel = $reasons[$selectedReason];
@endphp
<div class="modal-dialog modal-dialog-centered pds-cancel-order-dialog" role="document">
    {{ html()->form('POST', route('ordercancel.save'))->attribute('class', 'pds-cancel-order-form')->open() }}
    {{ html()->hidden('id', $id) }}
    <input type="hidden" name="reason" id="reason_select" value="{{ $selectedReason }}">
    <div class="modal-content pds-cancel-order-modal">
        <div class="pds-cancel-order-modal__accent" aria-hidden="true"></div>
        <div class="modal-header pds-cancel-order-modal__header">
            <div class="pds-cancel-order-modal__title-wrap">
                <span class="pds-cancel-order-modal__icon" aria-hidden="true"><i class="fas fa-ban"></i></span>
                <div class="pds-cancel-order-modal__copy">
                    <h5 class="modal-title pds-cancel-order-modal__title">အော်ဒါ ပယ်ဖျက်ရန်</h5>
                    <p class="pds-cancel-order-modal__subtitle">
                        <span class="pds-cancel-order-modal__chip">
                            <i class="fas fa-hashtag" aria-hidden="true"></i>
                            {{ $mm('order', 'အော်ဒါ') }} #{{ $id }}
                        </span>
                    </p>
                </div>
            </div>
            <button type="button" class="close pds-cancel-order-modal__close" data-dismiss="modal" aria-label="{{ $mm('close', 'ပိတ်ရန်') }}">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <div class="modal-body pds-cancel-order-modal__body">
            <p class="pds-cancel-order-modal__hint">{{ $mm('cancel_reason_hint', 'အော်ဒါ ပယ်ဖျက်ရသည့် အကြောင်းရင်းကို ရွေးပါ') }}</p>

            <div class="pds-cancel-order-field">
                <span class="pds-cancel-order-label" id="cancel_reason_label">{{ $mm('reason', 'အကြောင်းပြချက်') }}</span>
                <div class="pds-cancel-dd" data-pds-cancel-dd>
                    <button
                        type="button"
                        class="pds-cancel-dd__toggle"
                        id="cancel_reason_toggle"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                        aria-labelledby="cancel_reason_label cancel_reason_toggle"
                    >
                        <span class="pds-cancel-dd__value" id="cancel_reason_value">{{ $selectedLabel }}</span>
                        <span class="pds-cancel-dd__chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></span>
                    </button>
                    <div class="pds-cancel-dd__panel" role="listbox" aria-labelledby="cancel_reason_label" hidden>
                        @foreach($reasons as $value => $label)
                            @php $isActive = $selectedReason === $value; @endphp
                            <button
                                type="button"
                                class="pds-cancel-dd__option{{ $isActive ? ' is-active' : '' }}"
                                role="option"
                                aria-selected="{{ $isActive ? 'true' : 'false' }}"
                                data-reason="{{ $value }}"
                                data-label="{{ $label }}"
                            >
                                <span class="pds-cancel-dd__check" aria-hidden="true"><i class="fas fa-check"></i></span>
                                <span class="pds-cancel-dd__text">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="pds-cancel-order-field pds-cancel-order-field--other{{ $selectedReason === 'Other' ? '' : ' is-collapsed' }}" id="other_reason_field" aria-hidden="{{ $selectedReason === 'Other' ? 'false' : 'true' }}">
                <div class="pds-cancel-order-other-card">
                    <div class="pds-cancel-order-other-card__head">
                        <span class="pds-cancel-order-other-card__badge" aria-hidden="true"><i class="fas fa-pen"></i></span>
                        <div>
                            <label class="pds-cancel-order-label" for="other_reason">{{ $mm('cancel_other_reason', 'အခြား အကြောင်းရင်း') }}</label>
                            <p class="pds-cancel-order-other-card__help">{{ $mm('cancel_other_placeholder', 'အကြောင်းရင်းကို ဤနေရာတွင် ရေးပါ…') }}</p>
                        </div>
                    </div>
                    {{ html()->textarea('other_reason')
                        ->class('form-control pds-cancel-order-textarea')
                        ->id('other_reason')
                        ->rows(4)
                        ->attribute('placeholder', $mm('cancel_other_placeholder', 'အကြောင်းရင်းကို ဤနေရာတွင် ရေးပါ…'))
                        ->attribute('autocomplete', 'off') }}
                </div>
            </div>
        </div>

        <div class="modal-footer pds-cancel-order-modal__footer">
            <button type="button" class="btn pds-cancel-order-btn pds-cancel-order-btn--ghost" data-dismiss="modal">
                {{ $mm('close', 'ပိတ်ရန်') }}
            </button>
            <button type="submit" class="btn pds-cancel-order-btn pds-cancel-order-btn--primary">
                <i class="fas fa-check" aria-hidden="true"></i>
                <span>{{ $mm('submit', 'တင်မည်') }}</span>
            </button>
        </div>
    </div>
    {{ html()->form()->close() }}
</div>

<script>
(function ($) {
    var $modal = $('#remoteModelData');
    var $reason = $modal.find('#reason_select');
    var $other = $modal.find('#other_reason_field');
    var $dd = $modal.find('[data-pds-cancel-dd]');
    var $toggle = $dd.find('.pds-cancel-dd__toggle');
    var $panel = $dd.find('.pds-cancel-dd__panel');
    var $value = $dd.find('.pds-cancel-dd__value');
    var $options = $dd.find('.pds-cancel-dd__option');

    function toggleOther(reason) {
        var isOther = String(reason || '') === 'Other';
        if (isOther) {
            $other.removeClass('is-collapsed').attr('aria-hidden', 'false');
            window.setTimeout(function () { $other.find('textarea').trigger('focus'); }, 40);
        } else {
            $other.addClass('is-collapsed').attr('aria-hidden', 'true');
            $other.find('textarea, input').val('');
        }
    }

    function clearPanelPosition() {
        $dd.removeClass('is-drop-up');
        $panel.css({ maxHeight: '', top: '', bottom: '' });
    }

    function positionPanel() {
        var gap = 8;
        var pad = 12;
        var toggleEl = $toggle.get(0);
        if (!toggleEl) return;

        // Measure against viewport so the menu never slips under the modal edge.
        var rect = toggleEl.getBoundingClientRect();
        var spaceBelow = Math.max(0, window.innerHeight - rect.bottom - pad);
        var spaceAbove = Math.max(0, rect.top - pad);
        var preferUp = spaceBelow < 220 && spaceAbove > spaceBelow;
        var available = preferUp ? spaceAbove : spaceBelow;
        var maxH = Math.max(140, Math.min(available - gap, 320));

        $dd.toggleClass('is-drop-up', preferUp);
        if (preferUp) {
            $panel.css({ top: 'auto', bottom: 'calc(100% + ' + gap + 'px)', maxHeight: maxH + 'px' });
        } else {
            $panel.css({ top: 'calc(100% + ' + gap + 'px)', bottom: 'auto', maxHeight: maxH + 'px' });
        }
    }

    function setOpen(open) {
        $dd.toggleClass('is-open', open);
        $toggle.attr('aria-expanded', open ? 'true' : 'false');
        if (open) {
            $panel.prop('hidden', false);
            positionPanel();
            // Re-measure after paint (fonts / scroll).
            window.requestAnimationFrame(positionPanel);
        } else {
            $panel.prop('hidden', true);
            clearPanelPosition();
        }
    }

    function selectReason($opt) {
        var reason = String($opt.data('reason') || '');
        var label = String($opt.data('label') || $opt.find('.pds-cancel-dd__text').text() || '');
        $reason.val(reason);
        $value.text(label);
        $options.removeClass('is-active').attr('aria-selected', 'false');
        $opt.addClass('is-active').attr('aria-selected', 'true');
        setOpen(false);
        toggleOther(reason);
    }

    $toggle.off('click.pdsCancelDd').on('click.pdsCancelDd', function (e) {
        e.preventDefault();
        e.stopPropagation();
        setOpen(!$dd.hasClass('is-open'));
    });

    function onReposition() {
        if ($dd.hasClass('is-open')) {
            positionPanel();
        }
    }

    $(window).off('resize.pdsCancelDd scroll.pdsCancelDd')
        .on('resize.pdsCancelDd scroll.pdsCancelDd', onReposition);
    $modal.off('scroll.pdsCancelDd').on('scroll.pdsCancelDd', onReposition);

    $options.off('click.pdsCancelDd').on('click.pdsCancelDd', function (e) {
        e.preventDefault();
        e.stopPropagation();
        selectReason($(this));
    });

    $toggle.off('keydown.pdsCancelDd').on('keydown.pdsCancelDd', function (e) {
        if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            setOpen(true);
            var $active = $options.filter('.is-active').first();
            ($active.length ? $active : $options.first()).trigger('focus');
        } else if (e.key === 'Escape') {
            setOpen(false);
        }
    });

    $options.off('keydown.pdsCancelDd').on('keydown.pdsCancelDd', function (e) {
        var key = e.key;
        var index = $options.index(this);
        if (key === 'ArrowDown' || key === 'ArrowUp') {
            e.preventDefault();
            var next = key === 'ArrowDown' ? index + 1 : index - 1;
            if (next < 0) next = $options.length - 1;
            if (next >= $options.length) next = 0;
            $options.eq(next).trigger('focus');
            return;
        }
        if (key === 'Enter' || key === ' ') {
            e.preventDefault();
            selectReason($(this));
            $toggle.trigger('focus');
            return;
        }
        if (key === 'Escape') {
            e.preventDefault();
            setOpen(false);
            $toggle.trigger('focus');
        }
    });

    $(document).off('click.pdsCancelDd').on('click.pdsCancelDd', function (e) {
        if (!$(e.target).closest('[data-pds-cancel-dd]').length) {
            setOpen(false);
        }
    });

    $modal.off('hide.bs.modal.pdsCancelDd').on('hide.bs.modal.pdsCancelDd', function () {
        setOpen(false);
    });

    setOpen(false);
    toggleOther($reason.val());
})(jQuery);
</script>
