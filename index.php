<?php
require_once 'db.php';
require_once 'auth.php';

$page_title = '<span class="page-icon"><i class="fa-solid fa-chart-line"></i></span> Аналитика';
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

$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE 1=1 $date_condition");
$total_orders = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT SUM(total_price) as revenue FROM orders WHERE status IN ('Готов', 'Доставлен') $date_condition");
$total_revenue = $stmt->fetch()['revenue'] ?: 0;

$stmt = $pdo->query("SELECT SUM(total_area) as area FROM orders WHERE status IN ('В стирке', 'Сушится', 'Готов', 'Доставлен') $date_condition");
$total_area = $stmt->fetch()['area'] ?: 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE date(created_at) = date('now')");
$new_orders_today = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders WHERE 1=1 $date_condition GROUP BY status");
$status_counts = $stmt->fetchAll();

$chart_labels = [];
$chart_data = [];
$chart_colors = [];
foreach($status_counts as $row) {
    $chart_labels[] = $row['status'];
    $chart_data[] = $row['count'];
    if ($row['status'] == 'Новый')      $chart_colors[] = '#8b5cf6';
    elseif ($row['status'] == 'В стирке')  $chart_colors[] = '#f59e0b';
    elseif ($row['status'] == 'Сушится')   $chart_colors[] = '#ea580c';
    elseif ($row['status'] == 'Готов')     $chart_colors[] = '#10b981';
    elseif ($row['status'] == 'Доставлен') $chart_colors[] = '#059669';
    elseif ($row['status'] == 'Отменен')   $chart_colors[] = '#ef4444';
    else $chart_colors[] = '#3b82f6';
}

$trend_stmt = $pdo->query("SELECT date(created_at) as d, COUNT(*) as c FROM orders WHERE 1=1 $date_condition GROUP BY date(created_at) ORDER BY d ASC");
$trend_data_raw = $trend_stmt->fetchAll();
$trend_labels = [];
$trend_data = [];
foreach($trend_data_raw as $row) {
    $trend_labels[] = date('d.m', strtotime($row['d']));
    $trend_data[] = $row['c'];
}

$revenue_stmt = $pdo->query("SELECT date(created_at) as d, SUM(total_price) as r FROM orders WHERE status IN ('Готов', 'Доставлен') $date_condition GROUP BY date(created_at) ORDER BY d ASC");
$revenue_data_raw = $revenue_stmt->fetchAll();
$revenue_labels = [];
$revenue_data = [];
foreach($revenue_data_raw as $row) {
    $revenue_labels[] = date('d.m', strtotime($row['d']));
    $revenue_data[] = $row['r'];
}

$avg_stmt = $pdo->query("SELECT date(created_at) as d, AVG(total_price) as avg_p FROM orders WHERE status != 'Отменен' $date_condition GROUP BY date(created_at) ORDER BY d ASC");
$avg_raw = $avg_stmt->fetchAll();
$avg_labels = [];
$avg_price_data = [];
foreach($avg_raw as $row) {
    $avg_labels[] = date('d.m', strtotime($row['d']));
    $avg_price_data[] = round($row['avg_p']);
}

$time_stmt = $pdo->query("SELECT status_new_at, status_washing_at, status_drying_at, status_ready_at, status_delivered_at FROM orders WHERE 1=1 $date_condition");
$time_rows = $time_stmt->fetchAll();
$times = ['to_wash' => [], 'to_dry' => [], 'to_ready' => [], 'to_deliver' => []];

