---
name: php-creating-skeleton
description: Create or restore a service's static skeleton (repository layout, config and tooling files, the docker substructure, the public entrypoint, the CI caller, and the ecs IaC stacks). Use when bootstrapping a new service, scaffolding the standard files, or restoring one skeleton file.
---

# Create skeleton

Creates or restores the static skeleton of a service: the repository layout, the config and tooling files, the docker
substructure, the public entrypoint, the CI caller, and the ecs IaC stacks. Routes to the layout and tooling rules. The
canonical body of each skeleton file does not derive from a rule: it is copied verbatim from this skill's `assets/`
tree, never written from memory. The baseline src rules (`php-architecture`, `php-code-style`, `php-design-principles`)
apply only to any produced file under `src/`.

Target: one file to restore, or the whole skeleton when none is named.

## When to use

- Bootstrap a new service skeleton, scaffold the standard files, or restore one of those files.

## When NOT to use

- Add application code (domain, use cases, ports, adapters): use the matching layer skill (`php-creating-application`,
  `php-creating-driven`, `php-creating-driver`, `php-creating-query`).
- Change the database schema (a table, a column, a backfill, a seed): use `creating-migration`, the skeleton never
  carries schema.
- The skeleton is already in place and intact: do not rescaffold the whole tree to fix one concern, restore only the
  single file the request names.

## Pragmatic stance

Restore the smallest scope the request needs: one file when one is named, the whole skeleton only when none exists yet.
Every skeleton body is copied verbatim from this skill's `assets/` tree and edited at its placeholders alone, never
reconstructed from memory.

## Rules applied

Load and follow, in this priority:

- `skeleton-layout`: the repository layout, docker substructure, `.env` strategy, and what is service-specific.
- `docker-images`: the shared base image contract every Dockerfile and dockerignore must satisfy.
- `shell-scripts`: the Bash conventions for the shell scripts in the docker substructure.
- `php-skeleton-tooling`: the platform standard each config file must satisfy.
- `github-workflows`: the CI workflows.
- the `php-writing-documentation` skill: the service README. `web-documentation` owns the cross-file sync and prose
  style.

## Shapes and how-to (read when scaffolding)

The literal composition-root shapes that `php-architecture` used to inline live here, surfaced only when this skill
runs. Each is the source of truth for the matching root file under `src/`:

- `references/folder-skeleton.md`: the canonical `src/` and `tests/` folder tree.
- `references/layout-sources.md`: the external standards anchoring the `skeleton-layout` rule.
- `references/routes.md`: the `src/Routes.php` class (constructor middleware chain and grouped `register()`).
- `references/dependencies.md`: the `src/Dependencies.php` composition-root `definitions()` shape.
- `references/settings.md`: the app-wide `<Name>Settings` value object (byte-for-byte `DatabaseSettings`, apart from the
  `<RootNamespace>` placeholder on its namespace line).
- `references/settings-dynamic-key.md`: the Settings variant with a dynamic env-var key lookup method.

## Assembly order

1. Identify the target: one file to restore, or the whole skeleton.
2. **Elicit, before writing anything.** `CLAUDE.md` § Reading the project resolves most values by reading a file, and on
   a green field this skill is the thing that writes those files. Only `<github-org>` and `<spec-root>` resolve from
   outside them, so seventeen of the nineteen rows would read as absent, and § Capability absence would have every rule
   take its absent branch: no auth middleware, no infrastructure subtree, upstream base images, no messaging, silently
   and by the rules. Restoring one file into an existing repository does not have this problem, so skip this step there
   and read the values from the files that already exist.

   For a new service, ask for each value the scaffold is about to bake in, and take `none` for an answer: the service
   slug and its PascalCase root namespace, the Composer vendor, the runtime minor, whether the organization publishes
   base images and under what namespace, whether a shared CI workflow repository exists, whether there is an
   infrastructure subtree and of what kind, in which region, and under which public apex domain (none is an answer there
   too, and it drops the public DNS wiring), whether the local stack joins a shared network behind a shared proxy and at
   what host suffix, which packages supply the auth and idempotency middleware, and which broker the outbox publishes
   to. Record the answers and write them into the files as you go, so the reading table resolves against a real
   repository from the first commit onward.
