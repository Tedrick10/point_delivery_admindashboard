<x-frontand-layout :assets="$assets ?? []">
    @include('frontend-website.partials.legal_page', [
        'pageTitle' => __('message.privacy_policy'),
        'activeTab' => $activeTab ?? 'user',
        'userHtml' => $userHtml ?? '',
        'riderHtml' => $riderHtml ?? '',
        'userUrl' => route('privacypolicy', ['app' => 'user']),
        'riderUrl' => route('privacypolicy', ['app' => 'rider']),
    ])
</x-frontand-layout>
