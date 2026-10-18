<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\Fake;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Provider\Payload;
use JsonException;

/**
 * What every fake provider does with a request: checks the path and the JSON body, and encodes the response.
 */
final class RequestBody
{
    /**
     * Decodes the body of a request that must be a POST to a path.
     *
     * @throws LintException when the method, the path or the body is not what the provider accepts
     */
    public function read(HttpRequest $request, string $path, string $provider): Payload
    {
        if ($request->method !== 'POST' || !str_ends_with($request->url, $path)) {
            throw new LintException(sprintf('%s fake accepts only POST %s, not %s %s.', $provider, $path, $request->method, $request->url));
        }
        try {
            $decoded = json_decode($request->body, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new LintException(sprintf('%s fake received a body that is not JSON: %s.', $provider, $exception->getMessage()), 0, $exception);
        }
        if (!is_array($decoded)) {
            throw new LintException(sprintf('%s fake received a JSON body that is not an object.', $provider));
        }

        return new Payload($decoded, $provider . ' fake request');
    }

    /**
     * Reads the string values of a mapping, such as a subject state.
     *
     * @return array<string, string>
     */
    public function strings(Payload $payload, string $key): array
    {
        $strings = [];
        if (!$payload->has($key)) {
            return $strings;
        }
        $nested = $payload->nested($key);
        foreach ($nested->keys() as $name) {
            $strings[$name] = $nested->stringOr($name, '');
        }

        return $strings;
    }

    /**
     * Encodes a response document.
     *
     * @param array<string, mixed> $document
     * @throws LintException when the document cannot be encoded
     */
    public function encode(array $document): string
    {
        try {
            return json_encode($document, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        } catch (JsonException $exception) {
            throw new LintException(sprintf('A fake provider could not encode its response: %s.', $exception->getMessage()), 0, $exception);
        }
    }
}
