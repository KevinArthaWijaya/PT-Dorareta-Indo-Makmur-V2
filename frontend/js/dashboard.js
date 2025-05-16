document.addEventListener("DOMContentLoaded", () => {
  // === FETCH DATA RINGKASAN DASHBOARD ===
  fetchDashboardSummary();
  let salesTargetChart;

  async function fetchDashboardSummary() {
    try {
      const response = await fetch(
        "../../backend/api/dashboard/index.php?path=summary"
      );
      const result = await response.json();

      if (!result.success || !result.data)
        throw new Error("Gagal mengambil data dashboard");

      const { total_products, total_purchases, total_sales, out_of_stock } =
        result.data;

      document.getElementById("totalProducts").textContent = total_products;
      document.getElementById("totalPurchases").textContent =
        formatRupiah(total_purchases);
      document.getElementById("totalSales").textContent =
        formatRupiah(total_sales);
      document.getElementById("outOfStock").textContent = out_of_stock;
    } catch (err) {
      console.error("Error loading dashboard summary:", err);
    }
  }

  fetchWeeklyTrend();

  async function fetchWeeklyTrend() {
    try {
      const res = await fetch(
        "../../backend/api/dashboard/index.php?path=weekly-trend"
      );
      const json = await res.json();
      if (!json.success) throw new Error("Gagal fetch grafik mingguan");

      const { labels, sales, purchases } = json.data;

      const ctx = document.getElementById("weeklyChart").getContext("2d");
      new Chart(ctx, {
        type: "bar",
        data: {
          labels,
          datasets: [
            {
              label: "Sales",
              data: sales,
              backgroundColor: "rgba(59, 130, 246, 0.8)", // biru
            },
            {
              label: "Purchases",
              data: purchases,
              backgroundColor: "rgba(147, 197, 253, 0.8)", // biru muda
            },
          ],
        },
        options: {
          responsive: true,
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: (value) => `Rp ${value.toLocaleString("id-ID")}`,
              },
            },
          },
        },
      });
    } catch (err) {
      console.error("Weekly chart error:", err);
    }
  }

  fetchTopProducts();

  async function fetchTopProducts() {
    try {
      const res = await fetch(
        "../../backend/api/dashboard/index.php?path=top-products"
      );
      const json = await res.json();
      if (!json.success) throw new Error("Gagal fetch top products");

      const ctx = document.getElementById("topProductsChart").getContext("2d");

      new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: json.data.labels,
          datasets: [
            {
              label: "Qty Terjual",
              data: json.data.values,
              backgroundColor: [
                "#a855f7",
                "#f472b6",
                "#6366f1",
                "#8b5cf6",
                "#fb7185",
              ],
              borderWidth: 1,
            },
          ],
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: "bottom",
            },
          },
        },
      });
    } catch (err) {
      console.error("Top products chart error:", err);
    }
  }

  fetchTopCustomers();

  async function fetchTopCustomers() {
    try {
      const res = await fetch(
        "../../backend/api/dashboard/index.php?path=top-customers"
      );
      const json = await res.json();
      if (!json.success) throw new Error("Gagal fetch top customers");

      const ctx = document.getElementById("topCustomersChart").getContext("2d");

      new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: json.data.labels,
          datasets: [
            {
              label: "Total Belanja",
              data: json.data.values,
              backgroundColor: [
                "#34d399",
                "#60a5fa",
                "#c084fc",
                "#f87171",
                "#facc15",
              ],
              borderWidth: 1,
            },
          ],
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: "bottom",
            },
          },
        },
      });
    } catch (err) {
      console.error("Top customers chart error:", err);
    }
  }

  fetchStockAlert();

  async function fetchStockAlert() {
    try {
      const res = await fetch(
        "../../backend/api/dashboard/index.php?path=stock-alert"
      );
      const json = await res.json();
      if (!json.success) throw new Error("Gagal fetch stock alert");

      const tableBody = document.getElementById("stockAlertTable");
      tableBody.innerHTML = "";

      if (json.data.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="4" class="py-2 px-3 text-center text-gray-500">Semua stok aman</td></tr>`;
        return;
      }

      json.data.forEach((item) => {
        const row = `
        <tr>
          <td class="py-2 px-3">${item.sku}</td>
          <td class="py-2 px-3">${item.name}</td>
          <td class="py-2 px-3">${item.qty}</td>
          <td class="py-2 px-3 text-red-600 font-bold">${item.min_qty}</td>
        </tr>
      `;
        tableBody.insertAdjacentHTML("beforeend", row);
      });
    } catch (err) {
      console.error("Stock Alert Error:", err);
    }
  }

  fetchTopReturns();

  async function fetchTopReturns() {
    try {
      const res = await fetch(
        "../../backend/api/dashboard/index.php?path=top-returns"
      );
      const json = await res.json();
      if (!json.success) throw new Error("Gagal fetch retur terbanyak");

      const ctx = document.getElementById("topReturnChart").getContext("2d");

      new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: json.data.labels,
          datasets: [
            {
              label: "Qty Retur",
              data: json.data.values,
              backgroundColor: [
                "#f87171",
                "#fbbf24",
                "#34d399",
                "#60a5fa",
                "#a78bfa",
              ],
              borderWidth: 1,
            },
          ],
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: "bottom",
            },
          },
        },
      });
    } catch (err) {
      console.error("Top Return Chart Error:", err);
    }
  }

  fetchRecentInvoices();

  async function fetchRecentInvoices() {
    try {
      const res = await fetch(
        "../../backend/api/dashboard/index.php?path=recent-invoices"
      );
      const json = await res.json();
      if (!json.success) throw new Error("Gagal fetch invoice");

      const table = document.getElementById("invoiceTable");
      table.innerHTML = "";

      if (json.data.length === 0) {
        table.innerHTML = `<tr><td colspan="4" class="py-2 px-3 text-center text-gray-500">Belum ada invoice</td></tr>`;
        return;
      }

      json.data.forEach((inv) => {
        const statusClass =
          inv.status === "paid"
            ? "text-green-600 font-semibold"
            : "text-red-600 font-semibold";

        const row = `
        <tr>
          <td class="py-2 px-3">${inv.invoice_no}</td>
          <td class="py-2 px-3">${inv.customer}</td>
          <td class="py-2 px-3">Rp ${inv.grand_total.toLocaleString(
            "id-ID"
          )}</td>
          <td class="py-2 px-3 ${statusClass}">${inv.status}</td>
        </tr>
      `;
        table.insertAdjacentHTML("beforeend", row);
      });
    } catch (err) {
      console.error("Invoice fetch error:", err);
    }
  }

  fetchSalesTarget();

  async function fetchSalesTarget() {
    try {
      const res = await fetch(
        "../../backend/api/dashboard/index.php?path=sales-target"
      );
      const json = await res.json();
      if (!json.success) throw new Error("Gagal fetch target");

      const { target, achieved } = json.data;
      const percentage = Math.min((achieved / target) * 100, 100).toFixed(1);

      const ctx = document.getElementById("salesTargetChart").getContext("2d");

      // 🧼 Destroy chart lama jika sudah ada
      if (salesTargetChart) {
        salesTargetChart.destroy();
      }

      salesTargetChart = new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: ["Achieved", "Remaining"],
          datasets: [
            {
              data: [achieved, target - achieved],
              backgroundColor: ["#10b981", "#e5e7eb"],
              borderWidth: 1,
            },
          ],
        },
        options: {
          cutout: "70%",
          plugins: {
            legend: { display: false },
          },
        },
      });

      // Update Label
      document.getElementById(
        "salesTargetLabel"
      ).innerHTML = `<strong>${percentage}%</strong> tercapai dari target Rp ${target.toLocaleString(
        "id-ID"
      )}`;
    } catch (err) {
      console.error("Sales Target Chart Error:", err);
    }
  }

  function formatRupiah(value) {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      maximumFractionDigits: 0,
    }).format(value || 0);
  }

  // Toggle Add Target
  const addTargetBtn = document.getElementById("addTargetBtn");
  const addTargetBtnWrapper = document.getElementById("addTargetBtnWrapper");
  const targetFormWrapper = document.getElementById("targetFormWrapper");
  const targetForm = document.getElementById("targetForm");
  const targetInput = document.getElementById("targetInput");

  if (
    addTargetBtn &&
    addTargetBtnWrapper &&
    targetFormWrapper &&
    targetForm &&
    targetInput
  ) {
    addTargetBtn.addEventListener("click", () => {
      addTargetBtnWrapper.classList.add("hidden");
      targetFormWrapper.classList.remove("hidden");
    });

    targetForm.addEventListener("submit", async (e) => {
      e.preventDefault();

      const newTarget = parseInt(targetInput.value);
      if (isNaN(newTarget) || newTarget <= 0) {
        Swal.fire({
          icon: "warning",
          title: "Input Tidak Valid",
          text: "Masukkan target penjualan yang lebih dari 0",
          confirmButtonColor: "#dc2626",
        });
        return;
      }

      try {
        const formData = new FormData();
        formData.append("target", newTarget);

        const res = await fetch(
          "../../backend/api/dashboard/index.php?path=sales-target",
          {
            method: "POST",
            body: formData,
          }
        );

        const json = await res.json();
        if (json.success) {
          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: "Target berhasil diperbarui!",
            confirmButtonColor: "#dc2626",
          });

          fetchSalesTarget(); // refresh chart
          // Reset UI
          targetInput.value = "";
          targetFormWrapper.classList.add("hidden");
          addTargetBtnWrapper.classList.remove("hidden");
        } else {
          Swal.fire({
            icon: "error",
            title: "Gagal",
            text: json.message,
            confirmButtonColor: "#dc2626",
          });
        }
      } catch (err) {
        console.error("Update target error:", err);
        Swal.fire({
          icon: "error",
          title: "Terjadi Error",
          text: "Gagal mengirim data ke server.",
          confirmButtonColor: "#dc2626",
        });
      }
    });
  }

  // === DROPDOWN REPORT HANDLER ===
  const dropdown = document.getElementById("reportDropdown");
  const toggleBtn = document.getElementById("toggleReportDropdown");

  if (dropdown && toggleBtn) {
    toggleBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      dropdown.classList.toggle("hidden");
      dropdown.classList.toggle("flex");
    });

    document.addEventListener("click", (e) => {
      if (!document.getElementById("reportWrapper").contains(e.target)) {
        dropdown.classList.add("hidden");
        dropdown.classList.remove("flex");
      }
    });
  }
});
