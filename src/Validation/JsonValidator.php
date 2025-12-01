<?php

namespace Devtally\TallyXml\Validation;

/**
 * JsonValidator - Validates JSON structure before conversion
 * 
 * Ensures the JSON data matches the expected structure for
 * Master or Voucher data before converting to Tally XML
 */
class JsonValidator
{
    /**
     * @var array Validation errors
     */
    protected $errors = [];

    /**
     * Validate Master JSON structure
     *
     * @param array $json
     * @return bool
     */
    public function validateMasterJson(array $json): bool
    {
        $this->errors = [];

        // Required fields
        if (!isset($json['company_name']) || empty($json['company_name'])) {
            $this->errors[] = "Missing required field: 'company_name'";
        }

        if (!isset($json['ledger']) || !is_array($json['ledger'])) {
            $this->errors[] = "Missing or invalid field: 'ledger' (must be an array)";
        } else {
            $this->validateLedgers($json['ledger']);
        }

        return empty($this->errors);
    }

    /**
     * Validate Voucher JSON structure
     *
     * @param array $json
     * @return bool
     */
    public function validateVoucherJson(array $json): bool
    {
        $this->errors = [];

        // Required fields
        if (!isset($json['company_name']) || empty($json['company_name'])) {
            $this->errors[] = "Missing required field: 'company_name'";
        }

        // Validate optional arrays
        if (isset($json['units'])) {
            if (!is_array($json['units'])) {
                $this->errors[] = "Field 'units' must be an array";
            } else {
                $this->validateUnits($json['units']);
            }
        }

        if (isset($json['ledgers'])) {
            if (!is_array($json['ledgers'])) {
                $this->errors[] = "Field 'ledgers' must be an array";
            } else {
                $this->validateLedgers($json['ledgers']);
            }
        }

        if (isset($json['stock_item'])) {
            if (!is_array($json['stock_item'])) {
                $this->errors[] = "Field 'stock_item' must be an array";
            } else {
                $this->validateStockItems($json['stock_item']);
            }
        }

        // Validate voucher (required)
        if (!isset($json['voucher'])) {
            $this->errors[] = "Missing required field: 'voucher'";
        } else {
            if (!is_array($json['voucher'])) {
                $this->errors[] = "Field 'voucher' must be an array";
            } else {
                $this->validateVoucher($json['voucher']);
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate units array
     */
    protected function validateUnits(array $units): void
    {
        foreach ($units as $index => $unit) {
            if (!isset($unit['name'])) {
                $this->errors[] = "Unit at index {$index}: Missing 'name'";
            }
            if (!isset($unit['uqc_name'])) {
                $this->errors[] = "Unit at index {$index}: Missing 'uqc_name'";
            }
        }
    }

    /**
     * Validate ledgers array
     */
    protected function validateLedgers(array $ledgers): void
    {
        foreach ($ledgers as $index => $ledger) {
            if (!isset($ledger['name'])) {
                $this->errors[] = "Ledger at index {$index}: Missing 'name'";
            }
            
            // Validate GST details if present
            if (isset($ledger['gst_in']) && !empty($ledger['gst_in'])) {
                if (!$this->isValidGstin($ledger['gst_in'])) {
                    $this->errors[] = "Ledger '{$ledger['name']}': Invalid GSTIN format";
                }
            }
        }
    }

    /**
     * Validate stock items array
     */
    protected function validateStockItems(array $items): void
    {
        foreach ($items as $index => $item) {
            if (!isset($item['name'])) {
                $this->errors[] = "Stock item at index {$index}: Missing 'name'";
            }
            if (!isset($item['unit'])) {
                $this->errors[] = "Stock item at index {$index}: Missing 'unit'";
            }
            
            // Validate GST percentage if applicable
            if (isset($item['gst_applicable']) && $item['gst_applicable'] === true) {
                if (!isset($item['gst_percentage'])) {
                    $this->errors[] = "Stock item '{$item['name']}': GST applicable but 'gst_percentage' missing";
                }
                if (!isset($item['hsn'])) {
                    $this->errors[] = "Stock item '{$item['name']}': GST applicable but 'hsn' missing";
                }
            }
        }
    }

    /**
     * Validate voucher structure
     */
    protected function validateVoucher(array $voucher): void
    {
        // Required fields
        $required = ['voucher_type', 'voucher_number', 'voucher_date'];
        foreach ($required as $field) {
            if (!isset($voucher[$field]) || empty($voucher[$field])) {
                $this->errors[] = "Voucher: Missing required field '{$field}'";
            }
        }

        // Validate voucher type
        if (isset($voucher['voucher_type'])) {
            $validTypes = ['Sale', 'Purchase', 'Sale Return', 'Purchase Return', 'Payment', 'Receipt', 'Journal'];
            if (!in_array($voucher['voucher_type'], $validTypes)) {
                $this->errors[] = "Voucher: Invalid voucher_type '{$voucher['voucher_type']}'. Must be one of: " . implode(', ', $validTypes);
            }
        }

        // Validate date format
        if (isset($voucher['voucher_date']) && !$this->isValidDate($voucher['voucher_date'])) {
            $this->errors[] = "Voucher: Invalid date format for 'voucher_date'. Use YYYY-MM-DD format";
        }

        // Validate GSTIN if present
        if (isset($voucher['gst_in']) && !empty($voucher['gst_in'])) {
            if (!$this->isValidGstin($voucher['gst_in'])) {
                $this->errors[] = "Voucher: Invalid GSTIN format";
            }
        }

        // Validate voucher items
        if (isset($voucher['voucher_items'])) {
            if (!is_array($voucher['voucher_items'])) {
                $this->errors[] = "Voucher: 'voucher_items' must be an array";
            } else {
                $this->validateVoucherItems($voucher['voucher_items']);
            }
        }

        // Validate tax details
        if (isset($voucher['tax_details'])) {
            if (!is_array($voucher['tax_details'])) {
                $this->errors[] = "Voucher: 'tax_details' must be an array";
            } else {
                $this->validateTaxDetails($voucher['tax_details']);
            }
        }

        // Validate additional charges
        if (isset($voucher['additional_charges'])) {
            if (!is_array($voucher['additional_charges'])) {
                $this->errors[] = "Voucher: 'additional_charges' must be an array";
            }
        }
    }

    /**
     * Validate voucher items
     */
    protected function validateVoucherItems(array $items): void
    {
        if (empty($items)) {
            $this->errors[] = "Voucher: 'voucher_items' array is empty";
            return;
        }

        foreach ($items as $index => $item) {
            $required = ['ledger_name', 'stock_item_name', 'unit', 'price', 'qty'];
            foreach ($required as $field) {
                if (!isset($item[$field])) {
                    $this->errors[] = "Voucher item at index {$index}: Missing '{$field}'";
                }
            }

            // Validate numeric values
            if (isset($item['price']) && !is_numeric($item['price'])) {
                $this->errors[] = "Voucher item at index {$index}: 'price' must be numeric";
            }
            if (isset($item['qty']) && !is_numeric($item['qty'])) {
                $this->errors[] = "Voucher item at index {$index}: 'qty' must be numeric";
            }
        }
    }

    /**
     * Validate tax details
     */
    protected function validateTaxDetails(array $taxes): void
    {
        foreach ($taxes as $index => $tax) {
            if (!isset($tax['name'])) {
                $this->errors[] = "Tax detail at index {$index}: Missing 'name'";
            }
            if (!isset($tax['amount'])) {
                $this->errors[] = "Tax detail at index {$index}: Missing 'amount'";
            }
            if (isset($tax['amount']) && !is_numeric($tax['amount'])) {
                $this->errors[] = "Tax detail at index {$index}: 'amount' must be numeric";
            }
        }
    }

    /**
     * Validate GSTIN format
     */
    protected function isValidGstin(string $gstin): bool
    {
        // GSTIN format: 2 digits (state) + 10 alphanumeric + 1 letter + 1 digit + 1 letter + 1 alphanumeric
        // Example: 29ABCDE1234F1Z5
        return (bool) preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}[Z]{1}[0-9A-Z]{1}$/', $gstin);
    }

    /**
     * Validate date format
     */
    protected function isValidDate(string $date): bool
    {
        // Accept various date formats
        $timestamp = strtotime($date);
        return $timestamp !== false;
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors as string
     *
     * @return string
     */
    public function getErrorsAsString(): string
    {
        return implode("\n", $this->errors);
    }

    /**
     * Check if has errors
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