3. Lay out the repository and docker substructure per the layout rule.
4. Copy each skeleton file verbatim from the matching `assets/` subtree (`assets/config/`, `assets/docker/`,
   `assets/public/`, `assets/github/`, `assets/ecs/`), never from memory.
5. Replace every placeholder above with the service-specific value.
6. Copy the `.github/` files from `assets/github/`: exactly one CI workflow, `dependabot.yml`, and
   `copilot-instructions.md`. Which CI workflow to use turns on whether a shared workflow repository exists, and the
   choice is made by copying one template and never by commenting out part of another:
    - With `<ci-workflows>` set, copy `assets/github/workflows/ci.yml`. It is the thin caller, it calls the reusable
      workflow published by `<ci-workflows>`, and it pins the ref the caller template ships.
    - With `<ci-workflows>` unset there is nothing to call, and a `uses:` target in a repository that does not exist
      fails the run with workflow-not-found. Copy `assets/github/workflows/ci-standalone.yml` to
      `.github/workflows/ci.yml` instead. It is self-contained and runs the same make targets the reusable workflow
      runs, so the gate is the same either way.

   The github-workflows rule applies to whichever of the two is shipped. Add the README per the documentation rule.
7. Check each file against the tooling rule. With `<deploy-target>` set, run `terraform fmt` on the `ecs/` stacks, the
   templates align on the placeholder widths and substitution shifts every column. With `<deploy-target>` unset there is
   no such subtree and the step does not apply.

## Completeness gate

- [ ] Repository layout and docker substructure per the layout rule.
- [ ] Both Dockerfiles inherit `FROM` a `<base-images>` image pinned to its full versioned tag, and both dockerignores
      are in place (docker-images rule). With `<base-images>` unset the base is the upstream official image, pinned the
      same way.
- [ ] `docker-compose.yml` came from the template, `<database-host-port>` does not collide with another service on the
      local Docker host.
- [ ] The `public/` entrypoint, the `.github/workflows/ci.yml` workflow, `.github/dependabot.yml`, and
      `.github/copilot-instructions.md` came from their `assets/` templates (all apply to every service).
- [ ] `.github/workflows/ci.yml` carries exactly one job and no commented-out alternative. It is the caller from
      `assets/github/workflows/ci.yml` when `<ci-workflows>` is set, and the self-contained workflow from
      `assets/github/workflows/ci-standalone.yml` otherwise. Never both, and never one of them parked in comments beside
      the other.
- [ ] `docker/php/fpm-logging.prod.conf` is either absent, or present together with its `Dockerfile.prod` `COPY` line
      and its `Dockerfile.prod.dockerignore` unignore. Never one without the other two.
- [ ] `docker/<database-engine>/initdb.d/02-init-cdc.sql` is either absent, or present together with the binlog
      `command:` block on the `<service-name>-adm` compose service. Never one without the other.
- [ ] The `docker/<vendor>/` substructure came from the `assets/docker/` templates only for the vendors this service
      actually uses: `php/fpm-logging.prod.conf` when it tunes production sizing or logging, `<database-engine>/` only
      when it runs its own database container, `<database-engine>/initdb.d/02-init-cdc.sql` only when it has a CDC
      pipeline, `localstack/` and `dispatch/` only when it has the `<messaging-transport>` facts flow (applicability
      owned by the layout rule and the spec). No channel, queue, subscription, or dispatch worker is scaffolded for a
      service without that messaging, and with `<messaging-transport>` unset none of them is scaffolded at all.
- [ ] With `<deploy-target>` set, the `ecs/development/` and `ecs/production/` stacks came from the `assets/ecs/`
      templates, with the environment identity, backend keys, and per-environment defaults kept as the template ships
      them. The `module "service_messaging"` block, the facts outputs, and `dispatch_event_types` are kept only for a
      service that publishes facts, and dropped otherwise. With `<deploy-target>` unset there is no infrastructure
      subtree and this item is skipped, not failed.
