<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use ApiClient\Client\Connector;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use Tests\Fixtures\Requests\GetTokenRequest;

/**
 * Concrete connector for Great Food Ltd API.
 *
 * This demonstrates how to extend the generic Connector class
 * for a specific API implementation. It handles OAuth2 authentication
 * and provides Great Food API-specific configuration.
 */
class GreatFoodConnector extends Connector
{
    private ?string $bearerToken = null;

    /**
     * Initialize the Great Food API connector.
     *
     * @param ClientInterface $httpClient PSR-18 compliant HTTP client
     * @param RequestFactoryInterface $requestFactory PSR-17 request factory
     * @param StreamFactoryInterface $streamFactory PSR-17 stream factory
     * @param string $baseUrl Base URL for the Great Food API
     * @param string $clientId OAuth2 client ID
     * @param string $clientSecret OAuth2 client secret
     */
    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        private readonly string $baseUrl,
        private readonly string $clientId,
        private readonly string $clientSecret,
    ) {
        parent::__construct($httpClient, $requestFactory, $streamFactory);
    }

    /**
     * Resolve the base URL for Great Food API requests.
     *
     * @return string The base URL without trailing slash
     */
    protected function resolveBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get default headers for all Great Food API requests.
     *
     * @return array<string, string> Associative array of header name => value pairs
     */
    protected function defaultHeaders(): array
    {
        $headers = ['Accept' => 'application/json'];

        if ($this->bearerToken !== null) {
            $headers['Authorization'] = "Bearer {$this->bearerToken}";
        }

        return $headers;
    }

    /**
     * Authenticate with the API using OAuth2 client credentials flow.
     *
     * This method sends a token request to the API and stores the
     * access token for use in subsequent requests.
     *
     * @throws RuntimeException If authentication fails or token not found in response
     */
    public function authenticate(): void
    {
        $request = new GetTokenRequest($this->clientId, $this->clientSecret);
        $response = $this->send($request);

        if (!isset($response['access_token']) || !is_string($response['access_token'])) {
            throw new RuntimeException('Authentication failed: access_token not found in response');
        }

        $this->bearerToken = $response['access_token'];
    }

    /**
     * Check if the connector is authenticated.
     *
     * @return bool True if authenticated, false otherwise
     */
    public function isAuthenticated(): bool
    {
        return $this->bearerToken !== null;
    }
}
