document.addEventListener("DOMContentLoaded", () => {
  const tableBody = document.getElementById("userTableBody");
  const limitSelect = document.getElementById("limitSelect");
  const searchInput = document.getElementById("searchInput");
  const paginationControls = document.getElementById("paginationControls");
  const openModalBtn = document.getElementById("openUserModal");
  const userFormModal = document.getElementById("userFormModal");
  const userFormModalContent = document.getElementById("userFormModalContent");
  const closeModalBtn = document.getElementById("closeUserFormModal");
  const cancelModalBtn = document.getElementById("cancelModalBtn");
  const userForm = document.getElementById("userForm");
  const roleSelect = document.getElementById("role_id");
  const statusField = document.getElementById("status").closest("div");
  const hireDateInput = document.getElementById("hire_date");
  const passwordInput = document.getElementById("password");
  const submitBtn = document.getElementById("submitUserBtn");

  let currentPage = 1;
  let currentLimit = parseInt(limitSelect.value);
  let currentQuery = "";
  let currentData = [];
  let sortColumn = null;
  let sortAsc = true;

  flatpickr(hireDateInput, {
    dateFormat: "Y-m-d",
    maxDate: "today",
    altInput: true,
    altFormat: "F j, Y",
  });

  const phoneInput = document.getElementById("phone_number");
  phoneInput?.addEventListener("input", (e) => {
    e.target.value = e.target.value.replace(/\D/g, "");
  });

  async function fetchRoles(selectedRoleId = null) {
    try {
      const res = await fetch(
        "../../backend/api/user_management/index.php?type=roles",
        { credentials: "include" }
      );
      const json = await res.json();
      if (!json.status) throw new Error(json.message);

      roleSelect.innerHTML = `<option value="">Select Role</option>`;

      json.data.forEach((role) => {
        // Cegah Manager melihat role Admin
        if (
          window.userRole === "Manager" &&
          role.role_name.toLowerCase() === "admin"
        )
          return;

        const opt = document.createElement("option");
        opt.value = role.id;
        opt.textContent = role.role_name;
        roleSelect.appendChild(opt);
      });

      if (selectedRoleId) roleSelect.value = selectedRoleId;
    } catch (err) {
      console.error("Error fetching roles:", err);
    }
  }

  async function openUserModal(mode, user = null) {
    userForm.reset();
    userFormModal.classList.remove("hidden");
    userFormModal.classList.add("flex");
    requestAnimationFrame(() => {
      userFormModalContent.classList.remove("opacity-0", "scale-95");
      userFormModalContent.classList.add("opacity-100", "scale-100");
    });
    document.body.classList.add("overflow-hidden");

    document.getElementById("modalTitle").textContent =
      mode === "edit" ? "Edit User" : "Create User";
    submitBtn.textContent = mode === "edit" ? "Save" : "Create";
    document.getElementById("user_id").value = user?.id || "";
    passwordInput.placeholder =
      mode === "edit" ? "Kosongkan jika tidak diganti" : "Minimal 6 karakter";

    if (mode === "edit" && user) {
      statusField.classList.remove("hidden");
      userForm.first_name.value = user.first_name || "";
      userForm.last_name.value = user.last_name || "";
      userForm.email.value = user.email || "";
      userForm.phone_number.value = user.phone_number.replace("+62", "") || "";
      userForm.username.value = user.username || "";
      userForm.status.value = user.status?.toLowerCase() || "active";
      userForm.hire_date.value = user.hire_date || "";

      if (user.hire_date) {
        flatpickr("#hire_date", {
          defaultDate: user.hire_date,
          dateFormat: "Y-m-d",
          altInput: true,
          altFormat: "F j, Y",
          maxDate: "today",
        });
      }
      userForm.bio.value = user.bio || "";
      await fetchRoles(user.role_id);
    } else {
      statusField.classList.add("hidden");
      await fetchRoles();
    }
  }

  function closeModal() {
    userFormModalContent.classList.remove("opacity-100", "scale-100");
    userFormModalContent.classList.add("opacity-0", "scale-95");
    setTimeout(() => {
      userFormModal.classList.add("hidden");
      userFormModal.classList.remove("flex");
      document.body.classList.remove("overflow-hidden");
    }, 200);
  }

  async function fetchUsers() {
    try {
      const sort_by = sortColumn || "id";
      const order = sortColumn ? (sortAsc ? "ASC" : "DESC") : "ASC";

      const response = await fetch(
        `../../backend/api/user_management/index.php?q=${encodeURIComponent(
          currentQuery
        )}&limit=${currentLimit}&page=${currentPage}&sort_by=${sort_by}&order=${order}`,
        { credentials: "include" }
      );
      const json = await response.json();
      if (!json.status) throw new Error(json.message);
      currentData = json.data.users;
      renderTable(currentData);
      renderPagination(json.data.pagination);
    } catch (err) {
      console.error("Fetch error:", err);
      tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-red-500">Failed to load user data.</td></tr>`;
    }
  }

  openModalBtn?.addEventListener("click", () => openUserModal("create"));
  closeModalBtn?.addEventListener("click", closeModal);
  cancelModalBtn?.addEventListener("click", closeModal);

  function renderTable(users) {
    tableBody.innerHTML = "";
    if (users.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-gray-500">No users found.</td></tr>`;
      return;
    }

    if (sortColumn) {
      users.sort((a, b) => {
        const valA = (a[sortColumn] || "").toString().toLowerCase();
        const valB = (b[sortColumn] || "").toString().toLowerCase();
        return sortAsc ? valA.localeCompare(valB) : valB.localeCompare(valA);
      });
    }

    users.forEach((user) => {
      const hireDate = new Date(user.hire_date);
      const now = new Date();
      const years = now.getFullYear() - hireDate.getFullYear();
      const months = now.getMonth() - hireDate.getMonth();
      const workingPeriod = `${years} yr ${
        months < 0 ? months + 12 : months
      } mo`;

      const statusHTML = `
        <span class="inline-flex px-2 py-1 text-xs font-medium rounded uppercase ${
          user.status.toLowerCase() === "active"
            ? "bg-green-500 text-white"
            : "bg-red-500 text-white"
        }">${user.status.toUpperCase()}</span>`;

      const row = `
<tr class="hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
  <td class="px-4 py-3">${user.first_name}</td>
  <td class="px-4 py-3">${user.last_name}</td>
  <td class="px-4 py-3">${user.email}</td>
  <td class="px-4 py-3">${user.phone_number}</td>
  <td class="px-4 py-3">${user.role_name}</td>
  <td class="px-4 py-3 text-center">${workingPeriod}</td>
  <td class="px-4 py-3 text-center">${statusHTML}</td>
  <td class="px-4 py-3 text-center bg-transparent flex justify-center gap-2">
    <button class="editUserBtn" data-id="${user.id}" title="Edit">
      <img src="../../assets/icons/edit_tabel.png" class="w-7 h-7 p-1 hover:scale-110 transition" />
    </button>
    ${
      user.role_name.toLowerCase() !== "admin"
        ? `<button class="deleteUserBtn" data-id="${user.id}" title="Delete">
             <img src="../../assets/icons/delete_tabel.png" class="w-8 h-8 p-1 hover:scale-110 transition" />
           </button>`
        : ""
    }
  </td>
</tr>`;
      tableBody.insertAdjacentHTML("beforeend", row);
    });

    document.querySelectorAll(".editUserBtn").forEach((btn) => {
      btn.addEventListener("click", async () => {
        const userId = btn.dataset.id;
        try {
          const res = await fetch(
            `../../backend/api/user_management/index.php?id=${userId}`,
            { credentials: "include" }
          );
          const json = await res.json();
          if (!json.status || !json.data?.user)
            throw new Error("User tidak ditemukan.");
          openUserModal("edit", json.data.user);
        } catch (err) {
          console.error("Edit Load Error:", err);
          Swal.fire({
            icon: "error",
            title: "Gagal memuat data user",
            text: err.message,
          });
        }
      });
    });

    document.querySelectorAll(".deleteUserBtn").forEach((btn) => {
      btn.addEventListener("click", async () => {
        const userId = btn.dataset.id;
        const user = currentData.find((u) => u.id == userId);
        if (!user) return;

        if (user.role_name.toLowerCase() === "admin") {
          return Swal.fire({
            icon: "warning",
            title: "Tidak bisa menghapus Admin",
            text: "User dengan role Admin tidak boleh dihapus.",
          });
        }

        const confirmed = await Swal.fire({
          icon: "warning",
          title: "Yakin ingin menghapus user ini?",
          text: `${user.first_name} (${user.username})`,
          showCancelButton: true,
          confirmButtonText: "Ya, Hapus",
          cancelButtonText: "Batal",
          confirmButtonColor: "#e3342f",
          cancelButtonColor: "#6b7280",
        });

        if (confirmed.isConfirmed) {
          try {
            const res = await fetch(
              `../../backend/api/user_management/index.php?id=${userId}`,
              {
                method: "DELETE",
                credentials: "include",
              }
            );
            const text = await res.text();
            let json;
            try {
              json = JSON.parse(text);
            } catch (parseErr) {
              throw new Error("Respons dari server tidak valid JSON.");
            }

            if (json.status) {
              Swal.fire({ icon: "success", title: "User berhasil dihapus" });
              fetchUsers();
            } else {
              throw new Error(json.message || "Gagal menghapus user.");
            }
          } catch (err) {
            Swal.fire({
              icon: "error",
              title: "Gagal",
              text: err.message,
            });
          }
        }
      });
    });
  }

  userForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const userId = document.getElementById("user_id").value;
    const isEdit = Boolean(userId);

    const payload = {
      first_name: userForm.first_name.value.trim(),
      last_name: userForm.last_name.value.trim(),
      email: userForm.email.value.trim(),
      phone_number:
        "+62" + userForm.phone_number.value.trim().replace(/^0|^\+62/, ""),
      username: userForm.username.value.trim(),
      password: userForm.password.value.trim(),
      bio: userForm.bio.value.trim(),
      role_id: userForm.role_id.value,
      status: userForm.status.value,
      hire_date: userForm.hire_date.value,
    };

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(payload.email)) {
      return Swal.fire({ icon: "warning", title: "Email tidak valid" });
    }
    if (!payload.role_id) {
      return Swal.fire({ icon: "warning", title: "Role wajib dipilih" });
    }
    if (!payload.hire_date.match(/^\d{4}-\d{2}-\d{2}$/)) {
      return Swal.fire({
        icon: "warning",
        title: "Tanggal Hire tidak valid",
        text: "Gunakan format yyyy-mm-dd",
      });
    }

    try {
      let res;
      if (isEdit) {
        res = await fetch(
          `../../backend/api/user_management/index.php?id=${userId}`,
          {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
            credentials: "include",
          }
        );
      } else {
        const formData = new FormData(userForm);
        formData.set("phone_number", payload.phone_number);
        res = await fetch(`../../backend/api/user_management/index.php`, {
          method: "POST",
          body: formData,
          credentials: "include",
        });
      }

      const result = await res.json();
      if (result.status) {
        await Swal.fire({
          icon: "success",
          title: isEdit ? "Berhasil diupdate" : "Berhasil ditambahkan",
          timer: 1500,
          showConfirmButton: false,
        });
        closeModal();
        fetchUsers();
      } else {
        await Swal.fire({
          icon: "error",
          title: "Gagal",
          text: result.message || "Terjadi kesalahan",
        });
      }
    } catch (error) {
      console.error(error);
      await Swal.fire({
        icon: "error",
        title: "Error",
        text: "Gagal mengirim data ke server.",
      });
    }
  });

  function renderPagination({ total_pages }) {
    paginationControls.innerHTML = "";
    if (total_pages <= 1) return;

    if (currentPage > 1) {
      const prevBtn = createPageButton("&laquo;", currentPage - 1);
      paginationControls.appendChild(prevBtn);
    }

    for (let i = 1; i <= total_pages; i++) {
      const btn = createPageButton(i, i);
      if (i === currentPage) {
        btn.classList.add("bg-red-600", "text-white");
      } else {
        btn.classList.add(
          "hover:bg-red-100",
          "dark:hover:bg-red-800",
          "transition-colors"
        );
      }
      paginationControls.appendChild(btn);
    }

    if (currentPage < total_pages) {
      const nextBtn = createPageButton("&raquo;", currentPage + 1);
      paginationControls.appendChild(nextBtn);
    }
  }

  function createPageButton(label, page) {
    const btn = document.createElement("button");
    btn.innerHTML = label;
    btn.className =
      "px-3 py-1 rounded-md text-sm border border-transparent text-gray-800 dark:text-white";
    btn.addEventListener("click", () => {
      currentPage = page;
      fetchUsers();
    });
    return btn;
  }

  document.querySelectorAll("th.sortable").forEach((th) => {
    const column = th.dataset.column;
    const iconLight = th.querySelector(".sort-icon-light");
    const iconDark = th.querySelector(".sort-icon-dark");

    th.addEventListener("click", () => {
      if (sortColumn === column) {
        if (sortAsc === true) {
          sortAsc = false;
          iconLight.src =
            "../../assets/icons/lightmode/sort-alpha-down-light.png";
          iconDark.src = "../../assets/icons/darkmode/sort-alpha-down-dark.png";
        } else if (sortAsc === false) {
          sortColumn = null;
          sortAsc = null;
          iconLight.src = "../../assets/icons/lightmode/sort-alt-light.png";
          iconDark.src = "../../assets/icons/darkmode/sort-alt-dark.png";
          fetchUsers();
          return;
        }
      } else {
        document.querySelectorAll("th.sortable").forEach((el) => {
          el.querySelector(".sort-icon-light").src =
            "../../assets/icons/lightmode/sort-alt-light.png";
          el.querySelector(".sort-icon-dark").src =
            "../../assets/icons/darkmode/sort-alt-dark.png";
        });

        sortColumn = column;
        sortAsc = true;
        iconLight.src = "../../assets/icons/lightmode/sort-alpha-up-light.png";
        iconDark.src = "../../assets/icons/darkmode/sort-alpha-up-dark.png";
      }

      renderTable(currentData);
    });
  });

  searchInput.addEventListener("input", () => {
    currentQuery = searchInput.value.trim();
    currentPage = 1;
    fetchUsers();
  });

  limitSelect.addEventListener("change", () => {
    currentLimit = parseInt(limitSelect.value);
    currentPage = 1;
    fetchUsers();
  });

  fetchUsers();
});
