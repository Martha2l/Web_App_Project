<?php
declare(strict_types=1);

// Forward to REST API with method DELETE
$_SERVER['REQUEST_METHOD'] = 'DELETE';
require_once __DIR__ . '/../products.php';
