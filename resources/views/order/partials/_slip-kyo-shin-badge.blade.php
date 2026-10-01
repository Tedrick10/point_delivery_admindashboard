@if(!empty($row['is_kyo_shin']) || !empty($slipIsKyoShin))
    <span class="pds-slip-kyo-shin-badge" style="display:inline-block;margin-left:6px;padding:1px 7px;border-radius:999px;background:rgba(var(--brand-rgb), 0.16);color:var(--site-color);font-size:10px;font-weight:800;letter-spacing:0;white-space:nowrap;vertical-align:middle;">{{ __('message.kyo_shin_title') }}</span>
@endif