- [ ] `.gitignore` satisfies the ignore invariants in the layout rule.
- [ ] Each config file matches the platform standard (tooling rule).
- [ ] No placeholder left in any written file, and no composer constraint left on `*`. Check by NAME against the list
      in § Placeholders, never with a bare `<...>` pattern: the templates legitimately ship XML element names
      (`<testsuites>`, `<coverage>`, `<directory>`) and shell usage strings (`${1:?Usage: create_queue <queue_name>}`)
      that must survive verbatim, and several of them collide with real token names.
- [ ] No literal from the project the template came from survived. Check every written file value by value against what
      THIS project states: the organization slug, the base-image namespace, a registry host, a sibling relative path, a
      cloud region, and a public apex domain. Resolve each one per `CLAUDE.md` § Reading the project where that table
      names it, and from the file or the environment that holds it where it does not, because the toolkit declares
      nothing and a value it never declared still has to be checked. A capability this project does not have leaves no
      literal behind at all.
- [ ] The CI caller satisfies the github-workflows rule, README per the documentation rule.
- [ ] `composer validate` reports the file as valid, and, with `<deploy-target>` set, `terraform fmt -check` passes on
      the `ecs/` stacks.
- [ ] The `Makefile` came from the template with `PROJECT_NAME` filled in, and satisfies the tooling rule (§ Makefile
      shape).
- [ ] `make help` lists the closed public target set, and the file declares no target outside it.
- [ ] `make configure` completes from a clean working copy (no `vendor/`, no `composer.lock`). When `composer.json`
      declares a private package in `repositories`, that run also proves the `Makefile` runners forward its credential
      (the read-only registry token the CI carries, tooling rule). With no private package declared there is no
      credential to forward and the clean install is the whole check.
- [ ] No body invented from memory: each skeleton file came from its `assets/` subtree.

## Additional resources

The config and tooling files ship as templates under `assets/config/` and are copied verbatim, then edited only at the
placeholders. Both Dockerfiles, both dockerignores, and the `docker-compose.yml` (standard database, migrate, app, and
web services) ship as templates too, so every new service is born inheriting `FROM` the `<base-images>` images at a full
versioned tag (docker-images rule). With `<base-images>` unset the same templates inherit the upstream official images,
pinned the same way. The standard `docker/<vendor>/` substructure, the `public/` entrypoint, the
`.github/workflows/ci.yml` caller, and the `ecs/development/` and `ecs/production/` IaC stacks ship as templates as
well, under the mirrored `assets/docker/`, `assets/public/`, `assets/github/`, and `assets/ecs/` subtrees. These infra
bodies have no owning rule, so this skill is their canonical source (the layout, shell-scripts, github-workflows, and
docker-images rules still govern where they sit and how they are shaped). Only the `.env` files and genuinely
app-specific extras (extra compose services beyond the standard four, extra provider integrations) are not templated
here: they follow the layout rule. The `.gitignore` ships as a template, is copied verbatim, and must satisfy the ignore
invariants in the layout rule.

Config and tooling files: `composer.json`, `phpunit.xml`, `phpcs.xml`, `phpstan.neon`, `infection.json.dist`,
`.editorconfig`, `.gitattributes`, `.gitignore`, `Makefile`, `Dockerfile.dev`, `Dockerfile.prod`, `.dockerignore`,
`Dockerfile.prod.dockerignore`, `docker-compose.yml`.

