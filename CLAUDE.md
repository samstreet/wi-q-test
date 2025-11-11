# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP backend developer test repository for wi-Q. The goal is to create a library for consuming REST APIs, specifically to interact with a fictitious "Great Food Ltd" API for menu and product management.

## Key Requirements

1. **No Framework Preference**: No specific framework required, but candidates can use one with justification
2. **External Packages**: Use of third-party packages is encouraged where appropriate
3. **Testing**: Tests are highly desired to prove the solution works and is robust
4. **Time Constraint**: This is a 1-2 hour test, so keep solutions focused and practical

## Test Scenarios

### Scenario 1: Fetching and Displaying Menu Products
The library must:
- Authenticate with the API using OAuth2 client credentials flow
- Fetch all menus from `/menus` endpoint
- Find the menu named "Takeaway" by its ID
- Retrieve products for that menu from `/menu/{menu_id}/products`
- Output product data as a table with ID and Name columns

### Scenario 2: Updating Product Information
The library must:
- Update a specific product using `PUT /menu/{menu_id}/product/{product_id}`
- Demonstrate successful API request (e.g., fixing product 84 in menu 7 from "Chpis" to "Chips")

## API Structure

### Authentication
- **Endpoint**: `/auth_token`
- **Method**: POST
- **Content-Type**: `application/x-www-form-urlencoded`
- **Credentials**:
  - `client_id`: 1337
  - `client_secret`: 4j3g4gj304gj3
  - `grant_type`: client_credentials
- **Response**: Bearer token with expiration (see `responses/token.json`)

### Available Endpoints
- `GET /menus` - List all menus (requires Bearer token)
- `GET /menu/{menu_id}/products` - List products for a menu (requires Bearer token)
- `PUT /menu/{menu_id}/product/{product_id}` - Update a product (requires Bearer token)

## Sample Data

Sample API responses are provided in the `responses/` directory:
- `responses/token.json` - Sample authentication response
- `responses/menus.json` - Sample menus list (menu ID 3 is "Takeaway")
- `responses/menu-products.json` - Sample products for the Takeaway menu

## Technical Stack

- **PHP Version**: 8.4 (target the latest features)
- **Runtime**: Docker with Alpine Linux base
- **HTTP Client**: Guzzle (guzzlehttp/guzzle)
- **Testing**: PHPUnit 11.0+
- **Package Manager**: Composer

## Architecture Pattern: Saloon-Like Request Objects

The library uses a **Saloon-inspired architecture** with Request objects:

### Core Components

**CRITICAL**: The library (`src/`) is 100% agnostic with ZERO API-specific code.

#### Generic Library Components (`src/ApiClient/`)

1. **Abstract Connector** (`src/ApiClient/Client/Connector.php`)
   - Base class for all API connectors
   - Implements generic HTTP sending logic via Guzzle
   - Abstract methods: `resolveBaseUrl()`, `defaultHeaders()`
   - `send(Request $request): array` - generic request sending

2. **Abstract Request** (`src/ApiClient/Requests/Request.php`)
   - Base class for all API requests
   - Abstract methods: `resolveEndpoint()`, `method()`
   - Optional methods: `headers()`, `body()`
   - Defines the contract for any API endpoint

3. **HttpMethod Enum** (`src/ApiClient/Http/HttpMethod.php`)
   - Type-safe HTTP method constants (GET, POST, PUT, PATCH, DELETE)
   - Used instead of hardcoded strings

#### Great Food Implementation (`tests/Fixtures/`)

4. **GreatFoodConnector** (`tests/Fixtures/GreatFoodConnector.php`)
   - Extends `ApiClient\Client\Connector`
   - Provides Great Food base URL
   - Implements OAuth2 authentication
   - Manages bearer token injection

5. **Request Classes** (`tests/Fixtures/Requests/`)
   - `GetTokenRequest`, `GetMenusRequest`, `GetMenuProductsRequest`, `UpdateProductRequest`
   - Each extends `ApiClient\Requests\Request`
   - Defines specific Great Food API endpoints

6. **Service Layer** (`tests/Fixtures/Services/MenuService.php`)
   - Great Food business logic
   - Uses GreatFoodConnector and Request classes
   - Transforms API responses into Model objects

7. **Model Layer** (`tests/Fixtures/Models/`)
   - `Menu`, `Product` - Great Food data models
   - Factory methods (`fromArray()`) and serialization (`toArray()`)

### Agnostic Design Principle

**CRITICAL SEPARATION**:
- `src/` = 100% generic, reusable for ANY REST API
- `tests/Fixtures/` = Great Food Ltd specific implementation
- `tests/Integration/` = Validates tech test requirements
- `examples/` = Demonstrates library usage

This allows `src/` to be published as a standalone Composer package that anyone can use for any REST API.

## Coding Standards

### HTTP Constants Rule

**CRITICAL**: Always use HTTP method constants, never hardcoded strings.

```php
// ❌ BAD
public function method(): string
{
    return 'GET';
}

// ✅ GOOD
public function method(): string
{
    return HttpMethod::GET->value;
}
```

Create an `HttpMethod` enum (PHP 8.1+):
```php
enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
}
```

### Modern PHP 8.4 Features

- Use strict types: `declare(strict_types=1)`
- Constructor property promotion
- Typed properties
- Enums for constants
- Readonly properties where appropriate
- Short closure syntax: `fn($x) => $x * 2`

## Docker Commands

### Development
```bash
# Build and start containers
docker-compose up -d

# Run tests
docker-compose run test

# Enter app container
docker exec -it <container_name> sh

# Run scenario examples
docker-compose run app php examples/scenario1.php
docker-compose run app php examples/scenario2.php

# Run Composer commands
docker-compose run app composer install
docker-compose run app composer test
```

## Testing Approach

### Unit Tests (`tests/Unit/`)
Test the generic library components:
- Abstract `Connector` class with mock implementations
- Abstract `Request` class with mock implementations
- Verify generic HTTP sending logic works
- No API-specific tests in this layer

### Integration Tests (`tests/Integration/`)
Validate Great Food implementation and tech test requirements:
- `Scenario1Test.php` - Validates Scenario 1 (fetch and display Takeaway products)
- `Scenario2Test.php` - Validates Scenario 2 (update product name)
- Mock HTTP responses using sample JSON files from `responses/`
- Test GreatFoodConnector, Request classes, MenuService, Models together
- Verify authentication flow with OAuth2
- Test data transformation (API responses → Models)
- Error scenarios (invalid responses, missing data, auth failures)

## Documentation

Refer to the `docs/` directory for detailed documentation:
- `docs/IMPLEMENTATION_PLAN.md` - Phased implementation guide
- `docs/REQUIREMENTS.md` - Detailed requirements specification
- `docs/ARCHITECTURE.md` - Complete architecture documentation

## Disclosure
The README mentions that AI development tools are permitted but must be disclosed during the next interview stage.
