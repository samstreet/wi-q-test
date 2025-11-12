<?php

declare(strict_types=1);

namespace Tests\Fixtures\Services;

use Tests\Fixtures\GreatFoodConnector;
use Tests\Fixtures\Models\Menu;
use Tests\Fixtures\Models\Product;
use Tests\Fixtures\Requests\GetMenuProductsRequest;
use Tests\Fixtures\Requests\GetMenusRequest;
use Tests\Fixtures\Requests\UpdateProductRequest;

/**
 * Service for menu and product operations with Great Food Ltd API.
 *
 * This service provides high-level business logic for interacting with
 * menus and products. It handles authentication and data transformation.
 */
class MenuService
{
    /**
     * Initialize the menu service.
     *
     * @param GreatFoodConnector $connector The API connector to use
     */
    public function __construct(
        private readonly GreatFoodConnector $connector,
    ) {
    }

    /**
     * Get all menus from the API.
     *
     * This method ensures the connector is authenticated before making
     * the request and transforms the response data into Menu objects.
     *
     * @return array<int, Menu> Array of Menu objects
     */
    public function getMenus(): array
    {
        // Ensure authenticated
        if (!$this->connector->isAuthenticated()) {
            $this->connector->authenticate();
        }

        $request = new GetMenusRequest();
        $response = $this->connector->send($request);

        if (!isset($response['data']) || !is_array($response['data'])) {
            return [];
        }

        $menus = [];
        foreach ($response['data'] as $menuData) {
            if (!is_array($menuData) || array_is_list($menuData)) {
                continue;
            }
            /** @var array<string, mixed> $menuData */
            $menus[] = Menu::fromArray($menuData);
        }

        return $menus;
    }

    /**
     * Find a menu by name.
     *
     * This method searches through all menus to find one with a matching name.
     *
     * @param string $name The name of the menu to find
     * @return Menu|null The found menu or null if not found
     */
    public function findMenuByName(string $name): ?Menu
    {
        $menus = $this->getMenus();

        foreach ($menus as $menu) {
            if ($menu->name === $name) {
                return $menu;
            }
        }

        return null;
    }

    /**
     * Get products for a specific menu.
     *
     * This method ensures the connector is authenticated before making
     * the request and transforms the response data into Product objects.
     *
     * @param int $menuId The ID of the menu to fetch products for
     * @return array<int, Product> Array of Product objects
     */
    public function getMenuProducts(int $menuId): array
    {
        // Ensure authenticated
        if (!$this->connector->isAuthenticated()) {
            $this->connector->authenticate();
        }

        $request = new GetMenuProductsRequest($menuId);
        $response = $this->connector->send($request);

        if (!isset($response['data']) || !is_array($response['data'])) {
            return [];
        }

        $products = [];
        foreach ($response['data'] as $productData) {
            if (!is_array($productData) || array_is_list($productData)) {
                continue;
            }
            /** @var array<string, mixed> $productData */
            $products[] = Product::fromArray($productData);
        }

        return $products;
    }

    /**
     * Update a product.
     *
     * This method ensures the connector is authenticated before making
     * the update request.
     *
     * @param int $menuId The ID of the menu containing the product
     * @param int $productId The ID of the product to update
     * @param Product $product The product data to update
     * @return bool True if update was successful
     */
    public function updateProduct(int $menuId, int $productId, Product $product): bool
    {
        // Ensure authenticated
        if (!$this->connector->isAuthenticated()) {
            $this->connector->authenticate();
        }

        $request = new UpdateProductRequest($menuId, $productId, $product);
        $this->connector->send($request);

        return true;
    }
}
