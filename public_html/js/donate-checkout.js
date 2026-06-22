/*
 * Positive Tree donation checkout.
 *
 * Payment intents and verification use sispl.org/api (REST). Razorpay checkout
 * must open on sispl.org (registered merchant domain) — never on this site.
 * A small popup runs checkout on sispl.org; results return here via postMessage.
 */
(function () {
  const DONATE_API_BASE = 'https://sispl.org/api';
  const SISPL_ORIGIN = 'https://sispl.org';
  const SISPL_PAY_URL = `${SISPL_ORIGIN}/pay`;
  const SISPL_BRIDGE_URL = `${SISPL_ORIGIN}/pay/complete`;
  const DONATING_CLIENT_NAME = 'Positive Tree';
  const PENDING_KEY = 'positivetree_donate_pending';
  const POPUP_NAME = 'positivetree_payment';
  const POLL_INTERVAL_MS = 2000;
  const POLL_MAX_MS = 180000;

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
  const successDonorEl = document.getElementById('donate-success-donor');
  const txnIdEl = document.getElementById('donate-txn-id');
  const receiptNumberEl = document.getElementById('donate-receipt-number');
  const successAmountEl = document.getElementById('donate-success-amount');
  const successDatetimeEl = document.getElementById('donate-success-datetime');
  const closeButtons = modal.querySelectorAll('.modal-close');

  let selectedCause = '';
  let csrfToken = '';

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
    submitBtn.textContent = isSubmitting ? 'Preparing payment…' : 'Proceed to payment';
  }

  function openSuccessModal() {
    modal.classList.add('active');
    formPanel.hidden = true;
    successPanel.hidden = false;
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
    formPanel.hidden = false;
    successPanel.hidden = true;
    modal.classList.add('active');
  }

  function closeModal() {
    modal.classList.remove('active');
  }

  function updateCurrencyLabel() {
    if (currencyLabel && currencyEl) {
      currencyLabel.textContent = `(${currencyEl.value})`;
    }
  }

  function partnerReturnUrl() {
    return `${window.location.origin}/donate/`;
  }

  function buildPartnerBridgeReturnUrl() {
    const bridge = new URL(SISPL_BRIDGE_URL);
    bridge.searchParams.set('partner', partnerReturnUrl());
    return bridge.toString();
  }

  function buildSisplPayPopupUrl(reference) {
    const url = new URL(SISPL_PAY_URL);
    url.searchParams.set('ref', reference);
    url.searchParams.set('return_to', buildPartnerBridgeReturnUrl());
    url.searchParams.set('popup', '1');
    return url.toString();
  }

  function buildSisplPayRedirectUrl(reference) {
    const url = new URL(SISPL_PAY_URL);
    url.searchParams.set('ref', reference);
    url.searchParams.set('return_to', partnerReturnUrl());
    return url.toString();
  }

  function openPaymentPopup(reference) {
    const width = 520;
    const height = 720;
    const left = Math.max(0, Math.round(window.screenX + (window.outerWidth - width) / 2));
    const top = Math.max(0, Math.round(window.screenY + (window.outerHeight - height) / 2));
    const features = `popup=yes,width=${width},height=${height},left=${left},top=${top}`;

    return window.open(buildSisplPayPopupUrl(reference), POPUP_NAME, features);
  }

  function closePaymentPopup(popup) {
    if (!popup || popup.closed) return;
    try {
      popup.close();
    } catch (_err) {
      // Ignore popup close failures.
    }
  }

  function waitForPaymentPopupResult(reference, popup) {
    return new Promise((resolve, reject) => {
      let settled = false;
      let pollTimer = null;

      const cleanup = () => {
        window.removeEventListener('message', onMessage);
        if (pollTimer !== null) {
          clearInterval(pollTimer);
          pollTimer = null;
        }
      };

      const finish = (result) => {
        if (settled) return;
        settled = true;
        cleanup();
        closePaymentPopup(popup);
        resolve(result);
      };

      const fail = (reason) => {
        if (settled) return;
        settled = true;
        cleanup();
        closePaymentPopup(popup);
        reject(reason);
      };

      const onMessage = (event) => {
        if (event.origin !== SISPL_ORIGIN) return;
        const data = event.data;
        if (!data || data.type !== 'sispl-payment-result') return;

        const messageReference = data.reference || data.donation_ref || '';
        if (messageReference && messageReference !== reference) return;

        finish(data);
      };

      window.addEventListener('message', onMessage);
      pollTimer = setInterval(() => {
        if (popup && popup.closed && !settled) {
          fail({ cancelled: true });
        }
      }, 400);
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

  function savePendingDonation(data) {
    try {
      sessionStorage.setItem(PENDING_KEY, JSON.stringify(data));
    } catch (_err) {
      // Ignore storage failures.
    }
  }

  function readPendingDonation() {
    try {
      const raw = sessionStorage.getItem(PENDING_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (_err) {
      return null;
    }
  }

  function clearPendingDonation() {
    try {
      sessionStorage.removeItem(PENDING_KEY);
    } catch (_err) {
      // Ignore storage failures.
    }
  }

  function startPaymentPolling(reference, onCompleted) {
    let pollTimer = null;
    const startedAt = Date.now();

    const stopPolling = () => {
      if (pollTimer !== null) {
        clearInterval(pollTimer);
        pollTimer = null;
      }
    };

    const poll = async () => {
      try {
        const payment = await fetchPaymentStatus(reference, true);
        if (payment?.status === 'completed') {
          stopPolling();
          await onCompleted();
        }
      } catch (_err) {
        // Keep polling through transient errors (UPI QR can take a few seconds).
      }
    };

    poll();
    pollTimer = setInterval(() => {
      if (Date.now() - startedAt >= POLL_MAX_MS) {
        stopPolling();
        return;
      }
      poll();
    }, POLL_INTERVAL_MS);

    return stopPolling;
  }

  function showSuccessPanel(payment, fallbackAmount, fallbackCurrency, fallbackCause) {
    showConfirming(false);
    showError('');
    openSuccessModal();

    const cause = payment.project_name || fallbackCause || selectedCause;
    if (successCauseEl) successCauseEl.textContent = cause;
    if (successDonorEl) {
      successDonorEl.textContent = payment.donor_name || '';
      successDonorEl.closest('div').hidden = !payment.donor_name;
    }
    if (txnIdEl) txnIdEl.textContent = payment.razorpay_payment_id || '';
    if (receiptNumberEl) {
      receiptNumberEl.textContent = payment.reference || payment.public_reference || '';
    }
    if (successAmountEl) {
      successAmountEl.textContent = formatAmount(
        payment.amount ?? fallbackAmount,
        payment.currency || fallbackCurrency,
      );
    }
    if (successDatetimeEl) {
      successDatetimeEl.textContent = formatDateTime(payment.updated_at || payment.created_at);
    }

    clearPendingDonation();
  }

  async function fetchPaymentStatus(reference, syncFromGateway) {
    const query = syncFromGateway ? '?sync=1' : '';
    const result = await donateApi(`/payments/${encodeURIComponent(reference)}${query}`, {
      method: 'GET',
      headers: { Accept: 'application/json' },
    });
    return result.payment || null;
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

  async function resolveCompletedPayment(reference, razorpayResponse, token) {
    try {
      return await verifyHandlerPayment(reference, razorpayResponse, token);
    } catch (_verifyError) {
      const synced = await fetchPaymentStatus(reference, true);
      if (synced && synced.status === 'completed') {
        return synced;
      }
      throw _verifyError;
    }
  }

  async function finalizeCompletedPayment(reference, pending, razorpayResponse) {
    openSuccessModal();
    showConfirming(true);
    showError('');

    let payment = null;

    if (
      razorpayResponse?.razorpay_payment_id
      && razorpayResponse?.razorpay_order_id
      && razorpayResponse?.razorpay_signature
    ) {
      const token = await ensureCsrf();
      payment = await resolveCompletedPayment(reference, razorpayResponse, token);
    } else {
      payment = await fetchPaymentStatus(reference, false);
      if (!payment || payment.status !== 'completed') {
        payment = await fetchPaymentStatus(reference, true);
      }
    }

    if (!payment || payment.status !== 'completed') {
      throw new Error(
        'Payment verification failed. If the amount was deducted, contact info@positivetree.ngo with reference '
          + reference + '.',
      );
    }

    showSuccessPanel(
      payment,
      pending?.amount,
      pending?.currency,
      pending?.cause,
    );
  }

  function isCancelledResult(result) {
    return result?.status === 'cancelled' || result?.payment_cancelled === '1';
  }

  function isFailedResult(result) {
    return result?.status === 'failed' || result?.payment_failed === '1';
  }

  async function runSisplPopupCheckout(reference, pending) {
    setSubmitting(false);
    showConfirming(true);
    showError('');

    let settled = false;
    let popup = null;
    let stopPolling = null;

    const finishOnce = async (result) => {
      if (settled) return;
      settled = true;
      if (stopPolling) stopPolling();
      closePaymentPopup(popup);

      const razorpayResponse = result?.razorpay_payment_id
        ? {
            razorpay_payment_id: result.razorpay_payment_id,
            razorpay_order_id: result.razorpay_order_id,
            razorpay_signature: result.razorpay_signature,
          }
        : null;

      await finalizeCompletedPayment(reference, pending, razorpayResponse);
    };

    stopPolling = startPaymentPolling(reference, async () => {
      await finishOnce(null);
    });

    popup = openPaymentPopup(reference);
    if (!popup) {
      if (stopPolling) stopPolling();
      window.location.assign(buildSisplPayRedirectUrl(reference));
      return;
    }

    try {
      const result = await waitForPaymentPopupResult(reference, popup);

      if (settled) {
        return;
      }

      if (stopPolling) stopPolling();

      if (isCancelledResult(result)) {
        settled = true;
        const synced = await fetchPaymentStatus(reference, true);
        if (synced?.status === 'completed') {
          await finishOnce(null);
          return;
        }
        showConfirming(false);
        showError('Payment was cancelled. No amount was charged—you can try again when ready.');
        return;
      }

      if (isFailedResult(result)) {
        settled = true;
        showConfirming(false);
        showError('Payment could not be completed. Please try again or contact info@positivetree.ngo if you need help.');
        return;
      }

      await finishOnce(result);
    } catch (err) {
      if (stopPolling) stopPolling();

      if (!settled) {
        try {
          const synced = await fetchPaymentStatus(reference, true);
          if (synced?.status === 'completed') {
            await finishOnce(null);
            return;
          }
        } catch (_syncErr) {
          // Fall through.
        }
      }

      if (settled) {
        return;
      }

      showConfirming(false);
      if (err && err.cancelled) {
        showError('Payment was cancelled. No amount was charged—you can try again when ready.');
        return;
      }
      showError(
        err && err.message
          ? err.message
          : 'Payment could not be completed. Please try again or contact info@positivetree.ngo if you need help.',
      );
    }
  }

  function handlePaymentCancelledOrFailed(params, pending) {
    const paymentCancelled = params.get('payment_cancelled') === '1';
    const paymentFailed = params.get('payment_failed') === '1';
    if (!paymentCancelled && !paymentFailed) {
      return false;
    }

    history.replaceState({}, '', `${window.location.pathname}`);

    const cause = pending?.cause || '';
    if (cause) {
      openModal(cause);
    } else {
      modal.classList.add('active');
      formPanel.hidden = false;
      successPanel.hidden = true;
      showConfirming(false);
    }

    showError(
      paymentCancelled
        ? 'Payment was cancelled. No amount was charged—you can try again when ready.'
        : 'Payment could not be completed. Please try again or contact info@positivetree.ngo if you need help.',
    );
    return true;
  }

  async function handlePaymentReturn() {
    const params = new URLSearchParams(window.location.search);
    const reference = params.get('donation_ref');
    const paymentComplete = params.get('payment_complete') === '1';
    const paymentId = params.get('razorpay_payment_id');
    const orderId = params.get('razorpay_order_id');
    const signature = params.get('razorpay_signature');
    const pending = readPendingDonation();

    if (!reference) {
      return;
    }

    if (handlePaymentCancelledOrFailed(params, pending)) {
      return;
    }

    const hasRazorpayReturn = Boolean(paymentId && orderId && signature);
    if (!paymentComplete && !hasRazorpayReturn) {
      return;
    }

    history.replaceState({}, '', `${window.location.pathname}`);

    try {
      await finalizeCompletedPayment(
        reference,
        pending,
        paymentId && orderId && signature
          ? {
              razorpay_payment_id: paymentId,
              razorpay_order_id: orderId,
              razorpay_signature: signature,
            }
          : null,
      );
    } catch (err) {
      showConfirming(false);
      formPanel.hidden = false;
      successPanel.hidden = true;
      showError(
        err && err.message
          ? err.message
          : 'Payment verification failed. If the amount was deducted, contact info@positivetree.ngo with reference '
            + reference + '.',
      );
    }
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

      if (!intent.payment?.reference) {
        throw new Error('Unable to start payment. Please try again.');
      }

      const pending = {
        reference: intent.payment.reference,
        cause: selectedCause,
        amount,
        currency,
        donor_name: donorName,
      };

      savePendingDonation(pending);
      await runSisplPopupCheckout(intent.payment.reference, pending);
    } catch (err) {
      showConfirming(false);
      setSubmitting(false);
      showError(err && err.message ? err.message : 'Something went wrong. Please try again.');
    }
  });

  handlePaymentReturn();
})();
