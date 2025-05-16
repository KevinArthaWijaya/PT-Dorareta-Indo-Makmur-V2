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
    c.id,
    c.name,
    COUNT(s.id) AS total_transactions,
    IFNULL(SUM(s.grand_total), 0) AS total_spent,
    MAX(s.date) AS last_transaction
  FROM customers c
  LEFT JOIN sales s ON s.customer_id = c.id
  GROUP BY c.id, c.name
  ORDER BY c.name ASC
";

$result = $conn->query($sql);

if ($result) {
  while ($row = $result->fetch_assoc()) {
    $data[] = [
      'id' => $row['id'],
      'name' => $row['name'],
      'total_transactions' => (int)$row['total_transactions'],
      'total_spent' => (int)$row['total_spent'],
      'last_transaction' => $row['last_transaction']
    ];
  }

  header('Content-Type: application/json');
  echo json_encode($data);
} else {
  http_response_code(500);
  echo json_encode([
    'error' => 'Gagal mengambil data customer',
    'details' => $conn->error
  ]);
}
