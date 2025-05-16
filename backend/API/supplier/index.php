<?php
require '../../config.php';
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$override = $_POST['_method'] ?? null;
if ($override) $method = strtoupper($override);

$id = $_GET['id'] ?? null;

// GET ALL / BY ID
if ($method === 'GET') {
  if ($id) {
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $supplier = $res->fetch_assoc();

    if ($supplier) {
      echo json_encode($supplier);
    } else {
      http_response_code(404);
      echo json_encode(["success" => false, "message" => "Supplier tidak ditemukan"]);
    }
  } else {
    $result = $conn->query("SELECT * FROM suppliers ORDER BY id DESC");
    $suppliers = [];
    while ($row = $result->fetch_assoc()) {
      $suppliers[] = $row;
    }
    echo json_encode($suppliers);
  }
  exit;
}

// UPDATE (PUT)
if ($method === 'PUT') {
  parse_str(file_get_contents("php://input"), $_PUT);
  $id = $_GET['id'] ?? null;
  if (!$id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID supplier tidak ditemukan"]);
    exit;
  }

  $name = trim($_PUT['name'] ?? '');
  $phone = trim($_PUT['phone'] ?? '');
  $email = trim($_PUT['email'] ?? '');
  $address = trim($_PUT['address'] ?? '');

  if ($name === '') {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Nama supplier wajib diisi"]);
    exit;
  }

  $stmt = $conn->prepare("UPDATE suppliers SET name = ?, phone = ?, email = ?, address = ?, updated_at = NOW() WHERE id = ?");
  $stmt->bind_param("ssssi", $name, $phone, $email, $address, $id);
  $success = $stmt->execute();

  echo json_encode([
    "success" => $success,
    "message" => $success ? "Supplier berhasil diperbarui" : "Gagal memperbarui supplier"
  ]);
  exit;
}

// DELETE
if ($method === 'DELETE') {
  if (!$id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID supplier diperlukan"]);
    exit;
  }

  $stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
  $stmt->bind_param("i", $id);
  $success = $stmt->execute();

  echo json_encode([
    "success" => $success,
    "message" => $success ? "Supplier berhasil dihapus" : "Gagal menghapus supplier"
  ]);
  exit;
}

// Jika metode tidak diizinkan
http_response_code(405);
echo json_encode(["success" => false, "message" => "Metode tidak diizinkan"]);
exit;
