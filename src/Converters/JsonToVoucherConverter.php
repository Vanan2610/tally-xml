<?php

namespace Devtally\TallyXml\Converters;

use Devtally\TallyXml\TallyXmlConverter;
use Devtally\TallyXml\Builders\UnitBuilder;
use Devtally\TallyXml\Builders\LedgerBuilder;
use Devtally\TallyXml\Builders\StockItemBuilder;
use Devtally\TallyXml\Builders\VoucherBuilder;
use Devtally\TallyXml\Helpers;

/**
 * JsonToVoucherConverter - Convert JSON to Tally Voucher XML
 * 
 * Converts the user's voucher JSON structure to Tally-compatible XML
 * Supports units, ledgers, stock items, and vouchers with full details
 */
class JsonToVoucherConverter
{
    /**
     * @var TallyXmlConverter
     */
    protected $converter;

    /**
     * @var string Company name
     */
    protected $companyName;

    public function __construct()
    {
        $this->converter = new TallyXmlConverter();
    }

    /**
     * Convert JSON array to Tally XML
     *
     * @param array $json JSON data as associative array
     * @param bool $validate Whether to validate JSON structure (default: true)
     * @return string XML string
     * @throws \InvalidArgumentException if validation fails
     */
    public function convert(array $json, bool $validate = true): string
    {
        // Validate JSON structure if enabled
        if ($validate) {
            $validator = new \Devtally\TallyXml\Validation\JsonValidator();
            
            if (!$validator->validateVoucherJson($json)) {
                throw new \InvalidArgumentException(
                    "JSON validation failed:\n" . $validator->getErrorsAsString()
                );
            }
        }

        $this->companyName = $json['company_name'] ?? '';
        
        $allData = [];

        // Process units
        if (isset($json['units']) && is_array($json['units'])) {
            foreach ($json['units'] as $unitData) {
                $unit = $this->buildUnit($unitData);
                $allData[] = $unit;
            }
        }

        // Process ledgers
        if (isset($json['ledgers']) && is_array($json['ledgers'])) {
            foreach ($json['ledgers'] as $ledgerData) {
                $ledger = $this->buildLedger($ledgerData);
                $allData[] = $ledger;
            }
        }

        // Process stock items
        if (isset($json['stock_item']) && is_array($json['stock_item'])) {
            foreach ($json['stock_item'] as $itemData) {
                $stockItem = $this->buildStockItem($itemData);
                $allData[] = $stockItem;
            }
        }

        // Process voucher
        if (isset($json['voucher'])) {
            $voucher = $this->buildVoucher($json['voucher']);
            $allData[] = $voucher;
        }

        // Convert to XML
        return $this->convertToXml($allData);
    }

    /**
     * Build unit from data
     */
    protected function buildUnit(array $data): array
    {
        $builder = UnitBuilder::create(
            $data['name'],
            $data['uqc_name']
        );

        if (isset($data['decimal_point'])) {
            $builder->decimalPlaces($data['decimal_point']);
        }

        return $builder->build();
    }

    /**
     * Build ledger from data
     */
    protected function buildLedger(array $data): array
    {
        $builder = LedgerBuilder::create(
            $data['name'],
            $data['parent'] ?? 'Sundry Debtors'
        );

        if (!empty($data['address'])) {
            $builder->address($data['address']);
        }

        if (!empty($data['state'])) {
            $builder->state($data['state']);
        }

        if (!empty($data['country'])) {
            $builder->country($data['country']);
        }

        if (!empty($data['pincode'])) {
            $builder->pincode($data['pincode']);
        }

        if (!empty($data['gst_registration_type'])) {
            $builder->gstRegistrationType($data['gst_registration_type']);
        }

        if (!empty($data['gst_in'])) {
            $builder->gstin($data['gst_in']);
        }

        if (!empty($data['gst_duty_head'])) {
            $builder->gstDutyHead($data['gst_duty_head']);
        }

        if (!empty($data['gst_percentage'])) {
            $builder->gstPercentage($data['gst_percentage']);
        }

        return $builder->build();
    }

