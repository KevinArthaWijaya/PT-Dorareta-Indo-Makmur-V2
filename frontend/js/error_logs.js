document.addEventListener("DOMContentLoaded", () => {
  const loginForm = document.getElementById("loginForm");
  const usernameInput = document.getElementById("username");
  const passwordInput = document.getElementById("password");

  loginForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const username = usernameInput.value.trim();
    const password = passwordInput.value;

    try {
      const res = await fetch("../../backend/api/auth/index.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ username, password }),
        credentials: "include",
      });

      const result = await res.json();

      if (result.success) {
        // ✅ Login berhasil
        Swal.fire({
          icon: "success",
          title: "Login berhasil",
          showConfirmButton: false,
          timer: 1000,
        }).then(() => {
          window.location.href = "../../frontend/dashboard/dashboard.php";
        });
      } else {
        // ❌ Login gagal, tampilkan error
        Swal.fire({
          icon: "error",
          title: "Login gagal",
          text: result.message || "Username atau password salah",
        });
      }
    } catch (error) {
      console.error("Login request failed:", error);
      Swal.fire({
        icon: "error",
        title: "Terjadi Kesalahan",
        text: "Tidak dapat terhubung ke server. Silakan coba lagi nanti.",
      });
    }
  });
});
