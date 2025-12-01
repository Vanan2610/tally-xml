<?php

namespace Devtally\TallyXml\Enums;

/**
 * VoucherType - Enum for Tally voucher types
 * 
 * Defines the standard voucher types used in Tally
 */
class VoucherType
{
    public const SALES = 'Sales';
    public const SALE = 'Sale';
    public const PURCHASE = 'Purchase';
    public const SALE_RETURN = 'Sale Return';
    public const PURCHASE_RETURN = 'Purchase Return';
    public const PAYMENT = 'Payment';
    public const RECEIPT = 'Receipt';
    public const CONTRA = 'Contra';
    public const JOURNAL = 'Journal';
    public const CREDIT_NOTE = 'Credit Note';
    public const DEBIT_NOTE = 'Debit Note';
    public const DELIVERY_NOTE = 'Delivery Note';
    public const RECEIPT_NOTE = 'Receipt Note';
    public const STOCK_JOURNAL = 'Stock Journal';
    public const PHYSICAL_STOCK = 'Physical Stock';
    public const MEMORANDUM = 'Memorandum';
    public const REVERSING_JOURNAL = 'Reversing Journal';

    /**
     * Get all available voucher types
     *
     * @return array
     */
    public static function all(): array
    {
        return [
            self::SALES,
            self::SALE,
            self::PURCHASE,
            self::SALE_RETURN,
            self::PURCHASE_RETURN,
            self::PAYMENT,
            self::RECEIPT,
            self::CONTRA,
            self::JOURNAL,
            self::CREDIT_NOTE,
            self::DEBIT_NOTE,
            self::DELIVERY_NOTE,
            self::RECEIPT_NOTE,
            self::STOCK_JOURNAL,
            self::PHYSICAL_STOCK,
            self::MEMORANDUM,
            self::REVERSING_JOURNAL,
        ];
    }

    /**
     * Validate if voucher type is valid
     *
     * @param string $type
     * @return bool
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::all(), true);
    }
}
