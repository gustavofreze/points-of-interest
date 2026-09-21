<?php

declare(strict_types=1);

namespace Test\Unit;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Stream;

final class RequestFactory
{
    private const string LOCALHOST = 'points-of-interest.localhost';

    public static function postFrom(array $payload): ServerRequestInterface
    {
        $uri = new UriFactory()
            ->createUri()
            ->withScheme('https')
            ->withHost(RequestFactory::LOCALHOST)
            ->withPath('/');

        /** @var resource $stream */
        $stream = fopen('php://temp', 'r+');

        fwrite($stream, json_encode($payload));
        rewind($stream);

        $body = new Stream($stream);
        $headers = new Headers(['Content-Type' => 'application/json']);

        return new SlimRequest(
            uri: $uri,
            body: $body,
            method: 'POST',
            cookies: [],
            headers: $headers,
            serverParams: []
        );
    }

    public static function getWith(array $queryParameters): ServerRequestInterface
    {
        $uri = new UriFactory()
            ->createUri()
            ->withScheme('https')
            ->withHost(RequestFactory::LOCALHOST)
            ->withPath('/points-of-interest')
            ->withQuery(http_build_query($queryParameters));

        /** @var resource $stream */
        $stream = fopen('php://temp', 'r+');

        return new SlimRequest(
            uri: $uri,
            body: new Stream($stream),
            method: 'GET',
            cookies: [],
            headers: new Headers(),
            serverParams: []
        );
    }
}
