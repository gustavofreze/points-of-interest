<?php

declare(strict_types=1);

namespace Test\Unit\Driver\Http\Endpoints\PointOfInterest;

use PointsOfInterest\Application\Commands\RegisterPointOfInterest;
use PointsOfInterest\Application\Ports\Inbound\PointOfInterestRegistration;
use Throwable;

final class PointOfInterestRegistrationSpy implements PointOfInterestRegistration
{
    public ?RegisterPointOfInterest $received = null;

    public function __construct(private readonly ?Throwable $failure = null)
    {
    }

    public function handle(RegisterPointOfInterest $command): void
    {
        $this->received = $command;

        if (!is_null($this->failure)) {
            throw $this->failure;
        }
    }
}
