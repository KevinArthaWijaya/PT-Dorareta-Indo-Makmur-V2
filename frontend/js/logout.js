document.addEventListener("DOMContentLoaded", function () {
  const logoutBtn = document.getElementById("logoutBtn");

  if (logoutBtn) {
    logoutBtn.addEventListener("click", async (e) => {
      e.preventDefault(); // Cegah href bawaan

      try {
        const response = await fetch("../../backend/API/auth/logout_api.php", {
          method: "POST",
          credentials: "include",
        });
        const data = await response.json();

        if (data.status) {
          window.location.href = "../../frontend/auth/login.php";
        } else {
          alert("Gagal logout.");
        }
      } catch (error) {
        console.error("Logout Error:", error);
      }
    });
  }
});
