# Documentation page structure

Use this shape when writing a per-endpoint entry in `docs/USE_CASES.md`, `docs/QUERIES.md`, `docs/HEALTH.md`, or
`docs/WEBHOOKS.md`.

## Contents

- Table of contents
- Per-endpoint section (header, method and URL, headers, request, response)
- Response blocks (with body, without body, with notable headers)
- Variants (paginated, rate-limited, idempotent)
- Health check page

`````text
# Endpoint docs page structure (template)

`docs/USE_CASES.md` (write operations), `docs/QUERIES.md` (read operations), `docs/HEALTH.md`
(health and observability), and `docs/WEBHOOKS.md` (inbound webhook handlers) all share one strict
per-endpoint structure. The format is identical across files. Which endpoints exist, their paths,
methods, fields, status codes, headers, and error codes are contract facts owned by the spec. Read
them via `php-reading-spec`, do not invent them. The naming derivation and the fixed-wording tables live
under `references/`.

**Omit empty sections.** Render a section or table only when it has content. If an endpoint has no
body parameters, no path parameters, no query parameters, or no notable response headers, omit the
subsection and table entirely. Never render an empty table or a table with a single placeholder row.
Render the Headers table whenever the endpoint requires at least one header, including
`Authorization`.

## Table of contents

`docs/USE_CASES.md`, `docs/QUERIES.md`, and `docs/HEALTH.md` start with an anchor-linked bullet list
at the top of the file, with no heading and no preamble. Each bullet points to the auto-generated
anchor of an endpoint heading further down the file.

```
* [<Endpoint name>](#<anchor-slug>)
* [<Endpoint name>](#<anchor-slug>)
```

## Per-endpoint section

### Endpoint header

The `## <Endpoint name>` heading IS the anchor target. Markdown auto-generates `#endpoint-name` from
it. No `<div id='...'></div>` HTML anchor is added. When the endpoint is restricted to internal
callers, add the blockquote note directly below the purpose line. The note states the fact and stops
there, it does not name the caller or cite a spec section (whether an endpoint is internal is a
contract fact owned by the spec).

```
## <Endpoint name>

#### <One-line purpose description>.

> **Note:** Internal endpoint.
```

### HTTP method and URL

```
**<METHOD>** `{{<service-name>-dns}}/v1/<path>`
```

### Headers

| Header | Type | Description | Constraints | Required |
|:-------|:----:|:------------|:------------|:--------:|

Use the fixed wording from `references/fixed-wording.md` for `Content-Type`, `Authorization`, and
`Idempotency-Key`. `Content-Type` is mandatory whenever the endpoint accepts a request body.
`Authorization` is mandatory whenever the endpoint requires authentication. Omit the entire
`### Headers` section when the endpoint has no headers.

### Request

**Path and query parameters**:

| Parameter | Type | Description | Constraints | Required |
|:----------|:----:|:------------|:------------|:--------:|

**Body parameters**:

| Parameter | Type | Description | Constraints | Required |
|:----------|:----:|:------------|:------------|:--------:|

Followed by a complete JSON example containing every field from the table, including optional fields
with realistic values. Parameters in the table are ordered by name length ascending.

### Response section

Every possible status code is documented with the same block structure. All applicable lines
(`Description`, `Content-Type`, `Body`, notable `Headers`) appear on every response, success and
error alike. Shorthand or omission is prohibited. Status codes are listed in ascending numerical
order: `200`, `204`, `401`, `404`, `409`, `422`, `500`.

**Responses WITH a body** (any status code that returns content):

````
- `<STATUS_CODE> <STATUS_TEXT>`

  **Description**: <What this status means in the context of this endpoint.>

  **Content-Type**: application/json

  **Body**:
  ```json
  { ... }
  ```
````

When a status code has multiple possible error codes, list each variant in its own fenced block with
an `or` separator, one JSON body per block. Never stack multiple JSON objects inside one fenced block
and never wrap them in a JSON array, because each documented body is a single top-level object.

````
  ```json
  {
      "code": "FIRST_ERROR_CODE",
      "message": "First error description."
  }
  ```

  or when <condition>:

  ```json
  {
      "code": "SECOND_ERROR_CODE",
      "message": "Second error description."
  }
  ```
````

**Responses WITHOUT a body** (`204 No Content`):

````
- `204 No Content`

  **Description**: <What this status means in the context of this endpoint.>

  **Content-Type**: _(no content)_
````

**Responses with notable headers** (e.g., `Location`, `Retry-After`):

````
- `<STATUS_CODE> <STATUS_TEXT>`

  **Description**: <What this status means in the context of this endpoint.>

  **Headers**:
  | Header     |  Type  | Description                        | Constraints                                   | Required |
  |:-----------|:------:|:-----------------------------------|:----------------------------------------------|:--------:|
  | `Location` | String | URI of the newly created resource. | Must be an absolute URI. E.g., `/v1/users/1`. |   Yes    |

  **Content-Type**: application/json

  **Body**:
  ```json
  { ... }
  ```
````

## Variant: paginated endpoints

Add `page` and `per_page` as query parameters in the Request section and include the `pagination`
envelope in the response example. The pagination defaults and maximum are contract facts owned by the
spec, read them via `php-reading-spec`.

| Parameter  |  Type   | Description                         | Constraints                             | Required |
|:-----------|:-------:|:------------------------------------|:----------------------------------------|:--------:|
| `page`     | Integer | Page number (1-indexed). E.g., `1`. | Must be greater than or equal to 1.     |    No    |
| `per_page` | Integer | Items per page. E.g., `20`.         | Must be between 1 and 100. Default: 20. |    No    |

## Variant: rate-limited endpoints

Add a `429 Too Many Requests` entry in the response section with the `Retry-After` header. The
threshold and the `RATE_LIMIT_EXCEEDED` envelope are contract facts owned by the spec.

````
- `429 Too Many Requests`

  **Description**: Indicates that the rate limit has been exceeded.

  **Headers**:
  | Header        |  Type   | Description                                      | Constraints                             | Required |
  |:--------------|:-------:|:-------------------------------------------------|:----------------------------------------|:--------:|
  | `Retry-After` | Integer | Seconds until the client may retry the request.  | Must be a positive integer. E.g., `60`. |   Yes    |

  **Content-Type**: application/json

  **Body**:
  ```json
  {
      "code": "RATE_LIMIT_EXCEEDED",
      "message": "Too many requests. Try again later."
  }
  ```
````

## Variant: idempotent endpoints

Add `Idempotency-Key` in the Headers table using the fixed wording from `references/fixed-wording.md`.
The idempotency semantics (UUID v7, retention) are contract facts owned by the spec.

## Health check page

Health and observability endpoints (liveness, readiness, metrics) live in `docs/HEALTH.md`. They
describe operational contracts, not domain operations, and never go in `USE_CASES.md` or
`QUERIES.md`. `docs/HEALTH.md` follows the same per-endpoint structure above. Health endpoints
typically:

- Do not require `Authorization`.
- Return `200 OK` with a minimal body (`{ "status": "ok" }`) or empty when healthy. The 200 body is
  intentionally exempt from the `{ "code": "...", "message": "..." }` error structure, that structure
  applies to error responses and a healthy probe is a success.
- Return `503 Service Unavailable` when not ready. The body MAY follow the standard error envelope or
  the domain-specific diagnostic shape from `references/fixed-wording.md`. When the diagnostic shape
  is used, both the docs page and `openapi.yaml` reference the same schema under
  `components/schemas/`.
`````
