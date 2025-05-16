<?php
require '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Metode tidak diizinkan']);
    exit;
}

$sql = "SELECT 
            p.date,
            p.invoice_no,
            s.name AS supplier,
            p.status,
            p.grand_total,
            p.paid,
            p.due,
            p.payment_status
        FROM purchases p
        LEFT JOIN suppliers s ON p.supplier_id = s.id
        ORDER BY p.date DESC";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Query gagal dijalankan',
        'sql_error' => $conn->error
    ]);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'date' => $row['date'],
        'invoice_no' => $row['invoice_no'],
        'supplier' => $row['supplier'],
        'status' => $row['status'],
        'grand_total' => (int) $row['grand_total'],
        'paid' => (int) $row['paid'],
        'due' => (int) $row['due'],
        'payment_status' => $row['payment_status'],
    ];
}

header('Content-Type: application/json');
echo json_encode($data);
