# Tally XML Converter

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Convert JSON data to Tally-compatible XML for direct import into Tally ERP. Comprehensive support for vouchers, master data, GST, inventory, and more.

## Installation

Install via Composer:

```bash
composer require devtally/tally-xml
```

## Features

- 🔄 **JSON to Tally XML Conversion** - Direct conversion from your JSON data
- 📝 **Fluent Builder Pattern** - Easy, readable code with method chaining
- 💼 **Master Data Support** - Create ledgers, stock items, and units
- 📊 **Complete Voucher Support** - Sales, Purchase, Payment, Receipt, Returns
- 💰 **GST Ready** - Full GST support with automatic CGST/SGST/IGST handling
- 📦 **Inventory Management** - Stock items with batch allocation
- ✅ **Well Tested** - Comprehensive test suite
- 🎯 **Simple API** - Intuitive and easy to use

## Quick Start

### Convert JSON to Tally XML

The easiest way to get started is using the JSON converters:

```php
use Tamilvanan\TallyXml\Converters\JsonToVoucherConverter;

$json = [
    'company_name' => 'My Company Ltd',
    'voucher' => [
        'voucher_type' => 'Sale',
        'voucher_number' => 'INV-001',
        'voucher_date' => '2023-12-01',
        'ledger_name' => 'Customer Name',
        'narration' => 'Sale of goods',
        'total' => 11800,
        'voucher_items' => [
            [
                'ledger_name' => 'Sales Account',
                'stock_item_name' => 'Product A',
                'unit' => 'Nos',
                'price' => 1000,
                'qty' => 10,
                'discount' => 0
            ]
        ],
        'tax_details' => [
            ['name' => 'CGST @ 9%', 'amount' => 900],
            ['name' => 'SGST @ 9%', 'amount' => 900]
        ],
        'additional_charges' => [],
        'round_off' => ['name' => 'Round Off', 'amount' => 0]
    ]
];

$converter = new JsonToVoucherConverter();
$xml = $converter->convert($json);

// Save or send to Tally
file_put_contents('voucher.xml', $xml);
```

## Usage Guide

### 1. Master Data - Create Ledgers

```php
use Tamilvanan\TallyXml\Builders\LedgerBuilder;
use Tamilvanan\TallyXml\TallyXmlConverter;

// Create a customer ledger
$ledger = LedgerBuilder::customer('ABC Corporation')
    -> address('123 Business Park')
    ->state('Tamil Nadu')
    ->country('India')
    ->pincode('600001')
    ->gstRegistrationType('Regular')
    ->gstin('33ABCDE1234F1Z5')
    ->build();

// Convert to XML
$converter = new TallyXmlConverter();
$xml = $converter->convert($ledger);
```

**Other Ledger Types:**

```php
// Supplier
$supplier = LedgerBuilder::supplier('XYZ Supplies')->build();

// Sales Account
$sales = LedgerBuilder::salesAccount('Local Sales')->build();

// Purchase Account
$purchase = LedgerBuilder::purchaseAccount('Local Purchases')->build();

// Custom ledger with parent
$ledger = LedgerBuilder::create('Transport Charges', 'Indirect Expenses')->build();
```

### 2. Master Data - Create Stock Items

```php
use Tamilvanan\TallyXml\Builders\StockItemBuilder;

$stockItem = StockItemBuilder::create('Laptop HP Pavilion', 'Nos')
    ->gstApplicable(true)
    ->gstSupplyType('Goods')
    ->gstDetails('84713000', 18, 45000) // HSN, GST%, Price
    ->build();

$xml = $converter->convert($stockItem);
```

### 3. Master Data - Create Units

```php
use Tamilvanan\TallyXml\Builders\UnitBuilder;

$unit = UnitBuilder::create('Boxes', 'BOX')
    ->decimalPlaces(2)
    ->build();

$xml = $converter->convert($unit);
```

### 4. Create Vouchers - Sales Invoice

```php
use Tamilvanan\TallyXml\Builders\VoucherBuilder;

$voucher = VoucherBuilder::sales('INV-2024-001', '2024-01-15')
    ->partyName('ABC Corporation')
    ->partyAddress('123 Business Street')
    ->partyAddress('Bangalore, Karnataka')
    ->partyState('Karnataka')
    ->partyCountry('India')
    ->partyPincode('560001')
    ->partyGstRegistrationType('Regular')
    ->partyGstin('29ABCDE1234F1Z5')
    ->placeOfSupply('Karnataka')
    ->narration('Sale of laptops and accessories')
    ->reference('PO-ABC-456')
    
    // Add items
    ->addVoucherItem('Sales Account', 'Laptop HP Pavilion', 'Nos', 45000, 2, 0)
    ->addVoucherItem('Sales Account', 'Wireless Mouse', 'Nos', 500, 10, 0)
    
    // Add taxes
    ->addTax('CGST @ 9%', 8550)
    ->addTax('SGST @ 9%', 8550)
    
    // Round off
    ->roundOff('Round Off', 0.50)
    
    // Party total amount
    ->partyAmount('ABC Corporation', 112600.50)
    
    ->build();

$xml = $converter->convert($voucher);
```

### 5. Create Vouchers - Purchase

```php
$voucher = VoucherBuilder::purchase('PUR-001', '2024-01-15')
    ->partyName('Supplier XYZ')
    ->narration('Purchase of raw materials')
    ->addVoucherItem('Purchase Account', 'Raw Material A', 'Kgs', 100, 50, 0)
    ->addTax('CGST @ 6%', 300)
    ->addTax('SGST @ 6%', 300)
    ->partyAmount('Supplier XYZ', 5600)
    ->build();
```

