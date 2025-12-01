<?php

namespace Devtally\TallyXml\Builders;

use Devtally\TallyXml\Helpers;

/**
 * StockItemBuilder - Fluent builder for creating Tally stock items (inventory)
 * 
 * Provides a convenient way to build stock item master data structures
 * that can be converted to Tally XML.
 */
class StockItemBuilder
{
    /**
     * @var array Stock item data
     */
    protected $data = [];

    /**
     * Create a new stock item builder
     *
     * @param string $name Stock item name
     *@param string $unit Base unit (e.g., "Nos", "Kgs")
     */
    public function __construct(string $name, string $unit)
    {
        $this->data = [
            'TALLYMESSAGE' => [
                'STOCKITEM' => [
                    'ACTION' => 'Alter',
                    'NAME.LIST' => [
                        'NAME' => Helpers::clean($name),
                    ],
                    'BASEUNITS' => Helpers::clean($unit),
                ]
            ]
        ];
    }

    /**
     * Set HSN code
     */
    public function hsn(string $hsnCode): self
    {
        $this->data['TALLYMESSAGE']['STOCKITEM']['HSNDETAILS.LIST'] = [
            'HSNCODE' => Helpers::clean($hsnCode),
            'SRCOFHSNDETAILS' => 'Specify Details Here',
            'APPLICABLEFROM' => Helpers::getApplicableFrom(),
        ];
        return $this;
    }

    /**
     * Set if GST is applicable
     */
    public function gstApplicable(bool $applicable): self
    {
        $value = $applicable ? 
            Helpers::tallySpecialChar() . ' Applicable' : 
            Helpers::tallySpecialChar() . ' Not Applicable';
        
        $this->data['TALLYMESSAGE']['STOCKITEM']['GSTAPPLICABLE'] = $value;
        return $this;
    }

    /**
     * Set GST supply type
     */
    public function gstSupplyType(string $type): self
    {
        $this->data['TALLYMESSAGE']['STOCKITEM']['GSTTYPEOFSUPPLY'] = Helpers::clean($type);
        return $this;
    }

    /**
     * Set opening rate/price
     */
    public function price(float $price): self
    {
        $unit = $this->data['TALLYMESSAGE']['STOCKITEM']['BASEUNITS'] ?? 'Nos';
        $this->data['TALLYMESSAGE']['STOCKITEM']['_OPENINGRATE'] = Helpers::formatRate($price, $unit);
        return $this;
    }

    /**
     * Configure GST details with percentage
     */
    public function gstDetails(string $hsnCode, float $gstPercentage, ?float $openingRate = null): self
    {
        $unit = $this->data['TALLYMESSAGE']['STOCKITEM']['BASEUNITS'] ?? 'Nos';
        
        $gstDetails = [
            'CALCULATIONTYPE' => 'On Value',
            'HSNCODE' => Helpers::clean($hsnCode),
            'TAXABILITY' => 'Taxable',
            'SRCOFGSTDETAILS' => 'Specify Details Here',
            'GSTCALCSLABONMRP' => 'No',
            'APPLICABLEFROM' => Helpers::getApplicableFrom(),
            'ADDITIONALUNITS' => '',
        ];

        if ($openingRate !== null) {
            $gstDetails['OPENINGRATE'] = Helpers::formatRate($openingRate, $unit);
        }

        // Add state-wise GST rate details
        $cgstRate = $gstPercentage / 2;
        $sgstRate = $gstPercentage / 2;
        $igstRate = $gstPercentage;

        $gstDetails['STATEWISEDETAILS.LIST'] = [
            'STATENAME' => Helpers::tallySpecialChar() . ' Any',
            'RATEDETAILS.LIST' => [
                // CGST
                [
                    'GSTRATEDUTYHEAD' => 'CGST',
                    'GSTRATEVALUATIONTYPE' => 'Based on Value',
                    'GSTRATE' => $cgstRate,
                ],
                // SGST/UTGST
                [
                    'GSTRATEDUTYHEAD' => 'SGST/UTGST',
                    'GSTRATEVALUATIONTYPE' => 'Based on Value',
                    'GSTRATE' => $sgstRate,
                ],
                // IGST
                [
                    'GSTRATEDUTYHEAD' => 'IGST',
                    'GSTRATEVALUATIONTYPE' => 'Based on Value',
                    'GSTRATE' => $igstRate,
                ],
                // Cess
                [
                    'GSTRATEDUTYHEAD' => 'Cess',
                    'GSTRATEVALUATIONTYPE' => Helpers::tallySpecialChar() . ' Not Applicable',
                ],
                // State Cess
                [
                    'GSTRATEDUTYHEAD' => 'State Cess',
                    'GSTRATEVALUATIONTYPE' => 'Based on Value',
                ],
            ]
        ];

        $this->data['TALLYMESSAGE']['STOCKITEM']['GSTDETAILS.LIST'] = $gstDetails;

        // Also set HSN details
        $this->hsn($hsnCode);

        return $this;
    }

    /**
     * Build and return the stock item data array
     */
    public function build(): array
    {
        return $this->data;
    }

    /**
     * Create a stock item builder with static method
     */
    public static function create(string $name, string $unit): self
    {
        return new self($name, $unit);
    }
}
