<?php

/**
 * Donation Receipt (A4) — supplied layout, do not redesign.
 *
 * Receives from DocumentTemplateService::renderReceiptHtml():
 *   $placeholders, $document, $organization, $logoDataUri,
 *   $signatureDataUri, $pageCss, $accentColor, $brandBrown, $templateService
 */

$logoUrl = (isset($logoDataUri) && $logoDataUri !== null && $logoDataUri !== '')
    ? $logoDataUri
    : '';

$orgName = ($placeholders['{{organization_name}}'] ?? '') !== ''
    ? $placeholders['{{organization_name}}']
    : 'POSITIVE TREE FOUNDATION';
$tagline = ($placeholders['{{organization_tagline}}'] ?? '') !== ''
    ? $placeholders['{{organization_tagline}}']
    : 'EVERY LEAF HAS A DREAM';

$address = ($placeholders['{{organization_address}}'] ?? '') !== ''
    ? $placeholders['{{organization_address}}']
    : 'Plot No. 54, Sri Kanchi Nagar, Extension II Chinnaiyankulam, Orikkai, Kancheepuram, Kanchipuram – 631502, Tamil Nadu';
$phone = ($placeholders['{{organization_phone}}'] ?? '') !== ''
    ? $placeholders['{{organization_phone}}']
    : '+91 6384184900';
$email = ($placeholders['{{organization_email}}'] ?? '') !== ''
    ? $placeholders['{{organization_email}}']
    : 'info@positivetree.ngo';

$receiptNo = ($placeholders['{{receipt_number}}'] ?? '') !== ''
    ? $placeholders['{{receipt_number}}']
    : 'PT-2026-00001';
$date = ($placeholders['{{donated_at}}'] ?? '') !== ''
    ? $placeholders['{{donated_at}}']
    : '2026-06-16 09:36:39Z';
$status = ($placeholders['{{payment_status}}'] ?? '') !== ''
    ? $placeholders['{{payment_status}}']
    : 'Successful';

$donorName = ($placeholders['{{donor_name}}'] ?? '') !== ''
    ? $placeholders['{{donor_name}}']
    : 'Sample Donor';
$donorEmail = ($placeholders['{{donor_email}}'] ?? '') !== ''
    ? $placeholders['{{donor_email}}']
    : 'donor@example.com';
$donorPhone = ($placeholders['{{donor_phone}}'] ?? '') !== ''
    ? $placeholders['{{donor_phone}}']
    : '+91 9876543210';

$cause = ($placeholders['{{cause}}'] ?? '') !== ''
    ? $placeholders['{{cause}}']
    : 'Providing Education to Needy';
$channel = ($placeholders['{{channel}}'] ?? '') !== ''
    ? $placeholders['{{channel}}']
    : 'Online';
$paymentMethod = ($placeholders['{{payment_method}}'] ?? '') !== ''
    ? $placeholders['{{payment_method}}']
    : 'Razorpay';
$transactionRef = ($placeholders['{{transaction_ref}}'] ?? '') !== ''
    ? $placeholders['{{transaction_ref}}']
    : 'pay_SAMPLE123';

$amount = ($placeholders['{{amount_inr}}'] ?? '') !== ''
    ? $placeholders['{{amount_inr}}']
    : '₹5,000.00';
$amountWords = ($placeholders['{{amount_words}}'] ?? '') !== ''
    ? $placeholders['{{amount_words}}']
    : 'Rupees Five Thousand Only';
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Donation Receipt</title>

<style>
@page {
    size: A4;
    margin: 0;
}

* {
    box-sizing: border-box;
}

html, body {
    margin: 0;
    padding: 0;
    background: #f4f6f2;
    font-family: Arial, Helvetica, sans-serif;
    color: #1f2933;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.a4-page {
    width: 794px;
    height: 1123px;
    margin: 0 auto;
    background: #ffffff;
    position: relative;
    overflow: hidden;
    border-top: 13px solid #137a28;
}

.content {
    padding: 54px 58px 42px;
}

.logo {
    width: 90px;
    display: block;
    margin: 0 auto 13px;
}

.org-title {
    margin: 0;
    text-align: center;
    font-size: 38px;
    line-height: 1;
    letter-spacing: 0.5px;
    color: #8a4f18;
    font-weight: 900;
}

.tagline {
    margin-top: 10px;
    text-align: center;
    color: #137a28;
    font-size: 15px;
    letter-spacing: 4px;
    font-weight: 800;
}

