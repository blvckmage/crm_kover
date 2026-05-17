<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' — CRM' : 'CRM Чистка ковров' ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="overlay" id="overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="brand-icon"><i class="fa-solid fa-rug"></i></div>
            <div class="brand-text">
                KoverCRM
                <small>Чистка ковров</small>
            </div>
        </div>

        <div class="nav-section">
            <div class="nav-section-label">Навигация</div>
            <ul class="nav-links">
                <li>
                    <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-chart-line"></i> Аналитика
                    </a>
                </li>
                <li>
                    <a href="orders.php" class="<?= $current_page == 'orders.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-clipboard-list"></i> Заказы
                    </a>
                </li>
                <li>
                    <a href="order_form.php" class="<?= $current_page == 'order_form.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-circle-plus"></i> Новый заказ
                    </a>
                </li>
                <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <li>
                    <a href="users.php" class="<?= $current_page == 'users.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-users"></i> Пользователи
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="sidebar-user-info">
            <div class="sidebar-user-card">
                <div class="user-avatar">
                    <?= isset($_SESSION['username']) ? strtoupper(mb_substr($_SESSION['username'], 0, 1)) : 'Г' ?>
                </div>
                <div>
                    <div class="user-name"><?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Гость' ?></div>
                    <div class="user-role"><?= (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? 'Администратор' : 'Менеджер' ?></div>
                </div>
            </div>
            <a href="logout.php" class="btn btn-secondary btn-sm btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Выйти</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1>
                <button class="mobile-toggle" id="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
                <?= isset($page_title) ? $page_title : '<span class="page-icon"><i class="fa-solid fa-house"></i></span> Панель управления' ?>
            </h1>
            <div class="user-info user-info-header">
                <div class="user-chip">
                    <div class="user-chip-avatar">
                        <?= isset($_SESSION['username']) ? strtoupper(mb_substr($_SESSION['username'], 0, 1)) : 'Г' ?>
                    </div>
                    <?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Гость' ?>
                    <span class="chip-role"><?= (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? '· Админ' : '· Менеджер' ?></span>
                </div>
                <a href="logout.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Выход</a>
            </div>
        </div>
