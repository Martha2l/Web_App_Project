<?php
declare(strict_types=1);

// Forward to REST API with method GET
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once __DIR__ . '/../products.php';
