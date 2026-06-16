<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; color: #1e293b; margin: 40px; }
    .header { text-align: center; margin-bottom: 32px; border-bottom: 2px solid #15803d; padding-bottom: 16px; }
    .header h1 { color: #15803d; margin: 0 0 8px; font-size: 24px; }
    .header p { margin: 0; color: #64748b; }
    .meta { margin-bottom: 24px; }
    .meta table { width: 100%; border-collapse: collapse; }
    .meta td { padding: 8px 0; vertical-align: top; }
    .meta td:first-child { width: 180px; color: #64748b; }
    .amount { font-size: 22px; font-weight: bold; color: #15803d; margin: 24px 0; }
    .footer { margin-top: 48px; font-size: 12px; color: #64748b; text-align: center; }
  </style>
</head>
<body>
  <div class="header">
    <h1>Positive Tree Foundation</h1>
    <p>Donation Receipt</p>
  </div>

  <div class="meta">
    <table>
      <tr>
        <td>Receipt Number</td>
        <td><strong><?= htmlspecialchars((string) $donation['receipt_number']) ?></strong></td>
      </tr>
      <tr>
        <td>Date</td>
        <td><?= htmlspecialchars((string) $donatedAt) ?></td>
      </tr>
      <tr>
        <td>Donor Name</td>
        <td><?= htmlspecialchars((string) $donation['donor_name']) ?></td>
      </tr>
      <?php if (!empty($donation['donor_email'])): ?>
      <tr>
        <td>Email</td>
        <td><?= htmlspecialchars((string) $donation['donor_email']) ?></td>
      </tr>
      <?php endif; ?>
      <?php if (!empty($donation['donor_phone'])): ?>
      <tr>
        <td>Phone</td>
        <td><?= htmlspecialchars((string) $donation['donor_phone']) ?></td>
      </tr>
      <?php endif; ?>
      <tr>
        <td>Cause</td>
        <td><?= htmlspecialchars((string) $donation['cause']) ?></td>
      </tr>
      <tr>
        <td>Channel</td>
        <td><?= htmlspecialchars(ucfirst((string) $donation['channel'])) ?></td>
      </tr>
      <?php if (!empty($donation['payment_method'])): ?>
      <tr>
        <td>Payment Method</td>
        <td><?= htmlspecialchars(str_replace('_', ' ', (string) $donation['payment_method'])) ?></td>
      </tr>
      <?php endif; ?>
      <?php if (!empty($donation['transaction_ref'])): ?>
      <tr>
        <td>Transaction Ref</td>
        <td><?= htmlspecialchars((string) $donation['transaction_ref']) ?></td>
      </tr>
      <?php endif; ?>
    </table>
  </div>

  <div class="amount">Amount Received: ₹<?= htmlspecialchars($amountInr) ?></div>

  <?php if (!empty($donation['notes'])): ?>
  <p><strong>Notes:</strong> <?= htmlspecialchars((string) $donation['notes']) ?></p>
  <?php endif; ?>

  <div class="footer">
    Thank you for supporting Positive Tree Foundation. This receipt acknowledges your contribution.
  </div>
</body>
</html>
