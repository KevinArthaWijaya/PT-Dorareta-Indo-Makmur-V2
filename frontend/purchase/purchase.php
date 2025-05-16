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
  <title>Purchase Management - PT. Dorareta Indo Makmur</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="../../src/output.css" rel="stylesheet" />
  <link href="../css/header.css" rel="stylesheet" />
  <link href="../css/purchase.css" rel="stylesheet" />
  <script src="../js/header.js" defer></script>
  <script src="../js/logout.js" defer></script>
  <script src="../js/purchase.js" defer></script>
  <script> const USER_ROLE = "<?= $role ?>"; </script>
</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen">
  <?php include '../partials/header.php'; ?>

  <main class="px-4 pt-20 pb-10 sm:px-6 lg:px-8">
    <!-- PAGE HEADER -->
    <div class="w-full">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-2 mb-5">
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Purchase Management</h2>
        <div class="text-sm text-gray-500 dark:text-gray-300">
          <a href="../dashboard/dashboard.php" class="text-gray-600 dark:text-gray-200 font-medium hover:underline">Dashboard</a>
          <span class="mx-1">/</span>
          <span class="text-red-600 font-semibold">Purchase Management</span>
        </div>
      </div>
      <hr class="border-t border-gray-200 dark:border-gray-700 mb-8" />
    </div>

    <!-- TOOLBAR -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
      <!-- Filter & Search -->
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
          <input type="text" id="searchPurchase" placeholder="Search purchases"
            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 bg-white dark:bg-gray-800 dark:text-gray-100">
          <img src="../../assets/icons/lightmode/search-light.png"
            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 opacity-60 pointer-events-none block dark:hidden" alt="Search" />
          <img src="../../assets/icons/darkmode/search-dark.png"
            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 opacity-80 pointer-events-none hidden dark:block" alt="Search" />
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-wrap gap-3 w-full sm:w-auto">
        <!-- Create Purchase -->
        <button id="openCreatePurchase"
          class="relative overflow-hidden group px-6 py-2 rounded-md bg-neutral-800 text-white text-sm font-bold isolate w-full sm:w-auto">
          <span class="relative z-10">Create Purchase</span>
          <span class="absolute top-0 left-0 w-full h-full bg-red-700 -translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-in-out z-0"></span>
        </button>
      </div>
    </div>

    <!-- PURCHASE TABLE -->
<div class="overflow-x-auto bg-white dark:bg-gray-800 shadow rounded-lg">
  <table id="purchaseTable" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
<thead class="bg-gray-100 dark:bg-gray-700 text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 tracking-wider">
  <tr class="divide-x divide-gray-200 dark:divide-gray-600">
    <th class="px-4 py-3 text-left">Invoice No</th>

    <th class="px-4 py-3 text-left cursor-pointer sortable" data-column="date">
      Date
      <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
      <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
    </th>

    <th class="px-4 py-3 text-left cursor-pointer sortable" data-column="supplier">
      Supplier
      <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
      <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
    </th>

    <!-- Items Column -->
    <th class="px-4 py-3 text-left cursor-pointer sortable" data-column="items">
  Items
  <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
  <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
</th>

    <th class="px-4 py-3 text-center cursor-pointer sortable" data-column="status">
      Status
      <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
      <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
    </th>

    <th class="px-4 py-3 text-right cursor-pointer sortable" data-column="grand_total">
      Grand Total
      <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
      <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
    </th>

    <th class="px-4 py-3 text-right cursor-pointer sortable" data-column="paid">
      Paid
      <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
      <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
    </th>

    <th class="px-4 py-3 text-right cursor-pointer sortable" data-column="due">
      Due
      <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
      <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
    </th>

    <th class="px-4 py-3 text-center cursor-pointer sortable" data-column="payment_status">
      Payment Status
      <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
      <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
    </th>

    <th class="px-4 py-3 text-center">Action</th>
  </tr>
</thead>
    <tbody id="purchaseTableBody" class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-800 dark:text-gray-100">
      <!-- Data dari JS -->
    </tbody>
  </table>
</div>

