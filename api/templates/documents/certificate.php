<?php

/**
 * Donation Certificate (A4) — supplied layout, do not redesign.
 *
 * Receives from DocumentTemplateService::renderCertificateHtml():
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

$donorName = ($placeholders['{{donor_name}}'] ?? '') !== ''
    ? $placeholders['{{donor_name}}']
    : 'Sample Donor';
$donorPan = ($placeholders['{{donor_pan}}'] ?? '') !== ''
    ? $placeholders['{{donor_pan}}']
    : 'ABCDE1234F';

$amount = ($placeholders['{{amount_inr}}'] ?? '') !== ''
    ? $placeholders['{{amount_inr}}']
    : '₹5,000.00';
$amountWords = ($placeholders['{{amount_words}}'] ?? '') !== ''
    ? $placeholders['{{amount_words}}']
    : 'Rupees Five Thousand Only';

$cause = ($placeholders['{{cause}}'] ?? '') !== ''
    ? $placeholders['{{cause}}']
    : 'Providing Education to Needy';

$receiptNo = ($placeholders['{{receipt_number}}'] ?? '') !== ''
    ? $placeholders['{{receipt_number}}']
    : 'PT-2026-00001';
$certificateNo = ($placeholders['{{certificate_number}}'] ?? '') !== ''
    ? $placeholders['{{certificate_number}}']
    : 'PT-CERT-2026-00001';

$date = ($placeholders['{{certificate_date}}'] ?? '') !== ''
    ? $placeholders['{{certificate_date}}']
    : '2026-06-16T09:38:07Z';
$donationMode = ($placeholders['{{donation_mode}}'] ?? '') !== ''
    ? $placeholders['{{donation_mode}}']
    : 'Online';
$paymentMethod = ($placeholders['{{payment_method}}'] ?? '') !== ''
    ? $placeholders['{{payment_method}}']
    : 'Razorpay';

$deductionSection = '80G';
$deductionLaw = 'Section 80G of the Income Tax Act, 1961';
$eightyGRegRaw = isset($document['eighty_g_registration_number'])
    ? trim((string) $document['eighty_g_registration_number'])
    : '';
$approvalNo = $eightyGRegRaw !== '' ? $eightyGRegRaw : 'To be configured';
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Donation Certificate</title>

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
    background: #f3f4ef;
    font-family: Arial, Helvetica, sans-serif;
    color: #1f2933;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.a4-page {
    width: 794px;
    height: 1123px;
    margin: 0 auto;
    background:
        radial-gradient(circle at 86% 37%, rgba(37, 128, 51, 0.07) 0, rgba(37, 128, 51, 0.04) 18%, transparent 34%),
        linear-gradient(180deg, #fffdf8 0%, #fffefb 100%);
    position: relative;
    overflow: hidden;
    border-top: 10px solid #137a28;
}

.certificate-frame {
    position: absolute;
    left: 26px;
    right: 26px;
    top: 26px;
    bottom: 26px;
    border: 2px solid #a46a22;
    outline: 2px solid rgba(18, 126, 46, 0.25);
    outline-offset: -7px;
}

.corner {
    position: absolute;
    width: 92px;
    height: 92px;
    border-color: #a46a22;
    z-index: 3;
}

.corner.tl {
    left: 36px;
    top: 36px;
    border-top: 3px solid;
    border-left: 3px solid;
    border-radius: 18px 0 0 0;
}

.corner.tr {
    right: 36px;
    top: 36px;
    border-top: 3px solid;
    border-right: 3px solid;
    border-radius: 0 18px 0 0;
}

.corner.bl {
    left: 36px;
    bottom: 36px;
    border-bottom: 3px solid;
    border-left: 3px solid;
    border-radius: 0 0 0 18px;
}

.corner.br {
    right: 36px;
    bottom: 36px;
    border-bottom: 3px solid;
    border-right: 3px solid;
    border-radius: 0 0 18px 0;
}

.watermark-tree {
    position: absolute;
    right: 62px;
    top: 330px;
    width: 250px;
    opacity: 0.06;
    z-index: 1;
}

.content {
    position: relative;
    z-index: 5;
    padding: 58px 74px 52px;
    height: 100%;
    text-align: center;
}

.logo {
    width: 72px;
    height: auto;
    display: block;
    margin: 0 auto 12px;
}

.org-title {
    font-size: 29px;
    letter-spacing: 1px;
    color: #8a4f18;
    font-weight: 800;
    margin: 0;
}

.tagline {
    color: #117b2f;
    font-size: 13px;
    letter-spacing: 4px;
    font-weight: 700;
    margin-top: 8px;
}

.divider-leaf {
    width: 310px;
    height: 24px;
    margin: 18px auto 8px;
    position: relative;
}

.divider-leaf::before,
.divider-leaf::after {
    content: "";
    position: absolute;
    top: 12px;
    width: 132px;
    border-top: 2px solid rgba(164, 106, 34, 0.55);
}

.divider-leaf::before {
    left: 0;
}

.divider-leaf::after {
    right: 0;
}

.divider-leaf span {
    color: #137a28;
    font-size: 25px;
    line-height: 24px;
}

.certificate-title {
    font-family: Georgia, 'Times New Roman', serif;
    color: #0b6e23;
    font-size: 50px;
    line-height: 1;
    font-weight: 700;
    letter-spacing: 1px;
    margin: 8px 0 28px;
    text-transform: uppercase;
    text-shadow: 0 2px 0 rgba(11, 110, 35, 0.08);
}

.certify {
    font-family: Georgia, 'Times New Roman', serif;
    color: #333;
    font-size: 19px;
    margin: 0 0 18px;
}

.donor-name {
    font-family: Georgia, 'Times New Roman', serif;
    color: #0b6e23;
    font-size: 45px;
    font-weight: 700;
    margin: 0 0 10px;
}

.ornament-line {
    width: 285px;
    height: 14px;
    margin: 0 auto 24px;
    border-top: 2px solid #a46a22;
    position: relative;
}

.ornament-line::after {
    content: "◆";
    color: #a46a22;
    position: absolute;
    left: 50%;
    top: -10px;
    transform: translateX(-50%);
    background: #fffdf8;
    padding: 0 10px;
    font-size: 11px;
}

.statement {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 19px;
    line-height: 1.65;
    color: #28323a;
    margin: 0 auto 20px;
    max-width: 620px;
}

.statement strong {
    color: #0b6e23;
}

.amount-badge {
    width: 430px;
    height: 104px;
    margin: 18px auto 28px;
    border: 2px solid #138434;
    background: #fffaf0;
    position: relative;
    border-radius: 6px;
    box-shadow: inset 0 0 0 4px rgba(164, 106, 34, 0.08);
}

.amount-badge::before,
.amount-badge::after {
    content: "❧";
    position: absolute;
    top: 44px;
    color: #198c3c;
    font-size: 22px;
}

.amount-badge::before {
    left: 24px;
}

.amount-badge::after {
    right: 24px;
}

.amount-label {
    margin-top: 12px;
    font-size: 12px;
    letter-spacing: 2px;
    color: #8a4f18;
    font-weight: 800;
}

.amount-value {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 46px;
    color: #0b6e23;
    font-weight: 800;
    line-height: 1;
    margin-top: 6px;
}

.amount-words {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 14px;
    color: #8a4f18;
    font-weight: 700;
    margin-top: 2px;
}

.details-box {
    width: 560px;
    margin: 0 auto 14px;
    border: 1.6px solid #138434;
    border-radius: 10px;
    padding: 18px 24px;
    background: rgba(255, 255, 255, 0.78);
    display: grid;
    grid-template-columns: 1fr 1fr;
    column-gap: 35px;
    text-align: left;
    position: relative;
}

.details-box::after {
    content: "";
    position: absolute;
    top: 18px;
    bottom: 18px;
    left: 50%;
    border-left: 1px solid rgba(80, 80, 80, 0.35);
}

.detail-item {
    display: grid;
    grid-template-columns: 38px 1fr;
    gap: 12px;
    margin-bottom: 15px;
    min-height: 38px;
}

.detail-item:last-child {
    margin-bottom: 0;
}

.icon-circle {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #e7f4dc;
    color: #137a28;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.label {
    font-size: 11px;
    color: #5f6c77;
    margin-bottom: 3px;
}

.value {
    font-size: 14px;
    color: #101828;
    font-weight: 800;
    line-height: 1.25;
}

.pan-strip {
    width: 560px;
    margin: 0 auto 14px;
    height: 54px;
    border: 1.6px solid #138434;
    border-radius: 10px;
    background: rgba(239, 249, 240, 0.88);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 18px;
    text-align: left;
}

.pan-icon {
    width: 43px;
    height: 43px;
    border-radius: 50%;
    background: #d9f0d4;
    color: #137a28;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
    border: 1px solid rgba(19, 122, 40, 0.35);
}

.pan-label {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 18px;
    color: #2f3b42;
}

.pan-value {
    font-size: 19px;
    font-weight: 800;
    color: #151b24;
    letter-spacing: 0.4px;
}

.tax-box {
    width: 610px;
    margin: 0 auto 14px;
    border: 1.8px solid #138434;
    border-radius: 11px;
    background: rgba(241, 250, 242, 0.86);
    display: grid;
    grid-template-columns: 135px 1fr;
    gap: 12px;
    padding: 18px 22px;
    text-align: left;
}

.tax-shield {
    width: 105px;
    height: 105px;
    margin: 0 auto;
    border: 3px solid #137a28;
    border-radius: 50%;
    color: #137a28;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 56px;
}

.tax-title {
    color: #117b2f;
    font-size: 17px;
    font-weight: 900;
    letter-spacing: 0.7px;
    margin: 4px 0 9px;
}

.tax-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.tax-list li {
    font-size: 12.5px;
    line-height: 1.55;
    margin-bottom: 5px;
    color: #2a343d;
}

.tax-list li::before {
    content: "❧";
    color: #137a28;
    margin-right: 8px;
}

.tax-list strong {
    color: #0b6e23;
}

.ack {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 16px;
    line-height: 1.55;
    max-width: 610px;
    margin: 8px auto 20px;
    color: #2b333a;
}

.bottom-area {
    width: 610px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 245px;
    align-items: end;
}

.seal {
    width: 92px;
    height: 92px;
    border: 2px solid #a46a22;
    border-radius: 50%;
    color: #8a4f18;
    font-size: 10px;
    font-weight: 900;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    line-height: 1.25;
}

.signature {
    text-align: center;
}

.sign-line {
    height: 34px;
    border-bottom: 2px solid #7d8790;
    margin-bottom: 7px;
    position: relative;
}

.sign-line::before {
    content: "Signature";
    position: absolute;
    left: 68px;
    bottom: 2px;
    color: #0a4ecb;
    font-family: "Brush Script MT", cursive;
    font-size: 26px;
    transform: rotate(-3deg);
}

.sign-title {
    color: #117b2f;
    font-weight: 900;
    font-size: 14px;
}

.sign-sub {
    color: #1f2933;
    font-size: 13px;
    line-height: 1.45;
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
    <div class="certificate-frame"></div>
    <div class="corner tl"></div>
    <div class="corner tr"></div>
    <div class="corner bl"></div>
    <div class="corner br"></div>

    <img class="watermark-tree" src="<?= esc($logoUrl) ?>" alt="">

    <main class="content">
        <img class="logo" src="<?= esc($logoUrl) ?>" alt="Positive Tree Foundation">

        <h1 class="org-title"><?= esc($orgName) ?></h1>
        <div class="tagline"><?= esc($tagline) ?></div>

        <div class="divider-leaf"><span>❦</span></div>

        <div class="certificate-title">Donation Certificate</div>

        <p class="certify">This is to certify that</p>

        <div class="donor-name"><?= esc($donorName) ?></div>
        <div class="ornament-line"></div>

        <p class="statement">
            has generously contributed <strong><?= esc($amount) ?> (<?= esc($amountWords) ?>)</strong><br>
            towards <strong><?= esc($cause) ?></strong><br>
            at Positive Tree Foundation.
        </p>

        <section class="amount-badge">
            <div class="amount-label">DONATION AMOUNT</div>
            <div class="amount-value"><?= esc($amount) ?></div>
            <div class="amount-words">(<?= esc($amountWords) ?>)</div>
        </section>

        <section class="details-box">
            <div>
                <div class="detail-item">
                    <div class="icon-circle">▣</div>
                    <div>
                        <div class="label">Receipt No.</div>
                        <div class="value"><?= esc($receiptNo) ?></div>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="icon-circle">▤</div>
                    <div>
                        <div class="label">Certificate No.</div>
                        <div class="value"><?= esc($certificateNo) ?></div>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="icon-circle">♥</div>
                    <div>
                        <div class="label">Cause</div>
                        <div class="value"><?= esc($cause) ?></div>
                    </div>
                </div>
            </div>

            <div>
                <div class="detail-item">
                    <div class="icon-circle">▦</div>
                    <div>
                        <div class="label">Date</div>
                        <div class="value"><?= esc($date) ?></div>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="icon-circle">◎</div>
                    <div>
                        <div class="label">Donation Mode</div>
                        <div class="value"><?= esc($donationMode) ?></div>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="icon-circle">▰</div>
                    <div>
                        <div class="label">Payment Method</div>
                        <div class="value"><?= esc($paymentMethod) ?></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="pan-strip">
            <div class="pan-icon">▣</div>
            <div class="pan-label">Donor PAN</div>
            <div class="pan-value"><?= esc($donorPan) ?></div>
        </section>

        <section class="tax-box">
            <div class="tax-shield">✓</div>
            <div>
                <div class="tax-title">INCOME TAX DEDUCTION ELIGIBILITY</div>
                <ul class="tax-list">
                    <li>Eligible for deduction under <?= esc($deductionLaw) ?>.</li>
                    <li>Deduction available to the donor subject to valid registration, donor eligibility, mode of payment, and prevailing law.</li>
                    <li>80G Approval / Registration No.: <strong><?= esc($approvalNo) ?></strong></li>
                </ul>
            </div>
        </section>

        <p class="ack">
            This certificate is issued in gratitude for your generous support towards our mission.<br>
            The contribution details have been verified by our Accounts Team.
        </p>

        <section class="bottom-area">
            <div class="seal">
                THANK YOU FOR<br>
                MAKING A<br>
                DIFFERENCE
            </div>

            <div class="signature">
                <div class="sign-line"></div>
                <div class="sign-title">Authorized Signatory</div>
                <div class="sign-sub">
                    Accounts Team<br>
                    Positive Tree Foundation
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>
