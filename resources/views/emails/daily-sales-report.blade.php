<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Daily Sales Report</title>
</head>
<body>
    <h1>Daily Sales Report</h1>

    <p>
        Report date: <strong>{{ $reportDate }}</strong>
    </p>

    @if (empty($items))
        <p>No products were sold on this date.</p>
    @else
        <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse;">
            <thead>
                <tr>
                    <th align="left">Product</th>
                    <th align="right">Quantity Sold</th>
                    <th align="right">Revenue (USD)</th>
                </tr>
            </thead>
            <tbody>
                @php($totalRevenueCents = 0)
                @foreach ($items as $row)
                    @php($totalRevenueCents += $row['revenue_cents'])
                    <tr>
                        <td>{{ $row['product_name'] }} (ID: {{ $row['product_id'] }})</td>
                        <td align="right">{{ $row['quantity_sold'] }}</td>
                        <td align="right">${{ number_format($row['revenue_cents'] / 100, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th align="right" colspan="2">Total</th>
                    <th align="right">${{ number_format($totalRevenueCents / 100, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    @endif

    <p>
        This is an automated report.
    </p>
</body>
</html>