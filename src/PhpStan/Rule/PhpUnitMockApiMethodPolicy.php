<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Classifies PHPUnit mock APIs by the project's allowed test-double policy.
 */
final class PhpUnitMockApiMethodPolicy
{
    /**
     * @var list<string>
     */
    private const ALWAYS_PROHIBITED_METHODS = [
        'getMockBuilder',
        'createPartialMock',
        'createTestProxy',
        'getMockForAbstractClass',
        'getMockForTrait',
        'getMockFromWsdl',
    ];

    /**
     * @var list<string>
     */
    private const INTERFACE_ONLY_METHODS = [
        'createMock',
        'createConfiguredMock',
        'createStub',
        'createConfiguredStub',
    ];

    /**
     * Reports whether the PHPUnit method is always prohibited.
     */
    public function isAlwaysProhibited(string $methodName): bool
    {
        return in_array($methodName, self::ALWAYS_PROHIBITED_METHODS, true);
    }

    /**
     * Reports whether the PHPUnit method requires an interface class-string.
     */
    public function requiresInterfaceTarget(string $methodName): bool
    {
        return in_array($methodName, self::INTERFACE_ONLY_METHODS, true);
    }
}
