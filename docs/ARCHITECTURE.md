# Architecture Overview: Great Food Ltd API Client

## System Architecture

This document describes the technical architecture for the **generic REST API client library** and the **Great Food Ltd implementation** used for the tech test.

**KEY PRINCIPLE**: The library (`src/`) is 100% agnostic. All Great Food specific code lives in `tests/Fixtures/`.

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│         EXAMPLES / TESTS (Great Food Implementation)        │
│─────────────────────────────────────────────────────────────│
│  Examples Layer                                             │
│  ┌──────────────────┐         ┌──────────────────┐         │
│  │  scenario1.php   │         │  scenario2.php   │         │
│  └────────┬─────────┘         └────────┬─────────┘         │
│           │                              │                  │
│           └──────────────┬───────────────┘                  │
│                          │                                  │
│  ┌───────────────────────▼──────────────────────────┐      │
│  │  tests/Fixtures/Services/MenuService             │      │
│  │  (Great Food business logic)                     │      │
│  └───────────────────────┬──────────────────────────┘      │
│                          │                                  │
│  ┌──────────────┬────────▼──────────┬──────────────┐       │
│  │ Menu model   │  Product model    │ Other models │       │
│  └──────────────┴───────────────────┴──────────────┘       │
│                          │                                  │
│  ┌───────────────────────▼──────────────────────────┐      │
│  │  tests/Fixtures/Requests/                        │      │
│  │  GetMenusRequest | GetProductsRequest            │      │
│  │  GetTokenRequest | UpdateProductRequest          │      │
│  │  (Great Food API endpoints)                      │      │
│  └───────────────────────┬──────────────────────────┘      │
│                          │                                  │
│  ┌───────────────────────▼──────────────────────────┐      │
│  │  tests/Fixtures/GreatFoodConnector               │      │
│  │  (extends abstract Connector)                    │      │
│  │  - Great Food base URL                           │      │
│  │  - OAuth2 authentication logic                   │      │
│  │  - Bearer token management                       │      │
│  └───────────────────────┬──────────────────────────┘      │
└────────────────────────────┼─────────────────────────────────┘
                             │
                             │ extends / implements
                             │
┌────────────────────────────▼─────────────────────────────────┐
│         GENERIC LIBRARY (src/ - 100% Agnostic)               │
│──────────────────────────────────────────────────────────────│
│  ┌────────────────────────────────────────────────────┐     │
│  │  src/Client/Connector (abstract)                   │     │
│  │  - send(Request): array                            │     │
│  │  - abstract resolveBaseUrl(): string               │     │
│  │  - abstract defaultHeaders(): array                │     │
│  └─────────────────────┬──────────────────────────────┘     │
│                        │                                     │
│  ┌─────────────────────▼──────────────────────────────┐     │
│  │  src/Requests/Request (abstract)                   │     │
│  │  - abstract resolveEndpoint(): string              │     │
│  │  - abstract method(): string                       │     │
│  │  - headers(): array                                │     │
│  │  - body(): array                                   │     │
│  └────────────────────────────────────────────────────┘     │
│                                                              │
│  ┌────────────────────────────────────────────────────┐     │
│  │  src/Http/HttpMethod (enum)                        │     │
│  │  GET | POST | PUT | PATCH | DELETE                 │     │
│  └────────────────────────────────────────────────────┘     │
│                                                              │
│  ┌────────────────────────────────────────────────────┐     │
│  │         Guzzle HTTP Client                         │     │
│  └────────────────────────────────────────────────────┘     │
└──────────────────────────────────────────────────────────────┘
```

---

## Layer Responsibilities

## GENERIC LIBRARY LAYERS (src/)

### 1. Abstract Connector (`src/Client/Connector.php`)
**Purpose**: Generic HTTP communication base class

**Responsibilities**:
- Send HTTP requests via Guzzle
- Build full URLs from base URL + endpoint
- Merge default headers with request-specific headers
- Parse JSON responses
- Handle HTTP errors generically

**Key Methods**:
```php
abstract class Connector
{
    abstract protected function resolveBaseUrl(): string;
    abstract protected function defaultHeaders(): array;

