<?php

declare(strict_types=1);

namespace Tests\Fixtures\Requests;

use ApiClient\Http\HttpMethod;
use ApiClient\Requests\Request;

/**
 * Request to fetch all menus from Great Food Ltd API.
 *
 * This request retrieves a list of all available menus.
 */
class GetMenusRequest extends Request
{
    /**
     * Resolve the menus endpoint.
     *
     * @return string The menus endpoint path
     */
    public function resolveEndpoint(): string
    {
        return '/menus';
    }

    /**
     * Get the HTTP method for fetching menus.
     *
     * @return string The HTTP method (GET)
     */
    public function method(): string
    {
        return HttpMethod::GET->value;
    }
}
