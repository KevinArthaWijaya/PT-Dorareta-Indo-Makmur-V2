<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../auth/login.php");
    exit();
}

$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];
$avatar = (isset($_SESSION['profile_image']) && file_exists($_SESSION['profile_image']))
    ? $_SESSION['profile_image']
    : '../../assets/icons/user.png';

    require_once '../../backend/function.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard - PT. Dorareta Indo Makmur</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="../../src/output.css" rel="stylesheet" />
  <link href="../css/header.css" rel="stylesheet" />
  <script src="../js/header.js" defer></script>
  <script src="../js/logout.js" defer></script>
  <script src="../js/dashboard.js" defer></script>
</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen">
  <?php include '../partials/header.php'; ?>

  <main class="px-4 pt-20 pb-1  0 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="w-full mb-8">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-2">
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Dashboard</h2>
        <div class="text-sm text-gray-500 dark:text-gray-300">
          <a href="../dashboard/dashboard.php" class="text-gray-600 dark:text-gray-200 font-medium hover:underline">Dashboard</a>
        </div>
      </div>
      <hr class="mt-3 border-t border-gray-200 dark:border-gray-700" />
    </div>

    <!-- Menu Utama -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-10">
      <?php if (in_array('logistic', $permissions)): ?>
        <a href="../products/products.php" class="flex flex-col items-center p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition">
          <img src="../../assets/icons/lightmode/product-light.png" class="w-10 h-10 mb-2 block dark:hidden" />
          <img src="../../assets/icons/darkmode/product-dark.png" class="w-10 h-10 mb-2 hidden dark:block" />
          <span class="font-semibold text-gray-800 dark:text-white">Products</span>
        </a>
      <?php endif; ?>

      <?php if (in_array('logistic', $permissions)): ?>
        <a href="../purchase/purchase.php" class="flex flex-col items-center p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition">
          <img src="../../assets/icons/lightmode/purchase-light.png" class="w-10 h-10 mb-2 block dark:hidden" />
          <img src="../../assets/icons/darkmode/purchase-dark.png" class="w-10 h-10 mb-2 hidden dark:block" />
          <span class="font-semibold text-gray-800 dark:text-white">Purchase</span>
        </a>
      <?php endif; ?>

      <?php if (in_array('sales', $permissions)): ?>
        <a href="../sales/sales.php" class="flex flex-col items-center p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition">
          <img src="../../assets/icons/lightmode/sale-light.png" class="w-10 h-10 mb-2 block dark:hidden" />
          <img src="../../assets/icons/darkmode/sale-dark.png" class="w-10 h-10 mb-2 hidden dark:block" />
          <span class="font-semibold text-gray-800 dark:text-white">Sales</span>
        </a>
      <?php endif; ?>

      <?php if (hasAnyReportAccess($permissions)) : ?>
        <div class="relative" id="reportWrapper">
          <button id="toggleReportDropdown" type="button" class="flex flex-col items-center p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition w-full">
            <img src="../../assets/icons/lightmode/report-light.png" class="w-10 h-10 mb-2 block dark:hidden" />
            <img src="../../assets/icons/darkmode/report-dark.png" class="w-10 h-10 mb-2 hidden dark:block" />
            <span class="font-semibold text-gray-800 dark:text-white">Reports</span>
          </button>
          <div id="reportDropdown" class="absolute top-full left-0 mt-2 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl ring-1 ring-black/5 hidden flex-col z-50">
    <?php if (in_array('sales', $permissions)): ?>
    <a href="../report/report-sales.php" class="px-4 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700">📈 Sales Report</a>
    <?php endif; ?>

    <?php if (in_array('logistic', $permissions)): ?>
    <a href="../report/report-purchase.php" class="px-4 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700">🧾 Purchase Report</a>
    <a href="../report/report-supplier.php" class="px-4 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700">🏢 Supplier Report</a>
    <a href="../report/report-return.php" class="px-4 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700">♻️ Return Report</a>
    <a href="../report/report-stock.php" class="px-4 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700">📦 Stock Report</a>
    <?php endif; ?>

    <?php if (in_array('sales', $permissions)): ?>
    <a href="../report/report-top-products.php" class="px-4 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700">⭐ Top Products</a>
    <a href="../report/report-customer.php" class="px-4 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700">👤 Customer Report</a>
    <?php endif; ?>
</div>
        </div>
        <?php endif; ?>
        

      <?php if (in_array('manager', $permissions)): ?>
        <a href="../user management/user_management.php" class="flex flex-col items-center p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition">
          <img src="../../assets/icons/lightmode/user-management-light.png" class="w-10 h-10 mb-2 block dark:hidden" />
          <img src="../../assets/icons/darkmode/user-management-dark.png" class="w-10 h-10 mb-2 hidden dark:block" />
          <span class="font-semibold text-gray-800 dark:text-white">User Management</span>
        </a>
      <?php endif; ?>
    </div>

        <!-- 📊 STATISTIC CARDS -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <?php if (in_array('allrole', $permissions)): ?>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow flex flex-col">
          <div class="text-sm font-medium text-gray-500 dark:text-gray-300">Total Produk</div>
          <div id="totalProducts" class="text-2xl font-bold text-gray-800 dark:text-white">0</div>
        </div>
        <?php endif; ?>

        <?php if (in_array('logistic', $permissions)): ?>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow flex flex-col">
          <div class="text-sm font-medium text-gray-500 dark:text-gray-300">Total Pembelian</div>
          <div id="totalPurchases" class="text-2xl font-bold text-gray-800 dark:text-white">Rp 0</div>
        </div>
        <?php endif; ?>

        <?php if (in_array('sales', $permissions)): ?>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow flex flex-col">
          <div class="text-sm font-medium text-gray-500 dark:text-gray-300">Total Penjualan</div>
          <div id="totalSales" class="text-2xl font-bold text-gray-800 dark:text-white">Rp 0</div>
        </div>
        <?php endif; ?>

        <?php if (in_array('allrole', $permissions)): ?>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow flex flex-col">
          <div class="text-sm font-medium text-gray-500 dark:text-gray-300">Produk Habis</div>
          <div id="outOfStock" class="text-2xl font-bold text-red-600 dark:text-red-400">0</div>
        </div>
        <?php endif; ?>
    </div>

