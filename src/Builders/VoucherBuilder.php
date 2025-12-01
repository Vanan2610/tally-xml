<?php

namespace Devtally\TallyXml\Builders;

use Devtally\TallyXml\Enums\VoucherType;
use Devtally\TallyXml\Helpers;

/**
 * VoucherBuilder - Enhanced fluent builder for creating Tally vouchers
 * 
 * Provides comprehensive support for:
 * - All voucher types including Sale Return and Purchase Return
 * - Party details with GST information
 * - Inventory items with batch allocation
 * - Tax entries, additional charges, and round-off
 * - Automatic debit/credit calculation based on voucher type
 */
class VoucherBuilder
{
    /**
     * @var array Voucher data
     */
    protected $data = [];

    /**
     * @var string Voucher type
     */
    protected $voucherType;

    /**
     * @var array Ledger entries
     */
    protected $ledgerEntries = [];

    /**
     * @var array Inventory entries with full details
     */
    protected $inventoryEntries = [];

    /**
     * @var array Address lines
     */
    protected $addressLines = [];

    /**
     * Create a new voucher builder
     *
     * @param string $voucherType
     * @param string $voucherNumber
     * @param string $date Date in Tally format (YYYYMMDD) or standard date string
     */
    public function __construct(string $voucherType, string $voucherNumber, string $date)
    {
        $this->voucherType = $voucherType;
        $date = Helpers::formatDate($date);
        
        $this->data = [
            'TALLYMESSAGE' => [
                'VOUCHER' => [
                    'ACTION' => 'Alter',
                    'TAGNAME' => 'Voucher Number',
                    'TAGVALUE' => $voucherNumber,
                    'VOUCHERTYPENAME' => Helpers::clean($voucherType),
                    'DATE' => $date,
                    'EFFECTIVEDATE' => $date,
                    'REFERENCEDATE' => $date,
                    'VOUCHERNUMBER' => $voucherNumber,
                    'REFERENCE' => $voucherNumber,
                ]
            ]
        ];

        // Set view type based on voucher type
        $this->setVoucherView();
    }

    /**
     * Set appropriate view based on voucher type
     */
    protected function setVoucherView(): void
    {
        $type = strtolower($this->voucherType);
        
        if (in_array($type, ['sale', 'purchase', 'sale return', 'purchase return'])) {
            $this->data['TALLYMESSAGE']['VOUCHER']['PERSISTEDVIEW'] = 'Invoice Voucher View';
            $this->data['TALLYMESSAGE']['VOUCHER']['VCHENTRYMODE'] = 'Item Invoice';
        } else {
            $this->data['TALLYMESSAGE']['VOUCHER']['PERSISTEDVIEW'] = 'Accounting Voucher View';
            if (!in_array($type, ['receipt', 'payment'])) {
                $this->data['TALLYMESSAGE']['VOUCHER']['VCHENTRYMODE'] = 'As Voucher';
            }
        }
    }

    /**
     * Set voucher narration
     */
    public function narration(string $narration): self
    {
        $this->data['TALLYMESSAGE']['VOUCHER']['NARRATION'] = Helpers::clean($narration);
        return $this;
    }

    /**
     * Set party/customer name with full details
     */
    public function partyName(string $name): self
    {
        $name = Helpers::clean($name);
        $this->data['TALLYMESSAGE']['VOUCHER']['PARTYNAME'] = $name;
        $this->data['TALLYMESSAGE']['VOUCHER']['PARTYLEDGERNAME'] = $name;
        $this->data['TALLYMESSAGE']['VOUCHER']['PARTYMAILINGNAME'] = $name;
        $this->data['TALLYMESSAGE']['VOUCHER']['CONSIGNEEMAILINGNAME'] = $name;
        $this->data['TALLYMESSAGE']['VOUCHER']['BASICBASEPARTYNAME'] = $name;
        $this->data['TALLYMESSAGE']['VOUCHER']['BASICBUYERNAME'] = $name;
        return $this;
    }

