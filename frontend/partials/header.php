<?php
$defaultLight = "../../assets/image/default-user-lightmode.png";
$defaultDark = "../../assets/image/default-user-darkmode.png";

require_once '../../backend/function.php';
?>

<header class="w-full bg-white dark:bg-gray-800 shadow-md fixed top-0 z-50 px-4 sm:px-6 py-2 sm:py-3 flex justify-between items-center">
    <!-- Logo -->
    <div class="logo-container flex items-center">
        <a href="../../frontend/dashboard/dashboard.php">
            <img src="../../assets/icons/logo_dim.png" alt="Company Logo" class="h-8 sm:h-12 cursor-pointer">
        </a>
    </div>

    <!-- Mobile Header Right Section -->
    <div class="flex items-center gap-2 sm:hidden">
        <!-- Avatar Only -->
        <div class="user-avatar w-8 h-8 rounded-md border-2 border-black dark:border-white overflow-hidden bg-white dark:bg-gray-700 flex items-center justify-center">
            <img id="avatarMobile" src="<?= $defaultLight ?>" class="w-full h-full object-cover block dark:hidden">
            <img id="avatarMobileDark" src="<?= $defaultDark ?>" class="w-full h-full object-cover hidden dark:block">
        </div>

        <!-- Hamburger -->
        <button id="hamburgerBtn" class="block sm:hidden p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
            <div class="hamburger-line w-5 h-0.5 bg-black dark:bg-white mb-1"></div>
            <div class="hamburger-line w-5 h-0.5 bg-black dark:bg-white mb-1"></div>
            <div class="hamburger-line w-5 h-0.5 bg-black dark:bg-white"></div>
        </button>
    </div>

    <!-- Desktop Header Right -->
    <div class="hidden sm:flex items-center gap-5">
        <!-- User Info -->
        <div class="user-info-container relative">
            <div class="user-info flex items-center gap-3 cursor-pointer" id="userDropdown">
                <div class="user-avatar w-10 h-10 rounded-md border-2 border-black dark:border-white overflow-hidden bg-white dark:bg-gray-700 flex items-center justify-center">
                    <img id="avatarDesktop" src="<?= $defaultLight ?>" class="w-full h-full object-cover block dark:hidden">
                    <img id="avatarDesktopDark" src="<?= $defaultDark ?>" class="w-full h-full object-cover hidden dark:block">
                </div>
                <div class="user-meta flex flex-col">
                    <span class="user-name text-base font-semibold leading-5 dark:text-white" id="headerFullName">User</span>
                    <span class="user-role text-xs text-gray-400 dark:text-gray-300" id="headerRole">Guest</span>
                </div>
            </div>

            <!-- Dropdown Menu -->
            <div id="userDropdownMenu" class="user-dropdown-menu absolute top-14 left-0 bg-white dark:bg-gray-700 shadow-lg rounded-lg w-48 sm:w-full z-50 opacity-0 scale-95 pointer-events-none transition-all duration-300 ease-out hidden">
                <a href="../profile/profile.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:text-red-500 transform hover:translate-x-1 transition-all duration-200">My Profile</a>
                <hr class="border-t border-gray-300 dark:border-gray-600 mx-2" />
                <a href="#" id="logoutBtn" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:text-red-500 transform hover:translate-x-1 transition-all duration-200">Logout</a>
            </div>
        </div>

        <!-- Settings -->
        <button class="setting-btn p-2 rounded-md bg-red-500 hover:bg-red-600 text-white" id="gearBtn" title="Pengaturan">
            <img src="../../assets/icons/setting.e8120a9a.svg" class="h-6">
        </button>
    </div>
</header>