Infra files (mirrored under `assets/`): `public/index.php`, `public/robots.txt`, `public/sitemap.xml`,
`github/workflows/ci.yml`, `github/workflows/ci-standalone.yml`, `github/dependabot.yml`,
`github/copilot-instructions.md`, `docker/php/fpm-logging.prod.conf`, `docker/mysql/initdb.d/01-init-security.sh`,
`docker/mysql/initdb.d/02-init-cdc.sql`, `docker/localstack/init-ready.d/01-create-topics.sh`,
`docker/localstack/init-ready.d/02-create-queues.sh`, `docker/localstack/init-ready.d/03-create-sns-subscriptions.sh`,
`docker/dispatch/dispatch-<service-name>-facts.sh`, `ecs/.gitignore`, and for each of `ecs/development/` and
`ecs/production/`: `main.tf`, `providers.tf`, `variables.tf`, `outputs.tf`, `README.md`, `terraform.tfvars.example`.

Those are asset filenames and they are spelled as the assets are spelled. The `mysql/`, `localstack/`, `dispatch/`, and
`ecs/` bodies carry the bindings of the project that shipped them (`<database-engine>`, `<messaging-transport>`,
`<deploy-target>`), so a project on other bindings writes its own bodies for those instead of copying these.

Applicability, a template exists so it is ready when the service needs it, not so every service copies all of them. Copy
only the templates the service actually uses, and let the layout rule and the spec decide which apply:

- Always: `public/`, `.github/workflows/ci.yml`, `.github/dependabot.yml`, and `.github/copilot-instructions.md`. With
  `<deploy-target>` set, the `ecs/development/` and `ecs/production/` stacks as well (every service deploys), keeping
  only the parts the service needs. With `<deploy-target>` unset no infrastructure subtree is scaffolded, and the
  deployment stack is written or replaced whole elsewhere.
- `ecs/development/` and `ecs/production/`: these stacks are one organization's shape of a `<deploy-target>` deployment
  and not a portable interface, so another target replaces them wholesale instead of substituting into them. A project
  where `<deploy-target>` resolves must resolve `<cloud-region>` too, because the backend block is not a variable and
  takes no default, so an unfilled region fails `terraform init` before any plan runs. Four questions then decide what
  the copied bodies keep, and each is answered at fill time, never left as a sentence in the written stack:
    1. `<platform-iac>` set, the `service` and `service-messaging` modules come from that shared repository and the
       stack passes only the service-specific inputs. Unset, there is no shared module to call and the stack declares
       that plumbing itself, which changes who supplies it and never what gets provisioned.
    2. With a public apex domain for the environment, the `identity_jwks_url` local derives the JWKS host from the
       environment zone, which is what the template ships. Without one, there is no zone to derive from, so that local
       is dropped and the URL arrives as a required variable with no default.
    3. `<cloud-region>` set, the `aws_region` variable keeps the seeded default the template ships. Unset, drop that
       `default` line and let the provider read the region from the environment.
    4. `<base-images>` set, `ecr_registry` hosts both the application repository and the nginx sidecar repository, and
       `nginx_image_tag` names a tag published there. Unset, the sidecar is the upstream official nginx image, only the
       application repository is hosted in that registry, and the tag is pinned the same way.
- `docker/php/fpm-logging.prod.conf`: only when the service tunes production pool sizing or logging (layout rule).
  Copying it is three coupled edits, never one. The conf alone never reaches the image, because the production build
  context excludes `docker/`. Add all three or none:
    1. `docker/php/fpm-logging.prod.conf` from the template.
    2. `COPY docker/php/fpm-logging.prod.conf /usr/local/etc/php-fpm.d/zz-prod-logging.conf` in `Dockerfile.prod`,
       immediately before the `COPY --from=builder` line.
    3. `!docker/php/fpm-logging.prod.conf` in `Dockerfile.prod.dockerignore`, on the line right after `docker/`.

  The unignore belongs to `Dockerfile.prod.dockerignore` alone. `.dockerignore` filters the `Dockerfile.dev` context,
  and the development image never copies that conf, so an unignore there is dead weight.
- `docker/<database-engine>/initdb.d/`: only when the service operates its own database container (layout rule). The
  compose template already mounts the directory read-only on the `<service-name>-adm` service and passes
  `<SERVICE_NAME>_APP_USER` and `<SERVICE_NAME>_APP_PASSWORD`, so `01-init-security.sh` runs on first container start
  with nothing else to wire. A service with no database of its own drops the whole `<service-name>-adm` block, that
  mount included.
