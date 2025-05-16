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
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Product Management - PT. Dorareta Indo Makmur</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.2/xlsx.full.min.js"></script>
  <link href="../../src/output.css" rel="stylesheet" />
  <link href="../css/header.css" rel="stylesheet" />
  <script src="../js/products.js" defer></script>
  <script src="../js/header.js" defer></script>
  <script src="../js/logout.js" defer></script>
  <script> const USER_ROLE = "<?= $role ?>"; </script>
</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen">
<?php include '../partials/header.php'; ?>

<main class="px-4 pt-20 pb-10 sm:px-6 lg:px-8">
  <div class="w-full">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-2 mb-5">
      <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Product Management</h2>
      <div class="text-sm text-gray-500 dark:text-gray-300">
        <a href="../dashboard/dashboard.php" class="text-gray-600 dark:text-gray-200 font-medium hover:underline">Dashboard</a>
        <span class="mx-1">/</span>
        <span class="text-red-600 font-semibold">Product Management</span>
      </div>
    </div>
    <hr class="border-t border-gray-200 dark:border-gray-700 mb-8" />
  </div>

  <!-- TOOLBAR -->
  <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full sm:w-auto">
      <button id="openFilterModal"
        class="flex items-center gap-2 px-5 py-2 border border-red-600 text-red-600 text-sm font-medium rounded-md transition group hover:bg-red-600 hover:text-white">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-current transition group-hover:stroke-white" fill="none" viewBox="0 0 24 24">
          <line x1="21" y1="4" x2="14" y2="4" />
          <line x1="10" y1="4" x2="3" y2="4" />
          <line x1="21" y1="12" x2="12" y2="12" />
          <line x1="8" y1="12" x2="3" y2="12" />
          <line x1="21" y1="20" x2="16" y2="20" />
          <line x1="12" y1="20" x2="3" y2="20" />
          <line x1="14" y1="2" x2="14" y2="6" />
          <line x1="8" y1="10" x2="8" y2="14" />
          <line x1="16" y1="18" x2="16" y2="22" />
        </svg>
        Filter
      </button>

      <div class="relative w-full sm:w-64">
        <input type="text" id="searchInput" placeholder="Search on this table"
          class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-gray-800 bg-white dark:bg-gray-800 dark:text-gray-100">
        <img src="../../assets/icons/lightmode/search-light.png"
          class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 opacity-60 pointer-events-none block dark:hidden" alt="Search" />
        <img src="../../assets/icons/darkmode/search-dark.png"
          class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 opacity-80 pointer-events-none hidden dark:block" alt="Search" />
      </div>
    </div>

        <!-- Right: Buttons -->
    <div class="flex flex-wrap gap-3 w-full sm:w-auto">
      <button id="exportExcelBtn"
        class="px-5 py-2 border border-emerald-500 text-emerald-500 rounded-md text-sm font-medium transition hover:bg-emerald-500 hover:text-white hover:border-transparent w-full sm:w-auto">
        Excel
      </button>

      <button class="btn-import flex items-center justify-center gap-2 px-5 py-2 bg-emerald-500 text-white text-sm font-medium rounded hover:bg-emerald-600 transition w-full sm:w-auto">
        <img src="../../assets/icons/lightmode/import-light.png" class="w-4 h-4 filter invert" alt="Import"> Import
      </button>
      <input type="file" id="importFile" accept=".csv, .xls, .xlsx" class="hidden" />

      <button id="openCreateProduct"
        class="relative overflow-hidden group px-6 py-2 rounded-md bg-neutral-800 text-white text-sm font-bold isolate w-full sm:w-auto">
        <span class="relative z-10">Create Product</span>
        <span class="absolute top-0 left-0 w-full h-full bg-red-700 translate-y-[-100%] group-hover:translate-y-0 transition-transform duration-300 ease-in-out z-0"></span>
      </button>
    </div>
  </div>

  <!-- PRODUCT TABLE -->
  <div class="overflow-x-auto bg-white dark:bg-gray-800 shadow rounded-lg">
    <table id="productTable" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs tracking-wider">
        <tr>
          <th class="px-4 py-3 text-left">Image</th>

          <th class="px-4 py-3 text-left cursor-pointer sortable" data-column="product_name">
            Product Name
            <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
            <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
          </th>

          <th class="px-4 py-3 text-left cursor-pointer sortable" data-column="sku">
            SKU
            <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
            <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
          </th>

          <th class="px-4 py-3 text-left cursor-pointer sortable" data-column="category_id">
            Category
            <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
            <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
          </th>

          <th class="px-4 py-3 text-left cursor-pointer sortable" data-column="unit_id">
            Unit
            <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
            <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
          </th>

          <th class="px-4 py-3 text-left cursor-pointer sortable" data-column="selling_price">
            Selling Price
            <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
            <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
          </th>

          <th class="px-4 py-3 text-left cursor-pointer sortable" data-column="stock_quantity">
            Quantity
            <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
            <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
          </th>

          <th class="px-4 py-3 text-center cursor-pointer sortable" data-column="status">
            Status
            <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
            <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
          </th>

          <th class="px-4 py-3 text-center">Action</th>
        </tr>
      </thead>
      <tbody id="productTableBody" class="divide-y divide-gray-200 dark:divide-gray-700 text-sm text-gray-800 dark:text-gray-100">
        <!-- Product rows will be loaded by JS -->
      </tbody>
    </table>
  </div>

    <!-- PAGINATION -->
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mt-6">
    <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
      <span>Show per page:</span>
      <div class="relative">
        <select id="rowsPerPage"
          class="appearance-none text-sm pl-3 pr-8 py-1.5 rounded-md bg-transparent text-gray-800 dark:text-white focus:outline-none [&>option]:text-black">
          <option value="10" selected>10</option>
          <option value="20">20</option>
          <option value="30">30</option>
        </select>
        <img src="../../assets/icons/lightmode/arrow-down-light.png"
          class="absolute right-2 top-1/2 w-4 h-4 transform -translate-y-1/2 pointer-events-none opacity-60 block dark:hidden" />
        <img src="../../assets/icons/darkmode/arrow-down-dark.png"
          class="absolute right-2 top-1/2 w-4 h-4 transform -translate-y-1/2 pointer-events-none opacity-80 hidden dark:block" />
      </div>
    </div>
    <div id="paginationContainer" class="flex items-center gap-2 flex-wrap">
      <!-- Filled by JS -->
    </div>
  </div>

  <!-- MODAL CREATE / EDIT PRODUCT -->
  <div id="productModal"
    class="fixed inset-0 z-[1050] hidden bg-black/50 backdrop-blur-sm items-center justify-center overflow-y-auto transition-opacity duration-300">
    <div id="productModalContent"
      class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-4xl p-6 relative transform scale-95 opacity-0 transition-all duration-300 ease-out">

      <!-- Header -->
      <div class="flex justify-between items-center mb-4">
        <h3 id="modalTitle" class="text-xl font-bold text-gray-800 dark:text-white">Create Product</h3>
        <button id="closeProductModal" class="text-gray-500 hover:text-red-500 transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="border-t border-gray-300 dark:border-gray-600 mb-6"></div>

      <!-- FORM -->
      <form id="productForm" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="product_id" id="product_id" />

        <div>
          <label for="product_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Product Name</label>
          <input type="text" name="product_name" id="product_name" required
            class="w-full rounded-md border border-gray-400 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white placeholder-gray-500 focus:ring-1 focus:ring-gray-400 focus:outline-none p-2" />
        </div>

        <div>
          <label for="sku" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SKU</label>
          <input type="text" name="sku" id="sku" required
            class="w-full rounded-md border border-gray-400 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white placeholder-gray-500 focus:ring-1 focus:ring-gray-400 focus:outline-none p-2" />
        </div>

        <div>
          <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
          <select name="category_id" id="category_id" required
            class="w-full rounded-md border border-gray-400 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white p-2 focus:ring-1 focus:ring-gray-400 focus:outline-none">
            <option value="">-- Select --</option>
          </select>
        </div>

        <div>
          <label for="unit_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unit</label>
          <select name="unit_id" id="unit_id" required
            class="w-full rounded-md border border-gray-400 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white p-2 focus:ring-1 focus:ring-gray-400 focus:outline-none">
            <option value="">-- Select --</option>
          </select>
        </div>

        <div>
          <label for="selling_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Selling Price</label>
          <div class="flex rounded-md border border-gray-400 dark:border-gray-600 overflow-hidden bg-white dark:bg-gray-700 h-[38px]">
            <span class="flex items-center justify-center px-3 bg-gray-200 dark:bg-gray-600 text-sm text-gray-800 dark:text-white whitespace-nowrap">Rp</span>
            <input type="text" name="selling_price" id="selling_price" required
              class="flex-1 border-none focus:ring-0 px-3 text-sm bg-transparent text-black dark:text-white placeholder-gray-400" />
          </div>
        </div>

        <div>
          <label for="minimal_purchase" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Min Purchase</label>
          <input type="number" name="minimal_purchase" id="minimal_purchase" min="0" required
            class="w-full rounded-md border border-gray-400 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white focus:ring-1 focus:ring-gray-400 focus:outline-none p-2" />
        </div>

        <div>
          <label for="stock_quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Stock Quantity</label>
          <input type="number" name="stock_quantity" id="stock_quantity" min="0" required
            class="w-full rounded-md border border-gray-400 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white focus:ring-1 focus:ring-gray-400 focus:outline-none p-2" />
        </div>

        <div>
          <label for="min_stock_warning" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Min Stock Warning</label>
          <input type="number" name="min_stock_warning" id="min_stock_warning" min="0" required
            class="w-full rounded-md border border-gray-400 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white focus:ring-1 focus:ring-gray-400 focus:outline-none p-2" />
        </div>

                <div>
          <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
          <select name="status" id="status" required
            class="w-full rounded-md border border-gray-400 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white p-2 focus:ring-1 focus:ring-gray-400 focus:outline-none">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>

        <div>
          <label for="product_image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Image</label>
          <div class="flex items-center gap-4">
            <label for="product_image"
              class="cursor-pointer inline-block px-4 py-2 border border-gray-400 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-800 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600 transition">
              Choose File
            </label>
            <span id="fileName" class="text-sm text-gray-600 dark:text-gray-300">No file chosen</span>
          </div>
          <input type="file" name="product_image" id="product_image" accept="image/*" class="hidden">
        </div>

        <div class="md:col-span-2 flex justify-end gap-3 pt-4">
          <button type="button" id="cancelProductModalBtn"
            class="px-4 py-2 rounded-md border border-gray-400 text-gray-700 dark:text-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
            Cancel
          </button>
          <button type="submit" id="saveProductBtn"
            class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition font-bold">
            Save Product
          </button>
        </div>
      </form>
    </div>
