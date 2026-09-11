<div class="modal fade" id="pdsPendingRemarkHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered pds-pending-history-dialog" role="document">
        <div class="pds-pending-history-stage">
            <div class="modal-content pds-pending-history-modal">
                <div class="pds-pending-history-modal__head">
                    <div class="pds-pending-history-modal__brand">
                        <span class="pds-pending-history-modal__icon" aria-hidden="true">
                            <i class="fas fa-comment-dots"></i>
                        </span>
                        <div>
                            <h5 class="pds-pending-history-modal__title">{{ __('message.pending_remark_history') }}</h5>
                            <span class="pds-pending-history-modal__count" id="pdsPendingRemarkHistoryCount"></span>
                        </div>
                    </div>
                    <button type="button" class="pds-pending-history-modal__close" data-dismiss="modal" aria-label="{{ __('message.close') }}">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="pds-pending-history-modal__body" id="pdsPendingRemarkHistoryBody"></div>
            </div>
            <aside class="pds-pending-history-preview" id="pdsPendingRemarkImagePreview" hidden>
                <header class="pds-pending-history-preview__head">
                    <h5>{{ __('message.pending_image') }}</h5>
                    <button type="button" class="pds-pending-history-preview__close" id="pdsPendingRemarkImageClose" aria-label="{{ __('message.close') }}">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </header>
                <div class="pds-pending-history-preview__frame">
                    <img id="pdsPendingRemarkImage" src="" alt="{{ __('message.pending_image') }}">
                </div>
            </aside>
        </div>
    </div>
</div>
@push('bottom_script')
<script>
    (function () {
        if (window.__pdsPendingRemarkHistoryBound) {
            return;
        }
        window.__pdsPendingRemarkHistoryBound = true;

        var pendingImageLabel = @json(__('message.pending_image'));
        var totalLabel = @json(__('message.total'));

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function decodeRows(raw) {
            if (!raw) {
                return [];
            }
            try {
                var json = raw;
                try {
                    json = atob(raw);
                } catch (e) {}
                var rows = JSON.parse(json);
                return Array.isArray(rows) ? rows : [];
            } catch (err) {
                return [];
            }
        }

        function modalEl() {
            return document.getElementById('pdsPendingRemarkHistoryModal');
        }

        function previewEl() {
            return document.getElementById('pdsPendingRemarkImagePreview');
        }

        function closePreview() {
            var preview = previewEl();
            var img = document.getElementById('pdsPendingRemarkImage');
            var modal = modalEl();
            if (preview) {
                preview.hidden = true;
            }
            if (img) {
                img.removeAttribute('src');
            }
            modal?.classList.remove('has-preview');
            modal?.querySelectorAll('.pds-pending-history-card.is-previewing').forEach(function (card) {
                card.classList.remove('is-previewing');
            });
        }

        function openPreview(url, card) {
            var preview = previewEl();
            var img = document.getElementById('pdsPendingRemarkImage');
            var modal = modalEl();
            if (!preview || !img || !url) {
                return;
            }
            img.src = url;
            preview.hidden = false;
            modal?.classList.add('has-preview');
            modal?.querySelectorAll('.pds-pending-history-card.is-previewing').forEach(function (el) {
                el.classList.remove('is-previewing');
            });
            card?.classList.add('is-previewing');
        }

        function renderRows(rows) {
            var countEl = document.getElementById('pdsPendingRemarkHistoryCount');
            var bodyEl = document.getElementById('pdsPendingRemarkHistoryBody');
            if (countEl) {
                countEl.textContent = totalLabel + ' ' + rows.length;
            }
            if (!bodyEl) {
                return;
            }

            bodyEl.innerHTML = '<div class="pds-pending-history-timeline">' + rows.map(function (row, index) {
                var date = escapeHtml(row.date || '-');
                var remark = escapeHtml(row.remark || '');
                var photo = String(row.photo_url || '').trim();
                var photoHtml = photo
                    ? '<button type="button" class="pds-pending-history-card__photo" data-preview-url="' + escapeHtml(photo) + '">' +
                        '<img src="' + escapeHtml(photo) + '" alt="' + escapeHtml(pendingImageLabel) + '">' +
                      '</button>'
                    : '';

                return '<article class="pds-pending-history-card">' +
                    '<div class="pds-pending-history-card__rail">' +
                        '<span class="pds-pending-history-card__index">' + (index + 1) + '</span>' +
                    '</div>' +
                    '<div class="pds-pending-history-card__main">' +
                        '<div class="pds-pending-history-card__copy">' +
                            '<div class="pds-pending-history-card__meta">' +
                                '<span class="pds-pending-history-card__date">' +
                                    '<i class="far fa-calendar-alt" aria-hidden="true"></i>' + date +
                                '</span>' +
                            '</div>' +
                            (remark ? '<p class="pds-pending-history-card__remark">' + remark + '</p>' : '') +
                        '</div>' +
                        photoHtml +
                    '</div>' +
                '</article>';
            }).join('') + '</div>';
        }

        function openModal() {
            var modal = modalEl();
            if (!modal) {
                return;
            }
            closePreview();
            if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
                window.jQuery(modal).modal('show');
                return;
            }
            modal.classList.add('show');
            modal.style.display = 'block';
            modal.removeAttribute('aria-hidden');
            document.body.classList.add('modal-open');
        }

        document.addEventListener('click', function (e) {
            var totalBtn = e.target.closest('.pds-pending-total-btn');
            if (totalBtn) {
                e.preventDefault();
                e.stopPropagation();
                var rows = decodeRows(totalBtn.getAttribute('data-pending-remarks'));
                if (!rows.length) {
                    return;
                }
                renderRows(rows);
                openModal();
                return;
            }

            var photoBtn = e.target.closest('.pds-pending-history-card__photo');
            if (photoBtn && modalEl()?.contains(photoBtn)) {
                e.preventDefault();
                e.stopPropagation();
                openPreview(
                    photoBtn.getAttribute('data-preview-url') || '',
                    photoBtn.closest('.pds-pending-history-card')
                );
                return;
            }

            if (e.target.closest('#pdsPendingRemarkImageClose')) {
                e.preventDefault();
                closePreview();
            }
        });

        if (window.jQuery) {
            window.jQuery(document).on('hidden.bs.modal', '#pdsPendingRemarkHistoryModal', closePreview);
        }
    })();
</script>
@endpush
