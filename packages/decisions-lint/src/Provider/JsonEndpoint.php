<?php

declare(strict_types=1);

namespace DecisionsLint\Provider;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Http\Transport;
use JsonException;

/**
 * Posts JSON documents to a bearer-authenticated API and decodes what comes back.
 *
 * The two decision APIs share this shape; each client adds its path and body.
 */
final class JsonEndpoint
{
    /**
     * Creates an endpoint for one provider over a transport; an empty key sends no Authorization header.
     */
    public function __construct(private Transport $transport, private ProviderSettings $settings, private string $apiKey)
    {
    }

    /**
     * Posts every payload to a path and returns the decoded responses in the same order.
     *
     * @param non-empty-string $path the endpoint path, starting with a slash
     * @param list<array<string, mixed>> $payloads
     * @return list<array<string, mixed>>
     * @throws LintException when a request fails or a response is not a JSON object
     */
    public function post(string $path, array $payloads): array
    {
        $requests = [];
        foreach ($payloads as $payload) {
            $requests[] = $this->request($path, $payload);
        }
        $documents = [];
        foreach ($this->transport->send($requests) as $index => $response) {
            $documents[] = $this->decode($response, $requests[$index]);
        }

        return $documents;
    }

    /**
     * Builds the request for one payload.
     *
     * @param non-empty-string $path the endpoint path, starting with a slash
     * @param array<string, mixed> $payload
     * @throws LintException when the payload cannot be encoded
     */
    public function request(string $path, array $payload): HttpRequest
    {
        try {
            $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $exception) {
            throw new LintException(sprintf('Could not encode a request for %s: %s. Make sure the linted files are valid UTF-8.', $this->settings->name, $exception->getMessage()), 0, $exception);
        }

        $headers = $this->apiKey === '' ? [] : ['Authorization' => 'Bearer ' . $this->apiKey];

        return new HttpRequest('POST', rtrim($this->settings->baseUrl, '/') . $path, $headers + [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'decisions-lint',
        ], $body);
    }

    /**
     * Decodes a successful response, or explains the failure.
     *
     * @return array<string, mixed>
     * @throws LintException when the status is not 2xx or the body is not a JSON object
     */
    public function decode(HttpResponse $response, HttpRequest $request): array
    {
        $document = null;
        try {
            $document = json_decode($response->body, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            if ($response->ok()) {
                throw new LintException(sprintf('%s returned HTTP %d for %s with a body that is not JSON: %s. Check provider.base_url in decisions.yaml.', $this->settings->name, $response->status, $request->url, $exception->getMessage()), 0, $exception);
            }
        }
        if (!$response->ok()) {
            throw new LintException($this->failure($response, $request, is_array($document) ? $document : []));
        }
        if (!is_array($document)) {
            throw new LintException(sprintf('%s returned HTTP %d for %s with a JSON body that is not an object. Check provider.base_url in decisions.yaml.', $this->settings->name, $response->status, $request->url));
        }
        $object = [];
        foreach ($document as $key => $value) {
            $object[(string) $key] = $value;
        }

        return $object;
    }

    /**
     * Explains a non-2xx response with the provider's own message and the configuration to check.
     *
     * @param array<array-key, mixed> $document
     */
    public function failure(HttpResponse $response, HttpRequest $request, array $document): string
    {
        $detail = $this->detail($document, $response->body);
        $hint = match (true) {
            $response->status === 401 || $response->status === 403 => sprintf('Check that the %s environment variable holds a valid %s API key.', $this->settings->apiKeyEnv, $this->settings->name),
            $response->status === 404 => 'Check provider.base_url in decisions.yaml.',
            $response->status === 422 || $response->status === 400 => sprintf('Check provider.model ("%s") and the rule questions in decisions.yaml.', $this->settings->model),
            $response->status === 429 => 'Lower provider.concurrency in decisions.yaml or retry later.',
            $response->status >= 500 => 'The provider is unavailable; retry later.',
            default => 'Check the provider status and decisions.yaml.',
        };

        return sprintf('%s returned HTTP %d for POST %s%s. %s', $this->settings->name, $response->status, $request->url, $detail === '' ? '' : ': ' . $detail, $hint);
    }

    /**
     * Extracts the error message of a failure body, falling back to a short excerpt of the body.
     *
     * @param array<array-key, mixed> $document
     */
    public function detail(array $document, string $body): string
    {
        $error = $document['error'] ?? $document['message'] ?? $document['detail'] ?? null;
        if (is_array($error)) {
            $error = $error['message'] ?? $error['detail'] ?? null;
        }
        if (is_string($error) && $error !== '') {
            return $error;
        }
        $excerpt = trim((string) preg_replace('/\s+/', ' ', $body));

        return strlen($excerpt) > 200 ? substr($excerpt, 0, 200) . '…' : $excerpt;
    }
}
