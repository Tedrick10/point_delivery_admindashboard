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
                    <button type="button" class="pds-photo-carousel-zoom-btn" data-dispatch-photo-zoom="out" aria-label="Zoom out" title="Zoom out">
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <span class="pds-photo-carousel-zoom-level" data-dispatch-photo-zoom-level>100%</span>
                    <button type="button" class="pds-photo-carousel-zoom-btn" data-dispatch-photo-zoom="in" aria-label="Zoom in" title="Zoom in">
                        <i class="fas fa-search-plus"></i>
                    </button>
                    <button type="button" class="pds-photo-carousel-zoom-btn" data-dispatch-photo-zoom="rotate-left" aria-label="Rotate left" title="Rotate left">
                        <i class="fas fa-undo"></i>
                    </button>
                    <button type="button" class="pds-photo-carousel-zoom-btn" data-dispatch-photo-zoom="rotate-right" aria-label="Rotate right" title="Rotate right">
                        <i class="fas fa-redo"></i>
                    </button>
                    <button type="button" class="pds-photo-carousel-zoom-btn pds-photo-carousel-zoom-btn--reset" data-dispatch-photo-zoom="reset" aria-label="Reset zoom" title="Reset">
                        Reset
                    </button>
                </div>
                <p class="pds-photo-carousel-zoom-hint mb-0">Scroll / pinch ဖြင့် ချုံ/ချဲ့ · Rotate · ဆွဲပြီး ရွှေ့ · နှစ်ချက်နှိပ် zoom</p>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/pds-photo-zoom.js') }}?v=3"></script>
<script>
    (function () {
        if (window.PdsPhotoZoom) {
            window.PdsPhotoZoom.mountDispatchPhoto();
        }
    })();
</script>
