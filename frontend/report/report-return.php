<?php
session_start();
$role = $_SESSION['role'] ?? 'Guest';
require '../../backend/config.php';

$allowed_roles = ['Admin', 'Manager', 'Logistic', 'Accounting'];

if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Return Report - PT. Dorareta Indo Makmur</title>
  <link href="../../src/output.css" rel="stylesheet" />
  <link href="../css/header.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script src="../js/header.js" defer></script>
  <script src="../js/logout.js" defer></script>
  <script src="../js/report-return.js" defer></script>
</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen">
  <?php include '../partials/header.php'; ?>

  <main class="px-4 pt-28 pb-10 sm:px-6 lg:px-8">
    <!-- PAGE HEADER -->
    <div class="mb-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-2">
        <h2 class="text-2xl font-semibold">Return Report</h2>
        <div class="text-sm text-gray-500 dark:text-gray-300">
          <a href="../dashboard/dashboard.php" class="hover:underline text-gray-600 dark:text-gray-200">Dashboard</a>
          <span class="mx-1">/</span>
          <span class="text-red-600 font-semibold">Return Report</span>
        </div>
      </div>
      <hr class="mt-4 border-gray-300 dark:border-gray-600" />
    </div>

    <!-- FILTER + EXPORT -->
    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-end gap-4 mb-6">
      <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
        <div>
          <label for="filterDate" class="block text-sm font-medium mb-1">Tanggal</label>
          <input type="text" id="filterDate" placeholder="Pilih tanggal"
            class="px-3 py-2 border rounded-md bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-sm text-gray-900 dark:text-white flatpickr w-full sm:w-48" />
        </div>
        <div>
          <label for="filterType" class="block text-sm font-medium mb-1">Tipe Return</label>
          <select id="filterType"
            class="px-3 py-2 border rounded-md bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-sm text-gray-900 dark:text-white w-full sm:w-48">
            <option value="">Semua</option>
            <option value="sales">Sales Return</option>
            <option value="purchase">Purchase Return</option>
          </select>
        </div>
        <div class="mt-6 sm:mt-5">
          <button id="resetFilters"
            class="text-sm px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            Reset
          </button>
        </div>
      </div>

      <!-- EXPORT -->
      <div class="flex gap-3">
        <button id="exportExcel" class="text-sm px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
          Export Excel
        </button>
        <button id="printReport" class="text-sm px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
          Print
        </button>
      </div>
    </div>

<!-- TABEL -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-x-auto">
  <table id="returnReportTable" class="w-full table-fixed text-sm">
    <colgroup>
      <col style="width: 160px;">
      <col style="width: 140px;">
      <col style="width: 200px;">
      <col style="width: 200px;">
      <col style="width: 140px;">
    </colgroup>
    <thead class="bg-gray-100 dark:bg-gray-700 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
      <tr>
        <th class="px-3 py-2">Tanggal</th>
        <th class="px-3 py-2 text-center">Tipe</th>
        <th class="px-3 py-2 text-center">Nomor Transaksi</th>
        <th class="px-3 py-2 text-center">Nama</th>
        <th class="px-3 py-2 text-right">Jumlah Return</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-800 dark:text-gray-200">
      <!-- Diisi oleh JS -->
    </tbody>
  </table>
</div>


    <!-- PAGINATION -->
    <div class="flex justify-between items-center mt-6 text-sm">
      <div class="flex items-center gap-2">
        <span>Show</span>
        <div class="relative">
          <select id="rowsPerPage"
            class="appearance-none px-2 py-1 pr-8 rounded-md bg-transparent border border-transparent
                   text-gray-800 dark:text-white focus:outline-none focus:ring-0 focus:border-transparent
                   [&>option]:text-black cursor-pointer">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
          </select>
          <img src="../../assets/icons/lightmode/arrow-down-light.png"
            class="absolute right-2 top-1/2 w-4 h-4 transform -translate-y-1/2 pointer-events-none opacity-60 block dark:hidden" />
          <img src="../../assets/icons/darkmode/arrow-down-dark.png"
            class="absolute right-2 top-1/2 w-4 h-4 transform -translate-y-1/2 pointer-events-none opacity-80 hidden dark:block" />
        </div>
        <span>entries</span>
      </div>
      <div id="paginationControls" class="flex gap-2 flex-wrap"></div>
    </div>
  </main>
</body>
</html>
