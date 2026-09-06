{{-- Clean public legal document: Privacy / Terms --}}
@php
    $pageTitle = $pageTitle ?? __('message.privacy_policy');
    $activeTab = $activeTab ?? request('app', 'user');
    if (! in_array($activeTab, ['user', 'rider'], true)) {
        $activeTab = 'user';
    }
    $userHtml = trim((string) ($userHtml ?? ''));
    $riderHtml = trim((string) ($riderHtml ?? ''));
    $userUrl = $userUrl ?? route(request()->route()?->getName() ?? 'privacypolicy', ['app' => 'user']);
    $riderUrl = $riderUrl ?? route(request()->route()?->getName() ?? 'privacypolicy', ['app' => 'rider']);
    $activeHtml = $activeTab === 'rider' ? $riderHtml : $userHtml;
    $activeAppName = $activeTab === 'rider' ? 'Point Delivery Partner' : 'Point User';
@endphp

<main class="pds-legal padding-top-80">
    <div class="container pds-legal__container">
        <nav class="pds-legal__crumb" aria-label="breadcrumb">
            <a href="{{ route('frontend-section') }}">{{ __('message.home') }}</a>
            <span aria-hidden="true">/</span>
            <span>{{ $pageTitle }}</span>
        </nav>

        <article class="pds-legal__sheet">
            <header class="pds-legal__top">
                <div class="pds-legal__titles">
                    <h1>{{ $pageTitle }}</h1>
                    <p>{{ __('message.legal_page_subtitle') }}</p>
                </div>

                <div class="pds-legal__switch" role="tablist" aria-label="{{ $pageTitle }}">
                    <a href="{{ $userUrl }}"
                       class="{{ $activeTab === 'user' ? 'is-on' : '' }}"
                       role="tab"
                       aria-selected="{{ $activeTab === 'user' ? 'true' : 'false' }}">
                        {{ __('message.legal_tab_user_app') }}
                    </a>
                    <a href="{{ $riderUrl }}"
                       class="{{ $activeTab === 'rider' ? 'is-on' : '' }}"
                       role="tab"
                       aria-selected="{{ $activeTab === 'rider' ? 'true' : 'false' }}">
                        {{ __('message.legal_tab_rider_app') }}
                    </a>
                </div>
            </header>

            <div class="pds-legal__bar">
                <strong>{{ $activeAppName }}</strong>
                <span>{{ __('message.legal_last_updated') }}</span>
            </div>

            <div class="pds-legal__body">
                @if($activeHtml !== '')
                    {!! $activeHtml !!}
                @else
                    <p>{{ __('message.legal_content_empty') }}</p>
                @endif
            </div>
        </article>
    </div>
</main>
