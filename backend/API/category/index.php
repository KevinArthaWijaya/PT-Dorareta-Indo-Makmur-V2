<?php
header("Content-Type: application/json");
require_once '../../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// GET - Ambil semua kategori
if ($method === 'GET') {
    $result = $conn->query("SELECT * FROM categories ORDER BY id DESC");
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'sku_prefix' => $row['sku_prefix']
        ];
    }

    echo json_encode(['success' => true, 'data' => $data]);
    exit();
}

// POST - Tambah kategori
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    $prefix = strtoupper(trim($input['sku_prefix'] ?? ''));

    if ($name === '' || $prefix === '') {
        echo json_encode(['success' => false, 'message' => 'Nama dan prefix wajib diisi']);
        exit();
    }

    if (strlen($prefix) > 10) {
        echo json_encode(['success' => false, 'message' => 'Prefix maksimal 10 karakter']);
        exit();
    }

    $check = $conn->prepare("SELECT id FROM categories WHERE sku_prefix = ?");
    $check->bind_param("s", $prefix);
    $check->execute();
    $checkResult = $check->get_result();
    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Prefix sudah digunakan']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO categories (name, sku_prefix) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $prefix);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Kategori berhasil ditambahkan']);
    exit();
}

// PUT - Edit kategori
if ($method === 'PUT' && $id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    $prefix = strtoupper(trim($input['sku_prefix'] ?? ''));

    if ($name === '' || $prefix === '') {
        echo json_encode(['success' => false, 'message' => 'Nama dan prefix wajib diisi']);
        exit();
    }

    if (strlen($prefix) > 10) {
        echo json_encode(['success' => false, 'message' => 'Prefix maksimal 10 karakter']);
        exit();
    }

    $check = $conn->prepare("SELECT id FROM categories WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $checkResult = $check->get_result();
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Kategori tidak ditemukan']);
        exit();
    }

    $checkDup = $conn->prepare("SELECT id FROM categories WHERE sku_prefix = ? AND id != ?");
    $checkDup->bind_param("si", $prefix, $id);
    $checkDup->execute();
    $dupResult = $checkDup->get_result();
    if ($dupResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Prefix sudah digunakan oleh kategori lain']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE categories SET name = ?, sku_prefix = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $prefix, $id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Kategori berhasil diperbarui']);
    exit();
}

// DELETE - Hapus kategori
if ($method === 'DELETE' && $id) {
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Kategori berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus kategori']);
    }
    exit();
}

// Jika metode tidak dikenali
echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan']);
exit();
