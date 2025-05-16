document.addEventListener("DOMContentLoaded", () => {
  flatpickr("#filterDate", {
    dateFormat: "Y-m-d",
    allowInput: true,
    onChange: function () {
      currentPage = 1;
      renderTable();
    },
  });

  const tableBody = document.querySelector("#salesReportTable tbody");
  const filterDate = document.getElementById("filterDate");
  const filterCustomer = document.getElementById("filterCustomer");
  const filterStatus = document.getElementById("filterStatus");
  const resetBtn = document.getElementById("resetFilters");
  const rowsPerPageSelect = document.getElementById("rowsPerPage");
  const paginationContainer = document.getElementById("paginationControls");
  const exportExcelBtn = document.getElementById("exportExcel");
  const printBtn = document.getElementById("printReport");

  let allData = [];
  let currentPage = 1;
  let rowsPerPage = parseInt(rowsPerPageSelect.value);
  let sortBy = null;
  let sortOrder = "asc";

  function fetchSalesReport() {
    fetch("../../backend/api/report/sales.php")
      .then((res) => res.json())
      .then((data) => {
        allData = data;
        renderTable();
      })
      .catch((err) => {
        console.error("Gagal mengambil data laporan:", err);
        tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-red-500">Gagal memuat data.</td></tr>`;
      });
  }

  function renderTable() {
    const keyword = filterCustomer.value.toLowerCase();
    const selectedDate = filterDate.value;
    const selectedStatus = filterStatus.value;

    let filtered = allData.filter((item) => {
      const dateMatch = !selectedDate || item.date === selectedDate;
      const customerMatch = item.customer?.toLowerCase().includes(keyword);
      const statusMatch = !selectedStatus || item.status === selectedStatus;
      return dateMatch && customerMatch && statusMatch;
    });

    if (sortBy) {
      filtered.sort((a, b) => {
        const valA = a[sortBy];
        const valB = b[sortBy];

        if (!isNaN(valA) && !isNaN(valB)) {
          return sortOrder === "asc" ? valA - valB : valB - valA;
        }

        return sortOrder === "asc"
          ? String(valA).localeCompare(valB)
          : String(valB).localeCompare(valA);
      });
    }

    const start = (currentPage - 1) * rowsPerPage;
    const paginated = filtered.slice(start, start + rowsPerPage);

    tableBody.innerHTML = "";

    if (paginated.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-gray-500">Tidak ada data.</td></tr>`;
      return;
    }

    paginated.forEach((item) => {
      const row = document.createElement("tr");
      row.innerHTML = `
            <td class="px-4 py-2">${item.invoice}</td>
            <td class="px-4 py-2">${item.date}</td>
            <td class="px-4 py-2">${item.customer || "-"}</td>
            <td class="px-4 py-2">${statusBadge(item.status)}</td>
            <td class="px-4 py-2">Rp${formatNumber(item.total)}</td>
            <td class="px-4 py-2">Rp${formatNumber(item.paid)}</td>
            <td class="px-4 py-2">Rp${formatNumber(item.due)}</td>
            <td class="px-4 py-2">${paymentBadge(item.payment_status)}</td>
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

  function statusBadge(status) {
    const map = {
      completed: "bg-green-100 text-green-700",
      pending: "bg-yellow-100 text-yellow-700",
      cancelled: "bg-red-100 text-red-700",
    };
    const color = map[status] || "bg-gray-200 text-gray-800";
    return `<span class="inline-block px-3 py-1 rounded-md text-xs font-semibold capitalize ${color}">${status}</span>`;
  }

  function paymentBadge(paymentStatus) {
    const map = {
      paid: "bg-green-100 text-green-700",
      partial: "bg-yellow-100 text-yellow-700",
      unpaid: "bg-red-100 text-red-700",
    };
    const color = map[paymentStatus] || "bg-gray-200 text-gray-800";
    return `<span class="inline-block px-3 py-1 rounded-md text-xs font-semibold capitalize ${color}">${paymentStatus}</span>`;
  }

  // Event listeners
  rowsPerPageSelect.addEventListener("change", (e) => {
    rowsPerPage = parseInt(e.target.value);
    currentPage = 1;
    renderTable();
  });

  [filterCustomer, filterStatus].forEach((el) => {
    el.addEventListener("input", () => {
      currentPage = 1;
      renderTable();
    });
  });

  resetBtn.addEventListener("click", () => {
    filterDate._flatpickr.clear(); // reset flatpickr
    filterCustomer.value = "";
    filterStatus.value = "";
    currentPage = 1;
    renderTable();
  });

  // Sorting header
  document
    .querySelectorAll("#salesReportTable thead th[data-sort]")
    .forEach((th) => {
      th.addEventListener("click", () => {
        const key = th.getAttribute("data-sort");
        if (!key) return;

        if (sortBy === key) {
          sortOrder = sortOrder === "asc" ? "desc" : "asc";
        } else {
          sortBy = key;
          sortOrder = "asc";
        }
        renderTable();
      });
    });

  // Export Excel
  exportExcelBtn.addEventListener("click", () => {
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.table_to_sheet(
      document.getElementById("salesReportTable")
    );
    XLSX.utils.book_append_sheet(wb, ws, "SalesReport");
    XLSX.writeFile(wb, "sales_report.xlsx");
  });

  // Print Report
  printBtn.addEventListener("click", () => {
    const table = document.getElementById("salesReportTable").outerHTML;
    const date = new Date().toLocaleDateString("id-ID");

    const style = `
        <style>
          body { font-family: Arial, sans-serif; color: #333; padding: 20px; }
          header {
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;
          }
          header img { height: 50px; }
          h1 { font-size: 20px; text-align: right; margin: 0; }
          table {
            width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px;
          }
          th, td {
            border: 1px solid #ccc; padding: 6px 8px;
          }
          th {
            background-color: #f2f2f2; text-align: center;
          }
          td:nth-child(5), td:nth-child(6), td:nth-child(7) {
            text-align: right; white-space: nowrap;
          }
        </style>`;

    const html = `
        <header>
          <img src="../../assets/icons/logo_dim.png" alt="Logo">
          <h1>Laporan Penjualan<br><small style="font-weight: normal">Dicetak: ${date}</small></h1>
        </header>
        ${table}
        `;

    const win = window.open("", "_blank");
    win.document.write(
      `<html><head><title>Cetak Laporan Penjualan</title>${style}</head><body>${html}</body></html>`
    );
    win.document.close();
    win.focus();
    win.print();
    win.close();
  });

  // Load data awal
  fetchSalesReport();
});
