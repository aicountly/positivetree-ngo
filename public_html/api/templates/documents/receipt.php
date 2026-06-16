<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    <?= $pageCss ?>
    body { font-family: DejaVu Sans, sans-serif; color: #1e293b; }
    .header { text-align: center; margin-bottom: 24px; border-bottom: 2px solid var(--accent); padding-bottom: 16px; }
    .header h1 { color: var(--accent); margin: 0 0 8px; font-size: 24px; }
    .header p { margin: 0; color: #64748b; }
    .logo { max-height: 70px; margin-bottom: 12px; }
    .org-meta { text-align: center; color: #64748b; font-size: 12px; margin-bottom: 20px; }
    .meta table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .meta td { padding: 8px 0; vertical-align: top; }
    .meta td:first-child { width: 180px; color: #64748b; }
    .amount { font-size: 22px; font-weight: bold; color: var(--accent); margin: 24px 0; }
    .footer { margin-top: 40px; font-size: 12px; color: #64748b; text-align: center; }
    .signature { margin-top: 48px; }
    .signature .name { font-weight: bold; }
  </style>
</head>
<body>
  <div class="header">
    <?php if ($logoDataUri): ?>
      <img class="logo" src="<?= $logoDataUri ?>" alt="Logo" />
    <?php endif; ?>
    <h1><?= htmlspecialchars($organization['organization_name'] ?? '') ?></h1>
    <p><?= htmlspecialchars($document['title'] ?? 'Donation Receipt') ?></p>
  </div>

  <?php if (!empty($organization['address_lines']) || !empty($organization['phone'])): ?>
  <div class="org-meta">
    <?= htmlspecialchars(implode(' | ', array_filter([
      implode(', ', $organization['address_lines'] ?? []),
      $organization['phone'] ?? '',
      $organization['email'] ?? '',
    ]))) ?>
  </div>
  <?php endif; ?>

  <div class="meta">
    <table>
      <tr>
        <td>Receipt Number</td>
        <td><strong><?= htmlspecialchars($placeholders['{{receipt_number}}']) ?></strong></td>
      </tr>
      <?php if (!empty($document['show_fields']['donated_at'] ?? true)): ?>
      <tr>
        <td>Date</td>
        <td><?= htmlspecialchars($placeholders['{{donated_at}}']) ?></td>
      </tr>
      <?php endif; ?>
      <tr>
        <td>Donor Name</td>
        <td><?= htmlspecialchars($placeholders['{{donor_name}}']) ?></td>
      </tr>
      <?php if (!empty($document['show_fields']['email']) && $placeholders['{{donor_email}}'] !== ''): ?>
      <tr>
        <td>Email</td>
        <td><?= htmlspecialchars($placeholders['{{donor_email}}']) ?></td>
      </tr>
      <?php endif; ?>
      <?php if (!empty($document['show_fields']['phone']) && $placeholders['{{donor_phone}}'] !== ''): ?>
      <tr>
        <td>Phone</td>
        <td><?= htmlspecialchars($placeholders['{{donor_phone}}']) ?></td>
      </tr>
      <?php endif; ?>
      <?php if (!empty($document['show_fields']['cause'])): ?>
      <tr>
        <td>Cause</td>
        <td><?= htmlspecialchars($placeholders['{{cause}}']) ?></td>
      </tr>
      <?php endif; ?>
      <?php if (!empty($document['show_fields']['channel'])): ?>
      <tr>
        <td>Channel</td>
        <td><?= htmlspecialchars($placeholders['{{channel}}']) ?></td>
      </tr>
      <?php endif; ?>
      <?php if (!empty($document['show_fields']['payment_method']) && $placeholders['{{payment_method}}'] !== ''): ?>
      <tr>
        <td>Payment Method</td>
        <td><?= htmlspecialchars($placeholders['{{payment_method}}']) ?></td>
      </tr>
      <?php endif; ?>
      <?php if (!empty($document['show_fields']['transaction_ref']) && $placeholders['{{transaction_ref}}'] !== ''): ?>
      <tr>
        <td>Transaction Ref</td>
        <td><?= htmlspecialchars($placeholders['{{transaction_ref}}']) ?></td>
      </tr>
      <?php endif; ?>
    </table>
  </div>

  <div class="amount">Amount Received: <?= htmlspecialchars($placeholders['{{amount_inr}}']) ?></div>

  <?php if (!empty($document['show_fields']['notes']) && !empty($donation['notes'])): ?>
  <p><strong>Notes:</strong> <?= htmlspecialchars((string) $donation['notes']) ?></p>
  <?php endif; ?>

  <div class="signature">
    <div class="name"><?= htmlspecialchars($document['signature_name'] ?? '') ?></div>
    <div><?= htmlspecialchars($document['signature_title'] ?? '') ?></div>
  </div>

  <div class="footer">
    <?= htmlspecialchars($templateService->resolveText($document['footer_text'] ?? '', $placeholders)) ?>
  </div>
</body>
</html>
