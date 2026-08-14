<div class="modal fade pds-dispatch-photo-modal" id="dispatchPhotoViewModal" tabindex="-1" role="dialog" aria-labelledby="dispatchPhotoViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content pds-dispatch-photo-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dispatchPhotoViewModalLabel">{{ __('message.photo_order_images') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('message.close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body pds-dispatch-photo-modal-body">
                <div class="pds-dispatch-photo-viewport" id="dispatchPhotoViewport">
                    <div class="pds-dispatch-photo-stage">
                        <img src="" alt="" class="pds-dispatch-photo-img" id="dispatchPhotoImg" draggable="false">
                    </div>
                </div>
                <div class="pds-photo-carousel-zoom-bar">
                    <button type="button" class="pds-photo-carousel-zoom-btn" data-dispatch-photo-zoom="out" aria-label="Zoom out">
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <span class="pds-photo-carousel-zoom-level" data-dispatch-photo-zoom-level>100%</span>
                    <button type="button" class="pds-photo-carousel-zoom-btn" data-dispatch-photo-zoom="in" aria-label="Zoom in">
                        <i class="fas fa-search-plus"></i>
                    </button>
                    <button type="button" class="pds-photo-carousel-zoom-btn pds-photo-carousel-zoom-btn--reset" data-dispatch-photo-zoom="reset" aria-label="Reset zoom">
                        Reset
                    </button>
                </div>
                <p class="pds-photo-carousel-zoom-hint mb-0">Scroll / pinch ဖြင့် ချုံ/ချဲ့ · ဆွဲပြီး ရွှေ့ · နှစ်ချက်နှိပ် zoom</p>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('dispatchPhotoViewModal');
        if (!modal) return;

        var viewport = document.getElementById('dispatchPhotoViewport');
        var img = document.getElementById('dispatchPhotoImg');
        var title = document.getElementById('dispatchPhotoViewModalLabel');
        var zoomLevel = modal.querySelector('[data-dispatch-photo-zoom-level]');
        var scale = 1;
        var translateX = 0;
        var translateY = 0;
        var minScale = 1;
        var maxScale = 6;
        var isDragging = false;
        var dragStartX = 0;
        var dragStartY = 0;
        var dragOriginX = 0;
        var dragOriginY = 0;
        var pinchStartDistance = 0;
        var pinchStartScale = 1;
        var activePointers = new Map();

        function clamp(value, min, max) {
            return Math.min(max, Math.max(min, value));
        }

        function updateZoomLabel() {
            if (zoomLevel) {
                zoomLevel.textContent = Math.round(scale * 100) + '%';
            }
        }

        function applyTransform() {
            if (!img) return;
            img.style.transform = 'translate(calc(-50% + ' + translateX + 'px), calc(-50% + ' + translateY + 'px)) scale(' + scale + ')';
            if (viewport) {
                viewport.classList.toggle('is-zoomed', scale > 1.02);
            }
            updateZoomLabel();
        }

        function resetTransform() {
            scale = 1;
            translateX = 0;
            translateY = 0;
            if (img) {
                img.style.transform = 'translate(-50%, -50%) scale(1)';
            }
            if (viewport) {
                viewport.classList.remove('is-zoomed');
            }
            updateZoomLabel();
        }

        function zoomAt(clientX, clientY, nextScale) {
            if (!viewport) return;
            var rect = viewport.getBoundingClientRect();
            var centerX = rect.left + rect.width / 2;
            var centerY = rect.top + rect.height / 2;
            var offsetX = clientX - centerX;
            var offsetY = clientY - centerY;
            var ratio = nextScale / scale;

            translateX = (translateX - offsetX) * ratio + offsetX;
            translateY = (translateY - offsetY) * ratio + offsetY;
            scale = clamp(nextScale, minScale, maxScale);
            applyTransform();
        }

        function openPhotoViewer(url, name) {
            if (!img) return;
            img.src = url;
            img.alt = name || 'Photo';
            if (title) {
                title.textContent = name || '{{ __('message.photo_order_images') }}';
            }
            resetTransform();
            if (typeof $ !== 'undefined' && $.fn.modal) {
                $('#dispatchPhotoViewModal').modal('show');
            }
        }

        $(document).on('click', '.pds-dispatch-photo-thumb-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openPhotoViewer($(this).data('photo-url'), $(this).data('photo-name'));
        });

        modal.querySelectorAll('[data-dispatch-photo-zoom]').forEach(function (button) {
            button.addEventListener('click', function () {
                var action = button.getAttribute('data-dispatch-photo-zoom');
                if (action === 'reset') {
                    resetTransform();
                    return;
                }
                if (!viewport) return;
                var rect = viewport.getBoundingClientRect();
                var centerX = rect.left + rect.width / 2;
                var centerY = rect.top + rect.height / 2;
                var delta = action === 'in' ? 0.35 : -0.35;
                zoomAt(centerX, centerY, scale + delta);
            });
        });

        if (viewport) {
            viewport.addEventListener('wheel', function (event) {
                event.preventDefault();
                var delta = event.deltaY < 0 ? 0.15 : -0.15;
                zoomAt(event.clientX, event.clientY, scale + delta);
            }, { passive: false });

            viewport.addEventListener('dblclick', function (event) {
                if (scale > 1.02) {
                    resetTransform();
                } else {
                    zoomAt(event.clientX, event.clientY, 2.5);
                }
            });

            viewport.addEventListener('pointerdown', function (event) {
                if (scale <= 1.02) return;
                activePointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
                if (activePointers.size === 1) {
                    isDragging = true;
                    dragStartX = event.clientX;
                    dragStartY = event.clientY;
                    dragOriginX = translateX;
                    dragOriginY = translateY;
                    viewport.setPointerCapture(event.pointerId);
                } else if (activePointers.size === 2) {
                    isDragging = false;
                    var points = Array.from(activePointers.values());
                    pinchStartDistance = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);
                    pinchStartScale = scale;
                }
            });

            viewport.addEventListener('pointermove', function (event) {
                if (!activePointers.has(event.pointerId)) return;
                activePointers.set(event.pointerId, { x: event.clientX, y: event.clientY });

                if (activePointers.size === 2) {
                    var points = Array.from(activePointers.values());
                    var distance = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);
                    if (pinchStartDistance > 0) {
                        zoomAt(event.clientX, event.clientY, pinchStartScale * (distance / pinchStartDistance));
                    }
                    return;
                }

                if (isDragging && scale > 1.02) {
                    translateX = dragOriginX + (event.clientX - dragStartX);
                    translateY = dragOriginY + (event.clientY - dragStartY);
                    applyTransform();
                }
            });

            function endPointer(event) {
                activePointers.delete(event.pointerId);
                if (activePointers.size < 2) {
                    pinchStartDistance = 0;
                }
                if (activePointers.size === 0) {
                    isDragging = false;
                }
            }

            viewport.addEventListener('pointerup', endPointer);
            viewport.addEventListener('pointercancel', endPointer);
            viewport.addEventListener('pointerleave', endPointer);
        }

        if (typeof $ !== 'undefined') {
            $('#dispatchPhotoViewModal').on('hidden.bs.modal', resetTransform);
        }
    })();
</script>
