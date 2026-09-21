---
description: Authoring and consumption rules for shared container base images. The image bodies, their configuration, and the verification gate live in the base-image repository. App-specific compose services and FPM pool tuning stay in each app. Where the organization publishes no base images, only the consuming half applies, against the upstream official image.
paths:
    - ".dockerignore"
    - "*.dockerignore"
    - "Dockerfile*"
    - "docker-compose*.{yml,yaml}"
    - "compose*.{yml,yaml}"
    - "**/assets/config/.dockerignore"
    - "**/assets/config/*.dockerignore"
    - "**/assets/config/Dockerfile*"
    - "**/assets/config/docker-compose*.{yml,yaml}"
    - "**/assets/config/compose*.{yml,yaml}"
---

# Shared container images

When `<base-images>` is set, a `docker-images` repository publishes that namespace and is the single source for every
container artifact replicable across the organization's apps. Apps consume the published base images and add only their
own dependencies, code, and tuning. This rule covers both roles: authoring a base image inside `docker-images` and
consuming one inside an app. With `<base-images>` unset there is no such repository, § Authoring does not apply, and an
app inherits the upstream official image pinned the same way. Bash inside `RUN` steps, entrypoints, and container
scripts follows the project's Bash script conventions. The repository root layout of a PHP service is owned by
`skeleton-layout`.

## Pre-output checklist

Verify every item before producing any Dockerfile, dockerignore, or compose file.

1. Every app Dockerfile inherits `FROM` a `<base-images>` image pinned to its full versioned tag, never a moving tag,
   never `latest`. With `<base-images>` unset the base is the upstream official image, pinned the same way. See §
   Consuming.
2. No app re-declares what its base already provides, and where no base provides it, the app declares it itself. See §
   What the base provides.
3. A `.dockerignore` sits next to every Dockerfile and secrets never enter a build context copy. See § Build context.
4. Builds authenticate private Composer repositories through a BuildKit secret, never through a baked `ARG` or `ENV`.
   See § Secrets.
5. Base images are multi-target, pinned to an exact upstream tag, and hardened. Service and consumer-facing targets (a
   runtime an app runs, a sidecar, a static server) run non-root and ship a health check. Build/toolchain and
   one-shot/CLI targets (a builder stage discarded in the multi-stage app build, a job that runs once and exits) keep
   root only as a named, justified exception and expose no port. See § Authoring.
6. Compose files declare no `version:` key. Every long-running service has resource limits and a health signal. See §
   Compose.
7. Every Dockerfile, base image or consumer, begins with `# syntax=docker/dockerfile:1` on its first line. See § Syntax.
8. A consumer Dockerfile carries no comment other than that syntax directive. See § Consuming.
9. Layers are lean: one `RUN` per concern with its cache cleaned in the same layer, ephemeral build dependencies deleted
   before the layer closes, `COPY` never `ADD`, and dependency layers before code layers. See § Lean layers.

## Syntax

Every Dockerfile begins with `# syntax=docker/dockerfile:1` as its literal first line, the base images in
`docker-images` and the thin consumer files in an app alike. It pins the stable BuildKit frontend so build-time mounts,
secrets, and heredocs parse identically on every machine and in CI. The directive is the one line that is not a build
instruction and, in a consumer Dockerfile, the one line that is not a comment-free instruction either.

## What the base provides

A base bakes in the directives below, and the prohibition on restating them holds only where a base actually provides
them. With `<base-images>` unset there is no such base, so the app Dockerfile inherits the upstream official image at an
exact tag and declares them itself: the non-root `USER` with its `WORKDIR` owned by that user, the `HEALTHCHECK`, the
`STOPSIGNAL SIGQUIT` and exec-form entrypoint, the `php.ini-production` hardening, the OPcache tuning per environment,
and the extensions it needs. The invariant is that the runtime image runs non-root, ships a health check, shuts down
gracefully, and is hardened. Which layer declares it is the binding.

The PHP base (`<base-images>/php`, four targets) bakes in everything below, and while it does, an app Dockerfile that
restates any of these is wrong:

- Extensions: the official image set plus `bcmath` and `pdo_mysql`, with no leftover build dependencies.
- Non-root runtime: PHP-FPM runs as `www-data`, `WORKDIR /var/www/html` owned by it.
- Hardening: `php.ini-production` active, `expose_php` and `display_errors` off.
- OPcache defaults: production tuning in `runtime`, development tuning in `development`.
- A container health check probing the FPM socket, and graceful shutdown via the official `STOPSIGNAL SIGQUIT` and
  exec-form entrypoint.
- Development tooling (Xdebug, Composer, bash) in the `development` target only.
- A `cli` tooling variant (Composer, Xdebug coverage, git, docker CLI) consumed by every app Makefile's `PHP_IMAGE`,
  never by a Dockerfile.

