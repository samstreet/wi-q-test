<?php

declare(strict_types=1);

namespace Tests\Fixtures\Requests;

use ApiClient\Http\HttpMethod;
use ApiClient\Requests\Request;

/**
 * OAuth2 token request for Great Food Ltd API.
 *
 * This request implements the client credentials flow to obtain
 * an access token for API authentication.
 */
class GetTokenRequest extends Request
{
    /**
     * Initialize the token request.
     *
     * @param string $clientId OAuth2 client ID
     * @param string $clientSecret OAuth2 client secret
     */
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
    ) {
    }

    /**
     * Resolve the authentication endpoint.
     *
     * @return string The token endpoint path
     */
    public function resolveEndpoint(): string
    {
        return '/auth_token';
    }

    /**
     * Get the HTTP method for token request.
     *
     * @return string The HTTP method (POST)
     */
    public function method(): string
    {
        return HttpMethod::POST->value;
    }

    /**
     * Get headers for the token request.
     *
     * @return array<string, string> Headers for form-urlencoded content
     */
    public function headers(): array
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
    }

    /**
     * Get the request body for OAuth2 client credentials flow.
     *
     * @return array<string, string> The OAuth2 credentials
     */
    public function body(): array
    {
        return [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ];
    }
}
