<?php
declare(strict_types=1);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // 1. ดึงค่าจาก Railway Environment Variables
    $host = getenv('MYSQLHOST') ?: ($_ENV['MYSQLHOST'] ?? ($_SERVER['MYSQLHOST'] ?? null));
    $port = getenv('MYSQLPORT') ?: ($_ENV['MYSQLPORT'] ?? ($_SERVER['MYSQLPORT'] ?? null));
    $user = getenv('MYSQLUSER') ?: ($_ENV['MYSQLUSER'] ?? ($_SERVER['MYSQLUSER'] ?? null));
    $pass = getenv('MYSQLPASSWORD') ?: ($_ENV['MYSQLPASSWORD'] ?? ($_SERVER['MYSQLPASSWORD'] ?? null));

    // 2. ป้องกัน PDO Error [2002] Unix Socket
    if ($host === 'localhost' || !$host) {
        $host = getenv('RAILWAY_ENVIRONMENT') ? 'mysql.railway.internal' : '127.0.0.1';
    }

    // 3. กำหนดค่าเริ่มต้น (บังคับชี้ไปที่ DB ชื่อ railway)
    $port = $port ?: '3306';
    $name = 'railway'; // บังคับใช้ชื่อฐานข้อมูลเริ่มต้นของ Railway
    $user = $user ?: 'root';
    $pass = ($pass !== false && $pass !== null) ? $pass : '';

    // 4. เชื่อมต่อ PDO
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