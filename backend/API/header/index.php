<?php
session_start();
header('Content-Type: application/json');

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit;
}

// Ambil path dari session
$profilePath = $_SESSION['profile_image'] ?? '';
$localPath = "../../" . $profilePath; // untuk cek keberadaan file di server
$finalPath = (!empty($profilePath) && file_exists($localPath))
    ? "../../backend/" . $profilePath
    : "../../assets/image/default-user-lightmode.png";

// Kirim data user
$userInfo = [
    'user_id' => $_SESSION['user_id'],
    'full_name' => $_SESSION['full_name'] ?? 'User',
    'role' => $_SESSION['role'] ?? 'Guest',
    'profile_image' => $finalPath
];

echo json_encode([
    'status' => true,
    'data' => $userInfo
]);
