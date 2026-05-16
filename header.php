<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Чистка ковров</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="overlay" id="overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <i class="fa-solid fa-rug"></i> CRM
        </div>
        <ul class="nav-links">
            <li><a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>"><i class="fa-solid fa-chart-line"></i> Аналитика</a></li>
            <li><a href="orders.php" class="<?= $current_page == 'orders.php' ? 'active' : '' ?>"><i class="fa-solid fa-clipboard-list"></i> Заказы</a></li>
            <li><a href="order_form.php" class="<?= $current_page == 'order_form.php' ? 'active' : '' ?>"><i class="fa-solid fa-plus-circle"></i> Новый заказ</a></li>
            <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <li><a href="users.php" class="<?= $current_page == 'users.php' ? 'active' : '' ?>"><i class="fa-solid fa-users"></i> Пользователи</a></li>
            <?php endif; ?>
        </ul>
        
        <div class="sidebar-user-info">
            <div class="user-name"><i class="fa-solid fa-user"></i> <?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Гость' ?></div>
            <div class="user-role"><?= (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? 'Администратор' : 'Менеджер' ?></div>
            <a href="logout.php" class="btn btn-secondary btn-sm btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Выйти</a>
        </div>
    </aside>
    <main class="main-content">
        <div class="header">
            <h1>
                <button class="mobile-toggle" id="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
                <?= isset($page_title) ? $page_title : 'Панель управления' ?>
            </h1>
            <div class="user-info" style="display: flex; gap: 15px; align-items: center;">
                <span class="badge status-new" style="font-size: 14px; padding: 6px 14px;">
                    <i class="fa-solid fa-user"></i> <?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Гость' ?>
                    (<?= (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? 'Админ' : 'Менеджер' ?>)
                </span>
                <a href="logout.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Выход</a>
            </div>
        </div>