- `docker/<database-engine>/initdb.d/02-init-cdc.sql`: only when the service has a change-data-capture pipeline. Copying
  it is two coupled edits, never one. The script grants `REPLICATION CLIENT` and `REPLICATION SLAVE` to a `cdc` user,
  and those grants are useless unless the container writes a row-image binary log. Add both or none:
    1. `docker/<database-engine>/initdb.d/02-init-cdc.sql` from the template.
    2. A `command:` block on the `<service-name>-adm` service, right after its `ports:`, listing
       `--host-cache-size=0`, `--binlog-row-image=FULL`, `--server-id=1`, `--log-bin=mysql-bin`, and
       `--binlog-expire-logs-seconds=86400`.
- `docker/localstack/init-ready.d/` (channels, queues, subscriptions) and `docker/dispatch/`: only when the service has
  the `<messaging-transport>` facts flow (an outbox that publishes domain facts). A service that publishes no facts gets
  none of these, and drops the `module "service_messaging"` block, the facts outputs, and `dispatch_event_types` from
  its `ecs/` stacks. Whether the service publishes facts is a domain question the spec answers. With
  `<messaging-transport>` unset there is no broker emulation to scaffold and none of this applies.

The event names, the queue and channel names, and the provider block are illustrative. Fill or drop each per the
service, never scaffold a channel, queue, subscription, or dispatch worker a service does not have.

Placeholders to replace in every copied file:

- `<vendor>`: kebab-case organization slug, read from `composer.json` `name`, the segment before the slash (composer
  `name` and `keywords`, the image `LABEL` titles, the private package repository URLs). The `<vendor>` inside the path
  `docker/<vendor>/` is a different thing, the software vendor (`php`, `nginx`, the database engine), and it is never
  this token.
- `<RootNamespace>`: the PSR-4 prefix mapped to `src/`, read from `composer.json` `autoload.psr-4` with no trailing
  backslash. It is read whole and never composed from other tokens: it may be one segment or two, and it need not
  contain `<vendor>` at all. Fills the `autoload` and `autoload-dev` PSR-4 keys, every `namespace` line, and every `use`
  of the service's own code.
- `<service-name>`: kebab-case name (composer `name`, which is `<vendor>/<service-name>`, and `keywords`, `APP_NAME`,
  `DATABASE_HOST`, Makefile `PROJECT_NAME`, compose service names, and, with `<local-proxy>` set, the proxy router
  labels). With `<local-proxy>` unset there are no proxy labels and the app publishes its port directly.
- `<service_name>`: snake_case variant (`DATABASE_NAME`).
- `<database-host-port>`: the host port the database container publishes, unique across the local Docker host. With
  `<local-proxy>` set the neighbouring services share that host, so check their compose files before picking one.

The compose template interpolates `${DATABASE_NAME}` from the committed `.env.local`, whose value must equal the
`<service_name>_adm` the migrate service passes to Flyway. It also interpolates `${DATABASE_USER}` and
`${DATABASE_PASSWORD}` into the `<database-engine>` init hook, which creates that pair as the least-privilege
application user the app service then connects with. The `.env.local` body follows the layout rule, and those three keys
are the coupling the template depends on.

- `<bootstrap-file>`: the file excluded from the composer `review` script.
- `<one short sentence describing what the service does>`: composer `description`.
- `*` in every composer `require` and `require-dev` constraint: a placeholder, not a shipped value. Resolve each against
  Packagist at scaffold time and pin it (`^7.15`), under the dependency-policy cooldown in the toolkit preamble
  (`CLAUDE.md` § Dependency policy). A service left on `*` resolves to whatever is latest, which defeats that cooldown,
  and `composer validate` warns on every one.
