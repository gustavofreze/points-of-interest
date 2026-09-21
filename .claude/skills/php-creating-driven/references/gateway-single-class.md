# Gateway single-class shape

Use only for trivial plumbing (one operation, no idempotency key, no status translation, a single header), where the
Gateway depends on Http and the Settings VO directly.

```php
<?php

declare(strict_types=1);

// Single-class variant. Use ONLY for trivial plumbing (one operation, no idempotency key, no status
// translation, a single header). The Client tier is omitted and the Gateway depends on Http and the
// Settings VO directly. Promote to the two-tier shape as soon as a second operation, an idempotency key,
// or provider-specific status translation appears.

final readonly class <Provider><Resource>Gateway implements <Resource>Gateway
{
    public function __construct(
        private Http $http,
        private <Provider>ApiSettings $settings
    ) {
    }

    public function <operation>(<Aggregate> $<aggregate>): <Result>
    {
        try {
            $headers = Headers::fromArray(entries: ['<auth-header>' => $this->settings->apiKey]);
            $request = Request::get(url: "/<resource>/{$<aggregate>->id->toString()}", headers: $headers);
            $response = $this->http->send(request: $request);
        } catch (HttpException) {
            throw <Gateway>Error::from(code: null)->toException();
        }

        if (!$response->isSuccess()) {
            throw <Gateway>Error::from(code: $response->code())->toException();
        }

        return <Result>::from(<field>: $response->body()->get(key: '<field>')->toString());
    }
}
```
