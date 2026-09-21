# points-of-interest

PHP microservice. Hexagonal architecture (ports & adapters), DDD, CQRS. One bounded context per repository (polyrepo).
Bounded context: points of interest by GPS (registering points and finding those near a reference coordinate).

Those two lines are the whole of this file that belongs to this project. Everything below the divider is the portable
toolkit, and nothing of this project is ever written into it. Adopting it elsewhere is `cp -r .claude/` plus this file,
then rewriting those two lines. That copy is a fork and not a mirror: it owes this repository nothing afterwards, and
this repository tracks no other. Nothing is configured, because nothing is declared: every value the toolkit needs it
reads from the project, per § Reading the project.

---

## Reading the project

The toolkit writes `<token>` wherever a value belongs to the project rather than to the toolkit, and it reads every one
of them from the project itself. Nothing is declared, because a declared value is a value that can silently disagree
with the file the tooling actually resolves against, and because a toolkit that has to be configured is a toolkit that
gets adopted wrong.

Read each one from its source, at the moment you need it. In nearly every case the rule that names a token is already
looking at the file that answers it.

| Token                   | Read from                                                                                  |
|-------------------------|--------------------------------------------------------------------------------------------|
| `<vendor>`              | `composer.json` `name`, the segment before the slash                                       |
| `<service-name>`        | `composer.json` `name`, the segment after the slash                                        |
| `<RootNamespace>`       | `composer.json` `autoload.psr-4`, the prefix mapped to `src/`, trailing backslash stripped |
| `<runtime-minor>`       | `composer.json` `require.php`                                                              |
| `<github-org>`          | `git remote get-url origin`, the owner segment                                             |
| `<database-engine>`     | the `pdo_*` extension in `composer.json` `require`, else the database image in compose     |
| `<spec-root>`           | the sibling directory named `specifications`, absent when there is none beside the repo    |
| `<base-images>`         | the namespace in the app Dockerfile `FROM` line, resolved per family, absent per family   |
| `<ci-workflows>`        | the `uses:` target of the caller workflow, absent when no workflow calls out               |
| `<platform-iac>`        | the module `source` in the infrastructure stack, absent when there is no stack             |
| `<deploy-target>`       | the infrastructure subtree at the repository root, absent when there is none               |
| `<cloud-region>`        | the region in the infrastructure provider and backend blocks                               |
| `<local-network>`       | the external network declared in `docker-compose.yml`, absent when none is                 |
| `<local-domain>`        | the host suffix in the compose reverse-proxy label, absent when there is no label          |
| `<local-proxy>`         | the proxy checkout the `Makefile` names, absent when it names none                         |
| `<auth-package>`        | the package supplying the PSR-15 auth middleware the route file wires, absent when none    |
| `<idempotency-package>` | the package supplying the idempotency middleware, absent when none                         |
| `<messaging-transport>` | the broker the outbox adapter publishes to, absent when the service has no outbox          |

`<RootNamespace>` is read whole, never composed of a vendor part and a service part. It may be one segment or two, and
it need not contain `<vendor>`. Composing it breaks on both counts.

Two values that look like they belong here do not, because they are not project values at all. A secret store, a public
apex domain, a CI secret name, and a tenant discriminator column are named where they are used, in the rule that needs
them and in the file that holds them, rather than bound centrally. And the vendors exempt from the dependency cooldown
are a property of the toolkit, not of the project. See § Dependency policy.

### Where a token sits changes what it means

- **In a code block, a file template, or a filename.** Everything under `.claude/skills/*/assets/`, the code samples in
  `.claude/skills/*/references/`, and a path like `dispatch-<service-name>-facts.sh`. The token is a hole. Fill it when
  the file is written, and never let it survive into a written file. Absence never belongs here: a sentence that only
  reads correctly with the token unfilled is nonsense once it is filled, and it trips the scaffold gate if it is not.
  Put that sentence in a rule or a skill instead.
