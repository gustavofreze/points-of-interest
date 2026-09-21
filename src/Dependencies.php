<?php

declare(strict_types=1);

namespace PointsOfInterest;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Pdo\Mysql;
use PointsOfInterest\Application\Handlers\PointOfInterestRegistrationHandler;
use PointsOfInterest\Application\Ports\Inbound\PointOfInterestRegistration;
use PointsOfInterest\Application\Ports\Outbound\PointsOfInterest;
use PointsOfInterest\Driven\PointOfInterest\Outbox\Event\PointOfInterestRegistered;
use PointsOfInterest\Driven\PointOfInterest\Outbox\PointOfInterestEventPayloadSerializer;
use PointsOfInterest\Driven\PointOfInterest\Outbox\PointOfInterestEventTranslator;
use PointsOfInterest\Driven\PointOfInterest\Repository\PointOfInterestRepository;
use PointsOfInterest\Driven\Shared\Database\MySql\MySqlEngine;
use PointsOfInterest\Driven\Shared\Database\RelationalConnection;
use PointsOfInterest\Driver\Http\DriverExceptionMapping;
use PointsOfInterest\Query\PointOfInterest\FindAll\Database\PointsOfInterestFindingAdapter;
use PointsOfInterest\Query\PointOfInterest\FindAll\PointsOfInterestFinding;
use PointsOfInterest\Query\Shared\Http\QueryExceptionMapping;
use Psr\Container\ContainerInterface;
use TinyBlocks\BuildingBlocks\Event\IntegrationEventTranslators;
use TinyBlocks\Http\ErrorHandler\ErrorHandlingSettings;
use TinyBlocks\Http\ErrorHandler\ErrorMiddleware;
use TinyBlocks\Http\Logging\LogMiddleware;
use TinyBlocks\HttpHealthCheck\DoctrineHealthCheck;
use TinyBlocks\HttpHealthCheck\DrainMarker;
use TinyBlocks\HttpHealthCheck\HealthChecks;
use TinyBlocks\HttpHealthCheck\LivenessHandler;
use TinyBlocks\HttpHealthCheck\ReadinessHandler;
use TinyBlocks\Logger\Logger;
use TinyBlocks\Logger\Redactions\SecretRedaction;
use TinyBlocks\Logger\StreamLogger;
use TinyBlocks\Mapper\Mapper;
use TinyBlocks\Mapper\SnakeCase;
use TinyBlocks\Mapper\Structured;
use TinyBlocks\Outbox\DoctrineOutboxRepository;
use TinyBlocks\Outbox\OutboxRepository;
use TinyBlocks\Outbox\Serialization\PayloadSerializers;

use function DI\autowire;

final readonly class Dependencies
{
    public static function definitions(): array
    {
        return [
            ...self::query(),
            ...self::driven(),
            ...self::driver(),
            ...self::shared(),
            ...self::application()
        ];
    }

    private static function query(): array
    {
        return [
            PointsOfInterestFinding::class => autowire(PointsOfInterestFindingAdapter::class)
        ];
    }

    private static function driven(): array
    {
        return [
            Connection::class           => static function (ContainerInterface $container): Connection {
                /** @var DatabaseSettings $settings */
                $settings = $container->get(DatabaseSettings::class);

                return DriverManager::getConnection([
                    'driver'        => 'pdo_mysql',
                    'host'          => $settings->host,
                    'user'          => $settings->user,
                    'port'          => $settings->port,
                    'dbname'        => $settings->name,
                    'charset'       => 'utf8mb4',
                    'password'      => $settings->password,
                    'driverOptions' => [
                        Mysql::ATTR_INIT_COMMAND     => 'SET time_zone = "+00:00"',
                        Mysql::ATTR_EMULATE_PREPARES => false
                    ]
                ], new Configuration());
            },
            PointsOfInterest::class     => autowire(PointOfInterestRepository::class),
            OutboxRepository::class     => static function (ContainerInterface $container): OutboxRepository {
                $mapper = Mapper::create()
                    ->withNaming(namingStrategy: SnakeCase::create())
                    ->withMapping(type: PointOfInterestRegistered::class, mapping: Structured::create());

                return new DoctrineOutboxRepository(
                    connection: $container->get(Connection::class),
                    serializers: PayloadSerializers::createFrom(
                        elements: [new PointOfInterestEventPayloadSerializer(mapper: $mapper)]
                    ),
                    translators: IntegrationEventTranslators::createFrom(
                        elements: [new PointOfInterestEventTranslator()]
                    )
                );
            },
            RelationalConnection::class => autowire(MySqlEngine::class)
        ];
    }

    private static function driver(): array
    {
        return [
            LogMiddleware::class    => static function (ContainerInterface $container): LogMiddleware {
                return LogMiddleware::create()
                    ->withLogger(logger: $container->get(Logger::class))
                    ->build();
            },
            ErrorMiddleware::class  => static function (ContainerInterface $container): ErrorMiddleware {
                /** @var AppSettings $appSettings */
                $appSettings = $container->get(AppSettings::class);

                return ErrorMiddleware::create()
                    ->withLogger(logger: $container->get(Logger::class))
                    ->withMappings(new DriverExceptionMapping(), new QueryExceptionMapping())
                    ->withSettings(
                        settings: ErrorHandlingSettings::from(
                            logErrors: true,
                            logErrorDetails: true,
                            displayErrorDetails: $appSettings->debug
                        )
                    )
                    ->build();
            },
            LivenessHandler::class  => static fn(): LivenessHandler => LivenessHandler::create(),
            ReadinessHandler::class => static function (ContainerInterface $container): ReadinessHandler {
                $checks = HealthChecks::createFromEmpty()
                    ->withCritical(check: DoctrineHealthCheck::from(connection: $container->get(Connection::class)));

                return ReadinessHandler::from(checks: $checks, drainMarker: DrainMarker::default());
            }
        ];
    }

    private static function shared(): array
    {
        return [
            Logger::class           => static function (ContainerInterface $container): Logger {
                /** @var AppSettings $appSettings */
                $appSettings = $container->get(AppSettings::class);

                return StreamLogger::builder()
                    ->withComponent(component: $appSettings->appName)
                    ->withRedactions(SecretRedaction::default())
                    ->build();
            },
            AppSettings::class      => static fn(): AppSettings => AppSettings::fromEnvironment(),
            DatabaseSettings::class => static fn(): DatabaseSettings => DatabaseSettings::fromEnvironment()
        ];
    }

    private static function application(): array
    {
        return [
            PointOfInterestRegistration::class => autowire(PointOfInterestRegistrationHandler::class)
        ];
    }
}
