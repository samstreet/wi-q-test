# Implementation Plan: Great Food Ltd API Client Library

## Overview
This document outlines the phased implementation plan for creating a REST API client library to interact with the Great Food Ltd API. The implementation is designed to be completed within 1-2 hours while maintaining code quality and testability.

## Requirements Summary

### Functional Requirements
1. Create a reusable REST API client library (framework-agnostic)
2. Implement OAuth2 client credentials authentication flow
3. Fetch and display menu products in a table format (Scenario 1)
4. Update product information via API (Scenario 2)
5. Include comprehensive tests

### Non-Functional Requirements
- Clean, maintainable code architecture
- Proper separation of concerns
- Error handling for API failures
- Should work in real-world scenarios
- Time constraint: 1-2 hours maximum

## Technology Stack Recommendations

### Core Dependencies
- **HTTP Client**: Guzzle (industry standard for PHP HTTP requests)
- **Testing**: PHPUnit (standard for PHP testing)
- **Autoloading**: Composer PSR-4 autoloading

### Rationale
- Guzzle provides robust HTTP client functionality with built-in support for headers, authentication, and error handling
- PHPUnit is the de facto standard for PHP testing
- Composer enables proper dependency management and autoloading

---

## Design Principles

### Fully Agnostic Library Architecture

**CRITICAL**: The library (src/) is 100% agnostic with ZERO API-specific code.

**Library Components** (`src/` - Generic, Reusable):
- ✅ `Connector` - Abstract base class for any API connector
- ✅ `Request` - Abstract base class for any API request
- ✅ `HttpMethod` enum - Universal HTTP methods
- ✅ Generic HTTP communication logic
- ✅ Zero hardcoded URLs, endpoints, or API logic

**Great Food Implementation** (`tests/Fixtures/` - API-Specific):
- ❌ `GreatFoodConnector` - extends `Connector`, adds auth & Great Food base URL
- ❌ Request classes - extend `Request`, define Great Food endpoints
- ❌ `MenuService` - Great Food business logic
- ❌ `Menu` and `Product` models - Great Food data structures

**Why This Matters:**
- ✅ `src/` can be published as a standalone Composer package
- ✅ Any developer can use this library for ANY REST API
- ✅ Great Food implementation serves as example usage
- ✅ Tech test demonstrates library in action without polluting library code
- ✅ Clear separation: library vs. implementation

**Usage Pattern:**
```php
// Anyone using the library creates their own connector
class MyApiConnector extends ApiClient\Client\Connector
{
    protected function resolveBaseUrl(): string
    {
        return 'https://my-api.com';
    }
}

// And their own request classes
class GetUsersRequest extends ApiClient\Requests\Request
{
    public function resolveEndpoint(): string
    {
        return '/users';
    }

    public function method(): string
    {
        return HttpMethod::GET->value;
    }
}
```

### Modern PHP Practices (PHP 8.4)

- ✅ Leverage enums for type-safe constants
- ✅ Constructor property promotion
- ✅ Strict typing (`declare(strict_types=1)`)
- ✅ Readonly properties where appropriate
- ✅ Named arguments for clarity
- ✅ Short closure syntax
- ✅ Null-safe operator where appropriate

### Docker-First Development

- ✅ PHP 8.4 on Alpine Linux (minimal footprint)
- ✅ Reproducible development environment
- ✅ No local PHP installation required
- ✅ Easy CI/CD integration

---

## Phase 1: Project Foundation (15-20 minutes)

### Objectives
- Set up project structure
- Configure Composer dependencies
- Establish autoloading and namespacing

### Tasks
1. **Create project structure**
   ```
   src/                           # Generic, reusable library (API-agnostic)
   ├── Client/
   │   └── Connector.php          # Abstract base connector
   ├── Requests/
   │   └── Request.php            # Abstract base request
   └── Http/
       └── HttpMethod.php         # HTTP method enum (PHP 8.1+)

   tests/                         # Great Food Ltd specific implementation & tests
   ├── Fixtures/                  # Great Food API implementation
   │   ├── GreatFoodConnector.php # Concrete connector for Great Food API
   │   ├── Requests/
   │   │   ├── GetTokenRequest.php
   │   │   ├── GetMenusRequest.php
   │   │   ├── GetMenuProductsRequest.php
   │   │   └── UpdateProductRequest.php
   │   ├── Services/
   │   │   └── MenuService.php
   │   └── Models/
   │       ├── Menu.php
   │       └── Product.php
   ├── Unit/
   │   ├── Client/
   │   │   └── ConnectorTest.php  # Test abstract connector
   │   └── Requests/
   │       └── RequestTest.php    # Test abstract request
   └── Integration/
       ├── Scenario1Test.php      # Validate Scenario 1 requirements
       └── Scenario2Test.php      # Validate Scenario 2 requirements

   examples/
   ├── scenario1.php              # Demonstrate Scenario 1
   └── scenario2.php              # Demonstrate Scenario 2
   ```

