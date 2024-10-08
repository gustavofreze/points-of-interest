<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Mocks;

use Slim\Psr7\Environment;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Uri;

final class RequestHttpMock
{
    public static function getFrom(string $path, string $query = ''): Request
    {
        return self::createRequest(method: 'GET', path: $path, query: $query);
    }

    public static function postFrom(string $path, string $payload): Request
    {
        return self::createRequest(method: 'POST', path: $path, body: $payload);
    }

    private static function createRequest(
        string $method,
        string $path,
        ?string $body = '',
        ?string $query = ''
    ): Request {
        $serverParams = Environment::mock();
        $uri = new Uri(
            $serverParams['REQUEST_SCHEME'],
            $serverParams['SERVER_NAME'],
            $serverParams['SERVER_PORT'],
            $path,
            $query
        );
        $stream = (new StreamFactory())->createStream($body);
        $headers = new Headers(['HTTP_ACCEPT' => 'application/json']);

        return new Request(
            $method,
            $uri,
            $headers,
            [],
            $serverParams,
            $stream
        );
    }
}
