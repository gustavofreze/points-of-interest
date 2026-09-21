<?php

declare(strict_types=1);

namespace Test\Integration;

use Doctrine\DBAL\Connection;

final readonly class Fixtures
{
    private function __construct(private Connection $connection)
    {
    }

    public static function from(Connection $connection): Fixtures
    {
        return new Fixtures(connection: $connection);
    }

    public function purgeAll(): void
    {
        $this->truncatePointsOfInterest();
        $this->truncateOutboxEvents();
    }

    public function truncateOutboxEvents(): void
    {
        $this->connection->executeStatement(sql: 'DELETE FROM outbox_events');
    }

    public function truncatePointsOfInterest(): void
    {
        $this->connection->executeStatement(sql: 'DELETE FROM points_of_interest');
    }

    public function insertPointOfInterest(
        string $id,
        string $name,
        string $createdAt,
        int $xCoordinate,
        int $yCoordinate
    ): void {
        $this->connection->executeStatement(
            sql: '
                INSERT INTO points_of_interest (id, name, x_coordinate, y_coordinate, created_at)
                VALUES (UUID_TO_BIN(:id), :name, :xCoordinate, :yCoordinate, :createdAt)
            ',
            params: [
                'id'          => $id,
                'name'        => $name,
                'createdAt'   => $createdAt,
                'xCoordinate' => $xCoordinate,
                'yCoordinate' => $yCoordinate
            ]
        );
    }

    public function pointOfInterestCountOf(string $name): int
    {
        $count = $this->connection->fetchOne(
            query: '
                SELECT COUNT(*) AS total
                FROM points_of_interest AS poi
                WHERE poi.name = :name
            ',
            params: ['name' => $name]
        );

        return (int)$count;
    }

    public function outboxPayloadOf(string $eventType, string $pointOfInterestId): array
    {
        $payload = $this->connection->fetchOne(
            query: '
                SELECT evt.payload
                FROM outbox_events AS evt
                WHERE evt.aggregate_id = UUID_TO_BIN(:pointOfInterestId)
                  AND evt.event_type = :eventType
            ',
            params: [
                'eventType'         => $eventType,
                'pointOfInterestId' => $pointOfInterestId
            ]
        );

        return (array)json_decode((string)$payload, true);
    }

    public function outboxEventTypesOf(string $pointOfInterestId): array
    {
        return $this->connection->fetchFirstColumn(
            query: '
                SELECT evt.event_type
                FROM outbox_events AS evt
                WHERE evt.aggregate_id = UUID_TO_BIN(:pointOfInterestId)
                ORDER BY evt.aggregate_version
            ',
            params: ['pointOfInterestId' => $pointOfInterestId]
        );
    }
}
