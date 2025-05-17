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
  <title>Category Management - PT. Dorareta Indo Makmur</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="../../src/output.css" rel="stylesheet" />
  <link href="../css/header.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../js/header.js" defer></script>
  <script src="../js/logout.js" defer></script>
  <script src="../js/category.js" defer></script>
</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100 min-h-screen">
  <?php include '../partials/header.php'; ?>

  <main class="px-4 pt-28 pb-10 sm:px-6 lg:px-8">
    <!-- PAGE HEADER -->
    <div class="w-full">
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-2 mb-5">
    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Category Management</h2>
    <div class="text-sm text-gray-500 dark:text-gray-300">
      <a href="../dashboard/dashboard.php" class="text-gray-600 dark:text-gray-200 font-medium hover:underline">Dashboard</a>
      <span class="mx-1">/</span>
      <span class="text-red-600 font-semibold">Category Management</span>
    </div>
  </div>
  <hr class="border-t border-gray-200 dark:border-gray-700 mb-8" />
</div>

<!-- CREATE BUTTON -->
<div class="mb-4 flex justify-end">
  <button id="openCreateCategory"
    class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm px-4 py-2 rounded-md shadow transition">
    Create Category
  </button>
</div>

    <!-- TABLE -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-x-auto">
      <table id="categoryTable" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
        <thead class="bg-gray-100 dark:bg-gray-700 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
          <tr>
            <th class="px-4 py-3 text-left">No</th>
            <th class="px-5 py-3 text-left">Category Name</th>
            <th class="px-4 py-3 text-left">Prefix SKU</th>
            <th class="px-4 py-3 text-left">Actions</th>
          </tr>
        </thead>
        <tbody id="categoryTableBody" class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-800 dark:text-gray-200">
          <!-- Filled via JS -->
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mt-6">
  <!-- Show per page -->
  <div class="flex items-center gap-2 text-sm text-black dark:text-white">
    <span>Show</span>
    <div class="relative">
      <select id="rowsPerPage"
        class="appearance-none text-sm pl-3 pr-8 py-1.5 rounded-md bg-transparent 
               text-gray-900 dark:text-white focus:outline-none cursor-pointer [&>option]:text-black">
        <option value="5" selected>5</option>
        <option value="10">10</option>
        <option value="25">25</option>
      </select>
      <img src="../../assets/icons/lightmode/arrow-down-light.png"
        class="absolute right-2 top-1/2 w-4 h-4 transform -translate-y-1/2 pointer-events-none opacity-60 block dark:hidden" />
      <img src="../../assets/icons/darkmode/arrow-down-dark.png"
        class="absolute right-2 top-1/2 w-4 h-4 transform -translate-y-1/2 pointer-events-none opacity-80 hidden dark:block" />
    </div>
    <span>entries</span>
  </div>

  <!-- Pagination Buttons -->
  <div id="paginationControls" class="flex items-center gap-2 flex-wrap">
    <!-- JS akan isi tombol di sini -->
  </div>
</div>

  </main>

  <!-- MODAL -->
  <div id="categoryModal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-md p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 id="categoryModalTitle" class="text-xl font-semibold">Create Category</h3>
        <button id="closeCategoryModal" class="text-gray-500 hover:text-red-500">
          ✕
        </button>
      </div>
      <form id="categoryForm" class="space-y-4">
        <input type="hidden" id="categoryId" />
        <div>
          <label for="categoryName" class="block text-sm font-medium">Category Name</label>
          <input type="text" id="categoryName" required
            class="mt-1 w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:border-gray-600 text-sm" />
        </div>
        <div>
          <label for="prefixSKU" class="block text-sm font-medium">Prefix SKU</label>
          <input type="text" id="skuPrefix" required maxlength="5"
            class="mt-1 w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:border-gray-600 text-sm uppercase" />
        </div>
        <div class="flex justify-end gap-3 pt-3">
          <button type="button" id="cancelCategoryBtn"
            class="px-4 py-2 border border-gray-400 dark:border-gray-600 rounded text-sm">Cancel</button>
          <button type="submit" id="submitCategoryBtn"
            class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-semibold">Save</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
