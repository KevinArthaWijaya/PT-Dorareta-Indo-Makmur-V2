<?php
session_start();

// Jika user sudah login, arahkan ke dashboard
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}

// Jika belum login, arahkan ke halaman login
header("Location: auth/login.php");
exit();
?>
