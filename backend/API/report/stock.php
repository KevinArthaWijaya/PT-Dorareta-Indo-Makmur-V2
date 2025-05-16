<?php
require '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405);
  echo json_encode(['error' => 'Metode tidak diizinkan']);
  exit;
}

$data = [];

// Ambil semua produk beserta kategori
$sql = "
  SELECT 
    p.sku,
    p.product_name,
    k.name AS category,
    p.stock_quantity,
    p.min_stock_warning
  FROM products p
  LEFT JOIN categories k ON p.category_id = k.id
  ORDER BY p.product_name ASC
";

$result = $conn->query($sql);

if ($result) {
  while ($row = $result->fetch_assoc()) {
    $data[] = [
      'sku' => $row['sku'],
      'product_name' => $row['product_name'],
      'category' => $row['category'] ?? 'Tidak ada kategori',
      'stock_quantity' => (int)$row['stock_quantity'],
      'min_stock_warning' => (int)$row['min_stock_warning'],
    ];
  }

  header('Content-Type: application/json');
  echo json_encode($data);
} else {
  http_response_code(500);
  echo json_encode(['error' => 'Gagal mengambil data produk', 'details' => $conn->error]);
}
