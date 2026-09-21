# Endpoint naming derivation (reference)

Read this while naming an endpoint across the docs page and `openapi.yaml`. The Endpoint Name has one source and the
anchor slug and `operationId` derive from it mechanically. This file is narrative how-to, the synchronization invariant
that keeps the two renderings aligned stays in `web-documentation`. The actual endpoints and their summaries are
contract facts owned by the spec, read them via `php-reading-spec`.

## Endpoint name pattern per file

The `<Endpoint name>` follows a fixed pattern per file.

| File                | Pattern              | Examples                                                     |
|:--------------------|:---------------------|:-------------------------------------------------------------|
| `docs/USE_CASES.md` | `<Noun> <gerund>`    | `Payment creating`, `Payment capturing`, `Payment refunding` |
| `docs/QUERIES.md`   | `Find <what>`        | `Find payment by id`, `Find payments by organization`        |
| `docs/HEALTH.md`    | `<Probe> check`      | `Liveness check`, `Readiness check`, `Startup check`         |
| `docs/WEBHOOKS.md`  | `<Provider> webhook` | `Asaas webhook`                                              |

## Mechanical derivation

Three derived names share a single source, the Endpoint Name. The Endpoint Name in the docs page MUST match the
`summary` of the same endpoint in `openapi.yaml`. The anchor slug is what Markdown auto-generates from the heading
(lowercase, spaces replaced with hyphens). The `operationId` replaces the spaces with case boundaries and lowercases the
first letter.

| Endpoint Name                   | Anchor slug (`kebab-case`)      | OpenAPI `operationId` (`camelCase`) |
|:--------------------------------|:--------------------------------|:------------------------------------|
| `Payment creating`              | `payment-creating`              | `paymentCreating`                   |
| `Payment capturing`             | `payment-capturing`             | `paymentCapturing`                  |
| `Payment refunding`             | `payment-refunding`             | `paymentRefunding`                  |
| `Find payment by id`            | `find-payment-by-id`            | `findPaymentById`                   |
| `Find payments by organization` | `find-payments-by-organization` | `findPaymentsByOrganization`        |
| `Liveness check`                | `liveness-check`                | `livenessCheck`                     |
| `Readiness check`               | `readiness-check`               | `readinessCheck`                    |
| `Asaas webhook`                 | `asaas-webhook`                 | `asaasWebhook`                      |

When any of the three names changes, all three change together in the same commit, per the synchronization invariant
owned by `web-documentation`.
