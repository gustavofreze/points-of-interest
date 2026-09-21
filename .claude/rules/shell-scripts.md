---
description: Structure, naming, quoting, control flow, and logging rules for Bash scripts shipped inside a service's container assets.
paths:
    - "*.sh"
    - "**/*.sh"
    - "**/scripts/*"
---

# Scripts

Semantic rules for Bash scripts that ship as part of a service's container assets. Those assets sit under `docker/`, one
directory per containerized service (`docker/php/`, `docker/nginx/`, `docker/mysql/`, `docker/flyway/`,
`docker/localstack/`), and a script lives either in that directory's `scripts/` subdirectory or as a single-file utility
directly inside it (`entrypoint.sh`, `migrate.sh`, `generate-*.sh`). This rule applies to scripts that ship with the
service repository itself. Where `<ci-workflows>` is set, scripts living inside that centralized CI/CD repository follow
a separate rule set under its own conventions. With `<ci-workflows>` unset there is no such carve-out, and every script
in this repository is bound by this rule.

## Carve-out: container-provided init hooks

Bootstrap scripts under a containerized service's `initdb.d/` directory (`docker/mysql/initdb.d/`, and any directory
whose contract is owned by the upstream image entrypoint) are exempt from the shebang, `set -euo pipefail`, and `main()`
rules. They are sourced or executed by the image's own entrypoint, not invoked standalone, so they follow the
entrypoint's contract: a `#!/bin/sh` shebang (the image guarantees only POSIX sh), no `set -euo pipefail` and no
`main()` wrapper, with top-level statements running directly. Everything else in this rule (quoting, guard clauses,
naming, American English) still applies. This carve-out covers only init hooks whose execution contract is defined by
the container image. LocalStack `init-ready.d/` provisioning scripts are invoked as standalone Bash and remain bound to
the full rule.

## Pre-output checklist

This file follows the rules-as-checklist format: each numbered item below is the normative statement for that topic.
Some topics (Carve-out, Files, Naming, Logging) carry additional detail in their own section, the rest are complete in
the list. Verify every item before producing any Bash script.

1. Shebang is `#!/usr/bin/env bash`. Container-provided init hooks are exempt. See § Carve-out.
2. `set -euo pipefail` is the first line after shebang. Container-provided init hooks are exempt. See § Carve-out.
3. A header comment block exists after `set -euo pipefail` with name, one-line description, usage, and arguments.
4. A `main()` function exists. Top-level code is limited to sourcing libraries and `main "$@"`. Container-provided init
   hooks are exempt. See § Carve-out.
5. `main "$@"` is the last line of the file.
6. Libraries are sourced with `source "$(dirname "$0")/../lib/<name>.sh"` (relative to the script's own location).
7. Arguments are validated early with guard clauses (`local x="${1:?Usage: ...}"`).
8. All variables are double-quoted: `"${variable}"`, `"$@"`.
9. Conditionals use `[[ ]]`, never `[ ]` or `test`.
10. Command substitution uses `$(command)`, never backticks.
11. No `else` or `elif`. Use guard clauses with early exit.
12. `case` is used over chained `if` for multiple conditions.
13. `cd` is always paired with `|| exit 1`.
14. Cleanup logic uses `trap` on `EXIT`.
15. Constants are declared with `readonly` and ordered by name length ascending.
16. Functions are ordered: helpers first, `main` last.
17. No abbreviations in identifiers.
18. American English in all identifiers, comments, and documentation.

## Files

- Executable scripts under a containerized service's `scripts/` subdirectory (`docker/php/scripts/`) use **no `.sh`
  extension** when invoked as commands (`build-cache`, `wait-for-mysql`).
- Library scripts sourced into other scripts use the **`.sh` extension** (`log.sh`, `config.sh`).
- Single-file utilities sitting directly in a containerized service's own directory under `docker/` (entrypoint,
  migration runner, key generators) keep their `.sh` extension because they are invoked by file path from the Dockerfile
  or compose file (`entrypoint.sh`, `migrate.sh`, `generate-jwt-keys.sh`).
- File names use `kebab-case`.

The three `paths:` globs above map onto those three kinds and none of them is redundant, which is worth stating because
two of them look it. `*.sh` and `**/*.sh` are disjoint: the first matches only at the repository root and the second
only below it, so dropping either leaves a whole depth unguarded. `**/scripts/*` is the only glob that reaches the first
kind at all, because an extension-less executable matches no `.sh` pattern by construction, and it covers both the
service tree and the skill's `assets/` templates. A glob that matches nothing today is dormant, not dead, and it is the
coverage the day someone adds the directory.

## Naming

- Functions: `snake_case`, descriptive verbs (`fetch_config`, `resolve_image_tag`).
- Local variables: `snake_case`, declared with `local`.
- Constants and exported variables: `SCREAMING_SNAKE_CASE`.

## Logging

- A sourced `log.sh` library exposing `log_info`, `log_error`, `log_warn` is the recommended idiom once a script
  accumulates three or more log statements or runs in more than one context. Where it is used, informational output goes
  to `stderr` (`>&2`) and only machine-readable output goes to `stdout`.
- Short single-purpose init scripts may instead use bare `echo` with a bracketed prefix
  (`echo "[<context>-init] <message>"`) to `stdout`. This is the lightest idiom for a script whose only side effect is a
  sequence of provisioning calls.
