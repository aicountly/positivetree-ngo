(function () {
  const modal = document.getElementById('donate-modal');
  if (!modal) return;

  const formPanel = document.getElementById('donate-form-panel');
  const successPanel = document.getElementById('donate-success-panel');
  const form = document.getElementById('donate-form');
  const causeDisplay = document.getElementById('donate-cause-display');
  const errorEl = document.getElementById('donate-error');
  const submitBtn = document.getElementById('donate-submit');
  const receiptNumberEl = document.getElementById('donate-receipt-number');
  const receiptDownloadEl = document.getElementById('donate-receipt-download');
  const closeButtons = modal.querySelectorAll('.modal-close');

  let selectedCause = '';
  let razorpayKeyId = null;
  let razorpayLoaded = false;

  function showError(message) {
    errorEl.textContent = message;
    errorEl.hidden = !message;
  }

  function openModal(cause) {
    selectedCause = cause;
    causeDisplay.textContent = cause;
    form.reset();
    showError('');
    formPanel.hidden = false;
    successPanel.hidden = true;
    modal.classList.add('active');
  }

  function closeModal() {
    modal.classList.remove('active');
  }

  function loadRazorpayScript() {
    if (razorpayLoaded) {
      return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = 'https://checkout.razorpay.com/v1/checkout.js';
      script.onload = () => {
        razorpayLoaded = true;
        resolve();
      };
      script.onerror = () => reject(new Error('Unable to load payment gateway'));
      document.body.appendChild(script);
    });
  }

  async function ensureConfig() {
    if (razorpayKeyId) return razorpayKeyId;

    const response = await fetch('/api/payments/razorpay/config');
    const data = await response.json();

    if (!response.ok || !data.configured || !data.key_id) {
      throw new Error('Online donations are not available yet. Please contact us directly.');
    }

    razorpayKeyId = data.key_id;
    return razorpayKeyId;
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

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    showError('');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processing...';

    try {
      const formData = new FormData(form);
      const payload = {
        cause: selectedCause,
        amount_inr: Number(formData.get('amount_inr')),
        donor_name: String(formData.get('donor_name') || '').trim(),
        donor_email: String(formData.get('donor_email') || '').trim(),
        donor_phone: String(formData.get('donor_phone') || '').trim() || null,
      };

      if (!payload.donor_name || !payload.donor_email || payload.amount_inr < 1) {
        throw new Error('Please fill in all required fields with a valid amount.');
      }

      await ensureConfig();
      await loadRazorpayScript();

      const orderResponse = await fetch('/api/payments/razorpay/order', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const orderData = await orderResponse.json();
      if (!orderResponse.ok) {
        throw new Error(orderData.error || 'Unable to start payment');
      }

      const paymentResult = await new Promise((resolve, reject) => {
        const checkout = new window.Razorpay({
          key: orderData.key_id || razorpayKeyId,
          amount: orderData.amount_paise,
          currency: orderData.currency || 'INR',
          name: 'Positive Tree Foundation',
          description: selectedCause,
          order_id: orderData.order_id,
          prefill: {
            name: payload.donor_name,
            email: payload.donor_email,
            contact: payload.donor_phone || undefined,
          },
          handler: async (response) => {
            try {
              const verifyResponse = await fetch('/api/payments/razorpay/verify', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                  razorpay_order_id: response.razorpay_order_id,
                  razorpay_payment_id: response.razorpay_payment_id,
                  razorpay_signature: response.razorpay_signature,
                }),
              });
              const verifyData = await verifyResponse.json();
              if (!verifyResponse.ok) {
                throw new Error(verifyData.error || 'Payment verification failed');
              }
              resolve(verifyData.donation);
            } catch (error) {
              reject(error);
            }
          },
          modal: {
            ondismiss: () => reject(new Error('Payment cancelled')),
          },
        });

        checkout.on('payment.failed', (response) => {
          reject(new Error(response.error?.description || 'Payment failed'));
        });

        checkout.open();
      });

      formPanel.hidden = true;
      successPanel.hidden = false;
      receiptNumberEl.textContent = paymentResult.receipt_number || `#${paymentResult.id}`;
      if (paymentResult.public_receipt_token && receiptDownloadEl) {
        receiptDownloadEl.href = `/api/public/receipt/${paymentResult.public_receipt_token}?format=pdf`;
        receiptDownloadEl.hidden = false;
      } else if (receiptDownloadEl) {
        receiptDownloadEl.hidden = true;
      }
    } catch (error) {
      showError(error.message || 'Something went wrong. Please try again.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Proceed to payment';
    }
  });
})();
