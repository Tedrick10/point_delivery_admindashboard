<x-master-layout :assets="$assets ?? []">
<style>
body.pds-admin .pds-os-settlement-table tfoot td {
    padding: 0.85rem;
    background: #fff7ed;
    border-top: 2px solid #fdba74;
    vertical-align: middle;
}
body.pds-admin .pds-os-settlement-total-row .pds-os-settlement-total-items {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.82rem;
    font-weight: 600;
    color: #9a3412;
}
body.pds-admin .pds-os-settlement-total-row .pds-os-settlement-total-items strong {
    font-size: 0.95rem;
    font-weight: 700;
    color: #c2410c;
}
body.pds-admin .pds-admin-kpay-panel {
    margin: 0 0 1.15rem;
    padding: 0;
    background: #fff;
    border: 1px solid rgba(254, 111, 7, 0.14);
    border-radius: 18px;
    box-shadow: 0 10px 28px rgba(154, 52, 18, 0.06);
    overflow: hidden;
}
body.pds-admin .pds-admin-kpay-panel__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin: 0;
    padding: 1rem 1.15rem;
    border-bottom: 0;
    background:
        linear-gradient(135deg, rgba(254, 111, 7, 0.1) 0%, rgba(254, 111, 7, 0.02) 55%, #fff 100%);
}
body.pds-admin .pds-admin-kpay-panel__title {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    min-width: 0;
}
body.pds-admin .pds-admin-kpay-panel__icon {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    background: linear-gradient(145deg, #fe6f07, #f59e0b);
    color: #fff;
    font-size: 1rem;
    box-shadow: 0 8px 16px rgba(254, 111, 7, 0.28);
}
body.pds-admin .pds-admin-kpay-panel__copy strong {
    display: block;
    color: #1c1917;
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -0.015em;
    line-height: 1.2;
}
body.pds-admin .pds-admin-kpay-panel__copy span {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    margin-top: 0.2rem;
    padding: 0.18rem 0.5rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.85);
    border: 1px solid rgba(254, 111, 7, 0.12);
    color: #78716c;
    font-size: 0.72rem;
    font-weight: 600;
}
body.pds-admin .pds-admin-kpay-panel__copy span i {
    font-size: 0.64rem;
    color: #fe6f07;
}
body.pds-admin .pds-admin-kpay-panel__actions {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    flex-wrap: wrap;
}
body.pds-admin #osAdminKpayClearBtn.pds-admin-kpay-clear-btn {
    height: 40px;
    padding: 0 0.95rem;
    border-radius: 12px;
    border: 1px solid #fecaca;
    background: #fff;
    color: #b91c1c;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.01em;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: background .15s ease, border-color .15s ease, transform .15s ease;
}
body.pds-admin #osAdminKpayClearBtn.pds-admin-kpay-clear-btn:hover {
    background: #fef2f2;
    border-color: #f87171;
    color: #991b1b;
    transform: translateY(-1px);
}
body.pds-admin #osAdminKpayClearBtn.pds-admin-kpay-clear-btn[hidden] {
    display: none !important;
}
body.pds-admin #osAdminKpayCreateBtn.pds-admin-kpay-create-btn {
    height: 40px;
    padding: 0 1.05rem;
    border-radius: 12px;
    border: 0;
    background: linear-gradient(135deg, #fe6f07, #ea580c);
    color: #fff;
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    box-shadow: 0 8px 18px rgba(234, 88, 12, 0.28);
    transition: transform .15s ease, box-shadow .15s ease;
}
body.pds-admin #osAdminKpayCreateBtn.pds-admin-kpay-create-btn:hover {
    background: linear-gradient(135deg, #ea580c, #c2410c);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 12px 22px rgba(234, 88, 12, 0.34);
}
body.pds-admin #osAdminKpayCreateBtn.pds-admin-kpay-create-btn i {
    font-size: 0.7rem;
}
body.pds-admin .pds-admin-kpay-panel__list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 0.9rem;
    padding: 1rem 1.15rem 1.2rem;
    background: linear-gradient(180deg, #fffaf6 0%, #fff7ed 100%);
}
body.pds-admin .pds-admin-kpay-panel__empty {
    margin: 0;
    padding: 1.8rem 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    text-align: center;
    color: #a8a29e;
    font-size: 0.86rem;
    background: #fff;
    border: 1.5px dashed #fdba74;
    border-radius: 16px;
    grid-column: 1 / -1;
}
body.pds-admin .pds-admin-kpay-panel__empty i {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #fff7ed;
    color: #fe6f07;
    font-size: 1rem;
}
body.pds-admin .pds-admin-kpay-chip {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 0;
    width: 100%;
    min-height: 0;
    padding: 0;
    background: #fff;
    border: 1px solid #f0e6db;
    border-radius: 20px;
    box-shadow: 0 10px 24px rgba(120, 53, 15, 0.05);
    position: relative;
    overflow: hidden;
    transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
}
body.pds-admin .pds-admin-kpay-chip::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: linear-gradient(180deg, #fb923c 0%, #ea580c 100%);
    border-radius: 20px 0 0 20px;
}
body.pds-admin .pds-admin-kpay-chip:hover {
    background: #fff;
    border-color: #fdba74;
    box-shadow: 0 16px 32px rgba(154, 52, 18, 0.1);
    transform: translateY(-2px);
}
body.pds-admin .pds-admin-kpay-chip__top {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    min-width: 0;
    padding: 1.05rem 1.15rem 0.95rem 1.25rem;
}
body.pds-admin .pds-admin-kpay-chip__avatar {
    width: 46px;
    height: 46px;
    border-radius: 15px;
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, #fff7ed 0%, #ffedd5 100%);
    color: #c2410c;
    font-size: 1.05rem;
    font-weight: 800;
    border: 1px solid #fed7aa;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
}
body.pds-admin .pds-admin-kpay-chip__main {
    display: flex;
    flex-direction: column;
    gap: 0.28rem;
    min-width: 0;
    flex: 1 1 auto;
    padding-right: 0;
}
body.pds-admin .pds-admin-kpay-chip__main strong {
    font-size: 1.02rem;
    color: #1c1917;
    font-weight: 800;
    line-height: 1.35;
    letter-spacing: -0.02em;
    white-space: normal;
    overflow: visible;
    word-break: break-word;
}
body.pds-admin .pds-admin-kpay-chip__main span {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    min-width: 0;
    font-size: 0.82rem;
    color: #78716c;
    font-variant-numeric: tabular-nums;
    white-space: normal;
    overflow: visible;
    word-break: break-word;
}
body.pds-admin .pds-admin-kpay-chip__main span i {
    font-size: 0.72rem;
    color: #ea580c;
    flex: 0 0 auto;
}
body.pds-admin .pds-admin-kpay-chip__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-top: 0;
    padding: 0.85rem 1.05rem 0.95rem 1.25rem;
    border-top: 1px solid #f5efe8;
    background: linear-gradient(180deg, #fffaf6 0%, #fff7ed 100%);
}
body.pds-admin .pds-admin-kpay-chip__balance {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.12rem;
    min-width: 0;
}
body.pds-admin .pds-admin-kpay-chip__balance small {
    font-size: 0.64rem;
    font-weight: 800;
    color: #a8a29e;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
body.pds-admin .pds-admin-kpay-chip__meta em,
body.pds-admin .pds-admin-kpay-chip__balance em {
    font-style: normal;
    font-size: 1.22rem;
    font-weight: 800;
    color: #c2410c;
    white-space: nowrap;
    background: transparent;
    border: 0;
    border-radius: 0;
    padding: 0;
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.03em;
    line-height: 1.15;
}
body.pds-admin .pds-admin-kpay-chip__actions {
    position: static;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    flex: 0 0 auto;
    padding: 0;
    border-radius: 0;
    background: transparent;
    border: 0;
    box-shadow: none;
    z-index: 1;
}
body.pds-admin .pds-admin-kpay-chip__info,
body.pds-admin .pds-admin-kpay-chip__edit,
body.pds-admin .pds-admin-kpay-chip__del {
    width: 34px;
    height: 34px;
    border: 1px solid #efe8df;
    border-radius: 11px;
    background: #fff;
    color: #78716c;
    padding: 0;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.76rem;
    box-shadow: 0 1px 2px rgba(28, 25, 23, 0.04);
    transition: background .15s ease, color .15s ease, border-color .15s ease, box-shadow .15s ease;
}
body.pds-admin .pds-admin-kpay-chip__info:hover {
    background: #fff7ed;
    border-color: #fdba74;
    color: #ea580c;
    box-shadow: 0 4px 10px rgba(234, 88, 12, 0.12);
}
body.pds-admin .pds-admin-kpay-chip__edit:hover {
    background: #fff7ed;
    border-color: #fdba74;
    color: #c2410c;
    box-shadow: 0 4px 10px rgba(234, 88, 12, 0.12);
}
body.pds-admin .pds-admin-kpay-chip__del:hover {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
    box-shadow: 0 4px 10px rgba(220, 38, 38, 0.1);
}
body.pds-admin .pds-admin-kpay-history-modal .modal-dialog {
    max-width: 720px;
}
body.pds-admin .pds-admin-kpay-history-modal .modal-content {
    border-radius: 24px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 28px 60px rgba(28, 25, 23, 0.22);
}
body.pds-admin .pds-admin-kpay-history-modal .modal-header {
    padding: 1.15rem 1.35rem 0.7rem;
    border-bottom: 0;
    background: transparent;
}
body.pds-admin .pds-admin-kpay-history-modal .modal-title {
    gap: 0.7rem;
    flex-wrap: wrap;
}
body.pds-admin .pds-admin-kpay-history-modal .modal-body {
    max-height: min(72vh, 620px);
    overflow: auto;
    padding: 0.1rem 1.35rem 0.45rem;
}
body.pds-admin .pds-admin-kpay-history-modal .modal-footer {
    border-top: 0;
    padding: 0.65rem 1.35rem 1.2rem;
}
body.pds-admin .pds-admin-kpay-history-account {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    margin: 0 0 0.95rem;
    padding: 0 0 0.95rem;
    border-bottom: 1px solid #f0ebe4;
    background: transparent;
    border-radius: 0;
}
body.pds-admin .pds-admin-kpay-history-account__avatar {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, #fe6f07, #f59e0b);
    color: #fff;
    font-weight: 800;
    font-size: 1rem;
    box-shadow: 0 8px 16px rgba(254, 111, 7, 0.22);
}
body.pds-admin .pds-admin-kpay-history-account__main {
    min-width: 0;
    flex: 1 1 auto;
}
body.pds-admin .pds-admin-kpay-history-account__main strong {
    display: block;
    font-size: 1.02rem;
    font-weight: 800;
    color: #1c1917;
    line-height: 1.25;
    letter-spacing: -0.015em;
}
body.pds-admin .pds-admin-kpay-history-account__main span {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    margin-top: 0.2rem;
    font-size: 0.8rem;
    color: #78716c;
    font-variant-numeric: tabular-nums;
}
body.pds-admin .pds-admin-kpay-history-account__main span i {
    color: #fe6f07;
    font-size: 0.68rem;
}
body.pds-admin .pds-admin-kpay-history-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.6rem;
    margin: 0 0 1.25rem;
}
body.pds-admin .pds-admin-kpay-history-summary__item {
    display: flex;
    flex-direction: column;
    gap: 0.28rem;
    min-width: 0;
    padding: 0.8rem 0.85rem;
    border-radius: 14px;
    background: #fafaf9;
    border: 1px solid #f0ebe4;
}
body.pds-admin .pds-admin-kpay-history-summary__item span {
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #a8a29e;
}
body.pds-admin .pds-admin-kpay-history-summary__item span i {
    display: none;
}
body.pds-admin .pds-admin-kpay-history-summary__item strong {
    font-size: 1.05rem;
    color: #1c1917;
    font-weight: 800;
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
body.pds-admin .pds-admin-kpay-history-summary__item.is-balance strong { color: #047857; }
body.pds-admin .pds-admin-kpay-history-summary__item.is-transferred strong { color: #c2410c; }
body.pds-admin .pds-admin-kpay-history-day {
    margin: 0 0 1.35rem;
}
body.pds-admin .pds-admin-kpay-history-day:last-child {
    margin-bottom: 0.2rem;
}
body.pds-admin .pds-admin-kpay-history-day__head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.75rem;
    margin: 0 0 0.6rem;
    padding: 0 0.2rem;
}
body.pds-admin .pds-admin-kpay-history-day__head strong {
    font-size: 0.78rem;
    color: #a8a29e;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
body.pds-admin .pds-admin-kpay-history-day__head em {
    font-style: normal;
    font-size: 0.82rem;
    font-weight: 800;
    color: #c2410c;
    font-variant-numeric: tabular-nums;
}
body.pds-admin .pds-admin-kpay-history-day__list {
    display: flex;
    flex-direction: column;
    border: 1px solid #efe8df;
    border-radius: 18px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 10px 24px rgba(120, 53, 15, 0.04);
}
body.pds-admin .pds-admin-kpay-history-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 0.55rem 1.1rem;
    align-items: start;
    padding: 1.05rem 1.1rem 1.05rem 1.15rem;
    border-top: 1px solid #f3eee8;
    background: #fff;
    position: relative;
}
body.pds-admin .pds-admin-kpay-history-row::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0.85rem;
    bottom: 0.85rem;
    width: 3px;
    border-radius: 999px;
    background: linear-gradient(180deg, #fe6f07, #fdba74);
    opacity: 0;
    transition: opacity .15s ease;
}
body.pds-admin .pds-admin-kpay-history-row:first-child {
    border-top: 0;
}
body.pds-admin .pds-admin-kpay-history-row:hover {
    background: #fffbf7;
}
body.pds-admin .pds-admin-kpay-history-row:hover::before {
    opacity: 1;
}
body.pds-admin .pds-admin-kpay-history-row__main {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.22rem;
}
body.pds-admin .pds-admin-kpay-history-row__os {
    font-size: 0.98rem;
    font-weight: 800;
    color: #1c1917;
    line-height: 1.3;
    letter-spacing: -0.015em;
}
body.pds-admin .pds-admin-kpay-history-row__line {
    font-size: 0.78rem;
    color: #78716c;
    line-height: 1.45;
    font-weight: 500;
    white-space: normal;
    overflow: visible;
    word-break: break-word;
}
body.pds-admin .pds-admin-kpay-history-row__line b {
    color: #57534e;
    font-weight: 700;
}
body.pds-admin .pds-admin-kpay-history-row__foot {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem 0.7rem;
    margin-top: 0.28rem;
}
body.pds-admin .pds-admin-kpay-history-row__admin {
    font-size: 0.76rem;
    color: #78716c;
    font-weight: 500;
}
body.pds-admin .pds-admin-kpay-history-row__admin b {
    color: #57534e;
    font-weight: 700;
}
body.pds-admin .pds-admin-kpay-history-row__time {
    font-size: 0.72rem;
    color: #a8a29e;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}
body.pds-admin .pds-admin-kpay-history-row__time::before {
    content: '·';
    margin-right: 0.55rem;
    color: #d6d3d1;
}
body.pds-admin .pds-admin-kpay-history-row__amount {
    text-align: right;
    white-space: nowrap;
    padding-top: 0.05rem;
}
body.pds-admin .pds-admin-kpay-history-row__amount strong {
    display: block;
    font-size: 1.08rem;
    font-weight: 800;
    color: #c2410c;
    letter-spacing: -0.025em;
    font-variant-numeric: tabular-nums;
    line-height: 1.15;
}
body.pds-admin .pds-admin-kpay-history-empty {
    margin: 0;
    padding: 2rem 1.1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.55rem;
    text-align: center;
    color: #a8a29e;
    font-size: 0.9rem;
    border: 1.5px dashed #e7e5e4;
    border-radius: 16px;
    background: #fafaf9;
}
body.pds-admin .pds-admin-kpay-history-empty i {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #fff7ed;
    color: #fe6f07;
}
body.pds-admin .pds-admin-kpay-history-modal .pds-admin-kpay-history-close {
    min-width: 96px;
    height: 40px;
    border-radius: 12px;
    border: 1px solid #e7e5e4;
    background: #fff;
    color: #44403c;
    font-weight: 800;
}
body.pds-admin .pds-admin-kpay-history-modal .pds-admin-kpay-history-close:hover {
    background: #fafaf9;
    border-color: #d6d3d1;
}
@media (max-width: 720px) {
    body.pds-admin .pds-admin-kpay-history-summary {
        grid-template-columns: 1fr;
    }
    body.pds-admin .pds-admin-kpay-history-row {
        grid-template-columns: 1fr;
        gap: 0.45rem;
    }
    body.pds-admin .pds-admin-kpay-history-row__amount {
        text-align: left;
    }
}
/* Above proof lightbox (20050) and theme modal stack (10650) so Finished confirm always paints on top. */
body.pds-admin .pds-admin-kpay-modal,
body.pds-admin #osAdminKpayConfirmModal.modal,
body.pds-admin #osAdminKpayFormModal.modal,
body.pds-admin #osAdminKpayActionModal.modal,
body.pds-admin #osAdminKpayHistoryModal.modal {
    z-index: 21060 !important;
}
/* Fade transition can leave opacity:0 mid-frame; force visible once Bootstrap adds .show. */
body.pds-admin .pds-admin-kpay-modal.show,
body.pds-admin #osAdminKpayConfirmModal.modal.show {
    opacity: 1 !important;
    display: block !important;
    pointer-events: auto !important;
    visibility: visible !important;
}
body.pds-admin .modal-backdrop.pds-admin-kpay-backdrop {
    z-index: 21050 !important;
    opacity: 0.55 !important;
}
.pds-os-notify-toast {
    position: fixed;
    right: 1rem;
    bottom: 1.25rem;
    z-index: 22000;
    max-width: min(420px, calc(100vw - 2rem));
    padding: 0.85rem 1.05rem;
    border-radius: 14px;
    font-weight: 700;
    font-size: 0.92rem;
    line-height: 1.35;
    color: #fff;
    background: #1c1917;
    box-shadow: 0 16px 40px rgba(28, 25, 23, 0.28);
    opacity: 0;
    transform: translateY(10px);
    pointer-events: none;
    transition: opacity .18s ease, transform .18s ease;
}
.pds-os-notify-toast.is-visible {
    opacity: 1;
    transform: translateY(0);
}
.pds-os-notify-toast.is-error { background: #b91c1c; }
.pds-os-notify-toast.is-success { background: #15803d; }
.pds-os-notify-toast.is-warning { background: #c2410c; }
body.pds-admin .pds-admin-kpay-modal .modal-dialog {
    max-width: 440px;
}
body.pds-admin .pds-admin-kpay-modal .modal-content {
    border: 0;
    border-radius: 20px;
    overflow: visible;
    box-shadow: 0 28px 56px rgba(28, 25, 23, 0.2);
}
body.pds-admin .pds-admin-kpay-modal .modal-body {
    overflow: visible;
}
body.pds-admin .pds-admin-kpay-modal .modal-header {
    position: relative;
    border-bottom: 0;
    padding: 1.15rem 1.3rem 0.85rem;
    background: linear-gradient(180deg, #fff7ed 0%, #fff 100%);
}
body.pds-admin .pds-admin-kpay-modal .modal-header::before {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    height: 4px;
    background: linear-gradient(90deg, #fe6f07, #f59e0b);
}
body.pds-admin .pds-admin-kpay-modal .modal-title {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 1.05rem;
    font-weight: 800;
    color: #1c1917;
}
body.pds-admin .pds-admin-kpay-modal .modal-title i {
    width: 34px;
    height: 34px;
    border-radius: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, #fe6f07, #f59e0b);
    color: #fff;
    font-size: 0.8rem;
    box-shadow: 0 6px 14px rgba(254, 111, 7, 0.28);
}
body.pds-admin .pds-admin-kpay-queue-progress {
    display: inline-flex;
    align-items: center;
    margin-left: 0.35rem;
    padding: 0.18rem 0.55rem;
    border-radius: 999px;
    background: #fff7ed;
    border: 1px solid #fdba74;
    color: #c2410c;
    font-style: normal;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    white-space: nowrap;
}
body.pds-admin .pds-admin-kpay-queue-progress[hidden] {
    display: none !important;
}
body.pds-admin .pds-admin-kpay-modal .close {
    opacity: 0.4;
}
body.pds-admin .pds-admin-kpay-modal .modal-body {
    padding: 0.35rem 1.3rem 0.4rem;
}
body.pds-admin .pds-admin-kpay-modal .modal-footer {
    position: relative;
    z-index: 40;
    border-top: 0;
    padding: 0.9rem 1.3rem 1.25rem;
    gap: 0.55rem;
}
body.pds-admin .pds-admin-kpay-modal .form-group {
    margin-bottom: 0.9rem;
}
body.pds-admin .pds-admin-kpay-modal .form-group label {
    font-size: 0.74rem;
    font-weight: 700;
    color: #78716c;
    margin-bottom: 0.35rem;
}
body.pds-admin .pds-admin-kpay-modal .form-control {
    height: 44px;
    border-radius: 12px;
    border-color: #e7e5e4;
    background: #fafaf9;
    font-size: 0.9rem;
    color: #1c1917;
    box-shadow: none;
}
body.pds-admin .pds-admin-kpay-modal .form-control:focus {
    border-color: #fe6f07;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(254, 111, 7, 0.12);
}
body.pds-admin .pds-admin-kpay-modal .btn-secondary {
    border: 0;
    border-radius: 12px;
    background: #f5f5f4;
    color: #57534e;
    font-weight: 700;
    padding: 0.65rem 1.15rem;
}
body.pds-admin .pds-admin-kpay-modal .btn-primary,
body.pds-admin .pds-admin-kpay-modal .pds-admin-kpay-modal__primary {
    border: 0;
    border-radius: 12px;
    background: linear-gradient(135deg, #fe6f07, #ea580c);
    color: #fff;
    font-weight: 800;
    padding: 0.65rem 1.25rem;
    box-shadow: 0 8px 18px rgba(234, 88, 12, 0.28);
}
body.pds-admin .pds-admin-kpay-modal .btn-primary:hover,
body.pds-admin .pds-admin-kpay-modal .pds-admin-kpay-modal__primary:hover {
    background: linear-gradient(135deg, #ea580c, #c2410c);
}
body.pds-admin .pds-admin-kpay-action-modal .modal-dialog {
    max-width: 400px;
}
body.pds-admin .pds-admin-kpay-action-modal .modal-content {
    overflow: hidden;
    border-radius: 24px;
    border: 1px solid rgba(254, 215, 170, 0.55);
    background:
        radial-gradient(120% 80% at 50% -10%, rgba(255, 237, 213, 0.95) 0%, rgba(255, 255, 255, 0) 58%),
        #fff;
    box-shadow:
        0 28px 60px rgba(120, 53, 15, 0.16),
        0 2px 0 rgba(255, 255, 255, 0.8) inset;
}
body.pds-admin .pds-admin-kpay-action-modal .modal-header {
    display: block;
    position: relative;
    padding: 1.55rem 1.4rem 0;
    border-bottom: 0;
    background: transparent;
    text-align: center;
}
body.pds-admin .pds-admin-kpay-action-modal .modal-header::before {
    display: none;
}
body.pds-admin .pds-admin-kpay-action-modal .modal-title {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.85rem;
    width: 100%;
    margin: 0;
    padding: 0 1.5rem;
    font-size: 1.18rem;
    font-weight: 800;
    letter-spacing: -0.025em;
    color: #1c1917;
}
body.pds-admin .pds-admin-kpay-action-modal .modal-title i,
body.pds-admin .pds-admin-kpay-action-modal .pds-admin-kpay-action-hero {
    width: 64px;
    height: 64px;
    border-radius: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(160deg, #fff7ed 0%, #ffedd5 100%);
    color: #ea580c;
    font-size: 1.25rem;
    border: 1px solid #fed7aa;
    box-shadow:
        0 12px 24px rgba(234, 88, 12, 0.14),
        inset 0 1px 0 rgba(255, 255, 255, 0.85);
}
body.pds-admin .pds-admin-kpay-action-modal.is-danger .modal-title i,
body.pds-admin .pds-admin-kpay-action-modal.is-danger .pds-admin-kpay-action-hero {
    background: linear-gradient(160deg, #fff1f2 0%, #ffe4e6 100%);
    color: #e11d48;
    border-color: #fecdd3;
    box-shadow:
        0 12px 24px rgba(225, 29, 72, 0.12),
        inset 0 1px 0 rgba(255, 255, 255, 0.85);
}
body.pds-admin .pds-admin-kpay-action-modal .close {
    position: absolute;
    top: 0.95rem;
    right: 0.95rem;
    z-index: 2;
    width: 34px;
    height: 34px;
    margin: 0;
    padding: 0;
    border-radius: 11px;
    background: rgba(255, 255, 255, 0.72);
    border: 1px solid #f0e6db;
    opacity: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #a8a29e;
    font-size: 1.35rem;
    line-height: 1;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}
body.pds-admin .pds-admin-kpay-action-modal .close:hover {
    background: #fff;
    color: #57534e;
    border-color: #fdba74;
}
body.pds-admin .pds-admin-kpay-action-modal .modal-body {
    padding: 0.85rem 1.55rem 0.35rem;
    text-align: center;
}
body.pds-admin .pds-admin-kpay-action-copy {
    margin: 0 auto;
    max-width: 20rem;
    color: #78716c;
    font-size: 0.92rem;
    line-height: 1.6;
}
body.pds-admin .pds-admin-kpay-action-card {
    margin: 1.1rem auto 0;
    max-width: 100%;
    padding: 0.9rem 1rem;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.86);
    border: 1px solid #f0e6db;
    box-shadow: 0 8px 18px rgba(120, 53, 15, 0.05);
    display: flex;
    align-items: center;
    gap: 0.8rem;
    text-align: left;
}
body.pds-admin .pds-admin-kpay-action-card[hidden] {
    display: none !important;
}
body.pds-admin .pds-admin-kpay-action-card__avatar {
    width: 42px;
    height: 42px;
    border-radius: 13px;
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, #fff7ed 0%, #ffedd5 100%);
    border: 1px solid #fed7aa;
    color: #c2410c;
    font-weight: 800;
}
body.pds-admin .pds-admin-kpay-action-card__main {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}
body.pds-admin .pds-admin-kpay-action-card__main strong {
    color: #1c1917;
    font-size: 0.95rem;
    font-weight: 800;
    line-height: 1.3;
    word-break: break-word;
}
body.pds-admin .pds-admin-kpay-action-card__main span {
    color: #78716c;
    font-size: 0.8rem;
}
body.pds-admin .pds-admin-kpay-action-modal .modal-footer {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.65rem;
    padding: 1.15rem 1.4rem 1.4rem;
    border-top: 0;
    background: transparent;
}
body.pds-admin .pds-admin-kpay-action-modal .modal-footer .btn {
    width: 100%;
    margin: 0;
    min-height: 46px;
    border-radius: 14px;
    font-size: 0.92rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
}
body.pds-admin .pds-admin-kpay-action-modal .modal-footer .btn-secondary {
    background: #fff;
    border: 1px solid #e7e5e4;
    color: #57534e;
    box-shadow: 0 1px 2px rgba(28, 25, 23, 0.04);
}
body.pds-admin .pds-admin-kpay-action-modal .modal-footer .btn-secondary:hover {
    background: #fafaf9;
    border-color: #d6d3d1;
    color: #1c1917;
}
body.pds-admin .pds-admin-kpay-action-modal .pds-admin-kpay-modal__danger {
    border: 0;
    border-radius: 14px;
    background: linear-gradient(135deg, #fb7185 0%, #e11d48 100%);
    color: #fff !important;
    font-weight: 800;
    padding: 0.7rem 1.1rem;
    box-shadow: 0 12px 22px rgba(225, 29, 72, 0.22);
}
body.pds-admin .pds-admin-kpay-action-modal .pds-admin-kpay-modal__danger i,
body.pds-admin .pds-admin-kpay-action-modal .pds-admin-kpay-modal__danger span {
    color: #fff !important;
}
body.pds-admin .pds-admin-kpay-action-modal .pds-admin-kpay-modal__danger:hover {
    background: linear-gradient(135deg, #f43f5e 0%, #be123c 100%);
    color: #fff !important;
    transform: translateY(-1px);
}
body.pds-admin .pds-admin-kpay-action-modal .pds-admin-kpay-modal__danger:hover i,
body.pds-admin .pds-admin-kpay-action-modal .pds-admin-kpay-modal__danger:hover span {
    color: #fff !important;
}
body.pds-admin .pds-admin-kpay-action-modal .pds-admin-kpay-modal__primary {
    border: 0;
    border-radius: 14px;
    background: linear-gradient(135deg, #fe6f07, #ea580c);
    color: #fff;
    font-weight: 800;
    padding: 0.7rem 1.1rem;
    box-shadow: 0 12px 22px rgba(234, 88, 12, 0.24);
}
body.pds-admin .pds-admin-kpay-action-modal .pds-admin-kpay-modal__danger:disabled,
body.pds-admin .pds-admin-kpay-action-modal .pds-admin-kpay-modal__primary:disabled {
    opacity: 0.65;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}
body.pds-admin .pds-admin-kpay-confirm-amount {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 0.2rem;
    padding: 1.15rem 1rem;
    margin-bottom: 0.95rem;
    border-radius: 16px;
    background:
        radial-gradient(120% 120% at 50% 0%, rgba(254, 111, 7, 0.16), transparent 60%),
        linear-gradient(180deg, #fff7ed, #ffedd5);
    border: 1px solid #fed7aa;
}
body.pds-admin .pds-admin-kpay-confirm-amount span {
    display: block;
    font-size: 0.72rem;
    font-weight: 700;
    color: #9a3412;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}
body.pds-admin .pds-admin-kpay-confirm-amount strong {
    display: block;
    margin-top: 0.1rem;
    font-size: 1.85rem;
    font-weight: 800;
    color: #c2410c;
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.03em;
    line-height: 1.1;
}
body.pds-admin .pds-admin-kpay-confirm-amount i {
    display: none;
}
body.pds-admin .pds-admin-kpay-confirm-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.65rem;
    padding: 0.85rem;
    border: 1px solid #f3f0ec;
    border-radius: 16px;
    background: #fafaf9;
}
body.pds-admin .pds-admin-kpay-confirm-grid__row {
    display: flex;
    flex-direction: column;
    gap: 0.18rem;
    padding: 0.55rem 0.65rem;
    border: 0;
    border-radius: 12px;
    background: #fff;
    min-width: 0;
}
body.pds-admin .pds-admin-kpay-confirm-grid__row--full {
    grid-column: 1 / -1;
}
body.pds-admin .pds-admin-kpay-confirm-grid__row span {
    display: block;
    color: #a8a29e;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    margin: 0;
    padding: 0;
}
body.pds-admin .pds-admin-kpay-confirm-grid__row strong {
    display: block;
    color: #1c1917;
    font-weight: 700;
    word-break: break-word;
    line-height: 1.35;
    font-size: 0.88rem;
}
body.pds-admin .pds-admin-kpay-confirm-select {
    margin-top: 0.9rem;
    padding: 0.9rem;
    border-radius: 16px;
    background: #fff7ed;
    border: 1px solid #fed7aa;
}
body.pds-admin .pds-admin-kpay-confirm-select label {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    color: #9a3412 !important;
}
body.pds-admin .pds-admin-kpay-confirm-select label i {
    display: inline-block;
    color: #fe6f07;
}
body.pds-admin .pds-admin-kpay-confirm-select .pds-admin-kpay-combo {
    position: relative;
    width: 100%;
    z-index: 5;
}
body.pds-admin .pds-admin-kpay-confirm-select .pds-admin-kpay-combo__native {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    opacity: 0 !important;
    pointer-events: none !important;
    clip: rect(0, 0, 0, 0) !important;
}
body.pds-admin .pds-admin-kpay-confirm-select .pds-admin-kpay-combo__toggle {
    width: 100%;
    min-height: 54px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
    padding: 0.55rem 0.85rem;
    border: 1.5px solid #fdba74;
    border-radius: 14px;
    background: #fff;
    color: #1c1917;
    text-align: left;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(154, 52, 18, 0.04);
    transition: border-color .15s ease, box-shadow .15s ease;
}
body.pds-admin .pds-admin-kpay-confirm-select .pds-admin-kpay-combo.is-open .pds-admin-kpay-combo__toggle,
body.pds-admin .pds-admin-kpay-confirm-select .pds-admin-kpay-combo__toggle:focus {
    border-color: #fe6f07;
    box-shadow: 0 0 0 3px rgba(254, 111, 7, 0.12);
    outline: none;
}
body.pds-admin .pds-admin-kpay-confirm-select .pds-admin-kpay-combo__toggle > i {
    color: #fe6f07;
    font-size: 0.72rem;
    flex: 0 0 auto;
}
body.pds-admin .pds-admin-kpay-confirm-select .pds-admin-kpay-combo__label {
    flex: 1 1 auto;
    min-width: 0;
}
body.pds-admin .pds-admin-kpay-confirm-select .pds-admin-kpay-combo__label.is-placeholder {
    color: #a8a29e;
    font-size: 0.88rem;
    font-weight: 600;
}
body.pds-admin .pds-admin-kpay-confirm-select .pds-admin-kpay-combo__panel {
    display: none;
    flex-direction: column;
    position: absolute;
    left: 0;
    right: 0;
    top: calc(100% + 6px);
    max-height: 280px;
    overflow: hidden;
    border: 1px solid #fed7aa;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 18px 40px rgba(28, 25, 23, 0.16);
    z-index: 30;
}
body.pds-admin .pds-admin-kpay-confirm-select .pds-admin-kpay-combo.is-open .pds-admin-kpay-combo__panel {
    display: flex !important;
}
body.pds-admin .pds-admin-kpay-combo__search-wrap {
    padding: 0.75rem 0.75rem 0.45rem;
    background: #fffaf5;
    border-bottom: 1px solid #ffe0c2;
}
body.pds-admin .pds-admin-kpay-combo__search {
    width: 100%;
    height: 42px;
    padding: 0 0.85rem 0 2.35rem;
    border: 1.5px solid #fdba74;
    border-radius: 12px;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23fe6f07' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.3-4.3'/%3E%3C/svg%3E") no-repeat 0.85rem center;
    font-size: 0.88rem;
    color: #1c1917;
    outline: none;
    box-sizing: border-box;
}
body.pds-admin .pds-admin-kpay-combo__search:focus {
    border-color: #fe6f07;
    box-shadow: 0 0 0 3px rgba(254, 111, 7, 0.12);
}
body.pds-admin .pds-admin-kpay-combo__list {
    overflow-y: auto;
    max-height: 220px;
    padding: 0.45rem;
}
body.pds-admin .pds-admin-kpay-combo__option {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    width: 100%;
    border: 0;
    border-radius: 12px;
    background: transparent;
    text-align: left;
    padding: 0.6rem 0.65rem;
    margin-bottom: 0.25rem;
    cursor: pointer;
    transition: background .12s ease;
}
body.pds-admin .pds-admin-kpay-combo__option:hover {
    background: #fff7ed;
}
body.pds-admin .pds-admin-kpay-combo__option.is-selected {
    background: #ffedd5;
}
body.pds-admin .pds-admin-kpay-combo__option .pds-admin-kpay-option__avatar {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, #ffedd5, #fed7aa);
    color: #9a3412;
    font-size: 0.84rem;
    font-weight: 800;
}
body.pds-admin .pds-admin-kpay-combo__option .pds-admin-kpay-option__main {
    display: flex;
    flex-direction: column;
    gap: 0.12rem;
    min-width: 0;
    flex: 1 1 auto;
}
body.pds-admin .pds-admin-kpay-combo__option .pds-admin-kpay-option__main strong {
    font-size: 0.9rem;
    font-weight: 800;
    color: #1c1917;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
body.pds-admin .pds-admin-kpay-combo__option .pds-admin-kpay-option__main span {
    font-size: 0.76rem;
    color: #78716c;
    font-variant-numeric: tabular-nums;
}
body.pds-admin .pds-admin-kpay-combo__option .pds-admin-kpay-option__bal {
    flex: 0 0 auto;
    font-size: 0.78rem;
    font-weight: 800;
    color: #c2410c;
    background: #fff;
    border: 1px solid #fed7aa;
    border-radius: 999px;
    padding: 0.3rem 0.6rem;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
body.pds-admin .pds-admin-kpay-combo__empty {
    padding: 1rem 0.75rem;
    text-align: center;
    color: #a8a29e;
    font-size: 0.84rem;
    font-weight: 600;
}
body.pds-admin .pds-admin-kpay-option--selected {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    min-width: 0;
    width: 100%;
}
body.pds-admin .pds-admin-kpay-option--selected .pds-admin-kpay-option__avatar {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, #ffedd5, #fed7aa);
    color: #9a3412;
    font-size: 0.8rem;
    font-weight: 800;
}
body.pds-admin .pds-admin-kpay-option--selected .pds-admin-kpay-option__copy {
    display: flex;
    flex-direction: column;
    gap: 0.08rem;
    min-width: 0;
    flex: 1 1 auto;
}
body.pds-admin .pds-admin-kpay-option--selected strong {
    font-size: 0.9rem;
    font-weight: 800;
    color: #1c1917;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
body.pds-admin .pds-admin-kpay-option--selected span {
    font-size: 0.76rem;
    color: #78716c;
}
body.pds-admin .pds-admin-kpay-option--selected em {
    font-style: normal;
    font-size: 0.78rem;
    font-weight: 800;
    color: #c2410c;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    border-radius: 999px;
    padding: 0.28rem 0.55rem;
    white-space: nowrap;
}
body.pds-admin .pds-money-transfer-os__kpay--admin {
    display: block;
    margin-top: 0.2rem;
    color: #9a3412;
}
@media (max-width: 520px) {
    body.pds-admin .pds-admin-kpay-confirm-grid {
        grid-template-columns: 1fr;
    }
    body.pds-admin .pds-admin-kpay-panel__list {
        grid-template-columns: 1fr;
    }
    body.pds-admin .pds-admin-kpay-panel__actions {
        width: 100%;
    }
    body.pds-admin #osAdminKpayClearBtn.pds-admin-kpay-clear-btn,
    body.pds-admin #osAdminKpayCreateBtn.pds-admin-kpay-create-btn {
        flex: 1 1 auto;
        justify-content: center;
    }
}
</style>
<div id="adminLiveRoot" data-live-page="auto">
<div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-to-assign-page pds-dispatch-os-list-page">
        <div class="pds-dispatch-to-assign-screen pds-rider-screen">
            <div class="pds-rider-hero">
                <div class="pds-rider-hero__copy">
                    <div class="pds-rider-hero__eyebrow">
                        <i class="fas fa-store" aria-hidden="true"></i>
                        <span>{{ __('message.order') }}</span>
                    </div>
                    <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                </div>
                <div class="pds-rider-hero__stat">
                    <span class="pds-rider-hero__stat-value">{{ ($payToOsRows->count() ?? 0) + ($receiveFromOsRows->count() ?? 0) + ($kyoShinPayRows->count() ?? 0) }}</span>
                    <span class="pds-rider-hero__stat-label">{{ __('message.online_shopping') }}</span>
                </div>
            </div>

            @include('partials._branch-tabs', [
                'branchTabs' => $branchTabs ?? collect(),
                'selectedBranchId' => $selectedBranchId ?? null,
                'branchTabCounts' => $branchTabCounts ?? [],
                'allCount' => $allBranchCount ?? null,
                'includeAll' => false,
                'routeName' => 'order.dispatch.os-list',
                'routeQuery' => [
                    'from_date' => $filterFromDate,
                    'to_date' => $filterToDate,
                    'os_id' => $osFilter,
                ],
            ])

            <form method="GET" action="{{ route('order.dispatch.os-list') }}" class="pds-rider-toolbar" id="osListFilterForm">
                <input type="hidden" name="branch_id" value="{{ $branchFilter ?? 'all' }}">
                <div class="pds-rider-toolbar__fields">
                    <div class="pds-dispatch-field pds-dispatch-field-sm pds-rider-toolbar__grow">
                        <label for="os_list_os">{{ __('message.online_shopping') }}</label>
                        <select name="os_id" id="os_list_os" class="pds-dispatch-input pds-dispatch-select">
                            <option value="all" @selected($osFilter === 'all')>{{ __('message.all') }}</option>
                            <option value="0" @selected((string) $osFilter === '0')>{{ __('message.no_os') }}</option>
                            @foreach($osOptions as $option)
                                @php $osPhone = normalizeContactNumber($option->contact_number ?? ''); @endphp
                                <option value="{{ $option->id }}"
                                        data-phone="{{ $osPhone }}"
                                        @selected((string) $osFilter === (string) $option->id)>{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="os_list_from_date">{{ __('message.from') }}</label>
                        <input type="text" name="from_date" id="os_list_from_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $filterFromDate }}" autocomplete="off">
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="os_list_to_date">{{ __('message.to') }}</label>
                        <input type="text" name="to_date" id="os_list_to_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $filterToDate }}" autocomplete="off">
                    </div>
                </div>
                <div class="pds-rider-toolbar__aside">
                    <button type="button" class="pds-rider-day-btn" id="osListPrevDay" title="{{ __('message.previous') ?? 'Previous' }}">
                        <i class="fas fa-chevron-left" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="pds-rider-day-btn" id="osListNextDay" title="{{ __('message.next') ?? 'Next' }}">
                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </button>
                    <button type="submit" class="pds-rider-check-btn">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <span>{{ __('message.check') }}</span>
                    </button>
                </div>
            </form>

            @php
                $payToOsRows = $payToOsRows ?? collect();
                $receiveFromOsRows = $receiveFromOsRows ?? collect();
                $kyoShinPayRows = $kyoShinPayRows ?? collect();
                $adminKpayAccounts = $adminKpayAccounts ?? collect();
                $adminKpayAccountsJson = $adminKpayAccounts->map(static function ($a) {
                    return [
                        'id' => $a->id,
                        'kpay_name' => $a->kpay_name,
                        'kpay_phone' => $a->kpay_phone,
                        'amount' => (float) $a->amount,
                        'balance' => (float) $a->balance,
                        'label' => $a->label(),
                    ];
                })->values()->all();
                $requestedTab = request('tab');
                if (in_array($requestedTab, ['pay', 'receive', 'kyo_shin'], true)) {
                    $defaultTab = $requestedTab;
                } elseif ($payToOsRows->isNotEmpty()) {
                    $defaultTab = 'pay';
                } elseif ($kyoShinPayRows->isNotEmpty()) {
                    $defaultTab = 'kyo_shin';
                } else {
                    $defaultTab = 'receive';
                }
            @endphp

            <div class="pds-admin-kpay-panel" id="osAdminKpayPanel">
                <div class="pds-admin-kpay-panel__head">
                    <div class="pds-admin-kpay-panel__title">
                        <span class="pds-admin-kpay-panel__icon" aria-hidden="true"><i class="fas fa-wallet"></i></span>
                        <div class="pds-admin-kpay-panel__copy">
                            <strong>{{ __('message.admin_kpay_accounts') }}</strong>
                            <span>
                                <i class="far fa-calendar-alt" aria-hidden="true"></i>
                                {{ $filterFromDate }}@if($filterFromDate !== $filterToDate) — {{ $filterToDate }}@endif
                            </span>
                        </div>
                    </div>
                    <div class="pds-admin-kpay-panel__actions">
                        <button type="button"
                                class="pds-admin-kpay-clear-btn"
                                id="osAdminKpayClearBtn"
                                @if($adminKpayAccounts->isEmpty()) hidden @endif>
                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                            <span>{{ __('message.admin_kpay_clear_all') }}</span>
                        </button>
                        <button type="button" class="pds-rider-check-btn pds-admin-kpay-create-btn" id="osAdminKpayCreateBtn">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            <span>{{ __('message.admin_kpay_account_create') }}</span>
                        </button>
                    </div>
                </div>
                <div class="pds-admin-kpay-panel__list" id="osAdminKpayList">
                    @forelse($adminKpayAccounts as $account)
                        @php
                            $chipInitial = mb_strtoupper(mb_substr(trim((string) $account->kpay_name) ?: 'A', 0, 1));
                        @endphp
                        <div class="pds-admin-kpay-chip"
                             data-account-id="{{ $account->id }}"
                             data-balance="{{ (float) $account->balance }}"
                             data-amount="{{ (float) $account->amount }}"
                             data-kpay-name="{{ $account->kpay_name }}"
                             data-kpay-phone="{{ $account->kpay_phone }}">
                            <div class="pds-admin-kpay-chip__top">
                                <span class="pds-admin-kpay-chip__avatar" aria-hidden="true">{{ $chipInitial }}</span>
                                <div class="pds-admin-kpay-chip__main">
                                    <strong>{{ $account->kpay_name }}</strong>
                                    <span><i class="fas fa-mobile-alt" aria-hidden="true"></i>{{ $account->kpay_phone }}</span>
                                </div>
                            </div>
                            <div class="pds-admin-kpay-chip__meta">
                                <div class="pds-admin-kpay-chip__balance">
                                    <small>{{ __('message.admin_kpay_balance') }}</small>
                                    <em>{{ number_format((float) $account->balance) }}</em>
                                </div>
                                <div class="pds-admin-kpay-chip__actions">
                                    <button type="button"
                                            class="pds-admin-kpay-chip__info js-admin-kpay-info"
                                            data-id="{{ $account->id }}"
                                            title="{{ __('message.admin_kpay_history_title') }}">
                                        <i class="fas fa-info" aria-hidden="true"></i>
                                    </button>
                                    <button type="button"
                                            class="pds-admin-kpay-chip__edit js-admin-kpay-edit"
                                            data-id="{{ $account->id }}"
                                            title="{{ __('message.edit') }}">
                                        <i class="fas fa-pen" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" class="pds-admin-kpay-chip__del js-admin-kpay-del" data-id="{{ $account->id }}" title="{{ __('message.delete') }}">
                                        <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="pds-admin-kpay-panel__empty" id="osAdminKpayEmpty">
                            <i class="fas fa-university" aria-hidden="true"></i>
                            <span>{{ __('message.admin_kpay_empty_day') }}</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="pds-rider-body">
                    <div class="pds-os-settlement-tabs" role="tablist" aria-label="{{ __('message.os_list') }}">
                        <button
                            type="button"
                            class="pds-os-settlement-tab {{ $defaultTab === 'pay' ? 'is-active' : '' }}"
                            data-tab="pay"
                            role="tab"
                            aria-selected="{{ $defaultTab === 'pay' ? 'true' : 'false' }}"
                        >
                            <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            <span>{{ __('message.os_settlement_pay_to_os') }}</span>
                            <em class="js-os-tab-count" data-tab-count="pay">{{ $payToOsRows->count() }}</em>
                        </button>
                        <button
                            type="button"
                            class="pds-os-settlement-tab {{ $defaultTab === 'receive' ? 'is-active' : '' }}"
                            data-tab="receive"
                            role="tab"
                            aria-selected="{{ $defaultTab === 'receive' ? 'true' : 'false' }}"
                        >
                            <i class="fas fa-hand-holding-usd" aria-hidden="true"></i>
                            <span>{{ __('message.os_settlement_receive_from_os') }}</span>
                            <em class="js-os-tab-count" data-tab-count="receive">{{ $receiveFromOsRows->count() }}</em>
                        </button>
                        <button
                            type="button"
                            class="pds-os-settlement-tab {{ $defaultTab === 'kyo_shin' ? 'is-active' : '' }}"
                            data-tab="kyo_shin"
                            role="tab"
                            aria-selected="{{ $defaultTab === 'kyo_shin' ? 'true' : 'false' }}"
                        >
                            <i class="fas fa-wallet" aria-hidden="true"></i>
                            <span>{{ __('message.os_settlement_kyo_shin_pay') }}</span>
                            <em class="js-os-tab-count" data-tab-count="kyo_shin">{{ $kyoShinPayRows->count() }}</em>
                        </button>
                    </div>

                    <div
                        class="pds-os-settlement-panel pds-os-settlement-section {{ $defaultTab === 'pay' ? 'is-active' : '' }}"
                        data-panel="pay"
                        data-section="pay"
                        role="tabpanel"
                    >
                        <div class="pds-os-settlement-bulk-bar" data-section="pay" @if($payToOsRows->isEmpty()) style="display:none" @endif>
                            <div class="pds-os-settlement-bulk-bar__actions">
                                <button type="button" class="pds-os-finish-all-btn" id="osSettlementFinishAllPay" disabled>
                                    <i class="fas fa-check-double" aria-hidden="true"></i>
                                    <span>{{ __('message.finished_all') }}</span>
                                </button>
                            </div>
                        </div>
                        <div class="pds-rider-table-shell pds-rider-table-shell--scroll pds-os-settlement-shell">
                            @if($payToOsRows->isEmpty())
                                <div class="pds-os-settlement-section__empty">
                                    <p>{{ __('message.os_settlement_pay_empty') }}</p>
                                </div>
                            @else
                                <table class="table pds-rider-list-table pds-os-settlement-table" id="osSettlementPayTable">
                                    <thead>
                                        <tr>
                                            <th class="pds-rider-col-no">{{ __('message.no') }}</th>
                                            <th class="pds-os-settlement-col-os">{{ __('message.os_name') }}</th>
                                            <th class="pds-os-settlement-col-amount text-right">{{ __('message.amount') }}</th>
                                            <th class="pds-os-settlement-col-kpay">{{ __('message.kpay_name') }}</th>
                                            <th class="pds-os-settlement-col-kpay">{{ __('message.kpay_no') }}</th>
                                            <th class="pds-os-settlement-col-slip">{{ __('message.kpay_slip') }}</th>
                                            <th class="pds-os-settlement-col-method">{{ __('message.payment_method') }}</th>
                                            <th class="pds-os-settlement-col-preview">{{ __('message.show_slip_completed') }}</th>
                                            <th class="pds-os-settlement-col-action">{{ __('message.action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($payToOsRows as $index => $row)
                                            @include('order.partials._dispatch-os-settlement-row', ['row' => $row, 'index' => $index, 'section' => 'pay'])
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="pds-os-settlement-total-row">
                                            <td class="pds-rider-col-no"></td>
                                            <td class="pds-os-settlement-col-os">
                                                <span class="pds-os-settlement-total-items">
                                                    {{ __('message.total') }}:
                                                    <strong>{{ (int) $payToOsRows->sum('item_count') }}</strong>
                                                    {{ __('message.items') }}
                                                </span>
                                            </td>
                                            <td class="pds-os-settlement-col-amount"></td>
                                            <td class="pds-os-settlement-col-kpay"></td>
                                            <td class="pds-os-settlement-col-kpay"></td>
                                            <td class="pds-os-settlement-col-slip"></td>
                                            <td class="pds-os-settlement-col-method"></td>
                                            <td class="pds-os-settlement-col-preview"></td>
                                            <td class="pds-os-settlement-col-action"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            @endif
                        </div>
                    </div>

                    <div
                        class="pds-os-settlement-panel pds-os-settlement-section {{ $defaultTab === 'receive' ? 'is-active' : '' }}"
                        data-panel="receive"
                        data-section="receive"
                        role="tabpanel"
                    >
                        <div class="pds-os-receive-bulk-upload" id="osReceiveBulkUpload" @if($receiveFromOsRows->isEmpty()) style="display:none" @endif>
                            <div class="pds-os-receive-bulk-upload__preview" id="osReceiveBulkPreview" hidden>
                                <img src="" alt="" id="osReceiveBulkPreviewImg">
                            </div>
                            <div class="pds-os-receive-bulk-upload__copy">
                                <strong>{{ __('message.os_settlement_bulk_proof_title') }}</strong>
                                <span>{{ __('message.os_settlement_bulk_proof_hint') }}</span>
                            </div>
                            <label class="pds-os-receive-bulk-upload__btn" for="osReceiveBulkProofInput">
                                <i class="fas fa-qrcode" aria-hidden="true"></i>
                                <span>{{ __('message.upload_os_settlement_qr') }}</span>
                                <input
                                    type="file"
                                    id="osReceiveBulkProofInput"
                                    accept="image/*"
                                    hidden
                                >
                            </label>
                            <div class="pds-os-receive-bulk-upload__status" id="osReceiveBulkStatus" hidden></div>
                        </div>

                        <div class="pds-os-settlement-bulk-bar pds-os-settlement-bulk-bar--receive" data-section="receive" @if($receiveFromOsRows->isEmpty()) style="display:none" @endif>
                            <div class="pds-os-settlement-bulk-bar__actions">
                                <button type="button" class="pds-os-finish-all-btn" id="osSettlementFinishAllReceive" disabled>
                                    <i class="fas fa-check-double" aria-hidden="true"></i>
                                    <span>{{ __('message.finished_all') }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="pds-rider-table-shell pds-rider-table-shell--scroll pds-os-settlement-shell">
                            @if($receiveFromOsRows->isEmpty())
                                <div class="pds-os-settlement-section__empty pds-os-settlement-section__empty--receive">
                                    <p>{{ __('message.os_settlement_receive_empty') }}</p>
                                </div>
                            @else
                                <table class="table pds-rider-list-table pds-os-settlement-table" id="osSettlementReceiveTable">
                                    <thead>
                                        <tr>
                                            <th class="pds-rider-col-no">{{ __('message.no') }}</th>
                                            <th class="pds-os-settlement-col-os">{{ __('message.os_name') }}</th>
                                            <th class="pds-os-settlement-col-amount text-right">{{ __('message.amount') }}</th>
                                            <th class="pds-os-settlement-col-slip">{{ __('message.os_settlement_qr') }}</th>
                                            <th class="pds-os-settlement-col-preview">{{ __('message.show_slip_completed') }}</th>
                                            <th class="pds-os-settlement-col-action">{{ __('message.action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($receiveFromOsRows as $index => $row)
                                            @include('order.partials._dispatch-os-settlement-row', ['row' => $row, 'index' => $index, 'section' => 'receive'])
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="pds-os-settlement-total-row">
                                            <td class="pds-rider-col-no"></td>
                                            <td class="pds-os-settlement-col-os">
                                                <span class="pds-os-settlement-total-items">
                                                    {{ __('message.total') }}:
                                                    <strong>{{ (int) $receiveFromOsRows->sum('item_count') }}</strong>
                                                    {{ __('message.items') }}
                                                </span>
                                            </td>
                                            <td class="pds-os-settlement-col-amount"></td>
                                            <td class="pds-os-settlement-col-slip"></td>
                                            <td class="pds-os-settlement-col-preview"></td>
                                            <td class="pds-os-settlement-col-action"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            @endif
                        </div>
                    </div>

                    <div
                        class="pds-os-settlement-panel pds-os-settlement-section {{ $defaultTab === 'kyo_shin' ? 'is-active' : '' }}"
                        data-panel="kyo_shin"
                        data-section="kyo_shin"
                        role="tabpanel"
                    >
                        <div class="pds-os-settlement-bulk-bar" data-section="kyo_shin" @if($kyoShinPayRows->isEmpty()) style="display:none" @endif>
                            <div class="pds-os-settlement-bulk-bar__actions">
                                <button type="button" class="pds-os-finish-all-btn" id="osSettlementFinishAllKyoShin" @disabled($kyoShinPayRows->isEmpty())>
                                    <i class="fas fa-check-double" aria-hidden="true"></i>
                                    <span>{{ __('message.finished_all') }}</span>
                                </button>
                            </div>
                        </div>
                        <div class="pds-rider-table-shell pds-rider-table-shell--scroll pds-os-settlement-shell">
                            @if($kyoShinPayRows->isEmpty())
                                <div class="pds-os-settlement-section__empty pds-os-settlement-section__empty--kyo-shin">
                                    <p>{{ __('message.os_settlement_kyo_shin_empty') }}</p>
                                </div>
                            @else
                                <table class="table pds-rider-list-table pds-os-settlement-table" id="osSettlementKyoShinTable">
                                    <thead>
                                        <tr>
                                            <th class="pds-rider-col-no">{{ __('message.no') }}</th>
                                            <th class="pds-os-settlement-col-os">{{ __('message.os_name') }}</th>
                                            <th class="pds-os-settlement-col-amount text-right">{{ __('message.kyo_shin_total_advanced_paid') }}</th>
                                            <th class="text-right">{{ __('message.kyo_shin_remain') }}</th>
                                            <th class="text-right">{{ __('message.kyo_shin_advanced_paid') }}</th>
                                            <th class="text-center">{{ __('message.kyo_shin_total_parcels') }}</th>
                                            <th class="pds-os-settlement-col-action">{{ __('message.action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($kyoShinPayRows as $index => $row)
                                            @include('order.partials._dispatch-os-kyo-shin-settlement-row', ['row' => $row, 'index' => $index])
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
            </div>
        </div>
    </div>

    <div id="osSettlementSlipModalHost"></div>

    <div class="modal fade pds-admin-kpay-modal" id="osAdminKpayCreateModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus" aria-hidden="true" id="osAdminKpayFormIcon"></i>
                        <span id="osAdminKpayFormTitle">{{ __('message.admin_kpay_account_create') }}</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="osAdminKpayEditId" value="">
                    <div class="form-group">
                        <label for="osAdminKpayNameInput">{{ __('message.kpay_name') }}</label>
                        <input type="text" class="form-control" id="osAdminKpayNameInput" autocomplete="off" placeholder="{{ __('message.kpay_name') }}">
                    </div>
                    <div class="form-group">
                        <label for="osAdminKpayPhoneInput">{{ __('message.kpay_phone_number') }}</label>
                        <input type="text" class="form-control" id="osAdminKpayPhoneInput" autocomplete="off" placeholder="09xxxxxxxxx">
                    </div>
                    <div class="form-group mb-0">
                        <label for="osAdminKpayAmountInput">{{ __('message.amount') }}</label>
                        <input type="number" min="1" step="1" class="form-control" id="osAdminKpayAmountInput" autocomplete="off" placeholder="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('message.cancel') }}</button>
                    <button type="button" class="btn btn-primary pds-admin-kpay-modal__primary" id="osAdminKpayCreateSaveBtn">
                        <i class="fas fa-check" aria-hidden="true"></i> {{ __('message.save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade pds-admin-kpay-modal pds-admin-kpay-history-modal" id="osAdminKpayHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-receipt" aria-hidden="true"></i>
                        <span id="osAdminKpayHistoryTitle">{{ __('message.admin_kpay_history_title') }}</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="pds-admin-kpay-history-account" id="osAdminKpayHistoryAccount" hidden></div>
                    <div class="pds-admin-kpay-history-summary" id="osAdminKpayHistorySummary" hidden></div>
                    <div id="osAdminKpayHistoryBody">
                        <div class="pds-admin-kpay-history-empty">
                            <i class="fas fa-inbox" aria-hidden="true"></i>
                            <span>{{ __('message.admin_kpay_history_empty') }}</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn pds-admin-kpay-history-close" data-dismiss="modal">{{ __('message.close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade pds-admin-kpay-modal pds-admin-kpay-action-modal" id="osAdminKpayActionModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation pds-admin-kpay-action-hero" aria-hidden="true" id="osAdminKpayActionIcon"></i>
                        <span id="osAdminKpayActionTitle">{{ __('message.admin_kpay_clear_all_title') }}</span>
                    </h5>
                </div>
                <div class="modal-body">
                    <p class="pds-admin-kpay-action-copy" id="osAdminKpayActionMessage">{{ __('message.admin_kpay_clear_all_confirm') }}</p>
                    <div class="pds-admin-kpay-action-card" id="osAdminKpayActionCard" hidden>
                        <span class="pds-admin-kpay-action-card__avatar" id="osAdminKpayActionAvatar" aria-hidden="true">A</span>
                        <div class="pds-admin-kpay-action-card__main">
                            <strong id="osAdminKpayActionName">—</strong>
                            <span id="osAdminKpayActionPhone">—</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" id="osAdminKpayActionCancel">{{ __('message.cancel') }}</button>
                    <button type="button" class="btn pds-admin-kpay-modal__danger" id="osAdminKpayActionConfirmBtn">
                        <i class="fas fa-trash-alt" aria-hidden="true"></i>
                        <span id="osAdminKpayActionConfirmLabel">{{ __('message.admin_kpay_confirm_delete') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- No .fade: production SnackBar-less + fade can leave the confirm dialog at opacity 0. --}}
    <div class="modal pds-admin-kpay-modal" id="osAdminKpayConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-paper-plane" aria-hidden="true"></i>{{ __('message.admin_kpay_confirm_title') }}
                        <em class="pds-admin-kpay-queue-progress" id="osAdminKpayQueueProgress" hidden></em>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="pds-admin-kpay-confirm-amount">
                        <span>{{ __('message.transfer_amount') }}</span>
                        <strong id="osConfirmAmount">—</strong>
                    </div>
                    <div class="pds-admin-kpay-confirm-grid">
                        <div class="pds-admin-kpay-confirm-grid__row">
                            <span>{{ __('message.os_name') }}</span>
                            <strong id="osConfirmOsName">—</strong>
                        </div>
                        <div class="pds-admin-kpay-confirm-grid__row">
                            <span>{{ __('message.contact_number') }}</span>
                            <strong id="osConfirmOsPhone">—</strong>
                        </div>
                        <div class="pds-admin-kpay-confirm-grid__row pds-admin-kpay-confirm-grid__row--full">
                            <span>{{ __('message.os_address') }}</span>
                            <strong id="osConfirmOsAddress">—</strong>
                        </div>
                        <div class="pds-admin-kpay-confirm-grid__row">
                            <span>{{ __('message.kpay_name') }}</span>
                            <strong id="osConfirmKpayName">—</strong>
                        </div>
                        <div class="pds-admin-kpay-confirm-grid__row">
                            <span>{{ __('message.kpay_phone_number') }}</span>
                            <strong id="osConfirmKpayPhone">—</strong>
                        </div>
                    </div>
                    <div class="form-group mb-0 pds-admin-kpay-confirm-select">
                        <label for="osAdminKpayAccountSelect">
                            <i class="fas fa-wallet" aria-hidden="true"></i>
                            {{ __('message.admin_kpay_select_account') }}
                        </label>
                        <div class="pds-admin-kpay-combo" id="osAdminKpayCombo">
                            <select id="osAdminKpayAccountSelect" class="pds-admin-kpay-combo__native" tabindex="-1" aria-hidden="true"></select>
                            <button type="button" class="pds-admin-kpay-combo__toggle" id="osAdminKpayComboToggle" aria-haspopup="listbox" aria-expanded="false">
                                <span class="pds-admin-kpay-combo__label is-placeholder" id="osAdminKpayComboLabel">{{ __('message.admin_kpay_select_account') }}</span>
                                <i class="fas fa-chevron-down" aria-hidden="true"></i>
                            </button>
                            <div class="pds-admin-kpay-combo__panel" id="osAdminKpayComboPanel">
                                <div class="pds-admin-kpay-combo__search-wrap">
                                    <input type="text" class="pds-admin-kpay-combo__search" id="osAdminKpayComboSearch" placeholder="{{ __('message.search') }}…" autocomplete="off">
                                </div>
                                <div class="pds-admin-kpay-combo__list" id="osAdminKpayComboList" role="listbox"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" id="osAdminKpayConfirmCancel">{{ __('message.cancel') }}</button>
                    <button type="button" class="btn btn-primary pds-admin-kpay-modal__primary" id="osAdminKpayConfirmBtn">
                        <i class="fas fa-paper-plane" aria-hidden="true"></i> {{ __('message.admin_kpay_confirm_transfer') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


    @section('bottom_script')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="{{ asset('js/dispatch-os-fields.js') }}?v=10"></script>
        <script>
            $(document).ready(function () {
                if (typeof window.initOnlineShopFilterSelect === 'function') {
                    window.initOnlineShopFilterSelect('#os_list_os', {
                        placeholder: @json(__('message.select_name', ['select' => __('message.os_name')])),
                        allowClear: false
                    });
                }

                var filterFrom = @json($filterFromDate);
                var filterTo = @json($filterToDate);
                var filterBranchId = @json($branchFilter ?? 'all');

                // Soft-replace / SPA can leave closure dates stale while the table matches the URL.
                function currentOsListFilters() {
                    var params = new URLSearchParams(window.location.search || '');
                    var fromInput = (document.getElementById('os_list_from_date') || {}).value;
                    var toInput = (document.getElementById('os_list_to_date') || {}).value;
                    var from = String(params.get('from_date') || fromInput || filterFrom || '').trim();
                    var to = String(params.get('to_date') || toInput || filterTo || '').trim();
                    var branchRaw = params.get('branch_id');
                    if (branchRaw == null || String(branchRaw).trim() === '') {
                        branchRaw = filterBranchId;
                    }
                    return {
                        from_date: from,
                        to_date: to,
                        branch_id: branchRaw,
                    };
                }
                var uploadUrlTemplate = @json(url('dispatch-os-list/__OS__/upload-kpay-slip'));
                var removeUrlTemplate = @json(url('dispatch-os-list/__OS__/remove-kpay-slip'));
                var finishUrlTemplate = @json(url('dispatch-os-list/__OS__/finish'));
                var slipPreviewUrlTemplate = @json(url('dispatch-os-list/__OS__/slip-preview'));
                var finishAllUrl = @json(route('order.dispatch.os-finish-all'));
                var adminKpayStoreUrl = @json(route('order.dispatch.os-admin-kpay.store'));
                var adminKpayClearUrl = @json(route('order.dispatch.os-admin-kpay.clear'));
                var adminKpayDestroyUrlTemplate = @json(url('dispatch-os-list/admin-kpay-accounts/__ID__/delete'));
                var adminKpayUpdateUrlTemplate = @json(url('dispatch-os-list/admin-kpay-accounts/__ID__'));
                var adminKpayHistoryUrlTemplate = @json(url('dispatch-os-list/admin-kpay-accounts/__ID__/history'));
                var adminKpayClearTitle = @json(__('message.admin_kpay_clear_all_title'));
                var adminKpayClearConfirmMsg = @json(__('message.admin_kpay_clear_all_confirm'));
                var adminKpayDeleteTitle = @json(__('message.admin_kpay_account_delete_title'));
                var adminKpayDeleteConfirmMsg = @json(__('message.admin_kpay_account_delete_confirm'));
                var adminKpayConfirmDeleteLabel = @json(__('message.admin_kpay_confirm_delete'));
                var adminKpayConfirmActionLabel = @json(__('message.admin_kpay_confirm_action'));
                var adminKpayCreateTitle = @json(__('message.admin_kpay_account_create'));
                var adminKpayEditTitle = @json(__('message.admin_kpay_account_edit'));
                var adminKpayEditLabel = @json(__('message.edit'));
                var adminKpayHistoryTitle = @json(__('message.admin_kpay_history_title'));
                var adminKpayHistoryEmpty = @json(__('message.admin_kpay_history_empty'));
                var adminKpayHistoryAdminLabel = @json(__('message.admin_kpay_history_admin'));
                var adminKpayHistoryOsLabel = @json(__('message.admin_kpay_history_os'));
                var adminKpayHistoryAmountLabel = @json(__('message.admin_kpay_history_amount'));
                var adminKpayHistoryDayTotalLabel = @json(__('message.admin_kpay_history_day_total'));
                var adminKpayBalanceLabel = @json(__('message.admin_kpay_balance'));
                var csrf = $('meta[name="csrf-token"]').attr('content');
                var deleteLabel = @json(__('message.delete'));
                var adminKpayAccounts = @json($adminKpayAccountsJson ?? []);
                var finishConfirmQueue = [];
                var finishConfirmBusy = false;
                var finishConfirmQueueTotal = 0;
                var finishConfirmQueueDone = 0;
                var finishConfirmQueueStop = false;
                var finishQueueProgressTpl = @json(__('message.admin_kpay_queue_progress'));
                var finishQueueCancelledMsg = @json(__('message.admin_kpay_queue_cancelled'));
                var finishQueueDoneMsg = @json(__('message.admin_kpay_queue_done'));

                if (typeof window.pdsBindDispatchDatepickers === 'function') {
                    window.pdsBindDispatchDatepickers();
                } else if (typeof flatpickr !== 'undefined') {
                    document.querySelectorAll('.dispatch-datepicker').forEach(function (el) {
                        if (el._flatpickr) return;
                        flatpickr(el, { dateFormat: 'd-m-Y', allowInput: true, disableMobile: true });
                    });
                }

                function parseDmy(value) {
                    var parts = String(value || '').split('-');
                    if (parts.length !== 3) return null;
                    var d = parseInt(parts[0], 10);
                    var m = parseInt(parts[1], 10) - 1;
                    var y = parseInt(parts[2], 10);
                    if (!y || m < 0 || !d) return null;
                    return new Date(y, m, d);
                }

                function formatDmy(date) {
                    var d = String(date.getDate()).padStart(2, '0');
                    var m = String(date.getMonth() + 1).padStart(2, '0');
                    var y = date.getFullYear();
                    return d + '-' + m + '-' + y;
                }

                function shiftDay(delta) {
                    var fromEl = document.getElementById('os_list_from_date');
                    var toEl = document.getElementById('os_list_to_date');
                    if (!fromEl || !toEl) return;
                    var from = parseDmy(fromEl.value);
                    var to = parseDmy(toEl.value);
                    if (!from || !to) return;
                    from.setDate(from.getDate() + delta);
                    to.setDate(to.getDate() + delta);
                    fromEl.value = formatDmy(from);
                    toEl.value = formatDmy(to);
                    $('#osListFilterForm').trigger('submit');
                }

                $('#osListPrevDay').on('click', function () { shiftDay(-1); });
                $('#osListNextDay').on('click', function () { shiftDay(1); });

                function normalizeSettlementSide(value) {
                    return value === 'receive' || value === 'kyo_shin' ? value : 'pay';
                }

                function switchOsSettlementTab(tab) {
                    tab = normalizeSettlementSide(tab);
                    $('.pds-os-settlement-tab').each(function () {
                        var isActive = $(this).attr('data-tab') === tab;
                        $(this).toggleClass('is-active', isActive).attr('aria-selected', isActive ? 'true' : 'false');
                    });
                    $('.pds-os-settlement-panel').each(function () {
                        $(this).toggleClass('is-active', $(this).attr('data-panel') === tab);
                    });
                }

                $(document).on('click', '.pds-os-settlement-tab', function () {
                    switchOsSettlementTab($(this).attr('data-tab'));
                    if (typeof window.pdsResyncFrozenTables === 'function') {
                        window.requestAnimationFrame(window.pdsResyncFrozenTables);
                    }
                });

                function notify(message, status) {
                    status = status || 'error';
                    if (typeof SnackBar === 'function') {
                        SnackBar({ message: message, status: status });
                        return;
                    }
                    // Production sometimes ships without SnackBar — never fail silently.
                    try {
                        var $t = $('#pdsOsNotifyToast');
                        if (!$t.length) {
                            $t = $('<div>', {
                                id: 'pdsOsNotifyToast',
                                class: 'pds-os-notify-toast',
                                role: 'status',
                                'aria-live': 'polite',
                            }).appendTo(document.body);
                        }
                        $t
                            .removeClass('is-error is-success is-warning is-visible')
                            .addClass(status === 'success' ? 'is-success' : (status === 'warning' ? 'is-warning' : 'is-error'))
                            .text(String(message || ''))
                            .addClass('is-visible');
                        clearTimeout(window.__pdsOsNotifyTimer);
                        window.__pdsOsNotifyTimer = setTimeout(function () {
                            $t.removeClass('is-visible');
                        }, 4200);
                    } catch (eNotify) {
                        try { console.warn('[os-settlement]', status, message); } catch (e2) {}
                    }
                }

                function parseMoneyValue(value) {
                    if (typeof value === 'number' && isFinite(value)) return value;
                    var s = String(value == null ? '' : value).replace(/,/g, '').replace(/[^\d.-]/g, '');
                    var n = Number(s);
                    return isFinite(n) ? n : 0;
                }

                function parseRowAmount($row) {
                    var raw = $row.attr('data-amount');
                    if (raw == null || String(raw).trim() === '') {
                        raw = $row.find('.pds-os-amount-pill').first().text();
                    }
                    return Math.abs(parseMoneyValue(raw));
                }

                function hydrateAdminKpayAccountsFromDom() {
                    if ((adminKpayAccounts || []).length) return;
                    var fromDom = [];
                    $('#osAdminKpayList .pds-admin-kpay-chip').each(function () {
                        var $c = $(this);
                        var id = parseInt($c.attr('data-account-id'), 10);
                        if (!id) return;
                        var name = $c.attr('data-kpay-name') || '';
                        var phone = $c.attr('data-kpay-phone') || '';
                        var balance = parseMoneyValue($c.attr('data-balance'));
                        fromDom.push({
                            id: id,
                            kpay_name: name,
                            kpay_phone: phone,
                            amount: parseMoneyValue($c.attr('data-amount')),
                            balance: balance,
                            label: name + ' · ' + phone + ' (' + formatMoney(balance) + ')',
                        });
                    });
                    if (fromDom.length) {
                        adminKpayAccounts = fromDom;
                    }
                }

                function selectedAdminKpayAccountId($scope) {
                    var $sel = $();
                    if ($scope && $scope.length) {
                        $sel = $scope.find('#osAdminKpayAccountSelect').first();
                        if (!$sel.length && $scope.is('select')) $sel = $scope.first();
                    }
                    if (!$sel.length) $sel = $('#osAdminKpayAccountSelect').first();
                    return parseInt($sel.val(), 10) || 0;
                }

                function setPayMethodUi($wrap, method) {
                    method = method === 'cash' ? 'cash' : 'kpay';
                    $wrap.attr('data-method', method);
                    $wrap.find('.pds-os-pay-method__btn').each(function () {
                        $(this).toggleClass('is-active', $(this).data('method') === method);
                    });
                }

                function rowHasProof($row) {
                    if (String($row.attr('data-has-kpay')) === '1') return true;
                    return $row.find('.pds-os-kpay-preview').length > 0;
                }

                function syncRowFinishState($row) {
                    if (String($row.attr('data-is-finished')) === '1') return;
                    var section = $row.attr('data-section');
                    var method = $row.attr('data-payment-method') === 'cash' ? 'cash' : 'kpay';
                    var hasProof = rowHasProof($row);
                    $row.attr('data-has-kpay', hasProof ? '1' : '0');
                    var $btn = $row.find('.pds-os-finish-btn');
                    // ကြိုရှင်း: Finish only. Pay / receive always need a proof image.
                    var canFinish = section === 'kyo_shin' || hasProof;
                    $row.toggleClass('is-cash-method', method === 'cash');
                    $btn.toggleClass('is-disabled', !canFinish).prop('disabled', !canFinish);
                    if (canFinish) {
                        $btn.removeAttr('disabled');
                    } else {
                        $btn.attr('disabled', 'disabled');
                    }
                }

                function updateFinishAllState() {
                    function canFinishRows($rows) {
                        if (! $rows.length) return false;
                        var ok = true;
                        $rows.each(function () {
                            var $row = $(this);
                            if (String($row.attr('data-section')) === 'kyo_shin') {
                                return;
                            }
                            if (!rowHasProof($row)) {
                                ok = false;
                                return false;
                            }
                        });
                        return ok;
                    }
                    $('#osSettlementFinishAllPay').prop(
                        'disabled',
                        !canFinishRows($('#osSettlementPayTable tbody tr.pds-os-settlement-row[data-is-finished="0"]'))
                    );
                    $('#osSettlementFinishAllReceive').prop(
                        'disabled',
                        !canFinishRows($('#osSettlementReceiveTable tbody tr.pds-os-settlement-row[data-is-finished="0"]'))
                    );
                    $('#osSettlementFinishAllKyoShin').prop(
                        'disabled',
                        $('#osSettlementKyoShinTable tbody tr.pds-os-settlement-row[data-is-finished="0"]').length === 0
                    );
                }

                function normalizeImageFiles(list) {
                    var out = [];
                    if (!list) return out;
                    Array.prototype.forEach.call(list, function (file) {
                        if (!file) return;
                        if (file.type && file.type.indexOf('image/') === 0) {
                            out.push(file);
                            return;
                        }
                        // Clipboard / some OS drops omit MIME — trust common image extensions.
                        var name = String(file.name || '').toLowerCase();
                        if (/\.(jpe?g|png|gif|webp|bmp|heic)$/.test(name)) {
                            out.push(file);
                        }
                    });
                    return out;
                }

                function dedupeImageFiles(files) {
                    var seen = {};
                    var out = [];
                    (files || []).forEach(function (file) {
                        if (!file) return;
                        var key = [file.type || '', file.size || 0, file.lastModified || 0, file.name || ''].join('|');
                        if (seen[key]) return;
                        seen[key] = true;
                        out.push(file);
                    });
                    return out;
                }

                // One Cmd/Ctrl+V must only upload once even if SPA re-bound paste handlers.
                var pdsOsPasteLockUntil = 0;
                function claimOsPasteLock() {
                    var now = Date.now();
                    if (now < pdsOsPasteLockUntil) return false;
                    pdsOsPasteLockUntil = now + 800;
                    return true;
                }

                function appendLocalProofPreviews($row, files) {
                    var $card = $row.find('.pds-os-kpay-upload-card');
                    if (!$card.length || !files || !files.length) return;
                    var $gallery = $card.find('.pds-os-kpay-gallery');
                    if (!$gallery.length) {
                        $gallery = $('<div class="pds-os-kpay-gallery"></div>');
                        $card.prepend($gallery);
                    }
                    $card.addClass('has-images');
                    files.forEach(function (file) {
                        var localUrl = URL.createObjectURL(file);
                        var $preview = $(
                            '<span class="pds-os-kpay-preview pds-os-kpay-preview--local">'
                            + '<span class="pds-os-kpay-thumb-link">'
                            + '<img src="" alt="Proof" class="pds-os-kpay-thumb is-loading"></span></span>'
                        );
                        var $img = $preview.find('img');
                        $img.on('load', function () {
                            $img.removeClass('is-loading').addClass('is-ready');
                        });
                        $img.on('error', function () {
                            $img.removeClass('is-loading');
                        });
                        $img.attr('src', localUrl);
                        $gallery.append($preview);
                        // Revoke after paint; server URLs replace gallery on success.
                        window.setTimeout(function () {
                            try { URL.revokeObjectURL(localUrl); } catch (err) {}
                        }, 60000);
                    });
                }

                function setRowKpayUrls($row, urls) {
                    urls = (urls || []).filter(function (u) { return !!u; });
                    $row.attr('data-has-kpay', urls.length ? '1' : '0');
                    var $card = $row.find('.pds-os-kpay-upload-card');
                    var alt = $row.attr('data-section') === 'receive' ? 'QR' : 'Proof Image';
                    var $gallery = $card.find('.pds-os-kpay-gallery');
                    if (!$gallery.length) {
                        $gallery = $('<div class="pds-os-kpay-gallery"></div>');
                        $card.prepend($gallery);
                    }
                    if (!urls.length) {
                        $card.removeClass('has-images');
                        $gallery.empty();
                    } else {
                        $card.addClass('has-images');
                        var html = '';
                        urls.forEach(function (url, index) {
                            html += '<span class="pds-os-kpay-preview" data-index="' + index + '">'
                                + '<a href="' + url + '" class="pds-os-kpay-thumb-link" title="' + alt + '">'
                                + '<img src="' + url + '" alt="' + alt + '" class="pds-os-kpay-thumb is-loading"></a>'
                                + '<button type="button" class="pds-os-kpay-remove" data-index="' + index + '"'
                                + ' title="' + deleteLabel + '" aria-label="' + deleteLabel + '">'
                                + '<i class="fas fa-times" aria-hidden="true"></i></button></span>';
                        });
                        $gallery.html(html);
                        $gallery.find('img.pds-os-kpay-thumb').each(function () {
                            var img = this;
                            function markReady() {
                                $(img).removeClass('is-loading').addClass('is-ready');
                            }
                            if (img.complete && img.naturalWidth) {
                                markReady();
                            } else {
                                $(img).one('load', markReady).one('error', function () {
                                    $(img).removeClass('is-loading');
                                });
                            }
                        });
                    }
                    if (!$card.find('.pds-os-kpay-upload-btn').length) {
                        $gallery.after('<span class="pds-os-kpay-upload-btn" aria-hidden="true"><i class="fas fa-plus"></i></span>');
                    }
                    syncRowFinishState($row);
                    updateFinishAllState();
                }

                function rowSettlementSide($row) {
                    return String($row.attr('data-section') || '') === 'receive' ? 'receive' : 'pay';
                }

                function removeProofForRow($row, index) {
                    return new Promise(function (resolve, reject) {
                        var osId = parseInt($row.data('os-id'), 10);
                        if ((!osId && osId !== 0) || index < 0) {
                            reject(new Error('missing os'));
                            return;
                        }
                        var filters = currentOsListFilters();
                        $.ajax({
                            url: removeUrlTemplate.replace('__OS__', osId),
                            type: 'POST',
                            data: {
                                _token: csrf,
                                from_date: filters.from_date,
                                to_date: filters.to_date,
                                index: index,
                                settlement_side: rowSettlementSide($row),
                            },
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                            success: function (res) {
                                var urls = res.kpay_slip_urls || (res.kpay_slip_url ? [res.kpay_slip_url] : []);
                                setRowKpayUrls($row, urls);
                                resolve(res);
                            },
                            error: function (xhr) {
                                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                    ? xhr.responseJSON.message
                                    : @json(__('message.something_went_wrong'));
                                reject(new Error(msg));
                            },
                        });
                    });
                }

                function uploadProofForRow($row, files) {
                    files = normalizeImageFiles(Array.isArray(files) ? files : [files]);
                    return new Promise(function (resolve, reject) {
                        var osId = parseInt($row.data('os-id'), 10);
                        if (!osId && osId !== 0) {
                            reject(new Error('missing os'));
                            return;
                        }
                        if (!files.length) {
                            reject(new Error(@json(__('message.os_settlement_kpay_slip_required'))));
                            return;
                        }
                        var filters = currentOsListFilters();
                        var formData = new FormData();
                        formData.append('_token', csrf);
                        formData.append('from_date', filters.from_date);
                        formData.append('to_date', filters.to_date);
                        formData.append('settlement_side', rowSettlementSide($row));
                        if (files.length === 1) {
                            formData.append('kpay_slip', files[0]);
                        } else {
                            files.forEach(function (file) {
                                formData.append('kpay_slips[]', file);
                            });
                        }
                        $.ajax({
                            url: uploadUrlTemplate.replace('__OS__', osId),
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                            success: function (res) {
                                var urls = res.kpay_slip_urls || (res.kpay_slip_url ? [res.kpay_slip_url] : []);
                                if (urls.length) {
                                    setRowKpayUrls($row, urls);
                                } else {
                                    // Upload OK but URL list empty — keep local previews and unlock Finish.
                                    $row.attr('data-has-kpay', $row.find('.pds-os-kpay-preview').length ? '1' : '0');
                                    syncRowFinishState($row);
                                    updateFinishAllState();
                                }
                                resolve(res);
                            },
                            error: function (xhr) {
                                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                    ? xhr.responseJSON.message
                                    : @json(__('message.something_went_wrong'));
                                reject(new Error(msg));
                            },
                        });
                    });
                }

                function bindProofDropZone($zone, onFiles) {
                    if (!$zone || !$zone.length) return;
                    $zone.on('dragenter dragover', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        $(this).addClass('is-dragover');
                    });
                    $zone.on('dragleave dragend', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        $(this).removeClass('is-dragover');
                    });
                    $zone.on('drop', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        $(this).removeClass('is-dragover');
                        var dt = e.originalEvent && e.originalEvent.dataTransfer;
                        var files = normalizeImageFiles(dt && dt.files);
                        if (files.length) onFiles(files, $(this));
                    });
                    // Bulk paste is handled by the document-level paste.pdsOsProof handler
                    // to avoid uploading the same clipboard image twice.
                    $zone.off('paste.pdsOsProof');
                }

                function syncTabCounts() {
                    var payCount = $('#osSettlementPayTable tbody tr.pds-os-settlement-row').length;
                    var receiveCount = $('#osSettlementReceiveTable tbody tr.pds-os-settlement-row').length;
                    var kyoShinCount = $('#osSettlementKyoShinTable tbody tr.pds-os-settlement-row').length;
                    $('.js-os-tab-count[data-tab-count="pay"]').text(payCount);
                    $('.js-os-tab-count[data-tab-count="receive"]').text(receiveCount);
                    $('.js-os-tab-count[data-tab-count="kyo_shin"]').text(kyoShinCount);
                    $('.pds-rider-hero__stat-value').text(payCount + receiveCount + kyoShinCount);
                }

                function renumberOsSettlementRows($table) {
                    var $dataRows = $table.find('tbody tr.pds-os-settlement-row');
                    $dataRows.each(function (i) {
                        $(this).find('td.pds-rider-col-no').first().text(i + 1);
                    });
                    var $section = $table.closest('.pds-os-settlement-section');
                    var sectionKey = String($section.attr('data-section') || '');
                    if ($dataRows.length === 0) {
                        if (sectionKey === 'pay') {
                            $section.find('.pds-os-settlement-bulk-bar[data-section="pay"]').hide();
                        }
                        if (sectionKey === 'receive') {
                            $section.find('#osReceiveBulkUpload, .pds-os-settlement-bulk-bar[data-section="receive"]').hide();
                        }
                        if (sectionKey === 'kyo_shin') {
                            $section.find('.pds-os-settlement-bulk-bar[data-section="kyo_shin"]').hide();
                        }
                        var emptyMsg = sectionKey === 'receive'
                            ? @json(__('message.os_settlement_receive_empty'))
                            : (sectionKey === 'kyo_shin'
                                ? @json(__('message.os_settlement_kyo_shin_empty'))
                                : @json(__('message.os_settlement_pay_empty')));
                        var emptyClass = sectionKey === 'receive'
                            ? 'pds-os-settlement-section__empty pds-os-settlement-section__empty--receive'
                            : (sectionKey === 'kyo_shin'
                                ? 'pds-os-settlement-section__empty pds-os-settlement-section__empty--kyo-shin'
                                : 'pds-os-settlement-section__empty');
                        var $shell = $table.closest('.pds-os-settlement-shell');
                        if ($shell.length) {
                            $shell.html('<div class="' + emptyClass + '"><p>' + emptyMsg + '</p></div>');
                        }
                    }
                    syncTabCounts();
                    if ($('.pds-os-settlement-row').length === 0) {
                        $('.pds-os-settlement-tabs').remove();
                        var emptyHtml = '<div class="pds-rider-empty">'
                            + '<div class="pds-rider-empty__icon"><i class="fas fa-store"></i></div>'
                            + '<p>' + @json(__('message.os_settlement_no_rows')) + '</p>'
                            + '</div>';
                        $('.pds-rider-body').html(emptyHtml);
                    }
                }

                function refreshKyoShinTotal() {
                    var total = 0;
                    $('#osSettlementKyoShinTable tbody tr.pds-os-settlement-row').each(function () {
                        total += parseFloat($(this).attr('data-amount')) || 0;
                    });
                    $('#osSettlementKyoShinTable tfoot .pds-os-amount-pill').text(
                        total.toLocaleString('en-US', { maximumFractionDigits: 0 })
                    );
                }

                function markRowFinished($row) {
                    var $table = $row.closest('table');
                    $row.fadeOut(180, function () {
                        $(this).remove();
                        if ($table.is('#osSettlementKyoShinTable')) {
                            refreshKyoShinTotal();
                        }
                        refreshOsSettlementItemTotals($table);
                        renumberOsSettlementRows($table);
                        updateFinishAllState();
                    });
                }

                $(document).on('click', '.pds-os-pay-method__btn', function () {
                    var method = $(this).data('method') === 'cash' ? 'cash' : 'kpay';
                    var $wrap = $(this).closest('.pds-os-pay-method');
                    setPayMethodUi($wrap, method);
                    if ($wrap.hasClass('js-os-row-pay-method')) {
                        var $row = $wrap.closest('tr');
                        $row.attr('data-payment-method', method);
                        syncRowFinishState($row);
                    }
                    updateFinishAllState();
                });

                function handleRowProofFiles($row, files, opts) {
                    files = dedupeImageFiles(normalizeImageFiles(files));
                    if (opts && opts.singlePaste && files.length > 1) {
                        files = [files[0]];
                    }
                    if (!files.length) return;
                    var $card = $row.find('.pds-os-kpay-upload-card');
                    appendLocalProofPreviews($row, files);
                    $card.addClass('is-uploading');
                    uploadProofForRow($row, files).then(function () {
                        $card.removeClass('is-uploading');
                    }).catch(function (err) {
                        $card.removeClass('is-uploading');
                        $card.find('.pds-os-kpay-preview--local').remove();
                        if (!$card.find('.pds-os-kpay-preview').length) {
                            $card.removeClass('has-images');
                        }
                        notify(err.message || @json(__('message.something_went_wrong')), 'error');
                    });
                }

                $(document).off('change.pdsOsProof', '.pds-os-kpay-file-input')
                    .on('change.pdsOsProof', '.pds-os-kpay-file-input', function () {
                    var input = this;
                    var files = dedupeImageFiles(normalizeImageFiles(input.files));
                    if (!files.length) return;
                    var $row = $(input).closest('tr');
                    handleRowProofFiles($row, files);
                    input.value = '';
                });

                $(document).off('click.pdsOsProof', '.pds-os-kpay-remove')
                    .on('click.pdsOsProof', '.pds-os-kpay-remove', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var $btn = $(this);
                    var $row = $btn.closest('tr');
                    var $card = $btn.closest('.pds-os-kpay-upload-card');
                    var index = parseInt($btn.attr('data-index'), 10);
                    if (Number.isNaN(index) || index < 0) return;
                    $card.addClass('is-uploading');
                    removeProofForRow($row, index).then(function () {
                        $card.removeClass('is-uploading');
                    }).catch(function (err) {
                        $card.removeClass('is-uploading');
                        notify(err.message || @json(__('message.something_went_wrong')), 'error');
                    });
                });

                var proofLightboxUrls = [];
                var proofLightboxIndex = 0;

                function ensureProofLightbox() {
                    var $box = $('#pdsOsProofLightbox');
                    if ($box.length && !$box.find('.pds-os-proof-lightbox__shell').length) {
                        $box.remove();
                        $box = $();
                    }
                    if ($box.length) return $box;
                    $('body').append(
                        '<div id="pdsOsProofLightbox" class="pds-os-proof-lightbox" role="dialog" aria-modal="true" aria-label="Proof image">'
                        + '<div class="pds-os-proof-lightbox__shell">'
                        + '<header class="pds-os-proof-lightbox__top">'
                        + '<div class="pds-os-proof-lightbox__title"><i class="fas fa-image" aria-hidden="true"></i><span>Proof Image</span></div>'
                        + '<button type="button" class="pds-os-proof-lightbox__close" aria-label="Close"><i class="fas fa-times"></i></button>'
                        + '</header>'
                        + '<div class="pds-os-proof-lightbox__body">'
                        + '<button type="button" class="pds-os-proof-lightbox__nav pds-os-proof-lightbox__nav--prev" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>'
                        + '<div class="pds-os-proof-lightbox__stage"><img class="pds-os-proof-lightbox__img" alt="Proof image"></div>'
                        + '<button type="button" class="pds-os-proof-lightbox__nav pds-os-proof-lightbox__nav--next" aria-label="Next"><i class="fas fa-chevron-right"></i></button>'
                        + '</div>'
                        + '<footer class="pds-os-proof-lightbox__foot">'
                        + '<div class="pds-os-proof-lightbox__meta"></div>'
                        + '<div class="pds-os-proof-lightbox__thumbs"></div>'
                        + '</footer>'
                        + '</div></div>'
                    );
                    return $('#pdsOsProofLightbox');
                }

                function renderProofLightbox() {
                    var $box = ensureProofLightbox();
                    var url = proofLightboxUrls[proofLightboxIndex] || '';
                    var $img = $box.find('.pds-os-proof-lightbox__img');
                    $img.removeClass('is-ready').attr('src', url);
                    $img.off('load.pdsLb error.pdsLb').on('load.pdsLb error.pdsLb', function () {
                        $(this).addClass('is-ready');
                    });
                    // Cached images may already be complete before the load handler binds.
                    if ($img[0] && $img[0].complete && $img[0].naturalWidth) {
                        $img.addClass('is-ready');
                    }

                    $box.find('.pds-os-proof-lightbox__meta').html(
                        proofLightboxUrls.length
                            ? '<span class="pds-os-proof-lightbox__count">' + (proofLightboxIndex + 1)
                                + '</span><span class="pds-os-proof-lightbox__sep">/</span><span>'
                                + proofLightboxUrls.length + '</span>'
                            : ''
                    );
                    var multi = proofLightboxUrls.length > 1;
                    $box.find('.pds-os-proof-lightbox__nav--prev').prop('hidden', !multi);
                    $box.find('.pds-os-proof-lightbox__nav--next').prop('hidden', !multi);
                    $box.toggleClass('has-multi', multi);

                    var thumbsHtml = '';
                    if (multi) {
                        proofLightboxUrls.forEach(function (thumbUrl, i) {
                            thumbsHtml += '<button type="button" class="pds-os-proof-lightbox__thumb'
                                + (i === proofLightboxIndex ? ' is-active' : '')
                                + '" data-index="' + i + '" aria-label="Image ' + (i + 1) + '">'
                                + '<img src="' + thumbUrl + '" alt=""></button>';
                        });
                    }
                    $box.find('.pds-os-proof-lightbox__thumbs').html(thumbsHtml);
                }

                function openProofLightbox(urls, index) {
                    proofLightboxUrls = (urls || []).filter(Boolean);
                    if (!proofLightboxUrls.length) return;
                    proofLightboxIndex = Math.max(0, Math.min(index || 0, proofLightboxUrls.length - 1));
                    ensureProofLightbox().addClass('is-open');
                    $('body').addClass('pds-os-proof-lightbox-open');
                    renderProofLightbox();
                }

                function closeProofLightbox() {
                    $('#pdsOsProofLightbox').removeClass('is-open');
                    $('body').removeClass('pds-os-proof-lightbox-open');
                }

                function stepProofLightbox(delta) {
                    if (proofLightboxUrls.length < 2) return;
                    proofLightboxIndex = (proofLightboxIndex + delta + proofLightboxUrls.length) % proofLightboxUrls.length;
                    renderProofLightbox();
                }

                $(document).on('click', '.pds-os-kpay-thumb-link', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var $card = $(this).closest('.pds-os-kpay-upload-card');
                    var urls = $card.find('.pds-os-kpay-thumb-link').map(function () {
                        return $(this).attr('href');
                    }).get();
                    var index = $card.find('.pds-os-kpay-thumb-link').index(this);
                    openProofLightbox(urls, index);
                });

                $(document).on('click', '#pdsOsProofLightbox', function (e) {
                    if (e.target === this) closeProofLightbox();
                });
                $(document).on('click', '.pds-os-proof-lightbox__close', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeProofLightbox();
                });
                $(document).on('click', '.pds-os-proof-lightbox__nav--prev', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    stepProofLightbox(-1);
                });
                $(document).on('click', '.pds-os-proof-lightbox__nav--next', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    stepProofLightbox(1);
                });
                $(document).on('click', '.pds-os-proof-lightbox__thumb', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var i = parseInt($(this).attr('data-index'), 10);
                    if (Number.isNaN(i)) return;
                    proofLightboxIndex = i;
                    renderProofLightbox();
                });
                $(document).on('keydown.pdsOsProofLightbox', function (e) {
                    if (!$('#pdsOsProofLightbox').hasClass('is-open')) return;
                    if (e.key === 'Escape') closeProofLightbox();
                    if (e.key === 'ArrowLeft') stepProofLightbox(-1);
                    if (e.key === 'ArrowRight') stepProofLightbox(1);
                });

                // Delegated drag-and-drop (works after SPA table refresh).
                $(document).off('dragenter.pdsOsProof dragover.pdsOsProof', '.pds-os-kpay-upload-card')
                    .on('dragenter.pdsOsProof dragover.pdsOsProof', '.pds-os-kpay-upload-card', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).addClass('is-dragover');
                });
                $(document).off('dragleave.pdsOsProof dragend.pdsOsProof', '.pds-os-kpay-upload-card')
                    .on('dragleave.pdsOsProof dragend.pdsOsProof', '.pds-os-kpay-upload-card', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).removeClass('is-dragover');
                });
                $(document).off('drop.pdsOsProof', '.pds-os-kpay-upload-card')
                    .on('drop.pdsOsProof', '.pds-os-kpay-upload-card', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).removeClass('is-dragover');
                    var dt = e.originalEvent && e.originalEvent.dataTransfer;
                    var files = dedupeImageFiles(normalizeImageFiles(dt && dt.files));
                    if (!files.length) return;
                    handleRowProofFiles($(this).closest('tr'), files);
                });

                function clipboardImageFiles(clipboard) {
                    var files = [];
                    if (!clipboard) return files;
                    // Prefer items first, but never keep duplicate MIME variants of the same paste.
                    if (clipboard.items && clipboard.items.length) {
                        Array.prototype.forEach.call(clipboard.items, function (item) {
                            if (item.kind === 'file' && item.type && item.type.indexOf('image/') === 0) {
                                var f = item.getAsFile();
                                if (f) files.push(f);
                            }
                        });
                    }
                    if (!files.length && clipboard.files && clipboard.files.length) {
                        files = normalizeImageFiles(clipboard.files);
                    } else {
                        files = normalizeImageFiles(files);
                    }
                    files = dedupeImageFiles(files);
                    // One paste gesture = one proof image (OS often exposes png+tiff for the same shot).
                    if (files.length > 1) files = [files[0]];
                    return files;
                }

                // Paste only into the proof cell under the cursor / focused card.
                // Never keep a sticky hover target — that made clipboard images
                // "auto" upload into the wrong row after the mouse moved away.
                function isOsProofTypingTarget(el) {
                    if (!el || el === document.body) return false;
                    var tag = (el.tagName || '').toLowerCase();
                    if (tag === 'textarea' || el.isContentEditable) return true;
                    if (tag === 'input') {
                        var type = String(el.type || '').toLowerCase();
                        // Allow paste onto the invisible proof file input / card.
                        if ($(el).closest('.pds-os-kpay-upload-card, #osReceiveBulkUpload').length) {
                            return false;
                        }
                        return type !== 'file' && type !== 'checkbox' && type !== 'radio' && type !== 'button' && type !== 'submit';
                    }
                    if (tag === 'select') return true;
                    return false;
                }

                function resolveOsProofPasteCard(e) {
                    var $fromEvent = $(e.target).closest('.pds-os-kpay-upload-card');
                    if ($fromEvent.length) return $fromEvent;
                    var $hovered = $('.pds-os-kpay-upload-card:hover').last();
                    if ($hovered.length) return $hovered;
                    var $focused = $('.pds-os-kpay-upload-card:focus-within, .pds-os-kpay-upload-card:focus').first();
                    if ($focused.length) return $focused;
                    return $();
                }

                // Paste into hovered/focused proof cell or bulk QR zone.
                $(document).off('paste.pdsOsProof').on('paste.pdsOsProof', function (e) {
                    var clipboard = e.originalEvent && e.originalEvent.clipboardData;
                    var files = clipboardImageFiles(clipboard);
                    if (!files.length) return;

                    // Don't steal Cmd/Ctrl+V from date filters / search / other fields.
                    if (isOsProofTypingTarget(e.target)) {
                        return;
                    }

                    if (!claimOsPasteLock()) {
                        e.preventDefault();
                        e.stopPropagation();
                        return;
                    }

                    var $bulk = $('#osReceiveBulkUpload');
                    if ($bulk.length && ($bulk.is(':hover') || $bulk.is(':focus-within') || $bulk.is(':focus') || document.activeElement === $bulk[0] || $(e.target).closest('#osReceiveBulkUpload').length)) {
                        e.preventDefault();
                        e.stopPropagation();
                        runReceiveBulkUpload(files);
                        return;
                    }

                    var $card = resolveOsProofPasteCard(e);
                    if (!$card.length) return;
                    e.preventDefault();
                    e.stopPropagation();
                    handleRowProofFiles($card.closest('tr'), files, { singlePaste: true });
                });

                function runReceiveBulkUpload(files) {
                    files = normalizeImageFiles(files);
                    if (!files.length) return;

                    var $rows = $('#osSettlementReceiveTable tbody tr.pds-os-settlement-row[data-is-finished="0"]');
                    if (!$rows.length) return;

                    var $zone = $('#osReceiveBulkUpload');
                    var $status = $('#osReceiveBulkStatus');
                    var $preview = $('#osReceiveBulkPreview');
                    var $previewImg = $('#osReceiveBulkPreviewImg');
                    var localUrl = URL.createObjectURL(files[0]);
                    $previewImg.attr('src', localUrl);
                    $preview.prop('hidden', false);
                    $zone.addClass('is-uploading');
                    $status.prop('hidden', false).text(@json(__('message.os_settlement_bulk_proof_uploading')));

                    var index = 0;
                    var failed = 0;

                    function next() {
                        if (index >= $rows.length) {
                            $zone.removeClass('is-uploading');
                            if (failed > 0) {
                                $status.text(@json(__('message.os_settlement_bulk_proof_partial')));
                                notify(@json(__('message.os_settlement_bulk_proof_partial')), 'error');
                            } else {
                                $status.text(@json(__('message.os_settlement_bulk_proof_done')));
                            }
                            updateFinishAllState();
                            return;
                        }
                        var $row = $($rows[index]);
                        index += 1;
                        $status.text(
                            @json(__('message.os_settlement_bulk_proof_progress'))
                                .replace(':current', String(index))
                                .replace(':total', String($rows.length))
                        );
                        uploadProofForRow($row, files).then(function () {
                            next();
                        }).catch(function () {
                            failed += 1;
                            next();
                        });
                    }

                    next();
                }

                $('#osReceiveBulkProofInput').on('change', function () {
                    var input = this;
                    var files = normalizeImageFiles(input.files);
                    input.value = '';
                    if (!files.length) return;
                    runReceiveBulkUpload(files);
                });

                $('#osReceiveBulkProofInput').attr('multiple', 'multiple');
                bindProofDropZone($('#osReceiveBulkUpload'), function (files) {
                    runReceiveBulkUpload(files);
                });
                $('#osReceiveBulkUpload').attr('tabindex', '0');

                function parseRowItemIds($el) {
                    var raw = String($el.attr('data-item-ids') || $el.data('item-ids') || '');
                    if (!raw) return [];
                    return raw.split(',').map(function (id) {
                        return parseInt(id, 10);
                    }).filter(function (id) {
                        return id > 0;
                    });
                }

                function refreshOsSettlementItemTotals($table) {
                    if (!$table || !$table.length) return;
                    var total = 0;
                    $table.find('tbody tr.pds-os-settlement-row').each(function () {
                        var $badge = $(this).find('.pds-os-settlement-badge').first();
                        var n = parseInt(String($badge.text() || '').replace(/[^\d]/g, ''), 10);
                        if (!isNaN(n)) total += n;
                    });
                    var $strong = $table.find('tfoot .pds-os-settlement-total-items strong');
                    if ($strong.length) {
                        $strong.text(String(total));
                    }
                }

                function formatMoney(n) {
                    var num = Math.abs(Number(n) || 0);
                    return num.toLocaleString();
                }

                var adminKpayPlaceholder = @json(__('message.admin_kpay_select_account'));

                function escapeHtml(str) {
                    return String(str == null ? '' : str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                }

                function isAdminKpayComboOpen() {
                    var wrap = document.getElementById('osAdminKpayCombo');
                    return !!(wrap && wrap.classList.contains('is-open'));
                }

                function closeAdminKpayCombo() {
                    var wrap = document.getElementById('osAdminKpayCombo');
                    var panel = document.getElementById('osAdminKpayComboPanel');
                    var toggle = document.getElementById('osAdminKpayComboToggle');
                    if (wrap) wrap.classList.remove('is-open');
                    if (panel) panel.style.display = '';
                    if (toggle) toggle.setAttribute('aria-expanded', 'false');
                }

                function syncAdminKpayComboLabel() {
                    var $sel = $('#osAdminKpayAccountSelect');
                    var $label = $('#osAdminKpayComboLabel');
                    if (!$sel.length || !$label.length) return;
                    var $opt = $sel.find('option:selected');
                    var val = String($sel.val() || '');
                    if (!val || !$opt.length) {
                        $label.attr('class', 'pds-admin-kpay-combo__label is-placeholder').text(adminKpayPlaceholder);
                        return;
                    }
                    var name = String($opt.data('name') || '');
                    var phone = String($opt.data('phone') || '');
                    var bal = formatMoney($opt.data('balance') || 0);
                    var initial = name ? name.charAt(0).toUpperCase() : 'A';
                    $label.attr('class', 'pds-admin-kpay-combo__label').html(
                        '<span class="pds-admin-kpay-option--selected">' +
                            '<span class="pds-admin-kpay-option__avatar">' + escapeHtml(initial) + '</span>' +
                            '<span class="pds-admin-kpay-option__copy">' +
                                '<strong>' + escapeHtml(name || '—') + '</strong>' +
                                '<span>' + escapeHtml(phone) + '</span>' +
                            '</span>' +
                            '<em>' + escapeHtml(bal) + '</em>' +
                        '</span>'
                    );
                }

                function renderAdminKpayComboResults(query) {
                    var list = document.getElementById('osAdminKpayComboList');
                    var $sel = $('#osAdminKpayAccountSelect');
                    if (!list || !$sel.length) return;
                    var q = String(query || '').toLowerCase().trim();
                    var selected = String($sel.val() || '');
                    list.innerHTML = '';
                    var shown = 0;
                    $sel.find('option').each(function () {
                        var $opt = $(this);
                        var value = String($opt.attr('value') || '');
                        if (!value) return;
                        var name = String($opt.data('name') || '');
                        var phone = String($opt.data('phone') || '');
                        var bal = formatMoney($opt.data('balance') || 0);
                        var hay = (name + ' ' + phone + ' ' + bal).toLowerCase();
                        if (q && hay.indexOf(q) === -1) return;
                        var initial = name ? name.charAt(0).toUpperCase() : 'A';
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'pds-admin-kpay-combo__option' + (value === selected ? ' is-selected' : '');
                        btn.setAttribute('data-value', value);
                        btn.innerHTML =
                            '<span class="pds-admin-kpay-option__avatar">' + escapeHtml(initial) + '</span>' +
                            '<span class="pds-admin-kpay-option__main">' +
                                '<strong>' + escapeHtml(name || '—') + '</strong>' +
                                '<span>' + escapeHtml(phone) + '</span>' +
                            '</span>' +
                            '<span class="pds-admin-kpay-option__bal">' + escapeHtml(bal) + '</span>';
                        list.appendChild(btn);
                        shown += 1;
                    });
                    if (!shown) {
                        var empty = document.createElement('div');
                        empty.className = 'pds-admin-kpay-combo__empty';
                        empty.textContent = @json(__('message.admin_kpay_empty_day'));
                        list.appendChild(empty);
                    }
                }

                function openAdminKpayCombo() {
                    var wrap = document.getElementById('osAdminKpayCombo');
                    var panel = document.getElementById('osAdminKpayComboPanel');
                    var toggle = document.getElementById('osAdminKpayComboToggle');
                    var search = document.getElementById('osAdminKpayComboSearch');
                    if (!wrap || !panel) return;
                    wrap.classList.add('is-open');
                    panel.style.display = 'flex';
                    if (toggle) toggle.setAttribute('aria-expanded', 'true');
                    if (search) search.value = '';
                    renderAdminKpayComboResults('');
                    if (search) {
                        setTimeout(function () {
                            try { search.focus(); } catch (e) {}
                        }, 10);
                    }
                }

                function refreshAdminKpaySelect(needAmount) {
                    hydrateAdminKpayAccountsFromDom();
                    var $sel = $('#osAdminKpayAccountSelect').first();
                    closeAdminKpayCombo();
                    if ($.fn.select2 && $sel.hasClass('select2-hidden-accessible')) {
                        try { $sel.select2('destroy'); } catch (e) {}
                    }
                    $sel.empty();
                    var need = Math.abs(parseMoneyValue(needAmount));
                    var usable = (adminKpayAccounts || []).filter(function (a) {
                        return parseMoneyValue(a.balance) + 0.00001 >= need;
                    });
                    if (!usable.length) {
                        syncAdminKpayComboLabel();
                        return false;
                    }
                    usable.forEach(function (a) {
                        $sel.append($('<option>', {
                            value: String(a.id),
                            text: (a.kpay_name || '') + ' · ' + (a.kpay_phone || '') + ' (' + formatMoney(a.balance) + ')',
                            'data-name': a.kpay_name || '',
                            'data-phone': a.kpay_phone || '',
                            'data-balance': a.balance,
                        }));
                    });
                    $sel.val(String(usable[0].id));
                    if (String($sel.val() || '') !== String(usable[0].id)) {
                        try { $sel.find('option').prop('selected', false).first().prop('selected', true); } catch (eSel) {}
                        $sel.val(String(usable[0].id));
                    }
                    syncAdminKpayComboLabel();
                    return selectedAdminKpayAccountId() > 0;
                }

                // Use mousedown so document click does not instantly close the panel.
                $(document).off('mousedown.pdsAdminKpayCombo', '#osAdminKpayComboToggle')
                    .on('mousedown.pdsAdminKpayCombo', '#osAdminKpayComboToggle', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (isAdminKpayComboOpen()) {
                            closeAdminKpayCombo();
                        } else {
                            openAdminKpayCombo();
                        }
                    });

                $(document).off('mousedown.pdsAdminKpayComboOpt', '#osAdminKpayComboList .pds-admin-kpay-combo__option')
                    .on('mousedown.pdsAdminKpayComboOpt', '#osAdminKpayComboList .pds-admin-kpay-combo__option', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var value = $(this).attr('data-value');
                        if (!value) return;
                        $('#osAdminKpayAccountSelect').val(value);
                        syncAdminKpayComboLabel();
                        closeAdminKpayCombo();
                    });

                $(document).off('input.pdsAdminKpayComboSearch', '#osAdminKpayComboSearch')
                    .on('input.pdsAdminKpayComboSearch', '#osAdminKpayComboSearch', function () {
                        renderAdminKpayComboResults(this.value);
                    });

                $(document).off('keydown.pdsAdminKpayComboSearch', '#osAdminKpayComboSearch')
                    .on('keydown.pdsAdminKpayComboSearch', '#osAdminKpayComboSearch', function (e) {
                        if (e.key === 'Escape') closeAdminKpayCombo();
                    });

                $(document).off('mousedown.pdsAdminKpayComboClose').on('mousedown.pdsAdminKpayComboClose', function (e) {
                    if (!isAdminKpayComboOpen()) return;
                    if ($(e.target).closest('#osAdminKpayCombo').length) return;
                    closeAdminKpayCombo();
                });

                function syncAdminKpayClearBtn() {
                    var $btn = $('#osAdminKpayClearBtn');
                    if (!$btn.length) return;
                    if ((adminKpayAccounts || []).length) {
                        $btn.removeAttr('hidden');
                    } else {
                        $btn.attr('hidden', true);
                    }
                }

                function renderAdminKpayList() {
                    var $list = $('#osAdminKpayList');
                    $list.empty();
                    if (!adminKpayAccounts.length) {
                        $list.append(
                            $('<div>', { class: 'pds-admin-kpay-panel__empty', id: 'osAdminKpayEmpty' })
                                .append($('<i>', { class: 'fas fa-university', 'aria-hidden': 'true' }))
                                .append($('<span>').text(@json(__('message.admin_kpay_empty_day'))))
                        );
                        syncAdminKpayClearBtn();
                        return;
                    }
                    adminKpayAccounts.forEach(function (a) {
                        var name = String(a.kpay_name || '');
                        var initial = name ? name.charAt(0).toUpperCase() : 'A';
                        var $chip = $('<div>', {
                            class: 'pds-admin-kpay-chip',
                            'data-account-id': a.id,
                            'data-balance': a.balance,
                            'data-amount': a.amount,
                            'data-kpay-name': a.kpay_name || '',
                            'data-kpay-phone': a.kpay_phone || '',
                        });
                        var $top = $('<div>', { class: 'pds-admin-kpay-chip__top' });
                        $top.append($('<span>', { class: 'pds-admin-kpay-chip__avatar', 'aria-hidden': 'true', text: initial }));
                        $top.append(
                            $('<div>', { class: 'pds-admin-kpay-chip__main' })
                                .append($('<strong>').text(name))
                                .append(
                                    $('<span>')
                                        .append($('<i>', { class: 'fas fa-mobile-alt', 'aria-hidden': 'true' }))
                                        .append(document.createTextNode(a.kpay_phone || ''))
                                )
                        );
                        $chip.append($top);

                        var $actions = $('<div>', { class: 'pds-admin-kpay-chip__actions' });
                        $actions.append(
                            $('<button>', {
                                type: 'button',
                                class: 'pds-admin-kpay-chip__info js-admin-kpay-info',
                                'data-id': a.id,
                                title: adminKpayHistoryTitle,
                                html: '<i class="fas fa-info" aria-hidden="true"></i>',
                            })
                        );
                        $actions.append(
                            $('<button>', {
                                type: 'button',
                                class: 'pds-admin-kpay-chip__edit js-admin-kpay-edit',
                                'data-id': a.id,
                                title: adminKpayEditLabel,
                                html: '<i class="fas fa-pen" aria-hidden="true"></i>',
                            })
                        );
                        $actions.append(
                            $('<button>', {
                                type: 'button',
                                class: 'pds-admin-kpay-chip__del js-admin-kpay-del',
                                'data-id': a.id,
                                title: deleteLabel,
                                html: '<i class="fas fa-trash-alt" aria-hidden="true"></i>',
                            })
                        );
                        var $meta = $('<div>', { class: 'pds-admin-kpay-chip__meta' });
                        $meta.append(
                            $('<div>', { class: 'pds-admin-kpay-chip__balance' })
                                .append($('<small>').text(@json(__('message.admin_kpay_balance'))))
                                .append($('<em>').text(formatMoney(a.balance)))
                        );
                        $meta.append($actions);
                        $chip.append($meta);
                        $list.append($chip);
                    });
                    syncAdminKpayClearBtn();
                }

                function applyAdminBalanceDeduct(accountId, amount) {
                    var need = Math.abs(Number(amount) || 0);
                    adminKpayAccounts = (adminKpayAccounts || []).map(function (a) {
                        if (String(a.id) === String(accountId)) {
                            a.balance = Math.max(0, Number(a.balance) - need);
                            a.label = (a.kpay_name || '') + ' · ' + (a.kpay_phone || '') + ' (' + formatMoney(a.balance) + ')';
                        }
                        return a;
                    });
                    renderAdminKpayList();
                }

                function finishOneOs(osId, $row, $btn, paymentMethod, adminAccountId, onDone) {
                    var settlementSide = normalizeSettlementSide($row.attr('data-section'));
                    $btn.prop('disabled', true);
                    var filters = currentOsListFilters();
                    var payload = {
                        _token: csrf,
                        from_date: filters.from_date,
                        to_date: filters.to_date,
                        branch_id: filters.branch_id,
                        delivery_format: 'table',
                        payment_method: settlementSide === 'kyo_shin' ? 'cash' : (paymentMethod || 'kpay'),
                        settlement_side: settlementSide,
                        item_ids: parseRowItemIds($row),
                    };
                    if (adminAccountId) {
                        payload.admin_kpay_account_id = adminAccountId;
                    }
                    $.ajax({
                        url: finishUrlTemplate.replace('__OS__', osId),
                        type: 'POST',
                        data: payload,
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function (res) {
                            markRowFinished($row);
                            if (adminAccountId) {
                                applyAdminBalanceDeduct(adminAccountId, $row.attr('data-amount'));
                            }
                            if (res.message) notify(res.message, 'success');
                            if (typeof onDone === 'function') onDone(true);
                        },
                        error: function (xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : @json(__('message.something_went_wrong'));
                            notify(msg, 'error');
                            syncRowFinishState($row);
                            if (typeof onDone === 'function') onDone(false);
                        },
                    });
                }

                function mountAdminKpayModal($modal) {
                    if (!$modal || !$modal.length) return $();
                    try {
                        // Only dedupe this modal's own id — never delete sibling kpay modals.
                        var id = $modal.attr('id');
                        if (id) {
                            $('div#' + id).not($modal.first()).remove();
                            $modal = $('div#' + id).first();
                        }
                        if ($modal.length && !$modal.parent().is('body')) {
                            $modal.appendTo(document.body);
                        }
                    } catch (e) {}
                    return $modal;
                }

                function setFinishQueueProgress(current, total) {
                    var $el = $('#osAdminKpayQueueProgress');
                    if (!$el.length) return;
                    if (!total || total < 2) {
                        $el.attr('hidden', true).text('');
                        return;
                    }
                    var label = String(finishQueueProgressTpl || ':current / :total')
                        .replace(':current', String(current))
                        .replace(':total', String(total));
                    $el.removeAttr('hidden').text(label);
                }

                function clearFinishConfirmQueue() {
                    finishConfirmQueue = [];
                    finishConfirmBusy = false;
                    finishConfirmQueueStop = false;
                    finishConfirmQueueTotal = 0;
                    setFinishQueueProgress(0, 0);
                    $('#osSettlementFinishAllPay').prop('disabled', false);
                    updateFinishAllState();
                }

                function scheduleDrainFinishConfirmQueue(delayMs) {
                    finishConfirmBusy = false;
                    setTimeout(function () {
                        drainFinishConfirmQueue();
                    }, typeof delayMs === 'number' ? delayMs : 280);
                }

                function openFinishConfirmForRow($row, $btn, onDone) {
                    try {
                        var osId = parseInt($row.attr('data-os-id'), 10);
                        if (isNaN(osId)) {
                            notify(@json(__('message.something_went_wrong')), 'error');
                            if (typeof onDone === 'function') onDone(false);
                            return;
                        }
                        var amount = parseRowAmount($row);
                        var method = $row.attr('data-payment-method') === 'cash' ? 'cash' : 'kpay';
                        var side = normalizeSettlementSide($row.attr('data-section'));
                        // Pay + KPay always needs the Admin account popup (never silent-finish).
                        var needsAdmin = side === 'pay' && method === 'kpay';

                        if (!needsAdmin) {
                            finishOneOs(osId, $row, $btn, method, null, onDone);
                            return;
                        }

                        $('#osConfirmOsName').text($row.attr('data-os-name') || '—');
                        $('#osConfirmOsPhone').text($row.attr('data-os-phone') || '—');
                        $('#osConfirmOsAddress').text($row.attr('data-os-address') || '—');
                        $('#osConfirmKpayName').text($row.attr('data-kpay-name') || '—');
                        $('#osConfirmKpayPhone').text($row.attr('data-kpay-no') || '—');
                        $('#osConfirmAmount').text(formatMoney(amount));

                        if (finishConfirmQueueTotal > 1) {
                            setFinishQueueProgress(finishConfirmQueueDone + 1, finishConfirmQueueTotal);
                        } else {
                            setFinishQueueProgress(0, 0);
                        }

                        if (!refreshAdminKpaySelect(amount)) {
                            notify(@json(__('message.admin_kpay_empty_day')), 'error');
                            try {
                                var panel = document.getElementById('osAdminKpayPanel');
                                if (panel && typeof panel.scrollIntoView === 'function') {
                                    panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                }
                                $('#osAdminKpayCreateBtn').trigger('focus');
                            } catch (e2) {}
                            if (typeof onDone === 'function') onDone(false);
                            return;
                        }

                        try {
                            closeProofLightbox();
                        } catch (eLightbox) {}

                        var $modal = mountAdminKpayModal($('#osAdminKpayConfirmModal'));
                        if (!$modal.length) {
                            notify(@json(__('message.something_went_wrong')), 'error');
                            if (typeof onDone === 'function') onDone(false);
                            return;
                        }
                        $modal.removeData('finish-confirmed');
                        $modal.removeData('pending-finish');
                        $modal.data('finish-ctx', {
                            osId: osId,
                            $row: $row,
                            $btn: $btn,
                            method: method,
                            amount: amount,
                            onDone: onDone,
                        });

                        function showConfirmModal() {
                            try {
                                $modal.appendTo(document.body);
                            } catch (eMount) {}
                            var $confirmBtn = $modal.find('#osAdminKpayConfirmBtn');
                            try { closeAdminKpayCombo(); } catch (eClose) {}
                            // Prevent the same click that opened the modal from also hitting Confirm.
                            $confirmBtn.prop('disabled', true);
                            $modal.data('confirm-ready-at', Date.now() + 400);
                            function enableConfirmBtn() {
                                $confirmBtn.prop('disabled', false);
                            }
                            // Always re-enable even if Bootstrap skips shown.bs.modal (no .fade).
                            window.setTimeout(enableConfirmBtn, 450);
                            $modal
                                .off('show.bs.modal.pdsOsBackdrop shown.bs.modal.pdsOsForce')
                                .on('show.bs.modal.pdsOsBackdrop', function () {
                                    window.setTimeout(function () {
                                        $('.modal-backdrop').last().addClass('pds-admin-kpay-backdrop');
                                    }, 0);
                                })
                                .on('shown.bs.modal.pdsOsForce', function () {
                                    var el = $modal[0];
                                    if (el) {
                                        el.style.display = 'block';
                                        el.style.opacity = '1';
                                        el.style.visibility = 'visible';
                                    }
                                    $('.modal-backdrop').last().addClass('pds-admin-kpay-backdrop');
                                    try { closeAdminKpayCombo(); } catch (eClose2) {}
                                    enableConfirmBtn();
                                });
                            try {
                                $modal.modal({
                                    backdrop: 'static',
                                    keyboard: true,
                                    focus: true,
                                    show: false,
                                });
                            } catch (eInit) {}
                            $modal.modal('show');
                        }

                        if ($modal.hasClass('show') || $modal.hasClass('in')) {
                            $modal.off('hidden.bs.modal.pdsOsQueueOpen').one('hidden.bs.modal.pdsOsQueueOpen', function () {
                                showConfirmModal();
                            });
                            $modal.modal('hide');
                        } else {
                            showConfirmModal();
                        }
                    } catch (err) {
                        console.error('openFinishConfirmForRow', err);
                        notify(@json(__('message.something_went_wrong')), 'error');
                        syncRowFinishState($row);
                        if (typeof onDone === 'function') onDone(false);
                    }
                }

                function drainFinishConfirmQueue() {
                    if (finishConfirmBusy) return;
                    if (finishConfirmQueueStop) {
                        finishConfirmQueueStop = false;
                        updateFinishAllState();
                        return;
                    }
                    if (!finishConfirmQueue.length) {
                        setFinishQueueProgress(0, 0);
                        $('#osSettlementFinishAllPay').prop('disabled', false);
                        updateFinishAllState();
                        if (finishConfirmQueueTotal > 0 && finishConfirmQueueDone > 0) {
                            notify(
                                String(finishQueueDoneMsg || '').replace(':count', String(finishConfirmQueueDone)),
                                'success'
                            );
                        }
                        finishConfirmQueueTotal = 0;
                        finishConfirmQueueDone = 0;
                        return;
                    }
                    finishConfirmBusy = true;
                    var next = finishConfirmQueue.shift();
                    openFinishConfirmForRow(next.$row, next.$btn, function (ok) {
                        if (ok) finishConfirmQueueDone += 1;
                        if (finishConfirmQueueStop) {
                            finishConfirmBusy = false;
                            clearFinishConfirmQueue();
                            return;
                        }
                        scheduleDrainFinishConfirmQueue(ok ? 320 : 180);
                    });
                }

                function finishAllOs(items, $btn, section) {
                    var label = $btn.find('span').first();
                    var originalText = label.text();
                    $btn.prop('disabled', true);
                    label.text(@json(__('message.processing')));
                    var filters = currentOsListFilters();
                    $.ajax({
                        url: finishAllUrl,
                        type: 'POST',
                        data: {
                            _token: csrf,
                            from_date: filters.from_date,
                            to_date: filters.to_date,
                            branch_id: filters.branch_id,
                            items: items,
                            delivery_format: 'table',
                            settlement_side: section,
                        },
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function (res) {
                            var finishedIds = (res.finished_os_ids || []).map(function (id) { return String(id); });
                            var selector = section === 'receive'
                                ? '#osSettlementReceiveTable tbody tr.pds-os-settlement-row[data-is-finished="0"]'
                                : (section === 'kyo_shin'
                                    ? '#osSettlementKyoShinTable tbody tr.pds-os-settlement-row[data-is-finished="0"]'
                                    : '#osSettlementPayTable tbody tr.pds-os-settlement-row[data-is-finished="0"]');
                            $(selector).each(function () {
                                var $row = $(this);
                                if (!finishedIds.length || finishedIds.indexOf(String($row.data('os-id'))) !== -1) {
                                    markRowFinished($row);
                                }
                            });
                            if (res.message) notify(res.message, 'success');
                            setTimeout(function () { typeof window.adminLiveReloadPage === 'function' ? window.adminLiveReloadPage() : window.location.reload(); }, 400);
                        },
                        error: function (xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : @json(__('message.something_went_wrong'));
                            notify(msg, 'error');
                            label.text(originalText);
                            updateFinishAllState();
                        },
                    });
                }

                $(document).off('click.pdsOsFinish', '.pds-os-finish-btn')
                    .on('click.pdsOsFinish', '.pds-os-finish-btn', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var $btn = $(this);
                    if ($btn.prop('disabled') || $btn.hasClass('is-disabled') || $btn.attr('disabled')) {
                        if (!$btn.closest('tr.pds-os-settlement-row').find('.pds-os-kpay-preview').length
                            && $btn.closest('tr.pds-os-settlement-row').attr('data-section') !== 'kyo_shin') {
                            notify(@json(__('message.os_settlement_kpay_slip_required')), 'error');
                        }
                        return;
                    }
                    var $row = $btn.closest('tr.pds-os-settlement-row');
                    if (!$row.length) return;
                    syncRowFinishState($row);
                    if ($btn.prop('disabled') || $btn.hasClass('is-disabled') || $btn.attr('disabled')) {
                        if (!$row.find('.pds-os-kpay-preview').length && $row.attr('data-section') !== 'kyo_shin') {
                            notify(@json(__('message.os_settlement_kpay_slip_required')), 'error');
                        }
                        return;
                    }
                    openFinishConfirmForRow($row, $btn);
                });

                $(document).off('click.pdsOsFinishAllPay', '#osSettlementFinishAllPay')
                    .on('click.pdsOsFinishAllPay', '#osSettlementFinishAllPay', function (e) {
                    e.preventDefault();
                    var $btn = $(this);
                    updateFinishAllState();
                    if ($btn.prop('disabled') || finishConfirmBusy) return;

                    finishConfirmQueue = [];
                    finishConfirmQueueStop = false;
                    finishConfirmQueueDone = 0;
                    $('#osSettlementPayTable tbody tr.pds-os-settlement-row[data-is-finished="0"]').each(function () {
                        var $row = $(this);
                        syncRowFinishState($row);
                        if (!rowHasProof($row)) return;
                        var $rowBtn = $row.find('.pds-os-finish-btn').first();
                        if ($rowBtn.length && ($rowBtn.prop('disabled') || $rowBtn.hasClass('is-disabled'))) return;
                        if (!$rowBtn.length) $rowBtn = $btn;
                        finishConfirmQueue.push({ $row: $row, $btn: $rowBtn });
                    });
                    finishConfirmQueueTotal = finishConfirmQueue.length;
                    if (!finishConfirmQueue.length) {
                        notify(@json(__('message.os_settlement_finish_all_none')), 'error');
                        return;
                    }
                    $btn.prop('disabled', true);
                    drainFinishConfirmQueue();
                });

                $('#osAdminKpayConfirmBtn').off('click.pdsOsAdminConfirm').on('click.pdsOsAdminConfirm', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var $modal = $('#osAdminKpayConfirmModal').first();
                    var ctx = $modal.data('finish-ctx');
                    if (!ctx) return;
                    if (Date.now() < (Number($modal.data('confirm-ready-at')) || 0)) {
                        return;
                    }
                    var accountId = selectedAdminKpayAccountId($modal);
                    if (!accountId) {
                        notify(@json(__('message.admin_kpay_account_required')), 'error');
                        try {
                            openAdminKpayCombo();
                        } catch (eOpen) {}
                        return;
                    }
                    var $confirmBtn = $(this);
                    $confirmBtn.prop('disabled', true);
                    $modal.data('finish-confirmed', true);
                    $modal.data('pending-finish', {
                        accountId: accountId,
                        ctx: ctx,
                        $confirmBtn: $confirmBtn,
                    });
                    $modal.modal('hide');
                });

                $('#osAdminKpayConfirmModal').off('hidden.bs.modal.pdsOsAdminConfirm').on('hidden.bs.modal.pdsOsAdminConfirm', function () {
                    var $modal = $(this);
                    closeAdminKpayCombo();

                    var pending = $modal.data('pending-finish');
                    if (pending && pending.ctx) {
                        $modal.removeData('pending-finish');
                        $modal.removeData('finish-ctx');
                        $modal.removeData('finish-confirmed');
                        finishOneOs(
                            pending.ctx.osId,
                            pending.ctx.$row,
                            pending.ctx.$btn,
                            pending.ctx.method,
                            pending.accountId,
                            function (ok) {
                                if (pending.$confirmBtn) pending.$confirmBtn.prop('disabled', false);
                                if (typeof pending.ctx.onDone === 'function') pending.ctx.onDone(ok);
                            }
                        );
                        return;
                    }

                    if ($modal.data('finish-confirmed')) return;
                    var ctx = $modal.data('finish-ctx');
                    if (!ctx) return;
                    $modal.removeData('finish-ctx');
                    syncRowFinishState(ctx.$row);
                    // Cancel during Finished All stops the remaining queue.
                    if (finishConfirmQueueTotal > 0) {
                        finishConfirmQueueStop = true;
                        finishConfirmQueue = [];
                        setFinishQueueProgress(0, 0);
                        if (finishConfirmQueueDone > 0) {
                            notify(finishQueueCancelledMsg, 'success');
                        }
                        finishConfirmQueueTotal = 0;
                    }
                    if (typeof ctx.onDone === 'function') ctx.onDone(false);
                });

                function openAdminKpayForm(mode, account) {
                    var isEdit = mode === 'edit' && account;
                    $('#osAdminKpayEditId').val(isEdit ? account.id : '');
                    $('#osAdminKpayNameInput').val(isEdit ? (account.kpay_name || '') : '');
                    $('#osAdminKpayPhoneInput').val(isEdit ? (account.kpay_phone || '') : '');
                    $('#osAdminKpayAmountInput').val(isEdit ? (account.amount || '') : '');
                    $('#osAdminKpayFormTitle').text(isEdit ? adminKpayEditTitle : adminKpayCreateTitle);
                    $('#osAdminKpayFormIcon')
                        .removeClass('fa-plus fa-pen')
                        .addClass(isEdit ? 'fa-pen' : 'fa-plus');
                    mountAdminKpayModal($('#osAdminKpayCreateModal')).modal({
                        backdrop: true,
                        keyboard: true,
                        show: true,
                    });
                }

                $('#osAdminKpayCreateBtn').off('click.pdsOsAdminCreate').on('click.pdsOsAdminCreate', function () {
                    openAdminKpayForm('create');
                });

                $(document).off('click.pdsOsAdminEdit', '.js-admin-kpay-edit')
                    .on('click.pdsOsAdminEdit', '.js-admin-kpay-edit', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var id = $(this).data('id');
                    if (!id) return;
                    var account = (adminKpayAccounts || []).find(function (a) {
                        return String(a.id) === String(id);
                    });
                    if (!account) {
                        var $chip = $(this).closest('.pds-admin-kpay-chip');
                        account = {
                            id: id,
                            kpay_name: $chip.attr('data-kpay-name') || '',
                            kpay_phone: $chip.attr('data-kpay-phone') || '',
                            amount: $chip.attr('data-amount') || '',
                            balance: $chip.attr('data-balance') || '',
                        };
                    }
                    openAdminKpayForm('edit', account);
                });

                // Ensure confirm / create modals are not trapped under page transforms.
                mountAdminKpayModal($('#osAdminKpayConfirmModal'));
                mountAdminKpayModal($('#osAdminKpayCreateModal'));
                mountAdminKpayModal($('#osAdminKpayHistoryModal'));
                mountAdminKpayModal($('#osAdminKpayActionModal'));

                var adminKpayActionPending = null;

                function openAdminKpayActionModal(opts) {
                    opts = opts || {};
                    adminKpayActionPending = opts;
                    var isDanger = opts.variant !== 'primary';
                    var $modal = mountAdminKpayModal($('#osAdminKpayActionModal'));
                    $modal.toggleClass('is-danger', isDanger);
                    $('#osAdminKpayActionIcon')
                        .removeClass('fa-exclamation fa-trash-alt fa-times-circle')
                        .addClass(opts.icon || (isDanger ? 'fa-trash-alt' : 'fa-exclamation'));
                    $('#osAdminKpayActionTitle').text(opts.title || '');
                    $('#osAdminKpayActionMessage').text(opts.message || '');
                    $('#osAdminKpayActionConfirmLabel').text(opts.confirmLabel || adminKpayConfirmActionLabel);
                    var $confirmBtn = $('#osAdminKpayActionConfirmBtn');
                    $confirmBtn
                        .prop('disabled', false)
                        .toggleClass('pds-admin-kpay-modal__danger', isDanger)
                        .toggleClass('pds-admin-kpay-modal__primary btn-primary', !isDanger);
                    $confirmBtn.find('i')
                        .removeClass('fa-trash-alt fa-check fa-times-circle')
                        .addClass(opts.confirmIcon || (isDanger ? 'fa-trash-alt' : 'fa-check'));

                    var $card = $('#osAdminKpayActionCard');
                    if (opts.account) {
                        var name = opts.account.kpay_name || '—';
                        var phone = opts.account.kpay_phone || '—';
                        $('#osAdminKpayActionAvatar').text(String(name).charAt(0).toUpperCase() || 'A');
                        $('#osAdminKpayActionName').text(name);
                        $('#osAdminKpayActionPhone').text(phone);
                        $card.removeAttr('hidden');
                    } else {
                        $card.attr('hidden', true);
                    }

                    $modal.modal({
                        backdrop: 'static',
                        keyboard: true,
                        show: true,
                    });
                }

                function closeAdminKpayActionModal() {
                    adminKpayActionPending = null;
                    $('#osAdminKpayActionConfirmBtn').prop('disabled', false);
                    $('#osAdminKpayActionModal').modal('hide');
                }

                function removeAdminKpayAccountLocal(id) {
                    adminKpayAccounts = (adminKpayAccounts || []).filter(function (a) {
                        return String(a.id) !== String(id);
                    });
                    renderAdminKpayList();
                }

                function destroyAdminKpayAccount(id, onDone) {
                    $.ajax({
                        url: adminKpayDestroyUrlTemplate.replace('__ID__', id),
                        type: 'POST',
                        data: { _token: csrf },
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        success: function (res) {
                            removeAdminKpayAccountLocal(id);
                            if (res && res.message) notify(res.message, 'success');
                            if (typeof onDone === 'function') onDone(true);
                        },
                        error: function (xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                ? xhr.responseJSON.message
                                : @json(__('message.something_went_wrong'));
                            notify(msg, 'error');
                            if (typeof onDone === 'function') onDone(false);
                        },
                    });
                }

                function clearAdminKpayAccounts(onDone) {
                    $.ajax({
                        url: adminKpayClearUrl,
                        type: 'POST',
                        data: {
                            _token: csrf,
                            from_date: filterFrom,
                            to_date: filterTo,
                        },
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        success: function (res) {
                            var deletedIds = (res.deleted_ids || []).map(String);
                            if (deletedIds.length) {
                                adminKpayAccounts = (adminKpayAccounts || []).filter(function (a) {
                                    return deletedIds.indexOf(String(a.id)) === -1;
                                });
                            } else if (!res.skipped) {
                                adminKpayAccounts = [];
                            }
                            renderAdminKpayList();
                            if (res.message) notify(res.message, 'success');
                            if (typeof onDone === 'function') onDone(true);
                        },
                        error: function (xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                ? xhr.responseJSON.message
                                : @json(__('message.something_went_wrong'));
                            notify(msg, 'error');
                            if (typeof onDone === 'function') onDone(false);
                        },
                    });
                }

                $('#osAdminKpayActionConfirmBtn').off('click.pdsOsAdminAction').on('click.pdsOsAdminAction', function () {
                    var pending = adminKpayActionPending;
                    if (!pending || !pending.action) return;
                    var $btn = $(this);
                    $btn.prop('disabled', true);
                    if (pending.action === 'delete') {
                        destroyAdminKpayAccount(pending.id, function () {
                            closeAdminKpayActionModal();
                        });
                        return;
                    }
                    if (pending.action === 'clear') {
                        clearAdminKpayAccounts(function () {
                            closeAdminKpayActionModal();
                        });
                    }
                });

                $('#osAdminKpayActionModal').off('hidden.bs.modal.pdsOsAdminAction').on('hidden.bs.modal.pdsOsAdminAction', function () {
                    adminKpayActionPending = null;
                    $('#osAdminKpayActionConfirmBtn').prop('disabled', false);
                });

                function renderAdminKpayHistory(res) {
                    var account = res.account || {};
                    var days = res.days || [];
                    $('#osAdminKpayHistoryTitle').text(adminKpayHistoryTitle);

                    var $account = $('#osAdminKpayHistoryAccount');
                    $account.empty().removeAttr('hidden');
                    var initial = String(account.kpay_name || 'A').charAt(0).toUpperCase();
                    $account.append(
                        $('<span>', { class: 'pds-admin-kpay-history-account__avatar', 'aria-hidden': 'true', text: initial })
                    );
                    $account.append(
                        $('<div>', { class: 'pds-admin-kpay-history-account__main' })
                            .append($('<strong>').text(account.kpay_name || '—'))
                            .append(
                                $('<span>')
                                    .append($('<i>', { class: 'fas fa-mobile-alt', 'aria-hidden': 'true' }))
                                    .append(document.createTextNode(account.kpay_phone || '—'))
                            )
                    );

                    var $summary = $('#osAdminKpayHistorySummary');
                    $summary.empty().removeAttr('hidden');
                    $summary.append(
                        $('<div>', { class: 'pds-admin-kpay-history-summary__item is-balance' })
                            .append(
                                $('<span>')
                                    .append($('<i>', { class: 'fas fa-wallet', 'aria-hidden': 'true' }))
                                    .append(document.createTextNode(adminKpayBalanceLabel))
                            )
                            .append($('<strong>').text(formatMoney(account.balance || 0)))
                    );
                    $summary.append(
                        $('<div>', { class: 'pds-admin-kpay-history-summary__item is-transferred' })
                            .append(
                                $('<span>')
                                    .append($('<i>', { class: 'fas fa-paper-plane', 'aria-hidden': 'true' }))
                                    .append(document.createTextNode(adminKpayHistoryAmountLabel))
                            )
                            .append($('<strong>').text(formatMoney(res.transfer_total || 0)))
                    );
                    $summary.append(
                        $('<div>', { class: 'pds-admin-kpay-history-summary__item is-os' })
                            .append(
                                $('<span>')
                                    .append($('<i>', { class: 'fas fa-store', 'aria-hidden': 'true' }))
                                    .append(document.createTextNode('OS'))
                            )
                            .append($('<strong>').text(String(res.transfer_count || 0)))
                    );

                    var $body = $('#osAdminKpayHistoryBody');
                    $body.empty();
                    if (!days.length) {
                        $body.append(
                            $('<div>', { class: 'pds-admin-kpay-history-empty' })
                                .append($('<i>', { class: 'fas fa-inbox', 'aria-hidden': 'true' }))
                                .append($('<span>').text(adminKpayHistoryEmpty))
                        );
                        return;
                    }

                    days.forEach(function (day) {
                        var $day = $('<div>', { class: 'pds-admin-kpay-history-day' });
                        $day.append(
                            $('<div>', { class: 'pds-admin-kpay-history-day__head' })
                                .append($('<strong>').text(day.date || '—'))
                                .append($('<em>').text(formatMoney(day.total || 0)))
                        );
                        var $list = $('<div>', { class: 'pds-admin-kpay-history-day__list' });
                        (day.items || []).forEach(function (item) {
                            var $row = $('<div>', { class: 'pds-admin-kpay-history-row' });
                            var $main = $('<div>', { class: 'pds-admin-kpay-history-row__main' });
                            $main.append($('<div>', { class: 'pds-admin-kpay-history-row__os', text: item.os_name || '—' }));

                            if (item.os_phone) {
                                $main.append($('<div>', { class: 'pds-admin-kpay-history-row__line', text: item.os_phone }));
                            }
                            if (item.os_kpay_name || item.os_kpay_phone) {
                                $main.append(
                                    $('<div>', {
                                        class: 'pds-admin-kpay-history-row__line',
                                        text: [item.os_kpay_name, item.os_kpay_phone].filter(Boolean).join(' · '),
                                    })
                                );
                            }

                            var $foot = $('<div>', { class: 'pds-admin-kpay-history-row__foot' });
                            var $adminWrap = $('<span>', { class: 'pds-admin-kpay-history-row__admin' });
                            $adminWrap.append($('<b>').text(adminKpayHistoryAdminLabel + ': '));
                            $adminWrap.append(document.createTextNode(item.admin_name || '—'));
                            $foot.append($adminWrap);
                            if (item.finished_at) {
                                $foot.append($('<span>', { class: 'pds-admin-kpay-history-row__time', text: item.finished_at }));
                            }
                            $main.append($foot);

                            $row.append($main);
                            $row.append(
                                $('<div>', { class: 'pds-admin-kpay-history-row__amount' })
                                    .append($('<strong>').text(formatMoney(item.amount || 0)))
                            );
                            $list.append($row);
                        });
                        $day.append($list);
                        $body.append($day);
                    });
                }

                $(document).off('click.pdsOsAdminInfo', '.js-admin-kpay-info')
                    .on('click.pdsOsAdminInfo', '.js-admin-kpay-info', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var id = $(this).data('id');
                    if (!id) return;
                    var $btn = $(this);
                    $btn.prop('disabled', true);
                    $('#osAdminKpayHistoryTitle').text(adminKpayHistoryTitle);
                    $('#osAdminKpayHistoryAccount').attr('hidden', true).empty();
                    $('#osAdminKpayHistorySummary').attr('hidden', true).empty();
                    $('#osAdminKpayHistoryBody').html(
                        '<div class="pds-admin-kpay-history-empty">'
                        + '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i>'
                        + '<span>' + @json(__('message.processing')) + '</span></div>'
                    );
                    mountAdminKpayModal($('#osAdminKpayHistoryModal')).modal({
                        backdrop: true,
                        keyboard: true,
                        show: true,
                    });
                    $.ajax({
                        url: adminKpayHistoryUrlTemplate.replace('__ID__', id),
                        type: 'GET',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function (res) {
                            renderAdminKpayHistory(res || {});
                        },
                        error: function (xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                ? xhr.responseJSON.message
                                : @json(__('message.something_went_wrong'));
                            $('#osAdminKpayHistoryBody').html(
                                '<div class="pds-admin-kpay-history-empty">' + msg + '</div>'
                            );
                            notify(msg, 'error');
                        },
                        complete: function () {
                            $btn.prop('disabled', false);
                        },
                    });
                });

                $('#osAdminKpayCreateSaveBtn').off('click.pdsOsAdminSave').on('click.pdsOsAdminSave', function () {
                    var name = $.trim($('#osAdminKpayNameInput').val() || '');
                    var phone = $.trim($('#osAdminKpayPhoneInput').val() || '');
                    var amount = Number($('#osAdminKpayAmountInput').val() || 0);
                    var editId = $.trim($('#osAdminKpayEditId').val() || '');
                    if (!name || !phone || !(amount > 0)) {
                        notify(@json(__('message.something_went_wrong')), 'error');
                        return;
                    }
                    var $saveBtn = $(this);
                    $saveBtn.prop('disabled', true);
                    var isEdit = !!editId;
                    $.ajax({
                        url: isEdit
                            ? adminKpayUpdateUrlTemplate.replace('__ID__', editId)
                            : adminKpayStoreUrl,
                        type: 'POST',
                        data: isEdit
                            ? {
                                _token: csrf,
                                _method: 'PUT',
                                kpay_name: name,
                                kpay_phone: phone,
                                amount: amount,
                            }
                            : {
                                _token: csrf,
                                settlement_date: filterFrom,
                                kpay_name: name,
                                kpay_phone: phone,
                                amount: amount,
                                branch_id: filterBranchId,
                            },
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function (res) {
                            if (res.account) {
                                if (isEdit) {
                                    adminKpayAccounts = (adminKpayAccounts || []).map(function (a) {
                                        return String(a.id) === String(res.account.id) ? res.account : a;
                                    });
                                } else {
                                    adminKpayAccounts.unshift(res.account);
                                }
                                renderAdminKpayList();
                            }
                            if (res.message) notify(res.message, 'success');
                            $('#osAdminKpayCreateModal').modal('hide');
                            $('#osAdminKpayEditId').val('');
                        },
                        error: function (xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : @json(__('message.something_went_wrong'));
                            notify(msg, 'error');
                        },
                        complete: function () {
                            $saveBtn.prop('disabled', false);
                        },
                    });
                });

                $(document).off('click.pdsOsAdminDel', '.js-admin-kpay-del').on('click.pdsOsAdminDel', '.js-admin-kpay-del', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var $btn = $(this);
                    var id = $btn.attr('data-id') || $btn.data('id');
                    if (!id) return;
                    var account = (adminKpayAccounts || []).find(function (a) {
                        return String(a.id) === String(id);
                    });
                    if (!account) {
                        var $chip = $btn.closest('.pds-admin-kpay-chip');
                        account = {
                            id: id,
                            kpay_name: $chip.attr('data-kpay-name') || '',
                            kpay_phone: $chip.attr('data-kpay-phone') || '',
                        };
                    }
                    openAdminKpayActionModal({
                        action: 'delete',
                        id: id,
                        variant: 'danger',
                        icon: 'fa-trash-alt',
                        confirmIcon: 'fa-trash-alt',
                        title: adminKpayDeleteTitle,
                        message: adminKpayDeleteConfirmMsg,
                        confirmLabel: adminKpayConfirmDeleteLabel,
                        account: account,
                    });
                });

                $(document).off('click.pdsOsAdminClear', '#osAdminKpayClearBtn')
                    .on('click.pdsOsAdminClear', '#osAdminKpayClearBtn', function (e) {
                    e.preventDefault();
                    if (!(adminKpayAccounts || []).length) return;
                    openAdminKpayActionModal({
                        action: 'clear',
                        variant: 'danger',
                        icon: 'fa-times-circle',
                        confirmIcon: 'fa-trash-alt',
                        title: adminKpayClearTitle,
                        message: adminKpayClearConfirmMsg,
                        confirmLabel: adminKpayConfirmDeleteLabel,
                    });
                });

                $(document).off('click.pdsOsFinishAllReceive', '#osSettlementFinishAllReceive')
                    .on('click.pdsOsFinishAllReceive', '#osSettlementFinishAllReceive', function (e) {
                    e.preventDefault();
                    var $btn = $(this);
                    updateFinishAllState();
                    if ($btn.prop('disabled')) return;
                    var items = [];
                    $('#osSettlementReceiveTable tbody tr.pds-os-settlement-row[data-is-finished="0"]').each(function () {
                        var $row = $(this);
                        items.push({
                            os_id: parseInt($row.attr('data-os-id'), 10),
                            payment_method: 'cash',
                            settlement_side: 'receive',
                            item_ids: parseRowItemIds($row),
                        });
                    });
                    if (!items.length) return;
                    finishAllOs(items, $btn, 'receive');
                });

                $(document).off('click.pdsOsFinishAllKyoShin', '#osSettlementFinishAllKyoShin')
                    .on('click.pdsOsFinishAllKyoShin', '#osSettlementFinishAllKyoShin', function (e) {
                    e.preventDefault();
                    var $btn = $(this);
                    updateFinishAllState();
                    if ($btn.prop('disabled')) return;
                    var items = [];
                    $('#osSettlementKyoShinTable tbody tr.pds-os-settlement-row[data-is-finished="0"]').each(function () {
                        var $row = $(this);
                        items.push({
                            os_id: parseInt($row.attr('data-os-id'), 10),
                            payment_method: 'cash',
                            settlement_side: 'kyo_shin',
                            item_ids: parseRowItemIds($row),
                        });
                    });
                    if (!items.length) return;
                    finishAllOs(items, $btn, 'kyo_shin');
                });

                var slipPreviewXhr = null;

                function cleanupOsSlipCompletedModal() {
                    var $modal = $('#osSlipCompletedModal');
                    if ($modal.length) {
                        try {
                            $modal.removeData('bs.modal');
                        } catch (e) {}
                        $modal.remove();
                    }
                    $('#osSettlementSlipModalHost').empty();
                    if (!$('.modal.show').length) {
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css({ paddingRight: '', overflow: '' });
                    }
                }

                function openOsSlipCompletedModal(html) {
                    cleanupOsSlipCompletedModal();
                    $('#osSettlementSlipModalHost').html(html || '');
                    var $modal = $('#osSettlementSlipModalHost #osSlipCompletedModal');
                    if (!$modal.length) {
                        return;
                    }
                    // Move to <body> so fixed positioning / backdrop stacking works
                    // (same pattern as Daily Check detail modal). Nested page shells
                    // with transforms leave the X unclickable under the blur layer.
                    if (!$modal.parent().is('body')) {
                        $modal.appendTo(document.body);
                    }
                    $modal.modal({
                        backdrop: true,
                        keyboard: true,
                        show: true,
                    });
                }

                $(document)
                    .off('click.pdsOsSlipClose', '#osSlipCompletedCloseBtn, #osSlipCompletedModal [data-dismiss="modal"]')
                    .on('click.pdsOsSlipClose', '#osSlipCompletedCloseBtn, #osSlipCompletedModal [data-dismiss="modal"]', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var $modal = $('#osSlipCompletedModal');
                        if ($modal.length) {
                            $modal.modal('hide');
                        } else {
                            cleanupOsSlipCompletedModal();
                        }
                    });

                $(document)
                    .off('hidden.bs.modal.pdsOsSlip', '#osSlipCompletedModal')
                    .on('hidden.bs.modal.pdsOsSlip', '#osSlipCompletedModal', function () {
                        $(this).remove();
                        $('#osSettlementSlipModalHost').empty();
                        if (!$('.modal.show').length) {
                            $('.modal-backdrop').remove();
                            $('body').removeClass('modal-open').css({ paddingRight: '', overflow: '' });
                        }
                    });

                $(document).on('click', '.pds-os-slip-preview-btn', function () {
                    if ($(this).prop('disabled')) {
                        return;
                    }
                    var $btn = $(this);
                    var $row = $btn.closest('tr');
                    var osId = $btn.data('os-id') || $row.data('os-id');
                    var settlementSide = normalizeSettlementSide(
                        $btn.attr('data-section') || $row.attr('data-section')
                    );
                    var itemIds = parseRowItemIds($btn);
                    if (!itemIds.length) {
                        itemIds = parseRowItemIds($row);
                    }
                    if (slipPreviewXhr && slipPreviewXhr.readyState !== 4) {
                        slipPreviewXhr.abort();
                    }
                    // Do NOT use traditional:true — it serializes item_ids as
                    // item_ids=1&item_ids=2 (PHP keeps only the last id), so Show
                    // Slip showed 1 parcel while the list badge showed N.
                    var filters = currentOsListFilters();
                    slipPreviewXhr = $.ajax({
                        url: slipPreviewUrlTemplate.replace('__OS__', osId),
                        type: 'GET',
                        data: {
                            from_date: filters.from_date,
                            to_date: filters.to_date,
                            branch_id: filters.branch_id,
                            settlement_side: settlementSide,
                            item_ids: itemIds,
                        },
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function (res) {
                            openOsSlipCompletedModal(res.html || '');
                        },
                        error: function (xhr) {
                            if (xhr.statusText === 'abort') {
                                return;
                            }
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : @json(__('message.something_went_wrong'));
                            notify(msg, 'error');
                        },
                    });
                });

                $(document).on('click', '#osSlipScreenshotBtn', function (e) {
                    e.preventDefault();
                    var target = document.getElementById('osSlipCompletedCapture');
                    if (!target || typeof html2canvas !== 'function') {
                        notify(@json(__('message.something_went_wrong')), 'error');
                        return;
                    }
                    var $btn = $(this);
                    $btn.prop('disabled', true);
                    html2canvas(target, {
                        backgroundColor: '#ffffff',
                        scale: 2,
                        useCORS: true,
                        allowTaint: true,
                    }).then(function (canvas) {
                        var link = document.createElement('a');
                        link.download = 'slip-completed-' + filterTo + '.png';
                        link.href = canvas.toDataURL('image/png');
                        link.click();
                    }).catch(function () {
                        notify(@json(__('message.something_went_wrong')), 'error');
                    }).finally(function () {
                        $btn.prop('disabled', false);
                    });
                });

                $('.pds-os-settlement-table tbody tr[data-is-finished="0"]').each(function () {
                    syncRowFinishState($(this));
                });
                updateFinishAllState();
            });
        </script>
    @endsection
</x-master-layout>
