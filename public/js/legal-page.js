(function ($) {
    function bootLegalPage() {
        var $page = $('.pds-legal-page');
        if (!$page.length || typeof tinymce === 'undefined') {
            return;
        }

        var selector = $page.data('legal-editor');
        if (!selector || !$(selector).length || $(selector).next('.tox-tinymce').length) {
            return;
        }

        tinymce.init({
            selector: selector,
            height: 520,
            menubar: false,
            branding: false,
            plugins: ['advlist', 'autolink', 'lists', 'link', 'charmap', 'preview', 'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen', 'insertdatetime', 'table', 'help', 'wordcount'],
            toolbar: 'undo redo | blocks | bold italic | bullist numlist | alignleft aligncenter alignright | removeformat',
            content_style: "body{font-family:'Noto Sans Myanmar',Inter,sans-serif;font-size:15px;line-height:1.75;color:#1f2937;padding:12px 16px;}h2{font-size:1.2rem;margin:0 0 0.75rem;color:#111827;}h3{font-size:1rem;margin:1.25rem 0 0.45rem;padding-left:10px;border-left:3px solid #FE6F07;color:#111827;}p{margin:0 0 0.75rem;}ul{padding-left:1.15rem;margin:0 0 0.85rem;}li{margin:0 0 0.4rem;}em{color:#6b7280;}",
            setup: function (ed) {
                var sync = function () {
                    $('.pds-legal-preview-body').html(ed.getContent());
                };
                ed.on('init keyup change SetContent Undo Redo', sync);
            }
        });

        $('#legal_page_form').on('submit', function () {
            if (tinymce.activeEditor) {
                tinymce.activeEditor.save();
            }
        });
    }

    $(bootLegalPage);
})(jQuery);
