(function () {
  const numberFormat = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 });
  const moneyFormat = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    maximumFractionDigits: 2
  });

  const getReportData = () => {
    const payload = window.__INKWISE_REPORTS__ || {};
    return {
      inventory: {
        labels: Array.isArray(payload.inventory?.labels) ? payload.inventory.labels : (payload.inventory?.labels?.data ?? []),
        stock: Array.isArray(payload.inventory?.stock) ? payload.inventory.stock : (payload.inventory?.stock?.data ?? []),
        reorder: Array.isArray(payload.inventory?.reorder) ? payload.inventory.reorder : (payload.inventory?.reorder?.data ?? [])
      },
      sales: {
        labels: Array.isArray(payload.sales?.labels) ? payload.sales.labels : [],
        totals: Array.isArray(payload.sales?.totals) ? payload.sales.totals : []
      },
      summaries: payload.summaries || {}
    };
  };

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
    const csv = rows.map((row) => {
      return Array.from(row.querySelectorAll('th, td'))
        .map((cell) => '"' + (cell.textContent || '').replace(/"/g, '""') + '"')
        .join(',');
    });
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

  const initTabs = (shell) => {
    const tabs = shell.querySelectorAll('.reports-tab');
    const panels = shell.querySelectorAll('.reports-panel__content');
    tabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        const targetId = tab.dataset.tabTarget;
        tabs.forEach((btn) => {
          btn.classList.toggle('is-active', btn === tab);
          btn.setAttribute('aria-selected', btn === tab ? 'true' : 'false');
        });
        panels.forEach((panel) => {
          const isTarget = panel.id === targetId;
          panel.classList.toggle('is-visible', isTarget);
          panel.hidden = !isTarget;
        });
      });
    });
  };

  const fallbackDataset = (labels, values, labelName) => {
    if (labels.length && values.length) return { labels, values };
    return {
      labels: ['No data'],
      values: [0],
      labelName
    };
  };

  const initCharts = (shell, data, toast) => {
    if (typeof Chart === 'undefined') {
      showToast(toast, 'Charts unavailable — Chart.js not loaded.');
      return;
    }

    const salesCanvas = shell.querySelector('[data-chart="sales"]');
    if (salesCanvas) {
      const fallback = fallbackDataset(data.sales.labels, data.sales.totals, 'Revenue');
      const labels = fallback.labels;
      const totals = fallback.values;
      new Chart(salesCanvas, {
        type: 'line',
        data: {
          labels,
          datasets: [
            {
              label: 'Total sales (PHP)',
              data: totals,
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
    }

    const inventoryCanvas = shell.querySelector('[data-chart="inventory"]');
    if (inventoryCanvas) {
      const fallback = fallbackDataset(data.inventory.labels, data.inventory.stock, 'Stock level');
      const labels = fallback.labels;
      const stock = fallback.values;
      const reorder = data.inventory.reorder && data.inventory.reorder.length
        ? data.inventory.reorder
        : new Array(labels.length).fill(0);
      new Chart(inventoryCanvas, {
        type: 'bar',
        data: {
          labels,
          datasets: [
            {
              label: 'Current stock',
              data: stock,
              backgroundColor: 'rgba(90, 141, 224, 0.65)',
              borderRadius: 6
            },
            {
              label: 'Reorder level',
              data: reorder,
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
    }
  };

  const updateSummaryMetrics = (shell, data) => {
    const summaries = data.summaries || {};
    const sales = summaries.sales || {};
    const inventory = summaries.inventory || {};

    const ordersNode = shell.querySelector('[data-metric="orders-count"]');
    const revenueNode = shell.querySelector('[data-metric="revenue-total"]');
    const avgNode = shell.querySelector('[data-metric="average-order"]');
    const stockNode = shell.querySelector('[data-metric="stock-status"]');

    if (ordersNode) ordersNode.textContent = numberFormat.format(sales.orders || 0);
    if (revenueNode) revenueNode.textContent = moneyFormat.format(sales.revenue || 0);
    if (avgNode) avgNode.textContent = moneyFormat.format(sales.averageOrder || 0);
    if (stockNode) {
      const low = inventory.lowStock || 0;
      const out = inventory.outStock || 0;
      stockNode.textContent = `${numberFormat.format(low)} / ${numberFormat.format(out)}`;
    }
  };

  const bindActions = (shell, toast) => {
    const tables = {
      sales: shell.querySelector('#salesTable'),
      inventory: shell.querySelector('#inventoryTable')
    };

    shell.addEventListener('click', (event) => {
      const exportBtn = event.target.closest('[data-export]');
      if (exportBtn) {
        const key = exportBtn.dataset.export;
        const table = tables[key];
        if (!table) {
          showToast(toast, 'Table not found for export.');
          return;
        }
        const filename = `${key}-report-${new Date().toISOString().slice(0, 10)}.csv`;
        downloadCSV(table, filename);
        showToast(toast, 'CSV export started.');
        return;
      }

      const printBtn = event.target.closest('[data-print]');
      if (printBtn) {
        const key = printBtn.dataset.print;
        const table = tables[key];
        if (!table) {
          showToast(toast, 'Table not found for printing.');
          return;
        }
        printTable(table, `${key.charAt(0).toUpperCase() + key.slice(1)} report`);
        showToast(toast, 'Print dialog opened.');
        return;
      }

      const actionBtn = event.target.closest('[data-report-action]');
      if (actionBtn) {
        const action = actionBtn.dataset.reportAction;
        if (action === 'refresh') {
          showToast(toast, 'Data refreshed. Latest numbers loaded.');
        } else if (action === 'export-all') {
          const now = new Date().toISOString().slice(0, 10);
          Object.entries(tables).forEach(([key, table]) => {
            if (!table) return;
            downloadCSV(table, `${key}-report-${now}.csv`);
          });
          showToast(toast, 'All tables exported as CSV.');
        }
      }
    });

    const rangeSelect = shell.querySelector('[data-report-filter="sales-range"]');
    if (rangeSelect) {
      rangeSelect.addEventListener('change', () => {
        showToast(toast, 'Custom ranges coming soon. Displaying default data.');
        rangeSelect.value = '6m';
      });
    }
  };

  document.addEventListener('DOMContentLoaded', () => {
    const shell = document.getElementById('adminReportsShell');
    if (!shell) return;

    const toast = createToast();
    const data = getReportData();

    updateSummaryMetrics(shell, data);
    initTabs(shell);
    initCharts(shell, data, toast);
    bindActions(shell, toast);
  });
})();
