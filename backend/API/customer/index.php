<?php
require '../../config.php'; // pastikan path ke config sesuai
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$overrideMethod = $_POST['_method'] ?? null;
if ($overrideMethod) {
    $method = strtoupper($overrideMethod);
}

$id = $_GET['id'] ?? null;

// ========== GET (all / by id) ==========
if ($method === 'GET') {
    if ($id) {
        // Get customer by ID
        $stmt = $conn->prepare("SELECT id, name, phone, email, address FROM customers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();

        if ($customer) {
            echo json_encode($customer);
        } else {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Customer tidak ditemukan"]);
        }
    } else {
        // Get all customers
        $result = $conn->query("SELECT id, name, phone, email, address FROM customers ORDER BY id DESC");
        $customers = [];

        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }

        echo json_encode($customers);
    }
    exit;
}

// ========== PUT (update) ==========
if ($method === 'PUT') {
    parse_str(file_get_contents("php://input"), $_PUT); // fallback jika tidak pakai form-urlencoded

    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ID customer diperlukan"]);
        exit;
    }

    $name = trim($_PUT['name'] ?? '');
    $phone = trim($_PUT['phone'] ?? '');
    $email = trim($_PUT['email'] ?? '');
    $address = trim($_PUT['address'] ?? '');

    if ($name === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Nama tidak boleh kosong"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE customers SET name = ?, phone = ?, email = ?, address = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssssi", $name, $phone, $email, $address, $id);
    $success = $stmt->execute();

    if ($success) {
        echo json_encode(["success" => true, "message" => "Customer berhasil diperbarui"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Gagal memperbarui customer"]);
    }
    exit;
}

// ========== DELETE ==========
if ($method === 'DELETE') {
    if (!$id) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ID customer diperlukan untuk menghapus"]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();

    if ($success) {
        echo json_encode(["success" => true, "message" => "Customer berhasil dihapus"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Gagal menghapus customer"]);
    }
    exit;
}

// ========== Unsupported Method ==========
http_response_code(405);
echo json_encode(["success" => false, "message" => "Metode tidak diizinkan"]);
exit;