<!-- PAGINATION -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mt-6">
  <!-- Show per page -->
  <div class="flex items-center gap-2 text-sm text-black dark:text-white">
  <span>Show</span>
  <div class="relative">
  <select id="purchaseRowsPerPage"
  class="appearance-none text-sm pl-3 pr-8 py-1.5 rounded-md bg-transparent 
         text-gray-900 dark:text-white focus:outline-none cursor-pointer [&>option]:text-black">
      <option value="10" selected>10</option>
      <option value="20">20</option>
      <option value="30">30</option>
    </select>
    <img src="../../assets/icons/lightmode/arrow-down-light.png"
      class="absolute right-2 top-1/2 w-4 h-4 transform -translate-y-1/2 pointer-events-none opacity-60 block dark:hidden" />
    <img src="../../assets/icons/darkmode/arrow-down-dark.png"
      class="absolute right-2 top-1/2 w-4 h-4 transform -translate-y-1/2 pointer-events-none opacity-80 hidden dark:block" />
  </div>
  <span>entries</span>
</div>

  <!-- Pagination -->
  <div id="purchasePaginationContainer" class="flex items-center gap-2 flex-wrap">
    <!-- JS akan isi tombol di sini -->
  </div>
</div>
  </main>
</body>
</html>

<!-- MODAL CREATE/EDIT PURCHASE -->
<div id="purchaseModal"
     class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center overflow-y-auto p-4">
  <div id="purchaseModalContent"
     class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-5xl p-6 transform transition-all duration-300 scale-95 opacity-0">

    <!-- Header -->
    <div class="flex justify-between items-center mb-4">
      <h3 id="purchaseModalTitle" class="text-2xl font-semibold text-gray-900 dark:text-white">Create Purchase</h3>
      <button id="closePurchaseModal" class="text-gray-500 hover:text-red-500 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <hr class="border-t border-gray-300 dark:border-gray-600 mb-6" />

    <form id="purchaseForm" enctype="multipart/form-data" class="space-y-6">
      <input type="hidden" id="purchase_id" name="purchase_id" />

      <!-- Form Inputs -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="invoice_no" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Invoice No</label>
          <input type="text" id="invoice_no" name="invoice_no" readonly 
  class="w-full mt-1 p-2 border rounded-md bg-gray-100 dark:bg-gray-600 border-gray-300 dark:border-gray-500 text-gray-900 dark:text-white cursor-not-allowed" />
        </div>
        <div>
          <label for="purchase_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
          <input type="text" id="purchase_date" name="date" required class="w-full mt-1 p-2 border rounded-md bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white" />
        </div>
<div class="relative">
  <label for="supplier_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Supplier</label>
  <input type="text" id="supplier_name" name="supplier_name" required 
         autocomplete="off"
         class="w-full mt-1 p-2 border rounded-md bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white" />
  
  <!-- Dropdown suggestion -->
  <ul id="supplierSuggestions"
      class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-48 overflow-y-auto hidden text-sm text-gray-900 dark:text-white">
  </ul>
</div>
        <div>
          <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
          <select id="status" name="status" required class="w-full mt-1 p-2 border rounded-md bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white">
            <option value="ordered">Ordered</option>
            <option value="received">Received</option>
            <option value="pending">Pending</option>
          </select>
        </div>
      </div>

<!-- Product Item Table -->
<div class="mt-6 border border-gray-300 dark:border-gray-600 rounded-md overflow-hidden">
  <div class="grid grid-cols-12 gap-2 bg-gray-200 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-700 dark:text-white border-b border-gray-300 dark:border-gray-600">
    <div class="col-span-4">Product</div>
    <div class="col-span-2 text-center">Quantity</div>
    <div class="col-span-2 text-center">Cost</div>
    <div class="col-span-3 text-center">Subtotal</div>
    <div class="col-span-1"></div>
  </div>

  <!-- Container with max-height and scroll -->
  <div class="px-2 divide-y divide-gray-200 dark:divide-gray-700 max-h-[204px] overflow-y-auto" id="purchaseItemsContainer">
    <!-- JS generated product rows -->
  </div>

  <!-- Add Product button inside the scroll section -->
  <div class="px-4 pb-3 pt-2">
    <button type="button" id="addProductRow" class="text-sm text-emerald-600 font-medium hover:underline">+ Add Product</button>
  </div>
