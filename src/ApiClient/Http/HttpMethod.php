<?php

declare(strict_types=1);

namespace ApiClient\Http;

/**
 * HTTP method enum for type-safe HTTP method constants.
 *
 * This enum provides a type-safe way to specify HTTP methods in requests,
 * preventing typos and enabling better IDE support.
 */
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