    /**
     * Alias for partyName
     */
    public function partyLedger(string $name): self
    {
        return $this->partyName($name);
    }

    /**
     * Add party address line
     */
    public function partyAddress(string $address): self
    {
        $this->addressLines[] = Helpers::clean($address);
        return $this;
    }

    /**
     * Set party state
     */
    public function partyState(string $state): self
    {
        $state = Helpers::clean($state);
        $this->data['TALLYMESSAGE']['VOUCHER']['STATENAME'] = $state;
        $this->data['TALLYMESSAGE']['VOUCHER']['CONSIGNEESTATENAME'] = $state;
        return $this;
    }

    /**
     * Set party country
     */
    public function partyCountry(string $country): self
    {
        $country = Helpers::clean($country);
        $this->data['TALLYMESSAGE']['VOUCHER']['COUNTRYNAME'] = $country;
        $this->data['TALLYMESSAGE']['VOUCHER']['COUNTRYOFRESIDENCE'] = $country;
        return $this;
    }

    /**
     * Set party pincode
     */
    public function partyPincode(string $pincode): self
    {
        $this->data['TALLYMESSAGE']['VOUCHER']['PINCODE'] = Helpers::clean($pincode);
        return $this;
    }

    /**
     * Set party GST registration type
     */
    public function partyGstRegistrationType(string $type): self
    {
        $this->data['TALLYMESSAGE']['VOUCHER']['GSTREGISTRATIONTYPE'] = Helpers::clean($type);
        return $this;
    }

    /**
     * Set party GSTIN
     */
    public function partyGstin(string $gstin): self
    {
        $this->data['TALLYMESSAGE']['VOUCHER']['PARTYGSTIN'] = Helpers::clean($gstin);
        return $this;
    }

    /**
     * Set place of supply
     */
    public function placeOfSupply(string $place): self
    {
        $this->data['TALLYMESSAGE']['VOUCHER']['PLACEOFSUPPLY'] = Helpers::clean($place);
        return $this;
    }

    /**
     * Set reference number
     */
    public function reference(string $reference): self
    {
        $this->data['TALLYMESSAGE']['VOUCHER']['REFERENCE'] = Helpers::clean($reference);
        return $this;
    }

    /**
     * Set reference date
     */
    public function referenceDate(string $date): self
    {
        $this->data['TALLYMESSAGE']['VOUCHER']['REFERENCEDATE'] = Helpers::formatDate($date);
        return $this;
    }

    /**
     * Add voucher item with full inventory allocation
     *
     * @param string $ledgerName Sales/Purchase account ledger
     * @param string $stockItemName Stock item name
     * @param string $unit Unit of measurement
     * @param float $rate Rate per unit
     * @param float $qty Quantity
     * @param float|null $discount Discount amount
     */
    public function addVoucherItem(
        string $ledgerName,
        string $stockItemName,
        string $unit,
        float $rate,
        float $qty,
        ?float $discount = null
    ): self {
        $amount = round($rate * $qty, 2);
        $adjustedAmount = Helpers::adjustAmount($amount, $this->voucherType, 'item');
        $isDeemedPositive = Helpers::isDeemedPositive($this->voucherType, 'item');

        $item = [
            'STOCKITEMNAME' => Helpers::clean($stockItemName),
            'ISDEEMEDPOSITIVE' => $isDeemedPositive ? 'Yes' : 'No',
            'ISLASTDEEMEDPOSITIVE' => $isDeemedPositive ? 'Yes' : 'No',
            'ISAUTONEGATE' => 'No',
            'ISCUSTOMSCLEARANCE' => 'No',
            'ISTRACKCOMPONENT' => 'No',
            'ISTRACKPRODUCTION' => 'No',
            'ISPRIMARYITEM' => 'No',
            'ISSCRAP' => 'No',
            'RATE' => Helpers::formatRate($rate, $unit),
            'AMOUNT' => Helpers::formatAmount($adjustedAmount),
            'ACTUALQTY' => Helpers::formatQuantity($qty, $unit),
            'BILLEDQTY' => Helpers::formatQuantity($qty, $unit),
        ];

        if ($discount !== null && $discount != 0) {
            $item['DISCOUNT'] = Helpers::formatAmount($discount);
        }

        // Add batch allocation
        $item['BATCHALLOCATIONS.LIST'] = [
            'BATCHNAME' => 'Primary Batch',
            'DYNAMICCSTISCLEARED' => 'No',
            'AMOUNT' => Helpers::formatAmount($adjustedAmount),
            'ACTUALQTY' => Helpers::formatQuantity($qty, $unit),
            'BILLEDQTY' => Helpers::formatQuantity($qty, $unit),
        ];

        // Add accounting allocation
        $item['ACCOUNTINGALLOCATIONS.LIST'] = [
            'LEDGERNAME' => Helpers::clean($ledgerName),
            'ISDEEMEDPOSITIVE' => $isDeemedPositive ? 'Yes' : 'No',
            'ISLASTDEEMEDPOSITIVE' => $isDeemedPositive ? 'Yes' : 'No',
            'LEDGERFROMITEM' => 'No',
            'REMOVEZEROENTRIES' => 'No',
            'ISPARTYLEDGER' => 'No',
            'ISCAPVATTAXALTERED' => 'No',
            'ISCAPVATNOTCLAIMED' => 'No',
            'AMOUNT' => Helpers::formatAmount($adjustedAmount),
        ];

        $this->inventoryEntries[] = $item;
        return $this;
    }

