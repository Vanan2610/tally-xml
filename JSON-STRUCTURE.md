# Standard JSON Structure for Tally XML Package

This document defines the standard JSON structure for both **Master Data** and **Voucher Data**.

## Master JSON Structure

Used for creating master data (ledgers, accounts, etc.)

```json
{
  "company_name": "string (required)",
  "ledger": [
    {
      "name": "string (required)",
      "parent": "string (optional)",
      "address": "string (optional)",
      "country": "string (optional)",
      "state": "string (optional)",
      "pincode": "string (optional)",
      "gst_registration_type": "string (optional: Regular|Unregistered/Consumer|Composition)",
      "gst_in": "string (optional: 15-char GSTIN format)",
      "gst_duty_head": "string (optional: CGST|SGST/UTGST|IGST)",
      "gst_percentage": "integer (optional)"
    }
  ],
  "download": "boolean (optional)"
}
```

## Voucher JSON Structure

Used for creating vouchers (sales, purchase, payment, etc.)

```json
{
  "server_url": "string (optional)",
  "company_name": "string (required)",
  
  "units": [
    {
      "name": "string (required)",
      "uqc_name": "string (required)",
      "decimal_point": "integer (optional)"
    }
  ],
  
  "ledgers": [
    {
      "name": "string (required)",
      "parent": "string (optional)",
      "address": "string (optional)",
      "country": "string (optional)",
      "state": "string (optional)",
      "pincode": "string (optional)",
      "gst_registration_type": "string (optional)",
      "gst_in": "string (optional: 15-char GSTIN)",
      "gst_duty_head": "string (optional: CGST|SGST/UTGST|IGST)",
      "gst_percentage": "integer (optional)"
    }
  ],
  
  "stock_item": [
    {
      "name": "string (required)",
      "hsn": "string (required if gst_applicable=true)",
      "unit": "string (required)",
      "gst_percentage": "integer (required if gst_applicable=true)",
      "gst_applicable": "boolean (required)",
      "gst_supply_type": "string (optional: Goods|Services|Goods & Services)",
      "price": "integer (optional)"
    }
  ],
  
  "voucher": {
    "voucher_type": "string (required: Sale|Purchase|Sale Return|Purchase Return|Receipt|Payment|Journal)",
    "voucher_number": "string (required)",
    "voucher_date": "string (required: YYYY-MM-DD or any PHP strtotime format)",
    "place_of_supply": "string (optional)",
    
    "ledger_name": "string (optional)",
    "address_line_1": "string (optional)",
    "address_line_2": "string (optional)",
    "country": "string (optional)",
    "state": "string (optional)",
    "pincode": "string (optional)",
    "gst_registration_type": "string (optional)",
    "gst_in": "string (optional: 15-char GSTIN format)",
    
    "narration": "string (optional)",
    "total": "integer (optional)",
    
    "voucher_items": [
      {
        "ledger_name": "string (required)",
        "stock_item_name": "string (required)",
        "unit": "string (required)",
        "price": "numeric (required)",
        "qty": "numeric (required)",
        "discount": "numeric (optional)"
      }
    ],
    
    "tax_details": [
      {
        "name": "string (required)",
        "amount": "numeric (required)"
      }
    ],
    
    "additional_charges": [
      {
        "name": "string (required)",
        "amount": "numeric (required)"
      }
    ],
    
    "round_off": {
      "name": "string (required)",
      "amount": "numeric (required: can be positive or negative)"
    }
  }
}
```

## Validation Rules

### General Rules
- `company_name` is **required** in all cases
- All arrays can be empty but must be arrays if present
- Numeric fields must be valid numbers

### Voucher Type
Must be one of:
- `Sale` or `Sales`
- `Purchase`
- `Sale Return`
- `Purchase Return`
- `Payment`
- `Receipt`
- `Journal`

### Date Format
Accepts any PHP `strtotime()` compatible format:
- `2024-12-01`
- `01-12-2024`
- `2024/12/01`
- `December 1, 2024`

### GSTIN Format
Must match Indian GSTIN pattern:
- Format: `99AAAAA9999A9A9A`
- Example: `29ABCDE1234F1Z5`
- 2 digits (state code)
- 10 alphanumeric characters
- Specific pattern for last 5 characters

### Stock Items with GST
If `gst_applicable = true`, then required:
- `hsn` (HSN code)
- `gst_percentage`

### Voucher Items
Required fields:
- `ledger_name`
- `stock_item_name`
- `unit`
- `price` (numeric)
- `qty` (numeric)

## Using Validation

### Automatic Validation (Default)
```php
use Tamilvanan\TallyXml\Converters\JsonToVoucherConverter;

$converter = new JsonToVoucherConverter();
try {
    $xml = $converter->convert($json);  // Validates automatically
} catch (\InvalidArgumentException $e) {
    echo "Validation failed: " . $e->getMessage();
}
```

### Manual Validation
```php
use Tamilvanan\TallyXml\Validation\JsonValidator;

$validator = new JsonValidator();

if ($validator->validateVoucherJson($json)) {
    echo "Valid!";
} else {
    foreach ($validator->getErrors() as $error) {
        echo "- {$error}\n";
    }
}
```

### Bypass Validation
```php
// Skip validation (use with caution!)
$xml = $converter->convert($json, false);
```

## Common Validation Errors

### Missing Required Fields
```
Missing required field: 'company_name'
Voucher: Missing required field 'voucher_number'
```

### Invalid Voucher Type
```
Voucher: Invalid voucher_type 'SaleInvoice'. Must be one of: Sale, Purchase, ...
```

### Invalid GSTIN
```
Voucher: Invalid GSTIN format
Ledger 'ABC Corp': Invalid GSTIN format
```

### Missing GST Details
```
Stock item 'Product A': GST applicable but 'gst_percentage' missing
Stock item 'Product A': GST applicable but 'hsn' missing
```

### Invalid Numeric Values
```
Voucher item at index 0: 'price' must be numeric
Tax detail at index 0: 'amount' must be numeric
```

## Complete Example

See [test-validation.php](../examples/test-validation.php) for complete working examples.
