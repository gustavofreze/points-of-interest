# README structure

Use this shape when building or restoring the repository `README.md`.

## Contents

- Fixed section order
- Worked example (the section shapes filled in)

`````text
# README structure (template)

The repository `README.md` follows a fixed section order. The README never inlines endpoint
documentation: each endpoint lives in its docs page (`docs/USE_CASES.md`, `docs/QUERIES.md`,
`docs/HEALTH.md`, or `docs/WEBHOOKS.md`). Anchor links use Markdown auto-generated heading anchors
(`lowercase-hyphenated`). The `<div id='...'></div>` HTML anchor pattern is not used. Markdown
generates `#section-name` automatically from `## Section name`.

## Fixed section order

1. **Service name**. The H1 heading.
2. **Top-level table of contents**. Nested bullet list with anchor links to every section and
   subsection below. Use `*` for top-level bullets and `-` for nested bullets to mirror the
   hierarchy visually.
3. **`## Overview`**. One or two paragraphs describing what the service centralizes and the problem
   it solves. Include cross-cutting concerns the consumer must know about (outbox events,
   transactional invariants, at-least-once delivery semantics). Add a domain model diagram when the
   domain has two or more aggregates.
4. **`### Use cases`**. Under Overview. Bulleted list. Each item is a Markdown link to the
   corresponding section anchor inside `docs/USE_CASES.md`. One bullet per write-side endpoint.
5. **`### Queries`**. Under Overview. Bulleted list. Each item is a Markdown link to the
   corresponding section anchor inside `docs/QUERIES.md`. One bullet per domain read-side endpoint.
6. **`### Health checks`**. Under Overview. Bulleted list. Each item is a Markdown link to the
   corresponding section anchor inside `docs/HEALTH.md`. Covers liveness, readiness, startup probes,
   and observability endpoints. Kept separate from `### Queries` because these are operational
   contracts, not domain reads.
7. **`### Webhooks`**. Under Overview. Bulleted list. Each item is a Markdown link to the
   corresponding section anchor inside `docs/WEBHOOKS.md`. One bullet per provider-event pair the
   service consumes. Omit this subsection entirely when the service has no webhook endpoints.
8. **`## Installation`**. One subsection per `make` target group, in this order: `### Repository`
   (clone), `### Configuration` (configure, start, stop), `### Tests` (test with and without
   coverage), `### Review` (static analysis), `### Reports` (open analysis reports). Each subsection
   contains a Bash snippet with the corresponding `make` command(s). The list above is the whole of
   it: `configure-and-update` and `show-outdated` are deliberately absent, being maintenance
   commands a reader reaches for after the service already runs, not steps on the path to running
   it. Close the section with the blockquote note pointing readers to `make help` for the full
   target list, which is what covers them. Every snippet in this section is a `make` command and
   nothing else. A setup step written as a raw `docker` call is a step the `Makefile` should have
   taken, so it moves into the recipe that needs it rather than onto the reader.
9. **`## Environment setup`**. Tabular reference. Two subsections. `### Access URLs` (`Environment`
   and `DNS` columns), one row per environment (`Local`, `Development`, `Production` as applicable).
   `### Environment variables`, a table of the variables the service reads with exactly these
   columns in this order: `Variable`, `Description`, `Development value`. One row per variable. The
   `Development value` column prints the literal value `.env.local` carries, because that file is
   tracked (`skeleton-layout` § `.env` strategy) and hiding a value that sits two directories away
   in the same repository is theatre. That is workable only because nothing sensitive is allowed in
   the file: a value like `local`, `true`, a docker service name, a `localhost` URL, or a port. A
   real credential, API key, token, private key, certificate, production host, or connection string
   with a real endpoint is in neither the file nor this table. Its row carries a dash placeholder and
   the `Description` says how the deployed environment supplies it.
   When unsure whether a value is sensitive, treat it as sensitive and keep it out of both. Source
   the rows only from the tracked configuration (this repository ships no `.env.example`, which
   `skeleton-layout` forbids, so read the committed `docker-compose.yml` and `.env.local`). Never
   infer a variable from framework defaults. Leave out any variable that no tracked file confirms.
   The section opens by stating, in one or two sentences and nowhere else in the file, that the
   repository is a proof of concept, that this is why `.env.local` is committed, and that every
   value in it is a local-only default. Objective and short: the README documents the service, and a
   tracked environment file is a fact about it, not a subject the file argues about.

