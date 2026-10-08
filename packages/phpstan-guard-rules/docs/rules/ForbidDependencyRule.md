# ForbidDependencyRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.forbiddenDependency` |
| Scope | Symbol references and file access in analyzed PHP files |
| Configurable | Yes (`forbiddenDependencies`, `dependencyProjectRoot`, `dependencyFileReaders`) |

## Default Policy

Files outside the project-root `example/` and `examples/` directories must not
depend on files inside either directory. Tests receive no exemption. References
originating inside either example directory are allowed, including references
to production code and other examples.

Examples should remain independently editable documentation. Reusing their
classes, bootstrap scripts, or input data in tests makes example changes alter
the tests' prerequisites. Put reusable production code in an allowed source
directory and create test-owned inputs under `fixtures/`.

The distributed configuration is:

```neon
parameters:
    toolkit:
        dependencyProjectRoot: %currentWorkingDirectory%
        forbiddenDependencies:
            -
                from: ['**']
                excludeFrom: ['example/**', 'examples/**']
                to: ['example/**', 'examples/**']
```

Run PHPStan from the project root, or set `dependencyProjectRoot` to the absolute
project directory when invoking PHPStan from another directory. Paths do not
infer their root from namespace names, nested Composer files, or arbitrary
occurrences of `examples` in a filename.

## Custom Boundaries

Each policy has `from` and `to` lists of root-relative glob patterns and an
optional `excludeFrom` list. A dependency is forbidden when its source matches
`from`, does not match `excludeFrom`, and its resolved destination matches `to`.
Exclusions affect only their own policy. Overlapping policies produce one
finding per destination at a reference node.

For example, add a rule preventing production code from reusing test files:

```neon
parameters:
    toolkit:
        forbiddenDependencies:
            -
                from: ['src/**']
                excludeFrom: ['src/TestBridge/**']
                to: ['tests/**', 'fixtures/**']
```

PHPStan merges list configuration, so this adds to the default example boundary.
Use `forbiddenDependencies!:` when deliberately replacing the entire list, or
`forbiddenDependencies!: []` for an empty policy list.

Matching is case-sensitive and anchored at the project root. `*` matches within
one path segment, `**` crosses directory boundaries, `**/` also matches zero
directories, and `?` matches one non-separator character. Separators and `.`/`..`
segments are normalized; existing symlink destinations are also checked.
`examples/**` does not match `examples-backup/` or `vendor/package/examples/`.

## Symbol Dependencies

The rule checks the declaration file PHPStan resolves for:

- class instantiation, static calls, class constants, enum cases, and `::class`;
- inheritance, interfaces, trait use, attributes, and `instanceof`;
- native parameter, return, property, and catch types;
- function calls and global constants, including imported aliases;
- methods and properties with a statically known receiver; and
- dynamic class references when PHPStan knows one constant class name.

Method dependencies use the method's declaring class, including inherited
methods. Unused imports, PHPDoc-only type references, and transitive dependencies
through an otherwise allowed declaration are not independently traversed.

PHPStan must be able to discover the referenced declarations. Composer autoload
metadata, analyzed paths, and `scanDirectories`/`scanFiles` all work. When examples
are excluded from analysis and are not autoloaded, add the existing directories
to `scanDirectories`:

```neon
parameters:
    scanDirectories:
        - examples
```

This discovers symbols without executing the example or analyzing its body.
Include `tests/` in PHPStan's analyzed paths to check test dependencies. An
unknown symbol remains subject to PHPStan's own unknown-symbol diagnostics.

## File Dependencies

All four `include`/`require` forms are checked. Built-in reader registrations
cover `file_get_contents`, `fopen`, `file`, `readfile`, `parse_ini_file`,
`simplexml_load_file`, `hash_file`, `md5_file`, `sha1_file`, and
`SplFileObject::__construct`. Opening a file counts as a dependency regardless
of the requested mode.

Only the registered path argument is checked. Imported function aliases and
reordered named arguments are resolved. A namespaced function merely named
`file_get_contents` does not inherit the built-in registration.

```php
require __DIR__ . '/../examples/bootstrap.php'; // Forbidden from tests/.
file_get_contents(dirname(__DIR__) . '/examples/input.json'); // Forbidden.
file_get_contents(filename: __DIR__ . '/../examples/input.json'); // Forbidden.

echo 'See examples/input.json'; // An explanation, not a file dependency.
file_get_contents(__DIR__ . '/../fixtures/input.json'); // Allowed by default.
```

The path evaluator handles string literals, `__DIR__`, `__FILE__`, concatenation,
built-in `dirname()` with constant arguments, and expressions PHPStan resolves
to one constant string. Absolute local paths and `file:///` paths are supported.
No project code is executed to resolve a path.

Bare relative paths such as `examples/input.json` depend on the runtime working
directory and potentially `include_path`; the rule does not assume that either
equals the analysis project root. Remote URLs, other stream wrappers, unpacked
path arguments, uncertain unions, and paths or call targets only known at runtime
are left unresolved. Variables containing generalized strings, including some
paths assigned from `__DIR__`, may consequently remain unresolved. Use a directly
resolvable path expression when this boundary must be checked.

## Registering File Readers

Add fully qualified function or `Class::method` names, with a zero-based argument
`position` and its named-argument `name`:

```neon
parameters:
    toolkit:
        dependencyFileReaders:
            'App\Config\Loader::load':
                position: 0
                name: path
            'App\readConfig':
                position: 1
                name: filename
```

Registrations support functions, static and instance methods, and constructors
using `Class::__construct`. Inherited methods use their declaring class name.
Names are case-insensitive. These entries extend the built-ins or override the
argument definition of an existing reader. Arbitrary string arguments are never
interpreted as filesystem paths.

## Diagnostics and Disabling

Each error names the source file, destination file, symbol or reader, and matched
target pattern, followed by an instruction to remove the dependency or move the
shared responsibility to an allowed location. A file reference includes non-PHP
data such as JSON fixtures.

The rule follows `toolkit.allRules`, which defaults to `true`. Guard's existing
`phpstan.all-rules` policy checks that shared switch. Disable this rule explicitly
with:

```neon
parameters:
    toolkit:
        forbidDependency:
            enabled: false
```
