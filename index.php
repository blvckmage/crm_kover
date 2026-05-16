<?php
require_once 'db.php';
require_once 'auth.php';

$page_title = 'Аналитика';
$period = isset($_GET['period']) ? $_GET['period'] : 'all';

$date_condition = "";
if ($period === 'day') {
    $date_condition = "AND date(created_at) = date('now')";
} elseif ($period === 'week') {
    $date_condition = "AND date(created_at) >= date('now', '-7 days')";
} elseif ($period === 'month') {
    $date_condition = "AND date(created_at) >= date('now', 'start of month')";
} elseif ($period === 'year') {
    $date_condition = "AND date(created_at) >= date('now', 'start of year')";
}

// Get total orders
$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE 1=1 $date_condition");
$total_orders = $stmt->fetch()['total'];

// Get total revenue (only completed/delivered)
$stmt = $pdo->query("SELECT SUM(total_price) as revenue FROM orders WHERE status IN ('Готов', 'Доставлен') $date_condition");
$total_revenue = $stmt->fetch()['revenue'] ?: 0;

// Get total area washed
$stmt = $pdo->query("SELECT SUM(total_area) as area FROM orders WHERE status != 'Отменен' $date_condition");
$total_area = $stmt->fetch()['area'] ?: 0;

// Get new orders today
$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE date(created_at) = date('now')");
$new_orders_today = $stmt->fetch()['total'];

// Orders by status
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders WHERE 1=1 $date_condition GROUP BY status");
$status_counts = $stmt->fetchAll();

require_once 'header.php';
?>

<div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
    <a href="?period=day" class="btn <?= $period === 'day' ? '' : 'btn-secondary' ?>">За день</a>
    <a href="?period=week" class="btn <?= $period === 'week' ? '' : 'btn-secondary' ?>">За неделю</a>
    <a href="?period=month" class="btn <?= $period === 'month' ? '' : 'btn-secondary' ?>">За месяц</a>
    <a href="?period=year" class="btn <?= $period === 'year' ? '' : 'btn-secondary' ?>">За год</a>
    <a href="?period=all" class="btn <?= $period === 'all' ? '' : 'btn-secondary' ?>">За все время</a>
</div>

<div class="grid-cards">
    <div class="card stat-card">
        <span class="title">Всего заказов</span>
        <span class="value"><?= number_format($total_orders, 0, '', ' ') ?></span>
    </div>
    <div class="card stat-card">
        <span class="title">Выручка (Готовые)</span>
        <span class="value"><?= number_format($total_revenue, 0, '', ' ') ?> ₸</span>
    </div>
    <div class="card stat-card">
        <span class="title">Постирано кв.м</span>
        <span class="value"><?= number_format($total_area, 1, '.', ' ') ?> м²</span>
    </div>
    <div class="card stat-card">
        <span class="title">Заказы сегодня</span>
        <span class="value"><?= number_format($new_orders_today, 0, '', ' ') ?></span>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom: 20px;">Заказы по статусам</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Статус</th>
                    <th>Количество</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($status_counts as $row): ?>
                <tr>
                    <td><span class="badge <?= getStatusClass($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                    <td><b><?= $row['count'] ?></b></td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($status_counts) === 0): ?>
                <tr>
                    <td colspan="2" style="text-align: center; color: var(--text-muted); padding: 30px;">Нет данных</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
