<x-frontand-layout :assets="$assets ?? []">
    @include('frontend-website.partials.legal_page', [
        'pageTitle' => __('message.terms_and_conditions'),
        'activeTab' => $activeTab ?? 'user',
        'userHtml' => $userHtml ?? '',
        'riderHtml' => $riderHtml ?? '',
        'userUrl' => route('termofservice', ['app' => 'user']),
        'riderUrl' => route('termofservice', ['app' => 'rider']),
    ])
</x-frontand-layout>