foreach ($time_rows as $row) {
    if ($row['status_new_at'] && $row['status_washing_at'])
        $times['to_wash'][] = strtotime($row['status_washing_at']) - strtotime($row['status_new_at']);
    if ($row['status_washing_at'] && $row['status_drying_at'])
        $times['to_dry'][] = strtotime($row['status_drying_at']) - strtotime($row['status_washing_at']);
    if ($row['status_drying_at'] && $row['status_ready_at'])
        $times['to_ready'][] = strtotime($row['status_ready_at']) - strtotime($row['status_drying_at']);
    if ($row['status_ready_at'] && $row['status_delivered_at'])
        $times['to_deliver'][] = strtotime($row['status_delivered_at']) - strtotime($row['status_ready_at']);
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

$top_clients_stmt = $pdo->query("SELECT client_name, client_phone, SUM(total_price) as spent, SUM(total_area) as total_sqm FROM orders WHERE status != 'Отменен' $date_condition GROUP BY client_phone, client_name ORDER BY spent DESC LIMIT 10");
$top_clients = $top_clients_stmt->fetchAll();

require_once 'header.php';
?>

<!-- Period Tabs -->
<div class="period-tabs">
    <a href="?period=day"   class="period-tab <?= $period === 'day'   ? 'active' : '' ?>">Сегодня</a>
    <a href="?period=week"  class="period-tab <?= $period === 'week'  ? 'active' : '' ?>">7 дней</a>
    <a href="?period=month" class="period-tab <?= $period === 'month' ? 'active' : '' ?>">Месяц</a>
    <a href="?period=year"  class="period-tab <?= $period === 'year'  ? 'active' : '' ?>">Год</a>
    <a href="?period=all"   class="period-tab <?= $period === 'all'   ? 'active' : '' ?>">Всё время</a>
</div>

<!-- Stat Cards -->
<div class="grid-cards">
    <div class="card stat-card accent-purple">
        <div class="stat-icon icon-purple"><i class="fa-solid fa-box-archive"></i></div>
        <div class="stat-info">
            <div class="title">Всего заказов</div>
            <div class="value"><?= number_format($total_orders, 0, '', ' ') ?></div>
        </div>
    </div>
    <div class="card stat-card accent-green">
        <div class="stat-icon icon-green"><i class="fa-solid fa-sack-dollar"></i></div>
        <div class="stat-info">
            <div class="title">Выручка</div>
            <div class="value"><?= number_format($total_revenue, 0, '', ' ') ?> <span style="font-size:16px;font-weight:600;">₸</span></div>
        </div>
    </div>
    <div class="card stat-card accent-blue">
        <div class="stat-icon icon-blue"><i class="fa-solid fa-ruler-combined"></i></div>
        <div class="stat-info">
            <div class="title">Постирано</div>
            <div class="value"><?= number_format($total_area, 1, '.', ' ') ?> <span style="font-size:16px;font-weight:600;">м²</span></div>
        </div>
    </div>
    <div class="card stat-card accent-amber">
        <div class="stat-icon icon-amber"><i class="fa-solid fa-bell"></i></div>
        <div class="stat-info">
            <div class="title">Заказы сегодня</div>
            <div class="value"><?= number_format($new_orders_today, 0, '', ' ') ?></div>
        </div>
    </div>
</div>

<!-- Status Table + Donut Chart -->
<div class="charts-grid">
    <div class="card" style="margin-bottom:0;">
        <h3 style="margin-bottom:18px;"><span class="chart-icon"><i class="fa-solid fa-list-check"></i></span> Заказы по статусам</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Статус</th>
                        <th>Кол-во</th>
                        <th>Доля</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_for_share = array_sum(array_column($status_counts, 'count'));
                    foreach($status_counts as $row):
                        $pct = $total_for_share > 0 ? round($row['count'] / $total_for_share * 100) : 0;
                    ?>
                    <tr>
                        <td><span class="badge <?= getStatusClass($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                        <td><b><?= $row['count'] ?></b></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="flex:1;height:6px;background:#f1f5f9;border-radius:3px;min-width:60px;">
                                    <div style="height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg,#6366f1,#8b5cf6);border-radius:3px;"></div>
                                </div>
                                <span style="font-size:12px;color:var(--text-muted);font-weight:500;min-width:30px;"><?= $pct ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(count($status_counts) === 0): ?>
                    <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:30px;">Нет данных</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-bottom:0;">
        <h3 style="margin-bottom:18px;"><span class="chart-icon"><i class="fa-solid fa-chart-pie"></i></span> Доли по статусам</h3>
        <div class="chart-wrapper-sm">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
</div>

<!-- Trend + Revenue -->
<div class="charts-grid" style="margin-top:20px;">
    <div class="card" style="margin-bottom:0;">
        <h3 style="margin-bottom:18px;"><span class="chart-icon"><i class="fa-solid fa-arrow-trend-up"></i></span> Динамика заказов</h3>
        <div class="chart-wrapper">
            <canvas id="trendChart"></canvas>
        </div>
    </div>
    <div class="card" style="margin-bottom:0;">
        <h3 style="margin-bottom:18px;"><span class="chart-icon"><i class="fa-solid fa-coins"></i></span> Выручка по дням (₸)</h3>
        <div class="chart-wrapper">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
</div>

<!-- Avg Check + Process Time -->
<div class="charts-grid" style="margin-top:20px;">
    <div class="card" style="margin-bottom:0;">
        <h3 style="margin-bottom:18px;"><span class="chart-icon"><i class="fa-solid fa-receipt"></i></span> Средний чек по дням (₸)</h3>
        <div class="chart-wrapper">
            <canvas id="avgPriceChart"></canvas>
        </div>
    </div>
    <div class="card" style="margin-bottom:0;">
        <h3 style="margin-bottom:18px;"><span class="chart-icon"><i class="fa-solid fa-gauge"></i></span> Скорость обработки (часы)</h3>
        <div class="chart-wrapper">
            <canvas id="timeChart"></canvas>
        </div>
    </div>
</div>

<!-- Top Clients -->
<div class="card" style="margin-top:20px;">
    <h3 style="margin-bottom:18px;"><span class="chart-icon"><i class="fa-solid fa-trophy"></i></span> Топ-10 клиентов</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Клиент</th>
                    <th>Сумма</th>
                    <th>Площадь</th>
                    <th>Доля выручки</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $max_spent = count($top_clients) > 0 ? $top_clients[0]['spent'] : 1;
                foreach($top_clients as $i => $tc):
                    $bar_pct = $max_spent > 0 ? round($tc['spent'] / $max_spent * 100) : 0;
                ?>
                <tr>
                    <td><span class="rank-badge rank-<?= $i+1 ?>"><?= $i+1 ?></span></td>
                    <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($tc['client_name']) ?></div>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?= htmlspecialchars($tc['client_phone']) ?></div>
                    </td>
                    <td><span style="font-weight:700;color:var(--text-main);"><?= number_format($tc['spent'], 0, '', ' ') ?> ₸</span></td>
                    <td><?= $tc['total_sqm'] ?> м²</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;height:7px;background:#f1f5f9;border-radius:4px;min-width:80px;">
                                <div style="height:100%;width:<?= $bar_pct ?>%;background:linear-gradient(90deg,#10b981,#06d6a0);border-radius:4px;"></div>
                            </div>
                            <span style="font-size:12px;color:var(--text-muted);font-weight:500;"><?= $bar_pct ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(!$top_clients): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:30px;">Нет данных</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#64748b';
Chart.defaults.plugins.tooltip.padding = 12;
Chart.defaults.plugins.tooltip.cornerRadius = 10;
Chart.defaults.plugins.tooltip.titleFont = { size: 13, weight: '600' };
Chart.defaults.plugins.tooltip.bodyFont = { size: 12 };
Chart.defaults.plugins.tooltip.backgroundColor = '#0f172a';
Chart.defaults.plugins.tooltip.titleColor = '#f8fafc';
Chart.defaults.plugins.tooltip.bodyColor = '#94a3b8';

function makeGradient(ctx, color1, color2) {
    const g = ctx.createLinearGradient(0, 0, 0, 300);
    g.addColorStop(0, color1);
    g.addColorStop(1, color2);
    return g;
}

/* ---- Donut: Status ---- */
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            data: <?= json_encode($chart_data) ?>,
            backgroundColor: <?= json_encode($chart_colors) ?>,
            hoverBackgroundColor: <?= json_encode($chart_colors) ?>,
            borderWidth: 3,
            borderColor: '#ffffff',
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 16,
                    usePointStyle: true,
                    pointStyle: 'circle',
                    font: { size: 12, weight: '500' }
                }
            },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.label}: ${ctx.parsed} заказ(ов)`
                }
            }
        },
        animation: { animateRotate: true, duration: 800, easing: 'easeOutQuart' }
    }
});

/* ---- Line: Trend ---- */
const trendCtx = document.getElementById('trendChart').getContext('2d');
const trendGrad = makeGradient(trendCtx, 'rgba(99,102,241,0.3)', 'rgba(99,102,241,0.0)');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($trend_labels) ?>,
        datasets: [{
            label: 'Заказы',
            data: <?= json_encode($trend_data) ?>,
            borderColor: '#6366f1',
            backgroundColor: trendGrad,
            borderWidth: 2.5,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#6366f1',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0, font: { size: 11 } },
                grid: { color: '#f1f5f9', drawBorder: false }
            },
            x: {
                ticks: { font: { size: 11 } },
                grid: { display: false }
            }
        },
        animation: { duration: 700, easing: 'easeOutQuart' }
    }
});

/* ---- Bar: Revenue ---- */
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revGrad = makeGradient(revenueCtx, 'rgba(16,185,129,0.9)', 'rgba(16,185,129,0.5)');
new Chart(revenueCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($revenue_labels) ?>,
        datasets: [{
            label: 'Выручка ₸',
            data: <?= json_encode($revenue_data) ?>,
            backgroundColor: revGrad,
            borderRadius: 8,
            borderSkipped: false,
            hoverBackgroundColor: '#10b981'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    font: { size: 11 },
                    callback: v => v >= 1000 ? (v/1000).toFixed(0) + 'k ₸' : v + ' ₸'
                },
                grid: { color: '#f1f5f9', drawBorder: false }
            },
            x: {
                ticks: { font: { size: 11 } },
                grid: { display: false }
            }
        },
        animation: { duration: 700 }
    }
});

/* ---- Line: Avg Price ---- */
const avgCtx = document.getElementById('avgPriceChart').getContext('2d');
const avgGrad = makeGradient(avgCtx, 'rgba(245,158,11,0.3)', 'rgba(245,158,11,0.0)');
new Chart(avgCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($avg_labels) ?>,
        datasets: [{
            label: 'Средний чек ₸',
            data: <?= json_encode($avg_price_data) ?>,
            borderColor: '#f59e0b',
            backgroundColor: avgGrad,
            borderWidth: 2.5,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#f59e0b',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    font: { size: 11 },
                    callback: v => v >= 1000 ? (v/1000).toFixed(0) + 'k ₸' : v + ' ₸'
                },
                grid: { color: '#f1f5f9', drawBorder: false }
            },
            x: {
                ticks: { font: { size: 11 } },
                grid: { display: false }
            }
        },
        animation: { duration: 700 }
    }
});

/* ---- Horizontal Bar: Process Time ---- */
const timeCtx = document.getElementById('timeChart').getContext('2d');
new Chart(timeCtx, {
    type: 'bar',
    data: {
        labels: ['Ожидание стирки', 'Стирка → Сушка', 'Сушка → Готов', 'Готов → Доставлен'],
        datasets: [{
            label: 'Часов',
            data: <?= json_encode($time_data) ?>,
            backgroundColor: [
                'rgba(139,92,246,0.85)',
                'rgba(59,130,246,0.85)',
                'rgba(234,88,12,0.85)',
                'rgba(16,185,129,0.85)'
            ],
            hoverBackgroundColor: [
                '#8b5cf6', '#3b82f6', '#ea580c', '#10b981'
            ],
            borderRadius: 8,
            borderSkipped: false,
            barThickness: 28
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: { label: ctx => ` ${ctx.parsed.x} ч.` }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: {
                    font: { size: 11 },
                    callback: v => v + ' ч.'
                },
                grid: { color: '#f1f5f9', drawBorder: false }
            },
            y: {
                ticks: { font: { size: 12 } },
                grid: { display: false }
            }
        },
        animation: { duration: 700 }
    }
});
</script>

<?php require_once 'footer.php'; ?>
