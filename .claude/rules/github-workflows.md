---
description: Naming, ordering, inputs, security, and structural rules for all GitHub Actions workflow files.
paths:
    - ".github/workflows/**/*.{yml,yaml}"
    - "**/assets/github/workflows/**/*.{yml,yaml}"
---

# Workflows

Structural and stylistic rules for GitHub Actions workflow files. Bash inside `run:` steps follows the project's Bash
script conventions.

## Pre-output checklist

This file follows the rules-as-checklist format: each numbered item below is the normative statement for that topic. The
sections after the list (Style, Callers, Image tagging, Migrations) carry additional clarifications that build on the
same rules. Verify every item before producing any workflow YAML.

1. File name follows the convention: `ci-<runtime>.yml` for the reusable CI that lives in `<ci-workflows>`,
   `cd-<purpose>.yml` for dispatch CD, and `ci.yml` for the thin caller shipped in the service repo that delegates to
   the reusable workflow. With `<ci-workflows>` unset there is no caller and no callee: the service repository's own
   `ci.yml` carries the whole workflow, jobs and steps included. Every item below that speaks of a caller or of the
   reusable workflow then applies to that one file directly, so nothing goes unchecked.
2. `name` field follows the pattern `CI (<Context>)` or `CD (<Context>)` (equivalently `CI - <Context>` or
   `CD - <Context>` with a hyphen), using sentence case for the context (e.g., `CD (Run migration)`, not
   `CD (Run Migration)`).
3. Reusable workflows use `workflow_call` trigger. CD workflows use `workflow_dispatch` trigger.
4. Each workflow has a single responsibility. CI tests code. CD deploys it. Never combine both.
5. Every input has a `description` field. Descriptions use American English and end with a period.
6. Input names use `kebab-case`: `service-name`, `dry-run`, `skip-build`.
7. Inputs are ordered: required first, then optional. Each group by **name length ascending**.
8. Choice input options are in **alphabetical order**.
9. `env`, `outputs`, and `with` entries are ordered by **key length ascending**.
10. `permissions` keys are ordered by **key length ascending**, ties alphabetically (item 13).
11. Top-level workflow keys follow canonical order: `name`, `on`, `concurrency`, `permissions`, `env`, `jobs`.
12. Job-level properties follow canonical order: `if`, `name`, `needs`, `uses`, `with`, `secrets`, `runs-on`,
    `environment`, `timeout-minutes`, `strategy`, `outputs`, `permissions`, `env`, `steps`. A job that calls a reusable
    workflow accepts only the closed set `if`, `name`, `needs`, `uses`, `with`, `secrets`, `strategy`, `permissions`.
    See § Callers.
13. All other YAML property names within a block are ordered by **name length ascending**. Every length ordering in this
    rule (items 7, 9, 10, and this one) breaks ties **alphabetically**, so `contents` precedes `id-token`.
14. Jobs follow execution order: `load-config` → `lint` → `test` → `build` → `deploy`. With `<ci-workflows>` unset there
    is no `load-config` job to run first (item 18), and the order is `lint` → `test` → `build` → `deploy`.
15. Step names start with a verb and use sentence case: `Setup PHP`, `Run lint`, `Resolve image tag`.
16. Runtime versions are resolved from the service repo's native dependency file (`composer.json`, `go.mod`,
    `package.json`). No version is hardcoded in any workflow.
17. Service-specific overrides live in the pipeline config file `.<vendor>/pipeline.yml` in the service repo, never in
    `<ci-workflows>`. That path is the canonical spelling of this artifact and `skeleton-layout` uses the same one in
    its repository root tree. A root-level `.pipeline.yml` is not used. With `<ci-workflows>` unset the file is
    unnecessary, because the service repository already owns the workflow and an override is edited in place.
18. The `load-config` job reads the pipeline config file at runtime with safe fallback to defaults when absent. The
    fallback defaults are the runtime version resolved from the native dependency file (item 16), the timeout bounds in
    item 26, and the read-only permission baseline in item 19. A missing config file never blocks the run. With
    `<ci-workflows>` unset there is no `load-config` job, and those same defaults are written literally in the service's
    own workflow.
