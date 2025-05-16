<?php
require '../../config.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
} // 🛠️ PENTING: mulai session
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exitJson(false, 'Invalid request method.');
}

$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

if (empty($username) || empty($password)) {
    exitJson(false, 'Username dan Password wajib diisi.');
}

$stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ?");
if (!$stmt) {
    exitJson(false, 'Database error saat prepare statement.');
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if ($user['status'] !== 'active') {
        exitJson(false, 'Akun Anda tidak aktif.');
    }

    if (password_verify($password, $user['password'])) {
        // ✅ SET SESSION
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['profile_image'] = !empty($user['profile_image']) ? $user['profile_image'] : '/perubahan/assets/image/default-user-lightmode.png';
        $_SESSION['permissions'] = getPermissionsByRole($user['role_name']); // 🎯 Set permission di session
        
        // ✅ RESPON JSON
        exitJson(true, 'Login berhasil!', [
            'user_id' => (int) $user['id'],
            'username' => $user['username'],
            'full_name' => $user['first_name'] . ' ' . $user['last_name'],
            'role' => $user['role_name'],
            'profile_image' => $_SESSION['profile_image'],
            'redirect' => determineRedirect()
        ]);
    } else {
        logLoginError($username, 'Password salah');
        exitJson(false, 'Password salah!');
    }
} else {
    logLoginError($username, 'Username tidak ditemukan');
    exitJson(false, 'Username tidak ditemukan!');
}

$stmt->close();
$conn->close();

// 🔵 FUNCTION-FUNCTION TAMBAHAN

function determineRedirect() {
    return '/perubahan/frontend/dashboard/dashboard.php';
}

function getPermissionsByRole($roleName) {
    switch ($roleName) {
        case 'Admin':
            return [
                'products' => true,
                'purchase' => true,
                'sales' => true,
                'report' => true,
                'user_management' => true,
            ];
        case 'Manager':
            return [
                'products' => true,
                'purchase' => true,
                'sales' => true,
                'report' => true,
            ];
        case 'Accounting':
            return [
                'sales' => true,
                'report' => true,
            ];
        case 'Logistic':
            return [
                'products' => true,
                'purchase' => true,
            ];
        default:
            return []; // 🔥 Default kosong kalau user biasa
    }
}

function exitJson($status, $message, $data = []) {
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function logLoginError($username, $message) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO error_logs (user_id, username, error_message, error_time) VALUES (?, ?, ?, NOW())");
    $user_id = null;

    // Coba ambil ID user jika username ditemukan
    $getUser = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $getUser->bind_param("s", $username);
    $getUser->execute();
    $res = $getUser->get_result();
    if ($res->num_rows > 0) {
        $user_id = $res->fetch_assoc()['id'];
    }

    $stmt->bind_param("iss", $user_id, $username, $message);
    $stmt->execute();
}
?>
