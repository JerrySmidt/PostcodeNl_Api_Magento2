<?php

namespace PostcodeEu\AddressValidation\Test\Unit\Service;

use Magento\Framework\App\ProductMetadataInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PostcodeEu\AddressValidation\Helper\StoreConfigHelper;
use PostcodeEu\AddressValidation\HTTP\Client\Curl;
use PostcodeEu\AddressValidation\Service\ApiAvailabilityMonitor;
use PostcodeEu\AddressValidation\Service\Exception\AuthenticationException;
use PostcodeEu\AddressValidation\Service\Exception\BadRequestException;
use PostcodeEu\AddressValidation\Service\Exception\CurlException;
use PostcodeEu\AddressValidation\Service\Exception\ForbiddenException;
use PostcodeEu\AddressValidation\Service\Exception\InvalidJsonResponseException;
use PostcodeEu\AddressValidation\Service\Exception\NotFoundException;
use PostcodeEu\AddressValidation\Service\Exception\ServiceUnavailableException;
use PostcodeEu\AddressValidation\Service\Exception\TooManyRequestsException;
use PostcodeEu\AddressValidation\Service\Exception\UnexpectedException;
use PostcodeEu\AddressValidation\Service\PostcodeApiClient;

/**
 * Response handling and HTTP status to exception mapping for PostcodeApiClient.
 */
class PostcodeApiClientTest extends TestCase
{
    #[Test]
    public function successful_api_response_returns_decoded_json(): void
    {
        $result = $this->createClientWithResponse(200, '{"foo":"bar"}')->accountInfo();

        $this->assertSame(['foo' => 'bar'], $result);
    }

    #[Test]
    public function successful_response_with_invalid_json_raises_exception(): void
    {
        $this->expectException(InvalidJsonResponseException::class);

        $this->createClientWithResponse(200, 'not-json')->accountInfo();
    }

    #[Test]
    public function curl_failure_raises_curl_exception(): void
    {
        $this->expectException(CurlException::class);

        $curl = $this->createStub(Curl::class);
        $curl->method('get')->willThrowException(new \RuntimeException('Connection failed'));

        $this->createClient($curl)->accountInfo();
    }

    #[Test]
    #[DataProvider('httpStatusExceptionProvider')]
    public function non_success_http_status_maps_to_typed_exception(int $statusCode, string $exceptionClass): void
    {
        $this->expectException($exceptionClass);

        $this->createClientWithResponse($statusCode)->accountInfo();
    }

    /**
     * @return array<string, array{int, class-string<\Throwable>}>
     */
    public static function httpStatusExceptionProvider(): array
    {
        return [
            '400' => [400, BadRequestException::class],
            '401' => [401, AuthenticationException::class],
            '403' => [403, ForbiddenException::class],
            '404' => [404, NotFoundException::class],
            '429' => [429, TooManyRequestsException::class],
            '503' => [503, ServiceUnavailableException::class],
            '500' => [500, UnexpectedException::class],
        ];
    }

    /**
     * @param int $statusCode
     * @param string $body
     * @return PostcodeApiClient
     */
    private function createClientWithResponse(int $statusCode, string $body = '{}'): PostcodeApiClient
    {
        $curl = $this->createStub(Curl::class);
        $curl->method('getStatus')->willReturn($statusCode);
        $curl->method('getBody')->willReturn($body);

        return $this->createClient($curl);
    }

    /**
     * @param Curl $curl
     * @return PostcodeApiClient
     */
    private function createClient(Curl $curl): PostcodeApiClient
    {
        $storeConfigHelper = $this->createStub(StoreConfigHelper::class);
        $storeConfigHelper->method('getCurrentStoreBaseUrl')->willReturn('https://example.com/');
        $storeConfigHelper->method('getCredentials')->willReturn(['key' => 'key', 'secret' => 'secret']);
        $storeConfigHelper->method('getModuleVersion')->willReturn('1.0.0');

        $productMetadata = $this->createStub(ProductMetadataInterface::class);
        $productMetadata->method('getName')->willReturn('Magento');
        $productMetadata->method('getEdition')->willReturn('Community');
        $productMetadata->method('getVersion')->willReturn('2.4.8');

        return new PostcodeApiClient(
            $curl,
            $productMetadata,
            $storeConfigHelper,
            $this->createStub(ApiAvailabilityMonitor::class)
        );
    }
}
