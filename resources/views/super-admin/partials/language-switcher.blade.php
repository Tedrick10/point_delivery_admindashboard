@php
    $language_array = [];
    $language_option = appSettingData('get')?->language_option;
    if (!empty($language_option)) {
        $language_array = languagesArray($language_option);
    }
    $currentLocale = app()->getLocale();
    $currentFlag = languageFlagUrl($currentLocale);
@endphp
<div class="sa-lang" data-sa-lang>
    <button type="button"
            class="sa-lang__btn"
            id="sa-lang-toggle"
            aria-haspopup="true"
            aria-expanded="false"
            aria-controls="sa-lang-menu"
            title="{{ __('message.language') }}">
        <span class="sa-lang__flag" aria-hidden="true">
            <img src="{{ $currentFlag }}" alt="">
        </span>
        <span class="sa-lang__code">{{ strtoupper($currentLocale) }}</span>
        <i class="fas fa-chevron-down sa-lang__chevron" aria-hidden="true"></i>
    </button>
    <div class="sa-lang__menu" id="sa-lang-menu" hidden role="menu" aria-label="{{ __('message.select_language') }}">
        <div class="sa-lang__menu-head">{{ __('message.select_language') }}</div>
        <ul class="sa-lang__list">
            @forelse($language_array as $lang)
                @php
                    $isActive = $currentLocale === $lang['id'];
                    $label = $lang['native'] ?? $lang['title'];
                @endphp
                <li>
                    <a href="{{ route('change.language', ['locale' => $lang['id']]) }}"
                       class="sa-lang__item {{ $isActive ? 'is-active' : '' }}"
                       role="menuitem"
                       lang="{{ $lang['id'] }}">
                        <span class="sa-lang__flag" aria-hidden="true">
                            <img src="{{ languageFlagUrl($lang['id']) }}" alt="">
                        </span>
                        <span class="sa-lang__label">
                            <strong>{{ $label }}</strong>
                            @if(($lang['native'] ?? null) && ($lang['native'] !== $lang['title']))
                                <small>{{ $lang['title'] }}</small>
                            @endif
                        </span>
                        @if($isActive)
                            <i class="fas fa-check sa-lang__check" aria-hidden="true"></i>
                        @endif
                    </a>
                </li>
            @empty
                <li class="sa-lang__empty">{{ __('message.no_record_found') }}</li>
            @endforelse
        </ul>
    </div>
</div>
<script>
(function () {
    var root = document.querySelector('[data-sa-lang]');
    if (!root) return;
    var btn = root.querySelector('#sa-lang-toggle');
    var menu = root.querySelector('#sa-lang-menu');
    if (!btn || !menu) return;

    function closeMenu() {
        menu.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
        root.classList.remove('is-open');
    }

    function openMenu() {
        menu.hidden = false;
        btn.setAttribute('aria-expanded', 'true');
        root.classList.add('is-open');
    }

    btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (menu.hidden) openMenu();
        else closeMenu();
    });

    document.addEventListener('click', function (e) {
        if (!root.contains(e.target)) closeMenu();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeMenu();
    });
})();
</script>
