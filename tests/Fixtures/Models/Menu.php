<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use InvalidArgumentException;

/**
 * Menu model for Great Food Ltd API.
 *
 * Represents a menu with its basic properties. This model uses
 * readonly properties to ensure immutability.
 */
class Menu
{
    /**
     * Initialize a menu instance.
     *
     * @param int $id The unique identifier for the menu
     * @param string $name The name of the menu
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {
    }

    /**
     * Create a Menu from API response data.
     *
     * @param array<string, mixed> $data The API response data
     * @return self A new Menu instance
     * @throws InvalidArgumentException If required fields are missing
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['id']) || !is_numeric($data['id'])) {
            throw new InvalidArgumentException('Menu data must contain numeric id');
        }

        if (!isset($data['name']) || !is_string($data['name'])) {
            throw new InvalidArgumentException('Menu data must contain string name');
        }

        return new self(
            id: (int) $data['id'],
            name: $data['name'],
        );
    }
}
