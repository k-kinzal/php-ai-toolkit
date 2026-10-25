# PropertyTestConventionRule

| Property | Value |
|----------|-------|
| Identifiers | `customRules.propertyTestLocation`, `customRules.propertyTestClassName`, `customRules.propertyTestExtends`, `customRules.propertyTestMethodName`, `customRules.propertyTestMethodSignature`, `customRules.propertyTestDiscovery`, `customRules.propertyTestDeclaration`, `customRules.propertyTestEmpty`, `customRules.propertyTestExecution` |
| Scope | Classes under `tests/Property`, in `Tests\Property`, or PHPUnit subclasses named `*Properties` or using `Eris\TestTrait` |
| Rector | Unavailable |
| Configuration | `toolkit.propertyTestConvention.enabled` (inherits `allRules`) |

## What it checks

Property tests belong under `tests/Property`, with a class named `<Name>Properties` extending `PHPUnit\Framework\TestCase` directly. Guard's `pbt` preset restricts their filenames to `*Properties.php`.

Only concrete classes belong in this property-test scope: traits, interfaces, enums, and abstract helpers must live elsewhere. Each class must declare at least one test method; empty classes are reported. These are general property-test conventions, independent of project ownership.

Every method declared in the class must be named `satisfies<PropertyName>`, with an uppercase letter after `satisfies`. The method must be public, concrete, non-static, and have no parameters. Generate inputs inside the method. Helpers, constructors, lifecycle overrides such as `setUp`, and data providers cannot be declared in a property test class.

Mark each method with PHPUnit's `#[Test]` attribute so it runs despite not starting with `test`. Projects using PHPUnit 9 must use `@test`; projects supporting both versions can carry both markers. The rule requires the attribute when PHPUnit 10+ is installed and accepts the annotation on PHPUnit 9.

```php
<?php

namespace Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IntegerProperties extends TestCase
{
    use TestTrait;

    #[Test]
    public function satisfiesAdditionIdentity(): void
    {
        $this->forAll(Generators::int())->then(function (int $value): void {
            self::assertSame($value, $value + 0);
        });
    }
}
```

## Eris execution

When the class uses `Eris\TestTrait`, each valid property method must execute a quantifier from `$this->forAll(...)` with at least one generator. Both `->then($assertion)` and the quantifier's callable form are accepted. Quantifiers may be assigned to local variables, aliased, and configured with Eris's `when` (including its `and` alias), `withMaxSize`, `hook`, `stopOn`, or `disableShrinking` methods. Assigning the result of execution is also accepted.

The check examines top-level expression and return statements, matching the existing prohibition on control flow in test methods. An unexecuted closure, a first-class callable reference, a quantifier overwritten by another value, and a call after a return do not satisfy the check. Classes using another PBT framework are not required to use Eris. The rule checks execution form, not the strength of assertions or the generators' domain coverage.

## Shared test rules

Property tests also receive the existing restrictions on declared properties, class constants, private methods, helper traits, Reflection, broken-code expectations, redundant assertions, and control flow in test methods. The control-flow rule keeps its existing boundary: nested closures and anonymous classes are separate scopes. `Tests\Property` is included in the default restricted namespaces for descriptive PHPDoc and public API documentation rules.

`Eris\TestTrait` is allowed without trait adaptations because it supplies the PBT framework. Other traits remain prohibited. PHPUnit mock API restrictions do not apply to property tests, including concrete-class mocks. The Unit naming and source/test pairing conventions are not imposed on property test methods; property tests do not replace Unit test coverage requirements.

## Guard configuration

Import `vendor/k-kinzal/guard-php/rules/pbt.yaml`, alongside the project's PHPUnit and PHPStan presets. `guard init` selects it automatically when `giorgiosironi/eris` is installed. The preset requires Composer scripts containing `--testsuite=unit` for `test:unit` and `--testsuite=property` for `test:pbt`.

For each selected PHPUnit preset, `guard init` adds a property suite requirement when `tests/Property` exists or the `pbt` preset is selected. It also requires the discovery suffix:

```xml
<testsuite name="property">
    <directory suffix="Properties.php">tests/Property</directory>
</testsuite>
```

Existing Guard configurations must add these suite requirements when adopting PBT; importing `pbt.yaml` alone does not regenerate project-specific PHPUnit rules. Keep `tests/Property` in PHPStan's analysis paths.

## Rector

No automatic rewrite is provided: the property name and the move of setup behavior require a test author to choose the intended semantics.