    public function send(Request $request): array
    {
        // Generic HTTP sending logic
    }
}
```

**Dependencies**: Guzzle, Request

---

### 2. Abstract Request (`src/Requests/Request.php`)
**Purpose**: Define the contract for all API requests

**Responsibilities**:
- Define endpoint path
- Define HTTP method
- Optionally define headers
- Optionally define body

**Key Methods**:
```php
abstract class Request
{
    abstract public function resolveEndpoint(): string;
    abstract public function method(): string;

    public function headers(): array { return []; }
    public function body(): array { return []; }
}
```

**Dependencies**: None (pure abstraction)

---

### 3. HttpMethod Enum (`src/Http/HttpMethod.php`)
**Purpose**: Type-safe HTTP method constants

**Values**: GET, POST, PUT, PATCH, DELETE

**Dependencies**: None

---

## GREAT FOOD IMPLEMENTATION LAYERS (tests/Fixtures/)

### 4. GreatFoodConnector (`tests/Fixtures/GreatFoodConnector.php`)
**Purpose**: Concrete connector for Great Food API

**Responsibilities**:
- Provide Great Food base URL
- Manage OAuth2 authentication
- Inject bearer token into requests
- Provide API-specific default headers

**Key Methods**:
```php
class GreatFoodConnector extends Connector
{
    protected function resolveBaseUrl(): string
    {
        return $this->baseUrl;
    }

    protected function defaultHeaders(): array
    {
        // Include Authorization header if authenticated
    }

    public function authenticate(): void
    {
        // OAuth2 client credentials flow
    }
}
```

**Dependencies**: Abstract Connector, GetTokenRequest

---

### 5. Request Layer (`tests/Fixtures/Requests/`)
**Purpose**: Define individual Great Food API endpoint requests

**Components**:
- `GetTokenRequest`: OAuth2 authentication request
- `GetMenusRequest`: Fetch all menus
- `GetMenuProductsRequest`: Fetch products for a specific menu
- `UpdateProductRequest`: Update a product's information

**Responsibilities**:
- Define HTTP method for each endpoint
- Specify endpoint path with parameters
- Configure request-specific headers
- Define request body structure
- Validate request data
- Transform responses into arrays

**Example Implementation**:
```php
class GetMenusRequest extends ApiClient\Requests\Request
{
    public function resolveEndpoint(): string
    {
        return '/menus';
    }

    public function method(): string
    {
        return HttpMethod::GET->value;
    }
}
```

**Dependencies**: Abstract Request, HttpMethod, Models

---

### 6. Model Layer (`tests/Fixtures/Models/`)
**Purpose**: Represent Great Food API data as strongly-typed objects

**Components**:
- `Menu`: Represents a menu entity
- `Product`: Represents a product entity

**Responsibilities**:
- Encapsulate data with clear types
- Provide factory methods for creating instances from API data
- Provide serialization methods for API requests

**Key Methods**:
```php
class Menu
{
    public int $id;
    public string $name;

    public static function fromArray(array $data): self
    {
        $menu = new self();
        $menu->id = $data['id'];
        $menu->name = $data['name'];
        return $menu;
    }
}

class Product
{
    public int $id;
    public string $name;

    public static function fromArray(array $data): self
    {
        $product = new self();
        $product->id = $data['id'];
        $product->name = $data['name'];
        return $product;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name
        ];
    }
}
```

**Dependencies**: None (pure data objects)

---

### 7. Service Layer (`tests/Fixtures/Services/`)
**Purpose**: Implement Great Food business logic

**Components**:
- `MenuService`: Provides high-level operations for menu and product management

**Responsibilities**:
- Orchestrate multiple API calls to achieve business goals
- Transform API responses into domain models
- Implement business rules (e.g., finding menu by name)
- Provide clean, intuitive API for consumers

**Key Methods**:
```php
class MenuService
{
    public function __construct(
        private GreatFoodConnector $connector
    ) {}

