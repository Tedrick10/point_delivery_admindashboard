/**
 * Shared photo zoom / rotate for Admin photo viewers.
 * Supports: data-carousel-zoom, data-inline-photo-zoom, data-dispatch-photo-zoom
 */
(function (window, $) {
    'use strict';

    // Avoid double-binding when the script tag is included more than once.
    if (window.PdsPhotoZoom && window.PdsPhotoZoom.__booted) {
        if (typeof window.PdsPhotoZoom.boot === 'function') {
            window.PdsPhotoZoom.boot();
        }
        return;
    }

    var VIEWPORTS = new WeakMap();
    var DELEGATION_BOUND = false;

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, value));
    }

    function normalizeRotation(value) {
        return ((parseInt(value, 10) || 0) % 360 + 360) % 360;
    }

    function buildTransform(state) {
        return 'translate(calc(-50% + ' + state.translateX + 'px), calc(-50% + ' + state.translateY + 'px))'
            + ' rotate(' + state.rotation + 'deg) scale(' + state.scale + ')';
    }

    function defaultState() {
        return {
            scale: 1,
            rotation: 0,
            translateX: 0,
            translateY: 0,
            minScale: 1,
            maxScale: 6,
            isDragging: false,
            dragStartX: 0,
            dragStartY: 0,
            dragOriginX: 0,
            dragOriginY: 0,
            pinchStartDistance: 0,
            pinchStartScale: 1,
            activePointers: new Map(),
            slideStates: {},
            currentSlideKey: '0',
            getImg: null,
            zoomLevelEl: null,
            rotationInput: null,
            onRotationChange: null,
            bound: false,
            mounted: false
        };
    }

    function getController(viewport) {
        if (!viewport) {
            return null;
        }
        var state = VIEWPORTS.get(viewport);
        if (!state) {
            state = defaultState();
            VIEWPORTS.set(viewport, state);
        }
        return state;
    }

    function updateZoomLabel(state) {
        if (state.zoomLevelEl) {
            state.zoomLevelEl.textContent = Math.round(state.scale * 100) + '%';
        }
    }

    function writeRotationToDom(state) {
        var rotation = normalizeRotation(state.rotation);
        window.__pdsInlinePhotoRotation = rotation;
        if (state.rotationInput) {
            state.rotationInput.value = String(rotation);
        }
        var img = typeof state.getImg === 'function' ? state.getImg() : null;
        if (img) {
            img.setAttribute('data-pds-rotation', String(rotation));
        }
        if (typeof state.onRotationChange === 'function') {
            state.onRotationChange(rotation);
        }
    }

    function applyTransform(viewport, state) {
        var img = typeof state.getImg === 'function' ? state.getImg() : null;
        if (!img) {
            return;
        }
        // Use important so theme CSS cannot cancel rotation/zoom transforms.
        img.style.setProperty('transform', buildTransform(state), 'important');
        img.style.setProperty('transform-origin', 'center center', 'important');
        viewport.classList.toggle('is-zoomed', state.scale > 1.02);
        updateZoomLabel(state);
        writeRotationToDom(state);
    }

    function rememberSlideState(state) {
        state.slideStates[state.currentSlideKey] = {
            scale: state.scale,
            rotation: state.rotation,
            translateX: state.translateX,
            translateY: state.translateY
        };
    }

    function restoreSlideState(state, slideKey) {
        state.currentSlideKey = String(slideKey || '0');
        var saved = state.slideStates[state.currentSlideKey];
        if (saved) {
            state.scale = saved.scale;
            state.rotation = saved.rotation;
            state.translateX = saved.translateX;
            state.translateY = saved.translateY;
        } else {
            state.scale = 1;
            state.rotation = 0;
            state.translateX = 0;
            state.translateY = 0;
        }
    }

    function resetTransform(viewport, state, options) {
        options = options || {};
        state.scale = 1;
        state.rotation = options.keepRotation ? state.rotation : 0;
        state.translateX = 0;
        state.translateY = 0;
        if (!options.keepRotation) {
            rememberSlideState(state);
        }
        applyTransform(viewport, state);
        viewport.classList.remove('is-zoomed');
    }

    function rotateBy(viewport, state, delta) {
        state.rotation = normalizeRotation(state.rotation + delta);
        rememberSlideState(state);
        applyTransform(viewport, state);
    }

    function zoomAt(viewport, state, clientX, clientY, nextScale) {
        var rect = viewport.getBoundingClientRect();
        var centerX = rect.left + rect.width / 2;
        var centerY = rect.top + rect.height / 2;
        var offsetX = clientX - centerX;
        var offsetY = clientY - centerY;
        var ratio = nextScale / (state.scale || 1);

        state.translateX = (state.translateX - offsetX) * ratio + offsetX;
        state.translateY = (state.translateY - offsetY) * ratio + offsetY;
        state.scale = clamp(nextScale, state.minScale, state.maxScale);
        rememberSlideState(state);
        applyTransform(viewport, state);
    }

    function bindViewportInteractions(viewport, state) {
        if (state.bound) {
            return;
        }
        state.bound = true;

        viewport.addEventListener('wheel', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var delta = event.deltaY < 0 ? 0.15 : -0.15;
            zoomAt(viewport, state, event.clientX, event.clientY, state.scale + delta);
        }, { passive: false });

        viewport.addEventListener('dblclick', function (event) {
            event.preventDefault();
            if (state.scale > 1.02) {
                resetTransform(viewport, state, { keepRotation: true });
            } else {
                zoomAt(viewport, state, event.clientX, event.clientY, 2.4);
            }
        });

        viewport.addEventListener('pointerdown', function (event) {
            if (event.pointerType === 'mouse' && event.button !== 0) {
                return;
            }
            state.activePointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
            if (state.activePointers.size === 1) {
                state.isDragging = state.scale > 1.02;
                state.dragStartX = event.clientX;
                state.dragStartY = event.clientY;
                state.dragOriginX = state.translateX;
                state.dragOriginY = state.translateY;
                try {
                    viewport.setPointerCapture(event.pointerId);
                } catch (e) { /* ignore */ }
            } else if (state.activePointers.size === 2) {
                var points = Array.from(state.activePointers.values());
                state.pinchStartDistance = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);
                state.pinchStartScale = state.scale;
                state.isDragging = false;
            }
        });

        viewport.addEventListener('pointermove', function (event) {
            if (!state.activePointers.has(event.pointerId)) {
                return;
            }
            state.activePointers.set(event.pointerId, { x: event.clientX, y: event.clientY });

            if (state.activePointers.size === 2) {
                var points = Array.from(state.activePointers.values());
                var distance = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);
                if (state.pinchStartDistance > 0) {
                    var midX = (points[0].x + points[1].x) / 2;
                    var midY = (points[0].y + points[1].y) / 2;
                    zoomAt(viewport, state, midX, midY, state.pinchStartScale * (distance / state.pinchStartDistance));
                }
                return;
            }

            if (state.isDragging && state.scale > 1.02) {
                state.translateX = state.dragOriginX + (event.clientX - state.dragStartX);
                state.translateY = state.dragOriginY + (event.clientY - state.dragStartY);
                applyTransform(viewport, state);
            }
        });

        function endPointer(event) {
            state.activePointers.delete(event.pointerId);
            if (state.activePointers.size < 2) {
                state.pinchStartDistance = 0;
            }
            if (state.activePointers.size === 0) {
                state.isDragging = false;
            }
        }

        viewport.addEventListener('pointerup', endPointer);
        viewport.addEventListener('pointercancel', endPointer);
        viewport.addEventListener('pointerleave', endPointer);
    }

    function resolveViewportFromButton(button) {
        if (!button) {
            return null;
        }
        if (button.hasAttribute('data-inline-photo-zoom')) {
            return document.getElementById('pdsItemInlinePhotoViewport');
        }
        if (button.hasAttribute('data-dispatch-photo-zoom')) {
            return document.getElementById('dispatchPhotoViewport');
        }
        if (button.hasAttribute('data-carousel-zoom')) {
            return document.getElementById('photoOrderGallery');
        }
        return null;
    }

    function resolveAction(button) {
        return button.getAttribute('data-inline-photo-zoom')
            || button.getAttribute('data-dispatch-photo-zoom')
            || button.getAttribute('data-carousel-zoom')
            || '';
    }

    function handleZoomAction(viewport, action) {
        var state = getController(viewport);
        if (!state || !action) {
            return;
        }

        if (action === 'reset') {
            resetTransform(viewport, state);
            return;
        }
        if (action === 'rotate-left') {
            rotateBy(viewport, state, -90);
            return;
        }
        if (action === 'rotate-right') {
            rotateBy(viewport, state, 90);
            return;
        }

        var rect = viewport.getBoundingClientRect();
        var centerX = rect.left + rect.width / 2;
        var centerY = rect.top + rect.height / 2;
        if (action === 'in') {
            zoomAt(viewport, state, centerX, centerY, state.scale * 1.25);
        } else if (action === 'out') {
            zoomAt(viewport, state, centerX, centerY, state.scale / 1.25);
        }
    }

    function ensureMounted(viewport) {
        if (!viewport) {
            return null;
        }
        var state = getController(viewport);
        if (state && state.getImg) {
            return state;
        }
        if (viewport.id === 'pdsItemInlinePhotoViewport') {
            return mountInlinePhoto(viewport, { reset: false });
        }
        if (viewport.id === 'dispatchPhotoViewport') {
            return mountDispatchPhoto(viewport);
        }
        if (viewport.id === 'photoOrderGallery') {
            return mountCarousel(viewport);
        }
        return state;
    }

    function bindDelegation() {
        if (DELEGATION_BOUND) {
            return;
        }
        DELEGATION_BOUND = true;

        document.addEventListener('click', function (event) {
            var button = event.target.closest(
                '[data-inline-photo-zoom], [data-dispatch-photo-zoom], [data-carousel-zoom]'
            );
            if (!button) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            var viewport = resolveViewportFromButton(button);
            if (!viewport) {
                return;
            }
            ensureMounted(viewport);
            handleZoomAction(viewport, resolveAction(button));
        }, true);
    }

    function mountInlinePhoto(viewport, options) {
        options = options || {};
        viewport = viewport || document.getElementById('pdsItemInlinePhotoViewport');
        var img = document.getElementById('pdsItemInlinePhotoImg');
        if (!viewport || !img) {
            return null;
        }

        var state = getController(viewport);
        var shouldReset = options.reset === true || !state.mounted;
        state.maxScale = 5;
        state.getImg = function () {
            return document.getElementById('pdsItemInlinePhotoImg');
        };
        state.zoomLevelEl = viewport.parentElement
            ? viewport.parentElement.querySelector('[data-inline-photo-zoom-level]')
            : null;
        state.rotationInput = document.getElementById('photo_rotation');

        if (shouldReset) {
            state.scale = 1;
            state.rotation = 0;
            state.translateX = 0;
            state.translateY = 0;
            state.currentSlideKey = 'inline';
            state.slideStates = {};
        }

        state.mounted = true;
        bindViewportInteractions(viewport, state);
        applyTransform(viewport, state);
        writeRotationToDom(state);

        // Direct button binding (in addition to document delegation) — more reliable
        // when the form is injected into Bootstrap modals via AJAX.
        if (viewport.parentElement && !viewport.parentElement.getAttribute('data-pds-inline-zoom-bound')) {
            viewport.parentElement.setAttribute('data-pds-inline-zoom-bound', '1');
            viewport.parentElement.addEventListener('click', function (event) {
                var button = event.target.closest('[data-inline-photo-zoom]');
                if (!button || !viewport.parentElement.contains(button)) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                handleZoomAction(viewport, button.getAttribute('data-inline-photo-zoom') || '');
            });
        }

        window.__pdsResetInlinePhotoRotation = function () {
            state.rotation = 0;
            state.scale = 1;
            state.translateX = 0;
            state.translateY = 0;
            rememberSlideState(state);
            applyTransform(viewport, state);
        };

        return state;
    }

    function mountDispatchPhoto(viewport) {
        viewport = viewport || document.getElementById('dispatchPhotoViewport');
        var img = document.getElementById('dispatchPhotoImg');
        var modal = document.getElementById('dispatchPhotoViewModal');
        if (!viewport || !img) {
            return null;
        }

        var state = getController(viewport);
        state.getImg = function () {
            return document.getElementById('dispatchPhotoImg');
        };
        state.zoomLevelEl = modal
            ? modal.querySelector('[data-dispatch-photo-zoom-level]')
            : null;
        state.mounted = true;
        bindViewportInteractions(viewport, state);
        applyTransform(viewport, state);
        return state;
    }

    function mountCarousel(viewport) {
        viewport = viewport || document.getElementById('photoOrderGallery');
        var carousel = document.getElementById('photoOrderCarousel');
        if (!viewport || !carousel) {
            return null;
        }

        var state = getController(viewport);
        var track = carousel.querySelector('.pds-photo-carousel-track');
        var prevBtn = carousel.querySelector('.pds-photo-carousel-nav--prev');
        var nextBtn = carousel.querySelector('.pds-photo-carousel-nav--next');
        var counter = carousel.parentElement
            ? carousel.parentElement.querySelector('.pds-photo-carousel-counter')
            : null;
        var caption = carousel.parentElement
            ? carousel.parentElement.querySelector('.pds-photo-carousel-caption')
            : null;

        state.zoomLevelEl = carousel.parentElement
            ? carousel.parentElement.querySelector('[data-carousel-zoom-level]')
            : null;
        state.currentIndex = state.currentIndex || 0;
        state.mounted = true;

        function slides() {
            return carousel.querySelectorAll('.pds-photo-carousel-slide');
        }

        state.getImg = function () {
            var list = slides();
            if (!list.length) {
                return null;
            }
            var slide = list[state.currentIndex];
            return slide ? slide.querySelector('.pds-photo-carousel-img') : null;
        };

        function updateCarousel(index) {
            var list = slides();
            if (!list.length) {
                return;
            }
            rememberSlideState(state);
            state.currentIndex = (index + list.length) % list.length;
            restoreSlideState(state, String(state.currentIndex));
            if (track) {
                track.style.transform = 'translateX(-' + (state.currentIndex * 100) + '%)';
            }
            if (counter) {
                counter.textContent = (state.currentIndex + 1) + ' / ' + list.length;
            }
            if (caption) {
                caption.textContent = list[state.currentIndex].getAttribute('data-caption') || '';
            }
            if (prevBtn) {
                prevBtn.disabled = list.length <= 1;
            }
            if (nextBtn) {
                nextBtn.disabled = list.length <= 1;
            }
            list.forEach(function (slide, i) {
                var imgEl = slide.querySelector('.pds-photo-carousel-img');
                if (!imgEl) {
                    return;
                }
                if (i === state.currentIndex) {
                    imgEl.style.setProperty('transform', buildTransform(state), 'important');
                    imgEl.setAttribute('data-pds-rotation', String(state.rotation));
                } else {
                    var saved = state.slideStates[String(i)];
                    if (saved) {
                        imgEl.style.setProperty('transform', buildTransform(saved), 'important');
                        imgEl.setAttribute('data-pds-rotation', String(saved.rotation || 0));
                    } else {
                        imgEl.style.setProperty('transform', 'translate(-50%, -50%) rotate(0deg) scale(1)', 'important');
                        imgEl.setAttribute('data-pds-rotation', '0');
                    }
                }
            });
            viewport.classList.toggle('is-zoomed', state.scale > 1.02);
            updateZoomLabel(state);
            writeRotationToDom(state);
        }

        state.updateCarousel = updateCarousel;

        if (!carousel.getAttribute('data-pds-photo-zoom-nav')) {
            carousel.setAttribute('data-pds-photo-zoom-nav', '1');
            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    var ctrl = getController(viewport);
                    if (ctrl && typeof ctrl.updateCarousel === 'function') {
                        ctrl.updateCarousel(ctrl.currentIndex - 1);
                    }
                });
            }
            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    var ctrl = getController(viewport);
                    if (ctrl && typeof ctrl.updateCarousel === 'function') {
                        ctrl.updateCarousel(ctrl.currentIndex + 1);
                    }
                });
            }
        }

        bindViewportInteractions(viewport, state);
        updateCarousel(state.currentIndex || 0);
        return state;
    }

    function openDispatchPhoto(url, name) {
        var img = document.getElementById('dispatchPhotoImg');
        var title = document.getElementById('dispatchPhotoViewModalLabel');
        var viewport = document.getElementById('dispatchPhotoViewport');
        if (!img || !viewport) {
            return;
        }

        var state = mountDispatchPhoto(viewport);
        img.src = url;
        img.alt = name || 'Photo';
        if (title) {
            title.textContent = name || title.textContent;
        }
        if (state) {
            state.currentSlideKey = String(url || 'dispatch');
            if (!state.slideStates[state.currentSlideKey]) {
                resetTransform(viewport, state);
            } else {
                restoreSlideState(state, state.currentSlideKey);
                applyTransform(viewport, state);
            }
        }

        if (typeof $ !== 'undefined' && $.fn.modal) {
            $('#dispatchPhotoViewModal').modal('show');
        }
    }

    function readInlineRotation() {
        var img = document.getElementById('pdsItemInlinePhotoImg');
        if (img && img.hasAttribute('data-pds-rotation')) {
            return normalizeRotation(img.getAttribute('data-pds-rotation'));
        }
        var input = document.getElementById('photo_rotation');
        if (input && String(input.value || '').trim() !== '') {
            return normalizeRotation(input.value);
        }
        if (typeof window.__pdsInlinePhotoRotation === 'number') {
            return normalizeRotation(window.__pdsInlinePhotoRotation);
        }
        var viewport = document.getElementById('pdsItemInlinePhotoViewport');
        if (viewport) {
            var state = VIEWPORTS.get(viewport);
            if (state) {
                return normalizeRotation(state.rotation);
            }
        }
        return 0;
    }

    function boot() {
        bindDelegation();
        mountDispatchPhoto();
        mountCarousel();
    }

    window.PdsPhotoZoom = {
        __booted: true,
        boot: boot,
        mountInlinePhoto: mountInlinePhoto,
        mountDispatchPhoto: mountDispatchPhoto,
        mountCarousel: mountCarousel,
        openDispatchPhoto: openDispatchPhoto,
        readInlineRotation: readInlineRotation,
        rotateBy: function (viewport, delta) {
            var state = getController(viewport);
            if (state) {
                rotateBy(viewport, state, delta);
            }
        },
        reset: function (viewport) {
            var state = getController(viewport);
            if (state) {
                resetTransform(viewport, state);
            }
        },
        getRotation: function (viewport) {
            if (viewport && viewport.id === 'pdsItemInlinePhotoViewport') {
                return readInlineRotation();
            }
            var state = getController(viewport);
            return state ? normalizeRotation(state.rotation) : 0;
        }
    };

    window.__pdsResetInlinePhotoRotation = function () {
        var viewport = document.getElementById('pdsItemInlinePhotoViewport');
        if (viewport) {
            window.PdsPhotoZoom.reset(viewport);
        }
        window.__pdsInlinePhotoRotation = 0;
        var input = document.getElementById('photo_rotation');
        if (input) {
            input.value = '0';
        }
        var img = document.getElementById('pdsItemInlinePhotoImg');
        if (img) {
            img.setAttribute('data-pds-rotation', '0');
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    if (typeof $ !== 'undefined') {
        $(document).on('shown.bs.modal', '#remoteModelData', function () {
            window.PdsPhotoZoom.mountInlinePhoto(null, { reset: false });
        });
        $(document).on('hidden.bs.modal', '#dispatchPhotoViewModal', function () {
            var viewport = document.getElementById('dispatchPhotoViewport');
            if (viewport) {
                window.PdsPhotoZoom.reset(viewport);
            }
        });
        $(document).off('click.pdsPhotoThumb', '.pds-dispatch-photo-thumb-btn')
            .on('click.pdsPhotoThumb', '.pds-dispatch-photo-thumb-btn', function (e) {
                e.preventDefault();
                e.stopPropagation();
                window.PdsPhotoZoom.openDispatchPhoto(
                    $(this).data('photo-url'),
                    $(this).data('photo-name')
                );
            });
    }
})(window, window.jQuery);