</div>

<!-- Total -->
<div class="flex justify-end text-lg font-bold text-gray-800 dark:text-white mt-4">
  <span class="mr-2">Total:</span>
  <span id="grandTotalDisplay">Rp0</span>
</div>

      <!-- Payment Info -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div>
          <label for="payment_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Status</label>
          <select id="payment_status" name="payment_status" required class="w-full mt-1 p-2 border rounded-md bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white">
            <option value="unpaid">Unpaid</option>
            <option value="partial">Partial</option>
            <option value="paid">Paid</option>
          </select>
        </div>
        <div>
          <label for="paid" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Paid</label>
          <input type="text" id="paid" name="paid" min="0" class="w-full mt-1 p-2 border rounded-md bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white" />
        </div>
      </div>

      <!-- Summary -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
  <div>
    <label for="due" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Due</label>
    <input type="text" id="due" name="due" readonly class="w-full mt-1 p-2 border rounded-md bg-gray-100 dark:bg-gray-600 border-gray-300 dark:border-gray-500 text-gray-900 dark:text-white" />
  </div>
  <div>
  <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
  <textarea id="notes" name="notes" rows="1" class="w-full mt-1 p-2 border rounded-md bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white resize-y"></textarea>
</div>
</div>

      <!-- Actions -->
      <div class="flex justify-end space-x-3 pt-6">
        <button type="button" id="cancelPurchaseBtn" class="px-4 py-2 border rounded-md text-gray-700 dark:text-white border-gray-400 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition">Cancel</button>
        <button type="submit" id="submitPurchaseBtn" class="px-6 py-2 rounded-md font-semibold bg-red-600 hover:bg-red-700 text-white transition">Save Purchase</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL VIEW PURCHASE DETAIL -->
<div id="viewPurchaseModal"
  class="fixed inset-0 z-[60] hidden bg-black/50 backdrop-blur-sm flex items-center justify-center overflow-y-auto p-4">
  <div id="viewPurchaseContent"
    class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-5xl p-6 transform scale-95 opacity-0 transition-all duration-300">

    <!-- Header Modal -->
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Purchase Detail</h2>
      <button id="closeViewPurchaseModal" class="text-gray-400 hover:text-red-600 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <hr class="border-t border-gray-300 dark:border-gray-600 -mt-2 mb-5">

    <!-- Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-4 text-gray-800 dark:text-white">
      <div>
        <p><strong>DATE:</strong> <span id="view_date" class="text-blue-600"></span></p>
        <p><strong>STATUS:</strong> <span id="view_status"
            class="inline-block text-xs font-semibold px-2 py-1 rounded-md"></span></p>
        <p><strong>INVOICE NO:</strong> <span id="view_invoice"></span></p>
      </div>
      <div>
        <p><strong>PAYMENT STATUS:</strong> <span id="view_payment_status"
            class="inline-block text-xs font-semibold px-2 py-1 rounded-md"></span></p>
        <p><strong>SUPPLIER:</strong> <span id="view_supplier"></span></p>
      </div>
    </div>

    <!-- Tabel Produk -->
    <div class="overflow-x-auto mb-6 rounded-xl">
      <div class="scrollbar-hide overflow-auto max-h-80">
        <table class="min-w-full text-sm text-left text-gray-800 dark:text-white">
          <thead
            class="bg-gray-50 dark:bg-gray-700 text-xs text-gray-600 dark:text-gray-300 uppercase font-medium">
            <tr>
              <th class="px-4 py-2">No</th>
              <th class="px-4 py-2">SKU</th>
              <th class="px-4 py-2">Product</th>
              <th class="px-4 py-2 text-center">Qty</th>
              <th class="px-4 py-2 text-right">Cost</th>
              <th class="px-4 py-2 text-right">Subtotal</th>
            </tr>
          </thead>
          <tbody id="viewPurchaseItems" class="divide-y divide-gray-100 dark:divide-gray-700">
            <!-- diisi JS -->
          </tbody>
        </table>
      </div>
    </div>

    <!-- Ringkasan Total -->
    <div class="flex justify-end mb-6">
      <div
        class="bg-white dark:bg-gray-700 shadow-sm rounded-lg border border-gray-300 dark:border-gray-600 w-full sm:w-[400px] px-6 py-4 text-sm text-gray-800 dark:text-white">
        <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-600 pb-2 mb-2">
          <span class="font-medium text-gray-600 dark:text-gray-300">Grand Total</span>
          <span id="view_grand_total" class="font-semibold text-lg text-gray-900 dark:text-white">Rp0</span>
        </div>
        <div class="flex justify-between items-center mb-1">
          <span class="text-gray-500 dark:text-gray-400">Paid</span>
          <span id="view_paid" class="font-medium">Rp0</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-500 dark:text-gray-400">Due</span>
          <span id="view_due" class="font-medium text-red-600">Rp0</span>
        </div>
      </div>
    </div>

    <!-- Tombol Aksi -->
    <div class="flex justify-end gap-3">
    <button class="returnBtn bg-yellow-400 hover:bg-yellow-500 text-white font-medium px-4 py-2 rounded-md flex items-center gap-2 transition"
        id="returnBtnFinal">
  <img src="../../assets/icons/darkmode/return-product-dark.png" alt="Return Icon" class="w-4 h-4"> Return