2. **Create Docker Environment**

   **Dockerfile** (PHP 8.4 Alpine):
   ```dockerfile
   FROM php:8.4-cli-alpine

   # Install system dependencies
   RUN apk add --no-cache \
       curl \
       git \
       zip \
       unzip

   # Install PHP extensions
   RUN docker-php-ext-install \
       curl \
       mbstring

   # Install Composer
   COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

   WORKDIR /app

   # Copy composer files first for layer caching
   COPY composer.json composer.lock ./
   RUN composer install --no-scripts --no-autoloader

   # Copy application code
   COPY . .
   RUN composer dump-autoload --optimize
   ```

   **docker-compose.yml**:
   ```yaml
   version: '3.8'
   services:
     app:
       build: .
       volumes:
         - .:/app
       working_dir: /app
       command: tail -f /dev/null  # Keep container running

     test:
       build: .
       volumes:
         - .:/app
       working_dir: /app
       command: vendor/bin/phpunit
   ```

   **.dockerignore**:
   ```
   vendor/
   .git/
   .gitignore
   *.md
   tests/
   .phpunit.cache/
   ```

3. **Initialize Composer**
   - Create `composer.json` with dependencies:
     - guzzlehttp/guzzle: ^7.0
     - phpunit/phpunit: ^11.0 (dev) - for PHP 8.4 support
   - Require PHP 8.4+ in composer.json
   - Set up PSR-4 autoloading:
     - `ApiClient\` namespace for generic library (src/)
     - `Tests\Fixtures\` for Great Food implementation (tests/Fixtures/)
     - `Tests\` for test classes
   - Run `composer install`

4. **Create base configuration**
   - No hardcoded configuration in library
   - Configuration handled by concrete implementations (connectors)

### Deliverables
- ✅ Docker environment with PHP 8.4 Alpine
- ✅ Working Composer setup
- ✅ Directory structure
- ✅ Autoloading configured

---

## Phase 2: Generic Library Core (20-25 minutes)

### Objectives
- Create abstract, reusable library components (API-agnostic)
- Implement Saloon-like Request pattern
- Create HTTP method enum for type safety
- Implement abstract Connector base class

### Tasks

1. **Create HTTP Method Enum** (`src/Http/HttpMethod.php`)
   ```php
   namespace ApiClient\Http;

   enum HttpMethod: string
   {
       case GET = 'GET';
       case POST = 'POST';
       case PUT = 'PUT';
       case PATCH = 'PATCH';
       case DELETE = 'DELETE';
   }
   ```

2. **Create Abstract Request Class** (`src/Requests/Request.php`)
   ```php
   namespace ApiClient\Requests;

   abstract class Request
   {
       abstract public function resolveEndpoint(): string;
       abstract public function method(): string;

       public function headers(): array
       {
           return [];
       }

       public function body(): array
       {
           return [];
       }
   }
   ```
   - Generic base that any API can extend
   - No API-specific logic

3. **Create Abstract Connector Class** (`src/Client/Connector.php`)
   ```php
   namespace ApiClient\Client;

   abstract class Connector
   {
       protected \GuzzleHttp\Client $httpClient;

       public function __construct()
       {
           $this->httpClient = new \GuzzleHttp\Client();
       }

       abstract protected function resolveBaseUrl(): string;
       abstract protected function defaultHeaders(): array;

       public function send(Request $request): array
       {
           // Generic request sending logic
           // Build URL: baseUrl + request->resolveEndpoint()
           // Merge headers: defaultHeaders() + request->headers()
           // Send via Guzzle
           // Parse and return JSON
       }
   }
   ```
   - Handles HTTP communication generically
   - Concrete implementations provide base URL and default headers
   - No authentication logic (that's API-specific)

4. **Key Implementation Details**
   - **IMPORTANT**: Use `HttpMethod` enum constants, never hardcoded strings
   - Use PHP 8.4 features (constructor property promotion, enums, typed properties)
   - Strict types (`declare(strict_types=1)`)
   - Generic error handling (throw exceptions for HTTP errors)
   - Zero API-specific code in src/

5. **Unit Tests** (`tests/Unit/`)
   - Test abstract Request class with a mock implementation
   - Test abstract Connector class with a mock implementation
   - Verify generic functionality works correctly

### Deliverables
- ✅ `HttpMethod` enum with all HTTP methods
- ✅ Abstract `Request` class (fully generic)
- ✅ Abstract `Connector` class (fully generic)
- ✅ Unit tests for abstract components
- ✅ Zero API-specific code in library

---

## Phase 3: Great Food Ltd Implementation (15-20 minutes)

### Objectives
- Create Great Food API specific implementation using the generic library
- Implement all components in tests/Fixtures/ directory
- Keep library (src/) completely agnostic

### Tasks

1. **Create GreatFoodConnector** (`tests/Fixtures/GreatFoodConnector.php`)
   ```php
   namespace Tests\Fixtures;

   use ApiClient\Client\Connector;

   class GreatFoodConnector extends Connector
   {
       private ?string $bearerToken = null;

       public function __construct(
           private readonly string $baseUrl,
           private readonly string $clientId,
           private readonly string $clientSecret
       ) {
           parent::__construct();
       }

       protected function resolveBaseUrl(): string
       {
           return $this->baseUrl;
       }

       protected function defaultHeaders(): array
       {
           $headers = ['Accept' => 'application/json'];
           if ($this->bearerToken) {
               $headers['Authorization'] = "Bearer {$this->bearerToken}";
           }
           return $headers;
       }

       public function authenticate(): void
       {
           // Use GetTokenRequest to authenticate
           // Store token in $this->bearerToken
       }
   }
   ```

2. **Create Great Food Request Classes** (`tests/Fixtures/Requests/`)
   - `GetTokenRequest` - Extends `ApiClient\Requests\Request`
   - `GetMenusRequest` - Extends `ApiClient\Requests\Request`
   - `GetMenuProductsRequest` - Extends `ApiClient\Requests\Request`
   - `UpdateProductRequest` - Extends `ApiClient\Requests\Request`
   - Each implements specific Great Food API endpoints

3. **Create Model Classes** (`tests/Fixtures/Models/`)
   - `Menu.php` - Properties: `id`, `name`, with `fromArray()` factory
   - `Product.php` - Properties: `id`, `name`, with `fromArray()` and `toArray()`

4. **Create MenuService** (`tests/Fixtures/Services/MenuService.php`)
   - Constructor accepts `GreatFoodConnector`
   - Implements business logic for scenarios
   - Uses the Request classes to interact with API

5. **Integration Tests** (`tests/Integration/`)
   - Mock HTTP responses using sample JSON files
   - Test the complete Great Food implementation
   - Verify scenarios work correctly

### Deliverables
- ✅ `GreatFoodConnector` extending abstract `Connector`
- ✅ All Great Food specific Request classes
- ✅ Model classes with factories
- ✅ `MenuService` with all required methods
- ✅ Integration tests for Great Food implementation
- ✅ Library (src/) remains 100% agnostic

---

## Phase 4: Test Scenarios (10-15 minutes)

### Objectives
- Create tests that validate the tech test requirements
- Implement example scripts that demonstrate the library
- Use tests/Fixtures/ implementations

### Tasks

1. **Scenario Test 1** (`tests/Integration/Scenario1Test.php`)
   - Test that verifies Scenario 1 requirements
   - Mock HTTP responses using `tests/Fixtures/stubs/menus.json` and `tests/Fixtures/stubs/menu-products.json`
   - Assert correct products are fetched and formatted
   - Validates the "Takeaway" menu products display correctly

2. **Scenario Test 2** (`tests/Integration/Scenario2Test.php`)
   - Test that verifies Scenario 2 requirements
   - Mock HTTP response for product update
   - Assert update request is sent correctly
   - Validates product 84 in menu 7 update succeeds

3. **Example Script 1** (`examples/scenario1.php`)
   ```php
   require_once __DIR__ . '/../vendor/autoload.php';

   use Tests\Fixtures\GreatFoodConnector;
   use Tests\Fixtures\Services\MenuService;

   $connector = new GreatFoodConnector(
       'https://api.greatfood.example.com',
       '1337',
       '4j3g4gj304gj3'
   );

   $menuService = new MenuService($connector);

   // Fetch and display Takeaway products
   ```

4. **Example Script 2** (`examples/scenario2.php`)
   - Similar structure using the Great Food fixtures
   - Demonstrates product update

5. **Output Formatting**
   - Create helper function for table display in examples
   - Clear success/error messages

### Deliverables
- ✅ `Scenario1Test.php` - validates tech test requirement
- ✅ `Scenario2Test.php` - validates tech test requirement
- ✅ Working `scenario1.php` example
- ✅ Working `scenario2.php` example
- ✅ All tests pass

---

## Phase 5: Testing & Documentation (10-15 minutes)

### Objectives
- Ensure all tests pass
- Document usage and design decisions
- Add README for the library

### Tasks

1. **Run Full Test Suite**
   - Execute `composer test` or `vendor/bin/phpunit`
   - Ensure all tests pass
   - Check code coverage if time permits

2. **Create Library README** (optional but recommended)
   - Installation instructions
   - Quick start guide
   - API documentation
   - Design decisions

3. **Code Review**
   - Check for PSR-12 compliance
   - Ensure proper error handling
   - Verify separation of concerns

4. **Final Testing**
   - Run scenario examples to verify they work
   - Test with provided sample JSON responses

### Deliverables
- ✅ All tests passing
- ✅ Documentation complete
- ✅ Scenarios executable and producing correct output

---

## Design Decisions & Rationale

### 1. Three-Layer Architecture
- **Client Layer**: Raw HTTP communication, authentication
- **Service Layer**: Business logic, orchestration
- **Model Layer**: Data representation and transformation

**Rationale**: Clear separation of concerns makes code testable, maintainable, and reusable.

### 2. Dependency Injection
The `MenuService` accepts an `ApiClient` in its constructor rather than creating one internally.

**Rationale**: Enables easy testing with mocked clients and follows SOLID principles.

### 3. Guzzle HTTP Client
Using Guzzle instead of native PHP functions like `curl` or `file_get_contents`.

**Rationale**:
- Industry standard with excellent documentation
- Built-in support for middleware, retries, and error handling
- Easy to mock for testing
- PSR-7/PSR-18 compliant

### 4. Models with Factory Methods
Using static `fromArray()` methods instead of constructor complexity.

**Rationale**: Clean separation between object creation and initialization, easier testing.

### 5. Token Management in ApiClient
The client handles token storage and automatic injection.

**Rationale**: Reduces boilerplate in service layer and ensures consistent authentication.

---

## Time Allocation Summary

| Phase | Time | Focus |
|-------|------|-------|
| Phase 1: Foundation | 15-20 min | Setup, structure, dependencies |
| Phase 2: API Client | 20-25 min | Core HTTP client, authentication, tests |
| Phase 3: Business Logic | 15-20 min | Services, models, integration tests |
| Phase 4: Scenarios | 10-15 min | Example implementations |
| Phase 5: Testing & Docs | 10-15 min | Final verification, documentation |
| **Total** | **70-95 min** | **Within 1-2 hour constraint** |

---

## Testing Strategy

### Unit Tests
- Test individual components in isolation
- Mock external dependencies (HTTP responses)
- Fast execution, no real API calls

### Integration Tests
- Test component interactions
- Mock at the HTTP layer only
- Verify data flow through the system

### Example Tests
- The scenario examples serve as functional tests
- Demonstrate real-world usage
- Should be runnable and produce expected output

---

## Success Criteria

### Functional Success
- ✅ Scenario 1 produces correct table output
- ✅ Scenario 2 successfully makes update request
- ✅ All tests pass
- ✅ Library works with provided sample JSON responses

### Code Quality Success
- ✅ Clean, readable code
- ✅ Proper separation of concerns
- ✅ Comprehensive error handling
- ✅ Well-documented public APIs
- ✅ PSR-4 autoloading
- ✅ Testable architecture

### Interview Readiness
- ✅ Can explain design decisions clearly
- ✅ Can discuss trade-offs made
- ✅ Can demonstrate how it would work in production
- ✅ Can discuss what would be added with more time

---

## Future Enhancements (Out of Scope)

If this were a production system, consider adding:

1. **Caching**: Token caching to reduce authentication calls
2. **Rate Limiting**: Respect API rate limits
3. **Retry Logic**: Automatic retry with exponential backoff
4. **Logging**: PSR-3 compatible logging
5. **Validation**: Input validation with custom validator
6. **Pagination**: Handle paginated API responses
7. **Async Requests**: Parallel requests for better performance
8. **Configuration Management**: .env file support with vlucas/phpdotenv
9. **Response Caching**: Cache GET responses
10. **Comprehensive Error Types**: Specific exceptions for different failure modes

---

## Questions for Clarification

Before implementation begins, consider clarifying:

1. **Base URL**: What is the actual base URL for the Great Food Ltd API? (For now, can use `https://api.greatfood.example.com` or similar)

2. **Error Handling**: What should happen if:
   - The "Takeaway" menu doesn't exist?
   - Authentication fails?
   - A product update fails?

3. **Output Format**: Should scenario outputs be:
   - Plain text tables (simple echo)?
   - JSON output?
   - CLI formatted output?

4. **Testing Approach**: Should tests:
   - Use the provided JSON files as fixtures?
   - Mock all HTTP responses?
   - Be fully isolated unit tests?

5. **PHP Version**: What PHP version should be targeted? (Recommend PHP 7.4+ or 8.0+)

**Note**: For the purposes of this test, reasonable assumptions can be made for any of these questions, as long as they're documented in the code or interview discussion.
