<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Low Stock Alert</title>
</head>
<body>
    <h1>Low Stock Alert</h1>

    <p>
        A product is running low on stock.
    </p>

    <ul>
        <li><strong>Product:</strong> {{ $productName }} (ID: {{ $productId }})</li>
        <li><strong>Stock remaining:</strong> {{ $stockQuantity }}</li>
        <li><strong>Low stock threshold:</strong> {{ $lowStockThreshold }}</li>
    </ul>

    <p>
        This is an automated notification.
    </p>
</body>
</html>