- **In prose.** A rule sentence, a checklist item, a skill instruction. The token is a reference, resolved as you read
  the sentence, from the table above. It stays in the file forever. Never run a substitution pass over `.claude/`, and
  never commit a rule with a resolved value pasted into it.
- **In YAML frontmatter, never.** A rule's `description:` and a skill's `description:` are read by the tooling before
  any of this is in context, so a token there reaches the reader raw. Write the plain word and bind it in the body.

A worked example may print the resolved value beside the token, as `<database-engine>` (`mysql`), to show which spelling
a reader on another engine has to translate. That is the one place both appear together.

Case mirrors the value: `<service-name>` kebab, `<service_name>` snake, `<Service name>` sentence, `<SERVICE_NAME>`
screaming snake. One concept, one spelling per case. Never introduce a second spelling of a token that already exists,
because a reader cannot tell a variant from a new concept.

This file reaches every subagent, because a subagent loads the whole CLAUDE.md hierarchy. The built-in Explore and Plan
agents are the exception and skip it, so when you delegate to one of those and the task turns on a token, restate it in
the task text.

## Capability absence

A capability the project does not state is absent. That is a normal state and never an error. Do not invent a value, do
not substitute a plausible one, and do not treat the dependent rule as unenforceable.

Every rule that names a capability states inline what holds when it is absent, because **an invariant is stated about
the artifact and never about who supplies it**. Read a rule sentence as two claims. In `inherits FROM a
<base-images> image pinned to its full versioned tag, never a moving tag, never latest`, the pinning is the invariant
and the namespace is the binding. With no `<base-images>` the pinning still holds and the namespace is the upstream
official image.

The same split applies to a prohibition. A rule that forbids restating what a base image already provides is conditional
on a base actually providing it. With no base, the app restates those directives itself and the prohibition does not
apply.

## Commands

All PHP and Composer commands run inside Docker through the `Makefile`. The Composer scripts in `composer.json` are the
single source of truth for what each target runs. Run `make help` for the actual list.

The target set below is the invariant `php-skeleton-tooling` enforces, not a fact about the working copy in front of
you. Verify a target exists before invoking it, and where one is missing, use the project's nearest equivalent and
report the gap rather than concluding the command is unavailable.

- `make start`, `make stop`. Start and stop the local stack.
- `make configure`. Install dependencies and normalize `composer.json`.
- `make tests`. Run the full suite (unit, integration, mutation). Requires Docker.
- `make test-file FILE=<ClassNameTest>`. Run a single test file without coverage.
- `make review`, `make fix-review`. Run and autofix the lint.

## Architecture

The hexagon lives under `src/`, four layers, dependencies pointing inward only. The layer contract, folder skeleton, and
dependency rule are owned by `php-architecture`. Read that rule before creating any file under `src/`. Do not restate it
here.

The HTTP contract lives in `docs/` mirrored in `openapi.yaml` (synchronization owned by `web-documentation`). Schema
changes are forward-only Flyway files under `database/migrations/` (owned by `database-migration`).

## Dependency policy

NEVER install or upgrade to a package version released less than 7 days ago. The 7-day cooldown applies to every
dependency manager used in this repo (`composer`, `npm`, `go get`, `pip`, etc.).

Two vendors are exempt and may be installed or upgraded at any version, including same-day releases. The first is
`tiny-blocks`, the package family this toolkit is built on, which is why the exemption travels with the toolkit rather
than being declared per project. The second is the project's own `<vendor>`, whatever it resolves to. The principle
behind both is the same: the team maintains those packages and therefore already knows what the release contains. A
vendor the team merely consumes never qualifies, however much the team likes it. To exempt one more, add it here, in
this paragraph, and say who maintains it.

Before pinning a non-exception package version, verify the release date (e.g., on Packagist, npm registry, the GitHub
release page) against `today - 7 days`. If the latest version is too recent, fall back to the previous published version
that satisfies the cooldown and flag the fallback to the user.