- `<auth-package>`: the private authentication package, owner and package segments together. It fills two coupled places
  in `composer.json`, the `require` key and the `repositories` VCS `url` (`https://github.com/<auth-package>`), and the
  same token fills both so a package named differently from the origin's still resolves. The two entries move together
  and drop together. With `<auth-package>` unset the `require` key goes, the `repositories` block goes with it unless
  another private package remains, and `src/Routes.php` wires the route group without the authentication middleware.
- `<base-images>` in the `Makefile`: `BASE_IMAGE_NS` takes the shared base image namespace and `BASE_IMAGE_VERSION` its
  release. With `<base-images>` unset there is no shared namespace, so point `PHP_IMAGE` at the upstream official image
  pinned to an exact tag (`php:$(PHP_VERSION)-cli`) and delete `BASE_IMAGE_NS` and `BASE_IMAGE_VERSION` rather than
  leaving them orphaned, because the tooling rule removes an unreferenced variable instead of keeping it for symmetry.
  The sidecar images are named as literals in `docker-compose.yml`, never through a `Makefile` variable.
- `<local-proxy>` in the `Makefile`: `PROXY_DIR` takes the local proxy checkout. With `<local-proxy>` unset leave it
  empty and the `start` target skips the proxy step.

Comment-guided entries to fill in `phpunit.xml`, leaving the rest verbatim:

- `<source><exclude>`: the provider-, gateway-, and webhook-specific Settings files.
- `<php>`: the service-specific external endpoint URLs, mock secrets, and sandbox API keys.

Capability holes. Each is a hole in a shipped template AND a value the reading table resolves, so filling it is what
makes the reading table answer later. A capability the service does not have leaves no literal behind: delete the block,
the label, or the whole subtree the hole sits in, rather than filling it with a placeholder value.

- `<runtime-minor>`: the language `major.minor`. `assets/config/Dockerfile.dev`, `Dockerfile.prod` (both `FROM` lines),
  and `composer.json` `require.php`. All four carry the same literal and are the source the reading table then reads.
- `<base-images>`: the base-image namespace in both Dockerfile `FROM` lines and the two compose sidecar images. Absent
  means the upstream official images, and the app Dockerfile then declares what a base would have baked.
- `<local-network>`: the shared external Docker network, in `assets/config/docker-compose.yml`. Absent means dropping
  the `networks:` block entirely, because a network declared external and missing aborts `docker compose up`.
- `<local-domain>`: the host suffix in the compose reverse-proxy label. Absent means dropping the label block and
  publishing the port directly.
- `<platform-iac>`: the shared module source in both `assets/ecs/*/main.tf`. Absent means the stack declares its own
  plumbing or the subtree is not scaffolded at all.
- `<cloud-region>`: the region. Present in `assets/docker/localstack/init-ready.d/*.sh` AND, when the infrastructure
  subtree is scaffolded, throughout `assets/ecs/*/` (`providers.tf` backend and provider, `main.tf` module paths and
  remote-state key, `variables.tf`, `terraform.tfvars.example`, `README.md`). The backend block takes no variable, so an
  unfilled region there fails `terraform init` before any plan runs.
- `<messaging-transport>`: names the transport in the dispatch worker header. Absent means the dispatch script, the
  broker emulation directory, and the topic and queue provisioning are not scaffolded at all.

Placeholders specific to the infra templates:

- `<service-name>` also drives the derived messaging names in the docker and ecs templates: the `<messaging-transport>`
  channel `<service-name>-events`, the CDC service `<service-name>-cdc`, the dispatch script filename
  `dispatch-<service-name>-facts.sh` with its `[<service-name>-dispatch]` log prefix, and the ecs state keys
  `services/<service-name>/...`. With `<messaging-transport>` unset the channel and the dispatch script are not
  scaffolded, and the other derived names are unaffected.
- `<subscribed-channel>`: the channel the consumer queue subscribes to on `<messaging-transport>`. It is
  `<service-name>-events` in the self-consumption case and another context's channel for a cross-service one. It names
  the queues `<subscribed-channel>-<service-name>` and `<subscribed-channel>-<service-name>-dlq`, per the
  `{channel}.{consumer}` convention materialized with hyphens, so the queue name derives from the channel it consumes
  and never from the service that owns the stack. That naming convention holds on any transport.