</button>
      <button id="printPurchaseBtn"
        class="bg-green-500 hover:bg-green-600 text-white font-medium px-4 py-2 rounded-md flex items-center gap-2 transition">
        <img src="../../assets/icons/darkmode/print-dark.png" alt="Print Icon" class="w-4 h-4"> Print
      </button>
    </div>
  </div>
</div>

<!-- MODAL RETURN PURCHASE -->
<div id="returnPurchaseModal"
  class="fixed inset-0 z-[60] hidden bg-black/50 backdrop-blur-sm flex items-center justify-center overflow-y-auto p-4">
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-4xl p-6 relative">

    <form id="returnPurchaseForm">
      <!-- ✅ Hidden Input untuk purchase_id -->
      <input type="hidden" id="return_purchase_id" name="purchase_id" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Purchase Return</h2>
        <button id="closeReturnModalBtn" type="button" class="text-gray-400 hover:text-red-600 transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      <hr class="border-t border-gray-300 dark:border-gray-600 -mt-2 mb-5">

      <!-- Info -->
      <div class="text-sm mb-4">
        <p class="font-bold">Purchase Info:</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
          <div>
            <p>DATE: <span id="return_date"></span></p>
            <p>STATUS: <span id="return_status"
                class="inline-block text-xs font-semibold px-2 py-1 rounded-md bg-green-100 text-green-700"></span>
            </p>
            <p>INVOICE NO: <span id="return_invoice"></span></p>
          </div>
          <div>
            <p>PAYMENT STATUS: <span id="return_payment_status"
                class="inline-block text-xs font-semibold px-2 py-1 rounded-md bg-red-100 text-red-700"></span></p>
            <p>SUPPLIER: <span id="return_supplier"></span></p>
          </div>
        </div>
      </div>

      <!-- Produk Return -->
      <div class="w-full border border-gray-200 rounded-md overflow-hidden">
        <div class="grid grid-cols-12 bg-gray-100 text-xs font-medium text-gray-700 uppercase">
          <div class="col-span-1 p-2 text-center">No</div>
          <div class="col-span-1 p-2 text-center">SKU</div>
          <div class="col-span-3 p-2">Product</div>
          <div class="col-span-2 p-2 text-center">Quantity</div>
          <div class="col-span-2 p-2 text-center">Cost</div>
          <div class="col-span-2 p-2 text-right">Subtotal</div>
          <div class="col-span-1 p-2 text-center"></div>
        </div>

        <!-- Daftar produk -->
        <div id="return_items" class="divide-y divide-gray-100 max-h-[300px] overflow-y-auto">
          <!-- JS rows here -->
        </div>

        <!-- Tombol Add -->
        <div class="border-t border-gray-100 px-4 py-2">
          <button type="button" id="addReturnRow"
            class="text-green-600 text-sm hover:underline">+ Add Product</button>
        </div>
      </div>

      <!-- Total -->
      <div class="mt-4 flex justify-end text-lg font-bold text-gray-800 dark:text-white">
        <span class="mr-2">Total:</span>
        <span id="return_grand_total">Rp 0</span>
      </div>

      <!-- Tombol -->
      <div class="flex justify-end gap-3 mt-4">
        <button type="button" id="cancelReturnBtn"
          class="cancel-return-btn px-4 py-2 border rounded-md text-gray-700 dark:text-white border-gray-400 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition text-sm font-medium">
          Cancel
        </button>
        <button type="submit"
          class="px-6 py-2 rounded-md font-semibold bg-red-600 hover:bg-red-700 text-white transition text-sm">
          Confirm Return
        </button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL FILTER PURCHASE -->
