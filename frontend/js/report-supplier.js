document.addEventListener("DOMContentLoaded", () => {
  const tableBody = document.querySelector("#supplierReportTable tbody");
  const filterInput = document.getElementById("filterKeyword");
  const resetBtn = document.getElementById("resetFilters");
  const rowsPerPageSelect = document.getElementById("rowsPerPage");
  const paginationContainer = document.getElementById("paginationControls");
  const exportExcelBtn = document.getElementById("exportExcel");
  const printBtn = document.getElementById("printReport");

  let allData = [];
  let currentPage = 1;
  let rowsPerPage = parseInt(rowsPerPageSelect.value);

  function fetchSupplierReport() {
    fetch("../../backend/api/report/supplier.php")
      .then((res) => res.json())
      .then((data) => {
        allData = data;
        renderTable();
      })
      .catch((err) => {
        console.error("Gagal mengambil data supplier:", err);
        tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-red-500">Gagal memuat data.</td></tr>`;
      });
  }

  function renderTable() {
    const keyword = filterInput.value.toLowerCase();
    const filtered = allData.filter((item) =>
      item.name.toLowerCase().includes(keyword)
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
          <td class="px-3 py-2 text-sm">${item.name}</td>
          <td class="px-3 py-2 text-sm">${item.phone || "-"}</td>
          <td class="px-3 py-2 text-sm">${item.email || "-"}</td>
          <td class="px-3 py-2 text-sm">${item.address || "-"}</td>
          <td class="px-3 py-2 text-center text-sm">${item.total_purchases}</td>
          <td class="px-3 py-2 text-right text-sm">Rp${formatNumber(
            item.total_amount
          )}</td>
        `;
      tableBody.appendChild(row);
    });

    renderPagination(filtered.length);
  }

  function renderPagination(totalItems) {
    paginationContainer.innerHTML = "";
    const totalPages = Math.ceil(totalItems / rowsPerPage);
    if (totalPages <= 1) return;

    const createBtn = (label, active, onClick) => {
      const btn = document.createElement("button");
      btn.textContent = label;
      btn.className = `w-8 h-8 flex items-center justify-center rounded-md text-sm font-semibold ${
        active
          ? "bg-red-600 text-white"
          : "text-gray-900 dark:text-white hover:bg-red-100 hover:text-red-600"
      }`;
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

  function formatNumber(num) {
    return parseInt(num || 0).toLocaleString("id-ID");
  }

  // Event bindings
  rowsPerPageSelect.addEventListener("change", (e) => {
    rowsPerPage = parseInt(e.target.value);
    currentPage = 1;
    renderTable();
  });

  filterInput.addEventListener("input", () => {
    currentPage = 1;
    renderTable();
  });

  resetBtn.addEventListener("click", () => {
    filterInput.value = "";
    currentPage = 1;
    renderTable();
  });

  exportExcelBtn.addEventListener("click", () => {
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.table_to_sheet(
      document.getElementById("supplierReportTable")
    );
    XLSX.utils.book_append_sheet(wb, ws, "SupplierReport");
    XLSX.writeFile(wb, "supplier_report.xlsx");
  });

  printBtn.addEventListener("click", () => {
    const table = document.getElementById("supplierReportTable").outerHTML;
    const date = new Date().toLocaleDateString("id-ID");
    const style = `
        <style>
          body { font-family: Arial, sans-serif; padding: 20px; }
          header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
          header img { height: 50px; }
          h1 { font-size: 20px; margin: 0; }
          table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 20px; }
          th, td { border: 1px solid #ccc; padding: 6px 8px; }
          th { background-color: #f2f2f2; text-align: center; }
          td:nth-child(6) { text-align: right; }
        </style>`;
    const html = `
        <header>
          <img src="../../assets/icons/logo_dim.png" alt="Logo" />
          <h1>Laporan Supplier<br><small style="font-weight:normal">Dicetak: ${date}</small></h1>
        </header>${table}`;

    const win = window.open("", "_blank");
    win.document.write(
      `<html><head><title>Cetak Laporan Supplier</title>${style}</head><body>${html}</body></html>`
    );
    win.document.close();
    win.focus();
    win.print();
    win.close();
  });

  fetchSupplierReport();
});
