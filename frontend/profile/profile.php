<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../frontend/auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - PT. Dorareta Indo Makmur</title>
    <link href="../../src/output.css" rel="stylesheet">
    <link href="../../frontend/css/header.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"> <!-- Tambahkan SweetAlert2 CSS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Tambahkan SweetAlert2 JS -->
    <script src="../js/header.js" defer></script>
    <script src="../js/profile.js" defer></script>
    <script src="../js/logout.js" defer></script>
</head>

<body class="bg-gray-100 dark:bg-gray-900">
<?php include '../partials/header.php'; ?>

<main class="max-w-6xl mx-auto px-4 sm:px-6 py-28">
    <!-- Breadcrumb -->
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">My Profile</h2>
        <span class="text-sm text-gray-500 dark:text-gray-400">
            <a href="../../frontend/dashboard/dashboard.php" class="text-gray-600 hover:underline dark:text-gray-300">Dashboard</a> 
            <span class="mx-2 text-gray-400 dark:text-gray-500">/</span>
            <span class="text-red-500 dark:text-red-400">My Profile</span>
        </span>
    </div>

    <div class="h-px bg-gray-300 mb-6 dark:bg-gray-700"></div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <!-- Left Section: Avatar -->
        <div class="flex flex-col items-center gap-4">
            <div id="avatarContainer" class="relative w-56 h-56 overflow-hidden rounded-full border-2 border-gray-300 dark:border-gray-700">
                <img id="previewImageLight" src="../../assets/image/default-user-lightmode.png" class="w-full h-full object-cover block dark:hidden rounded-full">
                <img id="previewImageDark" src="../../assets/image/default-user-darkmode.png" class="w-full h-full object-cover hidden dark:block rounded-full">
                <div id="uploadOverlay" class="absolute inset-0 flex items-center justify-center opacity-0 pointer-events-none transition duration-300 group-hover:opacity-100">
                    <div class="absolute inset-0 backdrop-blur-sm rounded-full"></div>
                    <img src="../../assets/icons/lightmode/image-light.png" class="w-14 h-14 z-10 transform transition-transform duration-300 group-hover:scale-110 block dark:hidden" alt="Upload Light">
                    <img src="../../assets/icons/darkmode/image-dark.png" class="w-14 h-14 z-10 transform transition-transform duration-300 hidden dark:block" alt="Upload Dark">
                </div>
                <input type="file" id="profileImageInput" name="profile_image" accept="image/*" class="hidden">
            </div>
            <label class="text-gray-600 dark:text-gray-300 text-sm">Upload Profile Picture</label>
        </div>

        <!-- Right Section: Form -->
        <div class="md:col-span-2">
            <form id="profileForm" class="space-y-6" enctype="multipart/form-data">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">First Name</label>
                        <input type="text" name="first_name" value="" disabled class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 p-2 text-gray-900 dark:text-white focus:border-gray-400 dark:focus:border-gray-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Last Name</label>
                        <input type="text" name="last_name" value="" disabled class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 p-2 text-gray-900 dark:text-white focus:border-gray-400 dark:focus:border-gray-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Email</label>
                        <input type="email" name="email" value="" disabled class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 p-2 text-gray-900 dark:text-white focus:border-gray-400 dark:focus:border-gray-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Phone Number</label>
                        <div class="flex">
                            <span class="flex items-center px-3 rounded-l-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200">+62</span>
                            <input type="text" name="phone_number" value="" disabled class="w-full rounded-r-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 p-2 text-gray-900 dark:text-white focus:border-gray-400 dark:focus:border-gray-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Username</label>
                        <input type="text" name="username" value="" disabled class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 p-2 text-gray-900 dark:text-white focus:border-gray-400 dark:focus:border-gray-500 focus:outline-none">
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Password</label>
                        <input type="password" name="password" id="passwordInput" value="********" disabled class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 p-2 text-gray-900 dark:text-white focus:border-gray-400 dark:focus:border-gray-500 focus:outline-none">
                        <button type="button" id="togglePassword" class="absolute right-3 top-9">
                            <img src="../../assets/icons/lightmode/hide-password-light.png" id="eyeIcon" class="w-5 h-5">
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Bio</label>
                    <textarea name="bio" rows="4" disabled class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 p-2 text-gray-900 dark:text-white focus:border-gray-400 dark:focus:border-gray-500 focus:outline-none"></textarea>
                </div>

                <div class="flex flex-wrap gap-4 mt-6">
                    <button type="button" id="editBtn" class="px-6 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition">Edit Profile</button>
                    <button type="button" id="backBtn" class="px-6 py-2 border border-gray-500 text-gray-700 dark:text-gray-300 dark:border-gray-400 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition">Back</button>
                </div>
            </form>
        </div>
    </div>
</main>

</body>
</html>
