<?php

declare(strict_types=1);

namespace Guard\Reporting;

/**
 * An effective rule, its scope, constraints, and diagnostic message.
 */
final class RuleDescription
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        public string $id,
        public string $target,
        public string $level,
        public bool $fixable,
        public string $message,
        public array $details = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['id' => $this->id, 'target' => $this->target, 'level' => $this->level, 'fixable' => $this->fixable, 'message' => $this->message, 'details' => $this->details];
    }
}
