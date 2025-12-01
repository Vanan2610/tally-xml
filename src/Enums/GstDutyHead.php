<?php

namespace Devtally\TallyXml\Enums;

/**
 * GstDutyHead - Enum for GST duty head types
 * 
 * Defines the GST duty head classifications in Tally
 */
class GstDutyHead
{
    public const CGST = 'CGST';
    public const SGST = 'SGST/UTGST';
    public const IGST = 'IGST';
    public const CESS = 'Cess';
    public const STATE_CESS = 'State Cess';

    /**
     * Get all available duty heads
     *
     * @return array
     */
    public static function all(): array
    {
        return [
            self::CGST,
            self::SGST,
            self::IGST,
            self::CESS,
            self::STATE_CESS,
        ];
    }

    /**
     * Validate if duty head is valid
     *
     * @param string $dutyHead
     * @return bool
     */
    public static function isValid(string $dutyHead): bool
    {
        return in_array($dutyHead, self::all(), true);
    }
}