The nginx sidecar base (`<base-images>/nginx`, targets `runtime` and `development`, plus a `static` server) bakes the
site configuration (security headers, gzip, PHP fastcgi wiring), a liveness health check, and JSON access logging in
production. The upstream address is its single runtime parameter, the `PHP_UPSTREAM` environment variable (defaults to
the same-task address `127.0.0.1:9000`). Where that family is absent, whether because the namespace publishes no nginx
base or because there is no namespace, the sidecar is the upstream official nginx image at its `<major>.<minor>` tag
(§ Compose) and the app
supplies that site configuration itself under `docker/nginx/`, which `skeleton-layout` then expects instead of treating
as an exception.

The migration base (`<base-images>/flyway`, single `runtime` target) bakes the Flyway entrypoint script and prunes the
driver jars no service uses. An app's migrate service consumes it directly in the compose file, mounting only
`database/migrations/` and passing the `DATABASE_*` environment variables. Where that family is absent, whether because
the namespace publishes no Flyway base or because there is no namespace, the migrate service runs the upstream official
Flyway image at its `<major>.<minor>` tag (§ Compose), with the same mount and the same variables, and any entrypoint
it needs lives under
`docker/flyway/`.

## Stage vocabulary and tags

Five stage names are the closed vocabulary. Each names what the image is, and a family ships only the stages its role
calls for.

- **`builder`**. The compilation base an app inherits via `FROM`. It carries the toolchain that installs dependencies
  and compiles them, and the multi-stage app build discards it. Families: php, node.
- **`runtime`**. The production image the app runs (PHP-FPM non-root, the nginx sidecar non-root on 8080, the Flyway
  one-shot job). Families: php, nginx, flyway.
- **`cli`**. Command-line tooling (the PHP CLI SAPI) for one-shot commands. Family: php.
- **`static`**. The nginx server for a frontend's pre-built assets, non-root on 8080. Family: nginx.
- **`development`**. A variant of `runtime` built `FROM runtime`, adding local development tooling. It runs on a
  developer's machine and is never deployed. It is subordinate to `runtime`, not a peer of it. Families: php, nginx.

Every published tag is `<upstream-minor>-<role>-<semver>`, where `role` is identical to the stage name. There is no bare
tag: a role always sits between the upstream minor and the semantic version. Version tags are immutable, so any input
change bumps the family `VERSION`. `latest` is forbidden. Each stage also carries one floating convenience alias
`<upstream-minor>-<role>` that tracks its latest patch, for local use only, and an app always pins the full versioned
tag.

## Version layer

Each family that pins an upstream keeps one build unit per upstream minor or major, under a `<upstream-minor>/`
subdirectory (`images/runtimes/php/8.5/`, `images/runtimes/node/24.18/`, `images/infrastructure/nginx/1.29/`,
`images/infrastructure/flyway/12.9/`). The subdirectory segment is the exact minor (or major, for a family versioned by
major) read from the Dockerfile `FROM` pin. A build unit is self-contained: its own `Dockerfile`, `VERSION`,
configuration, baked scripts, and `scripts/smoke`, with a semantic version that advances independently of every other
upstream version. PHP 8.5 can sit at `1.3.0` while a future PHP 8.6 sits at `1.0.0`.

Two kinds of upstream change move differently:

- **Minor or major bump** (PHP 8.5 to 8.6, Flyway 12 to 13). The Dockerfile and its configuration diverge (a new pin, a
  renamed extension, a changed build flag, a new `.ini` directive), so the new version is a new sibling subdirectory
  that starts its own `VERSION` at `1.0.0`. The two lines publish in parallel and coexist, and each app migrates on its
  own schedule. Nothing in the old subdirectory changes.
- **Patch bump** (PHP 8.5.7 to 8.5.8). The upstream pin and the family `VERSION` both move inside the same subdirectory.
  No new directory appears, and the floating alias tracks the new patch.

The invariant that stands: a new upstream minor or major starts a new sibling `<upstream-minor>/` subdirectory with its
own `VERSION` at `1.0.0`, honoring the 7-day cooldown on the upstream pin. The step-by-step wiring (Makefile build,
lint, scan, audit, efficiency, smoke, publish) lives in an authoring skill, not this rule. A scaffold family with no
pinned upstream (like `images/runtimes/go/`) has no build unit and no `<upstream-minor>/` subdirectory yet. The
subdirectory is born with its first Dockerfile.

## Consuming

An app consumes the base in exactly two thin files:

- `Dockerfile.dev`: `FROM <base-images>/php:<minor>-development-<V>` plus a `COPY` of the app.
- `Dockerfile.prod`: a builder stage `FROM <base-images>/php:<minor>-builder-<V>` running `composer install`, then a
  runtime stage `FROM <base-images>/php:<minor>-runtime-<V>` copying the built app with `--chown=www-data:www-data`.

