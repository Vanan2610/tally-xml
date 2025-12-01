<?php

namespace Devtally\TallyXml\Converters;

use Devtally\TallyXml\TallyXmlConverter;
use Devtally\TallyXml\Builders\LedgerBuilder;

/**
 * JsonToMasterConverter - Convert JSON to Tally Master XML
 * 
 * Converts the user's master JSON structure to Tally-compatible XML
 */
class JsonToMasterConverter
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
            
            if (!$validator->validateMasterJson($json)) {
                throw new \InvalidArgumentException(
                    "JSON validation failed:\n" . $validator->getErrorsAsString()
                );
            }
        }

        $this->companyName = $json['company_name'] ?? '';
        
        $allMasters = [];

        // Process ledgers
        if (isset($json['ledger']) && is_array($json['ledger'])) {
            foreach ($json['ledger'] as $ledgerData) {
                $ledger = $this->buildLedger($ledgerData);
                $allMasters[] = $ledger;
            }
        }

        // Combine all masters and convert to XML
        return $this->convertToXml($allMasters);
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

        // Add registration details if we have all required info
        if (!empty($data['state']) && !empty($data['gst_registration_type']) && !empty($data['gst_in'])) {
            $builder->gstRegistrationDetails(
                $data['state'],
                $data['gst_registration_type'],
                $data['gst_in']
            );
        }

        // Add mailing details if we have required info
        if (!empty($data['state']) && !empty($data['country']) && !empty($data['name']) && !empty($data['pincode'])) {
            $builder->mailingDetails(
                $data['name'],
                $data['state'],
                $data['country'],
                $data['pincode']
            );
        }

        return $builder->build();
    }

    /**
     * Convert masters array to XML
     */
    protected function convertToXml(array $masters): string
    {
        // Set company name and create envelope structure
        $data = [
            '_COMPANY_NAME' => $this->companyName,
            'MASTERS' => $masters,
        ];

        return $this->converter->convert($data, 'Import');
    }
}
