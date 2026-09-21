<?php

declare(strict_types=1);

namespace Test\Integration;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\UriFactory;

final readonly class PointOfInterestRequests
{
    private const string BASE_URL = 'https://points-of-interest.localhost';

    public static function get(string $path): ServerRequestInterface
    {
        $uri = new UriFactory()->createUri(PointOfInterestRequests::BASE_URL . $path);

        return new ServerRequestFactory()->createServerRequest('GET', $uri);
    }
}
