# Contributing

## Setup

```bash
composer install
```

Tests run against an in-memory SQLite database via
[Orchestra Testbench](https://packagist.org/packages/orchestra/testbench) - no
external database or Telegram credentials needed.

## Before opening a PR

```bash
composer test      # Pest
composer lint       # Pint, check only
composer format      # Pint, fixes in place
composer analyse      # PHPStan, level max
```

All four run in CI across the PHP/Laravel matrix in
`.github/workflows/tests.yml`; a PR won't merge red.

If PHPStan flags something in code you didn't touch, that's pre-existing
debt frozen in `phpstan-baseline.neon` - leave it alone unless your change
specifically improves it. New code should not add to the baseline; if
`composer analyse` fails on a line you wrote, fix the type rather than
regenerating the baseline to hide it.

## Commit style

This repo uses [Conventional Commits](https://www.conventionalcommits.org/)
(`feat:`, `fix:`, `refactor:`, `chore:`, `docs:`, `test:`, `ci:`), with a
`!` after the type and a `BREAKING CHANGE:` footer for anything that breaks
the public API. `git log` is the best reference for the tone and level of
detail expected in a commit body - explain *why*, not just what changed.

Prefer one focused commit per logical change over one large commit bundling
several concerns.

## Versioning

The package is pre-1.0: a minor version bump may include breaking changes
while the API stabilizes. Breaking changes still need a `!`/`BREAKING
CHANGE:` marker and a CHANGELOG entry so downstream consumers can find them
at a glance.

## Companion packages

Essence is the core framework; packages like `tbe-billing`, `tbe-settings`,
and `tbe-user-management` extend it via the same bus/handler pattern. If
your change affects a hook or interface those packages rely on (bus
signatures, handler base classes, config keys), call that out explicitly in
the PR description.
