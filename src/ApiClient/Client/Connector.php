<?php

declare(strict_types=1);

namespace ApiClient\Client;

use ApiClient\Requests\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use RuntimeException;

/**
 * Abstract base connector for API communication.
 *
 * This class provides generic HTTP communication functionality that can be used
 * with any REST API. Concrete implementations must provide the base URL and
 * default headers specific to their API.
 *
 * Example usage:
 * <code>
 * class MyApiConnector extends Connector
 * {
 *     protected function resolveBaseUrl(): string
 *     {
 *         return 'https://api.example.com';
 *     }
 *
 *     protected function defaultHeaders(): array
 *     {
 *         return [
 *             'Accept' => 'application/json',
 *             'Authorization' => 'Bearer ' . $this->token,
 *         ];
 *     }
 * }
 * </code>
 */
abstract class Connector
{
    /**
     * The Guzzle HTTP client instance.
     */
    protected Client $httpClient;

    /**
     * Initialize the connector with a new Guzzle client.
     */
    public function __construct()
    {
        $this->httpClient = new Client();
    }

    /**
     * Resolve the base URL for API requests.
     *
     * This method should return the base URL for the API (without trailing slash).
     * All request endpoints will be appended to this base URL.
     *
     * @return string The base URL (e.g., 'https://api.example.com')
     */
    abstract protected function resolveBaseUrl(): string;

    /**
     * Get default headers for all requests.
     *
     * This method should return headers that will be included in every request.
     * Common examples include authentication headers, Accept headers, etc.
     *
     * @return array<string, string> Associative array of header name => value pairs
     */
    abstract protected function defaultHeaders(): array;

    /**
     * Send a request to the API.
     *
     * This method handles the complete HTTP request lifecycle:
     * - Builds the full URL from base URL and request endpoint
     * - Merges default headers with request-specific headers
     * - Determines content type based on request body
     * - Sends the request via Guzzle
     * - Parses the JSON response
     * - Handles HTTP errors with exceptions
     *
     * @param Request $request The request to send
     * @return array<string, mixed> The parsed JSON response as an associative array
     * @throws RuntimeException If the request fails or response cannot be parsed
     */
    public function send(Request $request): array
    {
        try {
            // Build the full URL
            $url = $this->resolveBaseUrl() . $request->resolveEndpoint();

            // Merge headers: default headers + request-specific headers
            $headers = array_merge($this->defaultHeaders(), $request->headers());

            // Prepare request options
            $options = [
                'headers' => $headers,
            ];

            // Add body if present
            $body = $request->body();
            if ($body !== []) {
                // Set Content-Type to application/json for requests with body
                // Check case-insensitively per RFC 2616
                $headerKeys = array_change_key_case($options['headers'], CASE_LOWER);
                if (!isset($headerKeys['content-type'])) {
                    $options['headers']['Content-Type'] = 'application/json';
                }
                $options['json'] = $body;
            }

            // Send the request
            $response = $this->httpClient->request(
                $request->method(),
                $url,
                $options
            );

            // Get response body
            $responseBody = (string) $response->getBody();

            // Parse JSON response
            if ($responseBody === '') {
                return [];
            }

            $decoded = json_decode($responseBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException(
                    'Failed to parse JSON response: ' . json_last_error_msg()
                );
            }

            // Handle non-associative arrays and scalar values
            if (!is_array($decoded) || array_is_list($decoded)) {
                /** @var array<string, mixed> */
                return ['data' => $decoded];
            }

            /** @var array<string, mixed> $decoded */
            return $decoded;

        } catch (RequestException $e) {
            // Handle HTTP errors (4xx, 5xx)
            $statusCode = $e->getResponse()?->getStatusCode() ?? 0;
            $responseBody = $e->getResponse()?->getBody()->getContents() ?? '';

            throw new RuntimeException(
                sprintf(
                    'HTTP request failed with status %d: %s',
                    $statusCode,
                    $responseBody !== '' ? $responseBody : $e->getMessage()
                ),
                $statusCode,
                $e
            );

        } catch (GuzzleException $e) {
            // Handle other Guzzle exceptions (network errors, etc.)
            throw new RuntimeException(
                'Request failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
