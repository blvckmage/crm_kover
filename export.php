<?php
require_once 'db.php';
require_once 'auth.php';

$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

$stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($format === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=orders_".date('Y-m-d').".xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    echo "<html><head><meta charset=\"UTF-8\"></head><body>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Клиент</th><th>Телефон</th><th>Ширина (м)</th><th>Длина (м)</th><th>Площадь (м2)</th><th>Сумма</th><th>Статус</th><th>Дата создания</th><th>Дата: Новый</th><th>Дата: В стирке</th><th>Дата: Сушится</th><th>Дата: Готов</th><th>Дата: Доставлен</th><th>Дата: Отменен</th></tr>";
    
    foreach ($orders as $order) {
        echo "<tr>";
        echo "<td>" . $order['id'] . "</td>";
        echo "<td>" . htmlspecialchars($order['client_name']) . "</td>";
        echo "<td>" . htmlspecialchars($order['client_phone']) . "</td>";
        echo "<td>" . str_replace('.', ',', $order['carpet_width']) . "</td>";
        echo "<td>" . str_replace('.', ',', $order['carpet_length']) . "</td>";
        echo "<td>" . str_replace('.', ',', $order['total_area']) . "</td>";
        echo "<td>" . $order['total_price'] . "</td>";
        echo "<td>" . htmlspecialchars($order['status']) . "</td>";
        echo "<td>" . $order['created_at'] . "</td>";
        echo "<td>" . ($order['status_new_at'] ?? '') . "</td>";
        echo "<td>" . ($order['status_washing_at'] ?? '') . "</td>";
        echo "<td>" . ($order['status_drying_at'] ?? '') . "</td>";
        echo "<td>" . ($order['status_ready_at'] ?? '') . "</td>";
        echo "<td>" . ($order['status_delivered_at'] ?? '') . "</td>";
        echo "<td>" . ($order['status_cancelled_at'] ?? '') . "</td>";
        echo "</tr>";
    }
    
    echo "</table></body></html>";
    exit;
} else {
    // CSV
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=orders_".date('Y-m-d').".csv");
    
    $output = fopen('php://output', 'w');
    // UTF-8 BOM for correct Excel opening of CSV
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['ID', 'Клиент', 'Телефон', 'Ширина (м)', 'Длина (м)', 'Площадь (м2)', 'Сумма', 'Статус', 'Дата создания', 'Дата: Новый', 'Дата: В стирке', 'Дата: Сушится', 'Дата: Готов', 'Дата: Доставлен', 'Дата: Отменен'], ';', '"', '\\');
    
    foreach ($orders as $order) {
        fputcsv($output, [
            $order['id'],
            $order['client_name'],
            $order['client_phone'],
            str_replace('.', ',', $order['carpet_width']),
            str_replace('.', ',', $order['carpet_length']),
            str_replace('.', ',', $order['total_area']),
            $order['total_price'],
            $order['status'],
            $order['created_at'],
            $order['status_new_at'] ?? '',
            $order['status_washing_at'] ?? '',
            $order['status_drying_at'] ?? '',
            $order['status_ready_at'] ?? '',
            $order['status_delivered_at'] ?? '',
            $order['status_cancelled_at'] ?? ''
        ], ';', '"', '\\');
    }
    
    fclose($output);
    exit;
}
