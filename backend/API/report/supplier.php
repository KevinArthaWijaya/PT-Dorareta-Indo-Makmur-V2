<?php
require '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$data = [];

// Query: Ambil data supplier + total pembelian dengan status 'received'
$sql = "
  SELECT 
    sup.id,
    sup.name,
    sup.phone,
    sup.email,
    sup.address,
    COUNT(p.id) AS total_purchases,
    IFNULL(SUM(p.grand_total), 0) AS total_amount
  FROM suppliers sup
  LEFT JOIN purchases p ON p.supplier_id = sup.id AND p.status = 'received'
  GROUP BY sup.id, sup.name, sup.phone, sup.email, sup.address
  ORDER BY sup.name ASC
";

$result = $conn->query($sql);

if ($result) {
  while ($row = $result->fetch_assoc()) {
    $data[] = [
      'id' => $row['id'],
      'name' => $row['name'],
      'phone' => isset($row['phone']) && $row['phone'] !== null ? $row['phone'] : '-',
      'email' => isset($row['email']) && $row['email'] !== null ? $row['email'] : '-',
      'address' => isset($row['address']) && $row['address'] !== null ? $row['address'] : '-',
      'total_purchases' => (int) $row['total_purchases'],
      'total_amount' => (int) $row['total_amount']
    ];
  }

  header('Content-Type: application/json');
  echo json_encode($data);
} else {
  http_response_code(500);
  echo json_encode([
    'error' => 'Gagal mengambil data supplier',
    'details' => $conn->error
  ]);
}
