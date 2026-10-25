# RequireClassPairRule

| Property | Value |
|----------|-------|
| Identifiers | `customRules.classPairMissing`, `customRules.classPairOrphaned` |
| Scope | Every named class, interface, trait, and enum declaration in the analyzed paths |
| Rector | Unavailable |
| Configurable | Yes (`classPairs`, `classPairExclude`, `classPairProjectRoot`) |

## What It Detects

A class pair declares that when a class matching one name template exists, a class matching a second template must exist too. The shipped pair requires a unit test class for every production class:

```neon
parameters:
    toolkit:
        classPairs:
            -
                from: '${src}\{name}'
                to: '${tests/Unit}\{name}Test'
```

`${src}` and `${tests/Unit}` are read from the PSR-4 sections of `composer.json`. With `"App\\": "src/"` under `autoload` and `"Tests\\": "tests/"` under `autoload-dev`, the pair becomes `App\{name}` and `Tests\Unit\{name}Test`. `{name}` stands for the rest of the class name, including nested namespaces, so `App\Service\UserService` needs `Tests\Unit\Service\UserServiceTest`.

### 1. Class without its counterpart (`customRules.classPairMissing`)

```
src/Service/UserService.php      <-- ERROR: no Tests\Unit\Service\UserServiceTest
src/Service/OrderService.php     <-- OK
tests/Unit/Service/OrderServiceTest.php
```

```
Create class "Tests\Unit\Service\UserServiceTest" in tests/Unit/Service/UserServiceTest.php
for class "App\Service\UserService": every class matching "App\{name}" needs a class
matching "Tests\Unit\{name}Test".
```

### 2. Counterpart without its origin (`customRules.classPairOrphaned`)

```
src/Service/OrderService.php
tests/Unit/Service/OrderServiceTest.php    <-- OK
tests/Unit/Service/LegacyServiceTest.php   <-- ERROR: no App\Service\LegacyService
```

```
Create class "App\Service\LegacyService" in src/Service/LegacyService.php for class
"Tests\Unit\Service\LegacyServiceTest", or remove "Tests\Unit\Service\LegacyServiceTest":
every class matching "Tests\Unit\{name}Test" needs a class matching "App\{name}".
```

The file in the message is where PSR-4 expects the missing class. It is omitted when no `composer.json` entry covers the namespace.

## Why This Is an Error

### Missing counterparts

Every production class should have a unit test that pins its behavior. AI code generators often create production code without tests, or create tests for some classes but not others. Declaring the pair makes the gap visible at analysis time rather than during code review, and the same mechanism can require a fake for every interface or a factory for every entity.

### Orphaned counterparts

When a class is renamed or deleted, its test may be left behind. Orphaned tests waste CI time, inflate coverage, and mislead the next reader about which classes exist.

## How to Fix

### For a missing counterpart

Create the class named in the message at the file named in the message:

```php
// tests/Unit/Service/UserServiceTest.php
namespace Tests\Unit\Service;

use App\Service\UserService;
use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    public function testCreatePersistsUser(): void
    {
        // ...
    }
}
```

### For an orphaned counterpart

Either delete the stale class or rename it to match the current origin:

```bash
# If the origin was renamed
git mv tests/Unit/Service/LegacyServiceTest.php tests/Unit/Service/ModernServiceTest.php

# If the origin was deleted
git rm tests/Unit/Service/LegacyServiceTest.php
```

## Configuration

### Templates

`from` and `to` are fully qualified class name templates. Each contains one `{name}` placeholder; text before it is a required prefix and text after it a required suffix. A template without `{name}` is a namespace prefix, so `from: '${src}'` means `${src}\{name}`. Matching is case-sensitive and `{name}` only matches valid class name segments, so `Tests\Unit\Test` never matches `Tests\Unit\{name}Test`.

Each pair is checked in both directions: a class matching `from` needs the class produced by `to`, and a class matching `to` needs the class produced by `from`. Pairs whose two templates produce the same class name for a class are ignored.

### Placeholders from `composer.json`

`${directory}` is replaced with the namespace that the `autoload` or `autoload-dev` PSR-4 section maps to that root-relative directory. A subdirectory of a mapped directory receives the mapped namespace plus one segment per path component, which is how `${tests/Unit}` resolves to `Tests\Unit`. The longest mapped directory wins. The `composer.json` is read from `classPairProjectRoot`, which defaults to `%currentWorkingDirectory%`; set it when PHPStan runs from another directory:

```neon
parameters:
    toolkit:
        classPairProjectRoot: %rootDir%/../../..
```

A pair whose placeholder names a directory without a PSR-4 mapping is skipped, so the shipped default is inert in a project without a `src/` namespace. Write the namespaces literally when the project autoloads through a classmap.

### Adding pairs

PHPStan merges lists, so a project adds pairs without repeating the default:

```neon
parameters:
    toolkit:
        classPairs:
            -
                from: '${src}\{name}Interface'
                to: '${src}\Fake\Fake{name}'
            -
                from: '${src}\Entity\{name}'
                to: '${tests/Unit}\Entity\{name}FactoryTest'
```

Use `classPairs!:` to replace the whole list, or `classPairs!: []` to keep the rule enabled with no pairs.

### Excluding classes

`classPairExclude` lists class name globs that no pair checks, in either direction. `*` matches any text including namespace separators, `?` matches one character, and `${directory}` placeholders are expanded. Because PHPStan merges lists, this is how a project exempts classes from the shipped pair without restating it:

```neon
parameters:
    toolkit:
        classPairExclude:
            - '${src}\Generated\*'
            - '*Interface'
            - '${tests/Unit}\Compatibility\ClassAliasesTest'
```

A pair's own `exclude` list applies to that pair only:

```neon
parameters:
    toolkit:
        classPairs:
            -
                from: '${src}\{name}Interface'
                to: '${src}\Fake\Fake{name}'
                exclude:
                    - '${src}\Legacy\*'
```

A test class that covers a file without a class, or a second test class declared in an existing test file, has no origin and is reported as orphaned. Exclude it by name when it is deliberate.

### Disabling

```neon
parameters:
    toolkit:
        classPair:
            enabled: false
```

## Rector

**Unavailable** — no automatic transformation is provided.

A meaningful counterpart carries behavior of its own. Generating an empty class would only hide the violation.
