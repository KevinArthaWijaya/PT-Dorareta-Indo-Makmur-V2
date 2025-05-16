<?php
require '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405);
  echo json_encode(['error' => 'Metode tidak diizinkan']);
  exit;
}

$data = [];

// ===== SALES RETURN =====
$sqlSales = "
  SELECT 
    sr.id,
    sr.created_at AS date,
    'sales' AS type,
    s.invoice_no AS transaction_no,
    c.name AS name,
    SUM(sri.subtotal) AS amount
  FROM sales_returns sr
  LEFT JOIN sales s ON sr.sale_id = s.id
  LEFT JOIN customers c ON s.customer_id = c.id
  LEFT JOIN sales_return_items sri ON sri.return_id = sr.id
  GROUP BY sr.id, sr.created_at, s.invoice_no, c.name
";

$resSales = $conn->query($sqlSales);
if ($resSales) {
  while ($row = $resSales->fetch_assoc()) {
    $data[] = $row;
  }
}

// ===== PURCHASE RETURN =====
$sqlPurchase = "
  SELECT 
    pr.id,
    pr.created_at AS date,
    'purchase' AS type,
    p.invoice_no AS transaction_no,
    sup.name AS name,
    SUM(pri.subtotal) AS amount
  FROM purchase_returns pr
  LEFT JOIN purchases p ON pr.purchase_id = p.id
  LEFT JOIN suppliers sup ON p.supplier_id = sup.id
  LEFT JOIN purchase_return_items pri ON pri.return_id = pr.id
  GROUP BY pr.id, pr.created_at, p.invoice_no, sup.name
";

$resPurchase = $conn->query($sqlPurchase);
if ($resPurchase) {
  while ($row = $resPurchase->fetch_assoc()) {
    $data[] = $row;
  }
}

// Urutkan berdasarkan tanggal DESC
usort($data, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

header('Content-Type: application/json');
echo json_encode($data);
