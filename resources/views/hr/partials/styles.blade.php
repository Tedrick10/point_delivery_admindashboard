<style>
    .pds-hr-page { --hr-orange: var(--site-color); --hr-ink: #0f172a; --hr-muted: #64748b; --hr-line: #e8edf5; }
    .pds-hr-hero {
        display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;
        background: linear-gradient(135deg, rgba(var(--brand-rgb), 0.08) 0%, #ffffff 58%);
        border: 1px solid #ffe4cc; border-radius: 20px; padding: 22px 24px; margin-bottom: 16px;
    }
    .pds-hr-hero__eyebrow {
        display: inline-flex; align-items: center; gap: 8px; color: var(--hr-orange); font-weight: 600; font-size: 13px; margin-bottom: 6px;
    }
    .pds-hr-hero__title { margin: 0; font-size: 26px; font-weight: 700; color: var(--hr-ink); }
    .pds-hr-hero__subtitle { margin: 6px 0 0; color: var(--hr-muted); max-width: 560px; }
    .pds-hr-hero__stats { display: flex; gap: 10px; flex-wrap: wrap; }
    .pds-hr-stat {
        min-width: 140px; background: linear-gradient(135deg, var(--site-color), var(--site-color)); color: #fff !important;
        border-radius: 16px; padding: 14px 18px; box-shadow: 0 10px 24px rgba(var(--brand-rgb), .22);
    }
    .pds-hr-stat--soft {
        background: #fff; color: var(--hr-ink) !important; border: 1px solid var(--hr-line); box-shadow: 0 8px 20px rgba(15, 23, 42, .04);
    }
    .pds-hr-stat--danger {
        background: linear-gradient(135deg, #dc2626, #ef4444);
        box-shadow: 0 10px 24px rgba(220, 38, 38, .22);
        color: #fff !important;
    }
    .pds-hr-stat__value { display: block; font-size: 24px; font-weight: 800; line-height: 1.1; }
    .pds-hr-stat__label { display: block; margin-top: 4px; font-size: 12px; opacity: .9; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; }
    .pds-hr-stat:not(.pds-hr-stat--soft),
    .pds-hr-stat:not(.pds-hr-stat--soft) .pds-hr-stat__value,
    .pds-hr-stat:not(.pds-hr-stat--soft) .pds-hr-stat__label,
    .pds-hr-stat:not(.pds-hr-stat--soft) span {
        color: #fff !important;
    }
    .pds-hr-stat--soft,
    .pds-hr-stat--soft .pds-hr-stat__value,
    .pds-hr-stat--soft .pds-hr-stat__label,
    .pds-hr-stat--soft span {
        color: var(--hr-ink) !important;
    }
    .pds-hr-toolbar {
        display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 14px;
    }
    .pds-hr-tabs { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .pds-hr-tab {
        display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--hr-line); background: #fff;
        color: #334155; border-radius: 999px; padding: 8px 14px; font-weight: 600; text-decoration: none; transition: .2s ease;
    }
    button.pds-hr-tab { cursor: pointer; }
    .pds-hr-tab:hover { border-color: #ffd8b0; color: var(--site-color); text-decoration: none; }
    .pds-hr-tab.is-active {
        background: linear-gradient(135deg, var(--site-color), var(--site-color)); border-color: transparent; color: #fff !important;
        box-shadow: 0 8px 18px rgba(var(--brand-rgb), .24);
    }
    .pds-hr-tab.is-active span {
        color: #fff !important;
    }
    .pds-hr-month {
        display: inline-flex; align-items: center; gap: 4px; background: #fff; border: 1px solid var(--hr-line);
        border-radius: 999px; padding: 4px;
    }
    .pds-hr-month--end { margin-left: auto; }
    .pds-hr-month__btn {
        width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; color: #475569; text-decoration: none;
    }
    .pds-hr-month__btn:hover { background: rgba(var(--brand-rgb), 0.08); color: var(--hr-orange); text-decoration: none; }
    .pds-hr-month__label { min-width: 130px; text-align: center; font-weight: 700; color: var(--hr-ink); }
    .pds-hr-summary {
        display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 14px;
    }
    .pds-hr-summary__card {
        background: #fff; border: 1px solid var(--hr-line); border-radius: 16px; padding: 16px 18px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .03);
    }
    .pds-hr-summary__label { display: block; color: var(--hr-muted); font-size: 12px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
    .pds-hr-summary__value { display: block; margin-top: 6px; font-size: 22px; font-weight: 800; color: var(--hr-ink); }
    .pds-hr-summary__card--danger .pds-hr-summary__value { color: #dc2626; }
    .pds-hr-summary__card--success .pds-hr-summary__value { color: #059669; }
    .pds-hr-panel {
        background: #fff; border: 1px solid var(--hr-line); border-radius: 20px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .04); overflow: hidden;
    }
    /* Sticky thead/tfoot need a scrollport without an overflow:hidden ancestor. */
    .pds-hr-panel--salary,
    .pds-hr-panel--late {
        overflow: visible;
        background: transparent;
        border: 0;
        box-shadow: none;
        border-radius: 0;
    }
    .pds-hr-side { padding: 18px; }
    .pds-hr-side__head { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; margin-bottom: 14px; }
    .pds-hr-side__head h5 { margin: 0; font-weight: 700; color: var(--hr-ink); }
    .pds-hr-side__head p { margin: 4px 0 0; color: var(--hr-muted); font-size: 13px; }
    .pds-hr-chip {
        background: rgba(var(--brand-rgb), 0.08); color: var(--site-color); border: 1px solid #ffd8b0; border-radius: 999px;
        padding: 6px 12px; font-weight: 700; white-space: nowrap;
    }
    .pds-hr-side__form { background: #f8fafc; border: 1px solid #eef2f7; border-radius: 14px; padding: 14px; margin-bottom: 14px; }
    .pds-hr-side__form .form-group { margin-bottom: 10px; }
    .pds-hr-side__form label { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
    .pds-hr-table { margin: 0; width: 100%; border-collapse: separate; border-spacing: 0; }
    .pds-hr-table thead th {
        background: #f8fafc; color: #64748b; font-size: 11px; letter-spacing: .06em; text-transform: uppercase;
        font-weight: 700; border-top: 0; border-bottom: 1px solid var(--hr-line); white-space: nowrap; padding: 12px 10px; vertical-align: middle;
    }

    /* —— Payroll salary sheets (frozen header + TOTAL) —— */
    .pds-hr-panel--salary,
    .pds-hr-panel--late {
        overflow: visible;
        background: transparent;
        border: 0;
        box-shadow: none;
        border-radius: 0;
    }
    .pds-hr-freeze-shell,
    .pds-hr-panel--salary .table-responsive,
    .pds-hr-panel--late .table-responsive {
        overflow-x: auto;
        overflow-y: hidden;
        max-height: none;
        position: relative;
        border-radius: 20px;
        border: 1px solid var(--hr-line);
        background: #fff;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .04);
        -webkit-overflow-scrolling: touch;
    }
    .pds-hr-table--salary,
    .pds-hr-table--late {
        min-width: 1100px;
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        margin: 0;
    }
    /* Classic freeze: only tbody scrolls vertically */
    .pds-hr-table--freeze {
        min-width: 1100px;
    }
    .pds-hr-table--freeze > thead,
    .pds-hr-table--freeze > tbody,
    .pds-hr-table--freeze > tfoot {
        display: block;
        width: 100%;
    }
    .pds-hr-table--freeze > tbody {
        max-height: min(58vh, 620px);
        overflow-y: auto;
        overflow-x: hidden;
    }
    .pds-hr-table--freeze > thead,
    .pds-hr-table--freeze > tfoot {
        overflow: hidden;
        flex-shrink: 0;
    }
    .pds-hr-table--freeze > thead > tr,
    .pds-hr-table--freeze > tbody > tr,
    .pds-hr-table--freeze > tfoot > tr {
        display: table;
        width: 100%;
        table-layout: fixed;
    }
    .pds-hr-table--salary thead th,
    .pds-hr-table--late thead th {
        position: static;
        top: auto;
        z-index: auto;
        background: #ffffff !important;
        color: #0f172a !important;
        text-align: center;
        white-space: normal;
        line-height: 1.25;
        font-size: 11px;
        letter-spacing: .02em;
        text-transform: none;
        font-weight: 700;
        padding: 14px 10px;
        border-bottom: 1px solid #e2e8f0 !important;
        vertical-align: middle;
        min-width: 0;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .06);
    }
    .pds-hr-table--salary thead th.pds-hr-col-no,
    .pds-hr-table--late thead th.pds-hr-col-no {
        width: 48px;
        left: auto;
        z-index: auto;
        background: #ffffff !important;
    }
    .pds-hr-table--salary thead th.pds-hr-col-name,
    .pds-hr-table--late thead th.pds-hr-col-name {
        text-align: left;
        width: 200px;
        left: auto;
        z-index: auto;
        white-space: nowrap;
        background: #ffffff !important;
    }
    .pds-hr-table--salary tbody td,
    .pds-hr-table--salary tfoot td,
    .pds-hr-table--late tbody td,
    .pds-hr-table--late tfoot td {
        text-align: center;
        vertical-align: middle;
        padding: 12px 10px;
        border-bottom: 1px solid #eef2f7;
        background: #fff;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .pds-hr-table--salary tbody tr:nth-child(even) td,
    .pds-hr-table--late tbody tr:nth-child(even) td {
        background: #fbfdff;
    }
    .pds-hr-table--salary tbody tr:hover td,
    .pds-hr-table--late tbody tr:hover td {
        background: rgba(var(--brand-rgb), 0.08) !important;
    }
    .pds-hr-table--salary tbody td.pds-hr-col-no,
    .pds-hr-table--salary tfoot td.pds-hr-col-no,
    .pds-hr-table--late tbody td.pds-hr-col-no,
    .pds-hr-table--late tfoot td.pds-hr-col-no {
        position: static;
        left: auto;
        z-index: auto;
        width: 48px;
        color: #94a3b8;
        font-weight: 700;
        background: inherit;
        box-shadow: none;
    }
    .pds-hr-table--salary tbody td.pds-hr-col-name,
    .pds-hr-table--salary tfoot td.pds-hr-col-name,
    .pds-hr-table--late tbody td.pds-hr-col-name,
    .pds-hr-table--late tfoot td.pds-hr-col-name {
        position: static;
        left: auto;
        z-index: auto;
        text-align: left;
        background: inherit;
        box-shadow: none;
        white-space: nowrap;
    }
    .pds-hr-table--salary tbody tr:nth-child(even) td.pds-hr-col-no,
    .pds-hr-table--salary tbody tr:nth-child(even) td.pds-hr-col-name,
    .pds-hr-table--late tbody tr:nth-child(even) td.pds-hr-col-no,
    .pds-hr-table--late tbody tr:nth-child(even) td.pds-hr-col-name {
        background: #fbfdff;
    }
    .pds-hr-table--salary tbody tr:hover td.pds-hr-col-no,
    .pds-hr-table--salary tbody tr:hover td.pds-hr-col-name,
    .pds-hr-table--late tbody tr:hover td.pds-hr-col-no,
    .pds-hr-table--late tbody tr:hover td.pds-hr-col-name {
        background: rgba(var(--brand-rgb), 0.08) !important;
    }
    .pds-hr-table--salary .pds-hr-cell,
    .pds-hr-table--late .pds-hr-cell {
        display: block;
        text-align: center;
        font-weight: 700;
        color: #334155;
        line-height: 1.2;
    }
    .pds-hr-table--salary .pds-hr-cell--muted,
    .pds-hr-table--late .pds-hr-cell--muted {
        color: #64748b;
        font-weight: 600;
    }
    .pds-hr-table--salary .pds-hr-cell--strong,
    .pds-hr-table--late .pds-hr-cell--strong {
        color: var(--hr-ink);
        font-weight: 800;
        font-size: 14px;
    }
    .pds-hr-table--salary .pds-hr-cell--danger,
    .pds-hr-table--late .pds-hr-cell--danger {
        color: #dc2626;
        font-weight: 800;
    }
    .pds-hr-table--salary .pds-hr-cell--success,
    .pds-hr-table--late .pds-hr-cell--success {
        color: #059669;
        font-weight: 800;
        font-size: 14px;
    }
    .pds-hr-table--salary .pds-hr-num,
    .pds-hr-table--salary .pds-hr-readonly,
    .pds-hr-table--late .pds-hr-num,
    .pds-hr-table--late .pds-hr-readonly {
        display: block;
        text-align: center;
        background: transparent;
        border: 0;
        border-radius: 0;
        padding: 0 !important;
        min-width: 0;
        font-weight: 700;
        color: #334155;
        box-shadow: none;
    }
    .pds-hr-table--salary .pds-hr-input,
    .pds-hr-table--late .pds-hr-input {
        display: block;
        width: 100%;
        max-width: 104px;
        margin: 0 auto;
        text-align: center;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px 6px;
        background: #fff;
        font-weight: 700;
        color: var(--hr-ink);
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, .03);
    }
    .pds-hr-table--salary .pds-hr-input:focus,
    .pds-hr-table--late .pds-hr-input:focus {
        outline: none;
        border-color: var(--site-color);
        box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .14);
    }
    .pds-hr-table--salary .pds-hr-input:disabled,
    .pds-hr-table--late .pds-hr-input:disabled {
        background: #f8fafc;
        color: #94a3b8;
    }
    .pds-hr-table--salary .pds-hr-person,
    .pds-hr-table--late .pds-hr-person {
        justify-content: flex-start;
        text-align: left;
        min-width: 0;
        gap: 8px;
    }
    .pds-hr-table--salary .pds-hr-avatar,
    .pds-hr-table--late .pds-hr-avatar {
        width: 32px;
        height: 32px;
        font-size: 13px;
    }
    .pds-hr-table--salary .pds-hr-badge,
    .pds-hr-table--late .pds-hr-badge {
        margin-top: 2px;
        font-size: 10px;
        padding: 1px 7px;
    }
    .pds-hr-table--salary tfoot,
    .pds-hr-table--late tfoot {
        position: static;
        bottom: auto;
        z-index: auto;
    }
    .pds-hr-table--salary tfoot tr.pds-hr-tfoot td,
    .pds-hr-table--late tfoot tr.pds-hr-tfoot td {
        position: static !important;
        bottom: auto !important;
        z-index: auto;
        background: linear-gradient(180deg, rgba(var(--brand-rgb), 0.08) 0%, rgba(var(--brand-rgb), 0.16) 100%) !important;
        border-top: 2px solid rgba(var(--brand-rgb), 0.45);
        border-bottom: 0;
        color: var(--site-color);
        font-weight: 800;
        padding-top: 14px;
        padding-bottom: 14px;
        box-shadow: 0 -4px 12px rgba(154, 52, 18, .08);
    }
    .pds-hr-table--salary tfoot tr.pds-hr-tfoot td.pds-hr-col-no,
    .pds-hr-table--salary tfoot tr.pds-hr-tfoot td.pds-hr-col-name,
    .pds-hr-table--late tfoot tr.pds-hr-tfoot td.pds-hr-col-no,
    .pds-hr-table--late tfoot tr.pds-hr-tfoot td.pds-hr-col-name {
        z-index: auto;
        background: linear-gradient(180deg, rgba(var(--brand-rgb), 0.08) 0%, rgba(var(--brand-rgb), 0.16) 100%) !important;
    }
    .pds-hr-table--salary .pds-hr-tfoot__label,
    .pds-hr-table--late .pds-hr-tfoot__label {
        text-align: left;
        font-weight: 800;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        font-size: 12px;
        color: var(--site-color);
    }
    .pds-hr-table--salary .pds-hr-th-sub,
    .pds-hr-table--late .pds-hr-th-sub {
        display: block;
        margin-top: 2px;
        font-size: 10px;
        font-weight: 600;
        opacity: .72;
        letter-spacing: 0;
    }
    .pds-hr-table thead th.pds-hr-th-fp {
        white-space: normal;
        text-transform: none;
        letter-spacing: 0;
        text-align: center;
        vertical-align: bottom;
        padding: 10px 12px 12px;
        background: linear-gradient(180deg, #fffaf5 0%, #f8fafc 100%);
        min-width: 118px;
    }
    .pds-hr-table thead th.pds-hr-th-fp--fine {
        min-width: 88px;
    }
    .pds-hr-th-stack {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        max-width: 160px;
    }
    .pds-hr-th-kicker {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 8px;
        border-radius: 999px;
        background: #fff;
        border: 1px solid #ffe4cc;
        color: var(--site-color);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        line-height: 1.2;
        white-space: nowrap;
    }
    .pds-hr-th-kicker i { font-size: 10px; opacity: .9; }
    .pds-hr-th-main {
        color: #0f172a;
        font-size: 12px;
        font-weight: 800;
        line-height: 1.3;
        letter-spacing: 0;
    }
    .pds-hr-th-fp--fine .pds-hr-th-kicker {
        border-color: #e2e8f0;
        background: #f8fafc;
        color: #64748b;
    }
    .pds-hr-table tbody td, .pds-hr-table tfoot td {
        border-color: #f1f5f9; padding: 10px; vertical-align: middle; white-space: nowrap;
    }
    .pds-hr-table tbody tr:hover { background: #fffaf5; }
    .pds-hr-table tfoot td { background: #f8fafc; font-weight: 700; }
    .pds-hr-table tfoot tr.pds-hr-tfoot td {
        background: linear-gradient(180deg, rgba(var(--brand-rgb), 0.08) 0%, rgba(var(--brand-rgb), 0.16) 100%);
        border-top: 2px solid rgba(var(--brand-rgb), 0.45);
        color: var(--site-color);
        padding-top: 12px;
        padding-bottom: 12px;
    }
    .pds-hr-tfoot__label {
        font-weight: 800;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        font-size: 12px;
    }
    .pds-hr-table--compact thead th, .pds-hr-table--compact tbody td { padding: 8px 10px; }
    .pds-hr-readonly {
        color: #334155;
        font-weight: 700;
        background: #f8fafc;
        border-radius: 10px;
        padding: 8px 10px !important;
        text-align: center;
        min-width: 64px;
    }
    .pds-hr-person { display: flex; align-items: center; gap: 10px; min-width: 160px; }
    .pds-hr-avatar {
        width: 36px; height: 36px; border-radius: 50%; display: grid; place-items: center;
        background: rgba(var(--brand-rgb), 0.08); color: var(--hr-orange); font-weight: 800; flex: 0 0 auto;
    }
    .pds-hr-avatar.is-rider { background: #eff6ff; color: #2563eb; }
    .pds-hr-person__name { font-weight: 700; color: var(--hr-ink); line-height: 1.2; }
    .pds-hr-badge {
        display: inline-flex; margin-top: 3px; font-size: 11px; font-weight: 700; color: var(--site-color);
        background: rgba(var(--brand-rgb), 0.08); border-radius: 999px; padding: 2px 8px;
    }
    .pds-hr-badge.is-rider { color: #1d4ed8; background: #eff6ff; }
    .pds-hr-input {
        width: 88px; max-width: 100%; border: 1px solid #e2e8f0; border-radius: 10px; padding: 6px 8px;
        background: #fff; font-weight: 600; color: var(--hr-ink); text-align: right;
    }
    .pds-hr-input:focus { outline: none; border-color: var(--site-color); box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .12); }
    .pds-hr-input:disabled { background: #f8fafc; color: #94a3b8; }
    .pds-hr-readonly {
        color: #334155;
        font-weight: 700;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 6px 8px !important;
        text-align: right;
        min-width: 64px;
        font-variant-numeric: tabular-nums;
    }
    .pds-hr-num { text-align: right; font-variant-numeric: tabular-nums; color: #334155; font-weight: 600; }
    .pds-hr-num--strong { color: var(--hr-ink); font-weight: 800; }
    .pds-hr-num--accent { color: var(--hr-orange); font-weight: 800; }
    .pds-hr-num--success { color: #059669; font-weight: 800; }
    .pds-hr-empty { text-align: center !important; color: var(--hr-muted); padding: 36px 16px !important; white-space: normal !important; }
    .pds-hr-icon-btn { color: #dc2626; padding: 4px 6px; }
    .pds-hr-icon-btn:hover { color: #b91c1c; }
    .pds-hr-table--salary thead th.pds-hr-col-rest,
    .pds-hr-table--salary tbody td.pds-hr-col-rest,
    .pds-hr-table--salary tfoot td.pds-hr-col-rest {
        min-width: 108px;
        width: 108px;
        overflow: visible;
    }
    .pds-hr-rest-cell {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .pds-hr-rest-cell__count {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
        font-variant-numeric: tabular-nums;
        min-width: 1.2em;
        text-align: center;
    }
    .pds-hr-rest-btn {
        width: 32px;
        height: 32px;
        border: 1px solid rgba(var(--brand-rgb), 0.45);
        border-radius: 10px;
        background: linear-gradient(180deg, rgba(var(--brand-rgb), 0.08), rgba(var(--brand-rgb), 0.16));
        color: var(--site-color);
        display: inline-grid;
        place-items: center;
        cursor: pointer;
        transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
        box-shadow: 0 2px 6px rgba(194, 65, 12, .08);
    }
    .pds-hr-rest-btn:hover,
    .pds-hr-rest-btn.is-open {
        background: linear-gradient(180deg, rgba(var(--brand-rgb), 0.16), rgba(var(--brand-rgb), 0.28));
        box-shadow: 0 6px 14px rgba(194, 65, 12, .16);
        transform: translateY(-1px);
        color: var(--site-color);
    }
    .pds-hr-rest-btn i { font-size: 13px; }
    .pds-hr-rest-popover {
        position: fixed;
        z-index: 1080;
        width: 240px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, .16);
        padding: 12px 14px 14px;
    }
    .pds-hr-rest-popover[hidden] { display: none !important; }
    .pds-hr-rest-popover__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 2px;
    }
    .pds-hr-rest-popover__head strong {
        font-size: 14px;
        font-weight: 800;
        color: #0f172a;
    }
    .pds-hr-rest-popover__close {
        width: 28px;
        height: 28px;
        border: 0;
        border-radius: 8px;
        background: #f1f5f9;
        color: #64748b;
        font-size: 18px;
        line-height: 1;
        cursor: pointer;
    }
    .pds-hr-rest-popover__close:hover { background: #e2e8f0; color: #0f172a; }
    .pds-hr-rest-popover__sub {
        margin: 0 0 10px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #94a3b8;
    }
    .pds-hr-rest-popover__list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 6px;
        max-height: 220px;
        overflow: auto;
    }
    .pds-hr-rest-popover__list li {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 8px 10px;
        border-radius: 10px;
        background: rgba(var(--brand-rgb), 0.08);
        border: 1px solid rgba(var(--brand-rgb), 0.16);
        color: var(--site-color);
        font-weight: 700;
        font-size: 13px;
        font-variant-numeric: tabular-nums;
    }
    .pds-hr-rest-popover__list li span {
        font-size: 11px;
        font-weight: 800;
        color: var(--site-color);
        background: #fff;
        border-radius: 999px;
        padding: 2px 8px;
        border: 1px solid rgba(var(--brand-rgb), 0.28);
    }
    .pds-hr-rest-popover__empty {
        margin: 0;
        padding: 16px 8px;
        text-align: center;
        color: #94a3b8;
        font-size: 13px;
        font-weight: 600;
    }
    .pds-hr-rest-popover__empty[hidden],
    .pds-hr-rest-popover__list[hidden] { display: none !important; }
    .pds-hr-formula-note {
        background: rgba(var(--brand-rgb), 0.08); border: 1px solid #ffd8b0; color: var(--site-color);
        border-radius: 14px; padding: 10px 14px; margin-bottom: 14px; font-size: 13px; font-weight: 600;
    }
    .pds-hr-table--late {
        min-width: 1280px;
    }
    .pds-hr-table--late .pds-hr-input--wide {
        max-width: 128px;
        min-width: 108px;
    }
    .pds-hr-table--late tfoot .pds-hr-col-name[colspan] {
        text-align: right;
        padding-right: 16px;
    }

    .pds-hr-extra {
        padding: 0;
        overflow: hidden;
    }
    .pds-hr-extra__head {
        display: flex; justify-content: space-between; gap: 16px; align-items: center;
        flex-wrap: wrap;
        padding: 20px 22px 18px;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border-bottom: 0;
    }
    .pds-hr-extra__title-wrap { display: flex; align-items: flex-start; gap: 14px; }
    .pds-hr-extra__icon {
        width: 44px; height: 44px; border-radius: 14px; display: grid; place-items: center; flex: 0 0 auto;
        background: linear-gradient(135deg, var(--site-color), var(--site-color)); color: #fff;
        box-shadow: 0 10px 20px rgba(var(--brand-rgb), .28); font-size: 16px;
    }
    .pds-hr-extra--bag .pds-hr-extra__icon {
        background: linear-gradient(135deg, #0ea5e9, #38bdf8);
        box-shadow: 0 10px 20px rgba(14, 165, 233, .28);
    }
    .pds-hr-extra__head h5,
    .pds-hr-page .pds-hr-extra__head h5,
    .pds-hr-extra__head h5.card-title,
    .pds-hr-extra__title {
        margin: 0 !important;
        font-weight: 800 !important;
        color: #ffffff !important;
        font-size: 18px !important;
        letter-spacing: -.01em;
        opacity: 1 !important;
    }
    .pds-hr-extra__head p,
    .pds-hr-page .pds-hr-extra__head p,
    .pds-hr-extra__hint {
        margin: 4px 0 0 !important;
        color: rgba(248, 250, 252, 0.85) !important;
        font-size: 13px !important;
        max-width: 460px;
        line-height: 1.45;
        opacity: 1 !important;
    }
    .pds-hr-extra__total-pill {
        display: inline-flex; flex-direction: column; align-items: flex-end; gap: 2px;
        background: rgba(255, 255, 255, .1);
        border: 1px solid rgba(255, 255, 255, .18); border-radius: 16px; padding: 10px 16px;
        backdrop-filter: blur(6px);
    }
    .pds-hr-extra__total-pill-label {
        font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
        color: rgba(248, 250, 252, .78) !important;
    }
    .pds-hr-extra__total-pill-value {
        font-size: 22px; font-weight: 800; color: rgba(var(--brand-rgb), 0.45) !important; line-height: 1.1; font-variant-numeric: tabular-nums;
    }
    .pds-hr-extra--bag .pds-hr-extra__total-pill-value { color: #7dd3fc !important; }
    .pds-hr-extra__form {
        display: grid;
        grid-template-columns: minmax(150px, 200px) minmax(140px, 160px) minmax(200px, 1fr) minmax(110px, 140px) auto;
        gap: 12px; align-items: end;
        background: #f8fafc; border: 0; border-bottom: 1px solid #eef2f7; border-radius: 0;
        padding: 18px 22px; margin-bottom: 0;
    }
    .pds-hr-extra__field label {
        display: flex; align-items: center; gap: 6px; margin-bottom: 7px;
        font-size: 11px; font-weight: 700; color: #64748b; letter-spacing: .05em; text-transform: uppercase;
    }
    .pds-hr-extra__field label i { color: #94a3b8; font-size: 10px; }
    .pds-hr-extra__control {
        width: 100%; height: 44px; border: 1px solid #e2e8f0; border-radius: 12px;
        background: #fff; padding: 0 14px; font-weight: 600; color: var(--hr-ink);
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .pds-hr-extra__control::placeholder { color: #94a3b8; font-weight: 500; }
    .pds-hr-extra__control:focus {
        outline: none; border-color: var(--site-color); box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .12);
    }
    .pds-hr-extra__form .select2-container { width: 100% !important; }
    .pds-hr-extra__form .select2-container--default .select2-selection--single {
        height: 44px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff;
        display: flex; align-items: center; padding: 0 8px 0 4px;
    }
    .pds-hr-extra__form .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--hr-ink); font-weight: 600; line-height: 42px; padding-left: 10px; padding-right: 28px;
    }
    .pds-hr-extra__form .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #94a3b8; font-weight: 500;
    }
    .pds-hr-extra__form .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 42px; right: 8px;
    }
    .pds-hr-extra__form .select2-container--default.select2-container--focus .select2-selection--single,
    .pds-hr-extra__form .select2-container--default.select2-container--open .select2-selection--single {
        border-color: var(--site-color); box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .12);
    }
    .pds-hr-extra__form .select2-container--default .select2-selection--single .select2-selection__clear {
        margin-right: 18px; font-weight: 700; color: #94a3b8;
    }
    .pds-hr-extra__form .select2-dropdown {
        border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden;
        box-shadow: 0 16px 32px rgba(15, 23, 42, .12); margin-top: 6px;
    }
    .pds-hr-extra__form .select2-search--dropdown { padding: 10px; }
    .pds-hr-extra__form .select2-search--dropdown .select2-search__field {
        border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 12px; font-weight: 600;
    }
    .pds-hr-extra__form .select2-search--dropdown .select2-search__field:focus {
        outline: none; border-color: var(--site-color); box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .12);
    }
    .pds-hr-extra__form .select2-results__option { padding: 10px 14px; font-weight: 600; }
    .pds-hr-extra__form .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background: rgba(var(--brand-rgb), 0.08); color: var(--site-color);
    }
    .pds-hr-extra__form .select2-container--default .select2-results__option[aria-selected=true] {
        background: rgba(var(--brand-rgb), 0.16); color: var(--site-color);
    }
    .pds-hr-extra__field--amount .pds-hr-extra__control { text-align: center; font-variant-numeric: tabular-nums; }
    .pds-hr-extra__add-btn {
        height: 44px; border: 0; border-radius: 12px; padding: 0 18px;
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        background: linear-gradient(135deg, var(--site-color), var(--site-color)); color: #fff;
        font-weight: 700; box-shadow: 0 10px 18px rgba(var(--brand-rgb), .22);
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .pds-hr-extra--bag .pds-hr-extra__add-btn {
        background: linear-gradient(135deg, #0284c7, #38bdf8);
        box-shadow: 0 10px 18px rgba(2, 132, 199, .22);
    }
    .pds-hr-extra__add-btn:hover {
        color: #fff; transform: translateY(-1px);
        box-shadow: 0 12px 22px rgba(var(--brand-rgb), .28);
    }
    .pds-hr-extra--bag .pds-hr-extra__add-btn:hover {
        box-shadow: 0 12px 22px rgba(2, 132, 199, .28);
    }
    .pds-hr-extra__toolbar {
        display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px;
        padding: 14px 22px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
    }
    .pds-hr-extra__filter-field {
        display: flex; flex-direction: column; gap: 6px; min-width: 160px;
    }
    .pds-hr-extra__filter-field--search { flex: 1 1 220px; min-width: 200px; }
    .pds-hr-extra__filter-field label {
        margin: 0; font-size: 11px; font-weight: 700; letter-spacing: .04em;
        text-transform: uppercase; color: #64748b;
    }
    .pds-hr-extra__filter-field label i { color: #94a3b8; margin-right: 4px; }
    .pds-hr-extra__toolbar .pds-hr-extra__control {
        height: 40px; border: 1px solid #dbe3ee; border-radius: 12px;
        background: #fff; padding: 0 12px; font-weight: 600; color: #0f172a;
    }
    .pds-hr-extra__toolbar .pds-hr-extra__control:focus {
        outline: none; border-color: var(--site-color); box-shadow: 0 0 0 3px rgba(251, 146, 60, .18);
    }
    .pds-hr-extra--bag .pds-hr-extra__toolbar .pds-hr-extra__control:focus {
        border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56, 189, 248, .18);
    }
    .pds-hr-extra__filter-clear {
        height: 40px; border: 1px solid #e2e8f0; border-radius: 12px;
        background: #fff; color: #64748b; font-weight: 700; font-size: 13px;
        padding: 0 14px; cursor: pointer; transition: .15s ease;
    }
    .pds-hr-extra__filter-clear:hover {
        background: rgba(var(--brand-rgb), 0.08); border-color: rgba(var(--brand-rgb), 0.45); color: var(--site-color);
    }
    .pds-hr-extra--bag .pds-hr-extra__filter-clear:hover {
        background: #f0f9ff; border-color: #7dd3fc; color: #0369a1;
    }
    .pds-hr-extra__list {
        border: 0; border-radius: 0; overflow: hidden; margin-bottom: 0; background: #fff;
    }
    .pds-hr-extra__list-head,
    .pds-hr-extra__row,
    .pds-hr-extra__footer {
        display: grid;
        grid-template-columns: 48px minmax(140px, 200px) 120px minmax(180px, 1fr) 120px 52px;
        gap: 12px; align-items: center; padding: 13px 22px;
    }
    .pds-hr-extra__list.is-readonly .pds-hr-extra__list-head,
    .pds-hr-extra__list.is-readonly .pds-hr-extra__row,
    .pds-hr-extra__list.is-readonly .pds-hr-extra__footer {
        grid-template-columns: 48px minmax(140px, 200px) 120px minmax(180px, 1fr) 120px;
    }
    .pds-hr-extra__list-head {
        background: #f1f5f9; border-bottom: 1px solid #e2e8f0;
        font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #64748b;
        text-align: center;
    }
    .pds-hr-extra__list-head .pds-hr-extra__col-name { text-align: left; }
    .pds-hr-extra__list-head .pds-hr-extra__col-about { text-align: left; }
    .pds-hr-extra__row {
        border-bottom: 1px solid #f1f5f9; transition: background .15s ease;
    }
    .pds-hr-extra__row:nth-child(even) { background: #fbfdff; }
    .pds-hr-extra__row:last-child { border-bottom: 0; }
    .pds-hr-extra__row:hover { background: #fffaf5; }
    .pds-hr-extra__col-no { color: #94a3b8; font-weight: 700; text-align: center; }
    .pds-hr-extra__col-name { display: inline-flex; align-items: center; gap: 10px; min-width: 0; }
    .pds-hr-extra__avatar {
        width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; flex: 0 0 auto;
        background: rgba(var(--brand-rgb), 0.08); color: var(--hr-orange); font-size: 12px; font-weight: 800;
    }
    .pds-hr-extra--bag .pds-hr-extra__avatar {
        background: #e0f2fe; color: #0284c7;
    }
    .pds-hr-extra__name {
        font-weight: 700; color: var(--hr-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .pds-hr-extra__col-date {
        color: #475569; font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; text-align: center;
    }
    .pds-hr-extra__col-about {
        color: #334155; font-weight: 600; line-height: 1.4; white-space: normal;
    }
    .pds-hr-extra__col-fine { text-align: center; }
    .pds-hr-extra__amount {
        display: inline-flex; min-width: 88px; justify-content: center;
        background: transparent; color: var(--site-color); border-radius: 0;
        padding: 0; font-weight: 800; font-variant-numeric: tabular-nums; font-size: 14px;
    }
    .pds-hr-extra--bag .pds-hr-extra__amount { color: #0369a1; }
    .pds-hr-extra__col-action { text-align: center; }
    .pds-hr-extra__delete {
        width: 34px; height: 34px; border-radius: 10px; display: inline-grid; place-items: center;
        color: #dc2626; background: #fef2f2; text-decoration: none; transition: .15s ease;
    }
    .pds-hr-extra__delete:hover { background: #fee2e2; color: #b91c1c; text-decoration: none; }
    .pds-hr-extra__empty {
        text-align: center; padding: 44px 20px; color: var(--hr-muted);
    }
    .pds-hr-extra__empty-icon {
        width: 52px; height: 52px; margin: 0 auto 12px; border-radius: 16px;
        display: grid; place-items: center; background: #f8fafc; color: #94a3b8; font-size: 18px;
    }
    .pds-hr-extra__empty strong { display: block; color: var(--hr-ink); margin-bottom: 4px; }
    .pds-hr-extra__empty p { margin: 0; font-size: 13px; }
    .pds-hr-extra__footer {
        background: linear-gradient(180deg, rgba(var(--brand-rgb), 0.08) 0%, rgba(var(--brand-rgb), 0.16) 100%);
        border-top: 2px solid rgba(var(--brand-rgb), 0.45);
        font-weight: 700; color: var(--site-color);
    }
    .pds-hr-extra--bag .pds-hr-extra__footer {
        background: linear-gradient(180deg, #f0f9ff 0%, #e0f2fe 100%);
        border-top-color: #7dd3fc;
        color: #075985;
    }
    .pds-hr-extra__footer span { grid-column: 1 / 5; text-align: right; }
    .pds-hr-extra__footer strong {
        grid-column: 5; justify-self: center;
        min-width: 96px; text-align: center;
        background: transparent; color: inherit; border-radius: 0;
        padding: 0; font-size: 16px; font-variant-numeric: tabular-nums; font-weight: 800;
    }
    .pds-hr-my-salary { padding: 22px; }
    .pds-hr-my-salary__grid {
        display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px;
    }
    .pds-hr-my-salary__item {
        background: #f8fafc; border: 1px solid #eef2f7; border-radius: 16px; padding: 14px 16px;
    }
    .pds-hr-my-salary__item span {
        display: block; font-size: 11px; font-weight: 700; letter-spacing: .05em;
        text-transform: uppercase; color: var(--hr-muted); margin-bottom: 6px;
    }
    .pds-hr-my-salary__item strong {
        display: block; font-size: 20px; font-weight: 800; color: var(--hr-ink);
        font-variant-numeric: tabular-nums;
    }
    .pds-hr-my-salary__item--accent {
        background: linear-gradient(135deg, rgba(var(--brand-rgb), 0.08), #ffffff); border-color: #ffd8b0;
    }
    .pds-hr-my-salary__item--accent strong { color: var(--hr-orange); }
    @media (max-width: 991px) {
        .pds-hr-summary { grid-template-columns: 1fr; }
        .pds-hr-hero__title { font-size: 22px; }
        .pds-hr-extra__form { grid-template-columns: 1fr; }
        .pds-hr-extra__toolbar { padding: 12px 16px; }
        .pds-hr-extra__filter-field,
        .pds-hr-extra__filter-field--search,
        .pds-hr-extra__filter-clear { width: 100%; min-width: 0; }
        .pds-hr-extra__add-btn { width: 100%; }
        .pds-hr-extra__list-head { display: none; }
        .pds-hr-extra__row,
        .pds-hr-extra__footer {
            grid-template-columns: 1fr auto;
            gap: 8px 12px;
        }
        .pds-hr-extra__col-no { display: none; }
        .pds-hr-extra__col-name { grid-column: 1 / 2; }
        .pds-hr-extra__col-fine { grid-column: 2; grid-row: 1; }
        .pds-hr-extra__col-date { grid-column: 1 / -1; color: #64748b; font-size: 13px; }
        .pds-hr-extra__col-about { grid-column: 1 / -1; }
        .pds-hr-extra__col-action { grid-column: 2; grid-row: 1; align-self: start; margin-top: 36px; }
        .pds-hr-extra__footer span { grid-column: 1; text-align: left; }
        .pds-hr-extra__footer strong { grid-column: 2; }
        .pds-hr-my-salary__grid { grid-template-columns: 1fr 1fr; }
        .pds-point-salary__form { grid-template-columns: 1fr; }
        .pds-point-salary__go { width: 100%; }
        .pds-point-salary__head { gap: 12px; }
    }

    .pds-hr-my-salary__item--point {
        appearance: none;
        -webkit-appearance: none;
        width: 100%;
        text-align: left;
        cursor: pointer;
        background: linear-gradient(135deg, rgba(var(--brand-rgb), 0.12), #ffffff);
        border: 1px solid #ffd8b0;
        transition: transform .12s ease, box-shadow .15s ease, border-color .15s ease;
    }
    .pds-hr-my-salary__item--point strong { color: var(--site-color); }
    .pds-hr-my-salary__item--point em {
        display: block; margin-top: 6px; font-style: normal;
        font-size: 11px; font-weight: 700; color: var(--site-color); opacity: .85;
    }
    .pds-hr-my-salary__item--point:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(var(--brand-rgb), .16);
        border-color: var(--site-color);
    }

    body.pds-point-modal-open { overflow: hidden; }
    .pds-point-modal {
        position: fixed; inset: 0; z-index: 1200;
        display: flex; align-items: center; justify-content: center; padding: 1.25rem;
    }
    .pds-point-modal[hidden] { display: none !important; }
    .pds-point-modal__backdrop {
        position: absolute; inset: 0;
        background: rgba(15, 23, 42, 0.52);
        backdrop-filter: blur(6px);
        opacity: 0; transition: opacity .2s ease;
    }
    .pds-point-modal.is-open .pds-point-modal__backdrop { opacity: 1; }
    .pds-point-modal__panel {
        position: relative; z-index: 1;
        width: min(100%, 400px);
        background: #fff;
        border: 1px solid rgba(var(--brand-rgb), 0.18);
        border-radius: 24px;
        box-shadow: 0 28px 64px rgba(15, 23, 42, .24);
        overflow: hidden;
        transform: translateY(14px) scale(.97); opacity: 0;
        transition: transform .2s ease, opacity .2s ease;
    }
    .pds-point-modal.is-open .pds-point-modal__panel {
        transform: translateY(0) scale(1); opacity: 1;
    }
    .pds-point-modal__x {
        position: absolute; top: 14px; right: 14px; z-index: 2;
        width: 34px; height: 34px; border: 0; border-radius: 999px;
        background: #fff; color: var(--site-color);
        border: 1px solid #ffd8b0;
        display: inline-flex; align-items: center; justify-content: center; cursor: pointer;
        transition: background .15s ease, color .15s ease;
    }
    .pds-point-modal__x:hover { background: var(--site-color); color: #fff; border-color: transparent; }
    .pds-point-modal__top {
        display: grid; justify-items: center; text-align: center; gap: 10px;
        padding: 28px 24px 18px;
        background:
            radial-gradient(120% 80% at 50% 0%, rgba(var(--brand-rgb), 0.16) 0%, transparent 62%),
            linear-gradient(180deg, #fff8f1 0%, #fff 100%);
        border-bottom: 1px solid #ffe8d4;
    }
    .pds-point-modal__icon {
        width: 56px; height: 56px; border-radius: 18px;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(145deg, var(--site-color), #ff8a3d);
        color: #fff; font-size: 22px;
        box-shadow: 0 12px 24px rgba(var(--brand-rgb), .32);
    }
    .pds-point-modal__top h5 {
        margin: 0; font-size: 18px; font-weight: 800; color: var(--hr-ink); letter-spacing: -0.02em;
    }
    .pds-point-modal__bal {
        display: inline-flex; flex-direction: column; align-items: center; gap: 2px;
        padding: 10px 18px; border-radius: 16px;
        background: #fff; border: 1px solid #ffd8b0;
        box-shadow: 0 6px 16px rgba(var(--brand-rgb), .1);
        min-width: 140px;
    }
    .pds-point-modal__bal span {
        font-size: 10px; font-weight: 800; letter-spacing: .08em;
        text-transform: uppercase; color: var(--hr-muted);
    }
    .pds-point-modal__bal strong {
        font-size: 26px; font-weight: 800; color: var(--site-color);
        font-variant-numeric: tabular-nums; line-height: 1.1;
    }
    .pds-point-modal__bal small {
        font-size: 12px; font-weight: 700; color: var(--hr-muted); text-transform: uppercase;
    }
    .pds-point-modal__form {
        display: grid; gap: 14px; padding: 20px 22px 22px;
    }
    .pds-point-modal__field {
        display: grid; gap: 7px; margin: 0;
    }
    .pds-point-modal__field span {
        font-size: 11px; font-weight: 800; letter-spacing: .06em;
        text-transform: uppercase; color: var(--hr-muted);
    }
    .pds-point-modal__field input {
        width: 100%; min-height: 48px; border: 1px solid #e8edf5; border-radius: 14px;
        background: #f8fafc; padding: 0 14px; font: inherit; font-weight: 700;
        color: var(--hr-ink); outline: 0;
        transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
    }
    .pds-point-modal__field input:focus {
        background: #fff;
        border-color: rgba(var(--brand-rgb), .55);
        box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .14);
    }
    .pds-point-modal__field input[name="points"] {
        font-size: 22px; font-weight: 800; color: var(--site-color); text-align: center;
        letter-spacing: -0.02em;
        -moz-appearance: textfield; appearance: textfield;
    }
    .pds-point-modal__field input[name="points"]::-webkit-outer-spin-button,
    .pds-point-modal__field input[name="points"]::-webkit-inner-spin-button {
        -webkit-appearance: none; margin: 0;
    }
    .pds-point-modal__submit {
        margin-top: 4px;
        min-height: 50px; border: 0; border-radius: 14px;
        background: linear-gradient(135deg, var(--site-color), #ff8a3d);
        color: #fff; font: inherit; font-size: 15px; font-weight: 800;
        cursor: pointer;
        box-shadow: 0 12px 26px rgba(var(--brand-rgb), .3);
        transition: filter .15s ease, transform .12s ease, box-shadow .15s ease;
    }
    .pds-point-modal__submit:hover {
        filter: brightness(0.97);
        box-shadow: 0 14px 30px rgba(var(--brand-rgb), .36);
        color: #fff;
    }
    .pds-point-modal__submit:active { transform: translateY(1px); }
    .pds-point-modal__icon--warn {
        background: linear-gradient(145deg, #dc2626, #f97316) !important;
        box-shadow: 0 12px 24px rgba(220, 38, 38, .28) !important;
    }
    .pds-point-modal__top--empty { padding-bottom: 8px; }
    .pds-point-modal__hint {
        margin: 0;
        font-size: 13px;
        font-weight: 600;
        color: var(--hr-muted);
    }
    .pds-point-modal__hint strong { color: #dc2626; font-weight: 800; }
    .pds-point-reqs {
        margin-top: 14px;
        background: #fff;
        border: 1px solid #ffd8b0;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 12px 28px rgba(var(--brand-rgb), .08);
    }
    .pds-point-reqs__head {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 14px 18px;
        background: linear-gradient(135deg, rgba(var(--brand-rgb), 0.12) 0%, #fff 70%);
        border-bottom: 1px solid #ffe4cc;
    }
    .pds-point-reqs__title {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 12px; font-weight: 800; letter-spacing: .05em;
        text-transform: uppercase; color: var(--hr-ink);
    }
    .pds-point-reqs__title i { color: var(--site-color); }
    .pds-point-reqs__head b {
        min-width: 28px; height: 28px; border-radius: 999px;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(var(--brand-rgb), 0.12); color: var(--site-color);
        font-size: 12px; font-weight: 800;
    }
    .pds-point-reqs__list {
        display: grid; gap: 10px; padding: 14px 16px 16px;
    }
    .pds-point-reqs__card {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 13px 14px;
        border-radius: 16px;
        background: #fffaf5;
        border: 1px solid #ffe4cc;
        transition: transform .12s ease, box-shadow .15s ease;
    }
    .pds-point-reqs__card:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(var(--brand-rgb), .1);
    }
    .pds-point-reqs__card--ok {
        background: #f0fdf4; border-color: #bbf7d0;
    }
    .pds-point-reqs__card--no {
        background: #fef2f2; border-color: #fecaca;
    }
    .pds-point-reqs__icon {
        width: 40px; height: 40px; border-radius: 13px; flex: 0 0 auto;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(145deg, var(--site-color), #ff8a3d);
        color: #fff; font-size: 14px;
        box-shadow: 0 8px 16px rgba(var(--brand-rgb), .24);
        margin-top: 1px;
    }
    .pds-point-reqs__card--ok .pds-point-reqs__icon {
        background: linear-gradient(145deg, #059669, #10b981);
        box-shadow: 0 8px 16px rgba(5, 150, 105, .22);
    }
    .pds-point-reqs__card--no .pds-point-reqs__icon {
        background: linear-gradient(145deg, #dc2626, #ef4444);
        box-shadow: 0 8px 16px rgba(220, 38, 38, .22);
    }
    .pds-point-reqs__body { flex: 1 1 auto; min-width: 0; display: grid; gap: 4px; }
    .pds-point-reqs__line {
        display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
    }
    .pds-point-reqs__line strong {
        font-size: 18px; font-weight: 800; color: var(--site-color);
        font-variant-numeric: tabular-nums; letter-spacing: -0.02em;
    }
    .pds-point-reqs__card--ok .pds-point-reqs__line strong { color: #047857; }
    .pds-point-reqs__card--no .pds-point-reqs__line strong { color: #b91c1c; }
    .pds-point-reqs__unit {
        font-size: 11px; font-weight: 700; color: var(--hr-muted);
        text-transform: uppercase;
    }
    .pds-point-reqs__tag {
        margin-left: auto;
        display: inline-flex; align-items: center;
        padding: 4px 10px; border-radius: 999px;
        font-size: 11px; font-weight: 800;
    }
    .pds-point-reqs__tag--wait {
        background: rgba(var(--brand-rgb), .14); color: var(--site-color);
    }
    .pds-point-reqs__tag--ok { background: #d1fae5; color: #047857; }
    .pds-point-reqs__tag--no { background: #fee2e2; color: #b91c1c; }
    .pds-point-reqs__meta {
        display: flex; flex-wrap: wrap; gap: 6px 12px;
        font-size: 12px; font-weight: 600; color: var(--hr-muted);
    }
    .pds-point-reqs__note {
        display: inline-flex; align-items: flex-start; gap: 8px;
        margin: 2px 0 0;
        max-width: 100%;
        width: fit-content;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.04);
        border: 1px dashed rgba(15, 23, 42, 0.1);
        color: #3f3a36;
        font-size: 12.5px;
        font-weight: 600;
        line-height: 1.35;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .pds-point-reqs__note > i {
        margin-top: 3px;
        color: var(--site-color);
        font-size: 9px;
        flex: 0 0 auto;
        opacity: 0.85;
    }
    .pds-point-reqs__card--ok .pds-point-reqs__note > i { color: #059669; }
    .pds-point-reqs__card--no .pds-point-reqs__note > i { color: #dc2626; }
    .pds-point-reqs__note span { min-width: 0; }
    .pds-point-reqs__meta span + span::before {
        content: "·"; margin-right: 12px; color: #cbd5e1;
    }

    /* Point → Salary request card */
    .pds-point-salary {
        margin-top: 14px;
        background: #fff;
        border: 1px solid #ffd8b0;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 12px 28px rgba(var(--brand-rgb), .08);
    }
    .pds-point-salary__head {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 18px 20px;
        background: linear-gradient(135deg, rgba(var(--brand-rgb), 0.12) 0%, #fff 70%);
        border-bottom: 1px solid #ffe4cc;
    }
    .pds-point-salary__badge {
        width: 44px; height: 44px; border-radius: 14px;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, var(--site-color), var(--site-color));
        color: #fff; font-size: 18px;
        box-shadow: 0 8px 18px rgba(var(--brand-rgb), .28);
        flex: 0 0 auto;
    }
    .pds-point-salary__copy { flex: 1 1 auto; min-width: 0; }
    .pds-point-salary__eyebrow {
        display: block; font-size: 11px; font-weight: 800; letter-spacing: .06em;
        text-transform: uppercase; color: var(--site-color); margin-bottom: 2px;
    }
    .pds-point-salary__copy h5 {
        margin: 0; font-size: 18px; font-weight: 800; color: var(--hr-ink);
    }
    .pds-point-salary__balance {
        display: inline-flex; align-items: baseline; gap: 6px;
        padding: 10px 14px; border-radius: 999px;
        background: #fff; border: 1px solid #ffd8b0;
        box-shadow: 0 4px 12px rgba(var(--brand-rgb), .1);
    }
    .pds-point-salary__balance strong {
        font-size: 22px; font-weight: 800; color: var(--site-color);
        font-variant-numeric: tabular-nums; line-height: 1;
    }
    .pds-point-salary__balance small {
        font-size: 12px; font-weight: 700; color: var(--hr-muted); text-transform: uppercase;
    }
    .pds-point-salary__form {
        display: grid;
        grid-template-columns: minmax(120px, 180px) minmax(0, 1fr) auto;
        gap: 12px;
        align-items: end;
        padding: 18px 20px;
    }
    .pds-point-salary__field {
        display: grid; gap: 6px; margin: 0; min-width: 0;
    }
    .pds-point-salary__field span {
        font-size: 11px; font-weight: 800; letter-spacing: .05em;
        text-transform: uppercase; color: var(--hr-muted);
    }
    .pds-point-salary__field input {
        width: 100%; min-height: 46px; border: 1px solid #e8edf5; border-radius: 14px;
        background: #fff; padding: 0 14px; font: inherit; font-weight: 700; color: var(--hr-ink);
        outline: 0; transition: border-color .15s ease, box-shadow .15s ease;
    }
    .pds-point-salary__field input:focus {
        border-color: rgba(var(--brand-rgb), .55);
        box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .14);
    }
    .pds-point-salary__field input[name="points"] {
        font-size: 18px; font-weight: 800; color: var(--site-color);
        -moz-appearance: textfield; appearance: textfield;
    }
    .pds-point-salary__field input[name="points"]::-webkit-outer-spin-button,
    .pds-point-salary__field input[name="points"]::-webkit-inner-spin-button {
        -webkit-appearance: none; margin: 0;
    }
    .pds-point-salary__go {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        min-height: 46px; border: 0; border-radius: 14px; padding: 0 18px;
        background: linear-gradient(135deg, var(--site-color), var(--site-color));
        color: #fff; font: inherit; font-weight: 800; white-space: nowrap; cursor: pointer;
        box-shadow: 0 10px 22px rgba(var(--brand-rgb), .28);
        transition: transform .12s ease, box-shadow .15s ease, filter .15s ease;
    }
    .pds-point-salary__go:hover {
        filter: brightness(0.96);
        box-shadow: 0 12px 26px rgba(var(--brand-rgb), .34);
        color: #fff;
    }
    .pds-point-salary__go:active { transform: translateY(1px); }
    .pds-point-salary__history {
        padding: 0 20px 18px;
        border-top: 1px solid #ffe4cc;
        background: linear-gradient(180deg, #fff8f2 0%, #fff 40%);
    }
    .pds-point-salary__history-label {
        display: block; padding: 14px 0 8px;
        font-size: 11px; font-weight: 800; letter-spacing: .06em;
        text-transform: uppercase; color: var(--hr-muted);
    }
    .pds-point-salary__list { display: grid; gap: 8px; }
    .pds-point-salary__row {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 12px 14px; border-radius: 14px;
        background: #fff; border: 1px solid #f1e6da;
    }
    .pds-point-salary__row-main { display: grid; gap: 2px; min-width: 0; }
    .pds-point-salary__row-main strong {
        color: var(--hr-ink); font-size: 14px; font-weight: 800;
    }
    .pds-point-salary__row-main small {
        color: var(--hr-muted); font-size: 12px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .pds-point-salary__tag {
        display: inline-flex; align-items: center; padding: 4px 10px;
        border-radius: 999px; font-size: 11px; font-weight: 800; flex: 0 0 auto;
    }
    .pds-point-salary__tag--wait { background: rgba(var(--brand-rgb), .12); color: var(--site-color); }
    .pds-point-salary__tag--ok { background: #ecfdf5; color: #047857; }
    .pds-point-salary__tag--no { background: #fef2f2; color: #b91c1c; }
</style>
<script>
(function () {
    function syncHrFreezeTable(table) {
        if (!table || !table.classList.contains('pds-hr-table--freeze')) return;
        var headRow = table.querySelector('thead tr');
        var bodyRow = table.querySelector('tbody tr[data-row-id]') || table.querySelector('tbody tr');
        var footRow = table.querySelector('tfoot tr');
        if (!headRow || !bodyRow) return;

        var headCells = headRow.children;
        var bodyCells = bodyRow.children;
        var count = Math.min(headCells.length, bodyCells.length);
        if (count < 1) return;

        // Reset widths so we can re-measure cleanly.
        for (var i = 0; i < count; i++) {
            headCells[i].style.width = '';
            bodyCells[i].style.width = '';
            if (footRow && footRow.children[i]) footRow.children[i].style.width = '';
        }

        var widths = [];
        for (var j = 0; j < count; j++) {
            widths.push(Math.ceil(Math.max(
                headCells[j].getBoundingClientRect().width,
                bodyCells[j].getBoundingClientRect().width
            )));
        }

        var total = widths.reduce(function (sum, w) { return sum + w; }, 0);
        table.style.width = total + 'px';
        table.style.minWidth = total + 'px';

        for (var k = 0; k < count; k++) {
            var px = widths[k] + 'px';
            headCells[k].style.width = px;
            headCells[k].style.minWidth = px;
            bodyCells[k].style.width = px;
            bodyCells[k].style.minWidth = px;
            if (footRow && footRow.children[k]) {
                footRow.children[k].style.width = px;
                footRow.children[k].style.minWidth = px;
            }
            // Apply same widths to every body row for consistency.
            var rows = table.querySelectorAll('tbody tr');
            for (var r = 0; r < rows.length; r++) {
                if (rows[r].children[k]) {
                    rows[r].children[k].style.width = px;
                    rows[r].children[k].style.minWidth = px;
                }
            }
        }
    }

    function syncAllHrFreezeTables() {
        document.querySelectorAll('table.pds-hr-table--freeze').forEach(syncHrFreezeTable);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncAllHrFreezeTables);
    } else {
        syncAllHrFreezeTables();
    }
    window.addEventListener('resize', function () {
        window.clearTimeout(window.__pdsHrFreezeTimer);
        window.__pdsHrFreezeTimer = window.setTimeout(syncAllHrFreezeTables, 120);
    });
    window.pdsSyncHrFreezeTables = syncAllHrFreezeTables;
})();
</script>