    /**
     * Add tax ledger entry (CGST/SGST/IGST)
     */
    public function addTax(string $ledgerName, float $amount): self
    {
        $adjustedAmount = Helpers::adjustAmount($amount, $this->voucherType, 'tax');
        $isDeemedPositive = Helpers::isDeemedPositive($this->voucherType, 'tax');

        $this->ledgerEntries[] = [
            'LEDGERNAME' => Helpers::clean($ledgerName),
            'ISDEEMEDPOSITIVE' => $isDeemedPositive ? 'Yes' : 'No',
            'ISLASTDEEMEDPOSITIVE' => $isDeemedPositive ? 'Yes' : 'No',
            'LEDGERFROMITEM' => 'No',
            'REMOVEZEROENTRIES' => 'No',
            'ISPARTYLEDGER' => 'No',
            'ISCAPVATTAXALTERED' => 'No',
            'ISCAPVATNOTCLAIMED' => 'No',
            'AMOUNT' => Helpers::formatAmount($adjustedAmount),
            'VATEXPAMOUNT' => Helpers::formatAmount($adjustedAmount),
        ];

        return $this;
    }

    /**
     * Add additional charge ledger (transport, packaging, etc.)
     */
    public function addAdditionalCharge(string $ledgerName, float $amount): self
    {
        $adjustedAmount = Helpers::adjustAmount($amount, $this->voucherType, 'ledger');
        $isDeemedPositive = Helpers::isDeemedPositive($this->voucherType, 'ledger');

        $this->ledgerEntries[] = [
            'LEDGERNAME' => Helpers::clean($ledgerName),
            'ISDEEMEDPOSITIVE' => $isDeemedPositive ? 'Yes' : 'No',
            'ISLASTDEEMEDPOSITIVE' => $isDeemedPositive ? 'Yes' : 'No',
            'REMOVEZEROENTRIES' => 'No',
            'ISPARTYLEDGER' => 'No',
            'AMOUNT' => Helpers::formatAmount($adjustedAmount),
        ];

        return $this;
    }

    /**
     * Add round-off entry
     */
    public function roundOff(string $ledgerName, float $amount): self
    {
        $this->ledgerEntries[] = [
            'LEDGERNAME' => Helpers::clean($ledgerName),
            'ISDEEMEDPOSITIVE' => ($amount < 0) ? 'Yes' : 'No',
            'REMOVEZEROENTRIES' => 'No',
            'ISPARTYLEDGER' => 'No',
            'AMOUNT' => Helpers::formatAmount($amount),
        ];

        return $this;
    }

