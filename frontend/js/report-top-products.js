document.addEventListener("DOMContentLoaded", () => {
  const tableBody = document.querySelector("#topProductsTable tbody");
  const filterKeyword = document.getElementById("filterKeyword");
  const resetBtn = document.getElementById("resetFilters");
  const rowsPerPageSelect = document.getElementById("rowsPerPage");
  const paginationContainer = document.getElementById("paginationControls");
  const exportExcelBtn = document.getElementById("exportExcel");
  const printBtn = document.getElementById("printReport");

  let allData = [];
  let currentPage = 1;
  let rowsPerPage = parseInt(rowsPerPageSelect.value);

  function fetchTopProducts() {
    fetch("../../backend/api/report/top-products.php")
      .then((res) => res.json())
      .then((data) => {
        allData = data;
        renderTable();
      })
      .catch((err) => {
        console.error("Failed to fetch top products:", err);
        tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-red-500">Failed to load data.</td></tr>`;
      });
  }

  function renderTable() {
    const keyword = filterKeyword.value.toLowerCase();

    const filtered = allData.filter((item) => {
      return (
        item.product_name.toLowerCase().includes(keyword) ||
        item.category.toLowerCase().includes(keyword)
      );
    });

    const start = (currentPage - 1) * rowsPerPage;
    const paginated = filtered.slice(start, start + rowsPerPage);

    tableBody.innerHTML = "";

    if (paginated.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-gray-500">No data available.</td></tr>`;
      return;
    }

    paginated.forEach((item) => {
      const row = document.createElement("tr");
      row.innerHTML = `
          <td class="px-3 py-2 whitespace-nowrap">${item.product_name}</td>
          <td class="px-3 py-2 text-center">${item.total_sold}</td>
          <td class="px-3 py-2 text-right whitespace-nowrap">Rp${formatNumber(
            item.total_sales
          )}</td>
          <td class="px-3 py-2 whitespace-nowrap">${item.category || "-"}</td>
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

  // Events
  filterKeyword.addEventListener("input", () => {
    currentPage = 1;
    renderTable();
  });

  resetBtn.addEventListener("click", () => {
    filterKeyword.value = "";
    currentPage = 1;
    renderTable();
  });

  rowsPerPageSelect.addEventListener("change", (e) => {
    rowsPerPage = parseInt(e.target.value);
    currentPage = 1;
    renderTable();
  });

  exportExcelBtn.addEventListener("click", () => {
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.table_to_sheet(
      document.getElementById("topProductsTable")
    );
    XLSX.utils.book_append_sheet(wb, ws, "TopProducts");
    XLSX.writeFile(wb, "top_products.xlsx");
  });

  printBtn.addEventListener("click", () => {
    const table = document.getElementById("topProductsTable").outerHTML;
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
          td:nth-child(2), td:nth-child(3) { text-align: center; white-space: nowrap; }
          td:nth-child(4) { text-align: left; }
        </style>`;

    const html = `
        <header>
          <img src="../../assets/icons/logo_dim.png" alt="Logo">
          <h1>Top Products Report<br><small style="font-weight: normal">Printed: ${date}</small></h1>
        </header>
        ${table}
      `;

    const win = window.open("", "_blank");
    win.document.write(
      `<html><head><title>Print Report</title>${style}</head><body>${html}</body></html>`
    );
    win.document.close();
    win.focus();
    win.print();
    win.close();
  });

  // Initial load
  fetchTopProducts();
});
