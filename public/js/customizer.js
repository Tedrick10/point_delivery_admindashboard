(function (jQuery) {
    "use strict";

    // Theme toggle removed from UI — always use light mode.
    function applyLightMode() {
        const body = jQuery('body');
        jQuery('.light-logo').removeClass('d-none');
        jQuery('.darkmode-logo').addClass('d-none');
        body.removeClass('dark');
        try {
            localStorage.setItem('dark', 'false');
        } catch (e) {}
        document.dispatchEvent(new CustomEvent("ChangeColorMode", { detail: { dark: false } }));
    }

    applyLightMode();
})(jQuery)
