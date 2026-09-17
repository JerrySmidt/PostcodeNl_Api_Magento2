<?php

namespace PostcodeEu\AddressValidation\Test\Unit\Api\Data;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PostcodeEu\AddressValidation\Api\Data\MagentoDebugInfo;
use PostcodeEu\AddressValidation\Api\Data\MagentoDebugInfo\Configuration;

/**
 * Debug payload defaults and nested shaping for MagentoDebugInfo.
 */
class MagentoDebugInfoTest extends TestCase
{
    #[Test]
    public function full_data_populates_all_debug_fields(): void
    {
        $data = [
            'moduleVersion' => '1.2.3',
            'magentoVersion' => '2.4.9',
            'client' => 'Postcode.eu_Magento_2',
            'session' => 'abc123',
            'configuration' => ['key' => 'api-key', 'secret' => 'api-secret'],
            'modules' => [
                ['name' => 'Magento_Catalog', 'setup_version' => '2.4.9'],
                ['name' => 'PostcodeEu_AddressValidation', 'setup_version' => '1.2.3'],
            ],
        ];

        $info = new MagentoDebugInfo($data);

        $this->assertSame('1.2.3', $info->getModuleVersion());
        $this->assertSame('2.4.9', $info->getMagentoVersion());
        $this->assertSame('Postcode.eu_Magento_2', $info->getClient());
        $this->assertSame('abc123', $info->getSession());
        $this->assertInstanceOf(Configuration::class, $info->getConfiguration());
        $this->assertSame('api-key', $info->getConfiguration()->getKey());
        $this->assertSame('api-secret', $info->getConfiguration()->getSecret());
        $this->assertSame($data['modules'], $info->getModules());
    }

    #[Test]
    public function empty_data_falls_back_to_blank_defaults(): void
    {
        $info = new MagentoDebugInfo([]);

        $this->assertSame('', $info->getModuleVersion());
        $this->assertSame('', $info->getMagentoVersion());
        $this->assertSame('', $info->getClient());
        $this->assertSame('', $info->getSession());
        $this->assertInstanceOf(Configuration::class, $info->getConfiguration());
        $this->assertSame('', $info->getConfiguration()->getKey());
        $this->assertSame('', $info->getConfiguration()->getSecret());
        $this->assertSame([], $info->getModules());
    }
}