    public function getMenus(): array
    {
        $request = new GetMenusRequest();
        $response = $this->connector->send($request);
        return array_map(
            fn($data) => Menu::fromArray($data),
            $response['data']
        );
    }

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

    public function getMenuProducts(int $menuId): array
    {
        $request = new GetMenuProductsRequest($menuId);
        $response = $this->connector->send($request);
        return array_map(
            fn($data) => Product::fromArray($data),
            $response['data']
        );
    }

    public function updateProduct(
        int $menuId,
        int $productId,
        Product $product
    ): bool {
        $request = new UpdateProductRequest($menuId, $productId, $product);
        $this->connector->send($request);
        return true;
    }
}
```

**Dependencies**: GreatFoodConnector, Request classes, Models

---

### 8. Examples Layer (`examples/`)
**Purpose**: Demonstrate library usage through concrete scenarios

**Components**:
- `scenario1.php`: Fetches and displays Takeaway menu products
- `scenario2.php`: Updates a product's name

**Responsibilities**:
- Initialize and configure the GreatFoodConnector
- Orchestrate API calls for specific use cases
- Format and display output
- Handle user-facing errors

**Dependencies**: All Fixtures components

---

## Data Flow

### Scenario 1: Fetch Takeaway Products

```
scenario1.php
    │
    ├─> MenuService::getMenus()
    │       │
    │       └─> new GetMenusRequest()
    │       │
    │       └─> connector->send(request)
    │               │
    │               ├─> Auto authenticate if needed
    │               │   └─> new GetTokenRequest()
    │               │   └─> Store bearer token
    │               │
    │               ├─> Build URL: baseUrl + request->resolveEndpoint()
    │               ├─> Merge headers (default + request headers)
    │               │
    │               └─> Guzzle HTTP Request
    │                       │
    │                       └─> Returns JSON
    │               │
    │               └─> Parse JSON to array
    │       │
    │       └─> Transform array to Menu[] objects
    │
    ├─> MenuService::findMenuByName('Takeaway')
    │       │
    │       └─> Filter menus array by name
    │
    ├─> MenuService::getMenuProducts($menuId)
    │       │
    │       └─> new GetMenuProductsRequest($menuId)
    │       │
    │       └─> connector->send(request)
    │               │
    │               └─> Guzzle HTTP Request (token already set)
    │                       │
    │                       └─> Returns JSON
    │               │
    │               └─> Parse JSON to array
    │       │
    │       └─> Transform array to Product[] objects
    │
    └─> Display products as table
```

### Scenario 2: Update Product

```
scenario2.php
    │
    ├─> Create Product object
    │
    ├─> MenuService::updateProduct(7, 84, $product)
    │       │
    │       └─> new UpdateProductRequest(7, 84, $product)
    │       │       │
    │       │       └─> resolveEndpoint(): "/menu/7/product/84"
    │       │       └─> method(): "PUT"
    │       │       └─> body(): $product->toArray()
    │       │
    │       └─> connector->send(request)
    │               │
    │               ├─> Build full request with headers
    │               │
    │               └─> Guzzle HTTP Request
    │                       │
    │                       └─> Returns response
    │               │
    │               └─> Verify success (HTTP 200/204)
    │       │
    │       └─> Return true/false
    │
    └─> Display success message
```

---

## Authentication Flow

```
┌─────────────────┐
│   ApiClient     │
│  (constructed)  │
└────────┬────────┘
         │
         ├─> First API call triggers authenticate()
         │
         ├─> POST /auth_token
         │   with client credentials
         │
         ├─> Receive bearer token
         │
         ├─> Store token in instance variable
         │
         └─> All subsequent requests include:
             "Authorization: Bearer {token}"
```

**Token Management Strategy**:
- Token stored in ApiClient instance
- Automatically injected into all requests
- For this test, no expiration handling needed (token valid for 999999999 seconds)
- Production version would implement token refresh logic

---

## Error Handling Strategy

### Layered Error Handling

```
┌─────────────────────────────────────────────────────┐
│ Examples Layer                                      │
│ - Catch and display user-friendly messages         │
│ - Exit with appropriate codes                       │
└──────────────────┬──────────────────────────────────┘
                   │ Throws custom exceptions
