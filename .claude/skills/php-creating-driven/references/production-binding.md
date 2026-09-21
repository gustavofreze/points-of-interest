# Production HTTP binding

Use in src/Dependencies.php to build the production Http facade over NetworkTransport, the only place that names the
PSR-18 client and PSR-17 factory.

```php
<?php

declare(strict_types=1);

// src/Dependencies.php builds the production Http over NetworkTransport, wrapping the PSR-18 client and
// PSR-17 factory, with timeouts and TLS verification on the client. Retry, circuit breaker, and logging
// wrap NetworkTransport as Transport decorators BEFORE the facade is built. The PSR-18 client and factory
// are the only concrete HTTP types named in the whole codebase, and they are named only here.

$client = new GuzzleClient(config: [/* connect_timeout, timeout, verify => true */]);
$factory = new HttpFactory();
$transport = NetworkTransport::with(client: $client, factory: $factory);

$http = Http::with(baseUrl: $settings->baseUrl, transport: $transport);

$gateway = new <Provider><Resource>Gateway(http: $http, settings: $settings);
```
