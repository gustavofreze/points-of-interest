---
name: php-creating-env-local
description: Create the committed .env.local of a PHP service by deriving variables from tracked sources. Use when bootstrapping a service that ships none, or when `make start` fails on a missing .env.local.
---

# Create env.local

Creates the `.env.local` at the repository root. The variable surface is never invented and never copied from a
template. It is derived from the tracked sources and reconciled against the README table. The repository ships no
`.env.example`, which `skeleton-layout` forbids.

Target: the `.env.local` at the repository root, created only when absent.

## When to use

- Bootstrap a working copy that has no `.env.local`.
- `make start` fails because `.env.local` is missing.

## When NOT to use

- `.env.local` already exists. This skill never reads it and never overwrites it. To add one variable to an existing
  file, the developer edits it by hand.
- Add or rename an environment variable in the code. Change the code first, document the row in the `README.md`, and
  only then run this skill on a fresh clone.
- Fill a secret value. This skill never writes one.

## Pragmatic stance

Fail closed. Any divergence between the code and the README aborts with a report naming the offending keys. A file that
silently omits a variable required by `EnvironmentVariable::from` fails later at startup with a worse message.

## Rules applied

- `skeleton-layout`: the `.env` strategy, the prohibited files, and the verbatim header.
- `php-writing-documentation`: the shape of the README `### Environment variables` table, which this skill consumes as a
  source.

## Sources

Build all five sets before writing anything. Apply each scan to every matching file, not just the first one found.

| Set | Origin                                             | Role                       |
|:----|:---------------------------------------------------|:---------------------------|
| `L` | `src/**/*.php`, `from(name: 'LITERAL')`            | derived keys               |
| `D` | `src/**/*.php`, `from(name: <non-literal>)`        | patterns, never expanded   |
| `C` | `docker-compose.yml`, `${VAR}` interpolations      | compose-time keys          |
| `T` | `phpunit.xml`, the `<php>` block                   | test-only, excluded        |
| `R` | `README.md`, the `### Environment variables` table | keys, descriptions, values |

**`L`, literal keys.** Every `EnvironmentVariable::from(name: '<LITERAL>')` call under `src/`. The `php-architecture`
Settings invariant forbids `fromOrDefault` and any code-side fallback, so every literal found is a required variable.

**`D`, dynamic patterns.** Every `EnvironmentVariable::from(name: <expression>)` call under `src/` whose argument is not
a string literal (`sprintf`, concatenation, a variable). Record the format string and convert it to a pattern: `%s`
becomes `[A-Z0-9_]+`, `%d` becomes `[0-9]+`. Never expand a pattern into a concrete key. The concrete instances are
unknowable from the code.

**`C`, compose keys.** Every `${VAR}` interpolation in `docker-compose.yml`. A literal value under an `environment:`
mapping is not a key.

**`T`, test keys.** Every `name` attribute in the `<php>` block of `phpunit.xml`. PHPUnit provides these, they never
belong in `.env.local`.

**`R`, README rows.** Every row of the `### Environment variables` table, read as the triple (variable, description,
development value).

## Reconciliation

Apply all five rules, not just the first that matches.

1. `k` in `L` or `C`, and `k` absent from `R`. **Abort.** Report the key and stop. Document it in the README first.
2. `k` in `R`, absent from `L` and `C`, and matching no pattern in `D`. **Abort.** Report the key as a stale README row.
3. `k` in `R` matching a pattern in `D`. **Emit.** The concrete instance of a dynamic key exists only in the README, and
   the README is authoritative for it.
4. `k` in `T` only. **Skip**, silently.
5. A pattern in `D` with no matching row in `R`. **Warn** and continue. Emit the pattern as a trailing comment. Zero
   configured instances is legitimate.

## Grounding

```
Use only the five sources above. Do not rely on general knowledge, on framework defaults, or on
variables you have seen in other services. If a variable's value is not stated in the README table,
write the key with an empty value. If you are unsure whether a token is a variable name, say "I
don't have enough information to answer that confidently", report it, and abort instead of guessing.
```

## Value derivation

- The `Development value` cell holds a concrete value: emit `KEY=<value>`, unquoted, unless the value contains a space
  or a `#`.
- The cell holds the dash placeholder or is empty: emit a comment line carrying the `Description` verbatim, then `KEY=`
  with no value. The file is tracked, so that key stays empty in it forever. Whatever fills it is a real value, and a
  real value reaches the container through the deployed environment (`skeleton-layout` § `.env` strategy), never through
  this file.
- Never copy a value from another service and never read an existing `.env.local`.

## Output format

The header is verbatim from `skeleton-layout`, followed by one blank line, followed by one entry per README row **in
README order**. No section headers. No blank line between entries.

The sample below is filled with the established scaffold vocabulary. Resolve `<service-name>`, `<service_name>`, and
`<SERVICE_NAME>` from `composer.json` as you write, and take every key and value from the README table, never from this
sample.

```
# Local defaults for the proof of concept. Committed on purpose, so a clone runs with no setup step.
# Every value here is local-only and reaches nothing outside this machine. Never put a real credential in it.

DATABASE_HOST=<service-name>-adm
DATABASE_PORT=3306
DATABASE_NAME=<service_name>_adm
# Database user, local value provisioned in .env.local
DATABASE_USER=
# Database password, local value provisioned in .env.local
DATABASE_PASSWORD=
# Bearer token for the <service-name>-facts dispatch, mirroring the secret store value in production
<SERVICE_NAME>_FACTS_TOKEN=
# NOTIFICATION_TEMPLATE_[A-Z0-9_]+ (dynamic key, no README row)
```

## Assembly order

1. Confirm `.env.local` is absent. If it exists, stop and report, do not read it.
2. Build `L`, `D`, `C`, `T`, and `R`, scanning every file in each source.
3. Reconcile. On rule 1 or rule 2, abort and list every offending key at once.
4. Emit the file in README order.
5. Check the completeness gate.

## Completeness gate

- [ ] `.env.local` did not exist before this skill ran.
- [ ] Every key in `L` and in `C` has a row in `R`.
- [ ] Every row in `R` is in `L`, is in `C`, or matches a pattern in `D`.
- [ ] No key from `T` was emitted.
- [ ] Every sensitive cell produced an empty value, never a guess.
- [ ] The header matches `skeleton-layout` verbatim.
- [ ] `git ls-files | grep -E '(^|/)[^/]*\.env(\.|$)'` reports `.env.local` and nothing else.
- [ ] No value was copied from another service.

## Does not do

- Does not read, edit, or overwrite an existing `.env.local`.
- Does not write a secret value.
- Does not create `.env`, `.env.example`, or `.env.secrets`.
- Does not run `make start`.
