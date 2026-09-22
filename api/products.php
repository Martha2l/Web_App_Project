<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/database.php';

function getRequestBody(): array {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }
    }
    return is_array($_POST) ? $_POST : [];
}

function ok(mixed $data, string $message = 'ดำเนินการสำเร็จ', int $status = 200): never {
    http_response_code($status);
    echo json_encode([
        'success' => true,
        'status'  => $status,
        'message' => $message,
        'data'    => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(string $message, int $status = 400): never {
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'status'  => $status,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function strLenSafe(string $s): int {
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

function strSubSafe(string $s, int $start, int $length): string {
    return function_exists('mb_substr') ? mb_substr($s, $start, $length, 'UTF-8') : substr($s, $start, $length);
}

function validateInput(array $d): array {
    $name = trim((string)($d['ProductName'] ?? $d['c_ProductName'] ?? ''));
    if ($name === '') {
        fail('กรุณากรอกชื่อสินค้า (Product Name)');
    }
    if (strLenSafe($name) > 30) {
        fail('ชื่อสินค้าต้องมีความยาวไม่เกิน 30 ตัวอักษร');
    }

    $rawPrice = $d['UnitPrice'] ?? $d['i_Price'] ?? null;
    if ($rawPrice === null || $rawPrice === '') {
        fail('กรุณากรอกราคาสินค้า');
    }
    if (!is_numeric($rawPrice) || (float)$rawPrice < 0) {
        fail('ราคาสินค้าต้องเป็นตัวเลขที่ไม่ติดลบ (>= 0)');
    }
    $price = (float)$rawPrice;

    $unit = trim((string)($d['QuantityPerUnit'] ?? $d['c_Unit'] ?? ''));
    if (strLenSafe($unit) > 30) {
        $unit = strSubSafe($unit, 0, 30);
    }

    $catId = isset($d['CategoryID']) && $d['CategoryID'] !== '' ? (int)$d['CategoryID'] : null;
    $suppId = isset($d['SupplierID']) && $d['SupplierID'] !== '' ? (int)$d['SupplierID'] : null;

    return [
        'ProductName'     => $name,
        'QuantityPerUnit' => $unit,
        'UnitPrice'       => $price,
        'CategoryID'      => $catId ?: 1,
        'SupplierID'      => $suppId ?: 1
    ];
}

try {
    $pdo = getDB();

    // ตรวจสอบชื่อตารางว่าในฐานข้อมูลใช้ tb_products (ตาม dbNorthwind.sql) หรือ Products
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $tableLower = array_map('strtolower', $tables);
    $isTbProducts = in_array('tb_products', $tableLower, true);

    $method = $_SERVER['REQUEST_METHOD'];
    $input = getRequestBody();

    // รองรับ Method Override เผื่อ Client ส่งผ่าน _method
    if ($method === 'POST') {
        $override = strtoupper((string)($_GET['_method'] ?? $_POST['_method'] ?? $input['_method'] ?? ''));
        if (in_array($override, ['PUT', 'DELETE'], true)) {
            $method = $override;
        }
    }

    // 1. GET: ดึงรายการสินค้าทั้งหมด, ค้นหา, หรือดึงรายชิ้น
    if ($method === 'GET') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $q = trim((string)($_GET['q'] ?? $_GET['search'] ?? ''));

        if ($isTbProducts) {
            $baseSql = "SELECT 
                            p.i_ProductID AS ProductID,
                            p.c_ProductName AS ProductName,
                            p.c_Unit AS QuantityPerUnit,
                            p.i_Price AS UnitPrice,
                            p.i_SupplierID AS SupplierID,
                            COALESCE(s.c_SupplierName, '-') AS SupplierName,
                            p.i_CategoryID AS CategoryID,
                            COALESCE(c.c_CategoryName, '-') AS CategoryName
                        FROM tb_products p
                        LEFT JOIN tb_categories c ON p.i_CategoryID = c.i_CategoryID
                        LEFT JOIN tb_suppliers s ON p.i_SupplierID = s.i_SupplierID";

            if ($id > 0) {
                $st = $pdo->prepare("{$baseSql} WHERE p.i_ProductID = ?");
                $st->execute([$id]);
                $row = $st->fetch();
                if (!$row) {
                    fail("ไม่พบสินค้ารหัส #{$id}", 404);
                }
                $row['UnitPrice'] = (float)$row['UnitPrice'];
                ok($row, 'ดึงข้อมูลสำเร็จ');
            }

            if ($q !== '') {
                $st = $pdo->prepare("{$baseSql} WHERE p.c_ProductName LIKE ? OR CAST(p.i_ProductID AS CHAR) LIKE ? ORDER BY p.i_ProductID DESC");
                $term = "%{$q}%";
                $st->execute([$term, $term]);
            } else {
                $st = $pdo->query("{$baseSql} ORDER BY p.i_ProductID DESC");
            }

            $list = $st->fetchAll();
            foreach ($list as &$item) {
                $item['UnitPrice'] = (float)$item['UnitPrice'];
            }
            ok($list, 'ดึงข้อมูลรายการสินค้าสำเร็จ');
        } else {
            // Fallback กรณีใช้ตาราง Products แบบมาตรฐาน
            $baseSql = "SELECT 
                            ProductID, ProductName, QuantityPerUnit, 
                            UnitPrice, SupplierID, CategoryID
                        FROM Products";
            if ($id > 0) {
                $st = $pdo->prepare("{$baseSql} WHERE ProductID = ?");
                $st->execute([$id]);
                $row = $st->fetch();
                if (!$row) fail("ไม่พบสินค้ารหัส #{$id}", 404);
                $row['UnitPrice'] = (float)$row['UnitPrice'];
                ok($row, 'ดึงข้อมูลสำเร็จ');
            }

            if ($q !== '') {
                $st = $pdo->prepare("{$baseSql} WHERE ProductName LIKE ? OR CAST(ProductID AS CHAR) LIKE ? ORDER BY ProductID DESC");
                $term = "%{$q}%";
                $st->execute([$term, $term]);
            } else {
                $st = $pdo->query("{$baseSql} ORDER BY ProductID DESC");
            }
            $list = $st->fetchAll();
            foreach ($list as &$item) {
                $item['UnitPrice'] = (float)$item['UnitPrice'];
            }
            ok($list, 'ดึงข้อมูลรายการสินค้าสำเร็จ');
        }
    }

    // 2. POST: เพิ่มสินค้าใหม่ (Create)
    if ($method === 'POST') {
        $data = validateInput($input);

        if ($isTbProducts) {
            $sql = "INSERT INTO tb_products (c_ProductName, i_SupplierID, i_CategoryID, c_Unit, i_Price)
                    VALUES (?, ?, ?, ?, ?)";
            $st = $pdo->prepare($sql);
            $st->execute([
                $data['ProductName'],
                $data['SupplierID'],
                $data['CategoryID'],
                $data['QuantityPerUnit'],
                $data['UnitPrice']
            ]);
            $newId = (int)$pdo->lastInsertId();
            ok(['ProductID' => $newId], "เพิ่มสินค้า '{$data['ProductName']}' เรียบร้อยแล้ว", 201);
        } else {
            $sql = "INSERT INTO Products (ProductName, SupplierID, CategoryID, QuantityPerUnit, UnitPrice)
                    VALUES (?, ?, ?, ?, ?)";
            $st = $pdo->prepare($sql);
            $st->execute([
                $data['ProductName'],
                $data['SupplierID'],
                $data['CategoryID'],
                $data['QuantityPerUnit'],
                $data['UnitPrice']
            ]);
            $newId = (int)$pdo->lastInsertId();
            ok(['ProductID' => $newId], "เพิ่มสินค้า '{$data['ProductName']}' เรียบร้อยแล้ว", 201);
        }
    }

    // 3. PUT: แก้ไขสินค้า (Update)
    if ($method === 'PUT') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($input['ProductID'] ?? 0);
        if ($id <= 0) {
            fail('กรุณาระบุรหัสสินค้า (ProductID) ที่ต้องการแก้ไข');
        }

        $data = validateInput($input);

        if ($isTbProducts) {
            $sql = "UPDATE tb_products 
                    SET c_ProductName = ?, i_SupplierID = ?, i_CategoryID = ?, c_Unit = ?, i_Price = ?
                    WHERE i_ProductID = ?";
            $st = $pdo->prepare($sql);
            $st->execute([
                $data['ProductName'],
                $data['SupplierID'],
                $data['CategoryID'],
                $data['QuantityPerUnit'],
                $data['UnitPrice'],
                $id
            ]);
            ok(['ProductID' => $id], "แก้ไขข้อมูลสินค้า #{$id} เรียบร้อยแล้ว");
        } else {
            $sql = "UPDATE Products 
                    SET ProductName = ?, SupplierID = ?, CategoryID = ?, QuantityPerUnit = ?, UnitPrice = ?
                    WHERE ProductID = ?";
            $st = $pdo->prepare($sql);
            $st->execute([
                $data['ProductName'],
                $data['SupplierID'],
                $data['CategoryID'],
                $data['QuantityPerUnit'],
                $data['UnitPrice'],
                $id
            ]);
            ok(['ProductID' => $id], "แก้ไขข้อมูลสินค้า #{$id} เรียบร้อยแล้ว");
        }
    }

    // 4. DELETE: ลบสินค้า (Delete)
    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($input['ProductID'] ?? 0);
        if ($id <= 0) {
            fail('กรุณาระบุรหัสสินค้า (ProductID) ที่ต้องการลบ');
        }

        if ($isTbProducts) {
            $st = $pdo->prepare("DELETE FROM tb_products WHERE i_ProductID = ?");
            $st->execute([$id]);
            if ($st->rowCount() === 0) {
                fail("ไม่พบสินค้า #{$id} หรือถูกลบไปแล้ว", 404);
            }
            ok(['ProductID' => $id], "ลบสินค้า #{$id} เรียบร้อยแล้ว");
        } else {
            $st = $pdo->prepare("DELETE FROM Products WHERE ProductID = ?");
            $st->execute([$id]);
            if ($st->rowCount() === 0) {
                fail("ไม่พบสินค้า #{$id} หรือถูกลบไปแล้ว", 404);
            }
            ok(['ProductID' => $id], "ลบสินค้า #{$id} เรียบร้อยแล้ว");
        }
    }

    fail('Method Not Allowed: รองรับเฉพาะ GET, POST, PUT, DELETE', 405);

} catch (PDOException $e) {
    fail('Database Error: ' . $e->getMessage(), 500);
} catch (Throwable $e) {
    fail('Server Error: ' . $e->getMessage(), 500);
}

