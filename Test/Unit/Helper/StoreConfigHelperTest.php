<?php

namespace PostcodeEu\AddressValidation\Test\Unit\Helper;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Developer\Helper\Data as DeveloperHelper;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PostcodeEu\AddressValidation\Helper\StoreConfigHelper;
use PostcodeEu\AddressValidation\Model\Config\Source\NlInputBehavior;
use PostcodeEu\AddressValidation\Model\Config\Source\ShowHideAddressFields;

/**
 * getJsinit() keys and behaviour flags for StoreConfigHelper.
 */
class StoreConfigHelperTest extends TestCase
{
    private const SUPPORTED_COUNTRIES_JSON = '[{"iso2":"NL","iso3":"nld"},{"iso2":"BE","iso3":"bel"}]';

    #[Test]
    public function frontend_init_config_includes_api_urls_and_flags(): void
    {
        $helper = $this->createHelper([
            StoreConfigHelper::PATH['supported_countries'] => self::SUPPORTED_COUNTRIES_JSON,
            StoreConfigHelper::PATH['disabled_countries'] => 'BE',
            StoreConfigHelper::PATH['allow_pobox_shipping'] => '1',
        ]);

        $jsinit = $helper->getJsinit();

        $this->assertSame(
            [
                'enabled_countries',
                'nl_input_behavior',
                'show_hide_address_fields',
                'base_url',
                'api_actions',
                'debug',
                'change_fields_position',
                'allow_pobox_shipping',
                'split_street_values',
            ],
            array_keys($jsinit)
        );
        $this->assertSame(['NL'], $jsinit['enabled_countries']);
        $this->assertSame(NlInputBehavior::ZIP_HOUSE, $jsinit['nl_input_behavior']);
        $this->assertSame(ShowHideAddressFields::SHOW, $jsinit['show_hide_address_fields']);
        $this->assertFalse($jsinit['debug']);
        $this->assertTrue($jsinit['allow_pobox_shipping']);
        $this->assertSame(
            [
                'dutchAddressLookup' => 'https://example.com/postcode-eu/V1/nl/address/{postcode}/{houseNumber}?form_key=FORMKEY',
                'autocomplete' => 'https://example.com/postcode-eu/V1/international/autocomplete/{context}/{term}?form_key=FORMKEY',
                'addressDetails' => 'https://example.com/postcode-eu/V1/international/address/{context}?form_key=FORMKEY',
                'validate' => 'https://example.com/postcode-eu/V1/international/validate/{country}?form_key=FORMKEY',
            ],
            $jsinit['api_actions']
        );
    }

    #[Test]
    public function admin_init_config_uses_backend_api_proxy(): void
    {
        $helper = $this->createHelper(
            [StoreConfigHelper::PATH['supported_countries'] => self::SUPPORTED_COUNTRIES_JSON],
            FrontNameResolver::AREA_CODE
        );

        $backendUrl = 'https://admin.example.com/postcode_eu/address/api/';

        $this->assertSame(
            [
                'dutchAddressLookup' => $backendUrl . 'method/postcode/postcode/{postcode}/house_number/{houseNumber}',
                'autocomplete' => $backendUrl . 'method/autocomplete/context/{context}/term/{term}',
                'addressDetails' => $backendUrl . 'method/address_details/context/{context}',
            ],
            $helper->getJsinit()['api_actions']
        );
    }

    /**
     * @param array<string, string|null> $config
     * @param string $areaCode
     * @return StoreConfigHelper
     */
    private function createHelper(array $config = [], string $areaCode = 'frontend'): StoreConfigHelper
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            function (string $path) use ($config) {
                return $config[$path] ?? null;
            }
        );
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            function (string $path) use ($config) {
                return !empty($config[$path]);
            }
        );

        $urlBuilder = $this->createStub(UrlInterface::class);
        $urlBuilder->method('getBaseUrl')->willReturn('https://example.com/');

        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);
        $context->method('getUrlBuilder')->willReturn($urlBuilder);
        $context->method('getRequest')->willReturn($this->createStub(RequestInterface::class));

        $store = $this->createStub(StoreInterface::class);
        $store->method('getCode')->willReturn('default');

        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $appState = $this->createStub(AppState::class);
        $appState->method('getAreaCode')->willReturn($areaCode);

        $formKey = $this->createStub(FormKey::class);
        $formKey->method('getFormKey')->willReturn('FORMKEY');

        $backendUrl = $this->createStub(BackendUrlInterface::class);
        $backendUrl->method('getUrl')->willReturn('https://admin.example.com/postcode_eu/address/api/');

        return new StoreConfigHelper(
            $context,
            $storeManager,
            $this->createStub(DeveloperHelper::class),
            $this->createStub(EncryptorInterface::class),
            $this->createStub(CountryCollectionFactory::class),
            $this->createStub(ResolverInterface::class),
            $formKey,
            $appState,
            $backendUrl
        );
    }
}