<!-- CHART SECTION -->
<div class="grid grid-cols-1 <?= $role === 'Logistic' ? 'md:grid-cols-2' : 'md:grid-cols-2 lg:grid-cols-3' ?> gap-6 mb-10">
  <?php if ($role === 'Logistic'): ?>
    <!-- ♻️ Top Returned Products -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow hover:shadow-lg transition-all">
      <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">♻️ Top Returned Products</h3>
      <canvas id="topReturnChart" height="220"></canvas>
    </div>

    <!-- 🏆 Top Selling Products -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow hover:shadow-lg transition-all">
      <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">🏆 Top Selling Products</h3>
      <canvas id="topProductsChart" height="220"></canvas>
    </div>
  <?php else: ?>
    <?php if (in_array('sales', $permissions)): ?>
      <!-- 📈 Weekly Chart -->
      <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow hover:shadow-lg transition-all">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">📈 Weekly Sales & Purchases</h3>
        <canvas id="weeklyChart" height="320"></canvas>
      </div>
    <?php endif; ?>

    <?php if (in_array('allrole', $permissions)): ?>
      <!-- 🏆 Top Selling Products -->
      <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow hover:shadow-lg transition-all">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">🏆 Top Selling Products</h3>
        <canvas id="topProductsChart" height="220"></canvas>
      </div>
    <?php endif; ?>

    <?php if (in_array('sales', $permissions)): ?>
      <!-- 🎯 Sales Target -->
      <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow hover:shadow-lg transition-all">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">🎯 Sales Target (This Month)</h3>
        <canvas id="salesTargetChart" height="220"></canvas>
        <p id="salesTargetLabel" class="mt-4 text-center text-sm text-gray-600 dark:text-gray-300"></p>
        <div class="mt-4 text-center" id="addTargetBtnWrapper">
          <?php if (in_array('manager', $permissions)): ?>
            <button id="addTargetBtn" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm font-medium">
              🎯 Add Target
            </button>
          <?php endif; ?>
        </div>
        <div class="mt-4 text-center hidden" id="targetFormWrapper">
          <form id="targetForm" class="inline-flex gap-2 items-center justify-center">
            <input type="number" id="targetInput" placeholder="Masukkan Target Baru"
              class="px-3 py-1 rounded border dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-white w-44" />
            <button type="submit"
              class="px-4 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded text-sm font-medium">
              Save
            </button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<!-- CHART LANJUTAN -->
<?php if ($role === 'Logistic'): ?>
  <div class="grid grid-cols-1 gap-6 mb-10">
    <!-- 🔔 Stock Alert full width -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
      <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Stock Alert</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b dark:border-gray-700">
              <th class="text-left py-2 px-3">SKU</th>
              <th class="text-left py-2 px-3">Product</th>
              <th class="text-left py-2 px-3">Stock</th>
              <th class="text-left py-2 px-3">Min Stock</th>
            </tr>
          </thead>
          <tbody id="stockAlertTable" class="divide-y dark:divide-gray-700 text-gray-800 dark:text-white">
            <!-- JS populate -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
    <?php if (in_array('sales', $permissions)): ?>
      <!-- 👥 Top Customers -->
      <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow hover:shadow-lg transition-all">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">👥 Top 5 Customers</h3>
        <canvas id="topCustomersChart" height="220"></canvas>
      </div>
    <?php endif; ?>

    <!-- ♻️ Top Returned Products (non-logistic only) -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow hover:shadow-lg transition-all">
      <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">♻️ Top Returned Products</h3>
      <canvas id="topReturnChart" height="220"></canvas>
    </div>
  </div>
<?php endif; ?>
</div>


<!-- TABEL: Stock Alert & Recent Invoice -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
  <?php if ($role !== 'Logistic'): ?>
    <!-- 🔔 Stock Alert (hanya selain Logistic) -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
      <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Stock Alert</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b dark:border-gray-700">
              <th class="text-left py-2 px-3">SKU</th>
              <th class="text-left py-2 px-3">Product</th>
              <th class="text-left py-2 px-3">Stock</th>
              <th class="text-left py-2 px-3">Min Stock</th>
            </tr>
          </thead>
          <tbody id="stockAlertTable" class="divide-y dark:divide-gray-700 text-gray-800 dark:text-white">
            <!-- JS will populate -->
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <?php if (in_array('sales', $permissions)): ?>
    <!-- 🧾 Recent Invoices -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
      <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Recent Invoices</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b dark:border-gray-700">
              <th class="py-2 px-3 text-left">Invoice</th>
              <th class="py-2 px-3 text-left">Customer</th>
              <th class="py-2 px-3 text-left">Total</th>
              <th class="py-2 px-3 text-left">Status</th>
            </tr>
          </thead>
          <tbody id="invoiceTable" class="divide-y dark:divide-gray-700 text-gray-800 dark:text-white">
            <!-- JS will populate -->
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>


  </main>
</body>
</html>
