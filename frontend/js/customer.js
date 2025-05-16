document.addEventListener("DOMContentLoaded", () => {
  const customerTableBody = document.querySelector("#customerTable tbody");
  const customerModal = document.getElementById("customerModal");
  const customerModalContent = document.getElementById("customerModalContent");
  const closeCustomerModal = document.getElementById("closeCustomerModal");
  const cancelCustomerBtn = document.getElementById("cancelCustomerBtn");
  const customerForm = document.getElementById("customerForm");

  const customerIdInput = document.getElementById("customerId");
  const nameInput = document.getElementById("customerName");
  const phoneInput = document.getElementById("customerPhone");
  const emailInput = document.getElementById("customerEmail");
  const addressInput = document.getElementById("customerAddress");

  const paginationContainer = document.getElementById("paginationControls");
  const rowsPerPageSelect = document.getElementById("rowsPerPage");

  let allCustomerData = [];
  let currentPage = 1;
  let rowsPerPage = parseInt(rowsPerPageSelect?.value || 10);

  rowsPerPageSelect?.addEventListener("change", (e) => {
    rowsPerPage = parseInt(e.target.value);
    currentPage = 1;
    renderTable();
  });

  function renderTable() {
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const paginatedData = allCustomerData.slice(start, end);

    customerTableBody.innerHTML = "";

    paginatedData.forEach((customer, index) => {
      const row = document.createElement("tr");

      row.innerHTML = `
          <td class="px-4 py-2">${start + index + 1}</td>
          <td class="px-4 py-2">${customer.name || "-"}</td>
          <td class="px-4 py-2">${customer.phone || "-"}</td>
          <td class="px-4 py-2">${customer.email || "-"}</td>
          <td class="px-4 py-2">${customer.address || "-"}</td>
          <td class="px-4 py-2">
            <div class="flex items-center gap-3">
              <button class="editBtn" data-id="${customer.id}" title="Edit">
                <img src="../../assets/icons/edit_tabel.png" class="w-7 h-7 p-1 hover:scale-110 transition duration-150 ease-in-out" />
              </button>
              <button class="deleteBtn" data-id="${customer.id}" title="Delete">
                <img src="../../assets/icons/delete_tabel.png" class="w-7 h-7 p-1 hover:scale-110 transition duration-150 ease-in-out" />
              </button>
            </div>
          </td>
        `;

      customerTableBody.appendChild(row);
    });

    bindActions();
    renderPaginationControls();
  }

  function renderPaginationControls() {
    const container = document.getElementById("paginationControls");
    container.innerHTML = "";

    const totalPages = Math.ceil(allCustomerData.length / rowsPerPage);
    if (totalPages <= 1) return;

    const createButton = (label, isActive, disabled, onClick) => {
      const btn = document.createElement("button");
      btn.textContent = label;
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

    // Tombol « (prev)
    if (currentPage > 1) {
      container.appendChild(
        createButton("«", false, false, () => {
          currentPage--;
          renderTable();
        })
      );
    }

    // Nomor halaman
    for (let i = 1; i <= totalPages; i++) {
      container.appendChild(
        createButton(i, i === currentPage, false, () => {
          currentPage = i;
          renderTable();
        })
      );
    }

    // Tombol » (next)
    if (currentPage < totalPages) {
      container.appendChild(
        createButton("»", false, false, () => {
          currentPage++;
          renderTable();
        })
      );
    }
  }

  function bindActions() {
    document.querySelectorAll(".editBtn").forEach((btn) => {
      btn.addEventListener("click", () => {
        const id = btn.getAttribute("data-id");
        openEditModal(id);
      });
    });

    document.querySelectorAll(".deleteBtn").forEach((btn) => {
      btn.addEventListener("click", () => {
        const id = btn.getAttribute("data-id");
        confirmDelete(id);
      });
    });
  }

  function loadCustomers() {
    fetch("../../backend/api/customer/index.php")
      .then((res) => res.json())
      .then((data) => {
        allCustomerData = data;
        currentPage = 1;
        renderTable();
      })
      .catch((err) => {
        console.error("Error fetching customers:", err);
        Swal.fire("Error", "Gagal mengambil data customer", "error");
      });
  }

  function openEditModal(id) {
    fetch(`../../backend/api/customer/index.php?id=${id}`)
      .then((res) => res.json())
      .then((data) => {
        customerIdInput.value = data.id;
        nameInput.value = data.name || "";
        phoneInput.value = (data.phone || "").replace("+62", "");
        emailInput.value = data.email || "";
        addressInput.value = data.address || "";

        customerModal.classList.remove("hidden");
        setTimeout(() => {
          customerModalContent.classList.remove("opacity-0", "scale-95");
          customerModalContent.classList.add("opacity-100", "scale-100");
        }, 20);
      })
      .catch(() => {
        Swal.fire("Error", "Gagal mengambil detail customer", "error");
      });
  }

  function closeModal() {
    customerModalContent.classList.remove("opacity-100", "scale-100");
    customerModalContent.classList.add("opacity-0", "scale-95");
    setTimeout(() => {
      customerModal.classList.add("hidden");
      customerForm.reset();
    }, 300);
  }

  closeCustomerModal.addEventListener("click", closeModal);
  cancelCustomerBtn.addEventListener("click", closeModal);
  window.addEventListener("click", (e) => {
    if (e.target === customerModal) closeModal();
  });

  customerForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const id = customerIdInput.value;
    const name = nameInput.value.trim();
    const phoneRaw = phoneInput.value.trim();
    const email = emailInput.value.trim();
    const address = addressInput.value.trim();

    if (!name) {
      Swal.fire("Validasi", "Nama customer wajib diisi.", "warning");
      return;
    }

    let phone = "";
    if (phoneRaw !== "") {
      if (!/^\d+$/.test(phoneRaw)) {
        Swal.fire(
          "Nomor Telepon Salah",
          "Hanya boleh angka dan tanpa spasi.",
          "warning"
        );
        return;
      }
      phone = "+62" + phoneRaw;
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

    fetch(`../../backend/api/customer/index.php?id=${id}`, {
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
          Swal.fire(
            "Berhasil",
            data.message || "Customer berhasil diupdate",
            "success"
          );
          closeModal();
          loadCustomers();
        } else {
          throw new Error(data.message || "Gagal mengupdate");
        }
      })
      .catch((err) => {
        console.error("Update error:", err);
        Swal.fire("Error", err.message || "Gagal mengupdate data", "error");
      });
  });

  function confirmDelete(id) {
    Swal.fire({
      title: "Yakin ingin menghapus?",
      text: "Data customer akan dihapus secara permanen.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Ya, hapus!",
      cancelButtonText: "Batal",
    }).then((result) => {
      if (result.isConfirmed) deleteCustomer(id);
    });
  }

  function deleteCustomer(id) {
    fetch(`../../backend/api/customer/index.php?id=${id}`, {
      method: "POST",
      body: new URLSearchParams({ _method: "DELETE" }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          Swal.fire("Dihapus", data.message || "Customer dihapus", "success");
          loadCustomers();
        } else {
          throw new Error(data.message || "Gagal menghapus customer");
        }
      })
      .catch((err) => {
        console.error("Delete error:", err);
        Swal.fire("Error", err.message || "Gagal menghapus data", "error");
      });
  }

  loadCustomers();
});