┌──────────────────▼──────────────────────────────────┐
│ Service Layer                                       │
│ - Validate inputs                                   │
│ - Handle business logic errors                      │
│ - Throw ServiceException                            │
└──────────────────┬──────────────────────────────────┘
                   │ Throws ApiException
┌──────────────────▼──────────────────────────────────┐
│ Client Layer                                        │
│ - Handle HTTP errors (4xx, 5xx)                     │
│ - Parse error responses                             │
│ - Throw ApiException                                │
└──────────────────┬──────────────────────────────────┘
                   │ Throws GuzzleException
┌──────────────────▼──────────────────────────────────┐
│ Guzzle HTTP Client                                  │
│ - Network errors                                    │
│ - Connection timeouts                               │
└─────────────────────────────────────────────────────┘
```

### Exception Hierarchy

```
Exception (PHP base)
    │
    ├─> ApiException
    │   │
    │   ├─> AuthenticationException (401 errors)
    │   ├─> NotFoundException (404 errors)
    │   └─> ServerException (5xx errors)
    │
    └─> ServiceException
        │
        └─> MenuNotFoundException
```

---

## Design Patterns Applied

### 1. Facade Pattern
**Where**: `MenuService`
**Why**: Provides simplified interface to complex API operations

### 2. Factory Pattern
**Where**: Model classes (`fromArray()` methods)
**Why**: Encapsulates object creation logic

### 3. Dependency Injection
**Where**: `MenuService` constructor accepts `ApiClient`
**Why**: Enables testing with mock objects, follows SOLID principles

### 4. Single Responsibility Principle
**Where**: Each layer has one clear responsibility
**Why**: Maintainability, testability, clarity

---

## Testing Strategy

### Unit Tests

**Client Layer Tests** (`tests/Unit/Client/GreatFoodConnectorTest.php`):
```php
- testAuthentication()
- testSendRequest()
- testAuthenticationFailure()
- testHttpErrors()
- testJsonParsing()
- testAutomaticTokenInjection()
```

**Request Layer Tests** (`tests/Unit/Requests/*.php`):
```php
// GetMenusRequestTest.php
- testResolveEndpoint()
- testMethod()
- testHeaders()

// GetMenuProductsRequestTest.php
- testResolveEndpointWithMenuId()
- testMethod()

// UpdateProductRequestTest.php
- testResolveEndpointWithIds()
- testMethod()
- testBodyFromProduct()
```

**Mocking Strategy**:
- Use Guzzle's MockHandler to simulate HTTP responses
- No actual HTTP calls in unit tests
- Request objects can be tested without mocking

### Integration Tests

**Service Layer Tests** (`tests/Integration/MenuServiceTest.php`):
```php
- testGetMenus()
- testFindMenuByName()
- testFindMenuByNameNotFound()
- testGetMenuProducts()
- testUpdateProduct()
- testUpdateProductFailure()
```

**Mocking Strategy**:
- Mock `GreatFoodConnector` responses
- Test service logic and data transformation
- Verify correct Request objects are created

### Functional Tests

**Scenario Scripts**:
- The example scripts serve as functional tests
- Should produce expected output when run
- Can be executed manually or automated

---

## Configuration Management

### Approach 1: Direct Configuration (Simplest for test)
```php
$connector = new GreatFoodConnector(
    'https://api.greatfood.example.com',
    '1337',
    '4j3g4gj304gj3'
);

$menuService = new MenuService($connector);
```

### Approach 2: Config File (Better for production)
```php
// config.php
return [
    'api' => [
        'base_url' => 'https://api.greatfood.example.com',
        'client_id' => '1337',
        'client_secret' => '4j3g4gj304gj3',
    ]
];

// Usage
$config = require 'config.php';
$connector = new GreatFoodConnector(
    $config['api']['base_url'],
    $config['api']['client_id'],
    $config['api']['client_secret']
);
$menuService = new MenuService($connector);
```

**Recommendation**: Use Approach 1 for the test (simplicity), mention Approach 2 in interview discussion.

---

## Namespace Structure

### Generic Library (src/)
```
ApiClient\                        # Generic, reusable library
    │
    ├─> Client\
    │   └─> Connector (abstract)  # Base connector class
    │
    ├─> Requests\
    │   └─> Request (abstract)    # Base request class
    │
    └─> Http\
        └─> HttpMethod (enum)     # HTTP method constants
```

### Great Food Implementation (tests/Fixtures/)
```
Tests\Fixtures\                   # Great Food specific implementation
    │
    ├─> GreatFoodConnector        # Extends ApiClient\Client\Connector
    │
    ├─> Requests\
    │   ├─> GetTokenRequest       # Extends ApiClient\Requests\Request
    │   ├─> GetMenusRequest       # Extends ApiClient\Requests\Request
    │   ├─> GetMenuProductsRequest
    │   └─> UpdateProductRequest
    │
    ├─> Services\
    │   └─> MenuService           # Great Food business logic
    │
    └─> Models\
        ├─> Menu                  # Great Food data models
        └─> Product
```

---

## File System Structure

```
wi-q-test/
├── src/                                # GENERIC LIBRARY (API-agnostic)
│   └── ApiClient/
│       ├── Client/
│       │   └── Connector.php           # Abstract base connector
│       ├── Requests/
│       │   └── Request.php             # Abstract base request
│       └── Http/
│           └── HttpMethod.php          # HTTP method enum
│
├── tests/
│   ├── Fixtures/                       # GREAT FOOD IMPLEMENTATION
│   │   ├── GreatFoodConnector.php
│   │   ├── Requests/
│   │   │   ├── GetTokenRequest.php
│   │   │   ├── GetMenusRequest.php
│   │   │   ├── GetMenuProductsRequest.php
│   │   │   └── UpdateProductRequest.php
│   │   ├── Services/
│   │   │   └── MenuService.php
│   │   └── Models/
│   │       ├── Menu.php
│   │       └── Product.php
│   │
│   ├── Unit/                           # Library unit tests
│   │   ├── Client/
│   │   │   └── ConnectorTest.php
│   │   └── Requests/
│   │       └── RequestTest.php
│   │
│   └── Integration/                    # Scenario validation tests
│       ├── Scenario1Test.php           # Validates Scenario 1 requirements
│       └── Scenario2Test.php           # Validates Scenario 2 requirements
│
├── examples/
│   ├── scenario1.php                   # Demonstrate Scenario 1
│   └── scenario2.php                   # Demonstrate Scenario 2
│
├── responses/                          # Sample API responses
│   ├── token.json
│   ├── menus.json
│   └── menu-products.json
│
├── docs/
│   ├── IMPLEMENTATION_PLAN.md
│   ├── REQUIREMENTS.md
│   └── ARCHITECTURE.md
│
├── Dockerfile
├── docker-compose.yml
├── .dockerignore
├── composer.json
├── phpunit.xml
├── README.md
├── CLAUDE.md
└── .gitignore
```

**Key Separation:**
- `src/` = 100% generic, reusable for any API
- `tests/Fixtures/` = Great Food API specific implementation
- `tests/Integration/` = Validates tech test requirements
- `examples/` = Demonstrates library usage

---

## Performance Considerations

### Token Caching
- Token valid for 999999999 seconds (effectively permanent for test)
- No need for complex refresh logic
- Production version would implement token expiration checking

### Request Optimization
- Each scenario makes minimal required API calls
- No unnecessary data fetching
- Sequential requests (no parallel requests needed for test)

### Memory Management
- Models are lightweight value objects
- No large data structures held in memory
- Suitable for processing hundreds of products

---

## Security Considerations

### Credential Management
- Credentials should not be committed to version control
- For test purposes, inline credentials acceptable
- Production version should use environment variables

### Input Validation
- Validate menu IDs are integers
- Validate product IDs are integers
- Sanitize product names if necessary

### API Security
- Bearer token transmitted securely (HTTPS assumed)
- Token stored only in memory (not persisted)
- No SQL injection risk (no database)

---

## Extensibility

The architecture supports easy extension:

### Adding New Endpoints
Create a new Request class following the Saloon-like pattern:

```php
// src/Requests/DeleteProductRequest.php
class DeleteProductRequest extends Request
{
    public function __construct(
        private int $menuId,
        private int $productId
    ) {}

    public function resolveEndpoint(): string
    {
        return "/menu/{$this->menuId}/product/{$this->productId}";
    }

    public function method(): string
    {
        return 'DELETE'; // Or use constants: HttpMethod::DELETE
    }
}

// Then use in MenuService
public function deleteProduct(int $menuId, int $productId): bool
{
    $request = new DeleteProductRequest($menuId, $productId);
    $this->connector->send($request);
    return true;
}
```

### Adding New Models
```php
// src/Models/Category.php
class Category
{
    public int $id;
    public string $name;

    public static function fromArray(array $data): self
    {
        $category = new self();
        $category->id = $data['id'];
        $category->name = $data['name'];
        return $category;
    }
}
```

### Adding New Services
```php
// src/Services/OrderService.php
class OrderService
{
    public function __construct(private GreatFoodConnector $connector) {}

    public function getOrders(): array
    {
        $request = new GetOrdersRequest();
        $response = $this->connector->send($request);
        return array_map(
            fn($data) => Order::fromArray($data),
            $response['data']
        );
    }

    public function createOrder(Order $order): bool
    {
        $request = new CreateOrderRequest($order);
        $this->connector->send($request);
        return true;
    }
}
```

---

## Coding Standards

### HTTP Method Constants

**IMPORTANT**: Always use HTTP method constants instead of hardcoded strings.

```php
// ❌ BAD - Hardcoded strings
public function method(): string
{
    return 'GET';
}

// ✅ GOOD - Using constants
public function method(): string
{
    return HttpMethod::GET;
}

// Or using PHP's built-in constants
public function method(): string
{
    return 'GET'; // Acceptable if no constant enum exists
}
```

**Rationale**:
- Type safety and IDE autocomplete
- Prevents typos (e.g., 'GTE' instead of 'GET')
- Easier refactoring
- Better code clarity

### HTTP Status Code Constants

Similarly, use constants for HTTP status codes:

```php
// ❌ BAD
if ($response->getStatusCode() === 200) {
    // ...
}

// ✅ GOOD
use Symfony\Component\HttpFoundation\Response;

if ($response->getStatusCode() === Response::HTTP_OK) {
    // ...
}
```

### Recommended Constant Libraries

1. **Symfony HTTP Foundation**: `Symfony\Component\HttpFoundation\Response` for status codes
2. **Custom Enum** (PHP 8.1+): Create `HttpMethod` enum for HTTP methods
3. **PSR-7**: Use `Psr\Http\Message\RequestInterface` constants

### Example HttpMethod Enum

```php
// src/Http/HttpMethod.php
namespace GreatFood\Http;

enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
}

// Usage in Request classes
public function method(): string
{
    return HttpMethod::GET->value;
}
```

### Additional Standards

- **PSR-12**: Follow PSR-12 coding style
- **Type Declarations**: Use strict types (`declare(strict_types=1)`)
- **Return Types**: Always declare return types
- **Property Types**: Use typed properties (PHP 7.4+)
- **Null Safety**: Use nullable types (`?Type`) appropriately

---

## Conclusion

This architecture provides:
- ✅ Clear separation of concerns (Request, Connector, Service, Model layers)
- ✅ Easy testability at every layer
- ✅ Extensibility for future requirements
- ✅ Saloon-like Request pattern for clean API definitions
- ✅ Minimal complexity (appropriate for 1-2 hour test)
- ✅ Production-ready patterns (with noted simplifications)
- ✅ Maintainable and readable code structure
- ✅ Type safety and modern PHP practices
