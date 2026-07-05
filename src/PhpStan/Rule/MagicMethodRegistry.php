<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Provides magic method alternatives for direct-call diagnostics.
 */
final class MagicMethodRegistry
{
    /**
     * @var array<string, string>
     */
    private const MAGIC_METHOD_ALTERNATIVES = [
        '__construct' => 'Use the new keyword: new ClassName(...$args)',
        '__destruct' => 'Use unset() or let the object go out of scope',
        '__call' => 'Call the method by name directly: $obj->method(...$args)',
        '__callStatic' => 'Call the static method by name directly: ClassName::method(...$args)',
        '__get' => 'Access the property directly: $obj->property',
        '__set' => 'Assign the property directly: $obj->property = $value',
        '__isset' => 'Use isset(): isset($obj->property)',
        '__unset' => 'Use unset(): unset($obj->property)',
        '__sleep' => 'Use serialize(): serialize($obj)',
        '__wakeup' => 'Use unserialize(): unserialize($data)',
        '__serialize' => 'Use serialize(): serialize($obj)',
        '__unserialize' => 'Use unserialize(): unserialize($data)',
        '__toString' => 'Use (string) cast: (string)$obj',
        '__invoke' => 'Call the object as a function: $obj(...$args)',
        '__set_state' => 'Reconstruct via constructor or factory method',
        '__clone' => 'Use the clone keyword: clone $obj',
        '__debugInfo' => 'Use var_dump(): var_dump($obj)',
    ];

    /**
     * Reports whether the method name is a PHP magic method.
     */
    public function isMagic(string $methodName): bool
    {
        return array_key_exists($methodName, self::MAGIC_METHOD_ALTERNATIVES);
    }

    /**
     * Returns the preferred language construct or direct usage alternative.
     */
    public function alternative(string $methodName): string
    {
        return self::MAGIC_METHOD_ALTERNATIVES[$methodName] ?? 'Use the corresponding language construct';
    }
}
