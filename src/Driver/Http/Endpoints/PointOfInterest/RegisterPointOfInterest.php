<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints\PointOfInterest;

use PointsOfInterest\Application\Ports\Inbound\PointOfInterestRegistration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TinyBlocks\Http\Server\Response;

final readonly class RegisterPointOfInterest implements RequestHandlerInterface
{
    public function __construct(private PointOfInterestRegistration $registration)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $payload = json_decode($request->getBody()->__toString(), true);
        $command = new Request(payload: (array)$payload)->toCommand();

        $this->registration->handle(command: $command);

        return Response::created(body: ['id' => $command->id->identityValue()]);
    }
}