## Worked example (the section shapes filled in)

Everything inside the block below is illustrative, not canonical: the H1, the endpoint names, the
Overview prose, the Access URLs table, and the Environment variables table. Only the section order
and the table columns are fixed. The real content comes from the spec (`php-reading-spec`), from the
contract rendered in the docs pages, and from this repository's own tracked configuration. The
`Local` DNS row mirrors the Traefik host rule in the committed `docker-compose.yml`
(`<service-name>.<local-domain>`). With `<local-domain>` unset the host rule falls back to
`<service-name>.localhost`, and the row is still the https proxy URL. With `<local-proxy>` unset
there is no proxy at all, so the row carries the port the app publishes directly.

````Markdown
# Payment

* [Overview](#overview)
    - [Use cases](#use-cases)
    - [Queries](#queries)
    - [Health checks](#health-checks)
* [Installation](#installation)
    - [Repository](#repository)
    - [Configuration](#configuration)
    - [Tests](#tests)
    - [Review](#review)
    - [Reports](#reports)
* [Environment setup](#environment-setup)
    - [Access URLs](#access-urls)
    - [Environment variables](#environment-variables)

## Overview

Centralizes payment creation, capture, and refunds against external payment providers. Solves the risk of duplicate
charges by automating idempotency and routing checks against strict database constraints and ACID transactions to ensure
financial consistency.

The write-side repositories integrate with `OutboxEvents::push` inside the same transaction that persists aggregate
state. Consumers must treat the published events as at-least-once.

### Use cases

- [Payment creating](docs/USE_CASES.md#payment-creating)
- [Payment capturing](docs/USE_CASES.md#payment-capturing)
- [Payment refunding](docs/USE_CASES.md#payment-refunding)

### Queries

- [Find payments by organization](docs/QUERIES.md#find-payments-by-organization)
- [Find payment by id](docs/QUERIES.md#find-payment-by-id)

### Health checks

- [Liveness check](docs/HEALTH.md#liveness-check)
- [Readiness check](docs/HEALTH.md#readiness-check)

## Installation

### Repository

To clone the repository using the command line, run:

```bash
git clone https://github.com/<github-org>/<service-name>.git
```

### Configuration

To install project dependencies locally, run:

```bash
make configure
```

To start the application containers, run:

```bash
make start
```

To stop the application containers, run:

```bash
make stop
```

### Tests

Run all tests with coverage and mutation testing:

```bash
make tests
```

Run a specific test file without coverage:

```bash
make test-file FILE=ClassNameTest
```

### Review

Run lint and style checks:

```bash
make review
```

### Reports

Open coverage and mutation reports in the browser:

```bash
make show-reports
```

> You can check other available commands by running `make help`.

## Environment setup

### Access URLs

| Environment   | DNS                                   |
|:--------------|:--------------------------------------|
| `Local`       | https://<service-name>.<local-domain> |
| `Development` |                                       |
| `Production`  |                                       |

### Environment variables

Every variable the service reads. This repository is a proof of concept, so `.env.local` is committed and the column
below is the literal content of that file. Nothing sensitive is allowed in either: the row of a real credential carries
a dash placeholder, and the `Description` says how the deployed environment supplies it.

| Variable              | Description                                                        | Development value |
|:----------------------|:-------------------------------------------------------------------|:------------------|
| `DATABASE_HOST`       | Database host (docker service name)                                | `payment-adm`     |
| `DATABASE_PORT`       | Database port                                                      | `3306`            |
| `DATABASE_NAME`       | Database schema name                                               | `payment_adm`     |
| `DATABASE_USER`       | Database user, the local container default                         | `root`            |
| `DATABASE_PASSWORD`   | Database password, the local container default                     | `root`            |
| `PAYMENT_FACTS_TOKEN` | Bearer token for the payment-facts dispatch, from the secret store | `—`               |
````
`````
