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
    .certificate { width: 100%; }
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
    .opening {
      text-align: center;
      color: #475569;
      font-size: 12px;
      margin: 12px 0 6px;
    }
    .donor-name {
      text-align: center;
      color: var(--green);
      font-size: 20px;
      font-weight: bold;
      margin: 8px 0 12px;
    }
    .body-text {
      text-align: center;
      color: #334155;
      font-size: 11px;
      line-height: 1.6;
      margin: 0 0 14px;
      padding: 0 16px;
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
      font-size: 14px;
      line-height: 52px;
      font-weight: bold;
    }
    .amount-label {
      color: var(--muted);
      font-size: 9px;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      margin: 0 0 4px;
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
      font-size: 9px;
      letter-spacing: 0.4px;
      text-transform: uppercase;
    }
    .amount-words {
      font-size: 11px;
      color: #334155;
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
      background: var(--brown);
    }
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
    .pan-bar {
      background: #fff7ed;
      border: 1px solid #fed7aa;
      border-radius: 8px;
      padding: 8px 12px;
      margin-bottom: 12px;
      text-align: center;
      color: var(--brown);
      font-size: 11px;
      font-weight: bold;
    }
    .eighty-g {
      border: 1px solid #bbf7d0;
      background: #f0fdf4;
      border-radius: 10px;
      padding: 10px 12px;
      margin-bottom: 12px;
    }
    .eighty-g-title {
      color: var(--green);
      font-size: 11px;
      font-weight: bold;
      margin: 0 0 6px;
      text-transform: uppercase;
    }
    .eighty-g-reg {
      color: var(--brown);
      font-size: 10px;
      font-weight: bold;
      margin: 0 0 8px;
    }
    .eighty-g ul {
      margin: 0;
      padding-left: 16px;
      color: #334155;
      font-size: 10px;
    }
    .eighty-g li { margin-bottom: 4px; }
    .closing {
      text-align: center;
      color: #475569;
      font-size: 10px;
      line-height: 1.6;
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
    .signature-label { color: var(--muted); font-size: 9px; margin-bottom: 2px; }
    .watermark { opacity: 0.12; height: 110px; }
  </style>
</head>
<body>
  <div class="certificate">
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
          <td class="title-pill"><?= htmlspecialchars($document['title'] ?? 'DONATION CERTIFICATE') ?></td>
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

    <?php if (!empty($document['opening_text'])): ?>
    <p class="opening">
      <?= htmlspecialchars($templateService->resolveText($document['opening_text'], $placeholders)) ?>
    </p>
    <?php endif; ?>

    <div class="donor-name"><?= htmlspecialchars($placeholders['{{donor_name}}']) ?></div>

    <?php if (!empty($document['body_text'])): ?>
    <p class="body-text">
      <?= nl2br(htmlspecialchars($templateService->resolveText($document['body_text'], $placeholders))) ?>
    </p>
    <?php endif; ?>

    <div class="amount-box">
      <table class="amount-table">
        <tr>
          <td style="width: 60px; vertical-align: middle;">
            <div class="amount-icon">Rs</div>
          </td>
          <td style="vertical-align: middle;">
            <p class="amount-label">Donation Amount</p>
            <p class="amount-main"><?= htmlspecialchars($placeholders['{{amount_inr}}']) ?></p>
            <?php if (!empty($document['show_fields']['amount_words'])): ?>
            <div class="amount-divider">Amount in words</div>
            <div class="amount-words">
              <strong><?= htmlspecialchars($placeholders['{{amount_words}}']) ?></strong>
            </div>
            <?php endif; ?>
          </td>
        </tr>
      </table>
    </div>

    <table class="details-table">
      <tr>
        <td>
          <div class="panel-head">Certificate Details</div>
          <div class="panel-body">
            <?php if (!empty($document['show_fields']['receipt_number'])): ?>
            <div class="field-row">
              <div class="field-label">Receipt No.</div>
              <div class="field-value"><?= htmlspecialchars($placeholders['{{receipt_number}}']) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($document['show_fields']['certificate_number'])): ?>
            <div class="field-row">
              <div class="field-label">Certificate No.</div>
              <div class="field-value"><?= htmlspecialchars($placeholders['{{certificate_number}}']) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($document['show_fields']['cause'])): ?>
            <div class="field-row">
              <div class="field-label">Cause</div>
              <div class="field-value"><?= htmlspecialchars($placeholders['{{cause}}']) ?></div>
            </div>
            <?php endif; ?>
          </div>
        </td>
        <td>
          <div class="panel-head">Donation Details</div>
          <div class="panel-body">
            <?php if (!empty($document['show_fields']['donated_at'])): ?>
            <div class="field-row">
              <div class="field-label">Date</div>
              <div class="field-value"><?= htmlspecialchars($placeholders['{{donated_at}}']) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($document['show_fields']['channel'])): ?>
            <div class="field-row">
              <div class="field-label">Donation Mode</div>
              <div class="field-value"><?= htmlspecialchars($placeholders['{{donation_mode}}']) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($document['show_fields']['payment_method']) && $placeholders['{{payment_method}}'] !== ''): ?>
            <div class="field-row">
              <div class="field-label">Payment Method</div>
              <div class="field-value"><?= htmlspecialchars($placeholders['{{payment_method}}']) ?></div>
            </div>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    </table>

    <?php if (!empty($document['show_fields']['donor_pan']) && $placeholders['{{donor_pan}}'] !== ''): ?>
    <div class="pan-bar">
      Donor PAN: <?= htmlspecialchars($placeholders['{{donor_pan}}']) ?>
    </div>
    <?php endif; ?>

    <?php
    $eightyGNotes = $document['eighty_g_notes'] ?? [];
    $eightyGReg = trim((string) ($document['eighty_g_registration_number'] ?? ''));
    if ($eightyGReg !== '' || !empty($eightyGNotes)):
    ?>
    <div class="eighty-g">
      <p class="eighty-g-title">80G Tax Exemption Eligibility</p>
      <?php if ($eightyGReg !== ''): ?>
      <p class="eighty-g-reg">Registration No.: <?= htmlspecialchars($eightyGReg) ?></p>
      <?php endif; ?>
      <?php if (!empty($eightyGNotes)): ?>
      <ul>
        <?php foreach ($eightyGNotes as $note): ?>
          <?php if (trim((string) $note) !== ''): ?>
          <li><?= htmlspecialchars((string) $note) ?></li>
          <?php endif; ?>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($document['closing_text'])): ?>
    <p class="closing">
      <?= nl2br(htmlspecialchars($templateService->resolveText($document['closing_text'], $placeholders))) ?>
    </p>
    <?php endif; ?>

    <table class="footer-row">
      <tr>
        <td class="signature-block">
          <?php if ($signatureDataUri): ?>
            <img class="signature-img" src="<?= $signatureDataUri ?>" alt="Signature" />
          <?php else: ?>
            <div style="height: 48px;"></div>
          <?php endif; ?>
          <?php if (!empty($document['signatory_label'])): ?>
            <div class="signature-label"><?= htmlspecialchars($document['signatory_label']) ?></div>
          <?php endif; ?>
          <div class="signature-name"><?= htmlspecialchars($document['signatory_name'] ?? 'Authorized Signatory') ?></div>
          <div class="signature-title"><?= htmlspecialchars($document['signatory_title'] ?? '') ?></div>
        </td>
        <td class="watermark-block">
          <?php if ($logoDataUri): ?>
            <img class="watermark" src="<?= $logoDataUri ?>" alt="" />
          <?php endif; ?>
        </td>
      </tr>
    </table>
  </div>
</body>
</html>
