document.addEventListener("DOMContentLoaded", () => {
  const isReadonly = USER_ROLE === "Manager" || USER_ROLE === "Accounting";
  if (isReadonly) {
    document.getElementById("openCreateProduct")?.classList.add("hidden");
    document.querySelector(".btn-import")?.classList.add("hidden");
    document.getElementById("importFile")?.setAttribute("disabled", "disabled");
  }
  const openModalBtn = document.getElementById("openCreateProduct");
  const modal = document.getElementById("productModal");
  const modalContent = document.getElementById("productModalContent");
  const closeModalBtn = document.getElementById("closeProductModal");
  const form = document.getElementById("productForm");
  const sellingPriceInput = document.getElementById("selling_price");
  const stockInput = document.getElementById("stock_quantity");
  const statusSelect = document.getElementById("status");
  const productIdInput = document.getElementById("product_id");
  let currentSortColumn = null;
  let currentSortDirection = "default";
  let currentPage = 1;
  let rowsPerPage = 10;

  fetchAndRenderProducts();

  async function loadProducts() {
    try {
      const res = await fetch(
        "../../backend/api/products/index.php?path=products"
      );
      const data = await res.json();

      const tbody = document.getElementById("productTableBody");
      tbody.innerHTML = "";

      data.forEach((product) => {
        const row = document.createElement("tr");
        row.className = `hover:bg-gray-50 dark:hover:bg-gray-700 ${
          product.highlight_color === "yellow"
            ? "bg-yellow-100 dark:bg-yellow-700"
            : ""
        }`;

        const isLowStock = product.stock_quantity < product.min_stock_warning;
        const statusLabel =
          product.status === "inactive" ? "Inactive" : "Active";
        const statusClass =
          product.status === "inactive"
            ? "bg-red-100 text-red-700"
            : "bg-green-100 text-green-700";

        const imagePath = product.product_image?.startsWith("uploads/")
          ? `${location.origin}/perubahan/backend/${product.product_image}`
          : `${location.origin}/perubahan/assets/image/default_product.png`;

        row.innerHTML = `
          <td class="px-4 py-2"><img src="${imagePath}" alt="Product" class="w-10 h-10 object-cover rounded" /></td>
          <td class="px-4 py-2">${product.product_name}</td>
          <td class="px-4 py-2">${product.sku}</td>
          <td class="px-4 py-2">${product.category_name}</td>
          <td class="px-4 py-2">${product.unit_name}</td>
          <td class="px-4 py-2">Rp ${parseInt(
            product.selling_price
          ).toLocaleString("id-ID")}</td>
          <td class="px-4 py-2 ${
            isLowStock ? "text-red-600 font-semibold" : ""
          }">${product.stock_quantity}</td>
          <td class="px-4 py-2 text-center align-middle">
            <span class="inline-block text-[13px] font-semibold px-3 py-[2px] rounded-md w-[76px] text-center ${statusClass}">${statusLabel}</span>
          </td>
          <td class="px-4 py-3 text-center bg-transparent">
            <div class="flex justify-center gap-2">
  ${
    !isReadonly
      ? `
    <button class="editProductBtn" data-id="${product.id}" title="Edit">
      <img src="../../assets/icons/edit_tabel.png" class="w-7 h-7 p-1 hover:scale-110 transition" />
    </button>
    <button class="deleteProductBtn" data-id="${product.id}" title="Delete">
      <img src="../../assets/icons/delete_tabel.png" class="w-8 h-8 p-1 hover:scale-110 transition" />
    </button>
  `
      : ""
  }
</div>
          </td>
        `;
        tbody.appendChild(row);
      });
    } catch (err) {
      console.error("Gagal load data produk", err);
    }
  }

  openModalBtn.addEventListener("click", () => {
    form.reset();
    fetchCategoriesAndUnits().then(() => {
      document.getElementById("modalTitle").textContent = "Create Product";
      document.getElementById("saveProductBtn").textContent = "Create";
      if (productIdInput) productIdInput.value = "";
      document.getElementById("status").value = "active";
      form.removeAttribute("data-original-category");
      form.removeAttribute("data-original-sku");

      modal.classList.remove("hidden");
      modal.classList.add("flex");
      setTimeout(() => {
        modalContent.classList.remove("scale-95", "opacity-0");
        modalContent.classList.add("scale-100", "opacity-100");
      }, 10);
    });
  });

  const closeModal = () => {
    modalContent.classList.remove("scale-100", "opacity-100");
    modalContent.classList.add("scale-95", "opacity-0");
    setTimeout(() => {
      modal.classList.add("hidden");
      modal.classList.remove("flex");
    }, 300);
  };

  closeModalBtn.addEventListener("click", closeModal);
  document
    .getElementById("cancelModalBtn")
    ?.addEventListener("click", closeModal);
  window.addEventListener("click", (e) => {
    if (e.target === modal) closeModal();
  });

  stockInput.addEventListener("input", () => {
    const value = parseInt(stockInput.value) || 0;
    statusSelect.value = value === 0 ? "inactive" : "active";
  });

  sellingPriceInput.addEventListener("input", (e) => {
    const raw = e.target.value.replace(/[^\d]/g, "");
    e.target.value = raw ? parseInt(raw).toLocaleString("id-ID") : "";
  });

  form.addEventListener("submit", async (e) => {
    if (isReadonly) {
      return Swal.fire({
        icon: "error",
        title: "Akses ditolak",
        text: "Role Anda tidak memiliki izin untuk membuat atau mengedit produk.",
      });
    }
    e.preventDefault();
    const formData = new FormData(form);

    const productName = formData.get("product_name").trim();
    const sku = formData.get("sku").trim();
    const categoryId = formData.get("category_id");
    const unitId = formData.get("unit_id");
    const sellingPrice = formData.get("selling_price").trim();
    const minimalPurchase = formData.get("minimal_purchase").trim();
    const stockQuantity = parseInt(formData.get("stock_quantity"));
    const minStockWarning = formData.get("min_stock_warning").trim();
    let status = formData.get("status") || "active";

    if (
      !productName ||
      !sku ||
      !categoryId ||
      !unitId ||
      !sellingPrice ||
      !minimalPurchase ||
      !minStockWarning
    ) {
      return Swal.fire({
        icon: "warning",
        title: "Semua field wajib diisi kecuali image.",
      });
    }

    if (stockQuantity === 0) status = "inactive";

    formData.set("status", status);
    formData.set("selling_price", sellingPrice.replace(/[^\d]/g, ""));

    try {
      const res = await fetch("../../backend/api/products/index.php", {
        method: "POST",
        body: formData,
      });
      const result = await res.json();

      if (result.status) {
        const isEdit = !!formData.get("product_id");
        Swal.fire({
          icon: "success",
          title: isEdit
            ? "Produk berhasil diubah"
            : "Produk berhasil ditambahkan",
          timer: 1500,
          showConfirmButton: false,
        });
        closeModal();
        form.reset();
        fetchAndRenderProducts();
      } else {
        Swal.fire({
          icon: "error",
          title: "Gagal",
          text: result.message || "Terjadi kesalahan.",
        });
      }
    } catch (error) {
      console.error("Submit Error:", error);
      Swal.fire({ icon: "error", title: "Gagal terhubung ke server" });
    }
  });

  form.addEventListener("reset", () => {
    statusSelect.value = "active";
    document.getElementById("sku").value = "";
  });

  async function fetchCategoriesAndUnits() {
    try {
      const [catRes, unitRes] = await Promise.all([
        fetch("../../backend/api/products/index.php?path=categories"),
        fetch("../../backend/api/products/index.php?path=units"),
      ]);
      const [categoryJson, unitJson] = await Promise.all([
        catRes.json(),
        unitRes.json(),
      ]);
      const categories = categoryJson.data || [];
      const units = unitJson.data || [];

      const categorySelect = document.getElementById("category_id");
      const unitSelect = document.getElementById("unit_id");

      categorySelect.innerHTML = '<option value="">-- Select --</option>';
      unitSelect.innerHTML = '<option value="">-- Select --</option>';

      categories.forEach((cat) => {
        const option = document.createElement("option");
        option.value = cat.id;
        option.textContent = cat.name;
        categorySelect.appendChild(option);
      });

      units.forEach((unit) => {
        const option = document.createElement("option");
        option.value = unit.id;
        option.textContent = unit.name;
        unitSelect.appendChild(option);
      });
    } catch (err) {
      console.error("Gagal memuat data kategori/unit", err);
    }
  }

  document
    .getElementById("category_id")
    .addEventListener("change", async function () {
      const selectedCategoryId = this.value;
      const skuInput = document.getElementById("sku");

      const productId = document.getElementById("product_id")?.value || "";
      const originalCategoryId =
        form.getAttribute("data-original-category") || "";
      const originalSku = form.getAttribute("data-original-sku") || "";

      if (!selectedCategoryId) {
        skuInput.value = "";
        return;
      }

      if (productId && selectedCategoryId === originalCategoryId) {
        skuInput.value = originalSku;
        return;
      }

      try {
        const res = await fetch(
          `../../backend/api/products/index.php?path=generate_sku&category_id=${selectedCategoryId}`
        );
        const data = await res.json();
        skuInput.value = data?.sku || "";
      } catch (err) {
        console.error("Gagal mengambil SKU otomatis", err);
      }
    });

  document.addEventListener("click", async (e) => {
    if (e.target.closest(".editProductBtn")) {
      const id = e.target.closest(".editProductBtn").dataset.id;
      try {
        const res = await fetch(
          `../../backend/api/products/index.php?path=single_product&id=${id}`
        );
        const data = await res.json();

        form.reset();
        fetchCategoriesAndUnits().then(() => {
          document.getElementById("modalTitle").textContent = "Edit Product";
          document.getElementById("saveProductBtn").textContent =
            "Save Changes";
          document.getElementById("product_id").value = data.id;
          document.getElementById("product_name").value = data.product_name;
          document.getElementById("sku").value = data.sku;
          document.getElementById("category_id").value = data.category_id;
          document.getElementById("unit_id").value = data.unit_id;
          document.getElementById("selling_price").value = parseInt(
            data.selling_price
          ).toLocaleString("id-ID");
          document.getElementById("minimal_purchase").value =
            data.minimal_purchase;
          document.getElementById("stock_quantity").value = data.stock_quantity;
          document.getElementById("min_stock_warning").value =
            data.min_stock_warning;
          document.getElementById("status").value = data.status;

          form.setAttribute("data-original-category", data.category_id);
          form.setAttribute("data-original-sku", data.sku);

          modal.classList.remove("hidden");
          modal.classList.add("flex");
          setTimeout(() => {
            modalContent.classList.remove("scale-95", "opacity-0");
            modalContent.classList.add("scale-100", "opacity-100");
          }, 10);
        });
      } catch (error) {
        console.error("Gagal mengambil data produk", error);
        Swal.fire({ icon: "error", title: "Gagal memuat data produk" });
      }
    }
  });

  document.addEventListener("click", async (e) => {
    if (e.target.closest(".deleteProductBtn")) {
      const id = e.target.closest(".deleteProductBtn").dataset.id;

      const confirm = await Swal.fire({
        title: "Yakin ingin menghapus produk ini?",
        text: "Tindakan ini tidak dapat dibatalkan.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Ya, hapus!",
        cancelButtonText: "Batal",
      });

      if (confirm.isConfirmed) {
        try {
          const res = await fetch(
            `../../backend/api/products/index.php?path=delete_product&id=${id}`,
            { method: "DELETE" }
          );
          const result = await res.json();

          if (result.status) {
            Swal.fire({
              icon: "success",
              title: "Produk berhasil dihapus",
              timer: 1500,
              showConfirmButton: false,
            });
            fetchAndRenderProducts();
          } else {
            Swal.fire({
              icon: "error",
              title: "Gagal menghapus",
              text: result.message || "Terjadi kesalahan.",
            });
          }
        } catch (err) {
          console.error("Delete Error:", err);
          Swal.fire({ icon: "error", title: "Gagal menghapus produk" });
        }
      }
    }
  });

  // ==========================================
  // 🔍 FILTER PRODUK (Open Modal + Apply)
  // ==========================================
  const openFilterModalBtn = document.getElementById("openFilterModal");
  const filterModal = document.getElementById("filterProductModal");
  const filterModalContent = document.getElementById("filterModalContent");
  const closeFilterModalBtn = document.getElementById("closeFilterModal");
  const filterForm = document.getElementById("filterProductForm");
  const clearFilterBtn = document.getElementById("clearFilterBtn");

  let lastFilterValues = {
    sku: "",
    category: "",
    unit: "",
    stockCondition: "",
    status: "",
  };

  openFilterModalBtn?.addEventListener("click", async () => {
    await loadFilterDropdowns();

    document.getElementById("filterSku").value = lastFilterValues.sku;
    document.getElementById("filterCategory").value = lastFilterValues.category;
    document.getElementById("filterUnit").value = lastFilterValues.unit;
    document.getElementById("filterStockCondition").value =
      lastFilterValues.stockCondition;
    document.getElementById("filterStatus").value = lastFilterValues.status;

    filterModal.classList.remove("hidden");
    filterModal.classList.add("flex");
    setTimeout(() => {
      filterModalContent.classList.remove("scale-95", "opacity-0");
      filterModalContent.classList.add("scale-100", "opacity-100");
    }, 10);
  });

  closeFilterModalBtn?.addEventListener("click", closeFilterModal);
  window.addEventListener("click", (e) => {
    if (e.target === filterModal) closeFilterModal();
  });

  function closeFilterModal() {
    filterModalContent.classList.remove("scale-100", "opacity-100");
    filterModalContent.classList.add("scale-95", "opacity-0");
    setTimeout(() => {
      filterModal.classList.add("hidden");
      filterModal.classList.remove("flex");
    }, 300);
  }

  clearFilterBtn?.addEventListener("click", () => {
    document.getElementById("filterSku").value = "";
    document.getElementById("filterCategory").value = "";
    document.getElementById("filterUnit").value = "";
    document.getElementById("filterStockCondition").value = "";
    document.getElementById("filterStatus").value = "";

    lastFilterValues = {
      sku: "",
      category: "",
      unit: "",
      stockCondition: "",
      status: "",
    };

    closeFilterModal();
    fetchAndRenderProducts();
  });

  filterForm?.addEventListener("submit", (e) => {
    e.preventDefault();
    applyFilter();
    closeFilterModal();
  });

  async function applyFilter() {
    const sku = document.getElementById("filterSku").value.trim();
    const category = document.getElementById("filterCategory").value;
    const unit = document.getElementById("filterUnit").value;
    const stockCondition = document.getElementById(
      "filterStockCondition"
    ).value;
    const status = document.getElementById("filterStatus").value;

    lastFilterValues = { sku, category, unit, stockCondition, status };

    const params = new URLSearchParams();
    if (sku) params.append("sku", sku);
    if (category) params.append("category", category);
    if (unit) params.append("unit", unit);
    if (stockCondition) params.append("stock_condition", stockCondition);
    if (status) params.append("status", status);

    try {
      const res = await fetch(
        `../../backend/api/products/index.php?path=filter&${params.toString()}`
      );
      const result = await res.json();

      if (result.status && result.data.length > 0) {
        renderFilteredProducts(result.data);
      } else {
        document.getElementById("productTableBody").innerHTML = `
          <tr><td colspan="9" class="text-center text-gray-500 dark:text-gray-300">Tidak ada produk ditemukan.</td></tr>`;
      }
    } catch (error) {
      console.error("Gagal filter produk:", error);
    }
  }

  function renderFilteredProducts(products) {
    const tbody = document.getElementById("productTableBody");
    tbody.innerHTML = "";

    products.forEach((product) => {
      const row = document.createElement("tr");
      row.className = `hover:bg-gray-50 dark:hover:bg-gray-700 ${
        product.highlight_color === "yellow"
          ? "bg-yellow-100 dark:bg-yellow-700"
          : ""
      }`;

      const isLowStock = product.stock_quantity < product.min_stock_warning;
      const statusLabel = product.status === "inactive" ? "Inactive" : "Active";
      const statusClass =
        product.status === "inactive"
          ? "bg-red-100 text-red-700"
          : "bg-green-100 text-green-700";

      const imagePath = product.product_image?.startsWith("uploads/")
        ? `${location.origin}/perubahan/backend/${product.product_image}`
        : `${location.origin}/perubahan/assets/image/default_product.png`;

      row.innerHTML = `
        <td class="px-4 py-2"><img src="${imagePath}" class="w-10 h-10 object-cover rounded" /></td>
        <td class="px-4 py-2">${product.product_name}</td>
        <td class="px-4 py-2">${product.sku}</td>
        <td class="px-4 py-2">${product.category_name || product.category}</td>
        <td class="px-4 py-2">${product.unit_name || product.unit}</td>
        <td class="px-4 py-2">Rp ${parseInt(
          product.selling_price
        ).toLocaleString("id-ID")}</td>
        <td class="px-4 py-2 ${
          isLowStock ? "text-red-600 font-semibold" : ""
        }">${product.stock_quantity}</td>
        <td class="px-4 py-2 text-center align-middle">
          <span class="inline-block text-[13px] font-semibold px-3 py-[2px] rounded-md w-[76px] text-center ${statusClass}">
            ${statusLabel}
          </span>
        </td>
        <td class="px-4 py-3 text-center">
  <div class="flex justify-center gap-2">
    ${
      !isReadonly
        ? `
        <button class="editProductBtn" data-id="${product.id}" title="Edit">
          <img src="../../assets/icons/edit_tabel.png" class="w-7 h-7 p-1 hover:scale-110 transition" />
        </button>
        <button class="deleteProductBtn" data-id="${product.id}" title="Delete">
          <img src="../../assets/icons/delete_tabel.png" class="w-8 h-8 p-1 hover:scale-110 transition" />
        </button>
      `
        : ""
    }
  </div>
</td>
      `;

      tbody.appendChild(row);
    });
  }

  async function loadFilterDropdowns() {
    const skuSelect = document.getElementById("filterSku");
    const categorySelect = document.getElementById("filterCategory");
    const unitSelect = document.getElementById("filterUnit");

    skuSelect.innerHTML = `<option value="">-- Semua --</option>`;
    categorySelect.innerHTML = `<option value="">-- Semua --</option>`;
    unitSelect.innerHTML = `<option value="">-- Semua --</option>`;

    try {
      const [productRes, categoryRes, unitRes] = await Promise.all([
        fetch("../../backend/api/products/index.php?path=products"),
        fetch("../../backend/api/products/index.php?path=categories"),
        fetch("../../backend/api/products/index.php?path=units"),
      ]);

      const productJson = await productRes.json();
      const categoryJson = await categoryRes.json();
      const unitJson = await unitRes.json();

      const products = productJson.data || [];
      const categories = categoryJson.data || [];
      const units = unitJson.data || [];

      // Ambil prefix SKU tanpa angka
      const skuPrefixes = new Set(
        products
          .map((p) => {
            const match = p.sku.match(/^[A-Z]+/);
            return match ? match[0] : null;
          })
          .filter((prefix) => prefix !== null)
      );

      skuPrefixes.forEach((prefix) => {
        const opt = document.createElement("option");
        opt.value = prefix;
        opt.textContent = prefix;
        skuSelect.appendChild(opt);
      });

      categories.forEach((cat) => {
        const opt = document.createElement("option");
        opt.value = cat.id;
        opt.textContent = cat.name;
        categorySelect.appendChild(opt);
      });

      units.forEach((unit) => {
        const opt = document.createElement("option");
        opt.value = unit.id;
        opt.textContent = unit.name;
        unitSelect.appendChild(opt);
      });
    } catch (err) {
      console.error("Gagal memuat dropdown filter", err);
    }
  }

  // ⬇️ Search
  const searchInput = document.getElementById("searchInput");
  searchInput?.addEventListener("input", async () => {
    const keyword = searchInput.value.trim();
    if (keyword === "") {
      fetchAndRenderProducts(); // tampilkan ulang semua
      return;
    }

    try {
      const res = await fetch(
        `../../backend/api/products/index.php?path=search&q=${encodeURIComponent(
          keyword
        )}`
      );
      const result = await res.json();

      if (result.status) {
        renderFilteredProducts(result.data);
      } else {
        document.getElementById("productTableBody").innerHTML = `
          <tr><td colspan="9" class="text-center text-gray-500 dark:text-gray-300">Tidak ada hasil pencarian.</td></tr>`;
      }
    } catch (error) {
      console.error("Gagal mencari produk:", error);
    }
  });

  // 🔁 Export to Excel
  const exportBtn = document.getElementById("exportExcelBtn");
  if (exportBtn) {
    exportBtn.addEventListener("click", () => {
      const table = document.getElementById("productTable");
      if (!table) return;

      let tableHTML = `<table border="1" style="border-collapse:collapse; width:100%; font-family:sans-serif;"><thead><tr style="background:#f3f4f6;">`;

      table.querySelectorAll("thead th").forEach((header) => {
        const text = header.innerText.trim();
        if (text !== "Action" && text !== "Image") {
          tableHTML += `<th style="padding:8px; border:1px solid #ccc;">${text}</th>`;
        }
      });

      tableHTML += "</tr></thead><tbody>";

      const rows = table.querySelectorAll("tbody tr");
      rows.forEach((row) => {
        tableHTML += "<tr>";
        row.querySelectorAll("td").forEach((cell, idx) => {
          if (idx !== 0 && idx !== row.cells.length - 1) {
            tableHTML += `<td style="padding:8px; border:1px solid #ccc;">${cell.innerText}</td>`;
          }
        });
        tableHTML += "</tr>";
      });

      tableHTML += "</tbody></table>";

      const blob = new Blob([tableHTML], { type: "application/vnd.ms-excel" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `data_produk_${new Date().getFullYear()}.xls`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    });
  }

  // Import Produk
  setupImportFunctionality();

  function setupImportFunctionality() {
    const importBtn = document.querySelector(".btn-import");
    const importFileInput = document.getElementById("importFile");

    if (!importBtn || !importFileInput) return;

    importBtn.addEventListener("click", () => importFileInput.click());

    importFileInput.addEventListener("change", function () {
      const file = this.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = function (e) {
        const data = e.target.result;
        const ext = file.name.split(".").pop().toLowerCase();

        let jsonData = [];
        if (ext === "csv") {
          jsonData = parseCSV(data).map(normalizeKeys);
        } else if (ext === "xls" || ext === "xlsx") {
          const workbook = XLSX.read(data, { type: "binary" });
          const sheet = workbook.Sheets[workbook.SheetNames[0]];
          jsonData = XLSX.utils
            .sheet_to_json(sheet, { defval: "" })
            .filter((row) => Object.values(row).some((val) => val !== ""))
            .map(normalizeKeys);
        } else {
          return Swal.fire(
            "Format tidak didukung",
            "Gunakan CSV/XLS/XLSX.",
            "error"
          );
        }

        sendImportToServer(jsonData);
      };

      reader.readAsBinaryString(file);
    });

    function parseCSV(csv) {
      const [header, ...rows] = csv.split("\n").filter(Boolean);
      const headers = header.split(/[,;]/).map((h) => h.trim());
      return rows.map((row) => {
        const values = row.split(/[,;]/);
        return headers.reduce((obj, key, i) => {
          obj[key] = values[i]?.trim() || "";
          return obj;
        }, {});
      });
    }

    function normalizeKeys(obj) {
      const cleaned = {};
      for (let key in obj) {
        const newKey = key.toLowerCase().replace(/\s+|\//g, "_");
        cleaned[newKey] = obj[key]?.toString().trim() || "";
      }
      return cleaned;
    }

    function sendImportToServer(data) {
      fetch("../../backend/api/products/index.php?path=import", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.status) {
            let html = `<strong>${res.message}</strong>`;
            if (res.failed.length > 0) {
              html += `<br><br><strong>Produk yang gagal:</strong><ul style="text-align:left;">`;
              res.failed.forEach((msg) => {
                html += `<li>${msg}</li>`;
              });
              html += `</ul>`;
            }

            Swal.fire({
              icon: "success",
              title: "Import Selesai!",
              html,
              width: 600,
              confirmButtonColor: "#4ade80",
            });
            fetchAndRenderProducts();
          } else {
            Swal.fire({
              icon: "error",
              title: "Import Gagal!",
              html: `<strong>${res.message}</strong><br><br>${(
                res.failed || []
              ).join("<br>")}`,
              width: 600,
            });
          }
        })
        .catch((err) => {
          console.error("Import Error:", err);
          Swal.fire("Oops!", "Gagal menghubungi server.", "error");
        });
    }
  }

  // Sorting
  document.querySelectorAll(".sortable").forEach((header) => {
    header.addEventListener("click", () => {
      const column = header.getAttribute("data-column");

      if (currentSortColumn === column) {
        currentSortDirection =
          currentSortDirection === "asc"
            ? "desc"
            : currentSortDirection === "desc"
            ? "default"
            : "asc";
      } else {
        currentSortColumn = column;
        currentSortDirection = "asc";
      }

      updateSortIcons();
      fetchAndRenderProducts(); // <--- ini HARUS ada
    });
  });

  function updateSortIcons() {
    document.querySelectorAll(".sortable").forEach((header) => {
      const column = header.getAttribute("data-column");
      const isActive = column === currentSortColumn;

      const light = header.querySelector(".sort-icon-light");
      const dark = header.querySelector(".sort-icon-dark");

      let iconName = "sort-alt";

      if (isActive) {
        if (currentSortDirection === "asc") iconName = "sort-alpha-up";
        else if (currentSortDirection === "desc") iconName = "sort-alpha-down";
      }

      if (light)
        light.src = `../../assets/icons/lightmode/${iconName}-light.png`;
      if (dark) dark.src = `../../assets/icons/darkmode/${iconName}-dark.png`;
    });
  }

  async function fetchAndRenderProducts() {
    let url = "../../backend/api/products/index.php?path=products";

    const params = new URLSearchParams();
    if (currentSortColumn && currentSortDirection !== "default") {
      params.append("sort_by", currentSortColumn);
      params.append("sort_dir", currentSortDirection);
    }
    params.append("page", currentPage);
    params.append("limit", rowsPerPage);

    url += "&" + params.toString();

    const res = await fetch(url);
    const result = await res.json();

    renderTable(result.data);
    renderPagination(result.total, result.page, result.total_pages);
  }

  document.getElementById("rowsPerPage").addEventListener("change", (e) => {
    rowsPerPage = parseInt(e.target.value);
    currentPage = 1;
    fetchAndRenderProducts();
  });

  function renderPagination(totalItems, page, totalPages) {
    const container = document.getElementById("paginationContainer");
    container.innerHTML = "";

    if (totalPages <= 1) return;

    // Tombol « Previous
    if (page > 1) {
      const prevBtn = document.createElement("button");
      prevBtn.innerHTML = "&laquo;";
      prevBtn.className =
        "px-3 py-1 rounded-md text-sm text-gray-800 dark:text-gray-300 hover:bg-red-100 hover:text-red-700 transition";
      prevBtn.addEventListener("click", () => {
        currentPage = page - 1;
        fetchAndRenderProducts();
      });
      container.appendChild(prevBtn);
    }

    // Tombol nomor halaman
    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement("button");
      btn.textContent = i;
      btn.className = `px-3 py-1 rounded-md text-sm font-medium ${
        i === page
          ? "bg-red-600 text-white"
          : "text-gray-800 dark:text-gray-300 hover:bg-red-100 hover:text-red-700"
      } transition`;
      btn.addEventListener("click", () => {
        currentPage = i;
        fetchAndRenderProducts();
      });
      container.appendChild(btn);
    }

    // Tombol » Next
    if (page < totalPages) {
      const nextBtn = document.createElement("button");
      nextBtn.innerHTML = "&raquo;";
      nextBtn.className =
        "px-3 py-1 rounded-md text-sm text-gray-800 dark:text-gray-300 hover:bg-red-100 hover:text-red-700 transition";
      nextBtn.addEventListener("click", () => {
        currentPage = page + 1;
        fetchAndRenderProducts();
      });
      container.appendChild(nextBtn);
    }
  }

  function renderTable(products) {
    const tbody = document.getElementById("productTableBody");
    tbody.innerHTML = "";

    products.forEach((product) => {
      const row = document.createElement("tr");
      row.className = `hover:bg-gray-50 dark:hover:bg-gray-700 ${
        product.highlight_color === "yellow"
          ? "bg-yellow-100 dark:bg-yellow-700"
          : ""
      }`;

      const isLowStock = product.stock_quantity < product.min_stock_warning;
      const statusLabel = product.status === "inactive" ? "Inactive" : "Active";
      const statusClass =
        product.status === "inactive"
          ? "bg-red-100 text-red-700"
          : "bg-green-100 text-green-700";

      const imagePath = product.product_image?.startsWith("uploads/")
        ? `${location.origin}/perubahan/backend/${product.product_image}`
        : `${location.origin}/perubahan/assets/image/default_product.png`;

      row.innerHTML = `
      <td class="px-4 py-2"><img src="${imagePath}" alt="Product" class="w-10 h-10 object-cover rounded" /></td>
      <td class="px-4 py-2">${product.product_name}</td>
      <td class="px-4 py-2">${product.sku}</td>
      <td class="px-4 py-2">${product.category_name}</td>
      <td class="px-4 py-2">${product.unit_name}</td>
      <td class="px-4 py-2">Rp ${parseInt(product.selling_price).toLocaleString(
        "id-ID"
      )}</td>
      <td class="px-4 py-2 ${isLowStock ? "text-red-600 font-semibold" : ""}">${
        product.stock_quantity
      }</td>
      <td class="px-4 py-2 text-center align-middle">
        <span class="inline-block text-[13px] font-semibold px-3 py-[2px] rounded-md w-[76px] text-center ${statusClass}">${statusLabel}</span>
      </td>
      <td class="px-4 py-3 text-center">
  <div class="flex justify-center gap-2">
    ${
      !isReadonly
        ? `
        <button class="editProductBtn" data-id="${product.id}" title="Edit">
          <img src="../../assets/icons/edit_tabel.png" class="w-7 h-7 p-1 hover:scale-110 transition" />
        </button>
        <button class="deleteProductBtn" data-id="${product.id}" title="Delete">
          <img src="../../assets/icons/delete_tabel.png" class="w-8 h-8 p-1 hover:scale-110 transition" />
        </button>
      `
        : ""
    }
  </div>
</td>
    `;

      tbody.appendChild(row);
    });
  }

  function renderTable(data) {
    const tbody = document.getElementById("productTableBody");
    tbody.innerHTML = "";

    data.forEach((product) => {
      const row = document.createElement("tr");
      row.className = `hover:bg-gray-50 dark:hover:bg-gray-700 ${
        product.highlight_color === "yellow"
          ? "bg-yellow-100 dark:bg-yellow-700"
          : ""
      }`;

      const isLowStock = product.stock_quantity < product.min_stock_warning;
      const statusLabel = product.status === "inactive" ? "Inactive" : "Active";
      const statusClass =
        product.status === "inactive"
          ? "bg-red-100 text-red-700"
          : "bg-green-100 text-green-700";

      const imagePath = product.product_image?.startsWith("uploads/")
        ? `${location.origin}/perubahan/backend/${product.product_image}`
        : `${location.origin}/perubahan/assets/image/default_product.png`;

      row.innerHTML = `
      <td class="px-4 py-2"><img src="${imagePath}" class="w-10 h-10 object-cover rounded" /></td>
      <td class="px-4 py-2">${product.product_name}</td>
      <td class="px-4 py-2">${product.sku}</td>
      <td class="px-4 py-2">${product.category_name}</td>
      <td class="px-4 py-2">${product.unit_name}</td>
      <td class="px-4 py-2">Rp ${parseInt(product.selling_price).toLocaleString(
        "id-ID"
      )}</td>
      <td class="px-4 py-2 ${isLowStock ? "text-red-600 font-semibold" : ""}">${
        product.stock_quantity
      }</td>
      <td class="px-4 py-2 text-center">
        <span class="inline-block text-[13px] font-semibold px-3 py-[2px] rounded-md w-[76px] text-center ${statusClass}">${statusLabel}</span>
      </td>
      <td class="px-4 py-3 text-center">
  <div class="flex justify-center gap-2">
    ${
      !isReadonly
        ? `
        <button class="editProductBtn" data-id="${product.id}" title="Edit">
          <img src="../../assets/icons/edit_tabel.png" class="w-7 h-7 p-1 hover:scale-110 transition" />
        </button>
        <button class="deleteProductBtn" data-id="${product.id}" title="Delete">
          <img src="../../assets/icons/delete_tabel.png" class="w-8 h-8 p-1 hover:scale-110 transition" />
        </button>
      `
        : ""
    }
  </div>
</td>
    `;
      tbody.appendChild(row);
    });
  }

  document
    .getElementById("cancelProductModalBtn")
    .addEventListener("click", () => {
      document.getElementById("productModal").classList.add("hidden");
    });
});
