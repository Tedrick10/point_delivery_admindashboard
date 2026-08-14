(function (jQuery) {
    "use strict";
    const storageDark = localStorage.getItem('dark')

    if (storageDark === 'true') {
        changeMode('true')
    } else if (storageDark === 'false') {
        changeMode('false')
    } else if (jQuery('body').hasClass('dark')) {
        changeMode('true')
    } else {
        changeMode('false')
    }

    jQuery(document).on("change", '.change-mode input[type="checkbox"]', function () {
        const dark = jQuery(this).attr('data-active');
        if (dark === 'true') {
            jQuery(this).attr('data-active', 'false')
        } else {
            jQuery(this).attr('data-active', 'true')
        }
        changeMode(dark)
    })

    function changeMode(dark) {
        const body = jQuery('body')
        const isDark = dark === 'true' || dark === true

        if (isDark) {
            jQuery('#dark-mode').prop('checked', true).attr('data-active', 'false')
            jQuery('.darkmode-logo').removeClass('d-none')
            jQuery('.light-logo').addClass('d-none')
            body.addClass('dark')
        } else {
            jQuery('#dark-mode').prop('checked', false).attr('data-active', 'true')
            jQuery('.light-logo').removeClass('d-none')
            jQuery('.darkmode-logo').addClass('d-none')
            body.removeClass('dark')
        }

        localStorage.setItem('dark', isDark ? 'true' : 'false')

        document.dispatchEvent(new CustomEvent("ChangeColorMode", { detail: { dark: isDark } }));
    }

})(jQuery)