19. Top-level `permissions` defaults to read-only (`contents: read`). Jobs escalate only the permissions they need.
20. Authentication to any cloud provider or registry uses short-lived OIDC federation exclusively. Static long-lived
    credentials (access keys, service account key files, passwords) are forbidden, in the workflow file and in a
    repository or environment secret alike. The invariant is about the credential and not about the provider, so it
    holds for whatever `<deploy-target>` is and for any registry or external service the workflow logs into.
21. Secrets are passed via `secrets: inherit` from callers. No secret is hardcoded. The private-package build
    credential is the read-only registry token the pipeline carries, a repository or organization secret the build step
    turns into `COMPOSER_AUTH` (docker-images, § Secrets). It travels as a build secret and never as a baked `ARG`, an
    `ENV`, or a tracked file, whatever the project names it. Where the project has no such token, no build secret is
    mounted, because no private package needs one. With `<ci-workflows>` unset there is no caller to inherit from, and
    the workflow declares the secrets its own steps read.
22. A sensitive value fetched from a secret store is masked with `::add-mask::` before assignment. The obligation is on
    the value and never on the store, so it holds identically for a repository secret, an environment secret, and a
    value read at runtime, and it holds unchanged where the project has no secret store at all.
23. Third-party actions are pinned to the latest available full commit SHA with a version comment:
    `uses: docker/login-action@<latest-sha> # v3.3.0`. Always verify the latest version before generating a workflow.
24. First-party actions (`actions/*`) are pinned to the latest major version tag available:
    `actions/checkout@v4`. When deriving a major from an existing minor/patch pin, **preserve the prefix style** of the
    original: `@v2.1.0` → `@v2`, `@2.1.0` → `@2`. Always check for the most recent major version before generating a
    workflow.
25. Production deployments require GitHub Environments protection rules (manual approval).
26. Every job that runs steps sets `timeout-minutes` to prevent indefinite hangs. CI jobs: 10 to 15 minutes. CD jobs: 20
    to 30 minutes. Adjust only with justification in a comment. A job that calls a reusable workflow with `uses:` never
    sets it, GitHub rejects the key there (item 12). The bound belongs to the jobs inside the called workflow.
27. CI workflows set `concurrency` with `group` scoped to the PR and `cancel-in-progress: true` to avoid redundant runs.
28. CD workflows set `concurrency` with `group` scoped to the environment and `cancel-in-progress: false` to prevent
    interrupted deployments.
29. CD workflows use `if: ${{ !cancelled() }}` to allow deployment to proceed after optional build steps.
30. Inline logic longer than 3 lines is extracted into a reusable workflow, a composite action, or a script in
    `<ci-workflows>`. The service repository root has no `scripts/` directory, and that holds with `<ci-workflows>`
    unset too, where the extraction target is a composite action under `.github/actions/` instead.

## Style

- All text (workflow names, step names, input descriptions, comments) uses American English (US spelling). Sentences and
  descriptions end with a period.

## Callers

This section describes the caller half of the split and applies where `<ci-workflows>` is set. With `<ci-workflows>`
unset there is no caller, and the first bullet moves onto the service's own `ci.yml` unchanged: it triggers on
`pull_request` targeting `main` only, with no `push` trigger. The three bullets after it describe delegation and stop
applying, because no job delegates.

- Callers trigger on `pull_request` targeting `main` only. No `push` trigger.
- Callers in service repos are static (~10 lines) and pass only `service-name` or `app-name`.
- Callers reference workflows with `@main` during development. Pin to a tag or SHA for production.
- A caller job carries only the keys GitHub allows next to `uses:` (`if`, `name`, `needs`, `uses`, `with`, `secrets`,
  `strategy`, `permissions`). Anything else (`timeout-minutes`, `runs-on`, `env`, `steps`, `outputs`, `environment`,
  `container`, `services`) fails the workflow at parse time with `Unexpected value`. `actionlint` catches it.

## Image tagging

- CD deploy builds: `<environment>-sha-<short-hash>` + `latest`.

## Migrations

- Migrations run **before** service deployment (schema first, code second).
- `cd-migrate.yml` supports `dry-run` mode (`flyway validate`) for pre-flight checks.
- Database credentials are fetched from the project's secret store at runtime, never stored in workflow files. With no
  secret store they arrive as workflow secrets, are still absent from every tracked file, and are still masked (item
  22).
