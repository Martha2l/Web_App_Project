<?php
declare(strict_types=1);

// Forward to REST API with method POST
$_SERVER['REQUEST_METHOD'] = 'POST';
require_once __DIR__ . '/../products.php';
