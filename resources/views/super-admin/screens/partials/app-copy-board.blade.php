@php
    $activeApp = $appCopy['app'] ?? 'admin';
    $rows = $appCopy['rows'] ?? null;
    $branding = $appCopy['branding'][$activeApp] ?? [];
    $enFlag = languageFlagUrl('en');
    $myFlag = languageFlagUrl('my');
    $totalRows = $rows && method_exists($rows, 'total') ? (int) $rows->total() : 0;
    $hasQuery = trim((string) ($appCopy['q'] ?? '')) !== '' || !empty($appCopy['screenId']);
@endphp
<div class="sa-copy" data-sa-copy>
    @if ($errors->any())
        <div class="sa-alert alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <nav class="sa-copy-switch" role="tablist" aria-label="{{ __('message.sa_screen_app_copy') }}">
        @foreach(($appCopy['apps'] ?? []) as $appKey => $meta)
            <a href="{{ route('super-admin.screens.show', ['screen' => 'app-copy', 'app' => $appKey]) }}"
               class="sa-copy-switch__item {{ $activeApp === $appKey ? 'is-active' : '' }}"
               role="tab"
               aria-selected="{{ $activeApp === $appKey ? 'true' : 'false' }}">
                <span class="sa-copy-switch__icon"><i class="fas {{ $meta['icon'] ?? 'fa-font' }}" aria-hidden="true"></i></span>
                <span class="sa-copy-switch__copy">
                    <strong>{{ $meta['label'] }}</strong>
                    <small>{{ number_format((int) ($meta['count'] ?? 0)) }} {{ __('message.sa_copy_texts') }}</small>
                </span>
            </a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('super-admin.screens.show', 'app-copy') }}" class="sa-copy-search" role="search">
        <input type="hidden" name="app" value="{{ $activeApp }}">
        <div class="sa-copy-search__field">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search"
                   name="q"
                   value="{{ $appCopy['q'] ?? '' }}"
                   placeholder="{{ __('message.sa_copy_search') }}"
                   autocomplete="off"
                   data-sa-copy-q>
            @if($activeApp !== 'admin')
                <span class="sa-copy-search__divider" aria-hidden="true"></span>
                <select name="screen_id" class="sa-copy-search__select" aria-label="{{ __('message.sa_copy_all_screens') }}" onchange="this.form.submit()">
                    <option value="">{{ __('message.sa_copy_all_screens') }}</option>
                    @foreach(($appCopy['screens'] ?? []) as $screen)
                        <option value="{{ $screen->screenId }}" @selected((int) ($appCopy['screenId'] ?? 0) === (int) $screen->screenId)>
                            {{ $screen->screenName }}
                        </option>
                    @endforeach
                </select>
            @endif
            @if($hasQuery)
                <a href="{{ route('super-admin.screens.show', ['screen' => 'app-copy', 'app' => $activeApp]) }}" class="sa-copy-search__clear">{{ __('message.reset') }}</a>
            @endif
            <button type="submit" class="sa-copy-search__go" aria-label="{{ __('message.search') }}" title="{{ __('message.search') }}">
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </button>
        </div>
    </form>

    <details class="sa-copy-brand">
        <summary>
            <span>{{ __('message.sa_copy_brand_quick') }}</span>
            <small>{{ $appCopy['apps'][$activeApp]['label'] ?? $activeApp }}</small>
        </summary>
        <form method="POST" action="{{ $appCopy['saveRoute'] }}" class="sa-copy-brand-form">
            @csrf
            <input type="hidden" name="app" value="{{ $activeApp }}">
            <div class="sa-copy-langs">
                @foreach(['en' => __('message.sa_copy_lang_en'), 'my' => __('message.sa_copy_lang_my')] as $locale => $localeLabel)
                    <div class="sa-copy-lang">
                        <h4>
                            <span class="sa-copy-flag" aria-hidden="true"><img src="{{ $locale === 'my' ? $myFlag : $enFlag }}" alt=""></span>
                            {{ $localeLabel }}
                        </h4>
                        <label>
                            {{ __('message.sa_copy_title') }}
                            <input type="text" name="copy[{{ $activeApp }}][{{ $locale }}][title]"
                                   value="{{ old("copy.$activeApp.$locale.title", $branding[$locale]['title'] ?? '') }}" maxlength="120">
                        </label>
                        <label>
                            {{ __('message.sa_copy_text') }}
                            <input type="text" name="copy[{{ $activeApp }}][{{ $locale }}][text]"
                                   value="{{ old("copy.$activeApp.$locale.text", $branding[$locale]['text'] ?? '') }}" maxlength="180">
                        </label>
                        <label>
                            {{ __('message.sa_copy_description') }}
                            <textarea name="copy[{{ $activeApp }}][{{ $locale }}][description]" rows="2" maxlength="500">{{ old("copy.$activeApp.$locale.description", $branding[$locale]['description'] ?? '') }}</textarea>
                        </label>
                    </div>
                @endforeach
            </div>
            <div class="sa-copy-actions">
                <button type="submit" class="sa-btn sa-btn-primary sa-btn-sm">{{ __('message.save') }}</button>
            </div>
        </form>
    </details>

    <form method="POST" action="{{ $appCopy['saveRoute'] }}" class="sa-copy-form">
        @csrf
        <input type="hidden" name="app" value="{{ $activeApp }}">
        <input type="hidden" name="q" value="{{ $appCopy['q'] ?? '' }}">
        <input type="hidden" name="screen_id" value="{{ $appCopy['screenId'] ?? '' }}">
        <input type="hidden" name="page" value="{{ $rows?->currentPage() ?? 1 }}">

        <div class="sa-copy-sheet">
            <div class="sa-copy-sheet__meta">
                <strong data-sa-copy-count>{{ number_format($totalRows) }}</strong>
                <span>{{ __('message.sa_copy_texts') }}</span>
            </div>
            <div class="sa-copy-table-wrap">
                <table class="sa-copy-table">
                    <thead>
                        <tr>
                            <th>{{ __('message.sa_copy_key') }}</th>
                            <th>
                                <span class="sa-copy-th">
                                    <span class="sa-copy-flag" aria-hidden="true"><img src="{{ $enFlag }}" alt=""></span>
                                    {{ __('message.sa_copy_lang_en') }}
                                </span>
                            </th>
                            <th>
                                <span class="sa-copy-th">
                                    <span class="sa-copy-flag" aria-hidden="true"><img src="{{ $myFlag }}" alt=""></span>
                                    {{ __('message.sa_copy_lang_my') }}
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($rows ?? []) as $row)
                            <tr data-sa-copy-row="{{ mb_strtolower(($row['label'] ?? '').' '.($row['group'] ?? '').' '.($row['en'] ?? '').' '.($row['my'] ?? '')) }}">
                                <td>
                                    <strong>{{ $row['label'] }}</strong>
                                    <small>{{ $row['group'] }}</small>
                                </td>
                                <td>
                                    <label class="sa-copy-cell">
                                        <span class="sa-copy-cell__lang">
                                            <span class="sa-copy-flag sa-copy-flag--sm" aria-hidden="true"><img src="{{ $enFlag }}" alt=""></span>
                                            EN
                                        </span>
                                        <textarea name="texts[{{ $row['id'] }}][en]" rows="2">{{ $row['en'] }}</textarea>
                                    </label>
                                </td>
                                <td>
                                    <label class="sa-copy-cell">
                                        <span class="sa-copy-cell__lang">
                                            <span class="sa-copy-flag sa-copy-flag--sm" aria-hidden="true"><img src="{{ $myFlag }}" alt=""></span>
                                            MY
                                        </span>
                                        <textarea name="texts[{{ $row['id'] }}][my]" rows="2">{{ $row['my'] }}</textarea>
                                    </label>
                                </td>
                            </tr>
                        @empty
                            <tr class="sa-copy-empty-row">
                                <td colspan="3" class="sa-empty">{{ __('message.no_record_found') }}</td>
                            </tr>
                        @endforelse
                        <tr class="sa-copy-empty-row" data-sa-copy-empty hidden>
                            <td colspan="3" class="sa-empty">{{ __('message.no_record_found') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sa-copy-footer">
            <button type="submit" class="sa-btn sa-btn-primary">{{ __('message.save') }}</button>
            @if($rows && method_exists($rows, 'links'))
                <div class="sa-copy-pager">
                    <span class="sa-copy-pager__meta">
                        {{ __('pagination.showing') }}
                        {{ $rows->firstItem() }}–{{ $rows->lastItem() }}
                        / {{ number_format($rows->total()) }}
                    </span>
                    <div class="sa-copy-pager__nav">
                        @if($rows->onFirstPage())
                            <span class="sa-copy-pager__btn is-disabled" aria-disabled="true"><i class="fas fa-chevron-left"></i></span>
                        @else
                            <a class="sa-copy-pager__btn" href="{{ $rows->previousPageUrl() }}" rel="prev" aria-label="Previous"><i class="fas fa-chevron-left"></i></a>
                        @endif
                        <span class="sa-copy-pager__page">{{ $rows->currentPage() }} / {{ $rows->lastPage() }}</span>
                        @if($rows->hasMorePages())
                            <a class="sa-copy-pager__btn" href="{{ $rows->nextPageUrl() }}" rel="next" aria-label="Next"><i class="fas fa-chevron-right"></i></a>
                        @else
                            <span class="sa-copy-pager__btn is-disabled" aria-disabled="true"><i class="fas fa-chevron-right"></i></span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </form>
</div>
<script>
(function () {
    var root = document.querySelector('[data-sa-copy]');
    if (!root) return;
    var input = root.querySelector('[data-sa-copy-q]');
    var rows = root.querySelectorAll('[data-sa-copy-row]');
    var empty = root.querySelector('[data-sa-copy-empty]');
    if (!input || !rows.length) return;

    function filterRows() {
        var q = (input.value || '').trim().toLowerCase();
        var shown = 0;
        rows.forEach(function (row) {
            var hay = row.getAttribute('data-sa-copy-row') || '';
            var match = !q || hay.indexOf(q) !== -1;
            row.hidden = !match;
            if (match) shown += 1;
        });
        if (empty) empty.hidden = shown !== 0;
    }

    input.addEventListener('input', filterRows);
})();
</script>
