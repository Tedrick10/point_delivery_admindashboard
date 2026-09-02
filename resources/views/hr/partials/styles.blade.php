<style>
    .pds-hr-page { --hr-orange: #FE6F07; --hr-ink: #0f172a; --hr-muted: #64748b; --hr-line: #e8edf5; }
    .pds-hr-hero {
        display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;
        background: linear-gradient(135deg, #fff7ed 0%, #ffffff 58%);
        border: 1px solid #ffe4cc; border-radius: 20px; padding: 22px 24px; margin-bottom: 16px;
    }
    .pds-hr-hero__eyebrow {
        display: inline-flex; align-items: center; gap: 8px; color: var(--hr-orange); font-weight: 600; font-size: 13px; margin-bottom: 6px;
    }
    .pds-hr-hero__title { margin: 0; font-size: 26px; font-weight: 700; color: var(--hr-ink); }
    .pds-hr-hero__subtitle { margin: 6px 0 0; color: var(--hr-muted); max-width: 560px; }
    .pds-hr-hero__stats { display: flex; gap: 10px; flex-wrap: wrap; }
    .pds-hr-stat {
        min-width: 140px; background: linear-gradient(135deg, #FE6F07, #ff8f3d); color: #fff;
        border-radius: 16px; padding: 14px 18px; box-shadow: 0 10px 24px rgba(254, 111, 7, .22);
    }
    .pds-hr-stat--soft {
        background: #fff; color: var(--hr-ink); border: 1px solid var(--hr-line); box-shadow: 0 8px 20px rgba(15, 23, 42, .04);
    }
    .pds-hr-stat__value { display: block; font-size: 24px; font-weight: 800; line-height: 1.1; }
    .pds-hr-stat__label { display: block; margin-top: 4px; font-size: 12px; opacity: .9; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; }
    .pds-hr-toolbar {
        display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 14px;
    }
    .pds-hr-tabs { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .pds-hr-tab {
        display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--hr-line); background: #fff;
        color: #334155; border-radius: 999px; padding: 8px 14px; font-weight: 600; text-decoration: none; transition: .2s ease;
    }
    button.pds-hr-tab { cursor: pointer; }
    .pds-hr-tab:hover { border-color: #ffd8b0; color: #c2410c; text-decoration: none; }
    .pds-hr-tab.is-active {
        background: linear-gradient(135deg, #FE6F07, #ff8f3d); border-color: transparent; color: #fff;
        box-shadow: 0 8px 18px rgba(254, 111, 7, .24);
    }
    .pds-hr-month {
        display: inline-flex; align-items: center; gap: 4px; background: #fff; border: 1px solid var(--hr-line);
        border-radius: 999px; padding: 4px;
    }
    .pds-hr-month--end { margin-left: auto; }
    .pds-hr-month__btn {
        width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; color: #475569; text-decoration: none;
    }
    .pds-hr-month__btn:hover { background: #fff7ed; color: var(--hr-orange); text-decoration: none; }
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
    .pds-hr-side { padding: 18px; }
    .pds-hr-side__head { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; margin-bottom: 14px; }
    .pds-hr-side__head h5 { margin: 0; font-weight: 700; color: var(--hr-ink); }
    .pds-hr-side__head p { margin: 4px 0 0; color: var(--hr-muted); font-size: 13px; }
    .pds-hr-chip {
        background: #fff7ed; color: #c2410c; border: 1px solid #ffd8b0; border-radius: 999px;
        padding: 6px 12px; font-weight: 700; white-space: nowrap;
    }
    .pds-hr-side__form { background: #f8fafc; border: 1px solid #eef2f7; border-radius: 14px; padding: 14px; margin-bottom: 14px; }
    .pds-hr-side__form .form-group { margin-bottom: 10px; }
    .pds-hr-side__form label { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
    .pds-hr-table { margin: 0; }
    .pds-hr-table thead th {
        background: #f8fafc; color: #64748b; font-size: 11px; letter-spacing: .06em; text-transform: uppercase;
        font-weight: 700; border-top: 0; border-bottom: 1px solid var(--hr-line); white-space: nowrap; padding: 12px 10px; vertical-align: middle;
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
        color: #c2410c;
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
        background: #fff7ed; color: var(--hr-orange); font-weight: 800; flex: 0 0 auto;
    }
    .pds-hr-avatar.is-rider { background: #eff6ff; color: #2563eb; }
    .pds-hr-person__name { font-weight: 700; color: var(--hr-ink); line-height: 1.2; }
    .pds-hr-badge {
        display: inline-flex; margin-top: 3px; font-size: 11px; font-weight: 700; color: #c2410c;
        background: #fff7ed; border-radius: 999px; padding: 2px 8px;
    }
    .pds-hr-badge.is-rider { color: #1d4ed8; background: #eff6ff; }
    .pds-hr-input {
        width: 88px; max-width: 100%; border: 1px solid #e2e8f0; border-radius: 10px; padding: 6px 8px;
        background: #fff; font-weight: 600; color: var(--hr-ink); text-align: right;
    }
    .pds-hr-input:focus { outline: none; border-color: #FE6F07; box-shadow: 0 0 0 3px rgba(254, 111, 7, .12); }
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
    .pds-hr-formula-note {
        background: #fff7ed; border: 1px solid #ffd8b0; color: #9a3412;
        border-radius: 14px; padding: 10px 14px; margin-bottom: 14px; font-size: 13px; font-weight: 600;
    }
    .pds-hr-extra { padding: 22px 22px 0; }
    .pds-hr-extra__head {
        display: flex; justify-content: space-between; gap: 16px; align-items: center;
        flex-wrap: wrap; margin-bottom: 18px;
    }
    .pds-hr-extra__title-wrap { display: flex; align-items: flex-start; gap: 14px; }
    .pds-hr-extra__icon {
        width: 44px; height: 44px; border-radius: 14px; display: grid; place-items: center; flex: 0 0 auto;
        background: linear-gradient(135deg, #FE6F07, #ff9a4a); color: #fff;
        box-shadow: 0 10px 20px rgba(254, 111, 7, .22); font-size: 16px;
    }
    .pds-hr-extra__head h5 { margin: 0; font-weight: 800; color: var(--hr-ink); font-size: 18px; letter-spacing: -.01em; }
    .pds-hr-extra__head p { margin: 4px 0 0; color: var(--hr-muted); font-size: 13px; max-width: 420px; line-height: 1.45; }
    .pds-hr-extra__total-pill {
        display: inline-flex; flex-direction: column; align-items: flex-end; gap: 2px;
        background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
        border: 1px solid var(--hr-line); border-radius: 16px; padding: 10px 16px;
        box-shadow: 0 8px 18px rgba(15, 23, 42, .04);
    }
    .pds-hr-extra__total-pill-label {
        font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--hr-muted);
    }
    .pds-hr-extra__total-pill-value {
        font-size: 22px; font-weight: 800; color: var(--hr-orange); line-height: 1.1; font-variant-numeric: tabular-nums;
    }
    .pds-hr-extra__form {
        display: grid;
        grid-template-columns: minmax(160px, 220px) minmax(220px, 1fr) minmax(120px, 150px) auto;
        gap: 12px; align-items: end;
        background: #f8fafc; border: 1px solid #eef2f7; border-radius: 18px;
        padding: 16px; margin-bottom: 18px;
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
        outline: none; border-color: #FE6F07; box-shadow: 0 0 0 3px rgba(254, 111, 7, .12);
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
        border-color: #FE6F07; box-shadow: 0 0 0 3px rgba(254, 111, 7, .12);
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
        outline: none; border-color: #FE6F07; box-shadow: 0 0 0 3px rgba(254, 111, 7, .12);
    }
    .pds-hr-extra__form .select2-results__option { padding: 10px 14px; font-weight: 600; }
    .pds-hr-extra__form .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background: #fff7ed; color: #c2410c;
    }
    .pds-hr-extra__form .select2-container--default .select2-results__option[aria-selected=true] {
        background: #ffedd5; color: #9a3412;
    }
    .pds-hr-extra__field--amount .pds-hr-extra__control { text-align: right; font-variant-numeric: tabular-nums; }
    .pds-hr-extra__add-btn {
        height: 44px; border: 0; border-radius: 12px; padding: 0 18px;
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        background: linear-gradient(135deg, #FE6F07, #ff8f3d); color: #fff;
        font-weight: 700; box-shadow: 0 10px 18px rgba(254, 111, 7, .22);
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .pds-hr-extra__add-btn:hover {
        color: #fff; transform: translateY(-1px);
        box-shadow: 0 12px 22px rgba(254, 111, 7, .28);
    }
    .pds-hr-extra__list {
        border: 1px solid #eef2f7; border-radius: 18px; overflow: hidden; margin-bottom: 22px; background: #fff;
    }
    .pds-hr-extra__list-head,
    .pds-hr-extra__row,
    .pds-hr-extra__footer {
        display: grid;
        grid-template-columns: 48px minmax(140px, 200px) minmax(220px, 1fr) 120px 52px;
        gap: 12px; align-items: center; padding: 12px 16px;
    }
    .pds-hr-extra__list.is-readonly .pds-hr-extra__list-head,
    .pds-hr-extra__list.is-readonly .pds-hr-extra__row,
    .pds-hr-extra__list.is-readonly .pds-hr-extra__footer {
        grid-template-columns: 48px minmax(140px, 200px) minmax(220px, 1fr) 120px;
    }
    .pds-hr-extra__list-head {
        background: #f8fafc; border-bottom: 1px solid #eef2f7;
        font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #64748b;
    }
    .pds-hr-extra__row {
        border-bottom: 1px solid #f1f5f9; transition: background .15s ease;
    }
    .pds-hr-extra__row:last-child { border-bottom: 0; }
    .pds-hr-extra__row:hover { background: #fffaf5; }
    .pds-hr-extra__col-no { color: #94a3b8; font-weight: 700; }
    .pds-hr-extra__col-name { display: inline-flex; align-items: center; gap: 10px; min-width: 0; }
    .pds-hr-extra__avatar {
        width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; flex: 0 0 auto;
        background: #fff7ed; color: var(--hr-orange); font-size: 12px; font-weight: 800;
    }
    .pds-hr-extra__name {
        font-weight: 700; color: var(--hr-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .pds-hr-extra__col-about {
        color: #334155; font-weight: 600; line-height: 1.4; white-space: normal;
    }
    .pds-hr-extra__col-fine { text-align: right; }
    .pds-hr-extra__amount {
        display: inline-flex; min-width: 84px; justify-content: flex-end;
        background: #fff7ed; color: #c2410c; border-radius: 999px;
        padding: 6px 12px; font-weight: 800; font-variant-numeric: tabular-nums;
    }
    .pds-hr-extra__col-action { text-align: right; }
    .pds-hr-extra__delete {
        width: 34px; height: 34px; border-radius: 10px; display: inline-grid; place-items: center;
        color: #dc2626; background: #fef2f2; text-decoration: none; transition: .15s ease;
    }
    .pds-hr-extra__delete:hover { background: #fee2e2; color: #b91c1c; text-decoration: none; }
    .pds-hr-extra__empty {
        text-align: center; padding: 40px 20px; color: var(--hr-muted);
    }
    .pds-hr-extra__empty-icon {
        width: 52px; height: 52px; margin: 0 auto 12px; border-radius: 16px;
        display: grid; place-items: center; background: #f8fafc; color: #94a3b8; font-size: 18px;
    }
    .pds-hr-extra__empty strong { display: block; color: var(--hr-ink); margin-bottom: 4px; }
    .pds-hr-extra__empty p { margin: 0; font-size: 13px; }
    .pds-hr-extra__footer {
        background: linear-gradient(90deg, #f8fafc, #fff7ed);
        border-top: 1px solid #eef2f7;
        font-weight: 700; color: #475569;
    }
    .pds-hr-extra__footer span { grid-column: 1 / 4; text-align: right; }
    .pds-hr-extra__footer strong {
        grid-column: 4; justify-self: end;
        min-width: 96px; text-align: center;
        background: #dcfce7; color: #166534; border-radius: 999px;
        padding: 8px 14px; font-size: 15px; font-variant-numeric: tabular-nums;
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
        background: linear-gradient(135deg, #fff7ed, #ffffff); border-color: #ffd8b0;
    }
    .pds-hr-my-salary__item--accent strong { color: var(--hr-orange); }
    @media (max-width: 991px) {
        .pds-hr-summary { grid-template-columns: 1fr; }
        .pds-hr-hero__title { font-size: 22px; }
        .pds-hr-extra__form { grid-template-columns: 1fr; }
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
        .pds-hr-extra__col-about { grid-column: 1 / -1; }
        .pds-hr-extra__col-action { grid-column: 2; grid-row: 1; align-self: start; margin-top: 36px; }
        .pds-hr-extra__footer span { grid-column: 1; text-align: left; }
        .pds-hr-extra__footer strong { grid-column: 2; }
        .pds-hr-my-salary__grid { grid-template-columns: 1fr 1fr; }
    }
</style>
