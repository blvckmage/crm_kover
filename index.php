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
$stmt = $pdo->query("SELECT SUM(total_area) as area FROM orders WHERE status IN ('В стирке', 'Сушится', 'Готов', 'Доставлен') $date_condition");
$total_area = $stmt->fetch()['area'] ?: 0;

// Get new orders today
$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE date(created_at) = date('now')");
$new_orders_today = $stmt->fetch()['total'];

// Orders by status
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders WHERE 1=1 $date_condition GROUP BY status");
$status_counts = $stmt->fetchAll();

// Prepare Status Chart Data
$chart_labels = [];
$chart_data = [];
$chart_colors = [];
foreach($status_counts as $row) {
    $chart_labels[] = $row['status'];
    $chart_data[] = $row['count'];
    if ($row['status'] == 'Новый') $chart_colors[] = '#8b5cf6';
    elseif ($row['status'] == 'В стирке') $chart_colors[] = '#f59e0b';
    elseif ($row['status'] == 'Сушится') $chart_colors[] = '#ea580c';
    elseif ($row['status'] == 'Готов') $chart_colors[] = '#10b981';
    elseif ($row['status'] == 'Доставлен') $chart_colors[] = '#166534';
    elseif ($row['status'] == 'Отменен') $chart_colors[] = '#ef4444';
    else $chart_colors[] = '#3b82f6';
}

// Prepare Trend Chart Data
$trend_stmt = $pdo->query("SELECT date(created_at) as d, COUNT(*) as c FROM orders WHERE 1=1 $date_condition GROUP BY date(created_at) ORDER BY d ASC");
$trend_data_raw = $trend_stmt->fetchAll();
$trend_labels = [];
$trend_data = [];
foreach($trend_data_raw as $row) {
    $trend_labels[] = date('d.m', strtotime($row['d']));
    $trend_data[] = $row['c'];
}

// --- Financial dynamic (Revenue by day)
$revenue_stmt = $pdo->query("SELECT date(created_at) as d, SUM(total_price) as r FROM orders WHERE status IN ('Готов', 'Доставлен') $date_condition GROUP BY date(created_at) ORDER BY d ASC");
$revenue_data_raw = $revenue_stmt->fetchAll();
$revenue_labels = [];
$revenue_data = [];
foreach($revenue_data_raw as $row) {
    $revenue_labels[] = date('d.m', strtotime($row['d']));
    $revenue_data[] = $row['r'];
}

// --- Average check and Square footage
$avg_stmt = $pdo->query("SELECT date(created_at) as d, AVG(total_price) as avg_p, AVG(total_area) as avg_a FROM orders WHERE status != 'Отменен' $date_condition GROUP BY date(created_at) ORDER BY d ASC");
$avg_raw = $avg_stmt->fetchAll();
$avg_labels = [];
$avg_price_data = [];
foreach($avg_raw as $row) {
    $avg_labels[] = date('d.m', strtotime($row['d']));
    $avg_price_data[] = round($row['avg_p']);
}

// --- Bottlenecks (Average time spent in each stage)
$time_stmt = $pdo->query("SELECT status_new_at, status_washing_at, status_drying_at, status_ready_at, status_delivered_at FROM orders WHERE 1=1 $date_condition");
$time_rows = $time_stmt->fetchAll();
$times = ['to_wash' => [], 'to_dry' => [], 'to_ready' => [], 'to_deliver' => []];

foreach ($time_rows as $row) {
    if ($row['status_new_at'] && $row['status_washing_at']) {
        $times['to_wash'][] = strtotime($row['status_washing_at']) - strtotime($row['status_new_at']);
    }
    if ($row['status_washing_at'] && $row['status_drying_at']) {
        $times['to_dry'][] = strtotime($row['status_drying_at']) - strtotime($row['status_washing_at']);
    }
    if ($row['status_drying_at'] && $row['status_ready_at']) {
        $times['to_ready'][] = strtotime($row['status_ready_at']) - strtotime($row['status_drying_at']);
    }
    if ($row['status_ready_at'] && $row['status_delivered_at']) {
        $times['to_deliver'][] = strtotime($row['status_delivered_at']) - strtotime($row['status_ready_at']);
    }
}
function getAvgHours($arr) {
    if (count($arr) === 0) return 0;
    return round(array_sum($arr) / count($arr) / 3600, 1);
}
$time_data = [
    getAvgHours($times['to_wash']),
    getAvgHours($times['to_dry']),
    getAvgHours($times['to_ready']),
    getAvgHours($times['to_deliver'])
];

