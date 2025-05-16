<?php
session_start();
require '../../config.php'; // Koneksi database

header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// 🔥 Function untuk refresh session setelah update
function refreshSessionData($conn, $userId) {
    $query = $conn->prepare("SELECT first_name, last_name, email, username, phone_number, bio, profile_image FROM users WHERE id = ?");
    $query->bind_param('i', $userId);
    $query->execute();
    $user = $query->get_result()->fetch_assoc();

    if ($user) {
        $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['phone_number'] = $user['phone_number'];
        $_SESSION['bio'] = $user['bio'];
        $_SESSION['profile_image'] = !empty($user['profile_image']) ? $user['profile_image'] : 'assets/image/default-user-lightmode.png';
    }
}

if ($method === 'GET') {
    // Ambil data profile
    $query = $conn->prepare("SELECT first_name, last_name, email, phone_number, username, bio, profile_image FROM users WHERE id = ?");
    $query->bind_param('i', $userId);
    $query->execute();
    $result = $query->get_result()->fetch_assoc();

    // Normalisasi path profile_image
    if (!empty($result['profile_image']) && file_exists("../../" . $result['profile_image'])) {
        $result['profile_image'] = $result['profile_image'];
    } else {
        $result['profile_image'] = "assets/image/default-user-lightmode.png";
    }

    echo json_encode([
        'status' => true,
        'data' => $result
    ]);
    exit;

} elseif ($method === 'POST') {
    // Update profile
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phoneNumber = $_POST['phone_number'] ?? '';
    $username = $_POST['username'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $password = $_POST['password'] ?? '';
    $profileImage = $_FILES['profile_image'] ?? null;

    // Validasi field wajib
    if (empty($firstName) || empty($email) || empty($phoneNumber) || empty($username)) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Required fields cannot be empty']);
        exit;
    }

    // Mulai query dasar
    $fields = "first_name = ?, last_name = ?, email = ?, phone_number = ?, username = ?, bio = ?";
    $params = [$firstName, $lastName, $email, $phoneNumber, $username, $bio];
    $types = "ssssss";

    // Jika password diisi
    if (!empty($password)) {
        $fields .= ", password = ?";
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $params[] = $hashedPassword;
        $types .= "s";
    }

    // Jika upload gambar baru
    if ($profileImage && $profileImage['error'] === 0) {
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($profileImage['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions)) {
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'Only JPG, JPEG, and PNG files are allowed']);
            exit;
        }

        $uploadDir = '../../uploads/user_image/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // --- Hapus gambar lama (kalau ada dan bukan default) ---
        $queryOld = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
        $queryOld->bind_param('i', $userId);
        $queryOld->execute();
        $oldData = $queryOld->get_result()->fetch_assoc();
        $oldImage = $oldData['profile_image'] ?? '';

        if (!empty($oldImage) && file_exists("../../" . $oldImage) && strpos($oldImage, 'uploads/user_image/') !== false) {
            unlink("../../" . $oldImage);
        }

        // Upload file baru
        $filename = uniqid('profile_', true) . '.' . $ext;
        $relativePath = 'uploads/user_image/' . $filename; // path database
        $uploadPath = '../../' . $relativePath; // path server

        if (move_uploaded_file($profileImage['tmp_name'], $uploadPath)) {
            $fields .= ", profile_image = ?";
            $params[] = $relativePath;
            $types .= "s";
        } else {
            http_response_code(500);
            echo json_encode(['status' => false, 'message' => 'Failed to upload profile image']);
            exit;
        }
    }

    // Final query update
    $sql = "UPDATE users SET $fields WHERE id = ?";
    $params[] = $userId;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        // 🔥 Panggil fungsi refresh session
        refreshSessionData($conn, $userId);

        echo json_encode(['status' => true, 'message' => 'Profile updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => 'Failed to update profile']);
    }
    exit;

} else {
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => 'Method Not Allowed']);
    exit;
}
?>
