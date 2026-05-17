<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Неверное имя пользователя или пароль";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — KoverCRM</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            background: #0f172a;
        }

        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #1e1b4b 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 60px 70px;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99,102,241,0.25) 0%, transparent 70%);
            top: -100px; right: -100px;
            pointer-events: none;
        }

        .login-left::after {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(139,92,246,0.2) 0%, transparent 70%);
            bottom: -50px; left: -50px;
            pointer-events: none;
        }

        .left-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 60px;
            position: relative;
            z-index: 1;
        }

        .left-brand-icon {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            box-shadow: 0 8px 24px rgba(99,102,241,0.4);
        }

        .left-brand-name {
            font-size: 24px;
            font-weight: 800;
            color: #f8fafc;
            letter-spacing: -0.5px;
        }

        .left-brand-sub {
            font-size: 12px;
            color: #6366f1;
            font-weight: 500;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .left-headline {
            font-size: 44px;
            font-weight: 800;
            color: #f8fafc;
            line-height: 1.15;
            letter-spacing: -1.5px;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .left-headline span {
            background: linear-gradient(135deg, #818cf8, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .left-desc {
            font-size: 16px;
            color: #94a3b8;
            line-height: 1.6;
            max-width: 380px;
            position: relative;
            z-index: 1;
            margin-bottom: 50px;
        }

        .left-features {
            display: flex;
            flex-direction: column;
            gap: 14px;
            position: relative;
            z-index: 1;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #cbd5e1;
            font-size: 14px;
            font-weight: 500;
        }

        .feature-item i {
            width: 32px; height: 32px;
            background: rgba(99,102,241,0.15);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: #818cf8;
            flex-shrink: 0;
        }

        .login-right {
            width: 480px;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px 50px;
        }

        .login-form-title {
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.8px;
            margin-bottom: 8px;
        }

        .login-form-sub {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 36px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 15px;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            color: #0f172a;
            background: white;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99,102,241,0.12);
        }

        .form-control::placeholder { color: #94a3b8; }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 500;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
            box-shadow: 0 4px 14px rgba(99,102,241,0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99,102,241,0.45);
            filter: brightness(1.05);
        }

        .btn-submit:active { transform: translateY(0); }

        @media (max-width: 900px) {
            .login-left { display: none; }
            .login-right { width: 100%; padding: 40px 28px; }
            body { background: #f8fafc; }
        }
    </style>
</head>
<body>
    <div class="login-left">
        <div class="left-brand">
            <div class="left-brand-icon"><i class="fa-solid fa-rug"></i></div>
            <div>
                <div class="left-brand-name">KoverCRM</div>
                <div class="left-brand-sub">Чистка ковров</div>
            </div>
        </div>

        <h1 class="left-headline">Управляйте<br>заказами <span>умнее</span></h1>
        <p class="left-desc">Профессиональная CRM-система для управления заказами на чистку ковров. Аналитика, отслеживание статусов и работа с клиентами в одном месте.</p>

        <div class="left-features">
            <div class="feature-item">
                <i class="fa-solid fa-chart-line"></i>
                Аналитика и отчёты в реальном времени
            </div>
            <div class="feature-item">
                <i class="fa-solid fa-list-check"></i>
                Полное управление статусами заказов
            </div>
            <div class="feature-item">
                <i class="fa-solid fa-users"></i>
                Топ клиентов и история заказов
            </div>
        </div>
    </div>

    <div class="login-right">
        <h2 class="login-form-title">Добро пожаловать</h2>
        <p class="login-form-sub">Войдите в систему для продолжения работы</p>

        <?php if ($error): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="username">Логин</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Введите логин" required autofocus>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Пароль</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Введите пароль" required>
                </div>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-right-to-bracket"></i> Войти в систему
            </button>
        </form>
    </div>
</body>
</html>
