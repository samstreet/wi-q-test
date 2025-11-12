# Requirements Document: Great Food Ltd API Client

## Project Context
This is a technical assessment for a PHP Backend Developer position at wi-Q. The goal is to demonstrate understanding of REST APIs and orchestration of API consumption within an application.

## Constraints

### Time Constraint
- Maximum time: 1-2 hours
- This is a strict constraint that influences all design decisions

### Technical Constraints
- **Framework**: No specific framework required (preferred), but can use one with justification
- **Language**: PHP (implied from "PHP Backend Developer Test")
- **Dependencies**: Use of third-party packages is encouraged
- **Testing**: Must include tests to prove the solution works and is robust
- **Real-World Viability**: The library must be designed to work in real-world scenarios

### Disclosure Requirements
- If AI development tools are used, this must be disclosed during the interview

---

## Functional Requirements

### FR1: API Client Library
**Description**: Create a library that provides functionality to interact with the Great Food Ltd REST API.

**Acceptance Criteria**:
- Library provides methods to authenticate with the API
- Library provides methods to make GET and PUT requests
- Library properly handles HTTP headers (Authorization, Content-Type)
- Library returns structured data from API responses
- Library handles errors gracefully

---

### FR2: OAuth2 Authentication (Client Credentials)
**Description**: Implement authentication using OAuth2 client credentials grant type.

**API Details**:
- **Endpoint**: `/auth_token`
- **Method**: POST
- **Content-Type**: `application/x-www-form-urlencoded`
- **Parameters**:
  - `client_id`: 1337
  - `client_secret`: 4j3g4gj304gj3
  - `grant_type`: client_credentials

**Response Structure** (from `tests/Fixtures/stubs/token.json`):
```json
{
    "access_token": "33w4yh344go3u4h34yh93n4h3un4g34g",
    "expires_in": 999999999,
    "token_type": "Bearer",
    "scope": "catalogue"
}
```

**Acceptance Criteria**:
- Successfully authenticate and receive bearer token
- Store token for subsequent requests
- Include token in Authorization header as "Bearer {token}"

---

### FR3: Scenario 1 - Display Takeaway Menu Products
**Description**: Fetch and display products from the "Takeaway" menu in a table format.

**Required Steps**:
1. Authenticate with the API
2. Fetch all menus from `GET /menus`
3. Identify the menu named "Takeaway"
4. Fetch products for that menu from `GET /menu/{menu_id}/products`
5. Display products in a table with ID and Name columns

**API Details**:

**GET /menus**
- **Method**: GET
- **Headers**: `Authorization: Bearer {token}`
- **Response Structure** (from `tests/Fixtures/stubs/menus.json`):
```json
{
    "data": [
        {
            "id": 1,
            "name": "Starters"
        },
        {
            "id": 3,
            "name": "Takeaway"
        }
    ]
}
```

**GET /menu/{menu_id}/products**
- **Method**: GET
- **Headers**: `Authorization: Bearer {token}`
- **Response Structure** (from `tests/Fixtures/stubs/menu-products.json`):
```json
{
    "data": [
        {
            "id": 1,
            "name": "Large Pizza"
        },
        {
            "id": 3,
            "name": "Burger"
        }
    ]
}
```

**Expected Output Format**:
```
| ID | Name    |
| -- | ------- |
| 4  | Burger  |
| 5  | Chips   |
| 99 | Lasagna |
```

**Acceptance Criteria**:
- Successfully authenticates
- Fetches all menus
- Correctly identifies "Takeaway" menu (ID: 3 based on sample data)
- Fetches products for the Takeaway menu
- Displays products in specified table format
- Handles case where "Takeaway" menu doesn't exist

---

### FR4: Scenario 2 - Update Product Name
**Description**: Update an incorrectly named product via the API.

**Business Context**: Product 84 in menu 7 is named "Chpis" but should be "Chips".

**API Details**:

**PUT /menu/{menu_id}/product/{product_id}**
- **Method**: PUT
- **Headers**: `Authorization: Bearer {token}`
- **Body**: Product model as described in the GET response (JSON)
- **Example Request**:
```json
{
    "id": 84,
    "name": "Chips"
}
```

**Required Steps**:
1. Authenticate with the API
2. Prepare product update payload
3. Send PUT request to `/menu/7/product/84`
4. Verify successful update

**Expected Output**:
"Proof that the API request has been successful" - this could be:
- HTTP 200/204 status code
- Success message from API
- Confirmation message in console

