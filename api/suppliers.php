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

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $tableLower = array_map('strtolower', $tables);

    if (in_array('tb_suppliers', $tableLower, true)) {
        $stmt = $pdo->query("SELECT i_SupplierID AS SupplierID, c_SupplierName AS SupplierName, c_Country AS Country FROM tb_suppliers ORDER BY i_SupplierID ASC");
    } elseif (in_array('suppliers', $tableLower, true)) {
        $stmt = $pdo->query("SELECT SupplierID, CompanyName AS SupplierName, Country FROM Suppliers ORDER BY SupplierID ASC");
    } else {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $data = $stmt->fetchAll();
    echo json_encode([
        'success' => true,
        'message' => 'ดึงข้อมูลผู้จัดจำหน่ายสำเร็จ',
        'data'    => $data
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
