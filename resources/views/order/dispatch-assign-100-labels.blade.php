<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Airway Bill</title>
    @php $embed = !empty($embed); @endphp
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100%;
            background: {{ $embed ? '#ececec' : '#ececec' }};
            color: #0a0a0a;
            font-family: "Helvetica Neue", Helvetica, Arial, ui-sans-serif, system-ui, sans-serif;
            font-synthesis: none;
            -webkit-font-smoothing: antialiased;
        }
        .float-actions {
            position: fixed;
            right: 1.15rem;
            bottom: 1.15rem;
            z-index: 20;
            display: {{ $embed ? 'none' : 'flex' }};
            align-items: center;
            gap: 0.45rem;
            padding: 0.4rem;
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid #d4d4d4;
            border-radius: 999px;
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.12);
        }
        .float-actions .hint {
            display: none;
        }
        .btn {
            min-height: 40px;
            padding: 0 1.05rem;
            border: 1px solid transparent;
            border-radius: 999px;
            background: transparent;
            color: #171717;
            font: inherit;
            font-size: 0.82rem;
            font-weight: 650;
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease, transform 0.15s ease;
        }
        .btn:hover {
            background: #f5f5f5;
            transform: translateY(-1px);
        }
        .btn.primary {
            background: #0a0a0a;
            color: #fff;
        }
        .btn.primary:hover {
            background: #000;
        }
        .stack {
            min-height: {{ $embed ? 'auto' : '100vh' }};
            padding: {{ $embed ? '1.25rem 0.85rem 1.5rem' : '2.75rem 1rem 5.5rem' }};
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            gap: 2rem;
        }
        .slip {
            width: 392px;
            background: #fff;
            border: 1px solid #d4d4d4;
            border-radius: 18px;
            overflow: hidden;
            box-shadow:
                0 1px 2px rgba(0, 0, 0, 0.04),
                0 18px 48px rgba(0, 0, 0, 0.08);
        }
        .slip-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 1rem 1.15rem;
            background: #fff;
            border-bottom: 2px solid #0a0a0a;
        }
        .slip-top .brand {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            min-width: 0;
            flex: 1;
        }
        .slip-top .mark {
            width: 42px;
            height: 42px;
            min-width: 42px;
            min-height: 42px;
            max-width: 42px;
            max-height: 42px;
            border-radius: 50%;
            background: #0a0a0a;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            font-weight: 800;
            flex-shrink: 0;
            overflow: hidden;
            border: 0;
            padding: 0;
            box-shadow: none;
            line-height: 0;
        }
        .slip-top .mark img {
            width: 100%;
            height: 100%;
            max-width: 100%;
            margin: 0;
            object-fit: contain;
            object-position: center;
            display: block;
            border: 0;
            padding: 0;
            background: transparent;
            filter: none;
            transform: none;
        }
        .slip-top .name {
            font-size: 0.92rem;
            font-weight: 750;
            letter-spacing: -0.02em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .slip-top .when {
            font-size: 0.68rem;
            font-weight: 600;
            color: #737373;
            letter-spacing: 0.02em;
            text-align: right;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .hero {
            display: grid;
            grid-template-columns: 128px 1fr;
            gap: 1.1rem;
            padding: 1.25rem 1.15rem 1.2rem;
            align-items: center;
            background: #fff;
        }
        .qr-box {
            width: 128px;
            height: 128px;
            padding: 8px;
            border-radius: 14px;
            background: #fff;
            border: 1.5px solid #0a0a0a;
            box-shadow: inset 0 0 0 4px #f5f5f5;
            cursor: pointer;
            appearance: none;
            display: block;
            text-align: left;
            transition: box-shadow 0.15s ease, transform 0.15s ease;
        }
        .qr-box:hover {
            box-shadow: inset 0 0 0 4px #f5f5f5, 0 6px 16px rgba(0, 0, 0, 0.12);
            transform: translateY(-1px);
        }
        .qr-box:focus-visible {
            outline: 2px solid #0a0a0a;
            outline-offset: 2px;
        }
        .qr-box svg,
        .qr-box img {
            width: 100%;
            height: 100%;
            display: block;
            pointer-events: none;
        }
        .qr-popup {
            position: fixed;
            inset: 0;
            z-index: 40;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
        }
        .qr-popup.is-open {
            display: flex !important;
        }
        .qr-popup[hidden] {
            display: none !important;
        }
        .qr-popup__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.48);
        }
        .qr-popup__panel {
            position: relative;
            z-index: 1;
            width: min(320px, 100%);
            padding: 1.15rem 1.15rem 1rem;
            border-radius: 18px;
            background: #fff;
            border: 1px solid #d4d4d4;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.22);
            text-align: center;
        }
        .qr-popup__code {
            margin: 0 0 0.85rem;
            font-family: "Iowan Old Style", "Palatino Linotype", Palatino, Georgia, serif;
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #0a0a0a;
        }
        .qr-popup__frame {
            width: 220px;
            height: 220px;
            margin: 0 auto 1rem;
            padding: 12px;
            border: 1.5px solid #0a0a0a;
            border-radius: 16px;
            background: #fff;
        }
        .qr-popup__frame svg,
        .qr-popup__frame img {
            width: 100%;
            height: 100%;
            display: block;
        }
        .qr-popup__close {
            min-height: 40px;
            padding: 0 1.2rem;
            border: 0;
            border-radius: 999px;
            background: #0a0a0a;
            color: #fff;
            font: inherit;
            font-size: 0.82rem;
            font-weight: 650;
            cursor: pointer;
        }
        .hero-copy {
            min-width: 0;
        }
        .hero h2 {
            margin: 0 0 0.45rem;
            font-size: 0.66rem;
            font-weight: 750;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #737373;
        }
        .hero .code {
            margin: 0;
            font-family: "Iowan Old Style", "Palatino Linotype", Palatino, Georgia, serif;
            font-size: 1.55rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.1;
            color: #0a0a0a;
            word-break: break-all;
        }
        .hero .date {
            display: inline-flex;
            margin-top: 0.75rem;
            padding: 0.28rem 0.55rem;
            border: 1px solid #e5e5e5;
            border-radius: 999px;
            color: #525252;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.01em;
        }
        .parties {
            border-top: 1px solid #e5e5e5;
        }
        .party {
            padding: 1rem 1.15rem 1.05rem;
        }
        .party + .party {
            border-top: 1px dashed #d4d4d4;
            background: #fafafa;
        }
        .party .kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin: 0 0 0.55rem;
            color: #737373;
            font-size: 0.64rem;
            font-weight: 750;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .party .kicker::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #0a0a0a;
        }
        .party.to .kicker::before {
            background: transparent;
            border: 1.5px solid #0a0a0a;
        }
        .who {
            margin: 0 0 0.4rem;
            font-size: 1rem;
            font-weight: 750;
            letter-spacing: -0.02em;
            line-height: 1.25;
            color: #0a0a0a;
        }
        .meta {
            display: grid;
            gap: 0.18rem;
        }
        .line {
            margin: 0;
            color: #404040;
            font-size: 0.78rem;
            line-height: 1.45;
        }
        .line.mm {
            font-family: "Noto Sans Myanmar", "Pyidaungsu", "Myanmar Text", sans-serif;
            font-weight: 400;
        }
        .money {
            display: grid;
            grid-template-columns: 1fr 1fr 1.15fr;
            border-top: 1px solid #e5e5e5;
            background: #fff;
        }
        .stat {
            padding: 0.95rem 0.95rem 1rem;
            min-width: 0;
        }
        .stat + .stat {
            border-left: 1px solid #e5e5e5;
        }
        .stat span {
            display: block;
            margin-bottom: 0.35rem;
            color: #737373;
            font-size: 0.62rem;
            font-weight: 750;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .stat b {
            display: block;
            font-size: 0.95rem;
            font-weight: 750;
            letter-spacing: -0.02em;
            color: #0a0a0a;
            word-break: break-word;
            line-height: 1.25;
        }
        .foot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            padding: 0.8rem 1.15rem 0.9rem;
            border-top: 1px solid #e5e5e5;
            color: #525252;
            font-size: 0.74rem;
            font-weight: 650;
            background: #fff;
        }
        .foot strong {
            color: #0a0a0a;
            font-weight: 750;
        }
        .empty {
            margin: 3.5rem 0;
            color: #737373;
            font-size: 0.92rem;
            font-weight: 500;
        }
        @page {
            size: 110mm 190mm;
            margin: 3mm;
        }
        @media print {
            html, body {
                width: auto !important;
                height: auto !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .toolbar,
            .float-actions { display: none !important; }
            .stack {
                display: block !important;
                min-height: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                gap: 0 !important;
                align-items: stretch !important;
            }
            .slip {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                border: 1.5px solid #000;
                border-radius: 0;
                box-shadow: none;
                overflow: hidden;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
                page-break-after: always;
                break-after: page;
            }
            .slip:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }
            .slip-top,
            .hero,
            .parties,
            .party,
            .money,
            .foot {
                background: #fff !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }
            .slip-top {
                border-bottom: 2px solid #000;
                padding: 0.55rem 0.7rem !important;
            }
            .slip-top .mark {
                background: #000 !important;
                color: #fff !important;
                border: 0 !important;
                box-shadow: none !important;
                width: 36px !important;
                height: 36px !important;
                min-width: 36px !important;
                min-height: 36px !important;
                max-width: 36px !important;
                max-height: 36px !important;
                overflow: hidden !important;
                border-radius: 50% !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .slip-top .mark img {
                width: 100% !important;
                height: 100% !important;
                max-width: none !important;
                max-height: none !important;
                margin: 0 !important;
                object-fit: fill !important;
                object-position: center !important;
                background: transparent !important;
                border: 0 !important;
                padding: 0 !important;
                transform: none !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .slip-top .name {
                font-size: 0.85rem !important;
            }
            .slip-top .when {
                font-size: 0.62rem !important;
            }
            .hero {
                grid-template-columns: 108px 1fr !important;
                padding: 0.65rem 0.7rem 0.55rem !important;
                gap: 0.7rem !important;
            }
            .qr-box {
                width: 108px !important;
                height: 108px !important;
                padding: 6px !important;
                border-color: #000;
                box-shadow: none !important;
                transform: none !important;
                cursor: default;
            }
            .hero h2 {
                font-size: 0.72rem !important;
                margin-bottom: 0.2rem !important;
            }
            .hero .code {
                font-size: 1.35rem !important;
                line-height: 1.1 !important;
            }
            .hero .date {
                margin-top: 0.35rem !important;
                font-size: 0.62rem !important;
                padding: 0.2rem 0.55rem !important;
            }
            .party {
                padding: 0.55rem 0.7rem !important;
            }
            .party .kicker {
                margin-bottom: 0.35rem !important;
                font-size: 0.58rem !important;
            }
            .who {
                font-size: 0.92rem !important;
                margin-bottom: 0.25rem !important;
            }
            .line {
                font-size: 0.72rem !important;
                margin: 0.12rem 0 !important;
            }
            .money {
                padding: 0.55rem 0.7rem !important;
            }
            .stat span {
                font-size: 0.58rem !important;
            }
            .stat b {
                font-size: 0.92rem !important;
            }
            .foot {
                padding: 0.45rem 0.7rem !important;
                font-size: 0.72rem !important;
            }
            .qr-popup { display: none !important; }
            .party + .party {
                border-top: 1px dashed #000;
            }
            .parties,
            .money,
            .stat + .stat,
            .foot {
                border-color: #000;
            }
            .hero h2,
            .party .kicker,
            .stat span,
            .line,
            .hero .date,
            .foot,
            .slip-top .when {
                color: #000 !important;
            }
            .who,
            .hero .code,
            .stat b,
            .foot strong {
                color: #000 !important;
            }
            .hero .date {
                border-color: #000;
            }
        }
    </style>
</head>
<body>
    <div class="float-actions" role="toolbar" aria-label="Print actions">
        <button type="button" class="btn" onclick="window.close()">Close</button>
        <button type="button" class="btn primary" onclick="window.print()">Print</button>
    </div>

    <div class="stack">
        @forelse($labels as $label)
            <article class="slip">
                <div class="slip-top">
                    <div class="brand">
                        @if(!empty($label->logo_url))
                            <span class="mark" aria-hidden="true">
                                <img src="{{ $label->logo_url }}" alt="">
                            </span>
                        @else
                            <span class="mark">P</span>
                        @endif
                        <div class="name">{{ $label->company }}</div>
                    </div>
                    <div class="when">{{ $label->printed_at }}</div>
                </div>

                <div class="hero">
                    <button type="button"
                            class="qr-box js-qr-open"
                            data-code="{{ $label->code }}"
                            title="View QR"
                            aria-label="View QR for {{ $label->code }}">
                        {!! $label->qr_svg !!}
                    </button>
                    <div class="hero-copy">
                        <h2>Airway Bill</h2>
                        <p class="code">{{ $label->code }}</p>
                        <div class="date">Created {{ $label->create_date }}</div>
                    </div>
                </div>

                <div class="parties">
                    <div class="party from">
                        <p class="kicker">From</p>
                        <p class="who">{{ $label->sender_name }}</p>
                        <div class="meta">
                            @if(!empty($label->sender_city))
                                <p class="line">{{ $label->sender_city }}</p>
                            @endif
                            <p class="line">{{ $label->sender_phone }}</p>
                            <p class="line">{{ $label->sender_address }}</p>
                        </div>
                    </div>
                    <div class="party to">
                        <p class="kicker">To</p>
                        <p class="who">{{ $label->receiver }}</p>
                        <div class="meta">
                            <p class="line">{{ $label->receiver_phone }}</p>
                            <p class="line mm">{{ $label->township }}</p>
                            <p class="line mm">{{ $label->receiver_address }}</p>
                        </div>
                    </div>
                </div>

                <div class="money">
                    <div class="stat">
                        <span>Item value</span>
                        <b>{{ $label->item_value }}</b>
                    </div>
                    <div class="stat">
                        <span>Delivery fee</span>
                        <b>{{ $label->deli_amount }}</b>
                    </div>
                    <div class="stat">
                        <span>Remark</span>
                        <b>{{ $label->remark !== '' ? $label->remark : '—' }}</b>
                    </div>
                </div>

                <div class="foot">
                    <span>Hot line</span>
                    <strong>{{ $label->hotline !== '' ? $label->hotline : '—' }}</strong>
                </div>
            </article>
        @empty
            <p class="empty">No parcels to print.</p>
        @endforelse
    </div>

    <div class="qr-popup" id="qrPopup" hidden>
        <div class="qr-popup__backdrop" data-qr-close></div>
        <div class="qr-popup__panel" role="dialog" aria-modal="true" aria-labelledby="qrPopupCode">
            <p class="qr-popup__code" id="qrPopupCode"></p>
            <div class="qr-popup__frame" id="qrPopupFrame"></div>
            <button type="button" class="qr-popup__close" data-qr-close>Close</button>
        </div>
    </div>

    <script>
        (function () {
            var popup = document.getElementById('qrPopup');
            var codeEl = document.getElementById('qrPopupCode');
            var frameEl = document.getElementById('qrPopupFrame');
            if (!popup || !codeEl || !frameEl) return;

            function openQr(btn) {
                var code = btn.getAttribute('data-code') || '';
                codeEl.textContent = code;
                frameEl.innerHTML = btn.innerHTML;
                popup.hidden = false;
                popup.removeAttribute('hidden');
                popup.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            }

            function closeQr() {
                popup.classList.remove('is-open');
                popup.hidden = true;
                popup.setAttribute('hidden', 'hidden');
                frameEl.innerHTML = '';
                document.body.style.overflow = '';
            }

            document.addEventListener('click', function (e) {
                var openBtn = e.target.closest('.js-qr-open');
                if (openBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    openQr(openBtn);
                    return;
                }
                if (e.target.closest('[data-qr-close]')) {
                    closeQr();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && popup.classList.contains('is-open')) {
                    closeQr();
                }
            });
        })();
    </script>
</body>
</html>
