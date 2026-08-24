<x-master-layout :assets="['textarea']">
    @include('setting.partials._legal_page_form', [
        'saveRoute' => route('term-condition-save'),
        'editorClass' => 'tinymce-terms_condition',
        'icon' => 'fa-file-contract',
        'subtitle' => __('message.legal_edit_subtitle_terms'),
    ])
</x-master-layout>
