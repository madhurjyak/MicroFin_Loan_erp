<?php

namespace App\Helpers;

/**
 * IndianCurrency Helper
 *
 * Formats monetary values in the Indian numbering system:
 *   ₹1,50,000.00  (lakhs and crores grouping)
 *
 * Usage in Blade: {{ \App\Helpers\IndianCurrency::format($amount) }}
 * Or register as a Blade directive in AppServiceProvider.
 */
class IndianCurrency
{
    /**
     * Format a float/int as Indian currency string.
     *
     * @param  float|int|string|null  $amount
     * @param  bool  $showSymbol  Whether to prefix with ₹
     * @return string  e.g. "₹1,50,000.00"
     */
    public static function format(float|int|string|null $amount, bool $showSymbol = true): string
    {
        $amount = (float) ($amount ?? 0);

        // Format with 2 decimal places
        $formatted = number_format(abs($amount), 2, '.', '');

        // Split into integer and decimal parts
        [$intPart, $decPart] = explode('.', $formatted);

        // Apply Indian grouping: last 3 digits, then groups of 2
        $intLen = strlen($intPart);

        if ($intLen <= 3) {
            $grouped = $intPart;
        } else {
            // First group: rightmost 3 digits
            $lastThree = substr($intPart, -3);
            $remaining = substr($intPart, 0, $intLen - 3);
            // Remaining split into groups of 2 from the right
            $grouped = ltrim(preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $remaining), ',')
                . ',' . $lastThree;
        }

        $result = $grouped . '.' . $decPart;

        if ($amount < 0) {
            $result = '-' . $result;
        }

        return $showSymbol ? '₹' . $result : $result;
    }

    /**
     * Convert number to Indian words (Lakhs / Crores).
     * Useful for cheque printing or legal notices.
     */
    public static function inWords(float $amount): string
    {
        $amount = (int) round($amount);

        if ($amount >= 10000000) {
            return number_format($amount / 10000000, 2) . ' Crore';
        }
        if ($amount >= 100000) {
            return number_format($amount / 100000, 2) . ' Lakh';
        }
        if ($amount >= 1000) {
            return number_format($amount / 1000, 2) . ' Thousand';
        }

        return (string) $amount;
    }
}