## Global defaults

These apply across every file in the repository, regardless of path.

- **Prose punctuation.** Do not use `;`, ` — ` (em-dash), ` – ` (en-dash), or ` -- ` as clause separators in prose,
  PHPDoc (`src/**/*.php`), shell comments (`**/*.sh`), or Markdown. Use two sentences, a comma, a colon, or parentheses
  instead. Hyphens in compound identifiers and Markdown tables are exempt.
- **Secrets in versioned files.** Never commit real secret values. Every tracked file carries placeholders or local-only
  defaults, never production credentials, API keys, tokens, private keys, certificates, connection strings pointing at
  real hosts, or customer data. This covers `README.md` and everything under `docs/`, `phpunit.xml` and
  `phpunit.xml.dist`, `docker-compose*.yml`, `Dockerfile*`, CI configuration (`.github/workflows/*`), and fixtures,
  seeders, snapshots, and test data. It covers `.env.local` too, which a proof of concept commits (`skeleton-layout` §
  `.env` strategy), so that file is bound by this rule like any other tracked file and not exempted by it. A real value
  lives in the project's secret store, and a project with no secret store has nowhere to put one: it reaches the
  container through the deployed environment and never through a file in this repository.

## Conventions

Rules (`.claude/rules/*.md`) are persistent invariants auto-injected when you read a file matching their `paths:` glob.
Skills (`.claude/skills/<name>/SKILL.md`) are intent-triggered workflows for producing new artifacts. Rules and skills
partition by type and do not conflict with each other. A rule is an invariant (what must always hold), a skill is a
workflow (how to build an artifact within those invariants), and each concern has a single owner. A skill applies and
defers to the rules covering the files it touches, it never overrides them. Artifact names use a stack prefix (`php-`,
`go-`, `tf-`, `web-`). Cross-cutting infrastructure, database, documentation, and version-control artifacts (for example
`creating-migration`, `database-migration`, `docker-images`, `shell-scripts`, `writing-commit-messages`) are
stack-neutral and carry no prefix.

No file under `.claude/` may carry an organization name, a registry host, a sibling directory path, a cloud region, or a
public domain. That is what makes the tree copy-paste portable, and it is checkable with one grep for the values this
project resolves to.

### Read the rule before creating a new file

Claude Code bug (anthropics/claude-code#23478): path-scoped rules are injected on **Read** but NOT on **Write/Edit** a
new file. Before creating any file under a glob covered by a rule, explicitly read the relevant `.claude/rules/*.md`
file. Prefer running the matching skill when creating new artifacts (endpoint, handler, repository, query slice,
migration, commit).

### Authority and conflict resolution

Always follow the rules (`.claude/rules/*.md`) and skills (`.claude/skills/<name>/SKILL.md`). They are the default
authority for every task. Only two artifacts override them, each within its own domain:

1. **On business rules, the spec wins.** For domain behavior, data models, aggregate boundaries, and domain events, the
   specifications at `<spec-root>` outrank every rule, skill, and ADR. Reach them through the `php-reading-spec` skill,
   never by guessing a path. With no `<spec-root>` there is no spec tier: rules and skills become the top authority and
   carry a stated default for every topic a spec would own, which a spec later overrides where it speaks. Either way the
   prohibition that matters survives. Never invent domain behavior, stop and ask.
2. **On technical decisions, an ADR wins** for the situation it addresses, over any rule or skill. ADRs live in
   `docs/adrs/` (service-specific) and, where `<spec-root>` resolves, at `<spec-root>/adrs/` (cross-cutting). Never
   create one without an explicit request.

Outside those two cases, rules and skills always apply, and they do not compete with each other. If two rules overlap,
favor the more specific one. Flag any real contradiction, between two rules or between an ADR and a rule, before
deviating from either.
