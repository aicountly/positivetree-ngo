<?php

declare(strict_types=1);

namespace App;

class DonationCauses
{
    public const ALL = [
        'Ashram for Spiritual Growth',
        'Old Age Home for Seniors',
        'Providing Education to Needy',
        'Food Programs to Support Those in Need',
    ];

    public static function isValid(string $cause): bool
    {
        return in_array($cause, self::ALL, true);
    }
}
