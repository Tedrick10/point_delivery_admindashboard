{!! html()->form('GET', route('languagewithkeyword.index'))->class('pds-list-filters')->open() !!}
    <div class="pds-list-filters__field">
        {!! html()->label(__('message.select_name', ['select' => __('message.language')]), 'language')->class('pds-list-filters__label') !!}
        {!! html()->select('language', isset($language) ? [$language->id => $language->language_name] : [], old('language'))
            ->id('language')
            ->class('select2Clear')
            ->attribute('data-placeholder', __('message.select_name', ['select' => __('message.language')]))
            ->attribute('data-ajax--url', route('ajax-list', ['type' => 'languagetable'])) !!}
    </div>

    <div class="pds-list-filters__field">
        {!! html()->label(__('message.select_name', ['select' => __('message.keyword')]), 'keyword')->class('pds-list-filters__label') !!}
        {!! html()->select('keyword', isset($keyword) ? [$keyword->id => $keyword->keyword_name] : [], old('keyword'))
            ->id('keyword')
            ->class('select2Clear')
            ->attribute('data-placeholder', __('message.select_name', ['select' => __('message.keyword')]))
            ->attribute('data-ajax--url', route('ajax-list', ['type' => 'defaultkeyword'])) !!}
    </div>

    <div class="pds-list-filters__field">
        {!! html()->label(__('message.select_name', ['select' => __('message.screen')]), 'screen')->class('pds-list-filters__label') !!}
        {!! html()->select('screen', isset($screen) ? [$screen->screenId => $screen->screenName] : [], old('screen'))
            ->id('screen')
            ->class('select2Clear')
            ->attribute('data-placeholder', __('message.select_name', ['select' => __('message.screen')]))
            ->attribute('data-ajax--url', route('ajax-list', ['type' => 'screen'])) !!}
    </div>

    <div class="pds-list-filters__actions">
        <button type="submit" class="btn btn-sm btn-primary">{{ __('message.apply_filter') }}</button>
        <a href="{{ route('languagewithkeyword.index') }}" class="btn btn-sm btn-outline-primary">
            <i class="ri-repeat-line"></i> {{ __('message.reset_filter') }}
        </a>
    </div>
{!! html()->form()->close() !!}
