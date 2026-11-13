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

1. **Service name**. The H1 heading, the service name in title case (each hyphen-separated word of
   `<service-name>` capitalized, so `client-gateway` reads `Client Gateway`). Nothing sits between
   the H1 and the table of contents: no badge, no tagline, no paragraph.
2. **Top-level table of contents**. Nested bullet list with anchor links to every section and
   subsection below. Use `*` for top-level bullets and `-` for nested bullets to mirror the
   hierarchy visually.
3. **`## Overview`**. One or two paragraphs describing what the service centralizes and the problem
   it solves. Include cross-cutting concerns the consumer must know about (outbox events,
   transactional invariants, at-least-once delivery semantics). The prose closes with `### Flow`,
   ahead of the endpoint lists.
4. **`### Flow`**. Under Overview, before `### Use cases`. Embeds
   `docs/diagrams/<service-name>-flow.svg`, the communication view: the services that call this one
   on the left, each with what it asks for, the service at the center with its use-case groups, the
   peers it calls during a request on the right in call order, the database below, and, when the
   service publishes events, the asynchronous chain from the outbox to each consumer, every step
   numbered in execution order. When the service publishes or consumes domain events it also embeds
   `docs/diagrams/<service-name>-events.svg`, the EventStorming view (the command, the aggregate,
   and the event it raises, then the policy and the command each consumer reacts with), followed by
   a one-line caption saying what that view shows. The subsection closes with the sources line, as
   real Markdown links to the HTML files, one per embedded diagram:
   `Sources: [<service-name>-flow.html](docs/diagrams/<service-name>-flow.html), [<service-name>-events.html](docs/diagrams/<service-name>-events.html).`
   The HTML file is the source of each diagram. The SVG is exported from it with the workspace
   export helper, which outlines every text as glyph paths because the repository host serves
   README images without loading fonts, and it is never edited by hand: change the HTML and export
   again. There is no `### Events` subsection. The events, the commands that raise them, and the
   consumers that react to them live in the diagrams.
5. **`### Use cases`**. Under Overview. Bulleted list. Each item is a Markdown link to the
   corresponding section anchor inside `docs/USE_CASES.md`. One bullet per write-side endpoint.
6. **`### Queries`**. Under Overview. Bulleted list. Each item is a Markdown link to the
   corresponding section anchor inside `docs/QUERIES.md`. One bullet per domain read-side endpoint.
7. **`### Health checks`**. Under Overview. Bulleted list. Each item is a Markdown link to the
   corresponding section anchor inside `docs/HEALTH.md`. Covers liveness, readiness, startup probes,
   and observability endpoints. Kept separate from `### Queries` because these are operational
   contracts, not domain reads.
8. **`### Webhooks`**. Under Overview. Bulleted list. Each item is a Markdown link to the
   corresponding section anchor inside `docs/WEBHOOKS.md`. One bullet per provider-event pair the
   service consumes. Omit this subsection entirely when the service has no webhook endpoints.
9. **`## Installation`**. `### Repository` (the clone command), then one subsection per `make help`
   group, in this order: `### Configuration` (the setup group), `### Tests` (the test group),
   `### Review` (the review group), `### Reports` (the reports group). Each subsection lists every
   target its group shows in `make help`, never a subset, each with a one-line sentence and a Bash
   snippet carrying the `make` command. Read the targets from `make help` (the `## @<group>`
   annotations in the `Makefile`), not from this template. Close the section with the blockquote
   note pointing readers to `make help` for the full target list, which covers the maintenance and
   help groups.
10. **`## Environment setup`**. Tabular reference. Two subsections. `### Access URLs` (`Environment`
    and `DNS` columns), one row per environment (`Local`, `Development`, `Production` as
    applicable). The `Local` row mirrors the compose host rule. Each cloud row carries the hostname
    the infrastructure stack under `<deploy-target>` gives the service: its public record, or, when
    the stack publishes none, the private name the sibling services resolve (the service name under
    the DNS zone of the stack). When the public hostname answers only `/health/*` (an internal service,
    whose API is reached only inside the private network), a note under the table says so and says
    how the sibling services reach the API. With `<deploy-target>` unset there is no cloud
    environment, so the table carries the `Local` row only and no note.
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
11. **`## HTTP contract`**. Optional, and only for a gateway (a service whose public hostname serves
    its API). It states what the gateway adds over the contracts of the services behind it, such as
    the client channels, the trusted edge, and the request and response headers it reads and writes,
    and closes with `### Error codes`, a table (`Code`, `Status`, `Meaning`) of every code the
    gateway produces by itself. The endpoint contract itself stays in `openapi.yaml` and the docs
    pages. An internal service has no such section.

Every error code the service code can produce is documented, in `openapi.yaml`, in the docs page
response blocks, and in the README error table where the README carries one. That includes the
codes no handler raises: the framework answers a known path under another method with `405` and
`METHOD_NOT_ALLOWED`, and a gateway whose route names an upstream it cannot resolve answers `500`
with `UPSTREAM_SERVICE_UNRESOLVED`. Each one is listed wherever the service produces it.

## Worked example (the section shapes filled in)

Everything inside the block below is illustrative, not canonical: the H1, the endpoint names, the
Overview prose, the Flow caption, the Access URLs table and its note (`example.com` stands for the
apex domain the stack resolves), and the Environment variables table. Only the section order, the
Flow shape, and the table columns are fixed. The real content comes from the spec (`php-reading-spec`), from the
contract rendered in the docs pages, and from this repository's own tracked configuration. The
`Local` DNS row mirrors the Traefik host rule in the committed `docker-compose.yml`
(`<service-name>.<local-domain>`). With `<local-domain>` unset the host rule falls back to
`<service-name>.localhost`, and the row is still the https proxy URL. With `<local-proxy>` unset
there is no proxy at all, so the row carries the port the app publishes directly.

````Markdown
# Payment

* [Overview](#overview)
    - [Flow](#flow)
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

### Flow

![Payment communication](docs/diagrams/payment-flow.svg)

![Payment domain events](docs/diagrams/payment-events.svg)

The domain events view shows the commands that raise each event and how each consumer reacts to it.

Sources: [payment-flow.html](docs/diagrams/payment-flow.html),
[payment-events.html](docs/diagrams/payment-events.html).

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

To update dependencies and normalize `composer.json`, run:

```bash
make configure-and-update
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

Run static code analysis:

```bash
make review
```

Fix what static code analysis can correct:

```bash
make fix-review
```

### Reports

Open coverage and mutation reports in the browser:

```bash
make show-reports
```

Show outdated direct dependencies:

```bash
make show-outdated
```

> You can check other available commands by running `make help`.

## Environment setup

### Access URLs

| Environment   | DNS                                     |
|:--------------|:----------------------------------------|
| `Local`       | https://<service-name>.<local-domain>   |
| `Development` | https://payment.development.example.com |
| `Production`  | https://payment.example.com             |

The public hostname answers only `/health/*`. The API is private: the sibling services reach it at the same hostname
through the internal load balancer.

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
