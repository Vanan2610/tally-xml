<?php

namespace Devtally\TallyXml;

/**
 * Helpers - Utility functions for Tally XML generation
 * 
 * Provides string cleaning, formatting, and Tally-specific utilities
 */
class Helpers
{
    /**
     * Clean string for Tally XML
     * Removes control characters and handles special characters
     *
     * @param string|null $string
     * @return string|null
     */
    public static function clean(?string $string): ?string
    {
        if ($string === null || $string === '') {
            return $string;
        }

        // Remove control characters (0x00-0x1F and 0x7F)
        $string = preg_replace('/[\x00-\x1F\x7F]/u', '', $string);
        
        // Handle ampersands properly for XML
        $string = str_replace('&', '&amp;', $string);
        
        // Remove quotes that might break XML
        $string = str_replace(['\'', '"'], '', $string);
        
        return $string;
    }

    /**
     * Format date for Tally (YYYYMMDD format)
     *
     * @param string $date Date string in any standard format
     * @return string Formatted date YYYYMMDD
     */
    public static function formatDate(string $date): string
    {
        return date('Ymd', strtotime($date));
    }

    /**
     * Create Tally special character prefix
     * Used for special values like "&#4; Applicable"
     *
     * @return string
     */
    public static function tallySpecialChar(): string
    {
        return '&#4;';
    }

    /**
     * Format amount for Tally
     * Ensures proper decimal formatting
     *
     * @param float|int $amount
     * @param int $decimals
     * @return string
     */
    public static function formatAmount($amount, int $decimals = 2): string
    {
        return number_format((float)$amount, $decimals, '.', '');
    }

    /**
     * Format quantity with unit for Tally
     *
     * @param float|int $quantity
     * @param string $unit
     * @return string
     */
    public static function formatQuantity($quantity, string $unit): string
    {
        return " {$quantity} {$unit}";
    }

    /**
     * Format rate with unit for Tally
     *
     * @param float|int $rate
     * @param string $unit
     * @return string
     */
    public static function formatRate($rate, string $unit): string
    {
        return "{$rate}/{$unit}";
    }

    /**
     * Get initials from a string (useful for abbreviations)
     *
     * @param string $str
     * @return string
     */
    public static function getInitials(string $str): string
    {
        $initials = '';
        $words = explode(' ', $str);
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        
        return strtoupper($initials);
    }

    /**
     * Create applicable date for Tally (typically start of financial year)
     * Defaults to April 1st of previous year
     *
     * @param int|null $yearsBack Number of years to go back
     * @return string
     */
    public static function getApplicableFrom(int $yearsBack = 1): string
    {
        return date('Y', strtotime("-{$yearsBack} year")) . '0401';
    }

    /**
     * Determine if amount should be deemed positive based on voucher type
     *
     * @param string $voucherType
     * @param string $entryType 'party'|'item'|'ledger'|'tax'
     * @return bool
     */
    public static function isDeemedPositive(string $voucherType, string $entryType = 'party'): bool
    {
        $voucherTypeLower = strtolower($voucherType);
        
        if ($entryType === 'party') {
            // Party ledger entry
            return !in_array($voucherTypeLower, ['purchase', 'sale return', 'receipt']);
        }
        
        if ($entryType === 'item') {
            // Inventory entry
            return in_array($voucherTypeLower, ['purchase', 'sale return']);
        }
        
        if ($entryType === 'ledger' || $entryType === 'tax') {
            // Additional ledger/tax entries
            return in_array($voucherTypeLower, ['purchase', 'sale return', 'receipt']);
        }
        
        return true;
    }

    /**
     * Adjust amount sign based on voucher type
     *
     * @param float|int $amount
     * @param string $voucherType
     * @param string $entryType
     * @return float
     */
    public static function adjustAmount($amount, string $voucherType, string $entryType = 'party'): float
    {
        $amount = (float)$amount;
        $voucherTypeLower = strtolower($voucherType);
        
        if ($entryType === 'party') {
            if (in_array($voucherTypeLower, ['sale', 'purchase return', 'payment'])) {
                return -$amount;
            }
        } elseif ($entryType === 'item') {
            if (in_array($voucherTypeLower, ['purchase', 'sale return'])) {
                return -$amount;
            }
        } elseif ($entryType === 'ledger' || $entryType === 'tax') {
            if (in_array($voucherTypeLower, ['purchase', 'sale return', 'receipt'])) {
                return -$amount;
            }
        }
        
        return $amount;
    }
}
