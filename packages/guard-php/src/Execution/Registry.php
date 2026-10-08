<?php

declare(strict_types=1);

namespace Guard\Execution;

use Guard\Diagnostic\PolicyException;
use Guard\Policy\Policy;
use Guard\Policy\PolicyBinding;
use Guard\Structure\Structurer;

/**
 * Registers structures and policies; file collection is shared by all registrations.
 */
final class Registry
{
    /**
     * Composes built-in structures and configured policies through the ordinary registration API.
     * @param list<PolicyBinding> $policies
     */
    public static function defaults(array $policies = []): self
    {
        $registry = new self();
        $registry->addStructure('php.tokens', new \Guard\Structure\Php\TokenParser());
        $registry->addStructure('php.metrics', new \Guard\Structure\Php\MetricParser());
        $registry->addStructure('markdown.headings', new \Guard\Structure\Markdown\HeadingStructurer());
        $registry->addStructure('markdown.badges', new \Guard\Structure\Markdown\Badge\BadgeStructurer());
        $registry->addStructure('text', new \Guard\Structure\TextStructurer());
        foreach (['json', 'json5', 'yaml', 'yml', 'neon', 'toml', 'xml', 'php'] as $format) {
            $registry->addStructure($format, new \Guard\Structure\DocumentStructurer($format));
        }
        foreach ($policies as $binding) {
            $registry->addPolicy($binding->id, $binding->policy, $binding->reportOrder);
        }
        return $registry;
    }
    /** @var array<string, Structurer> */
    private array $structurers = [];
    /** @var array<string, PolicyBinding> */
    private array $policies = [];
    /** Registers a reusable structure producer.
     * @throws PolicyException
     */
    public function addStructure(string $id, Structurer $structurer): void
    {
        if ($id === '' || isset($this->structurers[$id])) {
            throw new PolicyException('Structure id "' . $id . '" is empty or already registered. Choose a unique non-empty id.');
        }
        $this->structurers[$id] = $structurer;
    }
    /** Registers a policy that declares its own input requirements.
     * @throws PolicyException
     */
    public function addPolicy(string $id, Policy $policy, int $reportOrder = 0): void
    {
        if ($id === '' || isset($this->policies[$id])) {
            throw new PolicyException('Policy id "' . $id . '" is empty or already registered. Choose a unique non-empty id.');
        }
        $this->policies[$id] = new PolicyBinding($id, $policy, $reportOrder);
    }
    /**
     * @return array<string, Structurer>
     */
    public function structures(): array
    {
        return $this->structurers;
    }
    /**
     * @return list<PolicyBinding>
     */
    public function policies(): array
    {
        return array_values($this->policies);
    }
}
