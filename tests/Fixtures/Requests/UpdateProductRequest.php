<?php

declare(strict_types=1);

namespace Tests\Fixtures\Requests;

use ApiClient\Http\HttpMethod;
use ApiClient\Requests\Request;
use Tests\Fixtures\Models\Product;

/**
 * Request to update a product in Great Food Ltd API.
 *
 * This request updates a specific product within a menu.
 */
class UpdateProductRequest extends Request
{
    /**
     * Initialize the product update request.
     *
     * @param int $menuId The ID of the menu containing the product
     * @param int $productId The ID of the product to update
     * @param Product $product The product data to update
     */
    public function __construct(
        private readonly int $menuId,
        private readonly int $productId,
        private readonly Product $product,
    ) {
    }

    /**
     * Resolve the product update endpoint.
     *
     * @return string The product update endpoint path with menu and product IDs
     */
    public function resolveEndpoint(): string
    {
        return "/menu/{$this->menuId}/product/{$this->productId}";
    }

    /**
     * Get the HTTP method for updating a product.
     *
     * @return string The HTTP method (PUT)
     */
    public function method(): string
    {
        return HttpMethod::PUT->value;
    }

    /**
     * Get the request body containing product data.
     *
     * @return array<string, mixed> The product data as an array
     */
    public function body(): array
    {
        return $this->product->toArray();
    }
}
