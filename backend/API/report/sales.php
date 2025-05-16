<?php
require '../../config.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Metode tidak diizinkan"]);
    exit;
}

$sql = "SELECT 
          s.invoice_no AS invoice,
          s.date,
          c.name AS customer,
          s.status,
          s.grand_total AS total,
          s.paid,
          s.due,
          s.payment_status
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        ORDER BY s.date DESC";

$result = $conn->query($sql);
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
exit;
