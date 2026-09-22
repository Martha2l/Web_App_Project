<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDB();

    // ตรวจสอบชื่อตารางว่าใช้ tb_categories หรือ Categories
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $tableLower = array_map('strtolower', $tables);

    if (in_array('tb_categories', $tableLower, true)) {
        $stmt = $pdo->query("SELECT i_CategoryID AS CategoryID, c_CategoryName AS CategoryName, c_Description AS Description FROM tb_categories ORDER BY i_CategoryID ASC");
    } elseif (in_array('categories', $tableLower, true)) {
        $stmt = $pdo->query("SELECT CategoryID, CategoryName, Description FROM Categories ORDER BY CategoryID ASC");
    } else {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $data = $stmt->fetchAll();
    echo json_encode([
        'success' => true,
        'message' => 'ดึงข้อมูลหมวดหมู่สำเร็จ',
        'data'    => $data
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
