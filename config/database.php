<?php
declare(strict_types=1);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // 1. อ่านค่า Environment Variables ทั้งหมดจากระบบ (รวมถึง $_ENV และ $_SERVER)
    $host = getenv('MYSQLHOST') ?: ($_ENV['MYSQLHOST'] ?? ($_SERVER['MYSQLHOST'] ?? null));
    $port = getenv('MYSQLPORT') ?: ($_ENV['MYSQLPORT'] ?? ($_SERVER['MYSQLPORT'] ?? null));
    $name = getenv('MYSQLDATABASE') ?: ($_ENV['MYSQLDATABASE'] ?? ($_SERVER['MYSQLDATABASE'] ?? null));
    $user = getenv('MYSQLUSER') ?: ($_ENV['MYSQLUSER'] ?? ($_SERVER['MYSQLUSER'] ?? null));
    $pass = getenv('MYSQLPASSWORD') ?: ($_ENV['MYSQLPASSWORD'] ?? ($_SERVER['MYSQLPASSWORD'] ?? null));

    // ตรวจสอบกรณีที่มีการตั้งค่าผ่าน MYSQL_URL
    $mysqlUrl = getenv('MYSQL_URL') ?: ($_ENV['MYSQL_URL'] ?? ($_SERVER['MYSQL_URL'] ?? null));
    if ($mysqlUrl && (!$host || !$pass)) {
        $parsed = parse_url($mysqlUrl);
        if ($parsed) {
            $host = $parsed['host'] ?? $host;
            $port = (string)($parsed['port'] ?? $port);
            $user = $parsed['user'] ?? $user;
            $pass = $parsed['pass'] ?? $pass;
            $name = ltrim($parsed['path'] ?? '', '/') ?: $name;
        }
    }

    // 2. ตรวจสอบสภาพแวดล้อมว่ารันอยู่บน Railway หรือ Localhost
    $isRailway = getenv('RAILWAY_ENVIRONMENT') || getenv('MYSQLHOST') || getenv('MYSQL_URL') || !empty($_ENV['MYSQLHOST']);

    if ($isRailway) {
        // --- การตั้งค่าสำหรับใช้งานบน Railway ---
        $host = $host ?: 'mysql.railway.internal';
        $port = $port ?: '3306';
        $user = $user ?: 'root';
        $name = $name ?: 'railway'; // ชื่อ DB เริ่มต้นบน Railway
        $pass = ($pass !== false && $pass !== null) ? $pass : '';
    } else {
        // --- การตั้งค่าสำหรับ Localhost (MAMP / XAMPP) ---
        $host = '127.0.0.1';
        $port = '3306';
        $user = 'root';
        $name = 'db_northwind'; // กำหนดชื่อฐานข้อมูลตามที่คุณต้องการ
        
        // รหัสผ่านเริ่มต้นสำหรับ Localhost (MAMP ใช้ 'root', XAMPP มักเป็นค่าว่าง)
        $pass = (strpos(__DIR__, 'MAMP') !== false) ? 'root' : 'root'; 
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // กรณี Localhost: หากรหัสผ่าน 'root' ไม่ผ่าน จะทดลองเชื่อมต่อแบบไม่ใส่รหัสผ่าน (รองรับ XAMPP)
        if (!$isRailway) {
            try {
                $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, '', [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                return $pdo;
            } catch (PDOException $ex) {
                throw new RuntimeException("Database Connection Error: " . $e->getMessage());
            }
        }
        throw new RuntimeException("Database Connection Error: " . $e->getMessage());
    }
}

function db(): PDO {
    return getDB();
}