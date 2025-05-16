document.addEventListener("DOMContentLoaded", () => {
  const isReadonly = USER_ROLE === "Manager" || USER_ROLE === "Accounting";

  if (isReadonly) {
    document.getElementById("openCreatePurchase")?.classList.add("hidden");
  }
  const purchaseModal = document.getElementById("purchaseModal");
  const openPurchaseBtn = document.getElementById("openCreatePurchase");
  const closePurchaseBtn = document.getElementById("closePurchaseModal");
  const modalTitle = document.getElementById("purchaseModalTitle");
  const submitBtn = document.getElementById("submitPurchaseBtn");
  const purchaseForm = document.getElementById("purchaseForm");
  const purchaseItemsContainer = document.getElementById(
    "purchaseItemsContainer"
  );
  const grandTotalDisplay = document.getElementById("grandTotalDisplay");
  const paidInput = document.getElementById("paid");
  const dueInput = document.getElementById("due");
  const paymentStatus = document.getElementById("payment_status");
  const addProductRowBtn = document.getElementById("addProductRow");
  const cancelBtn = document.getElementById("cancelPurchaseBtn");

  cancelBtn.addEventListener("click", closeModal);

  paymentStatus.addEventListener("change", updateCalculation);
  paidInput.addEventListener("input", (e) => {
    formatNumberInput(e.target);
    updateCalculation();
  });

  flatpickr("#purchase_date", {
    dateFormat: "Y-m-d",
    maxDate: "today",
    defaultDate: new Date(),
  });

  flatpickr("#filterStartDate", {
    dateFormat: "Y-m-d",
    maxDate: "today",
  });

  flatpickr("#filterEndDate", {
    dateFormat: "Y-m-d",
    maxDate: "today",
  });

  openPurchaseBtn.addEventListener("click", () => {
    resetPurchaseForm();
    generateAutoInvoiceNo();
    modalTitle.textContent = "Create Purchase";
    submitBtn.textContent = "Save Purchase";

    purchaseModal.classList.remove("hidden");

    const modalContent = document.getElementById("purchaseModalContent");
    setTimeout(() => {
      modalContent.classList.remove("opacity-0", "scale-95");
      modalContent.classList.add("opacity-100", "scale-100");
    }, 20);
  });

  closePurchaseBtn.addEventListener("click", closeModal);
  window.addEventListener("click", (e) => {
    if (e.target === purchaseModal) closeModal();
  });

  addProductRowBtn.addEventListener("click", () => {
    addProductRow();
  });

  function getStatusBadge(status) {
    let color = "bg-gray-200 text-gray-800";
    if (status === "received") color = "bg-green-100 text-green-700";
    else if (status === "ordered") color = "bg-yellow-100 text-yellow-700";
    else if (status === "pending") color = "bg-red-100 text-red-700";
    return `<span class="px-3 py-1 rounded-md text-xs font-semibold ${color}">${status}</span>`;
  }

  function getPaymentStatusBadge(paymentStatus) {
    let color = "bg-gray-200 text-gray-800";
    if (paymentStatus === "paid") color = "bg-green-100 text-green-700";
    else if (paymentStatus === "partial")
      color = "bg-yellow-100 text-yellow-700";
    else if (paymentStatus === "unpaid") color = "bg-red-100 text-red-700";
    return `<span class="px-3 py-1 rounded-md text-xs font-semibold ${color}">${paymentStatus}</span>`;
  }

  let currentPagePurchase = 1;
  let rowsPerPagePurchase = 10;

  async function fetchAndRenderPurchases() {
    try {
      const url = new URL(
        "/perubahan/backend/api/purchase/index.php",
        window.location.origin
      );
      url.searchParams.set("path", "purchase");
      url.searchParams.set("page", currentPagePurchase);
      url.searchParams.set("limit", rowsPerPagePurchase);

      const res = await fetch(url);
      const result = await res.json();

      if (!result.success || !result.data) return;

      const tbody = document.getElementById("purchaseTableBody");
      tbody.innerHTML = "";

      result.data.forEach((purchase) => {
        const tr = document.createElement("tr");
        const totalItems = purchase.items?.length || 0;

        tr.innerHTML = `
        <td class="px-4 py-2">${purchase.invoice_no}</td>
        <td class="px-4 py-2">${purchase.date}</td>
        <td class="px-4 py-2">${purchase.supplier_name || "-"}</td>
        <td class="px-4 py-2">${totalItems}</td>
        <td class="px-4 py-2 text-center">${getStatusBadge(
          purchase.status
        )}</td>
        <td class="px-4 py-2 text-right">Rp ${Number(
          purchase.grand_total
        ).toLocaleString("id-ID")}</td>
        <td class="px-4 py-2 text-right">Rp ${Number(
          purchase.paid
        ).toLocaleString("id-ID")}</td>
        <td class="px-4 py-2 text-right">Rp ${Number(
          purchase.due
        ).toLocaleString("id-ID")}</td>
        <td class="px-4 py-2 text-center">${getPaymentStatusBadge(
          purchase.payment_status
        )}</td>
        <td class="px-4 py-2 text-center">
  <div class="flex justify-center gap-2">
    <button class="viewPurchaseBtn" data-id="${purchase.id}">
      <img src="../../assets/icons/show_tabel.png" class="w-8 h-8 p-1 hover:scale-110 transition" />
    </button>
    ${
      !isReadonly
        ? `
      <button class="editPurchaseBtn" data-id="${purchase.id}">
        <img src="../../assets/icons/edit_tabel.png" class="w-7 h-7 p-1 hover:scale-110 transition" />
      </button>
      ${
        purchase.status !== "received"
          ? `<button class="deletePurchaseBtn" data-id="${purchase.id}">
               <img src="../../assets/icons/delete_tabel.png" class="w-8 h-8 p-1 hover:scale-110 transition" />
             </button>`
          : ""
      }`
        : ""
    }
  </div>
</td>
      `;
        tbody.appendChild(tr);
      });

      renderPaginationPurchase(result.total, result.page, result.total_pages);
      populateFilterDropdowns();
    } catch (err) {
      console.error("Gagal memuat purchase:", err);
    }
  }

  function renderPaginationPurchase(totalItems, page, totalPages) {
    const container = document.getElementById("purchasePaginationContainer");
    container.innerHTML = "";

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

    // Tombol « Previous (hanya tampil jika bukan halaman pertama)
    if (page > 1) {
      container.appendChild(
        createButton("«", false, false, () => {
          currentPagePurchase--;
          fetchAndRenderPurchases();
        })
      );
    }

    // Tombol Nomor Halaman
    for (let i = 1; i <= totalPages; i++) {
      container.appendChild(
        createButton(i, i === page, false, () => {
          currentPagePurchase = i;
          fetchAndRenderPurchases();
        })
      );
    }

    // Tombol » Next (hanya tampil jika bukan halaman terakhir)
    if (page < totalPages) {
      container.appendChild(
        createButton("»", false, false, () => {
          currentPagePurchase++;
          fetchAndRenderPurchases();
        })
      );
    }
  }

  // Bind event to dropdown ID from your HTML
  document
    .getElementById("purchaseRowsPerPage")
    ?.addEventListener("change", (e) => {
      rowsPerPagePurchase = parseInt(e.target.value);
      currentPagePurchase = 1;
      fetchAndRenderPurchases();
    });

  function populateFilterDropdowns() {
    const supplierSet = new Set();
    const invoiceSet = new Set();

    document.querySelectorAll("#purchaseTableBody tr").forEach((row) => {
      const invoice = row.children[0]?.textContent.trim();
      const supplier = row.children[2]?.textContent.trim();

      if (invoice) invoiceSet.add(invoice);
      if (supplier) supplierSet.add(supplier);
    });

    const supplierSelect = document.getElementById("filterSupplier");
    const invoiceSelect = document.getElementById("filterInvoice");

    supplierSelect.innerHTML = '<option value="">-- Semua Supplier --</option>';
    invoiceSelect.innerHTML = '<option value="">-- Semua Invoice --</option>';

    [...supplierSet].sort().forEach((name) => {
      const opt = document.createElement("option");
      opt.value = name;
      opt.textContent = name;
      supplierSelect.appendChild(opt);
    });

    [...invoiceSet].sort().forEach((inv) => {
      const opt = document.createElement("option");
      opt.value = inv;
      opt.textContent = inv;
      invoiceSelect.appendChild(opt);
    });
  }

  function resetPurchaseForm() {
    purchaseForm.reset();
    document.getElementById("purchase_id").value = "";
    purchaseItemsContainer.innerHTML = "";
    grandTotalDisplay.textContent = "Rp0";
    paidInput.value = 0;
    dueInput.value = 0;
    paidInput.disabled = true;
    paymentStatus.value = "unpaid";
  }

  function closeModal() {
    const modalContent = document.getElementById("purchaseModalContent");
    modalContent.classList.remove("opacity-100", "scale-100");
    modalContent.classList.add("opacity-0", "scale-95");

    setTimeout(() => {
      purchaseModal.classList.add("hidden");
    }, 300);
  }

  FormData.prototype.appendAllToUrl = function (url) {
    const params = new URLSearchParams();
    for (const [key, value] of this.entries()) {
      params.append(key, value);
    }
    return `${url}&${params.toString()}`;
  };

  if (isReadonly) {
    // Sembunyikan tombol Create Purchase
    document.getElementById("openCreatePurchase")?.classList.add("hidden");

    // Optional: disable fungsi form, agar tidak bisa submit
    purchaseForm.addEventListener("submit", (e) => {
      e.preventDefault();
      Swal.fire({
        icon: "error",
        title: "Akses Ditolak",
        text: "Role Anda tidak memiliki izin untuk menyimpan data pembelian.",
      });
    });
  }

  purchaseForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const formData = new FormData();
    const invoice = document.getElementById("invoice_no").value.trim();
    const date = document.getElementById("purchase_date").value;
    const supplier = document.getElementById("supplier_name").value.trim();
    const status = document.getElementById("status").value;
    const paymentStat = paymentStatus.value;
    const paidVal = paidInput.value.replace(/\D/g, "") || 0;
    const dueVal = dueInput.value || 0;
    const grandTotal = grandTotalDisplay.textContent.replace(/\D/g, "") || 0;
    const notes = document.getElementById("notes").value;

    if (!invoice || !date || !supplier) {
      Swal.fire(
        "Oops!",
        "Invoice, Tanggal, dan Supplier wajib diisi.",
        "warning"
      );
      return;
    }

    const productRows = purchaseItemsContainer.querySelectorAll(".grid");
    if (productRows.length === 0) {
      Swal.fire("Oops!", "Tambahkan minimal 1 produk.", "warning");
      return;
    }

    let hasError = false;
    formData.append("invoice_no", invoice);
    formData.append("date", date);
    formData.append("supplier_name", supplier);
    formData.append("status", status);
    formData.append("payment_status", paymentStat);
    formData.append("paid", paidVal);
    formData.append("grand_total", grandTotal);
    formData.append("due", dueVal);
    formData.append("notes", notes);

    const purchaseId = document.getElementById("purchase_id").value;
    if (purchaseId) {
      formData.append("purchase_id", purchaseId);
    }

    productRows.forEach((row, index) => {
      const name = row
        .querySelector("input[name='product_name[]']")
        .value.trim();
      const qty = row.querySelector("input[name='quantity[]']").value || "0";
      const cost =
        row
          .querySelector("input[name='unit_price[]']")
          .value.replace(/\D/g, "") || "0";

      if (!name || parseInt(qty) <= 0) hasError = true;

      formData.append(`products[${index}][name]`, name);
      formData.append(`products[${index}][quantity]`, qty);
      formData.append(`products[${index}][unit_price]`, cost);
    });

    if (hasError) {
      Swal.fire("Error", "Nama produk dan quantity harus valid.", "warning");
      return;
    }

    let result;

    try {
      const isEdit = purchaseId !== "";
      console.log("purchase_id:", purchaseId); // <--- tambahkan di sini
      console.log("isEdit:", isEdit); // <--- dan ini

      if (isEdit) {
        const response = await fetch(
          "../../backend/api/purchase/index.php?path=purchase&method=PUT",
          {
            method: "POST",
            body: formData,
          }
        );
        result = await response.json();
      } else {
        const response = await fetch(
          "../../backend/api/purchase/index.php?path=purchase",
          {
            method: "POST",
            body: formData,
          }
        );
        result = await response.json();
      }

      if (result.success) {
        Swal.fire({
          icon: "success",
          title: result.message || "Berhasil disimpan",
          timer: 1500,
          showConfirmButton: false,
        });
        fetchAndRenderPurchases();
        closeModal();
      } else {
        throw new Error(result.message || "Terjadi kesalahan");
      }
    } catch (err) {
      Swal.fire("Gagal", err.message, "error");
    }
  });

  fetchAndRenderPurchases();

  // ============ Modal Filter Logic =============
  const openFilterBtn = document.getElementById("openFilterModal");
  const modal = document.getElementById("purchaseFilterModal");
  const modalContent = document.getElementById("purchaseFilterContent");
  const applyFilterBtn = document.getElementById("applyPurchaseFilter");
  const clearFilterBtn = document.getElementById("clearPurchaseFilter");
  const closeFilterBtn = document.getElementById("closeFilterModalBtn");
  const grandMinInput = document.getElementById("filterGrandMin");
  const grandMaxInput = document.getElementById("filterGrandMax");

  // === Animasi buka modal
  openFilterBtn?.addEventListener("click", () => {
    modal.classList.remove("hidden");
    setTimeout(() => {
      modalContent?.classList.remove("opacity-0", "scale-95");
      modalContent?.classList.add("opacity-100", "scale-100");
    }, 20);
  });

  // === Klik di luar konten modal untuk close
  window.addEventListener("click", (e) => {
    if (e.target === modal) closeFilterModal();
  });

  // === Tombol X close
  closeFilterBtn?.addEventListener("click", closeFilterModal);

  // === Fungsi tutup modal dengan animasi
  function closeFilterModal() {
    modalContent?.classList.remove("opacity-100", "scale-100");
    modalContent?.classList.add("opacity-0", "scale-95");
    setTimeout(() => {
      modal.classList.add("hidden");
    }, 300);
  }

  // === Format input ke Rupiah saat diketik
  function formatRupiahInput(input) {
    let value = input.value.replace(/[^\d]/g, "");
    if (value) {
      input.value = "Rp " + parseInt(value).toLocaleString("id-ID");
    } else {
      input.value = "";
    }
  }

  grandMinInput?.addEventListener("input", () =>
    formatRupiahInput(grandMinInput)
  );
  grandMaxInput?.addEventListener("input", () =>
    formatRupiahInput(grandMaxInput)
  );

  // === Tombol Apply Filter
  applyFilterBtn?.addEventListener("click", () => {
    const getCleanNumber = (val) =>
      parseFloat((val || "").replace(/[^\d]/g, "")) || 0;

    const startDate = document.getElementById("filterStartDate").value;
    const endDate = document.getElementById("filterEndDate").value;
    const supplier = document
      .getElementById("filterSupplier")
      .value.toLowerCase();
    const invoice = document
      .getElementById("filterInvoice")
      .value.toLowerCase();
    const status = document.getElementById("filterStatus").value;
    const paymentStatus = document.getElementById("filterPaymentStatus").value;
    const grandMin = getCleanNumber(grandMinInput.value);
    const grandMax = getCleanNumber(grandMaxInput.value) || Infinity;
    const minItems =
      parseInt(document.getElementById("filterMinItems").value) || 0;

    const rows = document.querySelectorAll("#purchaseTableBody tr");

    rows.forEach((row) => {
      const cells = row.children;
      const rowInvoice = cells[0].textContent.trim().toLowerCase();
      const rowDate = cells[1].textContent.trim();
      const rowSupplier = cells[2].textContent.trim().toLowerCase();
      const rowItems = parseInt(cells[3].textContent.trim()) || 0;
      const rowStatus = cells[4].textContent.trim().toLowerCase();
      const rawGrand = cells[5].textContent || "";
      const rowGrandTotal = getCleanNumber(rawGrand);
      const rowPaymentStatus = cells[8].textContent.trim().toLowerCase();

      const matchDate =
        (!startDate || new Date(rowDate) >= new Date(startDate)) &&
        (!endDate || new Date(rowDate) <= new Date(endDate));
      const matchInvoice = !invoice || rowInvoice.includes(invoice);
      const matchSupplier = !supplier || rowSupplier.includes(supplier);
      const matchStatus = !status || rowStatus === status;
      const matchPayment = !paymentStatus || rowPaymentStatus === paymentStatus;
      const matchGrand = rowGrandTotal >= grandMin && rowGrandTotal <= grandMax;
      const matchItems = rowItems >= minItems;

      const visible =
        matchDate &&
        matchInvoice &&
        matchSupplier &&
        matchStatus &&
        matchPayment &&
        matchGrand &&
        matchItems;

      row.style.display = visible ? "" : "none";
    });

    closeFilterModal();
  });

  // === Tombol Clear Filter
  clearFilterBtn?.addEventListener("click", () => {
    document
      .querySelectorAll(
        "#purchaseFilterModal input, #purchaseFilterModal select"
      )
      .forEach((input) => {
        input.value = "";
      });

    document
      .querySelectorAll("#purchaseTableBody tr")
      .forEach((row) => (row.style.display = ""));
  });

  const supplierInput = document.getElementById("supplier_name");
  const suggestionBox = document.createElement("ul");
  suggestionBox.id = "supplierSuggestions";
  suggestionBox.className =
    "absolute z-50 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-48 overflow-y-auto hidden text-sm w-full text-gray-900 dark:text-white";

  supplierInput.parentElement.appendChild(suggestionBox);

  supplierInput.addEventListener("input", async () => {
    const keyword = supplierInput.value.trim();
    if (!keyword) {
      suggestionBox.classList.add("hidden");
      return;
    }

    try {
      const res = await fetch(
        `../../backend/api/purchase/index.php?path=search_suppliers&q=${encodeURIComponent(
          keyword
        )}`
      );
      const data = await res.json();

      suggestionBox.innerHTML = "";
      if (data.success && data.data.length > 0) {
        data.data.forEach((name) => {
          const item = document.createElement("li");
          item.textContent = name;
          item.className =
            "px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer";
          item.addEventListener("click", () => {
            supplierInput.value = name;
            suggestionBox.classList.add("hidden");
          });
          suggestionBox.appendChild(item);
        });
        suggestionBox.classList.remove("hidden");
      } else {
        suggestionBox.classList.add("hidden");
      }
    } catch (err) {
      console.error("Gagal mengambil data supplier:", err);
      suggestionBox.classList.add("hidden");
    }
  });

  document.addEventListener("click", (e) => {
    if (
      !supplierInput.contains(e.target) &&
      !suggestionBox.contains(e.target)
    ) {
      suggestionBox.classList.add("hidden");
    }
  });

  function addProductRow(name = "", qty = 1, cost = 0, subtotal = 0) {
    const row = document.createElement("div");
    row.className =
      "grid grid-cols-12 gap-2 items-center py-2 border-b border-gray-200 dark:border-gray-600";
    row.innerHTML = `
        <div class="col-span-4 pl-2">
          <input type="text" name="product_name[]" placeholder="Product" required value="${name}"
            class="w-full p-2 border rounded-md bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white" />
        </div>
        <div class="col-span-2">
          <input type="number" name="quantity[]" min="1" value="${qty}"
            class="w-full p-2 text-center border rounded-md bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white quantity-input" required />
        </div>
        <div class="col-span-2">
          <input type="text" name="unit_price[]" value="${cost}"
            class="w-full p-2 text-center border rounded-md bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white cost-input" required />
        </div>
        <div class="col-span-3">
          <input type="text" name="subtotal[]" value="Rp${subtotal.toLocaleString(
            "id-ID"
          )}" readonly
            class="w-full p-2 text-center border rounded-md bg-gray-100 dark:bg-gray-600 border-gray-300 dark:border-gray-500 text-gray-900 dark:text-white subtotal-display" />
        </div>
        <div class="col-span-1 text-center">
          <button type="button" class="remove-row text-red-600 hover:text-red-800 text-2xl font-bold leading-none">×</button>
        </div>
      `;
    purchaseItemsContainer.appendChild(row);

    const productInput = row.querySelector("input[name='product_name[]']");
    enableProductAutocomplete(productInput);

    row
      .querySelector("input[name='unit_price[]']")
      .addEventListener("input", (e) => {
        formatNumberInput(e.target);
        updateCalculation();
      });

    row
      .querySelector("input[name='quantity[]']")
      .addEventListener("input", updateCalculation);

    row.querySelector(".remove-row").addEventListener("click", () => {
      row.remove();
      updateCalculation();
    });

    updateCalculation();
  }

  function formatNumberInput(input) {
    let value = input.value.replace(/\D/g, "");
    value = value ? parseInt(value).toLocaleString("id-ID") : "";
    input.value = value;
  }

  function updateCalculation() {
    const rows = purchaseItemsContainer.querySelectorAll(".grid");
    let grandTotal = 0;

    rows.forEach((row) => {
      const qty =
        parseFloat(row.querySelector("input[name='quantity[]']").value) || 0;
      const costRaw = row
        .querySelector("input[name='unit_price[]']")
        .value.replace(/\D/g, "");
      const cost = parseFloat(costRaw) || 0;
      const subtotal = qty * cost;

      row.querySelector(
        "input[name='subtotal[]']"
      ).value = `Rp${subtotal.toLocaleString("id-ID", {
        maximumFractionDigits: 0,
      })}`;

      grandTotal += subtotal;
    });

    grandTotalDisplay.textContent = `Rp${grandTotal.toLocaleString("id-ID")}`;

    const status = paymentStatus.value;
    let paid = 0;

    if (status === "paid") {
      paid = grandTotal;
      paidInput.value = paid.toLocaleString("id-ID");
      paidInput.disabled = true;
    } else if (status === "unpaid") {
      paid = 0;
      paidInput.value = 0;
      paidInput.disabled = true;
    } else if (status === "partial") {
      paidInput.disabled = false;
      const raw = paidInput.value.replace(/\D/g, "");
      paid = parseFloat(raw) || 0;
    }

    const due = grandTotal - paid;
    dueInput.value = due.toLocaleString("id-ID");
  }

  function enableProductAutocomplete(input) {
    const suggestionBox = document.createElement("ul");
    suggestionBox.className =
      "absolute z-50 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-48 overflow-y-auto hidden text-sm w-full text-gray-900 dark:text-white";
    suggestionBox.style.top = "100%";
    suggestionBox.style.left = "0";

    input.parentElement.style.position = "relative";
    input.parentElement.appendChild(suggestionBox);

    input.addEventListener("input", async () => {
      const keyword = input.value.trim();
      if (!keyword) {
        suggestionBox.classList.add("hidden");
        return;
      }

      try {
        const res = await fetch(
          `../../backend/api/purchase/index.php?path=search_products&q=${encodeURIComponent(
            keyword
          )}`
        );
        const data = await res.json();

        suggestionBox.innerHTML = "";
        if (data.success && data.data.length > 0) {
          data.data.forEach((name) => {
            const item = document.createElement("li");
            item.textContent = name;
            item.className =
              "px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer";
            item.addEventListener("click", () => {
              input.value = name;
              suggestionBox.classList.add("hidden");
            });
            suggestionBox.appendChild(item);
          });
          suggestionBox.classList.remove("hidden");
        } else {
          suggestionBox.classList.add("hidden");
        }
      } catch (err) {
        console.error("Gagal mengambil data produk:", err);
        suggestionBox.classList.add("hidden");
      }
    });

    document.addEventListener("click", (e) => {
      if (!input.contains(e.target) && !suggestionBox.contains(e.target)) {
        suggestionBox.classList.add("hidden");
      }
    });
  }

  document.addEventListener("click", async (e) => {
    if (e.target.closest(".editPurchaseBtn")) {
      const id = e.target.closest(".editPurchaseBtn").dataset.id;

      try {
        const res = await fetch(
          `../../backend/api/purchase/index.php?path=purchase&id=${id}`
        );
        const result = await res.json();
        if (!result.success)
          throw new Error("Gagal mengambil detail pembelian.");

        const data = result.data;

        resetPurchaseForm();
        modalTitle.textContent = "Edit Purchase";
        submitBtn.textContent = "Update Purchase";

        // Isi form dengan data dari API
        document.getElementById("purchase_id").value = data.id;
        document.getElementById("invoice_no").value = data.invoice_no;
        document.getElementById("purchase_date")._flatpickr.setDate(data.date);
        document.getElementById("supplier_name").value = data.supplier_name;
        document.getElementById("status").value = data.status;
        paymentStatus.value = data.payment_status;

        paidInput.disabled = paymentStatus.value !== "partial";
        paidInput.value = Number(data.paid).toLocaleString("id-ID");
        dueInput.value = Number(data.due).toLocaleString("id-ID");
        grandTotalDisplay.textContent = `Rp${Number(
          data.grand_total
        ).toLocaleString("id-ID")}`;
        document.getElementById("notes").value = data.notes || "";

        // Tampilkan produk
        purchaseItemsContainer.innerHTML = "";
        data.items.forEach((item) => {
          addProductRow(item.product_name, item.quantity, item.unit_price);
        });

        // Tampilkan modal
        purchaseModal.classList.remove("hidden");
        const modalContent = document.getElementById("purchaseModalContent");
        setTimeout(() => {
          modalContent.classList.remove("opacity-0", "scale-95");
          modalContent.classList.add("opacity-100", "scale-100");
        }, 20);
      } catch (err) {
        Swal.fire("Error", err.message, "error");
      }
    }
  });

  document.addEventListener("click", async (e) => {
    if (e.target.closest(".deletePurchaseBtn")) {
      const id = e.target.closest(".deletePurchaseBtn").dataset.id;

      const confirm = await Swal.fire({
        icon: "warning",
        title: "Yakin ingin menghapus?",
        showCancelButton: true,
        confirmButtonText: "Ya, hapus",
      });

      if (!confirm.isConfirmed) return;

      try {
        const res = await fetch(
          `../../backend/api/purchase/index.php?path=purchase&id=${id}`,
          {
            method: "DELETE",
          }
        );
        const result = await res.json();

        if (result.success) {
          Swal.fire("Berhasil", result.message, "success");
          fetchAndRenderPurchases();
        } else {
          throw new Error(result.message || "Gagal menghapus");
        }
      } catch (err) {
        Swal.fire("Gagal", err.message, "error");
      }
    }
  });

  //VIEW PURCHASE
  document.addEventListener("click", async (e) => {
    if (e.target.closest(".viewPurchaseBtn")) {
      const id = e.target.closest(".viewPurchaseBtn").dataset.id;

      try {
        const res = await fetch(
          `../../backend/api/purchase/index.php?path=purchase&id=${id}`
        );
        const result = await res.json();
        if (!result.success)
          throw new Error("Gagal mengambil detail pembelian");

        const data = result.data;
        const returnBtn = document.querySelector(".returnBtn");
        if (returnBtn) {
          if (isReadonly) {
            returnBtn.classList.add("hidden");
          } else {
            returnBtn.setAttribute("data-id", data.id);
            returnBtn.classList.remove("hidden");
          }
        }

        // 🔄 Set info
        document.getElementById("view_date").textContent = data.date;
        document.getElementById("view_invoice").textContent = data.invoice_no;
        document.getElementById("view_supplier").textContent =
          data.supplier_name || "-";
        document.getElementById("view_grand_total").textContent = `Rp${Number(
          data.grand_total
        ).toLocaleString("id-ID")}`;
        document.getElementById("view_paid").textContent = `Rp${Number(
          data.paid
        ).toLocaleString("id-ID")}`;
        document.getElementById("view_due").textContent = `Rp${Number(
          data.due
        ).toLocaleString("id-ID")}`;

        // 🔄 Status badges
        const statusBadge = document.getElementById("view_status");
        const paymentBadge = document.getElementById("view_payment_status");

        const statusColor =
          {
            received: "bg-green-100 text-green-700",
            ordered: "bg-yellow-100 text-yellow-700",
            pending: "bg-red-100 text-red-700",
          }[data.status] || "bg-gray-200 text-gray-800";

        const paymentColor =
          {
            paid: "bg-green-100 text-green-700",
            partial: "bg-yellow-100 text-yellow-700",
            unpaid: "bg-red-100 text-red-700",
          }[data.payment_status] || "bg-gray-200 text-gray-800";

        statusBadge.className = `inline-block text-xs font-semibold px-2 py-1 rounded-md ${statusColor}`;
        paymentBadge.className = `inline-block text-xs font-semibold px-2 py-1 rounded-md ${paymentColor}`;

        statusBadge.textContent = data.status;
        paymentBadge.textContent = data.payment_status;

        // 🔄 Isi tabel item
        const tbody = document.getElementById("viewPurchaseItems");
        tbody.innerHTML = "";

        let no = 1;
        for (const item of data.items) {
          const tr = document.createElement("tr");
          tr.innerHTML = `
            <td class="px-4 py-2 text-gray-700 dark:text-gray-100 text-center">${String(
              no
            ).padStart(2, "0")}.</td>
            <td class="px-4 py-2">${item.sku || "-"}</td>
            <td class="px-4 py-2">${item.product_name}</td>
            <td class="px-4 py-2 text-center">${item.quantity}</td>
            <td class="px-4 py-2 text-right">Rp ${Number(
              item.unit_price
            ).toLocaleString("id-ID")}</td>
            <td class="px-4 py-2 text-right">Rp ${(
              item.unit_price * item.quantity
            ).toLocaleString("id-ID")}</td>
          `;
          tbody.appendChild(tr);
          no++;
        }

        // 🔄 Tampilkan modal
        const viewModal = document.getElementById("viewPurchaseModal");
        const modalContent = document.getElementById("viewPurchaseContent");

        viewModal.classList.remove("hidden");
        setTimeout(() => {
          modalContent.classList.remove("opacity-0", "scale-95");
          modalContent.classList.add("opacity-100", "scale-100");
        }, 20);
      } catch (err) {
        Swal.fire("Gagal", err.message, "error");
      }
    }
  });

  const closeViewBtn = document.getElementById("closeViewPurchaseModal");
  closeViewBtn.addEventListener("click", () => {
    const modal = document.getElementById("viewPurchaseModal");
    const content = document.getElementById("viewPurchaseContent");
    content.classList.remove("opacity-100", "scale-100");
    content.classList.add("opacity-0", "scale-95");
    setTimeout(() => {
      modal.classList.add("hidden");
    }, 300);
  });

  //Logika Button Print Purchase Detail
  document.getElementById("printPurchaseBtn").addEventListener("click", () => {
    const content = document.getElementById("viewPurchaseContent");
    const clone = content.cloneNode(true);

    // Hapus tombol aksi
    clone.querySelectorAll("button, .no-print").forEach((el) => el.remove());

    const style = `
        <style>
    body {
      font-family: Arial, sans-serif;
      color: #333;
      padding: 20px;
    }

    h1 {
      text-align: center;
      font-size: 24px;
      margin-bottom: 24px;
    }

    .info {
      display: flex;
      justify-content: space-between;
      margin-bottom: 24px;
      font-size: 14px;
    }

    .info div {
      line-height: 1.6;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      font-size: 14px;
    }

    th, td {
      border: 1px solid #ccc;
      padding: 8px 10px;
    }

    th {
      background-color: #f5f5f5;
      font-weight: 600;
      text-align: center;
    }

    td:nth-child(1) {
      text-align: center;
      width: 40px;
    }

    td:nth-child(4),
    td:nth-child(5),
    td:nth-child(6) {
      text-align: right;
      white-space: nowrap;
    }

    .summary {
      margin-top: 30px;
      width: 280px;
      padding: 14px 18px;
      border: 1px solid #ccc;
      border-radius: 6px;
      float: right;
      font-size: 14px;
      background-color: #fafafa;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
    }

    .summary-row.total {
      font-weight: bold;
      font-size: 16px;
      border-top: 1px dashed #bbb;
      padding-top: 8px;
      margin-top: 14px;
    }

    .summary-row .label {
      color: #444;
    }

    .summary-row .value {
      text-align: right;
    }

    .summary-row .due {
      color: red;
      font-weight: 600;
    }

    .print-logo {
    text-align: center;
    margin-bottom: 10px;
  }

  .print-logo img {
    height: 60px;
    max-width: 200px;
    object-fit: contain;
  }

  @media print {
    .print-logo {
      display: block;
    }
  }
  </style>
      `;

    const invoice = clone.querySelector("#view_invoice")?.textContent || "";
    const date = clone.querySelector("#view_date")?.textContent || "";
    const supplier = clone.querySelector("#view_supplier")?.textContent || "";
    const status = clone.querySelector("#view_status")?.textContent || "";
    const payStatus =
      clone.querySelector("#view_payment_status")?.textContent || "";
    const grandTotal =
      clone.querySelector("#view_grand_total")?.textContent || "";
    const paid = clone.querySelector("#view_paid")?.textContent || "";
    const due = clone.querySelector("#view_due")?.textContent || "";
    const tbody = clone.querySelector("#viewPurchaseItems")?.innerHTML || "";

    const html = `
          <div class="print-logo">
    <img src="../../assets/icons/logo_dim.png" alt="Logo" style="height: 60px; margin: 0 auto; display: block;">
  </div>
    <h1>Purchase Detail</h1>
    <div class="info">
      <div>
        <strong>DATE:</strong> ${date}<br>
        <strong>STATUS:</strong> ${status}<br>
        <strong>INVOICE NO:</strong> ${invoice}
      </div>
      <div>
        <strong>PAYMENT STATUS:</strong> ${payStatus}<br>
        <strong>SUPPLIER:</strong> ${supplier}
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>No</th>
          <th>SKU</th>
          <th>Product</th>
          <th>Qty</th>
          <th>Cost</th>
          <th>Subtotal</th>
        </tr>
      </thead>
      <tbody>
        ${tbody}
      </tbody>
    </table>

    <div class="summary">
      <div class="summary-row total">
        <span class="label">Total:</span>
        <span class="value">${grandTotal}</span>
      </div>
      <div class="summary-row">
        <span class="label">Paid:</span>
        <span class="value">${paid}</span>
      </div>
      <div class="summary-row">
        <span class="label">Due:</span>
        <span class="value due">${due}</span>
      </div>
    </div>
      `;

    const printWindow = window.open("", "_blank");
    printWindow.document.write(
      `<html><head><title>Print Purchase</title>${style}</head><body>${html}</body></html>`
    );
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
  });

  document.addEventListener("click", (e) => {
    const returnBtn = e.target.closest(".returnBtn");
    if (returnBtn) {
      const viewModal = document.getElementById("viewPurchaseModal");
      const returnModal = document.getElementById("returnPurchaseModal");

      if (viewModal) viewModal.classList.add("hidden");
      if (returnModal) returnModal.classList.remove("hidden");

      document.getElementById("return_items").innerHTML = "";
      returnProductOptions = [];

      document.getElementById("return_date").textContent =
        document.getElementById("view_date").textContent;
      document.getElementById("return_status").textContent =
        document.getElementById("view_status").textContent;
      document.getElementById("return_invoice").textContent =
        document.getElementById("view_invoice").textContent;
      document.getElementById("return_payment_status").textContent =
        document.getElementById("view_payment_status").textContent;
      document.getElementById("return_supplier").textContent =
        document.getElementById("view_supplier").textContent;
      document.getElementById("return_purchase_id").value =
        returnBtn.getAttribute("data-id") || "";

      const rows = document.querySelectorAll("#viewPurchaseItems tr");
      rows.forEach((tr) => {
        const sku = tr.children[1]?.textContent?.trim() || "-";
        const productName = tr.children[2]?.textContent?.trim() || "";
        const qty = parseInt(tr.children[3]?.textContent?.trim() || "0");
        const unitPriceText = tr.children[4]?.textContent?.trim() || "0";
        const unitPrice = parseInt(unitPriceText.replace(/[^\d]/g, "")) || 0;

        if (productName && sku && qty > 0 && unitPrice > 0) {
          returnProductOptions.push({
            product_name: productName,
            unit_price: unitPrice,
            sku: sku,
            quantity: qty,
          });
        }
      });
    }

    if (e.target?.id === "addReturnRow") {
      addReturnProductRow();
    }

    if (e.target?.classList.contains("delete-return-row")) {
      e.target.closest(".grid")?.remove();
      updateReturnTotal();
    }
  });

  let returnProductOptions = [];

  function addReturnProductRow() {
    const tbody = document.getElementById("return_items");
    const rowCount = tbody.querySelectorAll(".grid").length + 1;
    const row = document.createElement("div");
    row.className = "grid grid-cols-12 items-center px-4 py-2 gap-2";

    const productOptions = returnProductOptions
      .map(
        (item) => `
          <option value="${item.product_name}" 
            data-price="${item.unit_price}" 
            data-sku="${item.sku}" 
            data-max="${item.quantity}">
            ${item.product_name}
          </option>`
      )
      .join("");

    row.innerHTML = `
        <div class="col-span-1 text-center text-sm text-gray-700 dark:text-white">${String(
          rowCount
        ).padStart(2, "0")}.</div>
        <div class="col-span-1 text-center text-xs font-mono text-gray-500 dark:text-gray-300 return-sku">-</div>
        <div class="col-span-3">
          <select class="w-full px-2 py-1 border rounded text-sm return-product
  bg-white text-gray-900 border-gray-300
  dark:bg-gray-700 dark:text-white dark:border-gray-600">
            <option value="" disabled selected>Pilih Produk</option>
            ${productOptions}
          </select>
        </div>
        <div class="col-span-2">
          <input type="number" value="1" min="1" class="w-full px-2 py-1 border border-gray-300 rounded text-sm return-qty">
        </div>
        <div class="col-span-2">
          <input type="text" value="Rp0" readonly
            class="w-full px-2 py-1 border border-gray-300 rounded text-sm return-cost bg-gray-100 text-gray-700 text-right">
        </div>
        <div class="col-span-2 text-right text-sm font-semibold return-subtotal">Rp0</div>
        <div class="col-span-1 text-center">
          <button class="text-red-500 hover:text-red-700 text-sm delete-return-row">✕</button>
        </div>`;

    tbody.appendChild(row);
    attachReturnRowListeners(row);
  }

  function attachReturnRowListeners(row) {
    const qtyInput = row.querySelector(".return-qty");
    const productSelect = row.querySelector(".return-product");

    qtyInput.addEventListener("input", () => {
      const selectedOption = productSelect.selectedOptions[0];
      const maxQty = parseInt(selectedOption?.getAttribute("data-max") || "0");
      let val = parseInt(qtyInput.value);
      if (val < 1) val = 1;
      if (val > maxQty) val = maxQty;
      qtyInput.value = val;
      updateReturnTotal();
    });

    productSelect.addEventListener("change", () => {
      const selectedOption = productSelect.selectedOptions[0];
      if (!selectedOption) return;

      const price = parseInt(selectedOption.getAttribute("data-price") || "0");
      const sku = selectedOption.getAttribute("data-sku") || "-";

      row.querySelector(".return-sku").textContent = sku;
      row.querySelector(".return-cost").value = `Rp${price.toLocaleString(
        "id-ID"
      )}`;
      updateReturnTotal();
    });
  }

  function updateReturnTotal() {
    const rows = document.querySelectorAll("#return_items .grid");
    let total = 0;

    rows.forEach((row) => {
      const qtyInput = row.querySelector(".return-qty");
      const costInput = row.querySelector(".return-cost");
      const subtotalEl = row.querySelector(".return-subtotal");

      const qty = parseInt(qtyInput?.value || "0");
      const cost =
        parseInt((costInput?.value || "0").replace(/[^\d]/g, "")) || 0;
      const subtotal = qty * cost;

      subtotalEl.textContent = `Rp${subtotal.toLocaleString("id-ID")}`;
      total += subtotal;
    });

    const totalDisplay = document.getElementById("return_grand_total");
    if (totalDisplay) {
      totalDisplay.textContent = `Rp ${total.toLocaleString("id-ID")}`;
    }
  }

  document
    .getElementById("returnPurchaseForm")
    ?.addEventListener("submit", async (e) => {
      e.preventDefault();

      const invoice =
        document.getElementById("return_invoice")?.textContent?.trim() || "";
      const supplier =
        document.getElementById("return_supplier")?.textContent?.trim() || "";
      const status =
        document
          .getElementById("return_status")
          ?.textContent?.trim()
          .toLowerCase() || "";
      const paymentStatus =
        document.getElementById("return_payment_status")?.textContent?.trim() ||
        "";
      const purchaseId = document
        .getElementById("return_purchase_id")
        ?.value?.trim();

      const items = document.querySelectorAll("#return_items .grid");
      const returnData = new FormData();

      if (!invoice || !supplier || items.length === 0 || !purchaseId) {
        Swal.fire("Oops!", "Data return tidak lengkap.", "warning");
        return;
      }

      let total = 0;
      let valid = true;

      items.forEach((row, index) => {
        const productSelect = row.querySelector(".return-product");
        const qtyInput = row.querySelector(".return-qty");
        const priceInput = row.querySelector(".return-cost");

        const selectedOption = productSelect?.selectedOptions?.[0];

        if (!selectedOption) {
          valid = false;
          return;
        }

        const sku = selectedOption.getAttribute("data-sku") || "";
        const quantity = parseInt(qtyInput?.value || "0");
        const unit_price = parseInt(
          (priceInput?.value || "0").replace(/[^\d]/g, "")
        );
        const productName = selectedOption?.value || "";

        if (sku === "" || quantity <= 0 || unit_price <= 0) {
          valid = false;
        }

        returnData.append(`items[${index}][sku]`, sku);
        returnData.append(`items[${index}][quantity]`, quantity);
        returnData.append(`items[${index}][unit_price]`, unit_price);
        returnData.append(`items[${index}][product_name]`, productName);

        total += quantity * unit_price;
      });

      if (!valid) {
        Swal.fire(
          "Invalid",
          "Semua produk harus valid dan quantity > 0.",
          "warning"
        );
        return;
      }

      returnData.append("purchase_id", purchaseId);
      returnData.append("invoice_no", invoice);
      returnData.append("supplier_name", supplier);
      returnData.append("status", status);
      returnData.append("payment_status", paymentStatus);
      returnData.append("total", total);

      try {
        const res = await fetch(
          "../../backend/api/purchase/index.php?path=purchase_return",
          {
            method: "POST",
            body: returnData,
          }
        );
        const result = await res.json();

        if (result.success) {
          Swal.fire("Sukses", "Return berhasil disimpan", "success");
          document
            .getElementById("returnPurchaseModal")
            .classList.add("hidden");
        } else {
          throw new Error(result.message || "Gagal menyimpan");
        }
      } catch (err) {
        Swal.fire("Gagal", err.message, "error");
      }
    });

  // Tombol X (closeReturnModalBtn) hanya menutup modal return
  document
    .getElementById("closeReturnModalBtn")
    .addEventListener("click", () => {
      document.getElementById("returnPurchaseModal").classList.add("hidden");
    });

  // Tombol Cancel: tutup modal return & buka kembali modal view purchase
  document.getElementById("cancelReturnBtn")?.addEventListener("click", () => {
    document.getElementById("returnPurchaseModal")?.classList.add("hidden");

    const viewModal = document.getElementById("viewPurchaseModal");
    const modalContent = document.getElementById("viewPurchaseContent");

    if (viewModal && modalContent) {
      viewModal.classList.remove("hidden");
      setTimeout(() => {
        modalContent.classList.remove("opacity-0", "scale-95");
        modalContent.classList.add("opacity-100", "scale-100");
      }, 20);
    }
  });

  document.querySelectorAll(".sortable").forEach((header) => {
    header.addEventListener("click", () => {
      const column = header.dataset.column;
      const currentSort = header.dataset.sort || "none";

      // Urutan rotasi: none -> asc -> desc -> none
      let newSort =
        currentSort === "none"
          ? "asc"
          : currentSort === "asc"
          ? "desc"
          : "none";

      // Reset semua sort dan icon
      document.querySelectorAll(".sortable").forEach((h) => {
        h.dataset.sort = "none";
        updateSortIcon(h, "none");
      });

      header.dataset.sort = newSort;
      updateSortIcon(header, newSort);
      sortPurchaseTable(column, newSort);
    });
  });

  function updateSortIcon(header, sortState) {
    const isDarkMode = window.matchMedia(
      "(prefers-color-scheme: dark)"
    ).matches;

    const imgLight = header.querySelector(".sort-icon-light");
    const imgDark = header.querySelector(".sort-icon-dark");

    let filename = "sort-alt";
    if (sortState === "asc") filename = "sort-alpha-up";
    else if (sortState === "desc") filename = "sort-alpha-down";

    if (imgLight)
      imgLight.src = `../../assets/icons/lightmode/${filename}-light.png`;
    if (imgDark)
      imgDark.src = `../../assets/icons/darkmode/${filename}-dark.png`;
  }

  function sortPurchaseTable(column, order) {
    const tbody = document.getElementById("purchaseTableBody");
    const rows = Array.from(tbody.querySelectorAll("tr"));

    const colIndexMap = {
      invoice_no: 0,
      date: 1,
      supplier: 2,
      items: 3,
      status: 4,
      grand_total: 5,
      paid: 6,
      due: 7,
      payment_status: 8,
    };

    const colIndex = colIndexMap[column];

    rows.sort((a, b) => {
      const valA = a.children[colIndex]?.textContent?.trim() || "";
      const valB = b.children[colIndex]?.textContent?.trim() || "";

      // Tentukan jenis kolom
      const numericColumns = ["items", "grand_total", "paid", "due"];
      const alphaColumns = ["supplier", "status", "payment_status"];
      const dateColumns = ["date"];

      if (numericColumns.includes(column)) {
        const numA = parseFloat(valA.replace(/[^\d.-]/g, "")) || 0;
        const numB = parseFloat(valB.replace(/[^\d.-]/g, "")) || 0;
        return order === "asc" ? numA - numB : numB - numA;
      }

      if (alphaColumns.includes(column)) {
        return order === "asc"
          ? valA.localeCompare(valB)
          : valB.localeCompare(valA);
      }

      if (dateColumns.includes(column)) {
        const dateA = new Date(valA);
        const dateB = new Date(valB);
        return order === "asc" ? dateA - dateB : dateB - dateA;
      }

      // Default fallback (string compare)
      return order === "asc"
        ? valA.localeCompare(valB)
        : valB.localeCompare(valA);
    });

    rows.forEach((row) => tbody.appendChild(row));
  }

  const searchInput = document.getElementById("searchPurchase");

  searchInput.addEventListener("input", () => {
    const keyword = searchInput.value.trim().toLowerCase();
    const rows = document.querySelectorAll("#purchaseTableBody tr");

    rows.forEach((row) => {
      const invoice = row.children[0]?.textContent.toLowerCase() || "";
      const supplier = row.children[2]?.textContent.toLowerCase() || "";

      const isMatch = invoice.includes(keyword) || supplier.includes(keyword);
      row.style.display = isMatch ? "" : "none";
    });
  });

  async function generateAutoInvoiceNo() {
    try {
      const res = await fetch(
        "../../backend/api/purchase/index.php?path=generate_invoice"
      );
      const result = await res.json();
      if (result.success) {
        document.getElementById("invoice_no").value = result.invoice_no;
      }
    } catch (err) {
      console.error("Gagal generate invoice:", err);
    }
  }
});
