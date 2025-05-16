document.addEventListener("DOMContentLoaded", () => {
  flatpickr("#filterDate", {
    dateFormat: "Y-m-d",
    allowInput: true,
    onChange: () => {
      currentPage = 1;
      renderTable();
    },
  });

  const tableBody = document.querySelector("#returnReportTable tbody");
  const filterDate = document.getElementById("filterDate");
  const filterType = document.getElementById("filterType");
  const resetBtn = document.getElementById("resetFilters");
  const rowsPerPageSelect = document.getElementById("rowsPerPage");
  const paginationContainer = document.getElementById("paginationControls");
  const exportExcelBtn = document.getElementById("exportExcel");
  const printBtn = document.getElementById("printReport");

  let allData = [];
  let currentPage = 1;
  let rowsPerPage = parseInt(rowsPerPageSelect.value);

  function fetchReturnReport() {
    fetch("../../backend/api/report/return.php")
      .then((res) => res.json())
      .then((data) => {
        allData = data;
        renderTable();
      })
      .catch((err) => {
        console.error("Gagal mengambil data return:", err);
        tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-red-500">Gagal memuat data.</td></tr>`;
      });
  }

  function renderTable() {
    const selectedDate = filterDate.value;
    const selectedType = filterType.value;

    let filtered = allData.filter((item) => {
      const dateMatch = !selectedDate || item.date.startsWith(selectedDate);
      const typeMatch = !selectedType || item.type === selectedType;
      return dateMatch && typeMatch;
    });

    const start = (currentPage - 1) * rowsPerPage;
    const paginated = filtered.slice(start, start + rowsPerPage);

    tableBody.innerHTML = "";

    if (paginated.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-gray-500">Tidak ada data.</td></tr>`;
      return;
    }

    paginated.forEach((item) => {
      const row = document.createElement("tr");
      row.innerHTML = `
  <td class="px-3 py-2 text-center whitespace-nowrap text-sm text-gray-800 dark:text-gray-200">
    ${item.date}
  </td>
  <td class="px-3 py-2 text-center">
    <span class="inline-block px-2 py-1 rounded text-xs font-medium ${
      item.type === "sales"
        ? "bg-blue-100 text-blue-700"
        : "bg-yellow-100 text-yellow-700"
    }">
      ${item.type === "sales" ? "Sales Return" : "Purchase Return"}
    </span>
  </td>
  <td class="px-3 py-2 text-center text-sm break-all">
    ${item.transaction_no || "-"}
  </td>
  <td class="px-3 py-2 text-center text-sm break-all">
    ${item.name || "-"}
  </td>
  <td class="px-3 py-2 text-right whitespace-nowrap text-sm">
    Rp${formatNumber(item.amount)}
  </td>
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

  function formatNumber(num) {
    return parseInt(num || 0).toLocaleString("id-ID");
  }

  function typeBadge(type) {
    const map = {
      sales: "bg-blue-100 text-blue-700",
      purchase: "bg-yellow-100 text-yellow-700",
    };
    const color = map[type] || "bg-gray-200 text-gray-800";
    return `<span class="block w-full text-center px-3 py-1 rounded-md text-xs font-semibold capitalize ${color}">
        ${type === "sales" ? "Sales Return" : "Purchase Return"}
      </span>`;
  }

  // Event Bindings
  rowsPerPageSelect.addEventListener("change", (e) => {
    rowsPerPage = parseInt(e.target.value);
    currentPage = 1;
    renderTable();
  });

  filterType.addEventListener("input", () => {
    currentPage = 1;
    renderTable();
  });

  resetBtn.addEventListener("click", () => {
    filterDate._flatpickr.clear();
    filterType.value = "";
    currentPage = 1;
    renderTable();
  });

  exportExcelBtn.addEventListener("click", () => {
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.table_to_sheet(
      document.getElementById("returnReportTable")
    );
    XLSX.utils.book_append_sheet(wb, ws, "ReturnReport");
    XLSX.writeFile(wb, "return_report.xlsx");
  });

  printBtn.addEventListener("click", () => {
    const table = document.getElementById("returnReportTable").outerHTML;
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
          td:nth-child(5) { text-align: right; white-space: nowrap; }
        </style>`;

    const html = `
        <header>
          <img src="../../assets/icons/logo_dim.png" alt="Logo">
          <h1>Laporan Return<br><small style="font-weight: normal">Dicetak: ${date}</small></h1>
        </header>
        ${table}
      `;

    const win = window.open("", "_blank");
    win.document.write(
      `<html><head><title>Cetak Laporan Return</title>${style}</head><body>${html}</body></html>`
    );
    win.document.close();
    win.focus();
    win.print();
    win.close();
  });

  // Load Data Awal
  fetchReturnReport();
});
