# Gateway two-tier shape

Use when a provider has more than trivial plumbing (auth headers on every call, idempotency keys, or several operations
sharing one send path), splitting the Client transport tier from the Gateway translation tier.

```php
<?php

declare(strict_types=1);

// Two-tier canonical shape. Use when the provider has more than trivial plumbing
// (auth headers on every call, idempotency keys, several operations sharing one send path).
//
// <Provider>Client at src/Driven/<Context>/Providers/<Provider>/<Provider>Client.php.
// It is the only place that names Http, builds the Request, attaches provider headers, and
// translates a non-success response into an application exception through the <Gateway>Error enum.

final readonly class <Provider>Client
{
    public function __construct(private Http $http, private <Provider>ApiSettings $settings)
    {
    }

    public function get(string $url): array
    {
        $request = Request::get(
            url: $url,
            headers: Headers::fromArray(entries: ['<auth-header>' => $this->settings->apiKey])
        );

        return $this->send(request: $request);
    }

    public function post(string $url, array $body, string $idempotencyKey): array
    {
        $request = Request::post(
            url: $url,
            body: $body,
            headers: Headers::fromArray(entries: [
                '<auth-header>'   => $this->settings->apiKey,
                'Idempotency-Key' => $idempotencyKey
            ])
        );

        return $this->send(request: $request);
    }

    public function send(Request $request): array
    {
        try {
            $response = $this->http->send(request: $request);
        } catch (HttpException) {
            throw <Gateway>Error::from(code: null)->toException();
        }

        if (!$response->isSuccess()) {
            throw <Gateway>Error::from(code: $response->code())->toException();
        }

        return $response->body()->toArray();
    }
}

// <Provider><Resource>Gateway implements the port, depends on the Client (and a status translator only
// when the provider returns provider-specific status strings), and maps the decoded payload to a domain
// object. It never touches Http or the Settings VO.

final readonly class <Provider><Resource>Gateway implements <Resource>Gateway
{
    public function __construct(
        private <Provider>Client $client,
        private <Provider><Resource>StatusTranslator $translator
    ) {
    }

    public function <operation>(<Aggregate> $<aggregate>): <Result>
    {
        $body = <Resource>Request::from(<aggregate>: $<aggregate>)->toArray();
        $response = $this->client->post(
            url: '/<resource>',
            body: $body,
            idempotencyKey: $<aggregate>->id->identityValue()
        );

        return <Resource>Response::from(body: $response)->toDomain();
    }

    public function <status>(<Identifier> $<identifier>): <Status>
    {
        $payload = $this->client->get(url: "/<resource>/{$<identifier>->toString()}");

        return $this->translator->to<Status>(status: $payload['status']);
    }
}
```
