(function () {
  const statusClassSuffixes = [
    'pending',
    'processing',
    'paid',
    'failed',
    'cancelled',
    'fulfilled',
    'success',
    'void',
    'ready',
    'shipped'
  ];

  const currencyFormatter = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  });

  const statusLabel = (status) => {
    const base = (status || '').toString().toLowerCase();
    if (!base) return 'Pending';
    switch (base) {
      case 'paid':
        return 'Paid';
      case 'pending':
        return 'Pending';
      case 'processing':
        return 'Processing';
      case 'fulfilled':
        return 'Fulfilled';
      case 'success':
        return 'Success';
      case 'failed':
        return 'Failed';
      case 'cancelled':
        return 'Cancelled';
      case 'void':
        return 'Voided';
      case 'ready':
        return 'Ready';
      case 'shipped':
        return 'Shipped';
      default:
        return base.charAt(0).toUpperCase() + base.slice(1);
    }
  };

  const parseMoney = (value) => {
    if (typeof value === 'number') return value;
    if (!value) return 0;
    if (value.dataset) {
      return parseMoney(value.textContent);
    }
    const numeric = String(value).replace(/[^0-9.-]+/g, '');
    const parsed = Number.parseFloat(numeric);
    return Number.isFinite(parsed) ? parsed : 0;
  };

  const formatMoneyNode = (node) => {
    if (!node) return;
    const amount = parseMoney(node.textContent);
    node.textContent = currencyFormatter.format(amount);
  };

  const copyToClipboard = async (text) => {
    if (!text) return false;
    try {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        await navigator.clipboard.writeText(text);
        return true;
      }
    } catch (error) {
      console.warn('Clipboard API failed, fallback engaged.', error);
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', 'readonly');
    textarea.style.position = 'absolute';
    textarea.style.left = '-9999px';
    document.body.appendChild(textarea);
    textarea.select();
    const success = document.execCommand('copy');
    document.body.removeChild(textarea);
    return success;
  };

  const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  };

  const postJson = async (url, payload = {}) => {
    if (!url) return null;
    const headers = {
      'Content-Type': 'application/json',
      Accept: 'application/json'
    };
    const csrf = getCsrfToken();
    if (csrf) headers['X-CSRF-TOKEN'] = csrf;

    const response = await fetch(url, {
      method: 'POST',
      headers,
      body: JSON.stringify(payload),
      credentials: 'same-origin'
    });

    if (!response.ok) {
      const text = await response.text().catch(() => '');
      const error = new Error('Request failed');
      error.status = response.status;
      error.body = text;
      throw error;
    }

    try {
      return await response.json();
    } catch (error) {
      return null;
    }
  };

  const showToast = (message, toastEl) => {
    if (!toastEl) return;
    toastEl.textContent = message;
    toastEl.hidden = false;
    toastEl.classList.add('is-visible');
    window.clearTimeout(showToast.timer);
    showToast.timer = window.setTimeout(() => {
      toastEl.classList.remove('is-visible');
      showToast.timer = window.setTimeout(() => {
        toastEl.hidden = true;
      }, 260);
    }, 2600);
  };

  const updateChipStatus = (chip, status) => {
    if (!chip) return;
    statusClassSuffixes.forEach((suffix) => {
      chip.classList.remove(`status-chip--${suffix}`);
    });
    const normalized = (status || '').toString().toLowerCase();
    if (normalized) {
      chip.classList.add(`status-chip--${normalized}`);
    }
    const isOutline = chip.classList.contains('status-chip--outline');
    chip.textContent = isOutline ? statusLabel(normalized) : `${statusLabel(normalized)} payment`;
  };

  document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('.ordersummary-admin-page');
    if (!page) return;
    const sidebar = page.querySelector('[data-order-sidebar]');
    const toastEl = document.querySelector('[data-toast]');

    page.querySelectorAll('[data-money]').forEach(formatMoneyNode);

    const timelineList = page.querySelector('[data-timeline-list]');
    const timelineToggle = page.querySelector('[data-timeline-toggle]');

    if (timelineToggle && timelineList) {
      timelineToggle.addEventListener('click', () => {
        const collapsed = timelineList.classList.toggle('is-collapsed');
        timelineToggle.textContent = collapsed ? 'Expand' : 'Collapse';
      });
    }

    if (sidebar) {
      const paymentToggleBtn = sidebar.querySelector('[data-sidebar-toggle]');
      const paymentSection = sidebar.querySelector('[data-sidebar-section]');

      if (paymentToggleBtn && paymentSection) {
        paymentToggleBtn.addEventListener('click', () => {
          const isExpanded = paymentToggleBtn.getAttribute('aria-expanded') !== 'false';
          paymentSection.hidden = isExpanded;
          paymentToggleBtn.setAttribute('aria-expanded', String(!isExpanded));
          paymentToggleBtn.textContent = isExpanded ? 'Expand' : 'Collapse';
        });
      }

      const paymentIndicator = page.querySelector('[data-payment-indicator]');
      const fulfillmentIndicator = page.querySelector('[data-fulfillment-indicator]');
      const fulfillmentPill = sidebar.querySelector('[data-fulfillment-pill]');
      const markPaidBtn = sidebar.querySelector('[data-sidebar-action="mark-paid"]');
      const markFulfilledBtn = sidebar.querySelector('[data-sidebar-action="mark-fulfilled"]');

      const setPaymentStatus = (status) => {
        sidebar.dataset.paymentStatus = status;
        updateChipStatus(paymentIndicator, status);
        if (markPaidBtn) {
          const isPaid = status === 'paid';
          markPaidBtn.disabled = isPaid;
          markPaidBtn.textContent = isPaid ? 'Paid' : 'Mark as paid';
        }
      };

      const setFulfillmentStatus = (status) => {
        sidebar.dataset.fulfillmentStatus = status;
        updateChipStatus(fulfillmentIndicator, status);
        updateChipStatus(fulfillmentPill, status);
        if (markFulfilledBtn) {
          const isComplete = ['fulfilled', 'shipped', 'completed', 'success'].includes(status);
          markFulfilledBtn.disabled = isComplete;
          markFulfilledBtn.textContent = isComplete ? 'Fulfilled' : 'Mark as fulfilled';
        }
      };

      setPaymentStatus(sidebar.dataset.paymentStatus || 'pending');
      setFulfillmentStatus(sidebar.dataset.fulfillmentStatus || 'processing');

      const performAction = async (type, payload = {}) => {
        let url = '';
        switch (type) {
          case 'mark-paid':
            url = sidebar.dataset.updatePaymentUrl || '';
            break;
          case 'mark-fulfilled':
            url = sidebar.dataset.updateFulfillmentUrl || '';
            break;
          case 'send-invoice':
            url = sidebar.dataset.sendInvoiceUrl || '';
            break;
          case 'schedule-pickup':
            url = sidebar.dataset.schedulePickupUrl || '';
            break;
          default:
            url = '';
        }

        if (!url) return null;
        return postJson(url, payload).catch((error) => {
          console.warn(`Action ${type} failed`, error);
          throw error;
        });
      };

      const handleMarkPaid = async () => {
        if ((sidebar.dataset.paymentStatus || '').toLowerCase() === 'paid') {
          showToast('Payment already marked as paid.', toastEl);
          return;
        }

        try {
          if (sidebar.dataset.updatePaymentUrl) {
            await performAction('mark-paid', { status: 'paid' });
          }
          setPaymentStatus('paid');
          showToast('Payment marked as paid.', toastEl);
        } catch (error) {
          showToast('Unable to update payment status. Please try again.', toastEl);
        }
      };

      const handleMarkFulfilled = async () => {
        const current = (sidebar.dataset.fulfillmentStatus || '').toLowerCase();
        if (['fulfilled', 'completed', 'success'].includes(current)) {
          showToast('Fulfillment already completed.', toastEl);
          return;
        }

        try {
          if (sidebar.dataset.updateFulfillmentUrl) {
            await performAction('mark-fulfilled', { status: 'fulfilled' });
          }
          setFulfillmentStatus('fulfilled');
          showToast('Fulfillment marked as complete.', toastEl);
        } catch (error) {
          showToast('Unable to update fulfillment status.', toastEl);
        }
      };

      const handleSendInvoice = async () => {
        const email = sidebar.dataset.customerEmail;
        if (sidebar.dataset.sendInvoiceUrl) {
          try {
            await performAction('send-invoice');
            showToast('Invoice email triggered successfully.', toastEl);
            return;
          } catch (error) {
            showToast('Sending invoice failed.', toastEl);
          }
        }

        if (email) {
          window.open(`mailto:${email}?subject=InkWise%20Invoice&body=Hi%2C%20please%20find%20your%20order%20invoice%20attached.`);
          showToast('Opened email client to send invoice.', toastEl);
        } else {
          showToast('Customer email not available.', toastEl);
        }
      };

      const handleSchedulePickup = async () => {
        try {
          if (sidebar.dataset.schedulePickupUrl) {
            await performAction('schedule-pickup');
          }
          showToast('Pickup scheduled. Update timeline when confirmed.', toastEl);
        } catch (error) {
          showToast('Unable to schedule pickup right now.', toastEl);
        }
      };

      const summarizeOrder = () => {
        const lines = [];
        const orderNumber = page.dataset.orderNumber || '';
        const header = orderNumber ? `Order ${orderNumber}` : 'Order summary';
        lines.push(header);

        const items = page.querySelectorAll('.ordersummary-table tbody tr');
        if (items.length) {
          items.forEach((row, index) => {
            const columns = row.querySelectorAll('td');
            if (columns.length < 5) return;
            const itemName = columns[0].innerText.trim().replace(/\s+/g, ' ');
            const qty = columns[2].innerText.trim();
            const total = columns[4].innerText.trim();
            lines.push(`${index + 1}. ${itemName} – Qty ${qty} – ${total}`);
          });
        }

        const grandTotal = sidebar.querySelector('[data-grand-total]')?.textContent?.trim();
        if (grandTotal) {
          lines.push(`Total due: ${grandTotal}`);
        }

        return lines.join('\n');
      };

      const handleCopySummary = async () => {
        const summary = summarizeOrder();
        const ok = await copyToClipboard(summary);
        showToast(ok ? 'Order summary copied.' : 'Unable to copy summary.', toastEl);
      };

      sidebar.addEventListener('click', (event) => {
        const target = event.target.closest('[data-sidebar-action]');
        if (!target) return;
        const action = target.dataset.sidebarAction;
        switch (action) {
          case 'mark-paid':
            handleMarkPaid();
            break;
          case 'send-invoice':
            handleSendInvoice();
            break;
          case 'mark-fulfilled':
            handleMarkFulfilled();
            break;
          case 'schedule-pickup':
            handleSchedulePickup();
            break;
          case 'copy-summary':
            handleCopySummary();
            break;
          default:
            break;
        }
      });
    }

    page.addEventListener('click', async (event) => {
      const copyTrigger = event.target.closest('[data-copy]');
      if (copyTrigger) {
        const value = copyTrigger.dataset.copy || copyTrigger.textContent;
        const ok = await copyToClipboard(value?.trim());
        showToast(ok ? 'Copied to clipboard.' : 'Unable to copy.', toastEl);
      }
    });

    const noteForm = page.querySelector('[data-note-form]');
    if (noteForm && timelineList) {
      noteForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const textarea = noteForm.querySelector('textarea');
        const note = textarea?.value.trim();
        if (!note) {
          showToast('Write a note before saving.', toastEl);
          return;
        }

        const now = new Date();
        const entry = document.createElement('li');
        entry.className = 'timeline-entry timeline-entry--note';
        entry.dataset.timelineEntry = '';
        entry.innerHTML = `
          <div class="timeline-entry__bullet" aria-hidden="true"></div>
          <div class="timeline-entry__body">
            <header>
              <h3>Internal note</h3>
              <time datetime="${now.toISOString()}">${now.toLocaleString()}</time>
            </header>
            <p class="timeline-entry__meta">${note}</p>
          </div>`;

        const emptyState = timelineList.querySelector('[data-timeline-empty]');
        if (emptyState) {
          emptyState.remove();
        }
        timelineList.insertBefore(entry, timelineList.firstChild);
        textarea.value = '';
        showToast('Note added locally. Connect backend to persist.', toastEl);
      });
    }

    page.querySelectorAll('[data-order-action]').forEach((button) => {
      button.addEventListener('click', () => {
        const action = button.dataset.orderAction;
        if (action === 'print') {
          const printUrl = page.dataset.printUrl;
          if (printUrl) {
            window.open(printUrl, '_blank', 'noopener');
          } else {
            window.print();
          }
        } else if (action === 'export') {
          const exportUrl = page.dataset.exportUrl;
          if (exportUrl) {
            window.open(exportUrl, '_blank', 'noopener');
            showToast('Exporting PDF in a new tab.', toastEl);
          } else {
            showToast('Connect export endpoint to download PDF.', toastEl);
          }
        }
      });
    });
  });
})();
