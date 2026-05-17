<?php
require_once 'db.php';
require_once 'auth.php';

if (!isAdmin()) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $username = trim($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'] === 'admin' ? 'admin' : 'manager';
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $password, $role]);
            header("Location: users.php?msg=created");
            exit;
        } catch (PDOException $e) {
            $error = "Пользователь с таким именем уже существует.";
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id !== (int)$_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    }
    header("Location: users.php?msg=deleted");
    exit;
}

$page_title = '<span class="page-icon"><i class="fa-solid fa-users"></i></span> Пользователи';
require_once 'header.php';

$users = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY id DESC")->fetchAll();
?>

<div class="card" style="max-width: 600px; margin-bottom: 24px;">
    <h3 style="margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid var(--border-color);">
        <span class="chart-icon"><i class="fa-solid fa-user-plus"></i></span> Добавить пользователя
    </h3>
    <?php if (isset($error)): ?>
        <div class="alert alert-error" style="margin-top: 15px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
        <div class="alert alert-success" style="margin-top: 15px;">Пользователь добавлен.</div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success" style="margin-top: 15px;">Пользователь удален.</div>
    <?php endif; ?>
    
    <form action="" method="POST" style="margin-top: 15px;">
        <input type="hidden" name="action" value="add_user">
        <div class="form-group">
            <label>Имя пользователя</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Пароль</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Роль</label>
            <select name="role" class="form-control">
                <option value="manager">Менеджер</option>
                <option value="admin">Администратор</option>
            </select>
        </div>
        <button type="submit" class="btn">Добавить</button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Логин</th>
                    <th>Роль</th>
                    <th>Дата создания</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= $user['role'] === 'admin' ? '<span class="badge status-new">Админ</span>' : '<span class="badge status-pickup">Менеджер</span>' ?></td>
                    <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                    <td>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <a href="users.php?delete=<?= $user['id'] ?>" class="btn btn-secondary btn-sm" style="color: var(--danger); border-color: #fecaca;" title="Удалить" onclick="return confirm('Вы уверены?')"><i class="fa-solid fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
