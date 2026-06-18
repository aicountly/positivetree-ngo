/*
 * Positive Tree donation checkout.
 *
 * Donations are processed by the SISPL Razorpay merchant account so we POST the
 * intent + verification to https://sispl.org/api directly from the browser. The
 * upstream URL is intentionally kept out of any visible UI copy: the user only
 * sees "Razorpay Secure Checkout". The cause of the selected card is sent as
 * `project_name`, and `client_name` is hard-coded to "Positive Tree".
 *
 * UPI QR payments often complete without firing Razorpay's handler callback, so
 * we poll /payments/{reference}?sync=1 until the server confirms capture.
 */
(function () {
  const DONATE_API_BASE = 'https://sispl.org/api';
  const DONATING_CLIENT_NAME = 'Positive Tree';
  const POLL_INTERVAL_MS = 2500;
  const POLL_MAX_MS = 120000;

  const modal = document.getElementById('donate-modal');
  if (!modal) return;

  const formPanel = document.getElementById('donate-form-panel');
  const successPanel = document.getElementById('donate-success-panel');
  const confirmingPanel = document.getElementById('donate-confirming-panel');
  const form = document.getElementById('donate-form');
  const causeDisplay = document.getElementById('donate-cause-display');
  const errorEl = document.getElementById('donate-error');
  const submitBtn = document.getElementById('donate-submit');
  const currencyEl = document.getElementById('donate-currency');
  const currencyLabel = document.getElementById('donate-currency-label');
  const successCauseEl = document.getElementById('donate-success-cause');
  const txnIdEl = document.getElementById('donate-txn-id');
  const receiptNumberEl = document.getElementById('donate-receipt-number');
  const successAmountEl = document.getElementById('donate-success-amount');
  const successDatetimeEl = document.getElementById('donate-success-datetime');
  const closeButtons = modal.querySelectorAll('.modal-close');

  let selectedCause = '';
  let csrfToken = '';
  let razorpayLoaded = false;
  let activePollTimer = null;
  let paymentSettled = false;

  function showError(message) {
    if (!errorEl) return;
    errorEl.textContent = message;
    errorEl.hidden = !message;
  }

  function showConfirming(show) {
    if (confirmingPanel) confirmingPanel.hidden = !show;
    if (submitBtn) submitBtn.hidden = show;
  }

  function setSubmitting(isSubmitting) {
    submitBtn.disabled = isSubmitting;
    submitBtn.textContent = isSubmitting ? 'Processing...' : 'Proceed to payment';
  }

  function stopPolling() {
    if (activePollTimer !== null) {
      clearInterval(activePollTimer);
      activePollTimer = null;
    }
  }

  function openModal(cause) {
    selectedCause = cause;
    causeDisplay.textContent = cause;
    if (successCauseEl) successCauseEl.textContent = cause;
    form.reset();
    if (currencyEl) currencyEl.value = 'INR';
    updateCurrencyLabel();
    showError('');
    showConfirming(false);
    setSubmitting(false);
    paymentSettled = false;
    stopPolling();
    formPanel.hidden = false;
    successPanel.hidden = true;
    modal.classList.add('active');
  }

  function closeModal() {
    stopPolling();
    modal.classList.remove('active');
  }

  function updateCurrencyLabel() {
    if (currencyLabel && currencyEl) {
      currencyLabel.textContent = `(${currencyEl.value})`;
    }
  }

  function loadRazorpayScript() {
    if (razorpayLoaded && window.Razorpay) {
      return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
      const existing = document.querySelector('script[data-rzp-checkout]');
      if (existing) {
        existing.addEventListener('load', () => {
          razorpayLoaded = true;
          resolve();
        });
        existing.addEventListener('error', () => reject(new Error('Unable to load payment gateway')));
        return;
      }

      const script = document.createElement('script');
      script.src = 'https://checkout.razorpay.com/v1/checkout.js';
      script.async = true;
      script.dataset.rzpCheckout = '1';
      script.onload = () => {
        razorpayLoaded = true;
        resolve();
      };
      script.onerror = () => reject(new Error('Unable to load payment gateway'));
      document.body.appendChild(script);
    });
  }

  async function parseJson(response) {
    const text = await response.text();
    let data = {};
    if (text) {
      try {
        data = JSON.parse(text);
      } catch (_err) {
        throw new Error(
          response.ok
            ? 'Donation server returned an invalid response. Please try again.'
            : `Donation server error (${response.status}). Please try again later.`,
        );
      }
    }
    if (!response.ok) {
      throw new Error(data.error || `Request failed (${response.status})`);
    }
    return data;
  }

  async function donateApi(path, options = {}) {
    let response;
    try {
      response = await fetch(`${DONATE_API_BASE}${path}`, {
        cache: 'no-store',
        ...options,
      });
    } catch (_err) {
      throw new Error('Cannot reach the donation server. Please check your connection and try again.');
    }
    return parseJson(response);
  }

  async function ensureCsrf() {
    if (csrfToken) return csrfToken;
    const session = await donateApi('/payments/csrf', {
      method: 'GET',
      headers: { Accept: 'application/json' },
    });
    if (!session.csrf_token) {
      throw new Error('Donation server did not return a security token. Please try again.');
    }
    if (session.razorpay_enabled === false) {
      throw new Error('Online donations are temporarily unavailable. Please contact us directly.');
    }
    csrfToken = session.csrf_token;
    return csrfToken;
  }

  function createRequestId() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
      return crypto.randomUUID();
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
      const rand = Math.floor(Math.random() * 16);
      const value = char === 'x' ? rand : (rand & 0x3) | 0x8;
      return value.toString(16);
    });
  }

  function sanitizeAmount(value) {
    const cleaned = String(value || '').replace(/[^0-9.]/g, '');
    const dotIndex = cleaned.indexOf('.');
    if (dotIndex === -1) return cleaned.slice(0, 10);
    const whole = cleaned.slice(0, dotIndex).slice(0, 10);
    const fraction = cleaned.slice(dotIndex + 1).replace(/\./g, '').slice(0, 2);
    return `${whole}.${fraction}`;
  }

  function formatAmount(amount, currency) {
    const numeric = Number(amount);
    if (!Number.isFinite(numeric)) return `${currency} ${amount}`;
    try {
      return new Intl.NumberFormat(currency === 'INR' ? 'en-IN' : 'en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
      }).format(numeric);
    } catch (_err) {
      return `${currency} ${numeric.toFixed(2)}`;
    }
  }

  function formatDateTime(isoString) {
    if (!isoString) return '';
    const date = new Date(isoString);
    if (Number.isNaN(date.getTime())) return isoString;
    try {
      return new Intl.DateTimeFormat('en-IN', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'Asia/Kolkata',
      }).format(date);
    } catch (_err) {
      return date.toLocaleString('en-IN');
    }
  }

  function showSuccessPanel(payment, fallbackAmount, fallbackCurrency) {
    paymentSettled = true;
    stopPolling();
    showConfirming(false);
    showError('');
    formPanel.hidden = true;
    successPanel.hidden = false;
    if (successCauseEl) successCauseEl.textContent = payment.project_name || selectedCause;
    if (txnIdEl) txnIdEl.textContent = payment.razorpay_payment_id || '';
    if (receiptNumberEl) receiptNumberEl.textContent = payment.reference || payment.public_reference || '';
    if (successAmountEl) {
      successAmountEl.textContent = formatAmount(
        payment.amount ?? fallbackAmount,
        payment.currency || fallbackCurrency,
      );
    }
    if (successDatetimeEl) {
      successDatetimeEl.textContent = formatDateTime(payment.updated_at || payment.created_at);
    }
  }

  async function fetchPaymentStatus(reference, syncFromGateway) {
    const query = syncFromGateway ? '?sync=1' : '';
    const result = await donateApi(`/payments/${encodeURIComponent(reference)}${query}`, {
      method: 'GET',
      headers: { Accept: 'application/json' },
    });
    return result.payment || null;
  }

  function startPaymentPolling(reference, onCompleted) {
    stopPolling();
    const startedAt = Date.now();

    const poll = async () => {
      if (paymentSettled) {
        stopPolling();
        return;
      }

      try {
        const payment = await fetchPaymentStatus(reference, true);
        if (payment && payment.status === 'completed') {
          stopPolling();
          onCompleted(payment);
        }
      } catch (_err) {
        // Keep polling through transient network errors.
      }

      if (Date.now() - startedAt >= POLL_MAX_MS) {
        stopPolling();
      }
    };

    poll();
    activePollTimer = setInterval(poll, POLL_INTERVAL_MS);
  }

  async function verifyHandlerPayment(reference, razorpayResponse, token) {
    const verified = await donateApi('/payments/verify', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-Token': token,
      },
      body: JSON.stringify({
        reference,
        razorpay_order_id: razorpayResponse.razorpay_order_id,
        razorpay_payment_id: razorpayResponse.razorpay_payment_id,
        razorpay_signature: razorpayResponse.razorpay_signature,
      }),
    });
    return verified.payment || {};
  }

  function openRazorpayCheckout(intent, donorName, donorEmail, donorPhone, reference, token) {
    return new Promise((resolve, reject) => {
      let dismissPollStarted = false;

      const finishWithPayment = (payment) => {
        if (paymentSettled) return;
        showSuccessPanel(payment, intent.payment.amount, intent.payment.currency);
        resolve(payment);
      };

      const checkout = new window.Razorpay({
        key: intent.razorpay.key_id,
        amount: intent.razorpay.amount,
        currency: intent.razorpay.currency,
        name: 'Positive Tree Foundation',
        description: selectedCause,
        order_id: intent.razorpay.order_id,
        prefill: {
          name: donorName,
          email: donorEmail,
          contact: donorPhone || undefined,
        },
        notes: intent.razorpay.notes || {},
        theme: { color: '#a8763e' },
        handler: async (response) => {
          try {
            stopPolling();
            const payment = await verifyHandlerPayment(reference, response, token);
            finishWithPayment(payment);
          } catch (err) {
            reject(err);
          }
        },
        modal: {
          ondismiss: () => {
            if (paymentSettled) return;

            showConfirming(true);
            showError('');

            if (dismissPollStarted) return;
            dismissPollStarted = true;

            startPaymentPolling(reference, finishWithPayment);

            setTimeout(() => {
              if (paymentSettled) return;
              stopPolling();
              showConfirming(false);
              reject(new Error(
                'Payment was not confirmed yet. If the amount was deducted, note your transaction ID and contact info@positivetree.ngo with reference '
                  + reference + '.',
              ));
            }, POLL_MAX_MS);
          },
        },
      });

      checkout.on('payment.failed', (response) => {
        stopPolling();
        reject(new Error(response?.error?.description || 'Payment failed'));
      });

      startPaymentPolling(reference, finishWithPayment);
      checkout.open();
    });
  }

  document.querySelectorAll('[data-donate-cause]').forEach((btn) => {
    btn.addEventListener('click', (event) => {
      event.preventDefault();
      openModal(btn.getAttribute('data-donate-cause') || '');
    });
  });

  closeButtons.forEach((btn) => btn.addEventListener('click', closeModal));
  modal.addEventListener('click', (event) => {
    if (event.target === modal) closeModal();
  });

  if (currencyEl) {
    currencyEl.addEventListener('change', updateCurrencyLabel);
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    showError('');
    showConfirming(false);
    setSubmitting(true);
    paymentSettled = false;
    stopPolling();

    try {
      const formData = new FormData(form);

      if (String(formData.get('website') || '').trim() !== '') {
        throw new Error('Submission blocked. Please refresh and try again.');
      }

      const currency = String(formData.get('currency') || 'INR').toUpperCase();
      if (currency !== 'INR' && currency !== 'USD') {
        throw new Error('Please choose a supported currency (INR or USD).');
      }

      const amountRaw = sanitizeAmount(String(formData.get('amount') || ''));
      const amount = Number(amountRaw);
      if (!Number.isFinite(amount) || amount <= 0) {
        throw new Error('Please enter a valid donation amount.');
      }

      const donorName = String(formData.get('donor_name') || '').trim();
      const donorEmail = String(formData.get('donor_email') || '').trim();
      const donorPhone = String(formData.get('donor_phone') || '').trim();
      const donorPan = String(formData.get('donor_pan') || '').trim().toUpperCase();

      if (!donorName || donorName.length < 2) {
        throw new Error('Please enter your full name.');
      }
      if (!donorEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(donorEmail)) {
        throw new Error('Please enter a valid email address.');
      }
      if (donorPan && !/^[A-Z]{5}[0-9]{4}[A-Z]$/.test(donorPan)) {
        throw new Error('PAN must match the format ABCDE1234F.');
      }
      if (!selectedCause) {
        throw new Error('Please select a cause to donate to.');
      }

      const token = await ensureCsrf();

      const intent = await donateApi('/payments', {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-Token': token,
        },
        body: JSON.stringify({
          client_name: DONATING_CLIENT_NAME,
          project_name: selectedCause,
          amount,
          currency,
          donor_name: donorName,
          donor_email: donorEmail,
          donor_phone: donorPhone || null,
          donor_pan: donorPan || null,
          request_id: createRequestId(),
          website: '',
          company: '',
        }),
      });

      if (!intent.razorpay || !intent.razorpay.order_id || !intent.payment?.reference) {
        throw new Error('Unable to start payment. Please try again.');
      }

      await loadRazorpayScript();
      await openRazorpayCheckout(
        intent,
        donorName,
        donorEmail,
        donorPhone,
        intent.payment.reference,
        token,
      );
    } catch (err) {
      if (!paymentSettled) {
        showConfirming(false);
        showError(err && err.message ? err.message : 'Something went wrong. Please try again.');
      }
    } finally {
      if (!paymentSettled) {
        setSubmitting(false);
      }
    }
  });
})();
