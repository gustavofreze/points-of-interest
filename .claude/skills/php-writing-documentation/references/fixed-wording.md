# Fixed documentation wording (reference)

Read this while filling header tables and response blocks. These tables fix the documentation wording so the docs pages
and `openapi.yaml` do not drift apart. They are the single source of wording across both renderings.

The contract facts these tables render are owned by the spec, not by this skill: the error-code values, the
`{ code, message }` envelope shape, the `Authorization` bearer-token mechanism, the proper name of the identity provider
that issues the token, the `Idempotency-Key` UUID v7 semantics, and the rate-limit thresholds. Read those via
`php-reading-spec`, do not invent them. What lives here is only the fixed phrasing that keeps the two documentation
renderings identical.

These tables are copied verbatim into every docs page and into `openapi.yaml`, so they never name a sibling service. A
proper name written here is published in the API contract of every service that renders it. Where the issuer matters to
the consumer, the spec supplies the name and the docs page carries it, the table does not.

## Standard headers (fixed wording)

When `Content-Type` or `Authorization` appear in the Headers table, use the exact wording below to avoid drift between
endpoints.

| Header          |  Type  | Description                                                          | Constraints                | Required |
|:----------------|:------:|:---------------------------------------------------------------------|:---------------------------|:--------:|
| `Content-Type`  | String | The request content type.                                            | Must be application/json.  |   Yes    |
| `Authorization` | String | Bearer token issued by the identity provider. E.g., `Bearer eyJ...`. | Must start with `Bearer `. |   Yes    |

When an endpoint accepts an idempotency key, use this wording for `Idempotency-Key`.

| Header            |  Type  | Description                                 | Constraints                                                                | Required |
|:------------------|:------:|:--------------------------------------------|:---------------------------------------------------------------------------|:--------:|
| `Idempotency-Key` | String | Unique key to ensure idempotent processing. | Must be a valid UUID format. E.g., `550e8400-e29b-41d4-a716-446655440000`. |    No    |

## Standard responses (fixed wording)

These responses appear on most endpoints. Their `Description` text is fixed: use the exact wording across all docs and
OpenAPI. `401` and `500` apply to virtually every endpoint that requires authentication (`401`) and that runs domain
logic (`500`). `503` applies only when the endpoint can legitimately report the service as not ready, typically health
check endpoints under `docs/HEALTH.md`. Do not list `503` on endpoints that cannot return it.

| Status | Fixed description                                                                       |
|:-------|:----------------------------------------------------------------------------------------|
| `401`  | Indicates that the request is missing a valid authentication token.                     |
| `500`  | Indicates that an unexpected error occurred on the server while processing the request. |
| `503`  | Indicates that the service is not ready to handle requests.                             |

The `code` and `message` values for these mirror the spec-owned error envelope.

- `401`: `{ "code": "UNAUTHORIZED", "message": "Missing Authorization header." }`
- `500`: `{ "code": "INTERNAL_ERROR", "message": "An unexpected error occurred." }`
- `503`: `{ "code": "SERVICE_UNAVAILABLE", "message": "The service is not ready." }`

## Domain-specific status codes (carve-out)

A status code carries domain semantics when its emission is tied to a specific business or session-lifecycle condition
rather than the generic protocol-level meaning. In those cases the fixed wording above is replaced by a custom,
consumer-actionable description. The same custom wording MUST appear identically across the docs page and
`openapi.yaml`. Whether a status code carries domain semantics is a contract decision owned by the spec.

| Status | Endpoint context                              | Custom wording covers                                                                        |
|:-------|:----------------------------------------------|:---------------------------------------------------------------------------------------------|
| `401`  | Endpoints validating credentials or sessions  | Credential failures and session-lifecycle failures, not bearer-token absence.                |
| `503`  | Health check endpoints with diagnostic bodies | Probe failure with a structured diagnostic body, not the standard `{ code, message }` shape. |

Constraints on the carve-out:

- The custom description must be precise and consumer-actionable. Vague descriptions ("an error occurred") are
  prohibited.
- The `code` value follows the standard `SCREAMING_SNAKE_CASE` convention. The carve-out is for the `description` text,
  not for the error-code naming.
- When a `503` returns a diagnostic body, the schema MUST be defined under `components/schemas/` in `openapi.yaml` and
  referenced from the `503` response. Inline schemas remain prohibited. All other endpoints that emit `503` continue to
  use the standard error envelope above.
- Endpoints that emit `401` for the generic missing-or-invalid bearer-token reason continue to use the standard fixed
  wording. The carve-out applies only to endpoints whose `401` reflects a domain condition.
- When in doubt about whether a status code is generic-protocol or domain-specific, default to the standard fixed
  wording. The carve-out is the exception, not the rule.
