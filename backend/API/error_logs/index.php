<?php
require '../../config.php';

header('Content-Type: application/json');
session_start();

// Ambil input JSON dari frontend
$input = json_decode(file_get_contents("php://input"), true);

$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if (!$username || !$password) {
    echo json_encode(['success' => false, 'message' => 'Username dan password wajib diisi.']);
    exit;
}

// Ambil user dari DB
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    logError(null, $username, "Username tidak ditemukan");
    echo json_encode(['success' => false, 'message' => 'Username tidak ditemukan.']);
    exit;
}

$user = $res->fetch_assoc();

// Cek password
if (!password_verify($password, $user['password'])) {
    logError($user['id'], $username, "Password salah");
    echo json_encode(['success' => false, 'message' => 'Password salah.']);
    exit;
}

// ✅ Login berhasil
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role_id']; // opsional, bisa diganti dengan JOIN jika perlu role_name

echo json_encode(['success' => true, 'message' => 'Login berhasil.']);
exit;

// 📝 Fungsi pencatatan error
function logError($userId, $username, $message) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO error_logs (user_id, username, error_message, error_time) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $userId, $username, $message);
    $stmt->execute();
}