- `<Service name>`: sentence-case variant, the CI caller name `CI (<Service name>)`. Only the first word is capitalized,
  so a two-word service reads `CI (Client gateway)`, never `CI (ClientGateway)` (github-workflows rule, item 2).
- `<SERVICE_NAME>`: SCREAMING_SNAKE_CASE variant, the `<database-engine>` init env vars `<SERVICE_NAME>_APP_USER` and
  `<SERVICE_NAME>_APP_PASSWORD`.
- `<DomainEventType>`: a PascalCase published fact that dispatches a use case. Fills the `dispatch_event_types` list in
  the ecs `main.tf` and the subscription filter in `03-create-sns-subscriptions.sh`. Replace the placeholder list with
  the real fact names.
- `<provider>` and `<PROVIDER>`: the external gateway identifier (kebab and SCREAMING_SNAKE). The ecs provider block
  (env vars, `extra_secrets`, timeout and base-URL variables) is illustrative for one gateway. Adjust it, repeat it, or
  drop it per service, and set the real secret values out of band, never in the tracked files.
- `<provider-sandbox-host>`, `<provider-host>`, `<api-version>`: the provider base-URL parts in the ecs `variables.tf`
  defaults.
- `<edge-path-pattern>`: a service-specific ALB listener path pattern in the ecs `main.tf` `edge_path_patterns`, next to
  the generic `/health/*`.
- `<root-domain>`: the environment's public apex domain, in both `assets/ecs/*/variables.tf` and both `README.md`. It is
  a hole the scaffolder fills from the environment the stack deploys into, never a value the toolkit knows. Where the
  environment has no public apex domain there is no public DNS, so the zone wiring goes and any issuer URL becomes a
  required variable with no default.
- `<ci-workflows>`: the repository publishing the reusable CI workflow, filling the `uses:` target of the caller in
  `assets/github/workflows/ci.yml`. It also decides which of the two CI templates is copied (assembly order, step 6),
  never which half of one template is commented out.
- `<ci-token-secret>`: the name of the secret carrying the read-only registry token the CI uses to reach private
  packages, read by the `COMPOSER_AUTH` env block of the install step in `assets/github/workflows/ci-standalone.yml`.
  Where the project carries no such token, delete that whole `env` block: no private package needs a build credential
  and the install resolves anonymously. The credential travels as a build secret, never as a baked `ARG`, an `ENV`, or a
  tracked file. The caller template names no secret, it passes `secrets: inherit`.
- `<vendor>` in the `cooldown.exclude` list of `.github/dependabot.yml`: the template ships the literal `tiny-blocks/*`
  and the project's own Composer vendor goes beside it as a second line. One exclude line per vendor, never the joined
  row on a single line. Those two vendors are the whole exemption, both of them maintained in-house (`CLAUDE.md` §
  Dependency policy). An exempt vendor is one the team maintains and never one it merely consumes, so a foreign vendor
  there waives the cooldown that protects the service.
- `<cloud-region>` in `docker/localstack/init-ready.d/*.sh`: the region the mock provisions into, the same value the
  application reads. It seeds `REGION`, and the guard on the next line makes `AWS_REGION` required when the hole is left
  empty, so the script fails loudly instead of assembling an ARN with an empty region. That guard is executable behavior
  and stays in the copied file whatever the project declares.

The `cdc` credentials in `docker/<database-engine>/initdb.d/02-init-cdc.sql` are local-only literals and have no hole to
fill. Outside the local environment that user is provisioned by the deployment stack, and its credentials live in a
secret store, or, where the project has none, in the deployed environment alone (`CLAUDE.md` § Global defaults). No
tracked file carries a real value either way, `.env.local` included, so the comment in the template says that and names
no store.

## Does not do

- Does not run tooling beyond the validation check.
