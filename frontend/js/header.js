document.addEventListener("DOMContentLoaded", function () {
  // =========================
  // ✨ Inisialisasi Element
  // =========================
  const userDropdown = document.getElementById("userDropdown");
  const dropdownMenu = document.querySelector(".user-dropdown-menu");
  const gearBtn = document.getElementById("gearBtn");
  const hamburgerBtn = document.getElementById("hamburgerBtn");
  const sideMenu = document.querySelector(".side-menu");
  const closeMenuBtn = document.getElementById("closeMenuBtn");
  const darkModeToggle = document.getElementById("darkModeToggle");
  const logoutBtnMobile = document.getElementById("logoutBtnMobile");

  const userName = document.querySelector(".user-name");
  const userRole = document.querySelector(".user-role");
  const userAvatars = document.querySelectorAll(".user-avatar img");

  const masterDataToggle = document.getElementById("masterDataToggle");
  const masterDataSubmenu = document.getElementById("masterDataSubmenu");
  const chevronLight = document.getElementById("chevronDownLight");
  const chevronDark = document.getElementById("chevronDownDark");

  // =========================
  // 🔄 Fetch Session dari API
  // =========================
  async function updateUserHeaderInfo() {
    try {
      const res = await fetch("../../backend/api/header/index.php");
      const json = await res.json();
      if (json.status) {
        const data = json.data;

        if (userName) userName.textContent = data.full_name || "User";
        if (userRole) userRole.textContent = data.role || "Guest";

        const imgPath = data.profile_image;

        userAvatars.forEach((imgEl) => {
          imgEl.src = imgPath;
        });
      }
    } catch (err) {
      console.error("Gagal update header info:", err);
    }
  }

  updateUserHeaderInfo();
  // =========================
  // ✨ Function untuk Rotate Gear
  // =========================
  function rotateGear() {
    const gearIcon = gearBtn ? gearBtn.querySelector("img") : null;
    if (gearIcon) {
      gearIcon.classList.add("rotate-once");
      gearIcon.addEventListener(
        "animationend",
        () => {
          gearIcon.classList.remove("rotate-once");
        },
        { once: true }
      );
    }
  }

  // =========================
  // ✨ Toggle User Dropdown Menu (Desktop only)
  // =========================
  if (userDropdown && dropdownMenu) {
    userDropdown.addEventListener("click", (e) => {
      if (window.innerWidth >= 640) {
        e.stopPropagation();
        if (dropdownMenu.classList.contains("opacity-0")) {
          dropdownMenu.classList.remove("hidden");
          setTimeout(() => {
            dropdownMenu.classList.remove(
              "opacity-0",
              "scale-95",
              "pointer-events-none"
            );
            dropdownMenu.classList.add("opacity-100", "scale-100");
          }, 10);
        } else {
          dropdownMenu.classList.remove("opacity-100", "scale-100");
          dropdownMenu.classList.add(
            "opacity-0",
            "scale-95",
            "pointer-events-none"
          );
          setTimeout(() => {
            dropdownMenu.classList.add("hidden");
          }, 300);
        }
      }
    });

    document.addEventListener("click", (e) => {
      if (window.innerWidth >= 640) {
        if (
          !userDropdown.contains(e.target) &&
          !dropdownMenu.contains(e.target)
        ) {
          dropdownMenu.classList.remove("opacity-100", "scale-100");
          dropdownMenu.classList.add(
            "opacity-0",
            "scale-95",
            "pointer-events-none"
          );
          setTimeout(() => {
            dropdownMenu.classList.add("hidden");
          }, 300);
        }
      }
    });
  }

  // =========================
  // ✨ Gear Button (Desktop only)
  // =========================
  if (gearBtn && sideMenu) {
    gearBtn.addEventListener("click", () => {
      if (window.innerWidth >= 640) {
        rotateGear();
        sideMenu.classList.remove("right-[-300px]");
        sideMenu.classList.add("right-0");
      }
    });
  }

  // =========================
  // ✨ Hamburger Button (Mobile only)
  // =========================
  if (hamburgerBtn && sideMenu) {
    hamburgerBtn.addEventListener("click", () => {
      if (window.innerWidth < 640) {
        const isOpen = sideMenu.classList.contains("right-0");
        if (isOpen) {
          sideMenu.classList.remove("right-0");
          sideMenu.classList.add("right-[-300px]");
          hamburgerBtn.classList.remove("open");
        } else {
          sideMenu.classList.remove("right-[-300px]");
          sideMenu.classList.add("right-0");
          hamburgerBtn.classList.add("open");
        }
      }
    });
  }

  // =========================
  // ✨ Close Side Menu (X Button)
  // =========================
  if (closeMenuBtn && sideMenu) {
    closeMenuBtn.addEventListener("click", () => {
      sideMenu.classList.remove("right-0");
      sideMenu.classList.add("right-[-300px]");
      if (window.innerWidth < 640 && hamburgerBtn)
        hamburgerBtn.classList.remove("open");
      if (window.innerWidth >= 640) rotateGear();
    });
  }

  // =========================
  // ✨ Logout Mobile Button
  // =========================
  if (logoutBtnMobile) {
    logoutBtnMobile.addEventListener("click", (e) => {
      e.preventDefault();
      if (confirm("Apakah Anda yakin ingin logout?")) {
        window.location.href = "../../backend/API/auth/logout.php";
      }
    });
  }

  // =========================
  // ✨ Dark Mode Toggle
  // =========================
  if (darkModeToggle) {
    const darkModeEnabled = localStorage.getItem("darkMode") === "true";
    if (darkModeEnabled) {
      document.body.classList.add("dark");
      darkModeToggle.checked = true;
    } else {
      document.body.classList.remove("dark");
      darkModeToggle.checked = false;
    }

    darkModeToggle.addEventListener("change", () => {
      if (darkModeToggle.checked) {
        document.body.classList.add("dark");
        localStorage.setItem("darkMode", "true");
      } else {
        document.body.classList.remove("dark");
        localStorage.setItem("darkMode", "false");
      }
    });
  }

  // =========================
  // ✨ Master Data Dropdown Toggle with Chevron Animation
  // =========================

  masterDataToggle?.addEventListener("click", () => {
    // Cek apakah submenu saat ini terbuka (hidden class tidak ada)
    const isOpen = !masterDataSubmenu.classList.contains("hidden");

    // Toggle visibility submenu (tampilkan/sembunyikan)
    masterDataSubmenu.classList.toggle("hidden");

    // Toggle class rotate-180 untuk animasi panah
    // Jika submenu akan dibuka, tambahkan rotate-180 (panah ke atas)
    // Jika akan ditutup, hapus rotate-180 (panah ke bawah)
    chevronLight?.classList.toggle("rotate-180", !isOpen);
    chevronDark?.classList.toggle("rotate-180", !isOpen);
  });
});
