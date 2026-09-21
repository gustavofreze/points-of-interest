---
description: Repository root layout, .env strategy, docker/ substructure, and per-environment asset naming for PHP service repositories.
paths:
    - "docker-compose.yml"
    - "Dockerfile*"
    - "docker/**"
    - ".env*"
    - "**/*.env"
    - "**/*.env.*"
    - ".gitignore"
    - "**/assets/config/docker-compose.yml"
    - "**/assets/config/Dockerfile*"
    - "**/assets/config/.gitignore"
    - "**/assets/docker/**"
---

# Project layout

Defines the layout of the repository at the root level: which files and directories live at the root, the `.env`
strategy, the `docker/` substructure, and per-environment asset naming. The contents of `src/` (PHP application code),
`database/migrations/` (SQL migrations), `.github/workflows/` (CI workflow files), the root tool-configuration files
(`composer.json`, `phpcs.xml`, `phpunit.xml`, `infection.json.dist`, `Makefile`, `.editorconfig`), and the README and
`docs/` tree are each governed by their own rules, not this one. Dockerfile bodies, dockerignore content, and base-image
inheritance are owned by the `docker-images` rule.

## Pre-output checklist

1. Every file and directory sits where the canonical layout places it, and no prohibited root path (`bin/`, `scripts/`,
   `config/`, `.env.example`, `.env.secrets`, `database/init/`, `database/scripts/`) is present. See § Repository root
   canonical layout and § Prohibited paths and patterns.
2. The single environment file is `.env.local`, it is committed, and it carries local-only defaults and no real
   credential. No other environment file exists, verified by filtering `git ls-files` to the `.env` names and getting
   that one line back. See § `.env` strategy.
3. Container assets group by containerized service under `docker/`, and every subtree (`php/`, `nginx/`, `mysql/`,
   `flyway/`, `localstack/`) exists exactly when it has a file of its own to carry, none of them mandatory.
   `<base-images>` resolves per family, so a family that namespace does not publish leaves its subtree expected. See §
   `docker/` substructure.
4. Any asset whose content differs by environment carries a `.dev` or `.prod` suffix before the extension, never a bare
   basename. See § Per-environment asset naming.

## Repository root canonical layout

```
<repo>/
├── .dockerignore                        # Build-context filter, used by Dockerfile.dev.
├── .editorconfig
├── .env.local                           # Committed. Local-only defaults, never a real credential.
├── .gitignore
├── composer.json
├── composer.lock                        # Committed.
├── docker-compose.yml
├── Dockerfile.dev
├── Dockerfile.prod
├── Dockerfile.prod.dockerignore         # Build-context filter override for Dockerfile.prod.
├── infection.json.dist
├── Makefile
├── phpcs.xml
├── phpunit.xml
├── README.md
├── .github/
│   └── workflows/                       # CI workflow files.
├── .<vendor>/
│   └── pipeline.yml                     # Optional. Present only when a pipeline default must change.
├── database/
│   └── migrations/                      # Flyway SQL files.
├── docker/
│   ├── php/                             # Mandatory.
│   ├── nginx/                           # When applicable (base override, or expected if <base-images> unset).
│   ├── mysql/                           # When applicable.
│   ├── flyway/                          # When applicable (base override, or expected if <base-images> unset).
│   └── localstack/                      # When applicable.
├── docs/                                # Documentation tree.
├── ecs/                                 # Only when <deploy-target> is set. Fixed name, never renamed.
│   ├── development/                     # Service-level IaC for development, planned and applied by CD.
│   └── production/                      # Service-level IaC for production, planned and applied by CD.
├── public/
│   └── index.php                        # Front controller / PSR-7 entrypoint.
├── reports/                             # Gitignored. Analysis output (coverage, mutation, lint).
├── src/                                 # PHP application code.
└── tests/                               # Test suites.
```

No `bin/`. No `scripts/`. No `config/`. No `.env.example`. No `.env.secrets`. No `database/init/`. No
`database/scripts/`.

The `ecs/` subtree is present only when `<deploy-target>` is set, and it keeps that directory name whatever the target
is called (never renamed). With `<deploy-target>` unset the repository carries no infrastructure subtree at all, and the
skeleton gate skips those items instead of failing them. The pipeline config file is `.<vendor>/pipeline.yml`, the
canonical spelling owned by `github-workflows` (item 17), and it is optional. With `<ci-workflows>` unset it is
unnecessary, because the service repository owns its workflow outright and an override is edited in place.

## `.env` strategy

Configuration via environment variables (12-Factor App, Factor III). Exactly one environment file exists, `.env.local`,
and it is committed.

### `.env.local` (committed, the single environment file)

