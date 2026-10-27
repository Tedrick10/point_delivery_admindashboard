@php
    $viewportId = $viewportId ?? '';
@endphp
<div class="pds-photo-carousel-zoom-bar pds-expense-photo-zoom-bar">
    <button type="button"
            class="pds-photo-carousel-zoom-btn"
            data-expense-photo-zoom="out"
            data-expense-photo-viewport="{{ $viewportId }}"
            aria-label="Zoom out"
            title="Zoom out">
        <i class="fas fa-search-minus"></i>
    </button>
    <span class="pds-photo-carousel-zoom-level" data-expense-photo-zoom-level>100%</span>
    <button type="button"
            class="pds-photo-carousel-zoom-btn"
            data-expense-photo-zoom="in"
            data-expense-photo-viewport="{{ $viewportId }}"
            aria-label="Zoom in"
            title="Zoom in">
        <i class="fas fa-search-plus"></i>
    </button>
    <button type="button"
            class="pds-photo-carousel-zoom-btn"
            data-expense-photo-zoom="rotate-left"
            data-expense-photo-viewport="{{ $viewportId }}"
            aria-label="Rotate left"
            title="Rotate left">
        <i class="fas fa-undo"></i>
    </button>
    <button type="button"
            class="pds-photo-carousel-zoom-btn"
            data-expense-photo-zoom="rotate-right"
            data-expense-photo-viewport="{{ $viewportId }}"
            aria-label="Rotate right"
            title="Rotate right">
        <i class="fas fa-redo"></i>
    </button>
    <button type="button"
            class="pds-photo-carousel-zoom-btn pds-photo-carousel-zoom-btn--reset"
            data-expense-photo-zoom="reset"
            data-expense-photo-viewport="{{ $viewportId }}"
            aria-label="Reset zoom"
            title="Reset">
        Reset
    </button>
</div>
<p class="pds-photo-carousel-zoom-hint mb-0">Scroll / pinch ဖြင့် ချုံ/ချဲ့ · Rotate · ဆွဲပြီး ရွှေ့ · နှစ်ချက်နှိပ် zoom</p>
