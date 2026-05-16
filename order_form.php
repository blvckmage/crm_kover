<?php
require_once 'db.php';
require_once 'auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = null;

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    if (!$order) {
        header("Location: orders.php");
        exit;
    }
}

$page_title = $id > 0 ? 'Редактировать заказ #' . $id : 'Новый заказ';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name = $_POST['client_name'];
    $client_phone = $_POST['client_phone'];
    $carpet_width = (float)$_POST['carpet_width'];
    $carpet_length = (float)$_POST['carpet_length'];
    $total_area = (float)$_POST['total_area'];
    $total_price = (float)$_POST['total_price'];
    $status = $_POST['status'];

    if ($id > 0) {
        $old_order = $pdo->query("SELECT status FROM orders WHERE id = $id")->fetch();
        $time_update = "";
        if ($old_order && $old_order['status'] !== $status) {
            $col = $status_columns[$status] ?? null;
            if ($col) $time_update = ", $col = CURRENT_TIMESTAMP";
        }
        $stmt = $pdo->prepare("UPDATE orders SET client_name=?, client_phone=?, carpet_width=?, carpet_length=?, total_area=?, total_price=?, status=?$time_update WHERE id=?");
        $stmt->execute([$client_name, $client_phone, $carpet_width, $carpet_length, $total_area, $total_price, $status, $id]);
        header("Location: orders.php?msg=updated");
    } else {
        $col = $status_columns[$status] ?? null;
        $cols = "client_name, client_phone, carpet_width, carpet_length, total_area, total_price, status";
        $vals = "?, ?, ?, ?, ?, ?, ?";
        if ($col) {
            $cols .= ", $col";
            $vals .= ", CURRENT_TIMESTAMP";
        }
        $stmt = $pdo->prepare("INSERT INTO orders ($cols) VALUES ($vals)");
        $stmt->execute([$client_name, $client_phone, $carpet_width, $carpet_length, $total_area, $total_price, $status]);
        header("Location: orders.php?msg=created");
    }
    exit;
}

require_once 'header.php';
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <form action="" method="POST">
        <div class="form-group">
            <label>Имя клиента</label>
            <input type="text" name="client_name" class="form-control" value="<?= $order ? htmlspecialchars($order['client_name']) : '' ?>" required>
        </div>
        <div class="form-group">
            <label>Номер телефона</label>
            <input type="text" name="client_phone" class="form-control" value="<?= $order ? htmlspecialchars($order['client_phone']) : '' ?>" placeholder="+7 (___) ___-__-__" required>
        </div>
        
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1; min-width: 150px;">
                <label>Ширина ковра (м)</label>
                <input type="number" step="0.01" name="carpet_width" id="width" class="form-control" value="<?= $order ? $order['carpet_width'] : '' ?>" required oninput="calculate()">
            </div>
            <div class="form-group" style="flex: 1; min-width: 150px;">
                <label>Длина ковра (м)</label>
                <input type="number" step="0.01" name="carpet_length" id="length" class="form-control" value="<?= $order ? $order['carpet_length'] : '' ?>" required oninput="calculate()">
            </div>
        </div>

        <div class="form-group">
            <label>Общая квадратура (м²)</label>
            <input type="number" step="0.01" name="total_area" id="area" class="form-control" value="<?= $order ? $order['total_area'] : '' ?>" required>
        </div>
        
        <div class="form-group">
            <label>Цена за 1 м² (₸) - для авторасчета</label>
            <input type="number" id="price_per_sqm" class="form-control" value="1000" oninput="calculate()">
            <small style="color: var(--text-muted); display: block; margin-top: 5px;">Значение только для удобства, не сохраняется в базе</small>
        </div>

        <div class="form-group">
            <label>Итоговая сумма (₸)</label>
            <input type="number" step="1" name="total_price" id="total_price" class="form-control" value="<?= $order ? $order['total_price'] : '' ?>" required>
        </div>

        <div class="form-group">
            <label>Статус</label>
            <select name="status" class="form-control">
                <?php foreach($statuses as $name => $class): ?>
                    <option value="<?= $name ?>" <?= ($order && $order['status'] === $name) ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-top: 30px; display: flex; gap: 10px;">
            <button type="submit" class="btn"><i class="fa-solid fa-save"></i> <?= $id > 0 ? 'Сохранить изменения' : 'Создать заказ' ?></button>
            <a href="orders.php" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>

<script>
function calculate() {
    let width = parseFloat(document.getElementById('width').value) || 0;
    let length = parseFloat(document.getElementById('length').value) || 0;
    let pricePerSqm = parseFloat(document.getElementById('price_per_sqm').value) || 0;
    
    let area = width * length;
    let total = area * pricePerSqm;
    
    document.getElementById('area').value = area.toFixed(2);
    document.getElementById('total_price').value = Math.round(total);
}
</script>

<?php require_once 'footer.php'; ?>
