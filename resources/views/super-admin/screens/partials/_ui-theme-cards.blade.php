@php
    $themePacks = $themePacks ?? array_values(uiThemePacks());
    $activeTheme = $activeTheme ?? uiThemePackId();
@endphp
<div class="sa-ui-theme-grid">
    @foreach($themePacks as $pack)
        @php $isActive = $activeTheme === $pack['id']; @endphp
        <label class="sa-ui-theme-card {{ $isActive ? 'is-active' : '' }}">
            <input type="radio" name="ui_theme" value="{{ $pack['id'] }}" @checked($isActive)>
            <div class="sa-ui-theme-card__preview" data-pack="{{ $pack['id'] }}">
                @if(($pack['id'] ?? '') === 'classic')
                    <div class="sa-theme-mock sa-theme-mock--classic">
                        <aside class="sa-theme-mock__side"></aside>
                        <div class="sa-theme-mock__main">
                            <div class="sa-theme-mock__top"></div>
                            <div class="sa-theme-mock__card"></div>
                            <div class="sa-theme-mock__card sa-theme-mock__card--sm"></div>
                            <div class="sa-theme-mock__dock"></div>
                        </div>
                    </div>
                @elseif(($pack['id'] ?? '') === 'liquid_glass')
                    <div class="sa-theme-mock sa-theme-mock--glass">
                        <aside class="sa-theme-mock__side sa-theme-mock__side--float"></aside>
                        <div class="sa-theme-mock__main sa-theme-mock__main--inset">
                            <div class="sa-theme-mock__top sa-theme-mock__top--pill"></div>
                            <div class="sa-theme-mock__card sa-theme-mock__card--glass"></div>
                            <div class="sa-theme-mock__card sa-theme-mock__card--glass sa-theme-mock__card--sm"></div>
                            <div class="sa-theme-mock__dock sa-theme-mock__dock--pill"></div>
                        </div>
                    </div>
                @else
                    <div class="sa-theme-mock sa-theme-mock--aurora">
                        <aside class="sa-theme-mock__side sa-theme-mock__side--dark">
                            <span class="sa-theme-mock__rail"></span>
                            <span class="sa-theme-mock__rail sa-theme-mock__rail--on"></span>
                            <span class="sa-theme-mock__rail"></span>
                        </aside>
                        <div class="sa-theme-mock__main sa-theme-mock__main--dense">
                            <div class="sa-theme-mock__top sa-theme-mock__top--solid"></div>
                            <div class="sa-theme-mock__card sa-theme-mock__card--rail"></div>
                            <div class="sa-theme-mock__card sa-theme-mock__card--rail sa-theme-mock__card--sm"></div>
                            <div class="sa-theme-mock__dock sa-theme-mock__dock--flat"></div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="sa-ui-theme-card__meta">
                <strong>{{ $pack['name'] }}</strong>
                <span>{{ $pack['subtitle'] }}</span>
            </div>
            @if($isActive)
                <em class="sa-ui-theme-card__badge">{{ __('message.active') }}</em>
            @endif
        </label>
    @endforeach
</div>
