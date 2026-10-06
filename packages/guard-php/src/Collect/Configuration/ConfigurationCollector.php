<?php

declare(strict_types=1);

namespace Guard\Collect\Configuration;

use Generator;
use Guard\Collect\Collector;
use Guard\Document\DataDocument;
use Guard\Document\DocumentFailure;
use Guard\Document\PhpDocument;
use Guard\Document\XmlDocument;
use Guard\Execution\Context;
use Guard\Execution\TargetPath;
use Guard\Policy\PolicyException;
use Guard\Policy\Rule;
use JsonException;
use Nette\Neon\Exception as NeonException;
use RuntimeException;

/**
 * Resolves and parses each configuration document once for its field policies.
 */
final class ConfigurationCollector implements Collector
{
    /**
     * @return Generator<int, ConfigurationDocument>
     * @throws JsonException
     * @throws NeonException
     * @throws PolicyException
     */
    public function collect(Context $context): Generator
    {
        /** @var array<string, non-empty-list<Rule>> $groups */
        $groups = [];
        foreach ($context->configuration->rules as $rule) {
            $path = (new TargetPath())->resolve($context->configuration->root, $rule->file);
            if (realpath($path) === realpath($context->configPath)) {
                throw new PolicyException('Rule ' . $rule->id . ' targets guard.yaml itself. Policies cannot rewrite their own constraints.');
            }
            $groups[$path][] = $rule;
        }
        foreach ($groups as $path => $rules) {
            yield $this->read($path, $rules);
        }
    }
    /**
     * @param list<Rule> $rules
     * @throws JsonException
     * @throws NeonException
     * @throws PolicyException
     */
    public function read(string $path, array $rules): ConfigurationDocument
    {
        try {
            if ($rules === []) {
                throw new PolicyException('A file plan requires at least one configuration rule.');
            }
            $source = file_get_contents($path);
            if ($source === false) {
                throw new PolicyException('Cannot read ' . $path . '. Check file permissions.');
            }
            $format = $rules[0]->format;
            $document = match ($format) {
                'xml' => new XmlDocument($source),
                'php' => new PhpDocument($source),
                default => new DataDocument($format, $source),
            };
            return new ConfigurationDocument($path, $source, $format, $document, $rules);
        } catch (RuntimeException|JsonException|NeonException $exception) {
            throw (new DocumentFailure())->at($path, $exception);
        }
    }
}
