document.addEventListener("DOMContentLoaded", () => {
  const tableBody = document.getElementById("categoryTableBody");
  const rowsPerPageSelect = document.getElementById("rowsPerPage");
  const paginationContainer = document.getElementById("paginationControls");

  const openModalBtn = document.getElementById("openCreateCategory");
  const modal = document.getElementById("categoryModal");
  const modalTitle = document.getElementById("categoryModalTitle");
  const closeBtn = document.getElementById("closeCategoryModal");
  const cancelBtn = document.getElementById("cancelCategoryBtn");
  const submitBtn = document.getElementById("submitCategoryBtn");
  const form = document.getElementById("categoryForm");
  const nameInput = document.getElementById("categoryName");
  const prefixInput = document.getElementById("skuPrefix");

  let allData = [];
  let currentPage = 1;
  let rowsPerPage = parseInt(rowsPerPageSelect.value);
  let editId = null;

  async function fetchData() {
    try {
      const res = await fetch("../../backend/API/category/index.php");
      const json = await res.json();

      if (!json.success) {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: json.message || "Gagal memuat data dari server",
          confirmButtonColor: "#8b5cf6",
        });
        return;
      }

      allData = json.data || [];
      renderTable();
    } catch (err) {
      console.error("Gagal memuat data kategori:", err);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Terjadi kesalahan saat mengambil data.",
        confirmButtonColor: "#8b5cf6",
      });
    }
  }

  function renderTable() {
    const start = (currentPage - 1) * rowsPerPage;
    const paginated = allData.slice(start, start + rowsPerPage);
    tableBody.innerHTML = "";

    if (paginated.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="4" class="text-center text-gray-500 py-3">Tidak ada data</td></tr>`;
      return;
    }

    paginated.forEach((cat, i) => {
      tableBody.innerHTML += `
        <tr>
          <td class="px-4 py-2">${start + i + 1}</td>
          <td class="px-4 py-2">${cat.name}</td>
          <td class="px-4 py-2">${cat.sku_prefix}</td>
          <td class="px-4 py-2 text-center">
            <div class="flex justify-center gap-2">
              <button class="editCategoryBtn" data-id="${cat.id}" title="Edit">
                <img src="../../assets/icons/edit_tabel.png" class="w-7 h-7 p-1 hover:scale-110 transition" />
              </button>
              <button class="deleteCategoryBtn" data-id="${
                cat.id
              }" title="Delete">
                <img src="../../assets/icons/delete_tabel.png" class="w-8 h-8 p-1 hover:scale-110 transition" />
              </button>
            </div>
          </td>
        </tr>
      `;
    });

    renderPagination();
  }

  function renderPagination() {
    const totalPages = Math.ceil(allData.length / rowsPerPage);
    paginationContainer.innerHTML = "";

    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement("button");
      btn.textContent = i;
      btn.className = `px-3 py-1 border rounded ${
        i === currentPage ? "bg-red-600 text-white" : "bg-white text-gray-700"
      }`;
      btn.addEventListener("click", () => {
        currentPage = i;
        renderTable();
      });
      paginationContainer.appendChild(btn);
    }
  }

  openModalBtn.addEventListener("click", () => {
    editId = null;
    modalTitle.textContent = "Tambah Kategori";
    nameInput.value = "";
    prefixInput.value = "";
    modal.classList.remove("hidden");
  });

  closeBtn.addEventListener("click", () => modal.classList.add("hidden"));
  cancelBtn.addEventListener("click", () => modal.classList.add("hidden"));

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const name = nameInput.value.trim();
    const prefix = prefixInput.value.trim().toUpperCase();

    if (!name || !prefix) {
      Swal.fire({
        icon: "warning",
        title: "Validasi Gagal",
        text: "Nama dan Prefix SKU wajib diisi.",
        confirmButtonColor: "#dc2626",
      });
      return;
    }

    const method = editId ? "PUT" : "POST";
    const url = `../../backend/API/category/index.php${
      editId ? "?id=" + editId : ""
    }`;

    try {
      const res = await fetch(url, {
        method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name, sku_prefix: prefix }),
      });

      const json = await res.json();

      if (json.success) {
        Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: json.message,
          confirmButtonColor: "#dc2626",
        });

        modal.classList.add("hidden");
        fetchData();
        form.reset();
      } else {
        Swal.fire({
          icon: "error",
          title: "Gagal",
          text: json.message,
          confirmButtonColor: "#dc2626",
        });
      }
    } catch (err) {
      console.error("Gagal simpan:", err);
      Swal.fire("Error", "Gagal menyimpan data", "error");
    }
  });

  rowsPerPageSelect.addEventListener("rowsPerPage", () => {
    rowsPerPage = parseInt(rowsPerPageSelect.value);
    currentPage = 1;
    renderTable();
  });

  window.editCategory = (id) => {
    const found = allData.find((c) => c.id === id);
    if (!found) return;

    editId = id;
    modalTitle.textContent = "Edit Kategori";
    nameInput.value = found.name;
    prefixInput.value = found.sku_prefix;
    modal.classList.remove("hidden");
  };

  window.deleteCategory = async (id) => {
    const confirm = await Swal.fire({
      title: "Yakin ingin menghapus?",
      text: "Data tidak bisa dikembalikan!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc2626",
      cancelButtonColor: "#6b7280",
      confirmButtonText: "Hapus",
      cancelButtonText: "Batal",
    });

    if (!confirm.isConfirmed) return;

    try {
      const res = await fetch(`../../backend/api/category/index.php?id=${id}`, {
        method: "DELETE",
      });

      const json = await res.json();
      if (json.success) {
        Swal.fire("Dihapus!", json.message, "success");
        fetchData();
      } else {
        Swal.fire("Gagal", json.message, "error");
      }
    } catch (err) {
      console.error("Gagal hapus:", err);
      Swal.fire("Error", "Tidak dapat menghapus data", "error");
    }
  };

  fetchData();

  // Event Delegation untuk tombol edit & delete
  tableBody.addEventListener("click", (e) => {
    const editBtn = e.target.closest(".editCategoryBtn");
    const deleteBtn = e.target.closest(".deleteCategoryBtn");

    if (editBtn) {
      const id = parseInt(editBtn.dataset.id);
      const found = allData.find((c) => c.id === id);
      if (!found) return;
      editId = id;
      modalTitle.textContent = "Edit Kategori";
      nameInput.value = found.name;
      prefixInput.value = found.sku_prefix;
      modal.classList.remove("hidden");
    }

    if (deleteBtn) {
      const id = parseInt(deleteBtn.dataset.id);
      Swal.fire({
        title: "Yakin ingin menghapus?",
        text: "Data tidak bisa dikembalikan!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#dc2626",
        cancelButtonColor: "#6b7280",
        confirmButtonText: "Hapus",
        cancelButtonText: "Batal",
      }).then(async (result) => {
        if (!result.isConfirmed) return;

        try {
          const res = await fetch(
            `../../backend/api/category/index.php?id=${id}`,
            {
              method: "DELETE",
            }
          );
          const json = await res.json();
          if (json.success) {
            Swal.fire("Dihapus!", json.message, "success");
            fetchData();
          } else {
            Swal.fire("Gagal", json.message, "error");
          }
        } catch (err) {
          console.error("Gagal hapus:", err);
          Swal.fire("Error", "Tidak dapat menghapus data", "error");
        }
      });
    }
  });

  function renderPagination() {
    const totalPages = Math.ceil(allData.length / rowsPerPage);
    paginationContainer.innerHTML = "";

    if (totalPages <= 1) return;

    const createButton = (
      text,
      isActive = false,
      disabled = false,
      onClick = null
    ) => {
      const btn = document.createElement("button");
      btn.textContent = text;
      btn.disabled = disabled;

      btn.className = `
        w-8 h-8 flex items-center justify-center rounded-md text-sm font-semibold transition-all
        ${
          isActive
            ? "bg-red-600 text-white"
            : "hover:bg-red-100 hover:text-red-600 text-gray-900 dark:text-white bg-transparent"
        }
        ${disabled ? "hidden" : "cursor-pointer"}
      `.replace(/\s+/g, " ");

      if (onClick && !disabled) btn.addEventListener("click", onClick);
      return btn;
    };

    if (currentPage > 1) {
      paginationContainer.appendChild(
        createButton("«", false, false, () => {
          currentPage--;
          renderTable();
        })
      );
    }

    for (let i = 1; i <= totalPages; i++) {
      paginationContainer.appendChild(
        createButton(i, i === currentPage, false, () => {
          currentPage = i;
          renderTable();
        })
      );
    }

    if (currentPage < totalPages) {
      paginationContainer.appendChild(
        createButton("»", false, false, () => {
          currentPage++;
          renderTable();
        })
      );
    }
  }
});
