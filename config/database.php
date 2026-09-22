<?php
declare(strict_types=1);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // 1. ดึงค่า Environment Variables
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
    if ($host === 'localhost' || !$host) {
        $host = getenv('RAILWAY_ENVIRONMENT') ? 'mysql.railway.internal' : '127.0.0.1';
    }

    // 3. กำหนดค่าสำหรับ Database (บังคับชี้ไปที่ db_northwind)
    $port = $port ?: '3306';
    
    // หากไม่พบชื่อฐานข้อมูล หรือเป็นชื่อเริ่มต้นของ Railway ให้บังคับใช้ db_northwind
    if (!$name || $name === 'railway') {
        $name = 'db_northwind';
    }

    $user = $user ?: 'root';
    $pass = ($pass !== false && $pass !== null) ? $pass : '';

    // 4. เชื่อมต่อ PDO ผ่าน TCP/IP
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