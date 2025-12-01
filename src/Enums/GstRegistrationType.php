<?php

namespace Devtally\TallyXml\Enums;

/**
 * GstRegistrationType - Enum for GST registration types
 * 
 * Defines the registration types used in Tally for GST
 */
class GstRegistrationType
{
    public const REGULAR = 'Regular';
    public const UNREGISTERED_CONSUMER = 'Unregistered/Consumer';
    public const COMPOSITION = 'Composition';
    public const CONSUMER = 'Consumer';
    public const UNREGISTERED = 'Unregistered';

    /**
     * Get all available registration types
     *
     * @return array
     */
    public static function all(): array
    {
        return [
            self::REGULAR,
            self::UNREGISTERED_CONSUMER,
            self::COMPOSITION,
            self::CONSUMER,
            self::UNREGISTERED,
        ];
    }

    /**
     * Validate if registration type is valid
     *
     * @param string $type
     * @return bool
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::all(), true);
    }
}
