<?php

namespace PostcodeEu\AddressValidation\Test\Unit\Helper;

use Magento\Customer\Helper\Address as AddressHelper;
use Magento\Developer\Helper\Data as DeveloperHelper;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Locale\Resolver as LocaleResolver;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PostcodeEu\AddressValidation\Helper\ApiClientHelper;
use PostcodeEu\AddressValidation\Helper\StoreConfigHelper;
use PostcodeEu\AddressValidation\Service\ApiAvailabilityMonitor;
use PostcodeEu\AddressValidation\Service\PostcodeApiClient;
use Psr\Log\LoggerInterface;

/**
 * Country-code mapping and autocomplete orchestration for ApiClientHelper.
 */
class ApiClientHelperTest extends TestCase
{
    #[Test]
    public function supported_iso2_codes_map_to_iso3(): void
    {
        $helper = $this->createHelper([
            (object)['iso2' => 'NL', 'iso3' => 'nld'],
            (object)['iso2' => 'DE', 'iso3' => 'deu'],
        ]);

        $this->assertSame('nld', $helper->getCountryIso3Code('NL'));
        $this->assertSame('nld', $helper->getCountryIso3Code('nl'));
        $this->assertSame('deu', $helper->getCountryIso3Code('DE'));
    }

    #[Test]
    public function unknown_iso2_codes_are_not_mapped(): void
    {
        $helper = $this->createHelper([
            (object)['iso2' => 'NL', 'iso3' => 'nld'],
        ]);

        $this->assertNull($helper->getCountryIso3Code('XX'));
    }

    #[Test]
    public function autocomplete_maps_iso2_context_to_iso3_before_lookup(): void
    {
        $client = $this->createMock(PostcodeApiClient::class);
        $client->expects($this->once())
            ->method('internationalAutocomplete')
            ->with('deu', 'Damrak', null, 'en')
            ->willReturn([]);

        $availabilityMonitor = $this->createStub(ApiAvailabilityMonitor::class);
        $availabilityMonitor->method('isAvailable')->willReturn(true);

        $localeResolver = $this->createStub(LocaleResolver::class);
        $localeResolver->method('getLocale')->willReturn('en_US');

        $helper = $this->createHelper(
            [(object)['iso2' => 'DE', 'iso3' => 'deu']],
            [
                'client' => $client,
                'availabilityMonitor' => $availabilityMonitor,
                'localeResolver' => $localeResolver,
            ]
        );

        $this->assertSame([], $helper->getAddressAutocomplete('DE', 'Damrak'));
    }

    /**
     * @param list<object{iso2: string, iso3: string}> $countries
     * @param array<string, object> $overrides
     * @return ApiClientHelper
     */
    private function createHelper(array $countries, array $overrides = []): ApiClientHelper
    {
        $storeConfigHelper = $overrides['storeConfigHelper'] ?? $this->createStub(StoreConfigHelper::class);
        $storeConfigHelper->method('getSupportedCountries')->willReturn($countries);

        $request = $overrides['request'] ?? $this->createStub(Request::class);

        if (isset($overrides['context'])) {
            $context = $overrides['context'];
        } else {
            $context = $this->createStub(Context::class);
            $context->method('getRequest')->willReturn($request);
        }

        return new ApiClientHelper(
            $overrides['moduleList'] ?? $this->createStub(ModuleListInterface::class),
            $overrides['developerHelper'] ?? $this->createStub(DeveloperHelper::class),
            $context,
            $request,
            $overrides['response'] ?? $this->createStub(Response::class),
            $overrides['client'] ?? $this->createStub(PostcodeApiClient::class),
            $overrides['localeResolver'] ?? $this->createStub(LocaleResolver::class),
            $storeConfigHelper,
            $overrides['productMetadata'] ?? $this->createStub(ProductMetadataInterface::class),
            $overrides['regionFactory'] ?? $this->createStub(RegionFactory::class),
            $overrides['addressHelper'] ?? $this->createStub(AddressHelper::class),
            $overrides['logger'] ?? $this->createStub(LoggerInterface::class),
            $overrides['availabilityMonitor'] ?? $this->createStub(ApiAvailabilityMonitor::class)
        );
    }
}
