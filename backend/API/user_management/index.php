<?php
require '../../config.php';
ob_start(); // Supaya semua output HTML tak sengaja terbuang
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['Admin', 'Manager'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_GET['id'] ?? null;
$type = $_GET['type'] ?? null;

function respond($status, $message, $data = []) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

// --- GET Roles ---
if ($method === 'GET' && $type === 'roles') {
    $res = $conn->query("SELECT id, role_name FROM roles ORDER BY role_name ASC");
    $roles = [];
    while ($r = $res->fetch_assoc()) {
        $roles[] = [
            'id' => (int)$r['id'],
            'role_name' => $r['role_name']
        ];
    }
    respond(true, "Roles fetched", $roles);
}

// --- GET Users List atau by ID ---
if ($method === 'GET') {
    // Jika ada parameter id, ambil hanya 1 user
    if ($userId) {
        $stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!$row) {
            respond(false, "User tidak ditemukan");
        }

        $user = [
            'id' => (int)$row['id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'phone_number' => $row['phone_number'],
            'username' => $row['username'],
            'bio' => $row['bio'],
            'role_id' => (int)$row['role_id'],
            'role_name' => $row['role_name'],
            'status' => $row['status'],
            'hire_date' => $row['hire_date'],
        ];

        respond(true, "User ditemukan", ['user' => $user]);
    }

    // Jika tanpa parameter id → lakukan paginasi & pencarian
    $q = $_GET['q'] ?? '';
    $sort_by = $_GET['sort_by'] ?? 'id';
    $order = strtoupper($_GET['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    $allowedSort = ['id', 'first_name', 'last_name', 'email', 'phone_number', 'username', 'status', 'hire_date'];
    if (!in_array($sort_by, $allowedSort)) $sort_by = 'id';

    $like = '%' . $q . '%';

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?");
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id 
        WHERE u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?
        ORDER BY u.$sort_by $order LIMIT ? OFFSET ?");
    $stmt->bind_param("sssii", $like, $like, $like, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => (int)$row['id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'phone_number' => $row['phone_number'],
            'username' => $row['username'],
            'bio' => $row['bio'],
            'role_id' => (int)$row['role_id'],
            'role_name' => $row['role_name'],
            'status' => $row['status'],
            'hire_date' => $row['hire_date'],
        ];
    }

    respond(true, "Fetched successfully", [
        'users' => $users,
        'pagination' => [
            'total' => $total,
            'limit' => $limit,
            'page' => $page,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
}


if ($method === 'DELETE' && $userId) {
    // Ambil data user yang akan dihapus
    $stmt = $conn->prepare("SELECT role_id, profile_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result || $result->num_rows === 0) {
        respond(false, "User tidak ditemukan");
    }

    $row = $result->fetch_assoc();

    // Cek apakah user adalah Admin
    $roleCheck = $conn->prepare("SELECT role_name FROM roles WHERE id = ?");
    $roleCheck->bind_param("i", $row['role_id']);
    $roleCheck->execute();
    $roleRes = $roleCheck->get_result()->fetch_assoc();

    if (strtolower($roleRes['role_name']) === 'admin') {
        respond(false, "User dengan role Admin tidak dapat dihapus.");
    }

    // Hapus gambar jika ada
    if (!empty($row['profile_image']) && file_exists("../../" . $row['profile_image'])) {
        unlink("../../" . $row['profile_image']);
    }

    // Hapus user dari DB
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        respond(true, "User deleted successfully");
    } else {
        respond(false, "Gagal menghapus user");
    }
}


// --- PUT (Update) ---
if ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$userId || empty($data)) respond(false, "Invalid request");

    $first = trim($data['first_name'] ?? '');
    $last = trim($data['last_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone_number'] ?? '');
    $username = trim($data['username'] ?? '');
    $bio = trim($data['bio'] ?? '');
    $role = $data['role_id'] ?? '';
    $status = $data['status'] ?? '';
    $hire = $data['hire_date'] ?? '';
    $password = trim($data['password'] ?? '');

    if (!$first || !$email || !$phone || !$username || !$role) {
        respond(false, "Required fields missing");
    }

    // Validasi panjang password jika tidak kosong
    if (!empty($password) && strlen($password) < 6) {
        respond(false, "Password minimal 6 karakter atau kosongkan jika tidak diganti.");
    }

    $query = "UPDATE users SET first_name=?, last_name=?, email=?, phone_number=?, username=?, bio=?, role_id=?, status=?, hire_date=?";
    $types = "ssssssiss";
    $params = [$first, $last, $email, $phone, $username, $bio, $role, $status, $hire];

    if (!empty($password)) {
        $query .= ", password=?";
        $types .= "s";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    $query .= " WHERE id=?";
    $types .= "i";
    $params[] = $userId;

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        respond(true, "User updated successfully");
    } else {
        respond(false, "Failed to update user");
    }
}


// --- POST (Create) ---
if ($method === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $role = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;
    if ($_SESSION['role'] === 'Manager') {
        $cekAdminRole = $conn->prepare("SELECT role_name FROM roles WHERE id = ?");
        $cekAdminRole->bind_param("i", $role);
        $cekAdminRole->execute();
        $roleResult = $cekAdminRole->get_result();
        $roleData = $roleResult->fetch_assoc();
    
        if ($roleData && strtolower($roleData['role_name']) === 'admin') {
            respond(false, "Manager tidak diizinkan membuat user dengan role Admin.");
        }
    }
    $status = 'active';
    $hire = $_POST['hire_date'] ?? '';
    $userImage = $_FILES['user_image'] ?? null;

    if (!$first || !$email || !$phone || !$username || strlen($password) < 6 || !$role || !$hire) {
        respond(false, "Field wajib tidak boleh kosong dan password minimal 6 karakter.");
    }

    // Cek apakah email atau username sudah ada
    $cek = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $cek->bind_param("ss", $email, $username);
    $cek->execute();
    $res = $cek->get_result();
    if ($res->num_rows > 0) {
        respond(false, "Email atau Username sudah digunakan.");
    }

    $imgPath = '';
    if ($userImage && $userImage['error'] === 0) {
        $ext = strtolower(pathinfo($userImage['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowed)) respond(false, "Image harus JPG/PNG.");

        $dir = '../../uploads/user_image/';
        if (!file_exists($dir)) mkdir($dir, 0777, true);
        $filename = uniqid("user_", true) . '.' . $ext;
        $imgPath = 'uploads/user_image/' . $filename;
        if (!move_uploaded_file($userImage['tmp_name'], "../../" . $imgPath)) {
            respond(false, "Gagal menyimpan gambar.");
        }
    } else {
        $defaultPath = '../../../assets/image/default-user-lightmode.png';
        if (file_exists($defaultPath)) {
        } else {
            respond(false, "File default user image tidak ditemukan.");
        }
    }

    $hashPass = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone_number, username, password, bio, role_id, status, hire_date, profile_image)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        respond(false, "Prepare statement gagal: " . $conn->error);
    }

    $stmt->bind_param("sssssssssss", $first, $last, $email, $phone, $username, $hashPass, $bio, $role, $status, $hire, $imgPath);
    if ($stmt->execute()) {
        respond(true, "User berhasil dibuat");
    } else {
        respond(false, "Gagal membuat user: " . $stmt->error);
    }
}

respond(false, "Method Not Allowed");
