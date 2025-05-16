<?php
// Mulai session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi database
$host = "localhost";
$user = "root";
$password = "";
$database = "user_management";

// Buat koneksi
$conn = new mysqli($host, $user, $password, $database);

// Cek koneksi
if ($conn->connect_error) {
    http_response_code(500); // Supaya API response sesuai error server
    die(json_encode([
        "status" => false,
        "message" => "Koneksi database gagal: " . $conn->connect_error
    ]));
}

// Set karakter encoding
$conn->set_charset("utf8mb4");
?>
