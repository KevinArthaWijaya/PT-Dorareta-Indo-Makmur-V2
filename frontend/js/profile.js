document.addEventListener("DOMContentLoaded", function () {
  // --- Inisialisasi Variabel ---
  const editBtn = document.getElementById("editBtn");
  const backBtn = document.getElementById("backBtn");
  const form = document.getElementById("profileForm");
  const fileInput = document.getElementById("profileImageInput");
  const previewImageLight = document.getElementById("previewImageLight");
  const previewImageDark = document.getElementById("previewImageDark");
  const uploadOverlay = document.getElementById("uploadOverlay");
  const passwordInput = document.getElementById("passwordInput");
  const togglePassword = document.getElementById("togglePassword");
  const eyeIcon = document.getElementById("eyeIcon");
  const avatarContainer = document.getElementById("avatarContainer");

  let isEditing = false;
  const originalValues = {}; // Menyimpan nilai awal untuk tombol Cancel
  let savedProfileImage = ""; // Menyimpan nama file gambar dari database
  togglePassword.disabled = true;
  passwordInput.type = "password";

  // --- Fungsi Update Icon Mata Password ---
  function updateEyeIcon() {
    const isDarkMode = document.body.classList.contains("dark");
    const isHidden = passwordInput.type === "password";
    eyeIcon.src = isHidden
      ? isDarkMode
        ? "../../assets/icons/darkmode/hide-password-dark.png"
        : "../../assets/icons/lightmode/hide-password-light.png"
      : isDarkMode
      ? "../../assets/icons/darkmode/show-password-dark.png"
      : "../../assets/icons/lightmode/show-password-light.png";
  }

  // --- Setup Awal Upload Overlay ---
  uploadOverlay.classList.add("opacity-0", "pointer-events-none");
  updateEyeIcon();

  // --- Observer untuk Dark/Light Mode ---
  const observer = new MutationObserver(updateEyeIcon);
  observer.observe(document.body, {
    attributes: true,
    attributeFilter: ["class"],
  });

  // --- Fungsi Fetch Profile dari API ---
  async function fetchProfile() {
    try {
      const response = await fetch("../../backend/api/profile/index.php");
      const result = await response.json();

      if (result.status) {
        const data = result.data;
        // Set value input dari data API
        form.first_name.value = data.first_name || "";
        form.last_name.value = data.last_name || "";
        form.email.value = data.email || "";
        form.phone_number.value = (data.phone_number || "").replace("+62", "");
        form.username.value = data.username || "";
        form.bio.value = data.bio || "";
        savedProfileImage = data.profile_image || "";

        // Set gambar preview (default/user uploaded)
        if (savedProfileImage.includes("uploads/user_image/")) {
          previewImageLight.src = "../../backend/" + savedProfileImage;
          previewImageDark.src = "../../backend/" + savedProfileImage;
        } else {
          previewImageLight.src = "../../" + savedProfileImage;
          previewImageDark.src =
            "../../" + savedProfileImage.replace("lightmode", "darkmode");
        }

        // Simpan semua original value
        form.querySelectorAll("input, textarea").forEach((input) => {
          originalValues[input.name] = input.value;
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Oops...",
          text: result.message || "Gagal memuat data profil.",
          confirmButtonColor: "#d33",
        });
      }
    } catch (error) {
      console.error(error);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Terjadi kesalahan saat mengambil data profil.",
        confirmButtonColor: "#d33",
      });
    }
  }
  fetchProfile();

  // --- Event Klik Edit Button ---
  editBtn.addEventListener("click", function () {
    if (!isEditing) {
      // Aktifkan semua input
      form
        .querySelectorAll("input:not([type='file']), textarea")
        .forEach((input) => {
          input.disabled = false;
          input.classList.remove("bg-gray-100", "dark:bg-gray-700");
        });
      passwordInput.type = "password";
      passwordInput.value = "";
      passwordInput.placeholder = "Biarkan kosong jika tidak diganti";
      togglePassword.disabled = false;
      fileInput.disabled = false;

      avatarContainer.classList.add("group"); // ✅ Tambahkan class group
      uploadOverlay.classList.remove("pointer-events-none"); // ✅ Buka hover + animasi blur

      editBtn.textContent = "Update Profile";
      backBtn.textContent = "Cancel";
      isEditing = true;
    } else {
      triggerUpdateProfile();
    }
  });

  // --- Event Klik Back Button ---
  backBtn.addEventListener("click", function () {
    if (isEditing) {
      // Kembalikan data ke nilai awal
      form
        .querySelectorAll("input:not([type='file']), textarea")
        .forEach((input) => {
          if (input.name in originalValues) {
            input.value = originalValues[input.name];
          }
          input.disabled = true;
          if (input.name === "phone_number") {
            input.className =
              "w-full rounded-r-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 p-2 text-gray-900 dark:text-white";
          } else {
            input.className =
              "w-full rounded-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 p-2 text-gray-900 dark:text-white";
          }
        });

      togglePassword.disabled = true;
      fileInput.disabled = true;
      fileInput.value = "";

      uploadOverlay.classList.add("opacity-0", "pointer-events-none"); // ✅ Sembunyikan overlay lagi
      avatarContainer.classList.remove("group"); // ✅ Hapus class group

      editBtn.textContent = "Edit Profile";
      backBtn.textContent = "Back";
      passwordInput.type = "password";
      passwordInput.value = "********";
      passwordInput.placeholder = "";
      updateEyeIcon();
      isEditing = false;
    } else {
      window.location.href = "../dashboard/dashboard.php";
    }
  });

  // --- Event Klik Upload Gambar ---
  if (uploadOverlay && fileInput) {
    uploadOverlay.addEventListener("click", () => {
      if (isEditing) fileInput.click();
    });

    fileInput.addEventListener("change", async function () {
      const file = this.files[0];
      if (file) {
        // Validasi Ukuran File (maksimal 2MB)
        if (file.size > 2 * 1024 * 1024) {
          Swal.fire({
            icon: "warning",
            title: "File Terlalu Besar!",
            text: "Ukuran maksimum 2MB. File akan otomatis diperkecil.",
            confirmButtonColor: "#d33",
          });

          // Resize gambar otomatis
          const resized = await resizeImage(file, 800, 800);
          if (resized) {
            previewImageLight.src = resized.preview;
            previewImageDark.src = resized.preview;
            fileInput.files = resized.fileList;
          } else {
            this.value = "";
          }
        } else {
          const reader = new FileReader();
          reader.onload = function (e) {
            previewImageLight.src = e.target.result;
            previewImageDark.src = e.target.result;
          };
          reader.readAsDataURL(file);
        }
      }
    });
  }

  // --- Event Klik Toggle Password Eye Icon ---
  if (togglePassword) {
    togglePassword.addEventListener("click", () => {
      passwordInput.type =
        passwordInput.type === "password" ? "text" : "password";
      updateEyeIcon();
    });
  }

  // --- Konfirmasi Update Profile ---
  async function triggerUpdateProfile() {
    const result = await Swal.fire({
      title: "Yakin Simpan Perubahan?",
      text: "Data akan diperbarui permanen.",
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#aaa",
      confirmButtonText: "Iya, simpan!",
      cancelButtonText: "Tidak",
    });

    if (result.isConfirmed) {
      submitUpdate();
    }
  }

  // --- Submit Update Profile ---
  async function submitUpdate() {
    const emailValue = form.email.value.trim();
    const phoneNumberValue = form.phone_number.value.trim();
    const passwordValue = form.password.value.trim();

    // Validasi Email Format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(emailValue)) {
      Swal.fire({
        icon: "warning",
        title: "Format Email Salah",
        text: "Masukkan email dengan format benar",
        confirmButtonColor: "#d33",
      });
      return;
    }

    // Validasi Nomor Telepon (hanya angka)
    if (!/^\d+$/.test(phoneNumberValue)) {
      Swal.fire({
        icon: "warning",
        title: "Format Nomor Salah",
        text: "Nomor hanya boleh angka.",
        confirmButtonColor: "#d33",
      });
      return;
    }

    // Validasi Password Minimal 6 karakter
    if (passwordValue !== "" && passwordValue.length < 6) {
      Swal.fire({
        icon: "warning",
        title: "Password Terlalu Pendek",
        text: "Minimal 6 karakter.",
        confirmButtonColor: "#d33",
      });
      return;
    }

    const formData = new FormData();
    formData.append("first_name", form.first_name.value.trim());
    formData.append("last_name", form.last_name.value.trim());
    formData.append("email", emailValue);
    formData.append("phone_number", "+62" + phoneNumberValue);
    formData.append("username", form.username.value.trim());
    formData.append("bio", form.bio.value.trim());
    if (passwordValue !== "") formData.append("password", passwordValue);
    if (fileInput.files.length > 0)
      formData.append("profile_image", fileInput.files[0]);

    Swal.fire({
      title: "Mengupload...",
      text: "Mohon tunggu sebentar.",
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    try {
      const response = await fetch("../../backend/api/profile/index.php", {
        method: "POST",
        body: formData,
      });
      const result = await response.json();
      Swal.close();
      if (result.status) {
        Swal.fire({
          icon: "success",
          title: "Berhasil!",
          text: "Profil berhasil diperbarui.",
          timer: 2000,
          showConfirmButton: false,
        }).then(() => window.location.reload());
      } else {
        Swal.fire({
          icon: "error",
          title: "Gagal!",
          text: result.message || "Gagal update profil.",
          confirmButtonColor: "#d33",
        });
      }
    } catch (error) {
      Swal.close();
      console.error(error);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Terjadi kesalahan saat mengupdate profil.",
        confirmButtonColor: "#d33",
      });
    }
  }

  // --- Fungsi Resize Image Otomatis ---
  async function resizeImage(file, maxWidth, maxHeight) {
    return new Promise((resolve) => {
      const img = new Image();
      img.onload = () => {
        const canvas = document.createElement("canvas");
        let width = img.width;
        let height = img.height;

        if (width > height) {
          if (width > maxWidth) {
            height = Math.round((height *= maxWidth / width));
            width = maxWidth;
          }
        } else {
          if (height > maxHeight) {
            width = Math.round((width *= maxHeight / height));
            height = maxHeight;
          }
        }

        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext("2d");
        ctx.drawImage(img, 0, 0, width, height);

        canvas.toBlob(
          (blob) => {
            const resizedFile = new File([blob], file.name, {
              type: file.type,
            });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(resizedFile);
            resolve({
              preview: URL.createObjectURL(resizedFile),
              fileList: dataTransfer.files,
            });
          },
          file.type,
          0.7
        );
      };
      img.onerror = () => resolve(false);
      img.src = URL.createObjectURL(file);
    });
  }
});
