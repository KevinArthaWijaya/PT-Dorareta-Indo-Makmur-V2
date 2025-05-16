document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("loginForm");
  const usernameInput = document.getElementById("username");
  const passwordInput = document.getElementById("password");
  const loadingOverlay = document.getElementById("loading");
  const rememberMeCheckbox = document.getElementById("remember_me");
  const togglePassword = document.getElementById("togglePassword");
  const alertBox = document.getElementById("alertBox");

  // Cek apakah cookie "remember_username" ada, jika ada, isi form dengan username
  const rememberMeCookie = document.cookie
    .split("; ")
    .find((row) => row.startsWith("remember_username="));

  if (rememberMeCookie) {
    const username = decodeURIComponent(rememberMeCookie.split("=")[1]);
    usernameInput.value = username;
  }

  form.addEventListener("submit", async function (event) {
    event.preventDefault();

    const username = usernameInput.value.trim();
    const password = passwordInput.value.trim();

    // Reset Alert
    alertBox.classList.add("hidden");
    alertBox.textContent = "";

    // Validasi input
    if (username === "" || password === "") {
      alertBox.textContent = "Username dan Password wajib diisi!";
      alertBox.classList.remove("hidden");
      return;
    }

    // Validasi panjang password (contoh: minimal 6 karakter)
    if (password.length < 6) {
      alertBox.textContent = "Password minimal 6 karakter!";
      alertBox.classList.remove("hidden");
      return;
    }

    // Tampilkan loading
    loadingOverlay.classList.remove("opacity-0", "invisible");

    try {
      // Kirim data login ke API
      const response = await fetch("../../backend/API/auth/index.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username, password }),
      });

      const data = await response.json();
      console.log(data);

      loadingOverlay.classList.add("opacity-0", "invisible");

      // Jika login berhasil
      if (data.status && data.data.redirect) {
        // Simpan username di cookie jika "Remember Me" dicentang
        if (rememberMeCheckbox.checked) {
          document.cookie = `remember_username=${encodeURIComponent(
            username
          )}; path=/; max-age=${7 * 24 * 60 * 60}`;
        } else {
          document.cookie =
            "remember_username=; path=/; expires=Thu, 01 Jan 1970 00:00:00 UTC;";
        }

        // Redirect ke halaman yang sesuai setelah login berhasil
        window.location.href = data.data.redirect;
      } else {
        // Tampilkan error dari API
        alertBox.textContent = data.message || "Login gagal.";
        alertBox.classList.remove("hidden");
      }
    } catch (error) {
      console.error("Login error:", error);
      loadingOverlay.classList.add("opacity-0", "invisible");

      alertBox.textContent = "Server tidak merespon atau koneksi terputus.";
      alertBox.classList.remove("hidden");
    }
  });

  // Toggle show/hide password
  if (togglePassword) {
    togglePassword.addEventListener("click", function () {
      if (passwordInput.type === "password") {
        passwordInput.type = "text";
        this.src = "../../assets/icons/lightmode/show-password-light.png";
      } else {
        passwordInput.type = "password";
        this.src = "../../assets/icons/lightmode/hide-password-light.png";
      }
    });
  }
});
