(function () {
  const numberFormat = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 });
  const moneyFormat = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    maximumFractionDigits: 2
  });
  const percentFormat = new Intl.NumberFormat('en-US', { maximumFractionDigits: 1 });
  const CDN_CHART_SRC = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js';

  const resolveChartSrc = () => {
    const script = document.querySelector('script[data-chartjs-src]');
    return script && script.src ? script.src : CDN_CHART_SRC;
  };

  const ensureChartJs = (() => {
    let loaderPromise = null;
    return () => {
      if (typeof Chart !== 'undefined') {
        return Promise.resolve();
      }

      if (loaderPromise) {
        return loaderPromise;
      }

      loaderPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = resolveChartSrc();
        script.defer = true;
        script.dataset.chartjsFallback = 'true';
        script.addEventListener('load', () => {
          if (typeof Chart === 'undefined') {
            reject(new Error('Chart.js loaded but Chart global is unavailable.'));
            return;
          }
          resolve();
        }, { once: true });
        script.addEventListener('error', () => reject(new Error('Failed to load Chart.js from CDN.')), { once: true });
        document.head.appendChild(script);
      });

      return loaderPromise;
    };
  })();

  const payload = () => window.__INKWISE_REPORTS__ || {};

  const createToast = () => {
    const toast = document.createElement('div');
    toast.className = 'reports-toast';
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    toast.hidden = true;
    document.body.appendChild(toast);
    return toast;
  };

  const showToast = (toast, message) => {
    if (!toast) return;
    toast.textContent = message;
    toast.hidden = false;
    toast.classList.add('is-visible');
    window.clearTimeout(showToast.timer);
    showToast.timer = window.setTimeout(() => {
      toast.classList.remove('is-visible');
      showToast.timer = window.setTimeout(() => {
        toast.hidden = true;
      }, 260);
    }, 2400);
  };

  const downloadCSV = (table, filename) => {
    if (!table) return false;
    const rows = Array.from(table.querySelectorAll('tr'));
    if (!rows.length) return false;
    const csv = rows.map((row) => Array.from(row.querySelectorAll('th, td'))
      .map((cell) => '"' + (cell.textContent || '').replace(/"/g, '""') + '"')
      .join(','));
    const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    return true;
  };

  const printTable = (table, heading = 'Report Export') => {
    if (!table) return false;
    const popup = window.open('', '_blank', 'noopener,noreferrer,width=1024,height=768');
    if (!popup) return false;
    popup.document.write('<html><head><title>' + heading + '</title>');
    popup.document.write('<style>body{font-family:Nunito,Arial,sans-serif;padding:24px;} table{width:100%;border-collapse:collapse;} th,td{padding:10px;border:1px solid #e2e8f0;text-align:left;} th{text-transform:uppercase;font-size:12px;color:#475569;}</style>');
    popup.document.write('</head><body>');
    popup.document.write('<h2>' + heading + '</h2>');
    popup.document.write(table.outerHTML);
    popup.document.write('</body></html>');
    popup.document.close();
    popup.focus();
    popup.print();
    return true;
  };

  const fallbackDataset = (labels, values, labelName) => {
    const safeLabels = Array.isArray(labels) ? labels : [];
    const safeValues = Array.isArray(values) ? values : [];
    if (safeLabels.length && safeValues.length) {
      return { labels: safeLabels, values: safeValues, labelName };
    }
    return { labels: ['No data'], values: [0], labelName };
  };

  const highlightSalesIntervalButtons = (shell, activeKey) => {
    shell.querySelectorAll('[data-sales-interval]').forEach((btn) => {
      const isActive = btn.dataset.salesInterval === activeKey;
      btn.classList.toggle('is-active', isActive);
      btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
  };

  const updateSalesRange = (shell, label) => {
    const rangeNode = shell.querySelector('[data-sales-range]');
    if (!rangeNode) return;
    if (!rangeNode.dataset.defaultRange) {
      rangeNode.dataset.defaultRange = rangeNode.textContent || 'Showing the most recent activity.';
    }
    rangeNode.textContent = label ? `Showing ${label}` : rangeNode.dataset.defaultRange;
  };

  const initSalesDashboard = (shell) => {
    const toast = createToast();
    const dataPayload = payload();
    const intervals = dataPayload.sales?.intervals || {};
    let activeInterval = dataPayload.sales?.defaultInterval || Object.keys(intervals)[0] || 'daily';
    const baseSummary = dataPayload.sales?.summary;
    const baseRange = dataPayload.sales?.rangeLabel;
    let salesChart = null;

    const summaryNodes = {
      orders: shell.querySelector('[data-metric="orders-count"]'),
      revenue: shell.querySelector('[data-metric="revenue-paid"]'),
      materialCost: shell.querySelector('[data-metric="material-cost"]'),
      pending: shell.querySelector('[data-metric="pending-revenue"]'),
      profit: shell.querySelector('[data-metric="profit-total"]'),
      profitMargin: shell.querySelector('[data-metric="profit-margin"]'),
      average: shell.querySelector('[data-metric="average-order"]')
    };

    const applySummary = (summary = {}) => {
      if (summaryNodes.orders) summaryNodes.orders.textContent = numberFormat.format(summary.orders || 0);
      if (summaryNodes.revenue) summaryNodes.revenue.textContent = moneyFormat.format(summary.revenue || 0);
      if (summaryNodes.materialCost) summaryNodes.materialCost.textContent = moneyFormat.format(summary.materialCost || 0);
      if (summaryNodes.pending) summaryNodes.pending.textContent = moneyFormat.format(summary.pendingRevenue || 0);
      if (summaryNodes.profit) summaryNodes.profit.textContent = moneyFormat.format(summary.profit || 0);
      if (summaryNodes.profitMargin) summaryNodes.profitMargin.textContent = `${percentFormat.format(summary.profitMargin || 0)}% margin`;
      if (summaryNodes.average) summaryNodes.average.textContent = moneyFormat.format(summary.averageOrder || 0);
    };

    const ensureSalesChart = () => {
      if (salesChart) return salesChart;
      const canvas = shell.querySelector('[data-chart="sales"]');
      if (!canvas) return null;
      const interval = intervals[activeInterval] || {};
      const dataset = fallbackDataset(interval.labels, interval.totals, 'Total sales (PHP)');
      salesChart = new Chart(canvas, {
        type: 'line',
        data: {
          labels: dataset.labels,
          datasets: [
            {
              label: dataset.labelName,
              data: dataset.values,
              borderColor: '#5a8de0',
              backgroundColor: 'rgba(90, 141, 224, 0.18)',
              fill: true,
              tension: 0.35,
              borderWidth: 2,
              pointRadius: 4,
              pointBackgroundColor: '#5a8de0'
            }
          ]
        },
        options: {
          responsive: true,
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: (value) => moneyFormat.format(value)
              }
            }
          },
          plugins: {
            tooltip: {
              callbacks: {
                label: (ctx) => moneyFormat.format(ctx.parsed.y)
              }
            }
          }
        }
      });
      return salesChart;
    };

    const applyInterval = (intervalKey, options = {}) => {
      const { skipSummary = false } = options;
      if (!intervals[intervalKey]) {
        showToast(toast, 'No data for that interval yet.');
        return;
      }
      activeInterval = intervalKey;
      highlightSalesIntervalButtons(shell, intervalKey);
      const interval = intervals[intervalKey];
      if (!skipSummary) {
        applySummary(interval.summary || {});
        updateSalesRange(shell, interval.range_label || '');
      } else if (!baseRange) {
        updateSalesRange(shell, interval.range_label || '');
      }
      const chart = ensureSalesChart();
      if (chart) {
        const dataset = fallbackDataset(interval.labels, interval.totals, 'Total sales (PHP)');
        chart.data.labels = dataset.labels;
        chart.data.datasets[0].data = dataset.values;
        chart.update();
      }
    };

    if (intervals[activeInterval]) {
      if (baseSummary) {
        applySummary(baseSummary);
      } else {
        applySummary(intervals[activeInterval].summary || {});
      }
      if (typeof baseRange === 'string' && baseRange.length) {
        updateSalesRange(shell, baseRange);
      } else {
        updateSalesRange(shell, intervals[activeInterval].range_label || '');
      }
    }

    ensureChartJs()
      .then(() => {
        if (!ensureSalesChart()) {
          showToast(toast, 'Charts unavailable — failed to initialise.');
        }
      })
      .catch((error) => {
        console.warn(error);
        showToast(toast, 'Charts unavailable — please check your connection.');
      });

    shell.addEventListener('click', (event) => {
      const intervalBtn = event.target.closest('[data-sales-interval]');
      if (intervalBtn) {
        applyInterval(intervalBtn.dataset.salesInterval);
        return;
      }

      const exportBtn = event.target.closest('[data-export="sales"]');
      if (exportBtn) {
        const table = shell.querySelector('#salesTable');
        const filename = `sales-report-${new Date().toISOString().slice(0, 10)}.csv`;
        if (downloadCSV(table, filename)) {
          showToast(toast, 'Sales CSV export started.');
        }
        return;
      }

      const printBtn = event.target.closest('[data-print="sales"]');
      if (printBtn) {
        const table = shell.querySelector('#salesTable');
        if (printTable(table, 'Sales report')) {
          showToast(toast, 'Print dialog opened.');
        }
        return;
      }

      const actionBtn = event.target.closest('[data-report-action]');
      if (actionBtn) {
        const action = actionBtn.dataset.reportAction;
        if (action === 'refresh') {
          showToast(toast, 'Data refreshed. Latest numbers loaded.');
        }
        if (action === 'export-sales') {
          const table = shell.querySelector('#salesTable');
          const filename = `sales-report-${new Date().toISOString().slice(0, 10)}.csv`;
          if (downloadCSV(table, filename)) {
            showToast(toast, 'Sales CSV export started.');
          }
        }
      }
    });

    // Apply default interval once the listeners are ready
    if (intervals[activeInterval]) {
      applyInterval(activeInterval, { skipSummary: Boolean(baseSummary) });
    }
  };

  const initInventoryDashboard = (shell) => {
    const toast = createToast();
    const dataPayload = payload();
    const inventory = dataPayload.inventory || {};
    const labels = Array.isArray(inventory.labels?.data) ? inventory.labels.data : inventory.labels;
    const stock = Array.isArray(inventory.stock?.data) ? inventory.stock.data : inventory.stock;
    const reorder = Array.isArray(inventory.reorder?.data) ? inventory.reorder.data : inventory.reorder;

    ensureChartJs()
      .then(() => {
        const canvas = shell.querySelector('[data-chart="inventory"]');
        if (!canvas) return;
        const dataset = fallbackDataset(labels, stock, 'Current stock');
        const reorderData = Array.isArray(reorder) && reorder.length
          ? reorder
          : new Array(dataset.labels.length).fill(0);
        new Chart(canvas, {
          type: 'bar',
          data: {
            labels: dataset.labels,
            datasets: [
              {
                label: 'Current stock',
                data: dataset.values,
                backgroundColor: 'rgba(90, 141, 224, 0.65)',
                borderRadius: 6
              },
              {
                label: 'Reorder level',
                data: reorderData,
                backgroundColor: 'rgba(251, 191, 36, 0.65)',
                borderRadius: 6
              }
            ]
          },
          options: {
            responsive: true,
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  callback: (value) => numberFormat.format(value)
                }
              }
            }
          }
        });
      })
      .catch((error) => {
        console.warn(error);
        showToast(toast, 'Charts unavailable — please check your connection.');
      });

    shell.addEventListener('click', (event) => {
      const exportBtn = event.target.closest('[data-export="inventory"]');
      if (exportBtn) {
        const table = shell.querySelector('#inventoryTable');
        const filename = `inventory-report-${new Date().toISOString().slice(0, 10)}.csv`;
        if (downloadCSV(table, filename)) {
          showToast(toast, 'Inventory CSV export started.');
        }
        return;
      }

      const printBtn = event.target.closest('[data-print="inventory"]');
      if (printBtn) {
        const table = shell.querySelector('#inventoryTable');
        if (printTable(table, 'Inventory report')) {
          showToast(toast, 'Print dialog opened.');
        }
        return;
      }

      const actionBtn = event.target.closest('[data-report-action]');
      if (actionBtn) {
        const action = actionBtn.dataset.reportAction;
        if (action === 'refresh') {
          showToast(toast, 'Data refreshed. Latest numbers loaded.');
        }
        if (action === 'export-inventory') {
          const table = shell.querySelector('#inventoryTable');
          const filename = `inventory-report-${new Date().toISOString().slice(0, 10)}.csv`;
          if (downloadCSV(table, filename)) {
            showToast(toast, 'Inventory CSV export started.');
          }
        }
      }
    });
  };

  document.addEventListener('DOMContentLoaded', () => {
    const salesShell = document.getElementById('adminSalesReportsShell');
    if (salesShell) {
      initSalesDashboard(salesShell);
    }

    const inventoryShell = document.getElementById('adminInventoryReportsShell');
    if (inventoryShell) {
      initInventoryDashboard(inventoryShell);
    }
  });
})();
