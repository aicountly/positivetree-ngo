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
      --panel-bg: #f4faf6;
      --amount-bg: #f0fdf4;
    }
    body {
      font-family: DejaVu Sans, sans-serif;
      color: #1e293b;
      font-size: 10px;
      line-height: 1.45;
    }
    .receipt { width: 100%; }
    .center { text-align: center; }
    .logo { height: 62px; margin-bottom: 4px; }
    .org-name {
      font-family: DejaVu Serif, serif;
      color: var(--brown);
      font-size: 18px;
      font-weight: bold;
      letter-spacing: 0.4px;
      margin: 0 0 4px;
      text-transform: uppercase;
    }
    .tagline-table {
      width: 72%;
      margin: 0 auto 8px;
      border-collapse: collapse;
    }
    .tagline-line { border-top: 1px solid var(--green); height: 1px; }
    .tagline {
      color: var(--green);
      font-size: 8px;
      letter-spacing: 1px;
      text-transform: uppercase;
      padding: 3px 8px;
      white-space: nowrap;
    }
    .title-pill-wrap { margin: 6px auto 8px; border-collapse: collapse; }
    .title-pill {
      background: var(--brown);
      color: #fff;
      padding: 6px 18px;
      font-size: 10px;
      font-weight: bold;
      letter-spacing: 0.5px;
      text-align: center;
    }
    .contact-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }
    .contact-table td {
      color: var(--muted);
      font-size: 8px;
      vertical-align: top;
      padding: 0 4px;
    }
    .contact-icon { width: 12px; height: 12px; vertical-align: middle; margin-right: 3px; }
    .meta-shell {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }
    .meta-box {
      width: 33.33%;
      border: 1px solid #dbeafe;
      background: #fff;
      padding: 7px 8px;
      vertical-align: top;
    }
    .meta-inner { width: 100%; border-collapse: collapse; }
    .meta-icon { width: 18px; vertical-align: top; padding-right: 4px; }
    .meta-icon img { width: 16px; height: 16px; }
    .meta-label { color: var(--muted); font-size: 8px; margin-bottom: 2px; }
    .meta-value { color: var(--green); font-size: 11px; font-weight: bold; }
    .details-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 8px 0;
      margin-bottom: 10px;
    }
    .details-table td {
      width: 50%;
      vertical-align: top;
      border: 1px solid #dbeafe;
      overflow: hidden;
    }
    .panel-head-table { width: 100%; border-collapse: collapse; }
    .panel-head {
      color: #fff;
      font-size: 9px;
      font-weight: bold;
      letter-spacing: 0.4px;
      padding: 6px 8px;
      text-transform: uppercase;
    }
    .panel-head.donor { background: var(--brown); }
    .panel-head.donation { background: var(--green); }
    .panel-head-icon { width: 16px; padding-right: 4px; }
    .panel-head-icon img { width: 14px; height: 14px; }
    .panel-body { padding: 6px 8px 8px; background: #fff; }
    .field-row { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    .field-icon { width: 18px; vertical-align: top; padding-top: 1px; }
    .field-icon img { width: 14px; height: 14px; }
    .field-label { color: var(--muted); font-size: 8px; margin-bottom: 1px; }
    .field-value { font-size: 10px; font-weight: bold; color: #0f172a; }
    .field-divider { border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }
    .amount-box {
      border: 1px solid #86c99a;
      background: var(--amount-bg);
      padding: 10px 12px;
      margin-bottom: 10px;
    }
    .amount-table { width: 100%; border-collapse: collapse; }
    .amount-icon-cell { width: 58px; vertical-align: middle; }
    .amount-icon-img { width: 52px; height: 52px; }
    .amount-main {
      color: var(--green);
      font-size: 16px;
      font-weight: bold;
      margin: 0 0 6px;
    }
    .amount-divider-table { width: 100%; border-collapse: collapse; margin: 6px 0; }
    .amount-divider-line { border-top: 1px solid #86efac; }
    .amount-divider-icon { width: 18px; text-align: center; }
    .amount-divider-icon img { width: 12px; height: 12px; }
    .amount-words { font-size: 10px; color: #334155; }
    .thanks {
      text-align: center;
      color: #475569;
      font-size: 9px;
      margin: 8px 0 10px;
      padding: 0 12px;
      line-height: 1.5;
    }
    .footer-row { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .signature-block { width: 55%; vertical-align: bottom; }
    .watermark-block { width: 45%; text-align: right; vertical-align: bottom; }
    .signature-img { max-height: 44px; max-width: 170px; margin-bottom: 4px; }
    .signature-name { color: var(--green); font-weight: bold; font-size: 10px; }
    .signature-title { color: #475569; font-size: 9px; }
    .watermark { opacity: 0.1; height: 100px; }
    .banner-table { width: 100%; border-collapse: collapse; }
    .banner {
      background: #fff7ed;
      border: 1px solid #fed7aa;
      text-align: center;
      color: var(--brown);
      font-size: 9px;
      padding: 7px 8px;
    }
    .banner-leaf { width: 14px; text-align: center; }
    .banner-leaf img { width: 10px; height: 10px; }
  </style>
</head>
<body>
<?php
$renderFieldRow = static function (string $iconFile, string $label, string $value): string {
    if ($value === '') {
        return '';
    }

    $icon = documentAssetDataUri($iconFile);
    $iconHtml = $icon ? '<img src="' . $icon . '" alt="" />' : '';

    return '<table class="field-row"><tr>'
        . '<td class="field-icon field-divider">' . $iconHtml . '</td>'
        . '<td class="field-divider"><div class="field-label">' . htmlspecialchars($label) . '</div>'
        . '<div class="field-value">' . htmlspecialchars($value) . '</div></td>'
        . '</tr></table>';
};

$metaCell = static function (string $iconFile, string $label, string $value): string {
    $icon = documentAssetDataUri($iconFile);
    $iconHtml = $icon ? '<img src="' . $icon . '" alt="" />' : '';

    return '<table class="meta-inner"><tr>'
        . '<td class="meta-icon">' . $iconHtml . '</td>'
        . '<td><div class="meta-label">' . htmlspecialchars($label) . '</div>'
        . '<div class="meta-value">' . htmlspecialchars($value) . '</div></td>'
        . '</tr></table>';
};
?>
  <div class="receipt">
    <div class="center">
      <?php if ($logoDataUri): ?>
        <img class="logo" src="<?= $logoDataUri ?>" alt="Logo" />
      <?php endif; ?>
      <h1 class="org-name"><?= htmlspecialchars($organization['organization_name'] ?? '') ?></h1>
      <?php if (!empty($organization['tagline'])): ?>
      <table class="tagline-table" align="center">
        <tr>
          <td class="tagline-line"></td>
          <td class="tagline"><?= htmlspecialchars($organization['tagline']) ?></td>
          <td class="tagline-line"></td>
        </tr>
      </table>
      <?php endif; ?>
      <table class="title-pill-wrap" align="center">
        <tr>
          <td class="title-pill"><?= htmlspecialchars($document['title'] ?? 'DONATION RECEIPT') ?></td>
        </tr>
      </table>
    </div>

    <table class="contact-table">
      <tr>
        <td>
          <?php if ($icon = documentAssetDataUri('icon-location.svg')): ?>
            <img class="contact-icon" src="<?= $icon ?>" alt="" />
          <?php endif; ?>
          <?= htmlspecialchars($placeholders['{{organization_address}}']) ?>
        </td>
        <?php if ($placeholders['{{organization_phone}}'] !== ''): ?>
        <td style="width: 18%; white-space: nowrap;">
          <?php if ($icon = documentAssetDataUri('icon-phone.svg')): ?>
            <img class="contact-icon" src="<?= $icon ?>" alt="" />
          <?php endif; ?>
          <?= htmlspecialchars($placeholders['{{organization_phone}}']) ?>
        </td>
        <?php endif; ?>
        <?php if ($placeholders['{{organization_email}}'] !== ''): ?>
        <td style="width: 22%; white-space: nowrap;">
          <?php if ($icon = documentAssetDataUri('icon-mail.svg')): ?>
            <img class="contact-icon" src="<?= $icon ?>" alt="" />
          <?php endif; ?>
          <?= htmlspecialchars($placeholders['{{organization_email}}']) ?>
        </td>
        <?php endif; ?>
      </tr>
    </table>

    <table class="meta-shell">
      <tr>
        <td class="meta-box">
          <?= $metaCell('icon-receipt.svg', 'Receipt Number', $placeholders['{{receipt_number}}']) ?>
        </td>
        <td class="meta-box">
          <?= $metaCell(
              'icon-calendar.svg',
              'Receipt Date',
              !empty($document['show_fields']['donated_at']) ? $placeholders['{{donated_at}}'] : '—'
          ) ?>
        </td>
        <td class="meta-box">
          <?= $metaCell(
              'icon-check.svg',
              'Payment Status',
              !empty($document['show_fields']['payment_status']) ? $placeholders['{{payment_status}}'] : '—'
          ) ?>
        </td>
      </tr>
    </table>

    <table class="details-table">
      <tr>
        <td>
          <table class="panel-head-table">
            <tr class="panel-head donor">
              <td class="panel-head-icon">
                <?php if ($icon = documentAssetDataUri('icon-person-white.svg')): ?>
                  <img src="<?= $icon ?>" alt="" />
                <?php endif; ?>
              </td>
              <td class="panel-head">Donor Details</td>
            </tr>
          </table>
          <div class="panel-body">
            <?= $renderFieldRow('icon-person.svg', 'Donor Name', $placeholders['{{donor_name}}']) ?>
            <?php if (!empty($document['show_fields']['email']) && $placeholders['{{donor_email}}'] !== ''): ?>
              <?= $renderFieldRow('icon-email.svg', 'Email', $placeholders['{{donor_email}}']) ?>
            <?php endif; ?>
            <?php if (!empty($document['show_fields']['phone']) && $placeholders['{{donor_phone}}'] !== ''): ?>
              <?= $renderFieldRow('icon-phone.svg', 'Phone', $placeholders['{{donor_phone}}']) ?>
            <?php endif; ?>
          </div>
        </td>
        <td>
          <table class="panel-head-table">
            <tr class="panel-head donation">
              <td class="panel-head-icon">
                <?php if ($icon = documentAssetDataUri('icon-heart-white.svg')): ?>
                  <img src="<?= $icon ?>" alt="" />
                <?php endif; ?>
              </td>
              <td class="panel-head">Donation Details</td>
            </tr>
          </table>
          <div class="panel-body">
            <?php if (!empty($document['show_fields']['cause'])): ?>
              <?= $renderFieldRow('icon-heart.svg', 'Cause', $placeholders['{{cause}}']) ?>
            <?php endif; ?>
            <?php if (!empty($document['show_fields']['channel'])): ?>
              <?= $renderFieldRow('icon-globe.svg', 'Channel', $placeholders['{{channel}}']) ?>
            <?php endif; ?>
            <?php if (!empty($document['show_fields']['payment_method']) && $placeholders['{{payment_method}}'] !== ''): ?>
              <?= $renderFieldRow('icon-card.svg', 'Payment Method', $placeholders['{{payment_method}}']) ?>
            <?php endif; ?>
            <?php if (!empty($document['show_fields']['transaction_ref']) && $placeholders['{{transaction_ref}}'] !== ''): ?>
              <?= $renderFieldRow('icon-ref.svg', 'Transaction Ref', $placeholders['{{transaction_ref}}']) ?>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    </table>

    <div class="amount-box">
      <table class="amount-table">
        <tr>
          <td class="amount-icon-cell">
            <?php if ($amountIcon = documentAssetDataUri('icon-amount-giving.svg')): ?>
              <img class="amount-icon-img" src="<?= $amountIcon ?>" alt="" />
            <?php endif; ?>
          </td>
          <td style="vertical-align: middle;">
            <p class="amount-main">Amount Received: <?= htmlspecialchars($placeholders['{{amount_inr}}']) ?></p>
            <?php if (!empty($document['show_fields']['amount_words'])): ?>
            <table class="amount-divider-table">
              <tr>
                <td class="amount-divider-line"></td>
                <td class="amount-divider-icon">
                  <?php if ($leafIcon = documentAssetDataUri('icon-leaf.svg')): ?>
                    <img src="<?= $leafIcon ?>" alt="" />
                  <?php endif; ?>
                </td>
                <td class="amount-divider-line"></td>
              </tr>
            </table>
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
    <table class="banner-table">
      <tr>
        <td class="banner-leaf">
          <?php if ($leafIcon = documentAssetDataUri('icon-leaf.svg')): ?>
            <img src="<?= $leafIcon ?>" alt="" />
          <?php endif; ?>
        </td>
        <td class="banner"><?= htmlspecialchars($document['banner_text']) ?></td>
        <td class="banner-leaf">
          <?php if ($leafIcon = documentAssetDataUri('icon-leaf.svg')): ?>
            <img src="<?= $leafIcon ?>" alt="" />
          <?php endif; ?>
        </td>
      </tr>
    </table>
    <?php endif; ?>
  </div>
</body>
</html>
