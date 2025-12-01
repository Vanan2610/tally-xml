<?php

namespace Devtally\TallyXml;

use DOMDocument;
use DOMElement;

/**
 * TallyXmlConverter - Main class for converting JSON data to Tally XML format
 * 
 * This class provides methods to convert various JSON data structures
 * into Tally-compatible XML format for import.
 */
class TallyXmlConverter
{
    /**
     * @var DOMDocument
     */
    protected $dom;

    /**
     * @var string Tally version (default: 9.0)
     */
    protected $tallyVersion = '9.0';

    /**
     * Initialize the converter
     */
    public function __construct(string $tallyVersion = '9.0')
    {
        $this->tallyVersion = $tallyVersion;
        $this->initializeDom();
    }

    /**
     * Initialize DOM Document
     */
    protected function initializeDom(): void
    {
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->formatOutput = true;
        $this->dom->preserveWhiteSpace = false;
    }

    /**
     * Convert JSON data to Tally XML
     *
     * @param array $data JSON data as associative array
     * @param string $requestType Type of Tally request (e.g., 'Import', 'Export')
     * @return string XML string
     */
    public function convert(array $data, string $requestType = 'Import'): string
    {
        $this->initializeDom();

        // Create root ENVELOPE element
        $envelope = $this->dom->createElement('ENVELOPE');
        $this->dom->appendChild($envelope);

        // Add HEADER
        $header = $this->createHeader($requestType);
        $envelope->appendChild($header);

        // Add BODY
        $body = $this->createBody($data);
        $envelope->appendChild($body);

        return $this->dom->saveXML();
    }

    /**
     * Create HEADER element
     */
    protected function createHeader(string $requestType): DOMElement
    {
        $header = $this->dom->createElement('HEADER');
        
        $tallyRequest = $this->dom->createElement('TALLYREQUEST', $requestType);
        $header->appendChild($tallyRequest);

        return $header;
    }

    /**
     * Create BODY element from data
     */
    protected function createBody(array $data): DOMElement
    {
        $body = $this->dom->createElement('BODY');
        
        // Add IMPORTDATA section
        $importData = $this->dom->createElement('IMPORTDATA');
        $body->appendChild($importData);

        // Add REQUESTDESC
        $requestDesc = $this->createRequestDesc();
        $importData->appendChild($requestDesc);

        // Add REQUESTDATA
        $requestData = $this->createRequestData($data);
        $importData->appendChild($requestData);

        return $body;
    }

    /**
     * Create REQUESTDESC element
     */
    protected function createRequestDesc(): DOMElement
    {
        $requestDesc = $this->dom->createElement('REQUESTDESC');
        
        $reportName = $this->dom->createElement('REPORTNAME', 'All Masters');
        $requestDesc->appendChild($reportName);

        return $requestDesc;
    }

    /**
     * Create REQUESTDATA element from data
     */
    protected function createRequestData(array $data): DOMElement
    {
        $requestData = $this->dom->createElement('REQUESTDATA');

        // Process each item in the data array
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->addArrayElement($requestData, $key, $value);
            } else {
                $element = $this->dom->createElement($key, htmlspecialchars((string)$value));
                $requestData->appendChild($element);
            }
        }

        return $requestData;
    }

    /**
     * Add array element to parent
     */
    protected function addArrayElement(DOMElement $parent, string $key, array $data): void
    {
        // Handle numeric keys (list items) - use parent's tag without .LIST
        if (is_numeric($key)) {
            // For numeric keys, determine the element name from context
            // We'll create each item directly
            foreach ($data as $childKey => $childValue) {
                if (is_array($childValue)) {
                    $this->addArrayElement($parent, $childKey, $childValue);
                } else {
                    $childElement = $this->dom->createElement($this->cleanElementName($childKey), htmlspecialchars((string)$childValue));
                    $parent->appendChild($childElement);
                }
            }
            return;
        }

        // Clean element name (remove .LIST suffix for the container, items will be added inside)
        $elementName = $this->cleanElementName($key);
        $element = $this->dom->createElement($elementName);
        $parent->appendChild($element);

        foreach ($data as $childKey => $childValue) {
            if (is_array($childValue)) {
                $this->addArrayElement($element, $childKey, $childValue);
            } else {
                // Skip empty values
                if ($childValue === '' || $childValue === null) {
                    continue;
                }
                $childElement = $this->dom->createElement($this->cleanElementName($childKey), htmlspecialchars((string)$childValue));
                $element->appendChild($childElement);
            }
        }
    }

    /**
     * Clean element name for XML compatibility
     */
    protected function cleanElementName(string $name): string
    {
        // Remove .LIST suffix for XML elements
        $name = str_replace('.LIST', '', $name);
        
        // Remove any characters that aren't valid in XML element names
        // XML element names can contain letters, digits, hyphens, underscores, and periods
        // but cannot start with digits
        $name = preg_replace('/[^a-zA-Z0-9\-_.]/', '', $name);
        
        return $name;
    }

    /**
     * Set Tally version
     */
    public function setTallyVersion(string $version): self
    {
        $this->tallyVersion = $version;
        return $this;
    }

    /**
     * Get current Tally version
     */
    public function getTallyVersion(): string
    {
        return $this->tallyVersion;
    }
}
