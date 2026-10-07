<section class="sa-ui-theme-page">
    @if ($errors->any())
        <p class="sa-fuel-default-panel__err">{{ $errors->first() }}</p>
    @endif

    <div class="sa-ui-theme-grid" id="saUiThemeGrid">
        @foreach(($uiTheme['packs'] ?? []) as $pack)
            @php $isActive = ($uiTheme['active'] ?? 'classic') === $pack['id']; @endphp
            <form method="POST" action="{{ route('super-admin.ui-theme.update') }}" class="sa-ui-theme-card-form">
                @csrf
                <input type="hidden" name="ui_theme" value="{{ $pack['id'] }}">
                <button type="submit"
                        class="sa-ui-theme-card {{ $isActive ? 'is-active' : '' }}"
                        data-pack="{{ $pack['id'] }}"
                        @if($isActive) aria-current="true" @endif>
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
                </button>
            </form>
        @endforeach
    </div>
</section>

<style>
.sa-ui-theme-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}
.sa-ui-theme-card-form { margin: 0; }
.sa-ui-theme-card {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    width: 100%;
    padding: 0.85rem;
    border-radius: 18px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    background: #fff;
    cursor: pointer;
    text-align: left;
    font: inherit;
    color: inherit;
    transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
}
.sa-ui-theme-card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(15,23,42,.08); }
.sa-ui-theme-card.is-active { border-color: #FE6F07; box-shadow: 0 0 0 3px rgba(254, 111, 7, 0.16); }
.sa-ui-theme-card__preview {
    position: relative;
    height: 132px;
    border-radius: 14px;
    overflow: hidden;
    pointer-events: none;
}
.sa-ui-theme-card__preview[data-pack="classic"] { background: #F7F4F0; }
.sa-ui-theme-card__preview[data-pack="liquid_glass"] {
    background: linear-gradient(145deg, #FFF4EB 0%, #FBF6F1 55%, #FFF8F2 100%);
}
.sa-ui-theme-card__preview[data-pack="aurora"] {
    background: #EDE9E5;
}

.sa-theme-mock {
    display: grid;
    grid-template-columns: 28px 1fr;
    gap: 6px;
    height: 100%;
    padding: 10px;
    box-sizing: border-box;
}
.sa-theme-mock__side {
    border-radius: 8px;
    background: #fff;
    border: 1px solid rgba(20,17,15,.08);
}
.sa-theme-mock__side--float {
    border-radius: 14px;
    background: rgba(255,255,255,.55);
    border: 1px solid rgba(255,255,255,.7);
    backdrop-filter: blur(6px);
}
.sa-theme-mock__side--dark {
    background: #1C1917;
    border: 0;
    border-radius: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 8px 4px;
}
.sa-theme-mock__rail {
    display: block;
    height: 6px;
    border-radius: 2px;
    background: rgba(255,255,255,.18);
    border-left: 2px solid transparent;
}
.sa-theme-mock__rail--on {
    background: rgba(var(--brand-rgb),.28);
    border-left-color: var(--site-color);
}
.sa-theme-mock__main {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}
.sa-theme-mock__main--inset {
    background: rgba(255,255,255,.35);
    border-radius: 16px;
    padding: 6px;
    border: 1px solid rgba(255,255,255,.55);
}
.sa-theme-mock__main--dense { gap: 4px; }
.sa-theme-mock__top {
    height: 14px;
    border-radius: 8px;
    background: rgba(255,255,255,.9);
    border: 1px solid rgba(20,17,15,.06);
}
.sa-theme-mock__top--pill { border-radius: 999px; background: rgba(255,255,255,.65); }
.sa-theme-mock__top--solid {
    border-radius: 0;
    border: 0;
    border-bottom: 2px solid var(--site-color);
    background: #fff;
}
.sa-theme-mock__card {
    flex: 1;
    border-radius: 12px;
    background: #fff;
    border: 1px solid rgba(20,17,15,.08);
    box-shadow: 0 6px 14px rgba(20,17,15,.04);
}
.sa-theme-mock__card--sm { flex: 0.55; }
.sa-theme-mock__card--glass {
    border-radius: 18px;
    background: rgba(255,255,255,.62);
    border: 1px solid rgba(255,255,255,.7);
    box-shadow: 0 10px 20px rgba(15,40,80,.08);
}
.sa-theme-mock__card--rail {
    border-radius: 8px;
    border-left: 3px solid var(--site-color);
    box-shadow: none;
}
.sa-theme-mock__dock {
    height: 12px;
    border-radius: 10px;
    background: #FFFCFA;
    border: 1px solid rgba(20,17,15,.08);
}
.sa-theme-mock__dock--pill {
    border-radius: 999px;
    background: rgba(255,255,255,.7);
    height: 14px;
}
.sa-theme-mock__dock--flat {
    border-radius: 0;
    height: 12px;
    background: #1C1917;
    border: 0;
    border-top: 2px solid var(--site-color);
}

.sa-ui-theme-card__meta { display: flex; flex-direction: column; gap: 0.2rem; pointer-events: none; }
.sa-ui-theme-card__meta strong { font-size: 0.98rem; color: #0f172a; }
.sa-ui-theme-card__meta span { font-size: 0.8rem; color: #64748b; }
.sa-ui-theme-card__badge {
    position: absolute; top: 12px; left: 12px;
    font-style: normal; font-size: 0.68rem; font-weight: 700;
    background: var(--site-color); color: #fff; border-radius: 999px; padding: 0.2rem 0.55rem;
    pointer-events: none;
}
@media (max-width: 900px) {
    .sa-ui-theme-grid { grid-template-columns: 1fr; }
}
</style>

<script>
(function () {
    document.querySelectorAll('.sa-ui-theme-card-form').forEach(function (form) {
        form.addEventListener('submit', function () {
            var btn = form.querySelector('.sa-ui-theme-card');
            if (btn) {
                btn.disabled = true;
                btn.style.opacity = '0.75';
            }
        });
    });
})();
</script>
