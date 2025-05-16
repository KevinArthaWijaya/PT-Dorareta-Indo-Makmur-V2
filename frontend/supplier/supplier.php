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
  <title>Supplier Management - PT. Dorareta Indo Makmur</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="../../src/output.css" rel="stylesheet" />
  <link href="../css/header.css" rel="stylesheet" />
  <script src="../js/header.js" defer></script>
  <script src="../js/logout.js" defer></script>
  <script src="../js/supplier.js" defer></script>
</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen">
  <?php include '../partials/header.php'; ?>

  <main class="px-4 pt-28 pb-10 sm:px-6 lg:px-8">
    <!-- PAGE HEADER -->
    <div class="w-full mb-5">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-2">
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Supplier Management</h2>
        <div class="text-sm text-gray-500 dark:text-gray-300">
          <a href="../dashboard/dashboard.php" class="text-gray-600 dark:text-gray-200 font-medium hover:underline">Dashboard</a>
          <span class="mx-1">/</span>
          <span class="text-red-600 font-semibold">Supplier Management</span>
        </div>
      </div>
      <hr class="border-t border-gray-200 dark:border-gray-700 mt-4" />
    </div>

    <!-- SEARCH INPUT -->
    <div class="flex items-center gap-2 mb-4">
      <div class="relative w-full sm:w-1/3">
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none dark:hidden">
          <img src="../../assets/icons/lightmode/search-light.png" class="w-4 h-4" />
        </div>
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none hidden dark:flex">
          <img src="../../assets/icons/darkmode/search-dark.png" class="w-4 h-4" />
        </div>
        <input type="text" id="searchSupplier"
          class="w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring focus:ring-blue-300"
          placeholder="Cari supplier..." />
      </div>
    </div>

    <!-- SUPPLIER TABLE -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm" id="supplierTable">
      <thead class="bg-gray-100 dark:bg-gray-700 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
  <tr>
    <th class="px-4 py-3 text-left">No</th>
    <th class="px-4 py-3 text-left">Name</th>
    <th class="px-4 py-3 text-left">Phone</th>
    <th class="px-4 py-3 text-left">Email</th>
    <th class="px-4 py-3 text-left">Address</th>
    <th class="px-4 py-3 text-left">Actions</th>
  </tr>
</thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-800 dark:text-gray-200">
          <!-- JS akan isi data -->
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mt-6">
      <div class="flex items-center gap-2 text-sm text-black dark:text-white">
        <span>Show</span>
        <div class="relative">
          <select id="rowsPerPage"
            class="appearance-none text-sm pl-3 pr-8 py-1.5 rounded-md bg-transparent 
                   text-gray-900 dark:text-white focus:outline-none cursor-pointer [&>option]:text-black">
            <option value="5">5</option>
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
      <div class="flex items-center gap-2 flex-wrap" id="paginationControls"></div>
    </div>
  </main>

  <!-- MODAL EDIT SUPPLIER -->
  <div id="supplierModal"
       class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center overflow-y-auto p-4">
    <div id="supplierModalContent"
         class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg p-6 transform transition-all duration-300 scale-95 opacity-0">

      <div class="flex justify-between items-center mb-4">
        <h3 id="supplierModalTitle" class="text-2xl font-semibold text-gray-900 dark:text-white">Edit Supplier</h3>
        <button id="closeSupplierModal" class="text-gray-500 hover:text-red-500 transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <hr class="border-t border-gray-300 dark:border-gray-600 mb-6" />

      <form id="supplierForm" class="space-y-5">
  <input type="hidden" id="supplierId" />

  <div>
    <label for="supplierName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
    <input type="text" id="supplierName" required
      class="w-full mt-1 p-2 border rounded-md bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white focus:outline-none focus:ring focus:ring-blue-300" />
  </div>

  <div>
  <label for="supplierPhone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
  <div class="flex rounded-md shadow-sm">
    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm">+62</span>
    <input type="text" id="supplierPhone"
      class="w-full rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-2 text-gray-900 dark:text-white focus:outline-none focus:ring focus:ring-blue-300" />
  </div>
</div>

  <div>
    <label for="supplierEmail" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
    <input type="email" id="supplierEmail"
      class="w-full mt-1 p-2 border rounded-md bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white focus:outline-none focus:ring focus:ring-blue-300" />
  </div>

  <div>
    <label for="supplierAddress" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
    <textarea id="supplierAddress" rows="2"
      class="w-full mt-1 p-2 border rounded-md bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white focus:outline-none focus:ring focus:ring-blue-300"></textarea>
  </div>

  <div class="flex justify-end space-x-3 pt-4">
    <button type="button" id="cancelSupplierBtn"
      class="px-4 py-2 border rounded-md text-gray-700 dark:text-white border-gray-400 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
      Cancel
    </button>
    <button type="submit" id="submitSupplierBtn"
      class="px-6 py-2 rounded-md font-semibold bg-red-600 hover:bg-red-700 text-white transition">
      Update
    </button>
  </div>
</form>

    </div>
  </div>
</body>
</html>
