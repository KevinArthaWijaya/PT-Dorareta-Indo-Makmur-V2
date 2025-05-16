<?php
session_start();
header('Content-Type: application/json');

// Hancurkan semua session
session_unset();
session_destroy();

// Return JSON
echo json_encode([
    'status' => true,
    'message' => 'Logout berhasil.'
]);
exit;
?>
