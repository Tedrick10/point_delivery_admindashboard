<script>
    $(document).ready(function() {
        $('.select2js').select2({ width: '100%' });

        @if(!empty($enableHomeSectionTags))
        $('.select2js-home-section').select2({
            width: '100%',
            tags: true,
            placeholder: 'Select or type custom name...',
        });
        @endif
    });
</script>
