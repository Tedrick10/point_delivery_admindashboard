<x-master-layout :assets="['textarea']">
    @include('setting.partials._legal_apps_form', [
        'saveRoute' => route('term-condition-save'),
        'userEditorClass' => 'tinymce-terms_condition_user',
        'riderEditorClass' => 'tinymce-terms_condition_rider',
        'icon' => 'fa-file-contract',
        'subtitle' => __('message.legal_edit_subtitle_terms'),
        'previewUrl' => route('termofservice', ['app' => 'user']),
        'user_setting' => $user_setting,
        'rider_setting' => $rider_setting,
    ])
</x-master-layout>