**Acceptance Criteria**:
- Successfully authenticates
- Sends PUT request with correct payload structure
- Handles success response appropriately
- Displays confirmation of successful update
- Handles error cases (e.g., product not found, authentication failure)

---

## Non-Functional Requirements

### NFR1: Code Quality
**Description**: Code must be clean, maintainable, and follow PHP best practices.

**Acceptance Criteria**:
- Proper separation of concerns
- Clear class and method naming
- Appropriate use of namespaces
- PSR-4 autoloading standards
- Error handling implemented throughout
- No hardcoded values where configuration is appropriate

---

### NFR2: Testability
**Description**: The solution must include comprehensive tests.

**Acceptance Criteria**:
- Unit tests for core functionality
- Integration tests for API interactions
- Tests prove the solution works and is robust
- Tests can be run via standard PHP testing tools (PHPUnit recommended)
- Mock external API calls appropriately

---

### NFR3: Real-World Viability
**Description**: The library should work in actual production scenarios, not just for this test.

**Acceptance Criteria**:
- Configurable API endpoints
- Secure credential management
- Proper error handling and reporting
- Extensible architecture for additional endpoints
- Thread-safe (if applicable)

---

### NFR4: Documentation
**Description**: Code and usage should be well-documented.

**Acceptance Criteria**:
- Clear README explaining how to use the library
- Code comments where logic is complex
- PHPDoc blocks for public methods
- Example usage provided (the scenario implementations serve this purpose)

---

## Technical Architecture Requirements

### TAR1: Separation of Concerns
The solution should separate:
- **HTTP Communication Layer**: Raw API interactions
- **Business Logic Layer**: Menu/product operations
- **Data Models**: Representation of API entities
- **Presentation Layer**: Output formatting

### TAR2: Reusability
The API client should be reusable for other endpoints beyond the two scenarios.

### TAR3: Dependency Management
Use Composer for:
- Autoloading (PSR-4)
- Dependency management
- Test execution

---

## Sample Data Provided

The following sample response stubs are provided in the `tests/Fixtures/stubs/` directory:

1. **tests/Fixtures/stubs/token.json**: OAuth2 token response
2. **tests/Fixtures/stubs/menus.json**: List of available menus
3. **tests/Fixtures/stubs/menu-products.json**: Products for the Takeaway menu

These files can be used for:
- Understanding expected API response structures
- Test fixtures
- Development without a live API

---

## Deliverables Checklist

- [ ] REST API client library
- [ ] OAuth2 authentication implementation
- [ ] Scenario 1 implementation (display Takeaway products)
- [ ] Scenario 2 implementation (update product name)
- [ ] Unit tests
- [ ] Integration tests
- [ ] Documentation (README, code comments)
- [ ] composer.json with dependencies
- [ ] Proof that both scenarios work correctly

---

## Evaluation Criteria (Anticipated)

Based on the README note about interview discussion, the solution will likely be evaluated on:

1. **Functionality**: Do both scenarios work correctly?
2. **Code Design**: Is the architecture clean and maintainable?
3. **Design Decisions**: Can the candidate explain why they made specific choices?
4. **Real-World Thinking**: Does the solution consider production concerns?
5. **Testing**: Are tests comprehensive and meaningful?
6. **Time Management**: Was the 1-2 hour constraint respected?
7. **Communication**: Can the candidate articulate how their code works?

---

## Assumptions

The following assumptions are made based on the README:

1. **No Live API**: Since the README states "It is not necessary to create a functioning REST API", we assume no live API will be available and sample data should be used for testing
2. **Base URL**: Not provided, can be mocked or configured
3. **PHP Version**: Modern PHP (7.4+ or 8.0+) is assumed
4. **Output Format**: Console/CLI output is acceptable for both scenarios
5. **Error Cases**: Reasonable error handling is expected but not extensively specified
6. **Data Validation**: Basic validation is expected but not specified in detail

---

## Open Questions

Questions that may need clarification during implementation or interview:

1. Should the library support other HTTP methods (DELETE, PATCH)?
2. Should token expiration be actively managed, or is simple storage sufficient?
3. What level of logging is expected (if any)?
4. Should the library support rate limiting?
5. Is configuration via .env file expected, or are inline credentials acceptable for the test?
6. Should the product update (Scenario 2) first fetch the product, modify it, then update, or just send the new name?
7. What PHP version compatibility is required?
