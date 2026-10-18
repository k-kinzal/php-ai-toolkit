<?php

declare(strict_types=1);

namespace DecisionsLint\Http;

/**
 * One HTTP request with its body already encoded.
 *
 * @property-read non-empty-string $method GET, POST and so on
 * @property-read non-empty-string $url the absolute URL
 * @property-read array<string, string> $headers header name to value
 * @property-read string $body the encoded body, or an empty string
 */
final class HttpRequest
{
    /**
     * Creates the immutable value.
     *
     * @param non-empty-string $method
     * @param non-empty-string $url
     * @param array<string, string> $headers
     */
    public function __construct(
        /** @readonly */
        private string $method,
        /** @readonly */
        private string $url,
        /** @readonly */
        private array $headers = [],
        /** @readonly */
        private string $body = '',
    ) {
    }

    /**
     * Returns a declared immutable property.
     *
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'method' => $this->method,
            'url' => $this->url,
            'headers' => $this->headers,
            'body' => $this->body,
            default => null,
        };
    }
}
