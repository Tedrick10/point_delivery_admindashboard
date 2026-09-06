<x-frontand-layout :assets="$assets ?? []">
    @include('frontend-website.partials.legal_page', [
        'pageTitle' => __('message.delete_account_page_title'),
        'activeTab' => $activeTab ?? 'user',
        'userHtml' => $userHtml ?? '',
        'riderHtml' => $riderHtml ?? '',
        'userUrl' => route('delete-account', ['app' => 'user']),
        'riderUrl' => route('delete-account', ['app' => 'rider']),
    ])
</x-frontand-layout>