<!-- Side Menu -->
<div class="side-menu fixed top-0 right-[-300px] w-60 sm:w-64 h-full bg-white dark:bg-gray-800 shadow-xl transition-all duration-300 z-50 px-4 py-6" id="sideMenu">
    <div class="side-menu-header flex justify-between items-center">
        <span class="font-semibold text-base sm:text-lg dark:text-white">
            <span class="block sm:hidden">Menu</span>
            <span class="hidden sm:block">Menu</span>
        </span>
        <button class="close-menu-btn text-xl dark:text-white" id="closeMenuBtn">✕</button>
    </div>

    <ul class="side-menu-list mt-4 space-y-3">
        <!-- Dark Mode Toggle -->
        <li>
            <div class="flex items-center justify-between p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-all duration-200 active:scale-95">
                <div class="flex items-center gap-3">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <span>🌙</span>
                    </div>
                    <span class="text-sm dark:text-white">Dark Mode</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="darkModeToggle" class="sr-only peer">
                    <div class="w-9 h-5 sm:w-11 sm:h-6 bg-gray-300 rounded-full peer peer-checked:bg-black dark:peer-checked:bg-white"></div>
                    <div class="absolute bg-white peer-checked:bg-black rounded-full transition-all 
                        w-4 h-4 top-[2px] left-[2px] peer-checked:translate-x-4 
                        sm:w-4 sm:h-4 sm:top-1 sm:left-1 sm:peer-checked:translate-x-5">
                    </div>
                </label>
            </div>
        </li>

        <!-- My Profile (Mobile Only) -->
        <li class="sm:hidden">
            <a href="../profile/profile.php" class="flex items-center gap-3 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded dark:text-white transition-all duration-200 active:scale-95">
                <div class="w-5 h-5 flex items-center justify-center">
                    <img src="../../assets/icons/my profile.png" alt="Profile" class="w-5 h-5 object-contain">
                </div>
                <span class="text-sm">My Profile</span>
            </a>
        </li>

        <!-- Error Logs -->
        <?php if (in_array('admin', $permissions)): ?>
        <li>
            <a href="../partials/error_logs.php" class="flex items-center gap-3 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded dark:text-white transition-all duration-200 active:scale-95">
                <div class="w-5 h-5 flex items-center justify-center">
                    <img src="../../assets/icons/error-log.png" alt="Error Log" class="w-5 h-5 object-contain">
                </div>
                <span class="text-sm">Error Logs</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Master Data (Dropdown Menu) -->
        <?php if (in_array('allrole', $permissions)): ?>
        <li>
        <div
            id="masterDataToggle"
            class="flex items-center justify-between p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer transition-all duration-200 active:scale-95"
            role="button"
            aria-expanded="false"
            aria-controls="masterDataSubmenu"
            tabindex="0"
        >
            <div class="flex items-center gap-3">
            <div class="w-5 h-5 flex items-center justify-center">
                <img
                src="../../assets/icons/master-data.png"
                alt="Master Data"
                class="w-5 h-5 object-contain"
                />
            </div>
            <span class="text-sm dark:text-white">Master Data</span>
            </div>
            <div class="w-4 h-4 flex items-center justify-center">
            <img
                id="chevronDownLight"
                src="../../assets/icons/lightmode/arrow-down-light.png"
                class="h-4 block dark:hidden transition-transform"
                alt="Toggle Dropdown"
            />
            <img
                id="chevronDownDark"
                src="../../assets/icons/darkmode/arrow-down-dark.png"
                class="h-4 hidden dark:block transition-transform"
                alt="Toggle Dropdown"
            />
            </div>
        </div>
        <?php endif; ?>

        <ul
            id="masterDataSubmenu"
            class="ml-6 mt-1 space-y-1 pl-3 border-l border-gray-300 dark:border-gray-600 hidden"
            role="menu"
            aria-label="Master Data Submenu">

        <?php if (in_array('master_data_customer', $permissions)): ?>
            <li role="none">
            <a
                href="../customer/customer.php"
                class="group flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-red-500 px-2 py-1.5 rounded-md transition-all duration-200"
                role="menuitem"
                tabindex="-1"
            >
                <span
                class="w-1.5 h-1.5 bg-red-500 rounded-full group-hover:scale-125 transition"
                aria-hidden="true"
                ></span>
                <span>Customers</span>
            </a>
            </li>
            <?php endif; ?>

            <?php if (in_array('master_data_supplier', $permissions)): ?>
            <li role="none">
            <a
                href="../supplier/supplier.php"
                class="group flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-red-500 px-2 py-1.5 rounded-md transition-all duration-200"
                role="menuitem"
                tabindex="-1"
            >
                <span
                class="w-1.5 h-1.5 bg-red-500 rounded-full group-hover:scale-125 transition"
                aria-hidden="true"
                ></span>
                <span>Suppliers</span>
            </a>
            </li>
            <?php endif; ?>

            <?php if (in_array('master_data_supplier', $permissions)): ?>
            <li role="none">
            <a
                href="../partials/category.php"
                class="group flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-red-500 px-2 py-1.5 rounded-md transition-all duration-200"
                role="menuitem"
                tabindex="-1"
            >
                <span
                class="w-1.5 h-1.5 bg-red-500 rounded-full group-hover:scale-125 transition"
                aria-hidden="true"
                ></span>
                <span>Category</span>
            </a>
            </li>
            <?php endif; ?>
        </ul>
        </li>

        <!-- Download Template -->
<li>
  <a
    href="../../assets/template/Template_import_product.xlsx"
    download
    class="flex items-center gap-3 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded dark:text-white transition-all duration-200 active:scale-95"
  >
    <div class="w-5 h-5 flex items-center justify-center">
      <img
        src="../../assets/icons/template_excel.png"
        alt="Template Excel"
        class="w-5 h-5 object-contain"
      />
    </div>
    <span class="text-sm">Download Template</span>
  </a>
</li>

        <!-- Logout (Mobile Only) -->
        <li class="sm:hidden">
            <a href="#" id="logoutBtnMobile" class="flex items-center gap-3 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded dark:text-white transition-all duration-200 active:scale-95">
                <div class="w-5 h-5 flex items-center justify-center">
                    <img src="../../assets/icons/logout.png" alt="Logout" class="w-5 h-5 object-contain">
                </div>
                <span class="text-sm">Logout</span>
            </a>
        </li>
    </ul>
</div>