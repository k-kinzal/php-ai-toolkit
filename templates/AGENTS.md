<!-- NOTE: You do not have permission to overwrite this file. Please ask a human operator to perform the changes for you. -->

# AGENTS

{{PROJECT_DESCRIPTION}}

## Project Tradeoff Sliders

- Scope     ●————————→ HIGH — Deliver the full intended scope; do not cut corners or skip requirements.
- Quality   ●————————→ HIGH — Quality is the top priority. Correctness, test coverage, and strict static analysis come first.
- Time      ←————————● LOW — There is no deadline pressure. Take the time needed to get it right.
- Cost      ←————————● LOW — Resource constraints are not a concern. Invest in doing things properly.

When in doubt, prioritize quality over everything else. It is better to ship less with confidence than to ship more with uncertainty.

## Tech Stack

{{TECH_STACK}}

## Coding Rules

- All code MUST pass PHPStan at level max with strict rules before committing
- All code MUST pass PHP-CS-Fixer checks before committing
- All test classes MUST have `#[CoversClass(...)]` attribute
- When lint errors include fix instructions (Tip:), follow them exactly
- Every public class in `src/` must have a corresponding test in `tests/Unit/`
- No `@phpstan-ignore` comments — fix the underlying type issue instead
- No `//` inline comments — use PHPDoc comments when documentation is needed
- No suppression of errors, warnings, or notices with `@` operator

{{ADDITIONAL_CODING_RULES}}

## Directory Structure

```
{{DIRECTORY_STRUCTURE}}
```

## Document References

{{DOCUMENT_REFERENCES}}
