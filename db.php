<?php
$db_url = getenv('DATABASE_URL');

try {
    if ($db_url) {
        // Парсим ссылку на PostgreSQL от Render (postgres://user:pass@host/dbname)
        $db_opts = parse_url($db_url);
        
        $dsn = "pgsql:host=" . $db_opts["host"] . ";port=" . ($db_opts["port"] ?? 5432) . ";dbname=" . ltrim($db_opts["path"], '/');
        $pdo = new PDO($dsn, $db_opts["user"], $db_opts["pass"]);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Создаем таблицы для PostgreSQL если их нет
        $stmt = $pdo->query("SELECT EXISTS (SELECT FROM pg_tables WHERE schemaname = 'public' AND tablename  = 'orders')");
        if (!$stmt->fetchColumn()) {
            $pdo->exec("
                CREATE TABLE orders (
                    id SERIAL PRIMARY KEY,
                    client_name VARCHAR(255) NOT NULL,
                    client_phone VARCHAR(255) NOT NULL,
                    carpet_width NUMERIC(10,2),
                    carpet_length NUMERIC(10,2),
                    total_area NUMERIC(10,2),
                    total_price NUMERIC(10,2),
                    status VARCHAR(50) DEFAULT 'Новый',
                    status_new_at TIMESTAMP,
                    status_washing_at TIMESTAMP,
                    status_drying_at TIMESTAMP,
                    status_ready_at TIMESTAMP,
                    status_delivered_at TIMESTAMP,
                    status_cancelled_at TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
        }
        
        $stmt = $pdo->query("SELECT EXISTS (SELECT FROM pg_tables WHERE schemaname = 'public' AND tablename  = 'users')");
        if (!$stmt->fetchColumn()) {
            $pdo->exec("
                CREATE TABLE users (
                    id SERIAL PRIMARY KEY,
                    username VARCHAR(255) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    role VARCHAR(50) NOT NULL DEFAULT 'manager',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            $default_admin_password = password_hash('admin', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO users (username, password, role) VALUES ('admin', '$default_admin_password', 'admin')");
        }
        
    } else {
        // Локальный режим или режим обычного виртуального хостинга (SQLite)
        $db_dir = getenv('RENDER_DISK_PATH') ?: __DIR__;
        $db_file = $db_dir . '/crm.sqlite';
        $db_exists = file_exists($db_file);
        
        $pdo = new PDO("sqlite:" . $db_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        if (!$db_exists) {
            $pdo->exec("
                CREATE TABLE orders (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    client_name TEXT NOT NULL,
                    client_phone TEXT NOT NULL,
                    carpet_width REAL,
                    carpet_length REAL,
                    total_area REAL,
                    total_price REAL,
                    status TEXT DEFAULT 'Новый',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
        }

        // Check if users table exists, if not create it
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        if (!$stmt->fetch()) {
            $pdo->exec("
                CREATE TABLE users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT UNIQUE NOT NULL,
                    password TEXT NOT NULL,
                    role TEXT NOT NULL DEFAULT 'manager',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            $default_admin_password = password_hash('admin', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO users (username, password, role) VALUES ('admin', '$default_admin_password', 'admin')");
        }

        // Add status timestamp columns if they don't exist
        $columns_to_add = [
            'status_new_at' => 'DATETIME',
            'status_washing_at' => 'DATETIME',
            'status_drying_at' => 'DATETIME',
            'status_ready_at' => 'DATETIME',
            'status_delivered_at' => 'DATETIME',
            'status_cancelled_at' => 'DATETIME'
        ];
        
        foreach ($columns_to_add as $col => $type) {
            try {
                $pdo->exec("ALTER TABLE orders ADD COLUMN $col $type");
            } catch (PDOException $e) {
                // Column probably already exists, ignore
            }
        }
    }
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

$statuses = [
    'Новый' => 'status-new',
    'В стирке' => 'status-washing',
    'Сушится' => 'status-drying',
    'Готов' => 'status-ready',
    'Доставлен' => 'status-delivered',
    'Отменен' => 'status-cancelled'
];

$status_columns = [
    'Новый' => 'status_new_at',
    'В стирке' => 'status_washing_at',
    'Сушится' => 'status_drying_at',
    'Готов' => 'status_ready_at',
    'Доставлен' => 'status_delivered_at',
    'Отменен' => 'status_cancelled_at'
];

function getStatusClass($status) {
    global $statuses;
    return isset($statuses[$status]) ? $statuses[$status] : 'badge';
}
?>
