<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Airway Bill</title>
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            background: #f6f4f0;
            color: #1f1b16;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            font-synthesis: none;
            -webkit-font-smoothing: antialiased;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 22px;
            background: #fff;
            border-bottom: 1px solid #ece8e1;
        }
        .toolbar h1 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }
        .toolbar p {
            margin: 3px 0 0;
            color: #8a8178;
            font-size: 12px;
        }
        .actions { display: flex; gap: 8px; }
        .btn {
            min-height: 36px;
            padding: 0 16px;
            border: 1px solid #e5e0d8;
            border-radius: 999px;
            background: #fff;
            color: #3f3a34;
            font: inherit;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
        }
        .btn.primary {
            background: #ea580c;
            border-color: #ea580c;
            color: #fff;
        }
        .stack {
            padding: 32px 16px 48px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 24px;
        }
        .slip {
            width: 360px;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 16px 40px rgba(40, 30, 20, .08);
        }
        .slip-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #ea580c;
            color: #fff;
        }
        .slip-top .brand {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .slip-top .mark {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            background: #fff;
            color: #ea580c;
            display: grid;
            place-items: center;
            font-size: 12px;
            font-weight: 600;
        }
        .slip-top img.mark { object-fit: contain; padding: 3px; }
        .slip-top .name { font-size: 13px; font-weight: 600; }
        .slip-top .when { font-size: 11px; opacity: .9; }
        .hero {
            display: grid;
            grid-template-columns: 118px 1fr;
            gap: 14px;
            padding: 16px;
            align-items: center;
            background: linear-gradient(180deg, #fffaf4 0%, #fff 70%);
        }
        .qr-box {
            width: 118px;
            height: 118px;
            padding: 8px;
            border-radius: 16px;
            background: #fff;
            border: 1px solid #f0e6d8;
        }
        .qr-box svg, .qr-box img { width: 100%; height: 100%; display: block; }
        .hero h2 {
            margin: 0 0 6px;
            font-size: 11px;
            font-weight: 600;
            color: #c2410c;
        }
        .hero .code {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            letter-spacing: .01em;
            line-height: 1.15;
        }
        .hero .date {
            margin-top: 10px;
            color: #7c746c;
            font-size: 12px;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            border-top: 1px solid #f1ece6;
        }
        .block { padding: 14px 16px 16px; }
        .block + .block { border-left: 1px solid #f1ece6; }
        .kicker {
            margin: 0 0 6px;
            color: #b45309;
            font-size: 10px;
            font-weight: 600;
        }
        .block.from .kicker { color: #78716c; }
        .who {
            margin: 0 0 8px;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.3;
        }
        .line {
            margin: 0 0 4px;
            color: #57534e;
            font-size: 12px;
            line-height: 1.4;
        }
        .line.mm { font-family: "Noto Sans Myanmar", "Pyidaungsu", "Myanmar Text", sans-serif; font-weight: 400; }
        .money {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px;
            padding: 14px 16px 16px;
            background: #faf8f5;
            border-top: 1px solid #f1ece6;
        }
        .stat span {
            display: block;
            margin-bottom: 3px;
            color: #8a8178;
            font-size: 10px;
            font-weight: 500;
        }
        .stat b {
            display: block;
            font-size: 14px;
            font-weight: 600;
        }
        .stat.cod b { color: #c2410c; }
        .foot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px 12px;
            color: #78716c;
            font-size: 11px;
            border-top: 1px solid #f1ece6;
        }
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        @media print {
            html, body {
                width: auto !important;
                height: auto !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }
            .toolbar { display: none !important; }
            .stack {
                display: block;
                padding: 0 !important;
                gap: 0 !important;
            }
            .slip {
                width: 90mm;
                max-width: 90mm;
                margin: 0 auto 0;
                border: 1.5px solid #111;
                border-radius: 0;
                box-shadow: none;
                overflow: visible;
                break-inside: avoid;
                page-break-inside: avoid;
                page-break-after: always;
                break-after: page;
            }
            .slip:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }
            .slip-top {
                background: #fff !important;
                color: #111 !important;
                border-bottom: 2px solid #111;
            }
            .slip-top .mark {
                background: #111 !important;
                color: #fff !important;
            }
            .slip-top .when { opacity: 1; }
            .hero, .money, .foot {
                background: #fff !important;
            }
            .hero h2,
            .kicker,
            .stat.cod b { color: #111 !important; }
            .qr-box { border-color: #111; }
            .grid, .block + .block, .money, .foot { border-color: #111; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <h1>Print labels</h1>
            <p>{{ count($labels) }} parcel{{ count($labels) === 1 ? '' : 's' }} · uncheck Headers and footers in the print dialog</p>
        </div>
        <div class="actions">
            <button type="button" class="btn" onclick="window.close()">Close</button>
            <button type="button" class="btn primary" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="stack">
        @forelse($labels as $label)
            <article class="slip">
                <div class="slip-top">
                    <div class="brand">
                        @if(!empty($label->logo_url))
                            <img class="mark" src="{{ $label->logo_url }}" alt="">
                        @else
                            <span class="mark">P</span>
                        @endif
                        <div class="name">{{ $label->company }}</div>
                    </div>
                    <div class="when">{{ $label->printed_at }}</div>
                </div>

                <div class="hero">
                    <div class="qr-box">{!! $label->qr_svg !!}</div>
                    <div>
                        <h2>AIRWAY BILL</h2>
                        <p class="code">{{ $label->code }}</p>
                        <div class="date">Created {{ $label->create_date }}</div>
                    </div>
                </div>

                <div class="grid">
                    <div class="block from">
                        <p class="kicker">From</p>
                        <p class="who">{{ $label->sender_name }}</p>
                        @if(!empty($label->sender_city))
                            <p class="line">{{ $label->sender_city }}</p>
                        @endif
                        <p class="line">{{ $label->sender_phone }}</p>
                        <p class="line">{{ $label->sender_address }}</p>
                    </div>
                    <div class="block">
                        <p class="kicker">To</p>
                        <p class="who">{{ $label->receiver }}</p>
                        <p class="line">{{ $label->receiver_phone }}</p>
                        <p class="line mm">{{ $label->township }}</p>
                        <p class="line mm">{{ $label->receiver_address }}</p>
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
                    <div class="stat cod">
                        <span>Remark</span>
                        <b>{{ $label->remark }}</b>
                    </div>
                </div>

                <div class="foot">
                    <span>Hot line</span>
                    <span>{{ $label->hotline !== '' ? $label->hotline : '—' }}</span>
                </div>
            </article>
        @empty
            <p>No parcels to print.</p>
        @endforelse
    </div>
</body>
</html>
