<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT. Dorareta Indo Makmur - Login</title>
    <link href="../../src/output.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <script src="../js/login.js" defer></script>
</head>

<body class="min-h-screen bg-gray-100 flex items-center justify-center">

    <!-- Loading overlay -->
    <div id="loading" class="fixed inset-0 bg-white bg-opacity-80 flex items-center justify-center opacity-0 invisible transition-opacity duration-300 z-50">
        <div class="w-12 h-12 border-4 border-gray-300 border-t-black rounded-full animate-spin"></div>
    </div>

    <div class="flex w-4/5 max-w-5xl bg-white rounded-lg overflow-hidden shadow-lg">
        <!-- Kiri (Gambar) -->
        <div class="hidden md:flex w-1/2 bg-gray-200 items-center justify-center">
            <img src="../../assets/icons/banner.png" alt="Login Banner" class="w-full h-full object-cover">
        </div>

        <!-- Kanan (Form Login) -->
        <div class="w-full md:w-1/2 flex flex-col items-center justify-center p-8">
            <div class="w-full max-w-md">
                <div class="text-center mb-6">
                    <img src="../../assets/icons/logo_dim.png" alt="Logo" class="mx-auto w-48 md:w-56 mb-6">
                    <hr class="border-t border-gray-300 my-4">
                </div>

                    <!-- Form Login -->
                    <!-- Alert Box -->
                    <div id="alertBox" class="hidden bg-red-100 text-red-600 font-bold text-center p-2 rounded mb-4"></div>

                    <!-- Form Login -->
                    <form id="loginForm" method="POST" action="../../backend/API/auth/index.php" class="space-y-4">
                        <input type="text" name="username" id="username" autocomplete="username"
                            placeholder="Username"
                            class="w-full border rounded-md p-2 text-base focus:outline-none focus:ring-2 focus:ring-indigo-300">

                        <div class="relative">
                            <input type="password" name="password" id="password" autocomplete="current-password"
                                placeholder="Password"
                                class="w-full border rounded-md p-2 pr-10 text-base focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            <img src="../../assets/icons/lightmode/hide-password-light.png" id="togglePassword"
                                class="absolute top-2 right-2 w-6 h-6 cursor-pointer" alt="Show/Hide Password">
                        </div>

                        <div class="flex items-center space-x-2">
                            <input type="checkbox" name="remember_me" id="remember_me" class="w-4 h-4">
                            <label for="remember_me" class="text-sm">Remember Me</label>
                        </div>

                        <button type="submit"
                            class="w-full bg-black text-white font-bold py-2 rounded-md hover:bg-red-600 transition-colors duration-300">
                            Login
                        </button>
                    </form>
            </div>
        </div>
    </div>
</body>
</html>
