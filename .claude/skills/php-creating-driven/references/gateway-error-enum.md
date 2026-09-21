# Gateway error enum

Use to own the single mapping from an HTTP status code to an application exception, classifying with from () and
instantiating with toException ().

```php
<?php

declare(strict_types=1);

// <Gateway>Error enum under src/Driven/<Context>/Providers/. Owns the single mapping from HTTP status
// to application exception. from(?Code) classifies, toException() instantiates. Each is a match over a
// single closed set. null maps to the timeout case (a transport failure leaves the outcome unknown).

enum <Gateway>Error
{
    case TIMEOUT;
    case REJECTED;
    case AUTH_FAILED;
    case UNAVAILABLE;
    case RATE_LIMITED;

    public static function from(?Code $code): <Gateway>Error
    {
        if (is_null($code)) {
            return <Gateway>Error::TIMEOUT;
        }

        if ($code->isTimeout()) {
            return <Gateway>Error::TIMEOUT;
        }

        if ($code->isServerError()) {
            return <Gateway>Error::UNAVAILABLE;
        }

        return match ($code) {
            Code::UNAUTHORIZED,
            Code::FORBIDDEN         => <Gateway>Error::AUTH_FAILED,
            Code::TOO_MANY_REQUESTS => <Gateway>Error::RATE_LIMITED,
            default                 => <Gateway>Error::REJECTED
        };
    }

    public function toException(): Throwable
    {
        return match ($this) {
            <Gateway>Error::TIMEOUT      => new <Operation>GatewayTimeout(),
            <Gateway>Error::REJECTED     => new <Operation>RejectedByGateway(),
            <Gateway>Error::AUTH_FAILED  => new <Operation>GatewayAuthFailed(),
            <Gateway>Error::UNAVAILABLE  => new <Operation>GatewayUnavailable(),
            <Gateway>Error::RATE_LIMITED => new <Operation>RateLimitedByGateway()
        };
    }
}
```
