<?php

namespace Devtally\TallyXml\Builders;

use Devtally\TallyXml\Helpers;
use Devtally\TallyXml\Enums\GstRegistrationType;

/**
 * LedgerBuilder - Fluent builder for creating Tally ledgers
 * 
 * Provides a convenient way to build ledger master data structures
 * that can be converted to Tally XML.
 */
class LedgerBuilder
{
    /**
     * @var array Ledger data
     */
    protected $data = [];

    /**
     * @var array Address lines
     */
    protected $addressLines = [];

    /**
     * Create a new ledger builder
     *
     * @param string $name Ledger name
     * @param string $parent Parent group (e.g., "Sundry Debtors", "Sundry Creditors", "Sales Account")
     */
    public function __construct(string $name, string $parent)
    {
        $this->data = [
            'TALLYMESSAGE' => [
                'LEDGER' => [
                    'ACTION' => 'Alter',
                    'NAME.LIST' => [
                        'NAME' => Helpers::clean($name),
                    ],
                    'PARENT' => Helpers::clean($parent),
                ]
            ]
        ];

        // Auto-enable bill-wise details for Sundry Debtors and Creditors
        if (in_array($parent, ['Sundry Debtors', 'Sundry Creditors'])) {
            $this->data['TALLYMESSAGE']['LEDGER']['BILLWISEDETAILS'] = 'Yes';
        }
    }

    /**
     * Add address line
     */
    public function address(string $address): self
    {
        $this->addressLines[] = Helpers::clean($address);
        return $this;
    }

    /**
     * Set state
     */
    public function state(string $state): self
    {
        $state = Helpers::clean($state);
        $this->data['TALLYMESSAGE']['LEDGER']['PRIORSTATENAME'] = $state;
        $this->data['TALLYMESSAGE']['LEDGER']['LEDSTATENAME'] = $state;
        return $this;
    }

    /**
     * Set country
     */
    public function country(string $country): self
    {
        $country = Helpers::clean($country);
        $this->data['TALLYMESSAGE']['LEDGER']['COUNTRYNAME'] = $country;
        $this->data['TALLYMESSAGE']['LEDGER']['COUNTRYOFRESIDENCE'] = $country;
        return $this;
    }

    /**
     * Set pincode
     */
    public function pincode(string $pincode): self
    {
        $this->data['TALLYMESSAGE']['LEDGER']['PINCODE'] = Helpers::clean($pincode);
        return $this;
    }

    /**
     * Set GST registration type
     */
    public function gstRegistrationType(string $type): self
    {
        $this->data['TALLYMESSAGE']['LEDGER']['GSTREGISTRATIONTYPE'] = Helpers::clean($type);
        return $this;
    }

    /**
     * Set GSTIN
     */
    public function gstin(string $gstin): self
    {
        $this->data['TALLYMESSAGE']['LEDGER']['PARTYGSTIN'] = Helpers::clean($gstin);
        return $this;
    }

    /**
     * Set GST duty head and tax type for tax ledgers
     */
    public function gstDutyHead(string $dutyHead): self
    {
        $this->data['TALLYMESSAGE']['LEDGER']['TAXTYPE'] = 'GST';
        $this->data['TALLYMESSAGE']['LEDGER']['GSTDUTYHEAD'] = Helpers::clean($dutyHead);
        return $this;
    }

    /**
     * Set tax rate/percentage
     */
    public function taxRate(float $rate): self
    {
        $this->data['TALLYMESSAGE']['LEDGER']['RATEOFTAXCALCULATION'] = $rate;
        return $this;
    }

    /**
     * Set GST percentage (alias for taxRate)
     */
    public function gstPercentage(float $percentage): self
    {
        return $this->taxRate($percentage);
    }

    /**
     * Add GST registration details
     */
    public function gstRegistrationDetails(string $state, string $gstType, string $gstin): self
    {
        $this->data['TALLYMESSAGE']['LEDGER']['LEDGSTREGDETAILS.LIST'] = [
            'APPLICABLEFROM' => Helpers::getApplicableFrom(),
            'GSTREGISTRATIONTYPE' => Helpers::clean($gstType),
            'PLACEOFSUPPLY' => Helpers::clean($state),
            'GSTIN' => Helpers::clean($gstin),
        ];
        return $this;
    }

    /**
     * Add mailing details
     */
    public function mailingDetails(string $name, string $state, string $country, string $pincode): self
    {
        $this->data['TALLYMESSAGE']['LEDGER']['LEDMAILINGDETAILS.LIST'] = [
            'APPLICABLEFROM' => Helpers::getApplicableFrom(),
            'PINCODE' => Helpers::clean($pincode),
            'MAILINGNAME' => Helpers::clean($name),
            'STATE' => Helpers::clean($state),
            'COUNTRY' => Helpers::clean($country),
        ];
        return $this;
    }

    /**
     * Build and return the ledger data array
     */
    public function build(): array
    {
        // Add address lines if any
        if (!empty($this->addressLines)) {
            $this->data['TALLYMESSAGE']['LEDGER']['ADDRESS.LIST'] = [];
            foreach ($this->addressLines as $address) {
                $this->data['TALLYMESSAGE']['LEDGER']['ADDRESS.LIST'][] = [
                    'ADDRESS' => $address
                ];
            }
        }

        return $this->data;
    }

    /**
     * Create a ledger builder with static method
     */
    public static function create(string $name, string $parent): self
    {
        return new self($name, $parent);
    }

    /**
     * Create a customer ledger (Sundry Debtors)
     */
    public static function customer(string $name): self
    {
        return new self($name, 'Sundry Debtors');
    }

    /**
     * Create a supplier ledger (Sundry Creditors)
     */
    public static function supplier(string $name): self
    {
        return new self($name, 'Sundry Creditors');
    }

    /**
     * Create a sales account ledger
     */
    public static function salesAccount(string $name): self
    {
        return new self($name, 'Sales Account');
    }

    /**
     * Create a purchase account ledger
     */
    public static function purchaseAccount(string $name): self
    {
        return new self($name, 'Purchase Account');
    }
}
