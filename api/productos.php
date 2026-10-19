<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../config.php';

$products = getProducts();
echo json_encode($products, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