Lives at the repository root, **tracked**. These services are proofs of concept, so the file ships with the repository
and a clone runs with no setup step. That is what the tracking buys, and the price is that **the file may carry nothing
but local-only defaults**: a container password the compose stack itself sets, a Compose service name, a port, a schema
name. A signing key, a third-party API token, a private certificate, or any value pointing at a real host is forbidden
in it, exactly as in every other tracked file (`CLAUDE.md` § Global defaults), and no gitignored sibling is introduced
to hold one. A real value reaches a deployed container through that environment and through the project's secret store,
never through this repository.

The consequence to accept deliberately: a per-developer override now edits a tracked file, so it shows up as a working
tree change. That is the trade, and it is the right one only while the values stay worthless. The
`php-creating-env-local` skill produces the file when a service does not ship one yet. Header, verbatim, at the top:

```
# Local defaults for the proof of concept. Committed on purpose, so a clone runs with no setup step.
# Every value here is local-only and reaches nothing outside this machine. Never put a real credential in it.
```

The `README.md` "Environment setup" documents the full variable surface and, because the file is tracked, prints the
literal value of every key rather than hiding any of them behind a placeholder. Hiding a value that sits in a tracked
file two directories away is theatre, and a value that could not be printed had no business being in the file. The
`README.md` states that the repository is a proof of concept and that this is why the values are committed.

### Loading order

Docker Compose interpolation reads `.env.local` through the `--env-file .env.local` flag carried by the `Makefile`
compose invocations (`start`, `stop`), never through an autoloaded `.env`. Services consume it via an explicit
`env_file:` entry marked `required: true`:

```yaml
services:
  app:
    env_file:
      - path: .env.local
        required: true
```

The flag is `true` because the file is committed. `required: false` accepts a missing file in silence and starts the
container with no variables at all, which the application then discovers deep inside itself when
`EnvironmentVariable::from` finds nothing. That was the right trade only while the file was gitignored and a fresh clone
legitimately had none. A tracked file that is missing is a file someone deleted, so the run should stop at
`docker compose` with the path named, which is what `true` does.

Inside the application, variables resolve in the order: process environment, then `.env.local`. The first non-empty
value wins.

### Prohibited

- A bare `.env`. The single environment file is `.env.local`, and a parallel `.env` duplicates values and invites drift.
- `.env.example`. The variable surface is documented in the `README.md`, a parallel example file invites drift, and a
  tracked `.env.local` already shows every key with its value.
- `.env.secrets`. A secret has no home in this repository at all, so a file named for one is forbidden whether it is
  tracked or gitignored.
- Any environment file other than `.env.local`. Verify with `git ls-files`, filtered to the `.env` names: `.env.local`
  is the only line it may print.

## `docker/` substructure

The `docker/` directory groups container assets by containerized service. Each subtree carries the files that the
corresponding container consumes via volume mounts, COPY directives, or init hooks, and each exists exactly when it has
a file to carry. None is mandatory, because git holds no empty directory: a subtree with nothing service-specific in it
is a subtree that cannot exist, so demanding one would be a rule no repository could satisfy.

**`<base-images>` resolves per family, not once for the whole namespace.** It answers "who publishes the base for this
container", and the answer differs by container. A namespace that publishes a PHP base and no nginx base leaves the
nginx side in the same state as a project with no namespace at all, which is what `CLAUDE.md` § Capability absence
describes: the binding is absent for that family, and the rule's unset branch is the one that applies to it. Read each
subsection below against the family it names, never against whether the namespace exists at all.

### `docker/php/` (when the service has PHP assets of its own)

Assets for the PHP-FPM service. Where a `<base-images>` PHP base exists, the entrypoint, the OPcache configuration, and
the development FPM logging defaults live in that image, not here (docker-images rule), and this directory keeps only
what is service-specific. Where none exists, there is no shared base to carry them, so they live here and the app
Dockerfile copies them in, which makes the directory expected. The service-specific contents either way, each of them
conditional, and all three absent leaving no directory at all:

- `fpm-logging.prod.conf` (when the service tunes production pool sizing or logging).
- `generate-*.sh` generator scripts (typically development certificates or keys).
- `certificates/`: sensitive assets (`*.pem`, `*.key`, `*.crt`), **gitignored**.

### `docker/nginx/` (when applicable)

Where a `<base-images>` nginx base exists, the site configuration lives in that image (`<base-images>/nginx`,
docker-images rule), consumed directly by the compose file with the `PHP_UPSTREAM` environment variable, and this
directory exists only when the service deliberately overrides it, which is the exception. Where none exists, whether
because the namespace publishes no nginx family or because there is no namespace, the default inverts: nothing carries
that configuration, so this directory is expected and holds the site configuration the sidecar mounts from an upstream
official image pinned to its `<major>.<minor>` tag (`docker-images` § Compose).

