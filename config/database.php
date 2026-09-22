<?php
declare(strict_types=1);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // 1. ดึงค่า Environment Variables โดยเช็กทั้ง getenv(), $_ENV และ $_SERVER
    $host = getenv('MYSQLHOST') ?: ($_ENV['MYSQLHOST'] ?? ($_SERVER['MYSQLHOST'] ?? null));
    $port = getenv('MYSQLPORT') ?: ($_ENV['MYSQLPORT'] ?? ($_SERVER['MYSQLPORT'] ?? null));
    $name = getenv('MYSQLDATABASE') ?: ($_ENV['MYSQLDATABASE'] ?? ($_SERVER['MYSQLDATABASE'] ?? null));
    $user = getenv('MYSQLUSER') ?: ($_ENV['MYSQLUSER'] ?? ($_SERVER['MYSQLUSER'] ?? null));
    $pass = getenv('MYSQLPASSWORD') ?: ($_ENV['MYSQLPASSWORD'] ?? ($_SERVER['MYSQLPASSWORD'] ?? null));

    // เช็กกรณีส่งมาเป็น MYSQL_URL
    $mysqlUrl = getenv('MYSQL_URL') ?: ($_ENV['MYSQL_URL'] ?? ($_SERVER['MYSQL_URL'] ?? null));
    if ($mysqlUrl && (!$host || !$user)) {
        $parsed = parse_url($mysqlUrl);
        if ($parsed) {
            $host = $parsed['host'] ?? $host;
            $port = (string)($parsed['port'] ?? $port);
            $user = $parsed['user'] ?? $user;
            $pass = $parsed['pass'] ?? $pass;
            $name = ltrim($parsed['path'] ?? '', '/') ?: $name;
        }
    }

    // 2. ป้องกัน PDO Error [2002] Unix Socket
    // หาก $host เป็น 'localhost' ให้เปลี่ยนเป็น IP หรือ Internal Domain ของ Railway
    if ($host === 'localhost' || !$host) {
        $host = getenv('RAILWAY_ENVIRONMENT') ? 'mysql.railway.internal' : '127.0.0.1';
    }

    // ค่า Default สำหรับ Localhost
    $port = $port ?: '3306';
    $name = ($name === 'railway' || !$name) ? 'db_northwind' : $name;
    $user = $user ?: 'root';
    $pass = ($pass !== false && $pass !== null) ? $pass : '';

    // 3. เชื่อมต่อ PDO ผ่าน TCP/IP (กำหนด host และ port ชัดเจน)
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        throw new RuntimeException("Database Connection Error: " . $e->getMessage());
    }
}

function db(): PDO {
    return getDB();
}