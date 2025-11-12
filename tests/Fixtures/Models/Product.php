<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use InvalidArgumentException;

/**
 * Product model for Great Food Ltd API.
 *
 * Represents a product with its basic properties. This model uses
 * readonly properties to ensure immutability and provides methods
 * for conversion to/from array format for API communication.
 */
class Product
{
    /**
     * Initialize a product instance.
     *
     * @param int $id The unique identifier for the product
     * @param string $name The name of the product
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {
    }

    /**
     * Create a Product from API response data.
     *
     * @param array<string, mixed> $data The API response data
     * @return self A new Product instance
     * @throws InvalidArgumentException If required fields are missing
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['id']) || !is_numeric($data['id'])) {
            throw new InvalidArgumentException('Product data must contain numeric id');
        }

        if (!isset($data['name']) || !is_string($data['name'])) {
            throw new InvalidArgumentException('Product data must contain string name');
        }

        return new self(
            id: (int) $data['id'],
            name: $data['name'],
        );
    }

    /**
     * Convert Product to array for API requests.
     *
     * @return array<string, mixed> The product data as an associative array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
