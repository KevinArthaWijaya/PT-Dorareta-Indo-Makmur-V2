document.addEventListener("DOMContentLoaded", () => {
  const tableBody = document.querySelector("#stockReportTable tbody");
  const filterCategory = document.getElementById("filterCategory");
  const resetBtn = document.getElementById("resetFilters");
  const rowsPerPageSelect = document.getElementById("rowsPerPage");
  const paginationContainer = document.getElementById("paginationControls");
  const exportExcelBtn = document.getElementById("exportExcel");
  const printBtn = document.getElementById("printReport");

  let allData = [];
  let currentPage = 1;
  let rowsPerPage = parseInt(rowsPerPageSelect.value);

  function fetchStockReport() {
    fetch("../../backend/api/report/stock.php")
      .then((res) => res.json())
      .then((data) => {
        allData = data;
        renderTable();
      })
      .catch((err) => {
        console.error("Gagal mengambil data stok:", err);
        tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-red-500">Gagal memuat data.</td></tr>`;
      });
  }

  function renderTable() {
    const keyword = filterCategory.value.toLowerCase();

    let filtered = allData.filter((item) =>
      item.category?.toLowerCase().includes(keyword)
    );

    const start = (currentPage - 1) * rowsPerPage;
    const paginated = filtered.slice(start, start + rowsPerPage);

    tableBody.innerHTML = "";

    if (paginated.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-gray-500">Tidak ada data.</td></tr>`;
      return;
    }

    paginated.forEach((item) => {
      const row = document.createElement("tr");
      row.innerHTML = `
          <td class="px-3 py-2 whitespace-nowrap text-sm">${item.sku}</td>
          <td class="px-3 py-2 text-sm">${item.product_name}</td>
          <td class="px-3 py-2 text-sm">${item.category || "-"}</td>
          <td class="px-3 py-2 text-center text-sm">${item.stock_quantity}</td>
          <td class="px-3 py-2 text-center text-sm">${
            item.min_stock_warning
          }</td>
          <td class="px-3 py-2 text-center text-sm">${stockStatus(
            item.stock,
            item.minimum_stock
          )}</td>
        `;
      tableBody.appendChild(row);
    });

    renderPagination(filtered.length);
  }

  function stockStatus(stock, min) {
    stock = parseInt(stock);
    min = parseInt(min);

    if (stock < min) {
      return `<span class="px-2 py-1 rounded text-xs font-semibold bg-red-100 text-red-700">Out of Stock</span>`;
    } else if (stock < min * 2) {
      return `<span class="px-2 py-1 rounded text-xs font-semibold bg-yellow-100 text-yellow-700">Low Stock</span>`;
    } else {
      return `<span class="px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-700">In Stock</span>`;
    }
  }

  function renderPagination(totalItems) {
    paginationContainer.innerHTML = "";
    const totalPages = Math.ceil(totalItems / rowsPerPage);
    if (totalPages <= 1) return;

    const createBtn = (label, active, onClick) => {
      const btn = document.createElement("button");
      btn.textContent = label;
      btn.className = `
          w-8 h-8 flex items-center justify-center rounded-md text-sm font-semibold
          ${
            active
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

  // Export Excel
  exportExcelBtn.addEventListener("click", () => {
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.table_to_sheet(
      document.getElementById("stockReportTable")
    );
    XLSX.utils.book_append_sheet(wb, ws, "StockReport");
    XLSX.writeFile(wb, "stock_report.xlsx");
  });

  // Print
  printBtn.addEventListener("click", () => {
    const table = document.getElementById("stockReportTable").outerHTML;
    const date = new Date().toLocaleDateString("id-ID");

    const style = `
        <style>
          body { font-family: Arial, sans-serif; color: #333; padding: 20px; }
          header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
          header img { height: 50px; }
          h1 { font-size: 20px; text-align: right; margin: 0; }
          table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
          th, td { border: 1px solid #ccc; padding: 6px 8px; }
          th { background-color: #f2f2f2; text-align: center; }
          td:nth-child(4), td:nth-child(5), td:nth-child(6) {
            text-align: center;
          }
        </style>`;

    const html = `
        <header>
          <img src="../../assets/icons/logo_dim.png" alt="Logo">
          <h1>Laporan Stok Produk<br><small style="font-weight: normal">Dicetak: ${date}</small></h1>
        </header>
        ${table}
      `;

    const win = window.open("", "_blank");
    win.document.write(
      `<html><head><title>Cetak Laporan Stok</title>${style}</head><body>${html}</body></html>`
    );
    win.document.close();
    win.focus();
    win.print();
    win.close();
  });

  // Event Bindings
  rowsPerPageSelect.addEventListener("change", (e) => {
    rowsPerPage = parseInt(e.target.value);
    currentPage = 1;
    renderTable();
  });

  filterCategory.addEventListener("input", () => {
    currentPage = 1;
    renderTable();
  });

  resetBtn.addEventListener("click", () => {
    filterCategory.value = "";
    currentPage = 1;
    renderTable();
  });

  // Initial load
  fetchStockReport();
});
