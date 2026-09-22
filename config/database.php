<?php
declare(strict_types=1);

/**
 * Database Connection Helper
 * รองรับทั้ง Railway PaaS (Environment Variables) 
 * และ Localhost (MAMP / XAMPP) อัตโนมัติ
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // 1. ตรวจสอบค่าจาก Railway หรือ Environment Variables
    $host = getenv('MYSQLHOST') ?: getenv('DB_HOST');
    $port = getenv('MYSQLPORT') ?: getenv('DB_PORT');
    $name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME');
    $user = getenv('MYSQLUSER') ?: getenv('DB_USER');
    $pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS');

    // ตรวจสอบกรณี Railway ให้มาในรูป MYSQL_URL เช่น mysql://root:pass@host:port/railway
    $mysqlUrl = getenv('MYSQL_URL');
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

    // 2. ถ้าอยู่บน Localhost (ยังไม่มี Environment Variables)
    if (!$host) {
        $host = '127.0.0.1';
    }
    if (!$port) {
        $port = '3306';
    }
    if (!$name) {
        $name = 'db_northwind';
    }
    if (!$user) {
        $user = 'root';
    }

    // สำหรับรหัสผ่าน Localhost: ลองทั้ง 'root' (MAMP) และ '' (XAMPP)
    $passwordsToTry = ($pass !== false && $pass !== null && $pass !== '') 
        ? [$pass] 
        : ['root', ''];

    $lastException = null;
    foreach ($passwordsToTry as $tryPass) {
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $tryPass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            $lastException = $e;
        }
    }

    // ถ้ายังไม่ได้ ลอง localhost เผื่อ socket resolution
    if ($host === '127.0.0.1') {
        foreach ($passwordsToTry as $tryPass) {
            try {
                $dsn = "mysql:host=localhost;port={$port};dbname={$name};charset=utf8mb4";
                $pdo = new PDO($dsn, $user, $tryPass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                return $pdo;
            } catch (PDOException $e) {
                $lastException = $e;
            }
        }
    }

    throw new RuntimeException("Database Connection Error: " . ($lastException ? $lastException->getMessage() : "Unknown error"));
}

// Alias function เพื่อความยืดหยุ่นในการเรียกใช้งาน
function db(): PDO {
    return getDB();
}

// Class wrapper สำหรับโค้ดแบบ OOP
class Database {
    public function getConnection(): PDO {
        return getDB();
    }
}

