<x-master-layout :assets="['textarea']">
    @include('setting.partials._legal_apps_form', [
        'saveRoute' => route('privacy-policy-save'),
        'userEditorClass' => 'tinymce-privacy_policy_user',
        'riderEditorClass' => 'tinymce-privacy_policy_rider',
        'icon' => 'fa-user-shield',
        'subtitle' => __('message.legal_edit_subtitle_privacy'),
        'previewUrl' => route('privacypolicy', ['app' => 'user']),
        'user_setting' => $user_setting,
        'rider_setting' => $rider_setting,
    ])
</x-master-layout>