    /**
     * Add party ledger entry (main party amount)
     */
    public function partyAmount(string $ledgerName, float $totalAmount): self
    {
        $adjustedAmount = Helpers::adjustAmount($totalAmount, $this->voucherType, 'party');
        $isDeemedPositive = Helpers::isDeemedPositive($this->voucherType, 'party');

        // This entry is added at the beginning
        array_unshift($this->ledgerEntries, [
            'LEDGERNAME' => Helpers::clean($ledgerName),
            'ISDEEMEDPOSITIVE' => $isDeemedPositive ? 'Yes' : 'No',
            'ISLASTDEEMEDPOSITIVE' => $isDeemedPositive ? 'Yes' : 'No',
            'LEDGERFROMITEM' => 'No',
            'REMOVEZEROENTRIES' => 'No',
            'ISPARTYLEDGER' => 'Yes',
            'ISCAPVATTAXALTERED' => 'No',
            'ISCAPVATNOTCLAIMED' => 'No',
            'AMOUNT' => Helpers::formatAmount($adjustedAmount),
            'VATEXPAMOUNT' => Helpers::formatAmount($adjustedAmount),
        ]);

        return $this;
    }

    /**
     * Add a generic ledger entry (for advanced use)
     */
    public function addLedgerEntry(string $ledgerName, float $amount, bool $isDr = true): self
    {
        $this->ledgerEntries[] = [
            'LEDGERNAME' => Helpers::clean($ledgerName),
            'ISDEEMEDPOSITIVE' => $isDr ? 'Yes' : 'No',
            'AMOUNT' => Helpers::formatAmount($amount),
        ];
        return $this;
    }

    /**
     * Build and return the voucher data array
     */
    public function build(): array
    {
        // Add address lines if any
        if (!empty($this->addressLines)) {
            $addresses = [];
            foreach ($this->addressLines as $address) {
                $addresses[] = ['ADDRESS' => $address];
            }
            $this->data['TALLYMESSAGE']['VOUCHER']['ADDRESS.LIST'] = $addresses;
        }

        // Add inventory entries
        if (!empty($this->inventoryEntries)) {
            $this->data['TALLYMESSAGE']['VOUCHER']['ALLINVENTORYENTRIES.LIST'] = $this->inventoryEntries;
        }

        // Add ledger entries
        if (!empty($this->ledgerEntries)) {
            $this->data['TALLYMESSAGE']['VOUCHER']['LEDGERENTRIES.LIST'] = $this->ledgerEntries;
        }

        return $this->data;
    }

    /**
     * Create a sales voucher builder
     */
    public static function sales(string $voucherNumber, string $date): self
    {
        return new self(VoucherType::SALE, $voucherNumber, $date);
    }

    /**
     * Create a purchase voucher builder
     */
    public static function purchase(string $voucherNumber, string $date): self
    {
        return new self(VoucherType::PURCHASE, $voucherNumber, $date);
    }

    /**
     * Create a sale return voucher builder
     */
    public static function saleReturn(string $voucherNumber, string $date): self
    {
        return new self(VoucherType::SALE_RETURN, $voucherNumber, $date);
    }

    /**
     * Create a purchase return voucher builder
     */
    public static function purchaseReturn(string $voucherNumber, string $date): self
    {
        return new self(VoucherType::PURCHASE_RETURN, $voucherNumber, $date);
    }

    /**
     * Create a payment voucher builder
     */
    public static function payment(string $voucherNumber, string $date): self
    {
        return new self(VoucherType::PAYMENT, $voucherNumber, $date);
    }

    /**
     * Create a receipt voucher builder
     */
    public static function receipt(string $voucherNumber, string $date): self
    {
        return new self(VoucherType::RECEIPT, $voucherNumber, $date);
    }

    /**
     * Create a journal voucher builder
     */
    public static function journal(string $voucherNumber, string $date): self
    {
        return new self(VoucherType::JOURNAL, $voucherNumber, $date);
    }
}
