<?php

declare(strict_types=1);

return [
    'organization' => [
        'organization_name' => 'Positive Tree Foundation',
        'address_lines' => [
            'Where comfort, care, and community come together',
        ],
        'phone' => '+91 6384184900',
        'email' => 'info@positivetree.ngo',
        'website' => 'https://positivetree.ngo',
        'logo_filename' => null,
    ],
    'receipt' => [
        'title' => 'Donation Receipt',
        'footer_text' => 'Thank you for supporting Positive Tree Foundation. This receipt acknowledges your contribution.',
        'signature_name' => 'Authorized Signatory',
        'signature_title' => 'Positive Tree Foundation',
        'accent_color' => '#15803d',
        'show_fields' => [
            'email' => true,
            'phone' => true,
            'cause' => true,
            'channel' => true,
            'payment_method' => true,
            'transaction_ref' => true,
            'notes' => false,
        ],
        'print' => [
            'paper' => 'A4',
            'orientation' => 'portrait',
            'margin_top_mm' => 15,
            'margin_right_mm' => 15,
            'margin_bottom_mm' => 15,
            'margin_left_mm' => 15,
        ],
    ],
    'certificate' => [
        'title' => 'Donation Certificate',
        'opening_text' => 'This is to certify that',
        'body_text' => '{{donor_name}} has generously contributed {{amount_inr}} ({{amount_words}}) towards {{cause}} at Positive Tree Foundation.',
        'closing_text' => 'We acknowledge this contribution with gratitude and confirm that the details have been verified by our Accounts Team.',
        'signatory_name' => 'Authorized Signatory',
        'signatory_title' => 'Accounts Team',
        'signatory_label' => 'Authorized Signatory',
        'accent_color' => '#15803d',
        'show_fields' => [
            'amount_words' => true,
            'cause' => true,
            'receipt_number' => true,
            'certificate_number' => true,
            'donated_at' => true,
        ],
        'print' => [
            'paper' => 'A4',
            'orientation' => 'portrait',
            'margin_top_mm' => 20,
            'margin_right_mm' => 20,
            'margin_bottom_mm' => 20,
            'margin_left_mm' => 20,
        ],
    ],
];
