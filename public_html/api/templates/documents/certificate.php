<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    <?= $pageCss ?>
    body { font-family: DejaVu Sans, sans-serif; color: #1e293b; }
    .header { text-align: center; margin-bottom: 32px; }
    .logo { max-height: 80px; margin-bottom: 16px; }
    .title { font-size: 28px; color: var(--accent); margin: 0 0 8px; letter-spacing: 1px; }
    .org-name { font-size: 16px; color: #64748b; margin: 0; }
    .border { border: 3px double var(--accent); padding: 32px; margin-top: 16px; }
    .opening { text-align: center; margin-bottom: 24px; font-size: 14px; line-height: 1.6; }
    .body-text { text-align: center; font-size: 16px; line-height: 1.8; margin: 24px 0; }
    .donor-name { font-size: 22px; font-weight: bold; color: var(--accent); margin: 16px 0; }
    .amount { font-size: 20px; font-weight: bold; margin: 16px 0; }
    .details { margin: 24px auto; max-width: 400px; font-size: 13px; }
    .details table { width: 100%; border-collapse: collapse; }
    .details td { padding: 6px 0; }
    .details td:first-child { color: #64748b; width: 140px; }
    .closing { text-align: center; margin-top: 32px; font-size: 14px; line-height: 1.6; }
    .signature { margin-top: 48px; text-align: right; }
    .signature .label { font-size: 12px; color: #64748b; }
    .signature .name { font-weight: bold; font-size: 16px; }
    .signature .title { font-size: 13px; color: #64748b; }
  </style>
</head>
<body>
  <div class="header">
    <?php if ($logoDataUri): ?>
      <img class="logo" src="<?= $logoDataUri ?>" alt="Logo" />
    <?php endif; ?>
    <h1 class="title"><?= htmlspecialchars($document['title'] ?? 'Donation Certificate') ?></h1>
    <p class="org-name"><?= htmlspecialchars($organization['organization_name'] ?? '') ?></p>
  </div>

  <div class="border">
    <?php if (!empty($document['opening_text'])): ?>
    <div class="opening">
      <?= nl2br(htmlspecialchars($templateService->resolveText($document['opening_text'], $placeholders))) ?>
    </div>
    <?php endif; ?>

    <div class="body-text">
      <?= nl2br(htmlspecialchars($templateService->resolveText($document['body_text'] ?? '', $placeholders))) ?>
    </div>

    <div class="donor-name"><?= htmlspecialchars($placeholders['{{donor_name}}']) ?></div>

    <?php if (!empty($document['show_fields']['amount_words'])): ?>
    <div class="amount"><?= htmlspecialchars($placeholders['{{amount_words}}']) ?> (<?= htmlspecialchars($placeholders['{{amount_inr}}']) ?>)</div>
    <?php else: ?>
    <div class="amount"><?= htmlspecialchars($placeholders['{{amount_inr}}']) ?></div>
    <?php endif; ?>

    <div class="details">
      <table>
        <?php if (!empty($document['show_fields']['receipt_number'])): ?>
        <tr>
          <td>Receipt No.</td>
          <td><?= htmlspecialchars($placeholders['{{receipt_number}}']) ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($document['show_fields']['certificate_number'])): ?>
        <tr>
          <td>Certificate No.</td>
          <td><?= htmlspecialchars($placeholders['{{certificate_number}}']) ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($document['show_fields']['cause'])): ?>
        <tr>
          <td>Cause</td>
          <td><?= htmlspecialchars($placeholders['{{cause}}']) ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($document['show_fields']['donated_at'])): ?>
        <tr>
          <td>Date</td>
          <td><?= htmlspecialchars($placeholders['{{donated_at}}']) ?></td>
        </tr>
        <?php endif; ?>
      </table>
    </div>

    <?php if (!empty($document['closing_text'])): ?>
    <div class="closing">
      <?= nl2br(htmlspecialchars($templateService->resolveText($document['closing_text'], $placeholders))) ?>
    </div>
    <?php endif; ?>

    <div class="signature">
      <?php if (!empty($document['signatory_label'])): ?>
      <div class="label"><?= htmlspecialchars($document['signatory_label']) ?></div>
      <?php endif; ?>
      <div class="name"><?= htmlspecialchars($document['signatory_name'] ?? '') ?></div>
      <div class="title"><?= htmlspecialchars($document['signatory_title'] ?? '') ?></div>
    </div>
  </div>
</body>
</html>