// --- Top 10 Clients
$top_clients_stmt = $pdo->query("SELECT client_name, client_phone, SUM(total_price) as spent, SUM(total_area) as total_sqm FROM orders WHERE status != 'Отменен' $date_condition GROUP BY client_phone, client_name ORDER BY spent DESC LIMIT 10");
$top_clients = $top_clients_stmt->fetchAll();

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

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 24px;">
    <div class="card" style="margin-bottom: 0;">
        <h3 style="margin-bottom: 20px;">Доли по статусам</h3>
        <div style="position: relative; height: 300px; width: 100%; display: flex; justify-content: center;">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
    <div class="card" style="margin-bottom: 0;">
        <h3 style="margin-bottom: 20px;">Динамика создания заказов</h3>
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="trendChart"></canvas>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 24px;">
    <div class="card" style="margin-bottom: 0;">
        <h3 style="margin-bottom: 20px;">Выручка по дням (₸)</h3>
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
    <div class="card" style="margin-bottom: 0;">
        <h3 style="margin-bottom: 20px;">Средний чек по дням (₸)</h3>
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="avgPriceChart"></canvas>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 24px;">
    <div class="card" style="margin-bottom: 0;">
        <h3 style="margin-bottom: 20px;">Скорость работы (в часах)</h3>
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="timeChart"></canvas>
        </div>
    </div>
    <div class="card" style="margin-bottom: 0; overflow-x: auto;">
        <h3 style="margin-bottom: 20px;">Топ-10 Клиентов</h3>
        <table style="width: 100%; font-size: 13px;">
            <thead><tr><th style="padding: 10px;">Имя</th><th style="padding: 10px;">Сумма</th><th style="padding: 10px;">Площадь</th></tr></thead>
            <tbody>
                <?php foreach($top_clients as $tc): ?>
                <tr>
                    <td style="padding: 10px;"><?= htmlspecialchars($tc['client_name']) ?><br><small style="color:var(--text-muted);"><?= htmlspecialchars($tc['client_phone']) ?></small></td>
                    <td style="padding: 10px;"><b><?= number_format($tc['spent'], 0, '', ' ') ?> ₸</b></td>
                    <td style="padding: 10px;"><?= $tc['total_sqm'] ?> м²</td>
                </tr>
                <?php endforeach; ?>
                <?php if(!$top_clients) echo "<tr><td colspan='3' style='padding:10px; text-align:center;'>Нет данных</td></tr>"; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            data: <?= json_encode($chart_data) ?>,
            backgroundColor: <?= json_encode($chart_colors) ?>,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($trend_labels) ?>,
        datasets: [{
            label: 'Количество заказов',
            data: <?= json_encode($trend_data) ?>,
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});

const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($revenue_labels) ?>,
        datasets: [{
            label: 'Выручка (₸)',
            data: <?= json_encode($revenue_data) ?>,
            backgroundColor: '#10b981',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

const avgPriceCtx = document.getElementById('avgPriceChart').getContext('2d');
new Chart(avgPriceCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($avg_labels) ?>,
        datasets: [{
            label: 'Средний чек (₸)',
            data: <?= json_encode($avg_price_data) ?>,
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

const timeCtx = document.getElementById('timeChart').getContext('2d');
new Chart(timeCtx, {
    type: 'bar',
    data: {
        labels: ['Ожидание стирки', 'Стирка', 'Сушка', 'Ожидание доставки'],
        datasets: [{
            label: 'Среднее время (часы)',
            data: <?= json_encode($time_data) ?>,
            backgroundColor: ['#8b5cf6', '#3b82f6', '#ea580c', '#166534'],
            borderRadius: 4
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true } }
    }
});
</script>

<?php require_once 'footer.php'; ?>
