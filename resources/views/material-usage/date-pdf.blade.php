<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Material Usage - {{ $displayDate }}</title>
    <style>
        @page {
            margin: 6mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5px;
            margin: 0;
            color: #000;
        }

        .title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .sub {
            font-size: 9px;
            margin-bottom: 6px;
            color: #333;
        }

        .group-title {
            background: #343a40;
            color: #fff;
            padding: 3px 5px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        th,
        td {
            border: 1px solid #444;
            padding: 3px 4px;
            vertical-align: top;
        }

        th {
            background: #e9ecef;
            text-align: left;
        }

        tr {
            page-break-inside: avoid;
        }

        .text-right {
            text-align: right;
        }

        .muted {
            color: #555;
        }
    </style>
</head>

<body>

    <div class="title">Material Usage Report</div>
    <div class="sub">
        Usage Date: <strong>{{ $displayDate }}</strong>
        &nbsp;|&nbsp; Total Records: <strong>{{ $usageLogs->count() }}</strong>
        &nbsp;|&nbsp; Generated: {{ now()->format('d-M-Y H:i') }}
    </div>

    @forelse ($groups as $groupTitle => $logs)

        <div class="group-title">
            {{ $groupTitle }} ({{ $logs->count() }})
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 30%;">Item</th>
                    <th style="width: 14%;">Product Model</th>
                    <th style="width: 14%;" class="text-right">Quantity</th>
                    <th style="width: 16%;">Reference</th>
                    <th style="width: 11%;">Source</th>
                    <th style="width: 15%;">Production Plan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                    @php $model = $resolveModel($log); @endphp
                    <tr>
                        <td>
                            <strong>{{ $log->item?->item_code }}</strong><br>
                            <span class="muted">{{ $log->item?->item_name }}</span>
                        </td>
                        <td>{{ $model?->model_code ?? '-' }}</td>
                        <td class="text-right">
                            {{ number_format((float) $log->quantity, 3) }}
                            {{ $log->item?->unit?->name }}
                        </td>
                        <td>{{ $log->reference ?? '-' }}</td>
                        <td>{{ $log->source === 'production-plan' ? 'Production Plan' : 'Manual' }}</td>
                        <td>{{ $log->productionPlan?->reference ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    @empty

        <p>No material usage records found for this date.</p>

    @endforelse

</body>
</html>