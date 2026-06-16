<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    <?= $pageCss ?>
    :root {
      --green: <?= htmlspecialchars($accentColor) ?>;
      --brown: <?= htmlspecialchars($brandBrown) ?>;
      --muted: #64748b;
      --line: #d1d5db;
      --panel: #f8fafc;
      --amount-bg: #f0fdf4;
    }
    body {
      font-family: DejaVu Sans, sans-serif;
      color: #1e293b;
      font-size: 11px;
      line-height: 1.45;
    }
    .receipt { width: 100%; }
    .center { text-align: center; }
    .logo { height: 72px; margin-bottom: 8px; }
    .org-name {
      color: var(--brown);
      font-size: 22px;
      font-weight: bold;
      letter-spacing: 0.5px;
      margin: 0;
      text-transform: uppercase;
    }
    .tagline-wrap {
      margin: 8px auto 10px;
      width: 70%;
      border-top: 1px solid var(--green);
      border-bottom: 1px solid var(--green);
      padding: 4px 0;
    }
    .tagline {
      color: var(--green);
      font-size: 10px;
      letter-spacing: 1px;
      text-transform: uppercase;
      margin: 0;
    }
    .title-pill-wrap { margin: 8px auto 10px; border-collapse: collapse; }
    .title-pill {
      background: var(--brown);
      color: #fff;
      padding: 6px 18px;
      font-size: 11px;
      font-weight: bold;
      letter-spacing: 0.5px;
      text-align: center;
    }
    .contact-row {
      color: var(--muted);
      font-size: 9px;
      margin-bottom: 14px;
    }
    .meta-bar {
      width: 100%;
      border-collapse: separate;
      border-spacing: 8px 0;
      margin-bottom: 12px;
    }
    .meta-bar td {
      width: 33.33%;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      background: #fff;
      padding: 8px 10px;
      vertical-align: top;
    }
    .meta-label {
      color: var(--muted);
      font-size: 9px;
      margin-bottom: 2px;
    }
    .meta-value {
      color: var(--green);
      font-size: 12px;
      font-weight: bold;
    }
    .details-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 10px 0;
      margin-bottom: 12px;
    }
    .details-table td {
      width: 50%;
      vertical-align: top;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      overflow: hidden;
    }
    .panel-head {
      color: #fff;
      font-size: 10px;
      font-weight: bold;
      letter-spacing: 0.4px;
      padding: 7px 10px;
      text-transform: uppercase;
    }
    .panel-head.donor { background: var(--brown); }
    .panel-head.donation { background: var(--green); }
    .panel-body { padding: 8px 10px 10px; background: #fff; }
    .field-row {
      padding: 6px 0;
      border-bottom: 1px dashed #cbd5e1;
    }
    .field-row:last-child { border-bottom: none; }
    .field-label {
      color: var(--muted);
      font-size: 9px;
      margin-bottom: 2px;
    }
    .field-value {
      font-size: 11px;
      font-weight: bold;
      color: #0f172a;
    }
    .amount-box {
      border: 1px solid #bbf7d0;
      background: var(--amount-bg);
      border-radius: 10px;
      padding: 12px 14px;
      margin-bottom: 12px;
    }
    .amount-table { width: 100%; border-collapse: collapse; }
    .amount-icon {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background: var(--green);
      color: #fff;
      text-align: center;
      font-size: 22px;
      line-height: 52px;
      font-weight: bold;
    }
    .amount-main {
      color: var(--green);
      font-size: 18px;
      font-weight: bold;
      margin: 0 0 6px;
    }
    .amount-divider {
      border-top: 1px solid #86efac;
      margin: 8px 0;
      text-align: center;
      color: var(--green);
      font-size: 10px;
    }
    .amount-words {
      font-size: 11px;
      color: #334155;
    }
    .thanks {
      text-align: center;
      color: #475569;
      font-size: 10px;
      margin: 10px 0 14px;
      padding: 0 12px;
    }
    .footer-row {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }
    .signature-block { width: 55%; vertical-align: bottom; }
    .watermark-block { width: 45%; text-align: right; vertical-align: bottom; }
    .signature-img { max-height: 48px; max-width: 160px; margin-bottom: 4px; }
    .signature-name {
      color: var(--green);
      font-weight: bold;
      font-size: 11px;
    }
    .signature-title { color: #475569; font-size: 10px; }
    .watermark { opacity: 0.12; height: 110px; }
    .banner {
      background: #fff7ed;
      border: 1px solid #fed7aa;
      border-radius: 8px;
      text-align: center;
      color: var(--brown);
      font-size: 10px;
      padding: 8px 10px;
    }
  </style>
</head>
<body>
  <div class="receipt">
    <div class="center">
      <?php if ($logoDataUri): ?>
        <img class="logo" src="<?= $logoDataUri ?>" alt="Logo" />
      <?php endif; ?>
      <h1 class="org-name"><?= htmlspecialchars($organization['organization_name'] ?? '') ?></h1>
      <?php if (!empty($organization['tagline'])): ?>
      <div class="tagline-wrap">
        <p class="tagline"><?= htmlspecialchars($organization['tagline']) ?></p>
      </div>
      <?php endif; ?>
      <table class="title-pill-wrap" align="center">
        <tr>
          <td class="title-pill"><?= htmlspecialchars($document['title'] ?? 'DONATION RECEIPT') ?></td>
        </tr>
      </table>
      <div class="contact-row">
        <?= htmlspecialchars($placeholders['{{organization_address}}']) ?>
        <?php if ($placeholders['{{organization_phone}}'] !== ''): ?>
          &nbsp;|&nbsp; <?= htmlspecialchars($placeholders['{{organization_phone}}']) ?>
        <?php endif; ?>
        <?php if ($placeholders['{{organization_email}}'] !== ''): ?>
          &nbsp;|&nbsp; <?= htmlspecialchars($placeholders['{{organization_email}}']) ?>
        <?php endif; ?>
      </div>
    </div>

    <table class="meta-bar">
      <tr>
        <td>
          <div class="meta-label">Receipt Number</div>
          <div class="meta-value"><?= htmlspecialchars($placeholders['{{receipt_number}}']) ?></div>
        </td>
        <td>
          <div class="meta-label">Receipt Date</div>
          <div class="meta-value">
            <?= !empty($document['show_fields']['donated_at'])
              ? htmlspecialchars($placeholders['{{donated_at}}'])
              : '—' ?>
          </div>
        </td>
        <td>
          <div class="meta-label">Payment Status</div>
          <div class="meta-value">
            <?= !empty($document['show_fields']['payment_status'])
              ? htmlspecialchars($placeholders['{{payment_status}}'])
              : '—' ?>
          </div>
        </td>
      </tr>
    </table>

    <table class="details-table">
      <tr>
        <td>
          <div class="panel-head donor">Donor Details</div>
          <div class="panel-body">
            <div class="field-row">
              <div class="field-label">Donor Name</div>
              <div class="field-value"><?= htmlspecialchars($placeholders['{{donor_name}}']) ?></div>
            </div>
            <?php if (!empty($document['show_fields']['email']) && $placeholders['{{donor_email}}'] !== ''): ?>
            <div class="field-row">
              <div class="field-label">Email</div>
              <div class="field-value"><?= htmlspecialchars($placeholders['{{donor_email}}']) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($document['show_fields']['phone']) && $placeholders['{{donor_phone}}'] !== ''): ?>
            <div class="field-row">
              <div class="field-label">Phone</div>
              <div class="field-value"><?= htmlspecialchars($placeholders['{{donor_phone}}']) ?></div>
            </div>
            <?php endif; ?>
          </div>
        </td>
        <td>
          <div class="panel-head donation">Donation Details</div>
          <div class="panel-body">
            <?php if (!empty($document['show_fields']['cause'])): ?>
            <div class="field-row">
              <div class="field-label">Cause</div>
              <div class="field-value"><?= htmlspecialchars($placeholders['{{cause}}']) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($document['show_fields']['channel'])): ?>
            <div class="field-row">
              <div class="field-label">Channel</div>
              <div class="field-value"><?= htmlspecialchars($placeholders['{{channel}}']) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($document['show_fields']['payment_method']) && $placeholders['{{payment_method}}'] !== ''): ?>
            <div class="field-row">
              <div class="field-label">Payment Method</div>
              <div class="field-value"><?= htmlspecialchars($placeholders['{{payment_method}}']) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($document['show_fields']['transaction_ref']) && $placeholders['{{transaction_ref}}'] !== ''): ?>
            <div class="field-row">
              <div class="field-label">Transaction Ref</div>
              <div class="field-value"><?= htmlspecialchars($placeholders['{{transaction_ref}}']) ?></div>
            </div>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    </table>

    <div class="amount-box">
      <table class="amount-table">
        <tr>
          <td style="width: 60px; vertical-align: middle;">
            <div class="amount-icon">Rs</div>
          </td>
          <td style="vertical-align: middle;">
            <p class="amount-main">Amount Received: <?= htmlspecialchars($placeholders['{{amount_inr}}']) ?></p>
            <?php if (!empty($document['show_fields']['amount_words'])): ?>
            <div class="amount-divider">Amount in words</div>
            <div class="amount-words">
              Amount in Words: <strong><?= htmlspecialchars($placeholders['{{amount_words}}']) ?></strong>
            </div>
            <?php endif; ?>
          </td>
        </tr>
      </table>
    </div>

    <?php if (!empty($document['show_fields']['notes']) && !empty($donation['notes'])): ?>
    <p><strong>Notes:</strong> <?= htmlspecialchars((string) $donation['notes']) ?></p>
    <?php endif; ?>

    <p class="thanks">
      <?= htmlspecialchars($templateService->resolveText($document['footer_text'] ?? '', $placeholders)) ?>
    </p>

    <table class="footer-row">
      <tr>
        <td class="signature-block">
          <?php if ($signatureDataUri): ?>
            <img class="signature-img" src="<?= $signatureDataUri ?>" alt="Signature" />
          <?php else: ?>
            <div style="height: 48px;"></div>
          <?php endif; ?>
          <div class="signature-name"><?= htmlspecialchars($document['signature_name'] ?? 'Authorized Signatory') ?></div>
          <div class="signature-title"><?= htmlspecialchars($document['signature_title'] ?? '') ?></div>
        </td>
        <td class="watermark-block">
          <?php if ($logoDataUri): ?>
            <img class="watermark" src="<?= $logoDataUri ?>" alt="" />
          <?php endif; ?>
        </td>
      </tr>
    </table>

    <?php if (!empty($document['banner_text'])): ?>
    <div class="banner"><?= htmlspecialchars($document['banner_text']) ?></div>
    <?php endif; ?>
  </div>
</body>
</html>