    /**
     * Build stock item from data
     */
    protected function buildStockItem(array $data): array
    {
        $builder = StockItemBuilder::create(
            $data['name'],
            $data['unit'] ?? 'Nos'
        );

        if (isset($data['gst_applicable'])) {
            $builder->gstApplicable($data['gst_applicable']);
        }

        if (!empty($data['gst_supply_type'])) {
            $builder->gstSupplyType($data['gst_supply_type']);
        }

        // Set GST details if we have HSN and percentage
        if (!empty($data['hsn']) && isset($data['gst_percentage'])) {
            $price = $data['price'] ?? null;
            $builder->gstDetails($data['hsn'], $data['gst_percentage'], $price);
        }

        return $builder->build();
    }

    /**
     * Build voucher from data
     */
    protected function buildVoucher(array $data): array
    {
        $voucherType = $data['voucher_type'] ?? 'Sale';
        $voucherNumber = $data['voucher_number'] ?? '';
        $voucherDate = $data['voucher_date'] ?? date('Y-m-d');

        // Create voucher using appropriate static method
        $builder = $this->createVoucherBuilder($voucherType, $voucherNumber, $voucherDate);

        // Party details
        if (!empty($data['ledger_name'])) {
            $builder->partyName($data['ledger_name']);
        }

        if (!empty($data['address_line_1'])) {
            $builder->partyAddress($data['address_line_1']);
        }

        if (!empty($data['address_line_2'])) {
            $builder->partyAddress($data['address_line_2']);
        }

        if (!empty($data['state'])) {
            $builder->partyState($data['state']);
        }

        if (!empty($data['country'])) {
            $builder->partyCountry($data['country']);
        }

        if (!empty($data['pincode'])) {
            $builder->partyPincode($data['pincode']);
        }

        if (!empty($data['gst_registration_type'])) {
            $builder->partyGstRegistrationType($data['gst_registration_type']);
        }

        if (!empty($data['gst_in'])) {
            $builder->partyGstin($data['gst_in']);
        }

        if (!empty($data['place_of_supply'])) {
            $builder->placeOfSupply($data['place_of_supply']);
        }

        if (!empty($data['narration'])) {
            $builder->narration($data['narration']);
        }

        // Add voucher items
        if (isset($data['voucher_items']) && is_array($data['voucher_items'])) {
            foreach ($data['voucher_items'] as $item) {
                $builder->addVoucherItem(
                    $item['ledger_name'] ?? 'Sales Account',
                    $item['stock_item_name'] ?? '',
                    $item['unit'] ?? 'Nos',
                    $item['price'] ?? 0,
                    $item['qty'] ?? 1,
                    $item['discount'] ?? null
                );
            }
        }

        // Add tax details
        if (isset($data['tax_details']) && is_array($data['tax_details'])) {
            foreach ($data['tax_details'] as $tax) {
                $builder->addTax(
                    $tax['name'] ?? 'GST',
                    $tax['amount'] ?? 0
                );
            }
        }

        // Add additional charges
        if (isset($data['additional_charges']) && is_array($data['additional_charges'])) {
            foreach ($data['additional_charges'] as $charge) {
                $builder->addAdditionalCharge(
                    $charge['name'] ?? '',
                    $charge['amount'] ?? 0
                );
            }
        }

        // Add round-off
        if (isset($data['round_off'])) {
            $builder->roundOff(
                $data['round_off']['name'] ?? 'Round Off',
                $data['round_off']['amount'] ?? 0
            );
        }

        // Add party amount (total)
        if (isset($data['total']) && isset($data['ledger_name'])) {
            $builder->partyAmount($data['ledger_name'], $data['total']);
        }

        return $builder->build();
    }

    /**
     * Create voucher builder based on type
     */
    protected function createVoucherBuilder(string $type, string $number, string $date): VoucherBuilder
    {
        $typeLower = strtolower($type);

        switch ($typeLower) {
            case 'sale':
            case 'sales':
                return VoucherBuilder::sales($number, $date);
            
            case 'purchase':
                return VoucherBuilder::purchase($number, $date);
            
            case 'sale return':
                return VoucherBuilder::saleReturn($number, $date);
            
            case 'purchase return':
                return VoucherBuilder::purchaseReturn($number, $date);
            
            case 'payment':
                return VoucherBuilder::payment($number, $date);
            
            case 'receipt':
                return VoucherBuilder::receipt($number, $date);
            
            default:
                return new VoucherBuilder($type, $number, $date);
        }
    }

    /**
     * Convert all data to XML
     */
    protected function convertToXml(array $allData): string
    {
        $data = [
            '_COMPANY_NAME' => $this->companyName,
            'DATA' => $allData,
        ];

        return $this->converter->convert($data, 'Import');
    }
}
