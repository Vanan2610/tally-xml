<?php

namespace Devtally\TallyXml\Tests;

use PHPUnit\Framework\TestCase;
use Devtally\TallyXml\TallyXmlConverter;

class TallyXmlConverterTest extends TestCase
{
    protected $converter;

    protected function setUp(): void
    {
        $this->converter = new TallyXmlConverter();
    }

    public function testConverterInitialization()
    {
        $this->assertInstanceOf(TallyXmlConverter::class, $this->converter);
        $this->assertEquals('9.0', $this->converter->getTallyVersion());
    }

    public function testBasicConversion()
    {
        $data = [
            'TALLYMESSAGE' => [
                'COMPANY' => [
                    'NAME' => 'Test Company',
                    'ADDRESS' => 'Test Address',
                ]
            ]
        ];

        $xml = $this->converter->convert($data);

        $this->assertStringContainsString('<ENVELOPE>', $xml);
        $this->assertStringContainsString('<HEADER>', $xml);
        $this->assertStringContainsString('<BODY>', $xml);
        $this->assertStringContainsString('<TALLYREQUEST>Import</TALLYREQUEST>', $xml);
        $this->assertStringContainsString('Test Company', $xml);
    }

    public function testSetTallyVersion()
    {
        $this->converter->setTallyVersion('9.0.1');
        $this->assertEquals('9.0.1', $this->converter->getTallyVersion());
    }

    public function testXmlStructure()
    {
        $data = ['TEST' => 'value'];
        $xml = $this->converter->convert($data);

        // Parse XML to verify structure
        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        $envelope = $doc->getElementsByTagName('ENVELOPE')->item(0);
        $this->assertNotNull($envelope);

        $header = $doc->getElementsByTagName('HEADER')->item(0);
        $this->assertNotNull($header);

        $body = $doc->getElementsByTagName('BODY')->item(0);
        $this->assertNotNull($body);
    }
}