A `default-ssl.conf` is prohibited when TLS is terminated upstream by a reverse proxy, which is the case locally
whenever `<local-proxy>` is set (that checkout is what runs the local Traefik) and in the cloud behind an ALB or a CDN:
configuring TLS at the nginx layer then duplicates the surface and creates drift. With `<local-proxy>` unset and nothing
terminating TLS in front of the service, the service terminates it itself and the file is permitted.

### `docker/mysql/initdb.d/` (when applicable)

Present only when the service operates its own MySQL container. Holds bootstrap SQL executed once on first container
start by the MySQL image's `docker-entrypoint-initdb.d` hook. Schema migrations never live here, they belong to
`database/migrations/` and run via Flyway.

### `docker/flyway/` (when applicable)

Where a `<base-images>` Flyway base exists, the migration entrypoint lives in that image (`<base-images>/flyway`,
docker-images rule), consumed directly by the compose file with the `DATABASE_*` environment variables, and this
directory exists only when the service deliberately overrides the baked entrypoint, which is the exception. Where none
exists, whether because the namespace publishes no Flyway family or because there is no namespace, the default inverts:
the upstream official Flyway image bakes no such entrypoint, so this directory is expected unless the migrate service
drives the image through its own flags alone, which needs no entrypoint and therefore no directory.

### `docker/localstack/init-ready.d/` (when applicable)

Present only when the service depends on a LocalStack container for AWS service mocking during local development. The
directory name `init-ready.d` is literal, required by LocalStack's init hook contract. Do NOT rename it.

## Per-environment asset naming

Files that vary between development and production use a suffix BEFORE the file extension:

| File kind                     | Development              | Production              |
|:------------------------------|:-------------------------|:------------------------|
| Dockerfile                    | `Dockerfile.dev`         | `Dockerfile.prod`       |
| PHP-FPM logging configuration | (shared base image) [^1] | `fpm-logging.prod.conf` |

[^1]: Only while a `<base-images>` PHP base exists. Where none does, no shared base carries the development FPM logging
defaults, so they live under `docker/php/` and take the `.dev` suffix like any other asset that differs by environment.

Where a `<base-images>` nginx base exists, the site configuration moved into it for both environments and no longer
appears at the service root. A service that overrides it uses the same `default.dev.conf` and `default.prod.conf` naming
under `docker/nginx/`. Where none exists that configuration lives under `docker/nginx/` outright, and the same holds for
the FPM logging defaults under `docker/php/` against their own family, each carrying the `.dev` or `.prod` suffix
whenever its content differs by environment.

The suffix `.dev` or `.prod` is the second-to-last segment of the basename. A bare basename without an environment
suffix is prohibited for any asset whose content differs by environment. Files identical across environments do NOT
carry a suffix (e.g., `generate-certificates.sh`).

## Prohibited paths and patterns

The patterns below are prohibited at the repository root. Each has a reason rooted in an external standard or in
repeated drift observed across services.

| Pattern                               | Reason                                                                                                                                                                               |
|:--------------------------------------|:-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `bin/`                                | Operational entrypoints live in the `Makefile` (workflow shortcuts) or in a container's own directory under `docker/` (container-specific scripts).                                  |
| `scripts/` (at root)                  | Workflow shortcuts live in the `Makefile`. Container-specific scripts live under the `scripts/` subdirectory of that same container directory (permitted, owned by their container). |
| `config/`                             | 12-Factor App (Factor III), configuration is read from environment variables, not from per-environment files committed to the repo.                                                  |
| `config/<env>/`                       | Same as above.                                                                                                                                                                       |
| `.env.example`                        | The variable surface is documented in the `README.md`. A parallel example file invites drift.                                                                                        |
| `.env.secrets`                        | A secret has no home in this repository at all, so a file named for one is forbidden whether it is tracked or gitignored.                                                            |
| Bare `Dockerfile` (no `.dev`/`.prod`) | Every Dockerfile is environment-specific. The naming convention makes the target explicit at the `docker build -f` call site.                                                        |
| `database/init/`                      | Bootstrap SQL belongs to the database container, not to the schema migration history. It lives in `docker/mysql/initdb.d/`.                                                          |
| `database/scripts/`                   | Migration entrypoint scripts belong to the migration container, in its base image or under `docker/flyway/`.                                                                         |
| `report/` (singular)                  | Analysis output is `reports/` (plural). Tooling defaults and the rest of the rule bundle assume the plural.                                                                          |
| Committed `*.pem`, `*.key`, `*.crt`   | OWASP universal rule. Private keys and certificates of any kind are never committed. Generators (`generate-*.sh`) live in `docker/php/`.                                             |

## Canonical references

The external standards anchoring these conventions are listed in the `php-creating-skeleton` skill
(`references/layout-sources.md`). When a question arises that this rule does not answer, defer to the linked source
there.
