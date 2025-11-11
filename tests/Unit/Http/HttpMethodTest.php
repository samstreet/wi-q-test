<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use ApiClient\Http\HttpMethod;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for HttpMethod enum.
 *
 * Tests that the HttpMethod enum provides all standard HTTP methods
 * with correct string values and can be used in string contexts.
 */
final class HttpMethodTest extends TestCase
{
    public function test_all_http_method_cases_exist(): void
    {
        $cases = HttpMethod::cases();

        $this->assertCount(7, $cases);

        $caseNames = array_map(fn ($case) => $case->name, $cases);

        $this->assertContains('GET', $caseNames);
        $this->assertContains('POST', $caseNames);
        $this->assertContains('PUT', $caseNames);
        $this->assertContains('PATCH', $caseNames);
        $this->assertContains('DELETE', $caseNames);
        $this->assertContains('HEAD', $caseNames);
        $this->assertContains('OPTIONS', $caseNames);
    }

    public function test_enum_values_are_correct_strings(): void
    {
        $this->assertSame('GET', HttpMethod::GET->value);
        $this->assertSame('POST', HttpMethod::POST->value);
        $this->assertSame('PUT', HttpMethod::PUT->value);
        $this->assertSame('PATCH', HttpMethod::PATCH->value);
        $this->assertSame('DELETE', HttpMethod::DELETE->value);
        $this->assertSame('HEAD', HttpMethod::HEAD->value);
        $this->assertSame('OPTIONS', HttpMethod::OPTIONS->value);
    }

    public function test_enum_can_be_used_in_string_context(): void
    {
        $method = HttpMethod::GET;

        // Test that we can get the string value
        $this->assertSame('GET', $method->value);

        // Test that it works in string concatenation
        $message = 'HTTP method: ' . $method->value;
        $this->assertSame('HTTP method: GET', $message);
    }

    public function test_enum_cases_match_their_values(): void
    {
        // Verify that case names match their string values
        foreach (HttpMethod::cases() as $case) {
            $this->assertSame($case->name, $case->value);
        }
    }

    public function test_from_method_creates_enum_from_string(): void
    {
        $this->assertSame(HttpMethod::GET, HttpMethod::from('GET'));
        $this->assertSame(HttpMethod::POST, HttpMethod::from('POST'));
        $this->assertSame(HttpMethod::PUT, HttpMethod::from('PUT'));
        $this->assertSame(HttpMethod::PATCH, HttpMethod::from('PATCH'));
        $this->assertSame(HttpMethod::DELETE, HttpMethod::from('DELETE'));
        $this->assertSame(HttpMethod::HEAD, HttpMethod::from('HEAD'));
        $this->assertSame(HttpMethod::OPTIONS, HttpMethod::from('OPTIONS'));
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(HttpMethod::tryFrom('INVALID'));
        $this->assertNull(HttpMethod::tryFrom('get'));
        $this->assertNull(HttpMethod::tryFrom(''));
    }
}