</div>


  <!-- MODAL FILTER PRODUCT -->
  <div id="filterProductModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4">
    <div id="filterModalContent" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-3xl p-6 scale-95 opacity-0 transition-all duration-300">

      <!-- Modal Header -->
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white">Filter Products</h3>
        <button id="closeFilterModal" class="text-gray-500 hover:text-red-600 dark:hover:text-red-400">
          <img src="../../assets/icons/lightmode/cancel-light.png" alt="Close" class="w-5 h-5 block dark:hidden">
          <img src="../../assets/icons/darkmode/cancel-dark.png" alt="Close" class="w-5 h-5 hidden dark:block">
        </button>
      </div>

      <hr class="border-t border-gray-200 dark:border-gray-600 mb-4" />

      <!-- Form Filter -->
      <form id="filterProductForm" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">SKU</label>
            <select id="filterSku"
              class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 text-sm bg-white dark:bg-gray-700 dark:text-white">
              <option value="">-- Semua --</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
            <select id="filterCategory"
              class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 text-sm bg-white dark:bg-gray-700 dark:text-white">
              <option value="">-- Semua --</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unit</label>
            <select id="filterUnit"
              class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 text-sm bg-white dark:bg-gray-700 dark:text-white">
              <option value="">-- Semua --</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stock Condition</label>
            <select id="filterStockCondition"
              class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 text-sm bg-white dark:bg-gray-700 dark:text-white">
              <option value="">-- Semua --</option>
              <option value="less_or_equal">Stock ≤ Min</option>
              <option value="more">Stock > Min</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
            <select id="filterStatus"
              class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 text-sm bg-white dark:bg-gray-700 dark:text-white">
              <option value="">-- Semua --</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>

        <!-- Filter Buttons -->
        <div class="flex flex-col md:flex-row justify-between items-center gap-3 pt-4">
          <button type="button" id="clearFilterBtn"
            class="w-full md:w-auto border border-red-600 text-red-600 font-medium px-4 py-2 rounded hover:bg-red-50 dark:hover:bg-red-900 transition">
            Clear Filter
          </button>
          <button type="submit"
            class="w-full md:w-auto bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-2 rounded-md transition">
            Apply Filter
          </button>
        </div>
      </form>
    </div>
  </div>
</main>
</body>
</html>
