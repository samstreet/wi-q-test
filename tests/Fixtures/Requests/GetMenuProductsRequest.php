<?php

declare(strict_types=1);

namespace Tests\Fixtures\Requests;

use ApiClient\Http\HttpMethod;
use ApiClient\Requests\Request;

/**
 * Request to fetch products for a specific menu.
 *
 * This request retrieves all products associated with a given menu ID.
 */
class GetMenuProductsRequest extends Request
{
    /**
     * Initialize the menu products request.
     *
     * @param int $menuId The ID of the menu to fetch products for
     */
    public function __construct(
        private readonly int $menuId,
    ) {
    }

    /**
     * Resolve the menu products endpoint.
     *
     * @return string The menu products endpoint path with menu ID
     */
    public function resolveEndpoint(): string
    {
        return "/menu/{$this->menuId}/products";
    }

    /**
     * Get the HTTP method for fetching menu products.
     *
     * @return string The HTTP method (GET)
     */
    public function method(): string
    {
        return HttpMethod::GET->value;
    }
}
