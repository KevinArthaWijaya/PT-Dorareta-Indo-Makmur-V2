<?php
require '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$data = [];

$sql = "
  SELECT 
    p.product_name,
    c.name AS category,
    SUM(si.quantity) AS total_sold,
    SUM(si.subtotal) AS total_sales,
    MAX(s.created_at) AS date
  FROM sales_items si
  LEFT JOIN products p ON si.product_id = p.id
  LEFT JOIN categories c ON p.category_id = c.id
  LEFT JOIN sales s ON si.sale_id = s.id
  WHERE s.status = 'completed'
  GROUP BY si.product_id
  ORDER BY total_sold DESC
  LIMIT 100
";

$result = $conn->query($sql);

if ($result) {
  while ($row = $result->fetch_assoc()) {
    $data[] = [
      'product_name' => $row['product_name'],
      'category' => $row['category'] ?? 'No Category',
      'total_sold' => (int)$row['total_sold'],
      'total_sales' => (int)$row['total_sales'],
      'date' => $row['date'], // useful for filtering by day
    ];
  }

  header('Content-Type: application/json');
  echo json_encode($data);
} else {
  http_response_code(500);
  echo json_encode([
    'error' => 'Failed to fetch top products',
    'details' => $conn->error
  ]);
}
