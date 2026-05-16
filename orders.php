<?php
require_once 'db.php';
require_once 'auth.php';

// Handle status update via Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];
    
    if (isset($statuses[$status])) {
        $col = $status_columns[$status] ?? null;
        if ($col) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, $col = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$status, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
        }
    }
    
    header("Location: orders.php?msg=status_updated");
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$id]);
    header("Location: orders.php?msg=deleted");
    exit;
}

$page_title = 'Список заказов';
require_once 'header.php';

$where = [];
$params = [];

if (!empty($_GET['search'])) {
    $search = $_GET['search'];
    $where[] = "(client_name LIKE ? OR client_phone LIKE ? OR id = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = (int)$search;
}

if (!empty($_GET['status_filter'])) {
    $where[] = "status = ?";
    $params[] = $_GET['status_filter'];
}

$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

// Count for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders $where_sql");
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();

$limit = 50;
$total_pages = ceil($total_records / $limit);
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}
$offset = ($page - 1) * $limit;

// Fetch records
$sql = "SELECT * FROM orders $where_sql ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<form method="GET" action="orders.php" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
    <input type="text" name="search" placeholder="Поиск по имени, номеру или ID" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" class="form-control" style="flex: 1; min-width: 200px; padding: 8px 12px;">
    <select name="status_filter" class="form-control" style="width: auto; padding: 8px 12px;">
        <option value="">Все статусы</option>
        <?php foreach($statuses as $name => $class): ?>
            <option value="<?= $name ?>" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] === $name) ? 'selected' : '' ?>><?= $name ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn"><i class="fa-solid fa-search"></i> Найти</button>
    <?php if(!empty($_GET['search']) || !empty($_GET['status_filter'])): ?>
        <a href="orders.php" class="btn btn-secondary">Сбросить</a>
    <?php endif; ?>
</form>

<div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
    <a href="order_form.php" class="btn"><i class="fa-solid fa-plus"></i> Создать заказ</a>
    <a href="export.php?format=excel" class="btn btn-secondary"><i class="fa-solid fa-file-excel"></i> Экспорт Excel</a>
    <a href="export.php?format=csv" class="btn btn-secondary"><i class="fa-solid fa-file-csv"></i> Экспорт CSV</a>
</div>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] == 'status_updated'): ?>
        <div class="alert alert-success">Статус заказа успешно обновлен.</div>
    <?php elseif($_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-success">Заказ успешно удален.</div>
    <?php elseif($_GET['msg'] == 'created'): ?>
        <div class="alert alert-success">Заказ успешно создан.</div>
    <?php elseif($_GET['msg'] == 'updated'): ?>
        <div class="alert alert-success">Данные заказа успешно обновлены.</div>
    <?php endif; ?>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Клиент</th>
                    <th>Телефон</th>
                    <th>Размеры (ШxД)</th>
                    <th>Площадь</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orders as $order): ?>
                <tr>
                    <td>#<?= $order['id'] ?></td>
                    <td><?= htmlspecialchars($order['client_name']) ?></td>
                    <td><?= htmlspecialchars($order['client_phone']) ?></td>
                    <td><?= $order['carpet_width'] ?>м x <?= $order['carpet_length'] ?>м</td>
                    <td><?= $order['total_area'] ?> м²</td>
                    <td><b><?= number_format($order['total_price'], 0, '', ' ') ?> ₸</b></td>
                    <td>
                        <form action="orders.php" method="POST" style="margin:0;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?= $order['id'] ?>">
                            <select name="status" class="status-select <?= getStatusClass($order['status']) ?>" onchange="this.className = 'status-select ' + this.options[this.selectedIndex].className; this.form.submit()">
                                <?php foreach($statuses as $name => $class): ?>
                                    <option value="<?= $name ?>" class="<?= $class ?>" <?= $order['status'] === $name ? 'selected' : '' ?>><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                    <td>
                        <a href="order_form.php?id=<?= $order['id'] ?>" class="btn btn-secondary btn-sm" title="Редактировать"><i class="fa-solid fa-pen"></i></a>
                        <a href="orders.php?delete=<?= $order['id'] ?>" class="btn btn-secondary btn-sm" style="color: var(--danger); border-color: #fecaca;" title="Удалить" onclick="return confirm('Вы уверены что хотите удалить этот заказ?')"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($orders) === 0): ?>
                <tr>
                    <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 30px;">Нет заказов</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if($total_pages > 1): ?>
<div style="margin-top: 20px; display: flex; justify-content: center; gap: 5px; margin-bottom: 30px; flex-wrap: wrap;">
    <?php for($i = 1; $i <= $total_pages; $i++): ?>
        <?php 
            $qs = $_GET;
            $qs['page'] = $i;
            $link = '?' . http_build_query($qs);
        ?>
        <a href="<?= $link ?>" class="btn <?= $i === $page ? '' : 'btn-secondary' ?>" style="padding: 6px 12px;"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
