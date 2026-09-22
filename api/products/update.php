<?php
declare(strict_types=1);

// Forward to REST API with method PUT
$_SERVER['REQUEST_METHOD'] = 'PUT';
require_once __DIR__ . '/../products.php';
