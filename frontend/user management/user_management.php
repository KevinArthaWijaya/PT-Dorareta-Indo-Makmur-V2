<?php
session_start();
$role = $_SESSION['role'] ?? 'Guest';
require '../../backend/config.php';

$allowed_roles = ['Admin', 'Manager'];

if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Management - PT. Dorareta Indo Makmur</title>
  <link href="../../src/output.css" rel="stylesheet" />
  <link href="../css/header.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
  <link href="../css/modal_user_form.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../js/user_management.js" defer></script>
  <script src="../js/header.js" defer></script>
  <script src="../js/logout.js" defer></script>
  <script>
  window.userRole = "<?= $_SESSION['role'] ?>";
</script>
</head>

<body class="bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-white min-h-screen">
<?php include '../partials/header.php'; ?>

<main class="px-4 pt-20 pb-10 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
  <!-- Title & Breadcrumb -->
  <div class="w-full">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-2">
      <h2 class="text-2xl font-semibold tracking-tight text-gray-800 dark:text-white">User Management</h2>
      <nav class="text-sm text-gray-500 dark:text-gray-400">
        <a href="../dashboard/dashboard.php" class="hover:underline text-gray-600 dark:text-gray-300">Dashboard</a>
        <span class="mx-2 text-gray-400">/</span>
        <span class="text-red-500 font-semibold dark:text-red-400">User Management</span>
      </nav>
    </div>

    <div class="h-px bg-gray-200 dark:bg-gray-700 mb-6"></div>

    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
<div class="relative w-full sm:max-w-xs">
  <input id="searchInput" type="text" placeholder="Search on this table"
    class="w-full pl-4 pr-10 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-1 focus:ring-gray-800 text-sm text-gray-800 dark:bg-gray-800 dark:text-white dark:border-gray-600" />

  <!-- Icon Lightmode -->
  <img src="../../assets/icons/lightmode/search-light.png"
    class="w-5 h-5 absolute right-3 top-2.5 opacity-60 pointer-events-none block dark:hidden" />

  <!-- Icon Darkmode -->
  <img src="../../assets/icons/darkmode/search-dark.png"
    class="w-5 h-5 absolute right-3 top-2.5 opacity-60 pointer-events-none hidden dark:block" />
</div>

      <button id="openUserModal"
        class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-all">
        + Create User
      </button>
    </div>

<!-- Table -->
<div class="overflow-x-auto shadow rounded-t-xl">
  <table class="min-w-full table-auto text-sm">
    <thead class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs tracking-wider">
      <tr>
  <th class="px-4 py-3 text-left cursor-pointer sortable" data-column="first_name">
    First Name
    <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
    <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
  </th>
  <th class="px-4 py-3 text-left cursor-pointer sortable" data-column="last_name">
    Last Name
    <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
    <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
  </th>
  <th class="px-4 py-3 text-left cursor-pointer sortable" data-column="email">
    Email
    <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
    <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
  </th>
  <th class="px-4 py-3 text-left cursor-pointer sortable" data-column="phone_number">
    Phone
    <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
    <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
  </th>
  <th class="px-4 py-3 text-left cursor-pointer sortable" data-column="role_name">
    Role
    <img src="../../assets/icons/lightmode/sort-alt-light.png" class="sort-icon-light w-3 h-3 inline-block ml-1 dark:hidden" />
    <img src="../../assets/icons/darkmode/sort-alt-dark.png" class="sort-icon-dark w-3 h-3 hidden ml-1 dark:inline-block" />
  </th>
  <th class="px-4 py-3 text-center cursor-pointer sortable" data-column="hire_date">
    Working Period
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
    <tbody id="userTableBody" class="divide-y divide-gray-200 dark:divide-gray-700">
      <!-- Data user akan dimuat via JavaScript -->
    </tbody>
  </table>
</div>

<!-- Pagination -->
<div class="mt-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
  <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
    <span>Showing</span>
    <div class="relative">
      <select id="limitSelect"
        class="bg-transparent appearance-none text-sm px-2 pr-8 py-1 rounded focus:outline-none text-gray-800 dark:text-white [&>option]:text-black">
        <option value="10" selected>10</option>
        <option value="20">20</option>
        <option value="30">30</option>
      </select>
      <!-- Icon Lightmode -->
      <img src="../../assets/icons/lightmode/arrow-down-light.png"
        class="absolute right-2 top-1/2 w-4 h-4 transform -translate-y-1/2 pointer-events-none opacity-60 block dark:hidden" />
      <!-- Icon Darkmode -->
      <img src="../../assets/icons/darkmode/arrow-down-dark.png"
        class="absolute right-2 top-1/2 w-4 h-4 transform -translate-y-1/2 pointer-events-none opacity-60 hidden dark:block" />
    </div>
    <span>rows per page</span>
  </div>
  <div id="paginationControls" class="flex gap-2 text-sm"></div>
