(function () {
    'use strict';

    var IMAGE_EXT = /\.(jpe?g|png|gif|webp|ico|svg|bmp|heic|heif)$/i;
    var SKIP_ACCEPT = /(\.csv|spreadsheet|\.xlsx|\.xls|\.zip|\.pdf)/i;
    var IMAGE_NAME = /(image|logo|favicon|photo|picture|avatar|banner|attachment|withdraw|gateway|cover)/i;
    var SAFE_ZONE = [
        '.sa-gs-brand-tile',
        '.pds-vehicle-upload',
        '.pds-vehicle-upload__frame',
        '.pds-profile-upload',
        '.pds-profile-upload__ring',
        '.pds-expense-modal__image-wrap',
        '.sa-expense-modal__image-wrap',
        '.pds-shop-product-cover',
        '.pds-daily-check-photo-preview',
        '.pds-ard-upload',
        '.pds-ard-upload-wrap',
        '.custom-file'
    ].join(', ');
    var UNSAFE_ZONE = [
        'body',
        'html',
        '#app',
        '#adminSpaContent',
        '#adminLiveRoot',
        '#mm-sidebar-toggle',
        '.pds-content-shell',
        '.content-page',
        '.pds-sidebar',
        '.iq-sidebar',
        '.mm-sidebar',
        '.pds-topbar',
        '.sa-sidebar',
        '.sa-nav',
        '.sa-main',
        '.sa-content',
        '.sa-module-page',
        '.wrapper'
    ].join(', ');
    var hoveredPasteInput = null;
    var pasteBound = false;

    function isImageFile(file) {
        if (!file) return false;
        if (file.type && file.type.indexOf('image/') === 0) return true;
        return IMAGE_EXT.test(file.name || '');
    }

    function isImageInput(input) {
        if (!input || input.type !== 'file' || input.disabled) return false;
        var accept = (input.getAttribute('accept') || '').toLowerCase();
        if (accept.indexOf('image') !== -1) return true;
        if (accept && SKIP_ACCEPT.test(accept)) return false;
        return IMAGE_NAME.test(input.name || '') || IMAGE_NAME.test(input.id || '') || IMAGE_NAME.test(input.className || '');
    }

    function pickFiles(dataTransfer, multiple) {
        var out = [];
        if (!dataTransfer || !dataTransfer.files) return out;
        for (var i = 0; i < dataTransfer.files.length; i++) {
            if (isImageFile(dataTransfer.files[i])) out.push(dataTransfer.files[i]);
        }
        if (!multiple && out.length > 1) out = [out[0]];
        return out;
    }

    function pickPasteFiles(clipboardData, multiple) {
        var out = [];
        if (!clipboardData) return out;

        var items = clipboardData.items;
        if (items && items.length) {
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                if (!item || item.kind !== 'file') continue;
                var file = item.getAsFile();
                if (isImageFile(file)) out.push(normalizePasteFile(file));
            }
        }

        if (!out.length && clipboardData.files && clipboardData.files.length) {
            for (var j = 0; j < clipboardData.files.length; j++) {
                if (isImageFile(clipboardData.files[j])) {
                    out.push(normalizePasteFile(clipboardData.files[j]));
                }
            }
        }

        if (!multiple && out.length > 1) out = [out[0]];
        return out;
    }

    function normalizePasteFile(file) {
        if (!file) return file;
        if (file.name && file.name !== 'image.png' && file.name !== 'blob') return file;
        var ext = 'png';
        if (file.type === 'image/jpeg') ext = 'jpg';
        else if (file.type === 'image/webp') ext = 'webp';
        else if (file.type === 'image/gif') ext = 'gif';
        try {
            return new File([file], 'pasted-image-' + Date.now() + '.' + ext, {
                type: file.type || 'image/png',
                lastModified: Date.now()
            });
        } catch (err) {
            return file;
        }
    }

    function hasFiles(event) {
        var types = event.dataTransfer && event.dataTransfer.types;
        if (!types) return false;
        for (var i = 0; i < types.length; i++) {
            if (types[i] === 'Files') return true;
        }
        return false;
    }

    function assignFiles(input, files) {
        if (!files.length) return false;
        try {
            var dt = new DataTransfer();
            files.forEach(function (file) { dt.items.add(file); });
            input.files = dt.files;
        } catch (err) {
            return false;
        }
        input.dispatchEvent(new Event('change', { bubbles: true }));
        if (window.jQuery) {
            window.jQuery(input).trigger('change');
        }
        return true;
    }

    function isUnsafeZone(el) {
        if (!el || el === document.body || el === document.documentElement) return true;
        try {
            return el.matches(UNSAFE_ZONE);
        } catch (err) {
            return true;
        }
    }

    function zoneFor(input) {
        var zone = null;
        try {
            zone = input.closest(SAFE_ZONE);
        } catch (err) {
            zone = null;
        }
        if (zone && !isUnsafeZone(zone)) return zone;
        return input;
    }

    function isVisible(el) {
        if (!el || !document.contains(el)) return false;
        var style = window.getComputedStyle(el);
        if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') return false;
        var rect = el.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0;
    }

    function clearHoveredPaste(input) {
        if (hoveredPasteInput === input) {
            hoveredPasteInput = null;
        }
    }

    function setHoveredPaste(input, zone) {
        if (!input || !isImageInput(input) || input.disabled) return;
        if (!isVisible(zone || zoneFor(input))) return;
        hoveredPasteInput = input;
        if (zone) zone.classList.add('is-paste-ready');
    }

    function unsetHoveredPaste(input, zone) {
        if (zone) zone.classList.remove('is-paste-ready');
        clearHoveredPaste(input);
    }

    function findHoveredPasteTarget() {
        if (!hoveredPasteInput) return null;
        if (!document.contains(hoveredPasteInput) || hoveredPasteInput.disabled) {
            hoveredPasteInput = null;
            return null;
        }
        var zone = zoneFor(hoveredPasteInput);
        if (!isVisible(hoveredPasteInput) && !isVisible(zone)) {
            hoveredPasteInput = null;
            return null;
        }
        return hoveredPasteInput;
    }

    function flashPasteZone(zone) {
        if (!zone) return;
        zone.classList.add('is-paste-flash');
        window.setTimeout(function () {
            zone.classList.remove('is-paste-flash');
        }, 700);
    }

    function bind(input) {
        if (!isImageInput(input) || input.dataset.imageDropBound === '1') return;
        var zone = zoneFor(input);
        if (!zone || isUnsafeZone(zone)) return;
        input.dataset.imageDropBound = '1';
        zone.classList.add('pds-image-dropzone');
        zone.setAttribute(
            'data-drop-label',
            window.PDS_IMAGE_DROP_LABEL || 'Drop or paste image'
        );
        zone.setAttribute(
            'data-paste-label',
            window.PDS_IMAGE_PASTE_LABEL || 'Paste image (Ctrl+V)'
        );
        zone.setAttribute('tabindex', zone.getAttribute('tabindex') || '0');
        zone.setAttribute('title', zone.getAttribute('title') || 'Hover and paste image (Ctrl+V / Cmd+V)');

        function enter(event) {
            if (!hasFiles(event)) return;
            event.preventDefault();
            event.dataTransfer.dropEffect = 'copy';
            zone.classList.add('is-dragover');
            setHoveredPaste(input, zone);
        }

        function leave(event) {
            if (!zone.contains(event.relatedTarget)) {
                zone.classList.remove('is-dragover');
            }
        }

        zone.addEventListener('dragenter', enter);
        zone.addEventListener('dragover', enter);
        zone.addEventListener('dragleave', leave);
        zone.addEventListener('drop', function (event) {
            event.preventDefault();
            zone.classList.remove('is-dragover');
            setHoveredPaste(input, zone);
            assignFiles(input, pickFiles(event.dataTransfer, !!input.multiple));
        });

        // Paste only while the mouse is over this upload box.
        zone.addEventListener('pointerenter', function () {
            setHoveredPaste(input, zone);
        });
        zone.addEventListener('mouseenter', function () {
            setHoveredPaste(input, zone);
        });
        zone.addEventListener('pointerleave', function (event) {
            if (!zone.contains(event.relatedTarget)) {
                unsetHoveredPaste(input, zone);
            }
        });
        zone.addEventListener('mouseleave', function (event) {
            if (!zone.contains(event.relatedTarget)) {
                unsetHoveredPaste(input, zone);
            }
        });
        zone.addEventListener('focusin', function () {
            setHoveredPaste(input, zone);
        });
        zone.addEventListener('focusout', function (event) {
            if (!zone.contains(event.relatedTarget)) {
                unsetHoveredPaste(input, zone);
            }
        });
    }

    function onPaste(event) {
        // Paste image only while the mouse is over an upload box.
        var input = findHoveredPasteTarget();
        if (!input) return;

        var files = pickPasteFiles(event.clipboardData, !!input.multiple);
        if (!files.length) return;

        // Image + hover wins even if focus is still on ItemValue / DeliAmount.
        event.preventDefault();
        event.stopPropagation();
        if (assignFiles(input, files)) {
            flashPasteZone(zoneFor(input));
        }
    }

    function scan(root) {
        try {
            var scope = root && root.querySelectorAll ? root : document;
            scope.querySelectorAll('input[type="file"]').forEach(bind);
            if (root && root.matches && root.matches('input[type="file"]')) bind(root);
        } catch (err) {}
    }

    function boot() {
        scan(document);
        if (!pasteBound) {
            pasteBound = true;
            document.addEventListener('paste', onPaste, true);
        }
        document.addEventListener('admin-spa:navigated', function () {
            scan(document.getElementById('adminSpaContent') || document);
        });
        document.addEventListener('admin-live:replaced', function () {
            scan(document.getElementById('adminLiveRoot') || document.getElementById('adminSpaContent') || document);
        });
        if (window.jQuery) {
            window.jQuery(document).on('shown.bs.modal', '.modal, #remoteModelData', function () {
                scan(this);
            });
        }
    }

    window.pdsScanImageDrop = scan;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