### 6. Create Vouchers - Payment

```php
$voucher = VoucherBuilder::payment('PAY-001', '2024-01-15')
    ->partyLedger('Supplier XYZ')
    ->narration('Payment for invoice PUR-001')
    ->reference('PUR-001')
    ->addLedgerEntry('Supplier XYZ', -5600, false) // Credit
    ->addLedgerEntry('Cash', 5600, true) // Debit
    ->build();
```

### 7. Create Vouchers - Sale Return

```php
$voucher = VoucherBuilder::saleReturn('SR-001', '2024-01-15')
    ->partyName('ABC Corporation')
    ->narration('Return of defective items')
    ->addVoucherItem('Sales Account', 'Laptop HP Pavilion', 'Nos', 45000, 1, 0)
    ->addTax('CGST @ 9%', -4050)
    ->addTax('SGST @ 9%', -4050)
    ->partyAmount('ABC Corporation', -53100)
    ->build();
```

## Complete JSON Structure Examples

### Purchase Voucher JSON

```json
{
    "company_name": "My Company Ltd",
    "units": [
        {
            "name": "Boxes",
            "uqc_name": "BOX",
            "decimal_point": 2
        }
    ],
    "ledgers": [
        {
            "name": "Supplier ABC",
            "parent": "Sundry Creditors",
            "address": "456 Supply Street",
            "country": "India",
            "state": "Tamil Nadu",
            "pincode": "600002",
            "gst_registration_type": "Regular",
            "gst_in": "33XYZAB5678G1H2"
        }
    ],
    "stock_item": [
        {
            "name": "Product X",
            "hsn": "84713000",
            "unit": "Nos",
            "gst_percentage": 18,
            "gst_applicable": true,
            "gst_supply_type": "Goods",
            "price": 1000
        }
    ],
    "voucher": {
        "voucher_type": "Purchase",
        "voucher_number": "PUR-001",
        "voucher_date": "2024-01-15",
        "place_of_supply": "Tamil Nadu",
        "ledger_name": "Supplier ABC",
        "address_line_1": "456 Supply Street",
        "address_line_2": "Near Central Station",
        "country": "India",
        "state": "Tamil Nadu",
        "pincode": "600002",
        "gst_registration_type": "Regular",
        "gst_in": "33XYZAB5678G1H2",
        "narration": "Purchase of goods",
        "total": 11800,
        "voucher_items": [
            {
                "ledger_name": "Purchase Account",
                "stock_item_name": "Product X",
                "unit": "Nos",
                "price": 1000,
                "qty": 10,
                "discount": 0
            }
        ],
        "tax_details": [
            {"name": "CGST @ 9%", "amount": 900},
            {"name": "SGST @ 9%", "amount": 900}
        ],
        "additional_charges": [
            {"name": "Transport Charges", "amount": 500}
        ],
        "round_off": {
            "name": "Round Off",
            "amount": -0.50
        }
    }
}
```

### Master Data JSON

```json
{
    "company_name": "My Company Ltd",
    "ledger": [
        {
            "name": "Customer ABC",
            "parent": "Sundry Debtors",
            "address": "123 Customer Street",
            "country": "India",
            "state": "Karnataka",
            "pincode": "560001",
            "gst_registration_type": "Regular",
            "gst_in": "29ABCDE1234F1Z5",
            "gst_duty_head": "CGST",
            "gst_percentage": 9
        }
    ],
    "download": true
}
```

## Supported Voucher Types

- **Sale** / **Sales** - Sales invoices
- **Purchase** - Purchase invoices
- **Sale Return** - Sales returns/credit notes
- **Purchase Return** - Purchase returns/debit notes
- **Payment** - Payment vouchers
- **Receipt** - Receipt vouchers
- **Journal** - Journal entries
- **Contra** - Contra entries

## GST Support

The package automatically handles:
- ✅ CGST/SGST split for intra-state transactions
- ✅ IGST for inter-state transactions
- ✅ GST registration types (Regular/Unregistered/Consumer)
- ✅ GSTIN validation format
- ✅ Place of supply
- ✅ HSN codes for stock items

## Date Formats

Tally uses `YYYYMMDD` format. The package automatically converts:
- `'2024-01-15'` → `'20240115'`
- `'15-01-2024'` → `'20240115'`
- Any PHP `strtotime()` compatible format

## Testing

Run the test suite:

```bash
composer install
vendor/bin/phpunit
```

## Requirements

- PHP 7.4 or higher
- ext-dom (usually included by default)

## Examples Directory

Check the `examples/` directory for more complete working examples:
- Creating master data
- Sales/Purchase vouchers with GST
- Payment/Receipt vouchers
- Batch imports

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Author

**Tamilvanan**  
Email: tamilvanan2610@gmail.com

## Support

If you encounter any issues or have questions:
1. Check the examples directory
2. Review the tests for usage patterns
3. Open an issue on GitHub

## Changelog

### Version 2.0.0 (Enhanced)
- ✨ Added comprehensive master data builders (Ledger, StockItem, Unit)
- ✨ Enhanced VoucherBuilder with full GST support
- ✨ Added JSON to XML converters for direct integration
- ✨ Support for inventory items with batch allocation
- ✨ Tax entries, additional charges, and round-off support
- ✨ Sale Return and Purchase Return voucher types
- ✨ Automatic debit/credit calculation based on voucher type
- 📚 Comprehensive documentation and examples

### Version 1.0.0
- Initial release with basic converter and voucher builder