</div>

  </div>
</main>

<!-- Modal Form -->
<div id="userFormModal" class="fixed inset-0 z-[1050] hidden bg-black/50 backdrop-blur-sm items-center justify-center overflow-y-auto transition-opacity duration-300">
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-4xl p-6 relative transform scale-95 opacity-0 transition-all duration-300 ease-out"
      id="userFormModalContent">

    <!-- Modal Header -->
    <div class="flex justify-between items-center mb-4">
      <h3 id="modalTitle" class="text-xl font-bold text-gray-800 dark:text-white">Create User</h3>
      <button id="closeUserFormModal" class="text-gray-500 hover:text-red-500 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
          viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <div class="border-t border-gray-300 dark:border-gray-600 mb-6"></div>

    <form id="userForm" class="grid grid-cols-1 md:grid-cols-2 gap-4" enctype="multipart/form-data">
  <input type="hidden" name="user_id" id="user_id" />

  <!-- First Name -->
  <div>
    <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">First Name</label>
    <input type="text" name="first_name" id="first_name" required
      class="w-full rounded-md border border-gray-400 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white placeholder-gray-500 focus:ring-1 focus:ring-gray-400 focus:outline-none p-2" />
  </div>

  <!-- Last Name -->
  <div>
    <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Last Name</label>
    <input type="text" name="last_name" id="last_name"
      class="w-full rounded-md border border-gray-400 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white placeholder-gray-500 focus:ring-1 focus:ring-gray-400 focus:outline-none p-2" />
  </div>

  <!-- Email -->
  <div>
    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
    <input type="email" name="email" id="email" required
      class="w-full rounded-md border border-gray-400 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white placeholder-gray-500 focus:ring-1 focus:ring-gray-400 focus:outline-none p-2" />
  </div>

  <!-- Phone Number -->
  <div>
    <label for="phone_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone Number</label>
    <div class="flex rounded-md shadow-sm">
      <span class="inline-flex items-center px-3 rounded-l-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">+62</span>
      <input type="text" name="phone_number" id="phone_number" placeholder="81234567890"
        class="w-full rounded-r-md border border-gray-400 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white placeholder-gray-500 focus:ring-1 focus:ring-gray-400 focus:outline-none p-2" />
    </div>
  </div>

  <!-- Username -->
  <div>
    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username</label>
    <input type="text" name="username" id="username" required
      class="w-full rounded-md border border-gray-400 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white placeholder-gray-500 focus:ring-1 focus:ring-gray-400 focus:outline-none p-2" />
  </div>

  <!-- Password -->
  <div>
    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
    <input type="password" name="password" id="password"
      class="w-full rounded-md border border-gray-400 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white placeholder-gray-500 focus:ring-1 focus:ring-gray-400 focus:outline-none p-2" />
  </div>

  <!-- Role -->
  <div>
    <label for="role_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role</label>
    <select name="role_id" id="role_id" required
      class="w-full rounded-md border border-gray-400 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white p-2 focus:ring-1 focus:ring-gray-400 focus:outline-none">
      <option value="">Select Role</option>
    </select>
  </div>

  <!-- Status (hidden, only for Edit) -->
  <div class="hidden">
    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
    <select name="status" id="status"
      class="w-full rounded-md border border-gray-400 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white p-2 focus:ring-1 focus:ring-gray-400 focus:outline-none">
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
    </select>
  </div>

  <!-- Hire Date -->
  <div>
    <label for="hire_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hire Date</label>
    <input type="text" name="hire_date" id="hire_date" required placeholder="yyyy-mm-dd"
      class="w-full rounded-md border border-gray-400 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white placeholder-gray-500 focus:ring-1 focus:ring-gray-400 focus:outline-none p-2" />
  </div>

  <!-- Bio -->
  <div class="md:col-span-2">
    <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bio</label>
    <textarea name="bio" id="bio" rows="3"
      class="w-full rounded-md border border-gray-400 dark:border-gray-600 bg-white dark:bg-gray-700 text-black dark:text-white placeholder-gray-500 focus:ring-1 focus:ring-gray-400 focus:outline-none p-2 resize-y max-h-32"></textarea>
  </div>

  <!-- Action Buttons -->
  <div class="md:col-span-2 flex justify-end gap-3 mt-4">
    <button type="button" id="cancelModalBtn"
      class="px-4 py-2 rounded-md border border-gray-400 text-gray-700 dark:text-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
      Cancel
    </button>
    <button type="submit" id="submitUserBtn"
      class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
      Save
    </button>
  </div>
</form>
  </div>
</div>
</body>
</html>
