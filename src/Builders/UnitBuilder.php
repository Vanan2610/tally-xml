<?php

namespace Devtally\TallyXml\Builders;

use Devtally\TallyXml\Helpers;
use Devtally\TallyXml\Enums\GstRegistrationType;

/**
 * UnitBuilder - Fluent builder for creating Tally units of measurement
 * 
 * Provides a convenient way to build unit data structures
 * that can be converted to Tally XML.
 */
class UnitBuilder
{
    /**
     * @var array Unit data
     */
    protected $data = [];

    /**
     * Create a new unit builder
     *
     * @param string $name Unit name (e.g., "Nos", "Kgs", "Meters")
     * @param string $uqcName Unique Quantity Code (e.g., "NOS", "KGS", "MTR")
     */
    public function __construct(string $name, string $uqcName)
    {
        $this->data = [
            'TALLYMESSAGE' => [
                'UNIT' => [
                    'ACTION' => 'Alter',
                    'NAME' => Helpers::clean($name),
                    'GSTREPUOM' => Helpers::clean($uqcName),
                    'ISSIMPLEUNIT' => 'Yes',
                    'FORPAYROLL' => 'No',
                    'REPORTINGUQCDETAILS.LIST' => [
                        'APPLICABLEFROM' => Helpers::getApplicableFrom(),
                        'REPORTINGUQCNAME' => Helpers::clean($uqcName),
                    ]
                ]
            ]
        ];
    }

    /**
     * Set decimal places for quantity
     */
    public function decimalPlaces(int $decimals): self
    {
        $this->data['TALLYMESSAGE']['UNIT']['DECIMALPLACES'] = $decimals;
        return $this;
    }

    /**
     * Build and return the unit data array
     */
    public function build(): array
    {
        return $this->data;
    }

    /**
     * Create a unit builder with static method
     */
    public static function create(string $name, string $uqcName): self
    {
        return new self($name, $uqcName);
    }
}
