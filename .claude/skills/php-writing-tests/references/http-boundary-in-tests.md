# HTTP boundary in tests shape

Use this shape when exercising an outbound HTTP gateway, faking the boundary with InMemoryTransport and asserting on the
recorded request.

```php
<?php

declare(strict_types=1);

/**
 * HTTP boundary in tests. Fake the boundary with InMemoryTransport and nothing else. Pre-build
 * responses with Response::with(...), served FIFO. Inject the Http facade built over the transport
 * into the gateway adapter under test, exactly as Http over NetworkTransport is injected in
 * production. Exercise outbound gateways in tests/Integration/, never tests/Unit/.
 */

use TinyBlocks\Http\Client\Response;
use TinyBlocks\Http\Client\Transports\InMemoryTransport;
use TinyBlocks\Http\Http;

$transport = InMemoryTransport::with(responses: [
    Response::with(body: ['id' => 'charge-201', 'status' => 'paid'], code: Code::OK)
]);
$http = Http::with(baseUrl: $settings->baseUrl, transport: $transport);

// Drive the adapter, then assert on the recorded request that Http resolved (absolute URL,
// merged default headers). receivedRequests() for all, lastReceivedRequest() for the latest.
$recorded = $transport->lastReceivedRequest();
self::assertSame('POST', $recorded->method());
self::assertSame('https://api.provider.example/v3/payments', $recorded->url());

// Queue a follow-up Response when the effect across invocations matters. An unexpected extra
// call against an exhausted queue surfaces as TinyBlocks\Http\Exceptions\NoMoreResponses.
```
