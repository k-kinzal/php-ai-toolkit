# SrcUnitTestPairRule

| Property | Value |
|----------|-------|
| Identifiers | `customRules.srcWithoutUnitTest`, `customRules.unitTestWithoutSource` |
| Scope | All PHP files in `src/` and `tests/Unit/` |
| Configurable | Yes |

## What It Detects

### 1. Source files without a matching test (`customRules.srcWithoutUnitTest`)

```
src/
  Service/
    UserService.php      <-- ERROR: no matching test
    OrderService.php     <-- OK
tests/Unit/
  Service/
    OrderServiceTest.php
```

```
Source file "src/Service/UserService.php" requires a matching unit test file
"tests/Unit/Service/UserServiceTest.php" to keep behavior verifiable.
```

### 2. Test files without a matching source (`customRules.unitTestWithoutSource`)

```
src/
  Service/
    OrderService.php
tests/Unit/
  Service/
    OrderServiceTest.php   <-- OK
    LegacyServiceTest.php  <-- ERROR: no matching source
```

```
Unit test file "tests/Unit/Service/LegacyServiceTest.php" requires a matching
source file "src/Service/LegacyService.php" to avoid stale or orphaned tests.
```

## Why This Is an Error

### Missing tests

Every source file should have a corresponding unit test to ensure its behavior is verifiable. AI code generators often create production code without tests, or create tests for some files but not others. This rule catches those gaps at analysis time rather than during code review.

### Orphaned tests

When a source file is renamed or deleted, its test file may be left behind. Orphaned tests waste CI time, create false confidence in coverage metrics, and confuse developers about the project structure.

## How to Fix

### For missing tests

Create the corresponding test file:

```
src/Service/UserService.php
  -> tests/Unit/Service/UserServiceTest.php
```

```php
// tests/Unit/Service/UserServiceTest.php
namespace Tests\Unit\Service;

use App\Service\UserService;
use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    public function testExample(): void
    {
        $service = new UserService();
        self::assertInstanceOf(UserService::class, $service);
    }
}
```

### For orphaned tests

Either delete the stale test file or rename it to match the current source file:

```bash
# If the source was renamed
git mv tests/Unit/Service/LegacyServiceTest.php tests/Unit/Service/ModernServiceTest.php

# If the source was deleted
git rm tests/Unit/Service/LegacyServiceTest.php
```

## Configuration

### Path markers

Customize the source and test directory markers:

```neon
parameters:
    customRules:
        srcMarker: '/src/'
        unitTestMarker: '/tests/Unit/'
```

### Exclude patterns

Exclude files from the source-side check by file name pattern:

```neon
parameters:
    customRules:
        srcUnitTestPairExcludePatterns:
            - '*.generated.php'
            - 'bootstrap.php'
```

### Mapping convention

The rule maps files by mirroring the directory structure and appending/removing `Test` from the filename:

| Source | Test |
|--------|------|
| `src/Foo/Bar.php` | `tests/Unit/Foo/BarTest.php` |
| `src/Service/UserService.php` | `tests/Unit/Service/UserServiceTest.php` |
