document.addEventListener("DOMContentLoaded", () => {
  const supplierTableBody = document.querySelector("#supplierTable tbody");
  const supplierModal = document.getElementById("supplierModal");
  const supplierModalContent = document.getElementById("supplierModalContent");
  const closeSupplierModal = document.getElementById("closeSupplierModal");
  const cancelSupplierBtn = document.getElementById("cancelSupplierBtn");
  const supplierForm = document.getElementById("supplierForm");

  const supplierIdInput = document.getElementById("supplierId");
  const nameInput = document.getElementById("supplierName");
  const phoneInput = document.getElementById("supplierPhone");
  const emailInput = document.getElementById("supplierEmail");
  const addressInput = document.getElementById("supplierAddress");

  const searchInput = document.getElementById("searchSupplier");
  const rowsPerPageSelect = document.getElementById("rowsPerPage");
  const paginationContainer = document.getElementById("paginationControls");

  let allSupplierData = [];
  let currentPage = 1;
  let rowsPerPage = parseInt(rowsPerPageSelect.value);

  rowsPerPageSelect.addEventListener("change", (e) => {
    rowsPerPage = parseInt(e.target.value);
    currentPage = 1;
    renderTable();
  });

  searchInput.addEventListener("input", () => {
    currentPage = 1;
    renderTable();
  });

  function loadSuppliers() {
    fetch("../../backend/api/supplier/index.php")
      .then((res) => res.json())
      .then((data) => {
        allSupplierData = data;
        currentPage = 1;
        renderTable();
      })
      .catch((err) => {
        console.error("Error fetching suppliers:", err);
        Swal.fire("Error", "Gagal mengambil data supplier", "error");
      });
  }

  function renderTable() {
    const keyword = searchInput.value.toLowerCase();
    const filteredData = allSupplierData.filter(
      (sup) =>
        sup.name.toLowerCase().includes(keyword) ||
        (sup.phone || "").toLowerCase().includes(keyword) ||
        (sup.email || "").toLowerCase().includes(keyword) ||
        (sup.address || "").toLowerCase().includes(keyword)
    );

    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const paginated = filteredData.slice(start, end);

    supplierTableBody.innerHTML = "";

    paginated.forEach((sup, i) => {
      const row = document.createElement("tr");
      row.innerHTML = `
          <td class="px-4 py-2">${start + i + 1}</td>
          <td class="px-4 py-2">${sup.name || "-"}</td>
          <td class="px-4 py-2">${sup.phone || "-"}</td>
          <td class="px-4 py-2">${sup.email || "-"}</td>
          <td class="px-4 py-2">${sup.address || "-"}</td>
          <td class="px-4 py-2">
            <div class="flex items-center gap-3">
              <button class="editBtn" data-id="${sup.id}" title="Edit">
                <img src="../../assets/icons/edit_tabel.png" class="w-7 h-7 p-1 hover:scale-110 transition" />
              </button>
              <button class="deleteBtn" data-id="${sup.id}" title="Delete">
                <img src="../../assets/icons/delete_tabel.png" class="w-7 h-7 p-1 hover:scale-110 transition" />
              </button>
            </div>
          </td>
        `;
      supplierTableBody.appendChild(row);
    });

    bindActions(filteredData);
    renderPagination(filteredData.length);
  }

  function bindActions() {
    document.querySelectorAll(".editBtn").forEach((btn) => {
      btn.addEventListener("click", () => {
        const id = btn.dataset.id;
        const supplier = allSupplierData.find((s) => s.id == id);
        if (supplier) {
          supplierIdInput.value = supplier.id;
          nameInput.value = supplier.name || "";
          phoneInput.value = supplier.phone || "";
          emailInput.value = supplier.email || "";
          addressInput.value = supplier.address || "";
          openModal();
        }
      });
    });

    document.querySelectorAll(".deleteBtn").forEach((btn) => {
      btn.addEventListener("click", () => {
        const id = btn.dataset.id;
        Swal.fire({
          title: "Hapus Supplier?",
          text: "Data akan dihapus permanen.",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#d33",
          cancelButtonColor: "#6c757d",
          confirmButtonText: "Ya, hapus!",
          cancelButtonText: "Batal",
        }).then((result) => {
          if (result.isConfirmed) deleteSupplier(id);
        });
      });
    });
  }

  function renderPagination(totalItems) {
    paginationContainer.innerHTML = "";
    const totalPages = Math.ceil(totalItems / rowsPerPage);
    if (totalPages <= 1) return;

    const createBtn = (label, isActive, onClick) => {
      const btn = document.createElement("button");
      btn.textContent = label;
      btn.className = `
          w-8 h-8 flex items-center justify-center rounded-md text-sm font-semibold
          ${
            isActive
              ? "bg-red-600 text-white"
              : "text-gray-900 dark:text-white hover:bg-red-100 hover:text-red-600"
          }
        `.trim();
      btn.addEventListener("click", onClick);
      return btn;
    };

    if (currentPage > 1) {
      paginationContainer.appendChild(
        createBtn("«", false, () => {
          currentPage--;
          renderTable();
        })
      );
    }

    for (let i = 1; i <= totalPages; i++) {
      paginationContainer.appendChild(
        createBtn(i, i === currentPage, () => {
          currentPage = i;
          renderTable();
        })
      );
    }

    if (currentPage < totalPages) {
      paginationContainer.appendChild(
        createBtn("»", false, () => {
          currentPage++;
          renderTable();
        })
      );
    }
  }

  function openModal() {
    supplierModal.classList.remove("hidden");
    setTimeout(() => {
      supplierModalContent.classList.remove("opacity-0", "scale-95");
      supplierModalContent.classList.add("opacity-100", "scale-100");
    }, 20);
  }

  function closeModal() {
    supplierModalContent.classList.remove("opacity-100", "scale-100");
    supplierModalContent.classList.add("opacity-0", "scale-95");
    setTimeout(() => {
      supplierModal.classList.add("hidden");
      supplierForm.reset();
    }, 300);
  }

  closeSupplierModal.addEventListener("click", closeModal);
  cancelSupplierBtn.addEventListener("click", closeModal);
  window.addEventListener("click", (e) => {
    if (e.target === supplierModal) closeModal();
  });

  supplierForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const id = supplierIdInput.value;
    const name = nameInput.value.trim();
    const rawPhone = phoneInput.value.trim();
    const email = emailInput.value.trim();
    const address = addressInput.value.trim();

    if (!name) {
      Swal.fire("Validasi", "Nama supplier wajib diisi.", "warning");
      return;
    }

    let phone = "";
    if (rawPhone !== "") {
      if (!/^\d+$/.test(rawPhone)) {
        Swal.fire(
          "Nomor Telepon Salah",
          "Nomor hanya boleh angka tanpa spasi.",
          "warning"
        );
        return;
      }
      phone = "+62" + rawPhone;
    }

    if (email !== "") {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        Swal.fire(
          "Email Tidak Valid",
          "Masukkan email yang valid, contoh: user@gmail.com",
          "warning"
        );
        return;
      }
    }

    fetch(`../../backend/api/supplier/index.php?id=${id}`, {
      method: "POST",
      body: new URLSearchParams({
        _method: "PUT",
        name,
        phone,
        email,
        address,
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          Swal.fire("Berhasil", data.message, "success");
          closeModal();
          loadSuppliers();
        } else {
          Swal.fire("Gagal", data.message, "error");
        }
      })
      .catch((err) => {
        console.error("Update error:", err);
        Swal.fire("Error", err.message, "error");
      });
  });

  function deleteSupplier(id) {
    fetch(`../../backend/api/supplier/index.php?id=${id}`, {
      method: "POST",
      body: new URLSearchParams({ _method: "DELETE" }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          Swal.fire("Dihapus", data.message, "success");
          loadSuppliers();
        } else {
          throw new Error(data.message);
        }
      })
      .catch((err) => {
        console.error("Delete error:", err);
        Swal.fire("Error", err.message, "error");
      });
  }

  loadSuppliers();
});