With `<base-images>` unset both files inherit the upstream official PHP image at an exact tag on `<runtime-minor>`, keep
the same two-stage shape, and additionally carry the directives § What the base provides lists. They stop being thin,
and the exact pin, the builder/runtime split, and the `--chown` all still hold.

An app adds only its own dependencies, code, and tuning (for example an FPM pool sizing conf under `docker/php/`).
Dependency layers come before code layers so the cache survives code changes. The canonical file bodies live in the
php-creating-skeleton skill and the usage contract is documented in the base-image repository README.

Whichever file names the tag, the image has to reach the local daemon before the build runs, and it always gets there by
being pulled from the registry. An app never builds a base image locally, not even where a checkout of the base-image
repository sits beside it. A tag is immutable and names published bytes, while a local build names whatever that
checkout happens to hold, so two machines on the same tag would stop agreeing and the pin would document nothing. The
pull is Docker's own, triggered by the `FROM` line or by a runner naming the image, and it covers every image the stack
consumes (the app base, the nginx sidecar, the Flyway migration image) with no `Makefile` target in between. Building
those images is the base-image repository's own job, and publishing them is what makes them reachable here.

A consumer Dockerfile carries no comment. The only line that is not a build instruction is the
`# syntax=docker/dockerfile:1` directive. A consumer file is short and does one obvious thing per line, so a comment
would only restate the instruction. The reasoning behind each step lives in the base image it inherits and in this rule,
never copied into the app.

`<base-images>` resolves per family, and every sentence below is read against the family it names rather than against
the namespace as a whole. A namespace publishing a PHP base and no nginx base is set for the app image and absent for
the web sidecar at the same time, which is `CLAUDE.md` § Capability absence applied one container at a time. Asking only
whether the namespace exists gets the sidecar wrong.

The web sidecar is consumed directly in the compose file. Where an nginx family exists it is
`image: <base-images>/nginx:<minor>-development-<V>` with `PHP_UPSTREAM` pointing at the app service
(`<service-name>:9000`), and no nginx configuration file lives in the app unless it deliberately overrides the base.
Where none exists the sidecar takes the upstream official image at its `<major>.<minor>` tag (§ Compose) and the app
carries the site
configuration under `docker/nginx/`. Either way the app mounts only its `public/` directory, and it keeps its Traefik
labels while `<local-proxy>` is set, publishing its port directly otherwise. The migrate service splits the same way:
`image: <base-images>/flyway:<minor>-runtime-<V>` with no entrypoint override where a Flyway family exists, and the
upstream official image at its `<major>.<minor>` tag where none does, with a read-only mount of `database/migrations/`
and the
`DATABASE_*` variables in both cases.

## Build context

Every Dockerfile has a dockerignore covering at least `.git`, `.env` and `.env.local`, IDE directories, `reports/`,
`tests/`, and the `Dockerfile*` and `docker-compose*` files themselves. The development image ships the host-installed
`vendor/` on purpose, the production context excludes it through a per-Dockerfile override
(`Dockerfile.prod.dockerignore`) so the builder stage installs from the lock file alone.

## Lean layers

Every layer carries only what the image keeps. One `RUN` groups a single concern and cleans up after itself in the same
layer, so nothing it fetched survives into the image:

- Package installations use the no-cache mode of the package manager, and build-only dependencies are installed under a
  virtual group and deleted (`apk add --virtual .build-deps … && … && apk del .build-deps`) before the `RUN` ends. A
  tool removed in a later layer still occupies the earlier one, so the removal and the installation share a layer.
- `COPY` moves files in, never `ADD`. `ADD` also fetches URLs and unpacks archives, which hides what enters the image.
  Fetch explicitly and verify a checksum when a remote artifact is needed.
- Dependency layers come before code layers so a code change does not invalidate the dependency cache. Copy the lock
  file and install, then copy the source.

The efficiency stage of the review gate holds these to fixed thresholds. A widening layer is a signal to prune the
`RUN`, not to relax the threshold.

## Secrets

