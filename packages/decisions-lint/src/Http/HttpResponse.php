<?php

declare(strict_types=1);

namespace DecisionsLint\Http;

/**
 * The status and body one HTTP request came back with.
 *
 * @property-read int $status the HTTP status code
 * @property-read string $body the raw response body
 */
final class HttpResponse
{
    /**
     * Creates the immutable value.
     */
    public function __construct(
        /** @readonly */
        private int $status,
        /** @readonly */
        private string $body,
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
            'status' => $this->status,
            'body' => $this->body,
            default => null,
        };
    }

    /**
     * Tells whether the status is in the 2xx range.
     */
    public function ok(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }
}
