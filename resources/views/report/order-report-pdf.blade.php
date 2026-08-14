<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid black;
        }
        th, td {
            padding-top: 8px;
            padding-bottom: 8px;
            text-align: center;
            border-bottom: 1px solid #bfbfbf;
            font-size: 12px;
        }
        h1 {
            text-align: center;
        }
        p {
            font-size: 12px;
        }
        .bold-text {
            font-weight: bold;
        }
        .text-capitalize {
            text-transform: capitalize;
        }
        .note {
            margin-top: 20px;
            font-size: 10px;
            text-align: center;
            color: green;
        }
    </style>
</head>
<body>

<h1>{{ __('message.order_report') }}</h1>

@if($dateFilterText)
    <p><strong>{{ $dateFilterText }}</strong></p>
@endif

<table>
    <thead>
        <tr>
            @foreach($headings as $heading)
                <th>{{ $heading }}</th>
            @endforeach
        </tr>
    </thead>

    <tbody>
        @foreach($mappedData as $row)
            <tr>
                @foreach($row as $cell)
                    @if(
                        $cell === 'Total' ||
                        $cell === $totalAmountSum ||
                        $cell === $totalAmountDeliveryman ||
                        $cell === $totalAmountOrder
                    )
                        <td class="bold-text">{{ $cell }}</td>
                    @else
                        <td class="text-capitalize">{{ $cell }}</td>
                    @endif
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

<div class="note">
    <p>
        *Note :
        {{ __('message.this_report_was_generated_by_a_computer_and_does_not_require_a_signature_or_company_stamp_to_be_considered_valid') }}
    </p>
</div>

</body>
</html>
