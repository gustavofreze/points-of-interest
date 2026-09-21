# OpenAPI structure

Use this shape when adding or updating an operation, schema, or tag in `openapi.yaml`.

## Contents

- Structural invariants
- Skeleton (paths, operations, components and schemas)

`````yaml
# openapi.yaml structure (template)

`openapi.yaml` is the inbound API contract in machine-readable form, placed at the service root and
versioned with the source code. It and the docs pages are two renderings of one contract, kept in
sync by the `web-documentation` rule. Which endpoints, schemas, fields, tags, and error codes exist
are contract facts owned by the spec. Read them via `php-reading-spec`, do not invent them. The Endpoint
Name to `operationId` derivation lives in `references/naming-derivation.md`. Prose inside
`description` fields follows the documentation prose style owned by `web-documentation`.

## Structural invariants

1. Place `openapi.yaml` at the service root, versioned with source code.
2. Document all mandatory headers, input and output schemas, and every possible error code
   exhaustively.
3. Follow the standardized error structure `{ "code": "...", "message": "..." }`. The envelope shape
   and the error-code values are contract facts owned by the spec.
4. Every endpoint has a unique `operationId` derived from the Endpoint Name in `camelCase`. See
   `references/naming-derivation.md` for the mechanical derivation.
5. Every endpoint has exactly one `tag`. A tag groups a cohesive set of endpoints within the bounded
   context. A service MAY define multiple tags when its endpoints split naturally into distinct
   subdomains. The intent is navigability in Swagger UI, not a strict 1:1 mapping between tags and
   services. Health check endpoints always use the `Health` tag, which is not a bounded context. The
   real tag set comes from the spec.
6. Every `422` response with multiple possible error codes uses `examples` (plural) with a named key
   per variant. Every `4xx` response with a single specific code uses `example` (singular) with the
   inline object.
7. Every property in request and response schemas includes `description` and `example`.
8. Properties and `required` lists in schemas are ordered by name length ascending, consistent with
   the codebase convention.
9. Enum properties always include the `enum` keyword with the full list of allowed values.
10. Nullable properties declare `null` as a member of `type`, never the `nullable` keyword. OpenAPI 3.1 aligns
    the Schema Object with JSON Schema 2020-12, which removed `nullable` outright, so a 3.1 parser ignores it and
    the property reads as non-nullable. Use `type: [<type>, 'null']`. When the property is a `$ref`, use
    `oneOf: [- $ref, - type: 'null']` and keep the `description` as a sibling of `oneOf`.
11. Reusable schemas live in `components/schemas/`. Inline schemas are prohibited for request bodies
    and response objects.
12. `description` text for shared status codes (`401`, `500`, `503`) uses the exact wording from
    `references/fixed-wording.md`, the single source of wording across docs and OpenAPI.
13. Paginated endpoints define `page` and `per_page` as `parameters` with `default` and `maximum`
    values. The defaults and maximum are contract facts owned by the spec.
14. Rate-limited endpoints include a `429` response with a `Retry-After` header in the `headers`
    section.
15. Idempotent `POST` endpoints define `Idempotency-Key` as an optional header parameter. The
    idempotency semantics are contract facts owned by the spec.

## Skeleton

```yaml
openapi: 3.1.0 info: title: <Service> API version: <version> tags:
  - name: <Tag>
  - name: Health paths: /v1/<path>:
    <method>:
      operationId: <operationId>
      tags:
        - <Tag>
      summary: <Endpoint name>
      description: <One-line purpose>.
      parameters:
        - name: <param>
          in: <query|path|header>
          required: <true|false>
          schema:
            $ref: '#/components/schemas/<Schema>'
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/<RequestSchema>'
      responses:
        '<code>':
          description: <Fixed or domain-specific wording>.
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/<ResponseSchema>'
              examples:
                <variantKey>:
                  value:
                    code: <ERROR_CODE>
                    message: <Error description>.
components: schemas:
    <Schema>:
      type: object
      required:
        - <field>
      properties:
        <field>:
          type: <type>
          enum: [<value>]
          description: <Field description>.
          example: <example>
        <nullable-field>:
          type: [<type>, 'null']
          description: <Field description>.
          example: <example>
        <nullable-ref-field>:
          description: <Field description>.
          oneOf:
            - $ref: '#/components/schemas/<Schema>'
            - type: 'null'
```
`````
