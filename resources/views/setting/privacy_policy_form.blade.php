<x-master-layout :assets="['textarea']">
    @include('setting.partials._legal_page_form', [
        'saveRoute' => route('privacy-policy-save'),
        'editorClass' => 'tinymce-privacy_policy',
        'icon' => 'fa-user-shield',
        'subtitle' => __('message.legal_edit_subtitle_privacy'),
    ])
</x-master-layout>
