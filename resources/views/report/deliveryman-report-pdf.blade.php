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
            text-align: left;
            border-bottom: 1px solid #bfbfbf;
            font-size: 12px;
        }
        h1 {
            text-align: center;
        }
        p {
            font-size: 12px;
        }
        .text-capitalize {
            text-transform: capitalize;
        }
        .text-center {
            text-align: center;
        }
        .bold-text {
            font-weight: bold;
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

<h1>{{ __('message.report_of') }} {{ $deliverymanName }}</h1>

@if($dateFilterText)
    <p><strong>{{ $dateFilterText }}</strong></p>
@endif

<table>
    <thead>
        <tr>
            @foreach($headings as $heading)
                <th class="text-center">{{ $heading }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($mappedData as $row)
            <tr>
                @foreach($row as $cell)
                    @if(
                        $cell === 'Total' ||
                        $cell === $totalAmountOrder ||
                        $cell === $totalAmountSum ||
                        $cell === $totalAmountDeliveryman
                    )
                        <td class="bold-text text-center">{{ $cell }}</td>
                    @else
                        <td class="text-capitalize text-center">{{ $cell }}</td>
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
