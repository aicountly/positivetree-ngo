<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    <?= $pageCss ?>
    :root {
      --green: <?= htmlspecialchars($accentColor) ?>;
      --brown: <?= htmlspecialchars($brandBrown) ?>;
      --gold: #c4a35a;
      --muted: #64748b;
      --panel-bg: #f4faf6;
      --line: #d8e8dc;
    }
    body {
      font-family: DejaVu Sans, sans-serif;
      color: #1e293b;
      font-size: 10px;
      line-height: 1.4;
    }
    .page-frame {
      width: 100%;
      border-collapse: collapse;
    }
    .page-frame-outer {
      border: 2px solid var(--gold);
      padding: 3px;
    }
    .page-frame-inner {
      border: 1px solid var(--green);
      padding: 10px 12px 8px;
      position: relative;
    }
    .watermark-wrap {
      position: absolute;
      right: 8px;
      top: 120px;
      width: 180px;
      text-align: right;
      z-index: 0;
    }
    .watermark {
      opacity: 0.08;
      height: 220px;
    }
    .content { position: relative; z-index: 1; }
    .center { text-align: center; }
    .logo { height: 58px; margin-bottom: 4px; }
    .org-name {
      font-family: DejaVu Serif, serif;
      color: var(--brown);
      font-size: 16px;
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
    .title-table {
      width: 100%;
      border-collapse: collapse;
      margin: 6px 0 10px;
    }
    .title-ornament {
      border-bottom: 1px solid var(--gold);
      width: 28%;
    }
    .cert-title {
      font-family: DejaVu Serif, serif;
      color: var(--green);
      font-size: 20px;
      font-weight: bold;
      letter-spacing: 0.5px;
      text-align: center;
      padding: 0 8px;
      white-space: nowrap;
    }
    .opening-table {
      width: 100%;
      border-collapse: collapse;
      margin: 4px 0 8px;
    }
    .opening-line { border-bottom: 1px solid var(--gold); width: 35%; }
    .opening {
      text-align: center;
      color: #475569;
      font-size: 10px;
      font-style: italic;
      padding: 0 10px;
    }
    .donor-name {
      font-family: DejaVu Serif, serif;
      text-align: center;
      color: var(--green);
      font-size: 22px;
      font-weight: bold;
      margin: 6px 0 8px;
    }
    .body-text {
      text-align: center;
      color: #334155;
      font-size: 10px;
      line-height: 1.55;
      margin: 0 0 10px;
      padding: 0 18px;
    }
    .amount-shell {
      width: 72%;
      margin: 0 auto 10px;
      border-collapse: collapse;
    }
    .amount-box {
      border: 1px solid #86c99a;
      background: var(--panel-bg);
      padding: 10px 12px;
      text-align: center;
    }
    .amount-label {
      color: var(--brown);
      font-size: 8px;
      letter-spacing: 0.6px;
      text-transform: uppercase;
      margin: 0 0 4px;
    }
    .amount-main {
      font-family: DejaVu Serif, serif;
      color: var(--green);
      font-size: 20px;
      font-weight: bold;
      margin: 0 0 4px;
    }
    .amount-words {
      color: var(--brown);
      font-size: 9px;
    }
    .details-shell {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 8px;
    }
    .details-box {
      border: 1px solid #86c99a;
      background: #fff;
      padding: 8px 10px;
    }
    .details-cols {
      width: 100%;
      border-collapse: collapse;
    }
    .details-cols td {
      width: 50%;
      vertical-align: top;
      padding: 0 6px;
    }
    .detail-row {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 5px;
    }
    .detail-icon {
      width: 18px;
      vertical-align: top;
      padding-top: 1px;
    }
    .icon-circle {
      width: 16px;
      height: 16px;
      border-radius: 8px;
      background: var(--green);
      color: #fff;
      font-size: 7px;
      font-weight: bold;
      text-align: center;
      line-height: 16px;
    }
    .detail-label {
      color: var(--muted);
      font-size: 8px;
      margin-bottom: 1px;
    }
    .detail-value {
      font-size: 9px;
      font-weight: bold;
      color: #0f172a;
    }
    .pan-bar {
      border: 1px solid #86c99a;
      background: var(--panel-bg);
      padding: 6px 10px;
      margin-bottom: 8px;
    }
    .pan-table { width: 100%; border-collapse: collapse; }
    .eighty-g {
      border: 1px solid #86c99a;
      background: var(--panel-bg);
      padding: 8px 10px;
      margin-bottom: 8px;
    }
    .eighty-g-head {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 6px;
    }
    .shield-icon {
      width: 18px;
      height: 18px;
      border-radius: 9px;
      background: var(--green);
      color: #fff;
      font-size: 10px;
      font-weight: bold;
      text-align: center;
      line-height: 18px;
    }
    .eighty-g-title {
      font-family: DejaVu Serif, serif;
      color: var(--green);
      font-size: 10px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }
    .eighty-g ul {
      margin: 0;
      padding-left: 14px;
      color: #334155;
      font-size: 9px;
    }
    .eighty-g li { margin-bottom: 3px; }
    .eighty-g-reg {
      margin: 6px 0 0;
      font-size: 9px;
      color: #334155;
    }
    .eighty-g-reg strong { color: #0f172a; }
    .closing {
      text-align: center;
      color: #475569;
      font-size: 9px;
      line-height: 1.55;
      margin: 6px 0 10px;
      padding: 0 14px;
    }
    .footer-row {
      width: 100%;
      border-collapse: collapse;
      margin-top: 4px;
    }
    .seal-block { width: 42%; vertical-align: bottom; }
    .signature-block { width: 58%; vertical-align: bottom; text-align: right; }
    .seal-table {
      width: 92px;
      height: 92px;
      border: 2px solid var(--green);
      border-radius: 46px;
      border-collapse: collapse;
    }
    .seal-logo { height: 28px; margin-bottom: 2px; }
    .seal-text {
      font-size: 6px;
      color: var(--green);
      font-weight: bold;
      line-height: 1.2;
      text-transform: uppercase;
      letter-spacing: 0.2px;
    }
    .signature-img { max-height: 42px; max-width: 140px; margin-bottom: 3px; }
    .signature-name {
      font-family: DejaVu Serif, serif;
      color: var(--green);
      font-weight: bold;
      font-size: 10px;
    }
    .signature-title { color: #475569; font-size: 9px; }
    .signature-label { color: var(--muted); font-size: 8px; margin-bottom: 2px; }
    .signature-org { color: var(--brown); font-size: 9px; font-weight: bold; }
  </style>
</head>
<body>
<?php
$eightyGNotes = $document['eighty_g_notes'] ?? [];
$eightyGReg = trim((string) ($document['eighty_g_registration_number'] ?? ''));
$eightyGRegDisplay = $eightyGReg !== '' ? $eightyGReg : 'To be configured';
$bodyText = $templateService->resolveText($document['body_text'] ?? '', $placeholders);
$certTitle = $document['title'] ?? 'DONATION CERTIFICATE';
$orgName = $organization['organization_name'] ?? 'Positive Tree Foundation';
$renderDetailRow = static function (string $icon, string $label, string $value): string {
    if ($value === '') {
        return '';
    }

    return '<table class="detail-row"><tr>'
        . '<td class="detail-icon"><div class="icon-circle">' . htmlspecialchars($icon) . '</div></td>'
        . '<td><div class="detail-label">' . htmlspecialchars($label) . '</div>'
        . '<div class="detail-value">' . htmlspecialchars($value) . '</div></td>'
        . '</tr></table>';
};
?>
  <table class="page-frame">
    <tr>
      <td class="page-frame-outer">
        <table class="page-frame-inner" width="100%">
          <tr>
            <td>
              <?php if ($logoDataUri): ?>
              <div class="watermark-wrap">
                <img class="watermark" src="<?= $logoDataUri ?>" alt="" />
              </div>
              <?php endif; ?>

              <div class="content">
                <div class="center">
                  <?php if ($logoDataUri): ?>
                    <img class="logo" src="<?= $logoDataUri ?>" alt="Logo" />
                  <?php endif; ?>
                  <h1 class="org-name"><?= htmlspecialchars($orgName) ?></h1>
                  <?php if (!empty($organization['tagline'])): ?>
                  <table class="tagline-table" align="center">
                    <tr>
                      <td class="tagline-line"></td>
                      <td class="tagline"><?= htmlspecialchars($organization['tagline']) ?></td>
                      <td class="tagline-line"></td>
                    </tr>
                  </table>
                  <?php endif; ?>
                </div>

                <table class="title-table">
                  <tr>
                    <td class="title-ornament"></td>
                    <td class="cert-title"><?= htmlspecialchars($certTitle) ?></td>
                    <td class="title-ornament"></td>
                  </tr>
                </table>

                <?php if (!empty($document['opening_text'])): ?>
                <table class="opening-table">
                  <tr>
                    <td class="opening-line"></td>
                    <td class="opening"><?= htmlspecialchars($templateService->resolveText($document['opening_text'], $placeholders)) ?></td>
                    <td class="opening-line"></td>
                  </tr>
                </table>
                <?php endif; ?>

                <div class="donor-name"><?= htmlspecialchars($placeholders['{{donor_name}}']) ?></div>

                <?php if ($bodyText !== ''): ?>
                <p class="body-text"><?= htmlspecialchars($bodyText) ?></p>
                <?php endif; ?>

                <table class="amount-shell" align="center">
                  <tr>
                    <td class="amount-box">
                      <p class="amount-label">Donation Amount</p>
                      <p class="amount-main"><?= htmlspecialchars($placeholders['{{amount_inr}}']) ?></p>
                      <?php if (!empty($document['show_fields']['amount_words'])): ?>
                      <p class="amount-words">(<?= htmlspecialchars($placeholders['{{amount_words}}']) ?>)</p>
                      <?php endif; ?>
                    </td>
                  </tr>
                </table>

                <table class="details-shell">
                  <tr>
                    <td class="details-box">
                      <table class="details-cols">
                        <tr>
                          <td>
                            <?php if (!empty($document['show_fields']['receipt_number'])): ?>
                              <?= $renderDetailRow('R', 'Receipt No.', $placeholders['{{receipt_number}}']) ?>
                            <?php endif; ?>
                            <?php if (!empty($document['show_fields']['certificate_number'])): ?>
                              <?= $renderDetailRow('C', 'Certificate No.', $placeholders['{{certificate_number}}']) ?>
                            <?php endif; ?>
                            <?php if (!empty($document['show_fields']['cause'])): ?>
                              <?= $renderDetailRow('+', 'Cause', $placeholders['{{cause}}']) ?>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if (!empty($document['show_fields']['donated_at'])): ?>
                              <?= $renderDetailRow('D', 'Date', $placeholders['{{certificate_date}}']) ?>
                            <?php endif; ?>
                            <?php if (!empty($document['show_fields']['channel'])): ?>
                              <?= $renderDetailRow('M', 'Donation Mode', $placeholders['{{donation_mode}}']) ?>
                            <?php endif; ?>
                            <?php if (!empty($document['show_fields']['payment_method']) && $placeholders['{{payment_method}}'] !== ''): ?>
                              <?= $renderDetailRow('P', 'Payment Method', $placeholders['{{payment_method}}']) ?>
                            <?php endif; ?>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </table>

                <?php if (!empty($document['show_fields']['donor_pan']) && $placeholders['{{donor_pan}}'] !== ''): ?>
                <div class="pan-bar">
                  <table class="pan-table">
                    <tr>
                      <td style="width: 22px;">
                        <div class="icon-circle">ID</div>
                      </td>
                      <td>
                        <span class="detail-label">Donor PAN</span>
                        <strong><?= htmlspecialchars($placeholders['{{donor_pan}}']) ?></strong>
                      </td>
                    </tr>
                  </table>
                </div>
                <?php endif; ?>

                <div class="eighty-g">
                  <table class="eighty-g-head">
                    <tr>
                      <td style="width: 22px;">
                        <div class="shield-icon">&#10003;</div>
                      </td>
                      <td class="eighty-g-title">Income Tax Deduction Eligibility</td>
                    </tr>
                  </table>
                  <?php if (!empty($eightyGNotes)): ?>
                  <ul>
                    <?php foreach ($eightyGNotes as $note): ?>
                      <?php if (trim((string) $note) !== ''): ?>
                      <li><?= htmlspecialchars((string) $note) ?></li>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </ul>
                  <?php endif; ?>
                  <p class="eighty-g-reg">
                    80G Approval / Registration No.: <strong><?= htmlspecialchars($eightyGRegDisplay) ?></strong>
                  </p>
                </div>

                <?php if (!empty($document['closing_text'])): ?>
                <p class="closing">
                  <?= nl2br(htmlspecialchars($templateService->resolveText($document['closing_text'], $placeholders))) ?>
                </p>
                <?php endif; ?>

                <table class="footer-row">
                  <tr>
                    <td class="seal-block">
                      <table class="seal-table" align="left">
                        <tr>
                          <td align="center" style="vertical-align: middle;">
                            <?php if ($logoDataUri): ?>
                              <img class="seal-logo" src="<?= $logoDataUri ?>" alt="" />
                            <?php endif; ?>
                            <div class="seal-text">Thank You<br />For Making<br />A Difference</div>
                          </td>
                        </tr>
                      </table>
                    </td>
                    <td class="signature-block">
                      <?php if ($signatureDataUri): ?>
                        <img class="signature-img" src="<?= $signatureDataUri ?>" alt="Signature" />
                      <?php else: ?>
                        <div style="height: 42px;"></div>
                      <?php endif; ?>
                      <?php if (!empty($document['signatory_label'])): ?>
                        <div class="signature-label"><?= htmlspecialchars($document['signatory_label']) ?></div>
                      <?php endif; ?>
                      <div class="signature-name"><?= htmlspecialchars($document['signatory_name'] ?? 'Authorized Signatory') ?></div>
                      <div class="signature-title"><?= htmlspecialchars($document['signatory_title'] ?? 'Accounts Team') ?></div>
                      <div class="signature-org"><?= htmlspecialchars($orgName) ?></div>
                    </td>
                  </tr>
                </table>
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
