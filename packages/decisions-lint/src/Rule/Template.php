<?php

declare(strict_types=1);

namespace DecisionsLint\Rule;

use DecisionsLint\Diagnostic\LintException;

/**
 * Fills `{name}` placeholders in rule instructions, messages and fixes.
 */
final class Template
{
    /**
     * The placeholders every template may use, filled from the target, the rule and the answer.
     *
     * @var list<string>
     */
    public const PLACEHOLDERS = [
        'file', 'line', 'column', 'kind', 'name', 'class', 'namespace', 'qualified_name', 'signature', 'visibility',
        'rule', 'answer', 'probability', 'confidence', 'choice', 'score', 'threshold',
    ];

    /**
     * Returns the placeholder names a template uses, in order of first appearance.
     *
     * @return list<string>
     */
    public function placeholders(string $template): array
    {
        preg_match_all('/\{([a-z_]+)\}/', $template, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * Rejects a template that uses a placeholder no value is ever supplied for.
     *
     * @throws LintException when the template names an unknown placeholder
     */
    public function check(string $template, string $context): void
    {
        foreach ($this->placeholders($template) as $placeholder) {
            if (!in_array($placeholder, self::PLACEHOLDERS, true)) {
                throw new LintException(sprintf('%s uses the unknown placeholder {%s}. Use one of: {%s}.', $context, $placeholder, implode('}, {', self::PLACEHOLDERS)));
            }
        }
    }

    /**
     * Replaces every placeholder that has a value; the others are left as written.
     *
     * @param array<string, string> $values
     */
    public function render(string $template, array $values): string
    {
        $replacements = [];
        foreach ($values as $name => $value) {
            $replacements['{' . $name . '}'] = $value;
        }

        return strtr($template, $replacements);
    }
}
