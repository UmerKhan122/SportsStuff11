<?php
$db_file = __DIR__ . '/../amez_db.sqlite';
$needs_init = !file_exists($db_file);

try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($needs_init) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_name TEXT NOT NULL,
                customer_whatsapp TEXT NOT NULL,
                total_amount REAL DEFAULT 0,
                status TEXT DEFAULT 'ongoing',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                image_path TEXT,
                product_name TEXT NOT NULL,
                size TEXT,
                quantity INTEGER DEFAULT 1,
                price REAL DEFAULT 0,
                deadline DATE,
                notes TEXT,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            );
        ");
    }
} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}

function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    if (strpos($path, '/admin') !== false) {
        $path = str_replace('/admin', '', $path);
    }
    return $protocol . $domainName . rtrim($path, '/');
}
?>
