<?php
// Anti cache
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

session_start();

// Langsung arahkan ke halaman login tanpa cek sesi
header("Location: auth/login.php");
exit();
?>