.receipt-badge-row {
    width: 430px;
    margin: 18px auto 20px;
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 12px;
}

.receipt-badge-row::before,
.receipt-badge-row::after {
    content: "";
    border-top: 2px solid #a46a22;
}

.receipt-badge {
    background: #9a631f;
    color: #fff;
    border-radius: 999px;
    padding: 9px 31px;
    font-weight: 900;
    letter-spacing: 2px;
    font-size: 17px;
}

.contact-line {
    text-align: center;
    max-width: 650px;
    margin: 0 auto 29px;
    color: #4b5b68;
    font-size: 13px;
    line-height: 1.7;
}

.contact-line span {
    color: #137a28;
    font-weight: 800;
    margin: 0 6px;
}

.summary-card {
    height: 95px;
    border: 1px solid #d8c4a8;
    border-radius: 10px;
    background: #fffdfa;
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    margin-bottom: 26px;
    box-shadow: 0 5px 14px rgba(45, 36, 20, 0.08);
}

.summary-item {
    display: grid;
    grid-template-columns: 64px 1fr;
    align-items: center;
    padding: 0 22px;
    border-right: 1px solid #d8c4a8;
}

.summary-item:last-child {
    border-right: 0;
}

.summary-icon {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #e8f5df;
    color: #137a28;
    font-size: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.summary-label {
    color: #53606b;
    font-size: 15px;
    margin-bottom: 6px;
}

.summary-value {
    color: #0f172a;
    font-size: 17px;
    font-weight: 900;
}

.summary-value.green {
    color: #117b2f;
}

.cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 17px;
    margin-bottom: 26px;
}

.info-card {
    border: 1px solid #d8c4a8;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 5px 14px rgba(45, 36, 20, 0.08);
}

.card-head {
    height: 58px;
    display: flex;
    align-items: center;
    padding: 0 24px;
    gap: 14px;
    color: #fff;
    font-weight: 900;
    letter-spacing: 0.6px;
    font-size: 17px;
}