No secret is embedded in any image layer or versioned compose file. Private Composer repositories authenticate with
`RUN --mount=type=secret,id=composer_auth,env=COMPOSER_AUTH` and the caller passes
`--secret id=composer_auth,env=COMPOSER_AUTH`. In CI the value is built from the read-only registry token the pipeline
carries, the single credential that reads private packages, held as a repository or organization secret and passed in by
`<ci-workflows>`. It reaches the build as a build secret and never as a baked `ARG`, an `ENV`, or a tracked file,
whatever the project happens to name it. Where the project has no such token, no build secret is mounted, because no
private package needs one, and the mount is dropped rather than replaced by a baked `ARG`. The local counterpart of that
same value is the `COMPOSER_AUTH` environment variable forwarded into the tooling container by the `Makefile` runners,
an invariant owned by `php-skeleton-tooling` (§ Private package authentication). Runtime configuration enters through
environment variables (compose `env_file`, the project's secret store in the cloud), never through files baked into the
image. The `.env.local` that `env_file` reads is a tracked file (`skeleton-layout` § `.env` strategy), so it holds
local-only defaults and never a real value. With no secret store a real value has no home in the repository at all, and
it reaches the container through the deployed environment.

## Authoring

This section applies only where the organization runs a base-image repository (`<base-images>` set). With
`<base-images>` unset nothing below is authored anywhere, and this section, § Stage vocabulary and tags, and § Version
layer describe a repository the project does not have. Every other section still holds, § Syntax and § What the base
provides included, the latter as the list the app Dockerfile declares itself.

Inside `docker-images`, every image family lives under `images/`, one directory per family grouped by role: language
toolchains an app image builds `FROM` under `images/runtimes/` (like `images/runtimes/php/`), standalone sidecars under
`images/infrastructure/` (like `images/infrastructure/nginx/` and `images/infrastructure/flyway/`). Each family that
pins an upstream holds one self-contained build unit per upstream minor or major under a `<upstream-minor>/`
subdirectory (like `images/runtimes/php/8.5/` and `images/infrastructure/flyway/12.9/`), per § Version layer. Each build
unit ships a single `Dockerfile` with the targets it needs (`runtime` always, `development`, `builder`, `cli`, and a
`static` server where the family calls for them), a `VERSION` file with its base semantic version, its configuration or
baked scripts, and a `scripts/smoke` assertion script.

Non-root and a health check are not a blanket property of every target, they are the contract of the targets an app
actually runs. A service or consumer-facing target (a runtime an app runs, a sidecar, a static server) runs as a
non-root last `USER` with its `WORKDIR` owned by that user, ships a `HEALTHCHECK`, and exposes only an unprivileged
port. A build/toolchain target (a builder stage the multi-stage app build discards) or a one-shot/CLI target (a job that
runs once and exits, a local tooling image) may keep root as its last user, but only as an exception named and justified
where it is declared, and it exposes no port. The review gate enforces this split per target: the audit stage keeps the
non-root check active on every service target and exempts only the named root targets, and each `scripts/smoke` asserts
non-root where the target's class requires it. Upstream images are pinned to an exact tag (patch version and distro
release) and follow the 7-day dependency cooldown. Nothing app-specific enters the base-image repository. Every change
runs the review gate (`make review`: lint, build, vulnerability scan, CIS audit, layer-efficiency check, and smoke)
before consumers bump their pins, and any input change bumps `VERSION`. An upstream vulnerability finding that cannot be
fixed locally (a vulnerable jar inside an upstream bundle) is accepted only through a suppression scoped to the single
family it affects, carrying a written justification and the revisit condition, never a repository-wide one. Publication
pushes only immutable versioned tags to the registry, itself provisioned as infrastructure code (through
`<platform-iac>` where the organization has a shared stack) and never created by hand.

## Compose

Tag pinning splits by where the tag is read, and the compose file sits on the permissive side of that split. A `FROM`
line is pinned to its full version because it decides what a built artifact contains, and a moving base silently changes
what ships (§ Consuming). A compose image is built into nothing: this file is the local development stack, no CI job
runs it, and the deployed counterparts of these containers are provisioned elsewhere. An upstream official image
consumed here is therefore pinned to `<major>.<minor>`, which picks up a patch fix on the next `docker compose pull`
rather than on the next pull request, and the upstream minor is a real compatibility boundary. The floor stays a floor:
`latest` and a bare major are still forbidden, because neither states what the service runs on. A `<base-images>` image
keeps its full `<upstream-minor>-<role>-<V>` tag even here, since that version is immutable, a bump is a deliberate
release the CI version guard already gates, and nothing is gained by tracking an alias of it.

No `version:` key (deprecated). Every long-running service declares `cpus` and `mem_limit`. Health signals: services
whose image ships a `HEALTHCHECK` (every `<base-images>` runtime and sidecar target does) inherit it, other long-running
services declare a `healthcheck:` block. With `<base-images>` unset an image ships one only because the app declared it,
so the compose service carries the block unless its own Dockerfile already carries the directive. One-shot jobs (a
Flyway migration container) are exempt. Databases persist on named volumes prefixed with the service name so they can be
purged per service on re-provision. Configuration reaches containers via an `env_file:` entry and never as a value
written into the compose file, so the two never drift apart. Neither of them carries a real credential, because the
compose file and the `.env.local` it points at are both tracked.

## Delegated to owners

- Script content (entrypoints, init hooks, smoke scripts): the shell-scripts rule.
- Repository root layout and `docker/` substructure of a PHP service: the skeleton-layout rule.
- CI workflow structure: the github-workflows rule.