<div id="purchaseFilterModal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center overflow-y-auto p-4">
  <div id="purchaseFilterContent" class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-3xl p-6 transform transition-all duration-300 scale-95 opacity-0">

    <!-- Modal Header -->
<div class="flex justify-between items-center mb-4">
  <h3 class="text-xl font-semibold text-gray-800 dark:text-white">Filter Purchases</h3>
  <button id="closeFilterModalBtn" class="text-gray-500 hover:text-red-600 dark:hover:text-red-400">
    <img src="../../assets/icons/lightmode/cancel-light.png" alt="Close" class="w-5 h-5 block dark:hidden">
    <img src="../../assets/icons/darkmode/cancel-dark.png" alt="Close" class="w-5 h-5 hidden dark:block">
  </button>
</div>

<hr class="border-t border-gray-200 dark:border-gray-600 mb-4" />

    <!-- Filter Fields -->
    <div class="grid grid-cols-2 gap-4 mb-4">
      <div>
        <label class="block mb-1 text-sm">Tanggal Mulai</label>
        <input type="date" id="filterStartDate" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white" />
      </div>
      <div>
        <label class="block mb-1 text-sm">Tanggal Selesai</label>
        <input type="date" id="filterEndDate" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white" />
      </div>
      <div>
        <label class="block mb-1 text-sm">Supplier</label>
        <select id="filterSupplier" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
          <option value="">-- Semua Supplier --</option>
        </select>
      </div>
      <div>
        <label class="block mb-1 text-sm">Status</label>
        <select id="filterStatus" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
          <option value="">-- Semua --</option>
          <option value="received">Received</option>
          <option value="ordered">Ordered</option>
          <option value="pending">Pending</option>
        </select>
      </div>
      <div>
        <label class="block mb-1 text-sm">Payment Status</label>
        <select id="filterPaymentStatus" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
          <option value="">-- Semua --</option>
          <option value="paid">Paid</option>
          <option value="partial">Partial</option>
          <option value="unpaid">Unpaid</option>
        </select>
      </div>
      <div>
        <label class="block mb-1 text-sm">Nomor Invoice</label>
        <select id="filterInvoice" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
          <option value="">-- Semua Invoice --</option>
        </select>
      </div>
      <div>
        <label class="block mb-1 text-sm">Grand Total (Min)</label>
        <input type="text" id="filterGrandMin" placeholder="Minimal"
          class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white" />
      </div>
      <div>
        <label class="block mb-1 text-sm">Grand Total (Max)</label>
        <input type="text" id="filterGrandMax" placeholder="Maksimal"
          class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white" />
      </div>
      <div>
        <label class="block mb-1 text-sm">Jumlah Item ≥</label>
        <input type="number" id="filterMinItems" min="1" placeholder="Contoh: 1"
          class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white" />
      </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-between mt-6">
      <button id="clearPurchaseFilter" class="border border-red-600 text-red-600 hover:bg-red-50 dark:hover:bg-red-900 px-4 py-2 rounded">
        Clear Filter
      </button>
      <button id="applyPurchaseFilter" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
        Apply Filter
      </button>
    </div>

  </div>
</div>