.card-head.brown {
    background: linear-gradient(90deg, #8a4f18, #a46a22);
}

.card-head.green {
    background: linear-gradient(90deg, #0f6b22, #178735);
}

.card-head-icon {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #fff;
    color: inherit;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-body {
    padding: 22px 24px;
}

.row {
    display: grid;
    grid-template-columns: 54px 1fr;
    gap: 14px;
    align-items: center;
    min-height: 66px;
    border-bottom: 1px dashed #cfcfcf;
}

.row:last-child {
    border-bottom: 0;
}

.row-icon {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #e8f5df;
    color: #137a28;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
}

.row-label {
    color: #53606b;
    font-size: 14px;
    margin-bottom: 4px;
}

.row-value {
    color: #101828;
    font-size: 15.5px;
    font-weight: 900;
    line-height: 1.35;
}

.amount-box {
    height: 118px;
    border: 2px solid #137a28;
    background: #eff9ee;
    border-radius: 10px;
    display: grid;
    grid-template-columns: 150px 1fr;
    align-items: center;
    padding: 0 30px;
    margin-bottom: 28px;
}

.amount-icon {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    background: #d6eccd;
    color: #0d7029;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 46px;
}

.amount-title {
    color: #0b6e23;
    font-size: 34px;
    font-weight: 900;
    line-height: 1;
}

.amount-rule {
    height: 18px;
    margin: 7px 0;
    border-bottom: 1px solid rgba(19, 122, 40, 0.5);
    position: relative;
}

.amount-rule::after {
    content: "❧";
    position: absolute;
    left: 50%;
    bottom: -11px;
    transform: translateX(-50%);
    background: #eff9ee;
    color: #137a28;
    padding: 0 9px;
}

.words {
    font-size: 14.5px;
    color: #28323a;
}

.words strong {
    color: #0b6e23;
}

.ack {
    text-align: center;
    color: #3a4651;
    font-size: 15px;
    line-height: 1.6;
    width: 560px;
    margin: 0 auto 40px;
}

.sign-row {
    display: grid;
    grid-template-columns: 1fr 270px;
    gap: 40px;
    align-items: end;
    margin-top: 20px;
}

.sign-line {
    width: 190px;
    height: 42px;
    border-bottom: 2px solid #137a28;
    position: relative;
}

.sign-line::before {
    content: "Signature";
    position: absolute;
    left: 12px;
    bottom: 1px;
    color: #0a4ecb;
    font-family: "Brush Script MT", cursive;
    font-size: 28px;
    transform: rotate(-3deg);
}

.sign-title {
    margin-top: 9px;
    color: #117b2f;
    font-size: 17px;
    font-weight: 900;
}

.sign-sub {
    margin-top: 4px;
    color: #1f2933;
    font-size: 14px;
}

.watermark {
    position: absolute;
    right: 58px;
    bottom: 108px;
    width: 210px;
    opacity: 0.06;
}

.footer {
    position: absolute;
    left: 26px;
    right: 26px;
    bottom: 26px;
    height: 42px;
    border: 1px solid #dfc49a;
    background: #fff8ec;
    color: #137a28;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    border-radius: 8px;
}

@media print {
    html, body {
        background: #fff;
    }

    .a4-page {
        margin: 0;
        box-shadow: none;
    }
}
</style>
</head>

<body>
<div class="a4-page">
    <main class="content">
        <img class="logo" src="<?= esc($logoUrl) ?>" alt="Positive Tree Foundation">

        <h1 class="org-title"><?= esc($orgName) ?></h1>
        <div class="tagline"><?= esc($tagline) ?></div>

        <div class="receipt-badge-row">
            <div class="receipt-badge">DONATION RECEIPT</div>
        </div>

        <div class="contact-line">
            <span>●</span><?= esc($address) ?>
            <br>
            <span>☎</span><?= esc($phone) ?>
            <span>|</span>
            <span>✉</span><?= esc($email) ?>
        </div>

        <section class="summary-card">
            <div class="summary-item">
                <div class="summary-icon">▣</div>
                <div>
                    <div class="summary-label">Receipt Number</div>
                    <div class="summary-value green"><?= esc($receiptNo) ?></div>
                </div>
            </div>

            <div class="summary-item">
                <div class="summary-icon">▦</div>
                <div>
                    <div class="summary-label">Receipt Date</div>
                    <div class="summary-value"><?= esc($date) ?></div>
                </div>
            </div>

            <div class="summary-item">
                <div class="summary-icon">✓</div>
                <div>
                    <div class="summary-label">Payment Status</div>
                    <div class="summary-value green"><?= esc($status) ?></div>
                </div>
            </div>
        </section>

        <section class="cards">
            <div class="info-card">
                <div class="card-head brown">
                    <div class="card-head-icon">●</div>
                    DONOR DETAILS
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="row-icon">♟</div>
                        <div>
                            <div class="row-label">Donor Name</div>
                            <div class="row-value"><?= esc($donorName) ?></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="row-icon">✉</div>
                        <div>
                            <div class="row-label">Email</div>
                            <div class="row-value"><?= esc($donorEmail) ?></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="row-icon">☎</div>
                        <div>
                            <div class="row-label">Phone</div>
                            <div class="row-value"><?= esc($donorPhone) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <div class="card-head green">
                    <div class="card-head-icon">♥</div>
                    DONATION DETAILS
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="row-icon">♥</div>
                        <div>
                            <div class="row-label">Cause</div>
                            <div class="row-value"><?= esc($cause) ?></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="row-icon">◎</div>
                        <div>
                            <div class="row-label">Channel</div>
                            <div class="row-value"><?= esc($channel) ?></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="row-icon">▰</div>
                        <div>
                            <div class="row-label">Payment Method</div>
                            <div class="row-value"><?= esc($paymentMethod) ?></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="row-icon">▤</div>
                        <div>
                            <div class="row-label">Transaction Ref</div>
                            <div class="row-value"><?= esc($transactionRef) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="amount-box">
            <div class="amount-icon">♥</div>
            <div>
                <div class="amount-title">Amount Received: <?= esc($amount) ?></div>
                <div class="amount-rule"></div>
                <div class="words">
                    Amount in Words: <strong><?= esc($amountWords) ?></strong>
                </div>
            </div>
        </section>

        <p class="ack">
            Thank you for supporting Positive Tree Foundation. This receipt acknowledges
            your generous contribution towards our social impact initiatives.
        </p>

        <section class="sign-row">
            <div>
                <div class="sign-line"></div>
                <div class="sign-title">Authorized Signatory</div>
                <div class="sign-sub">Positive Tree Foundation</div>
            </div>
        </section>
    </main>

    <img class="watermark" src="<?= esc($logoUrl) ?>" alt="">
    <div class="footer">Thank you for helping us grow hope, care, and opportunity.</div>
</div>
</body>
</html>
