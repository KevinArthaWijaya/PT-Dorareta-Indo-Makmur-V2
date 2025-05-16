<?php
session_start();
require '../../backend/config.php'; // Perbaiki path ke config.php

// Cek apakah user sudah login & admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

// Ambil data error logs
$query = "SELECT error_logs.*, users.first_name, users.last_name 
          FROM error_logs 
          LEFT JOIN users ON error_logs.user_id = users.id 
          ORDER BY error_time DESC";

$result = $conn->query($query);
$logs = $result->fetch_all(MYSQLI_ASSOC); // Store the result into an array to reuse it
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <title>Error Logs - PT. Dorareta Indo Makmur</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Added viewport for mobile responsiveness -->
    <link href="../../src/output.css" rel="stylesheet" />
    <script src="../js/header.js" defer></script>
    <script src="../js/logout.js" defer></script>
</head>

<body class="bg-gray-100 dark:bg-gray-900">

    <?php include '../partials/header.php'; // Perbaiki path ke header.php ?>

    <main class="p-8 pt-20">
        <!-- Breadcrumb -->
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Logs Error</h2>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                <a href="../../frontend/dashboard/dashboard.php" class="text-grey-600 hover:underline dark:text-gray-300">Dashboard</a> 
                <span class="mx-2 text-gray-400 dark:text-gray-500">/</span>
                <span class="text-red-500 dark:text-red-400">Error Logs</span>
            </span>
        </div>

        <div class="h-px bg-gray-300 mb-6 dark:bg-gray-700"></div>

        <!-- Table for Desktop -->
        <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow-md md:block hidden">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-200 dark:bg-gray-700">
                    <tr>
                        <th class="py-3 px-6 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Waktu</th>
                        <th class="py-3 px-6 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Username</th>
                        <th class="py-3 px-6 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Nama Lengkap</th>
                        <th class="py-3 px-6 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Pesan Error</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $row): ?>
                        <?php
                        $fullName = $row['first_name'] ? $row['first_name'] . " " . $row['last_name'] : "-";
                        ?>
                        <tr class="border-t border-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="py-4 px-6 text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($row['error_time']) ?></td>
                            <td class="py-4 px-6 text-sm text-gray-600 dark:text-gray-200"><?= htmlspecialchars($row['username']) ?></td>
                            <td class="py-4 px-6 text-sm text-gray-600 dark:text-gray-200"><?= htmlspecialchars($fullName) ?></td>
                            <td class="py-4 px-6 text-sm text-gray-600 dark:text-gray-200"><?= htmlspecialchars($row['error_message']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Card Layout for Mobile -->
        <div class="md:hidden bg-white dark:bg-gray-800 rounded-lg shadow-md mt-8">
            <?php foreach ($logs as $row): ?>
                <?php
                $fullName = $row['first_name'] ? $row['first_name'] . " " . $row['last_name'] : "-";
                ?>
                <div class="border-t border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-200">Waktu: <?= htmlspecialchars($row['error_time']) ?></div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">Username: <?= htmlspecialchars($row['username']) ?></div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">Nama Lengkap: <?= htmlspecialchars($fullName) ?></div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">Pesan Error: <?= htmlspecialchars($row['error_message']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

</body>

</html>
