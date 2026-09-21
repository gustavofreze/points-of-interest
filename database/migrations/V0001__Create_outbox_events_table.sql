CREATE TABLE outbox_events
(
    id                BINARY(16)   NOT NULL COMMENT '[NONE] The outbox event identifier in Version 7 UUID format (e.g., 0190d09e-a7a8-7e89-b48c-09fff0f0f0f0).',
    payload           JSON         NOT NULL COMMENT '[NONE] The recorded fact serialized as a JSON object in snake_case, which carries the registered point and where it sits on the plane (e.g., {"name": "Lanchonete", "x_coordinate": 27, "y_coordinate": 12}).',
    revision          INT          NOT NULL COMMENT '[NONE] The schema revision of the serialized payload (e.g., 1).',
    event_type        VARCHAR(255) NOT NULL COMMENT '[NONE] The kind of fact the event records in CamelCase (e.g., PointOfInterestRegistered).',
    aggregate_id      BINARY(16)   NOT NULL COMMENT '[NONE] The identifier of the aggregate that recorded the event in Version 7 UUID format (e.g., 0190d09e-a7a8-7e89-b48c-09fff0f0f0f0).',
    aggregate_type    VARCHAR(255) NOT NULL COMMENT '[NONE] The kind of aggregate that recorded the event in CamelCase (e.g., PointOfInterest).',
    aggregate_version BIGINT       NOT NULL COMMENT '[NONE] The version the aggregate carried when it recorded the event, which detects a lost update when two writers reach the same value (e.g., 3).',
    occurred_at       TIMESTAMP(6) NOT NULL COMMENT '[NONE] The UTC date and time when the event occurred in ISO 8601 format (e.g., 2026-02-13T08:49:44.931408+00:00).',
    created_at        TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) COMMENT '[NONE] The UTC date and time when the record was created in ISO 8601 format (e.g., 2026-02-13T08:49:44.931408+00:00).',
    PRIMARY KEY (id),
    CONSTRAINT unq_outbox_events_aggregate_type_aggregate_id_aggregate_version UNIQUE (aggregate_type, aggregate_id, aggregate_version)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_0900_ai_ci
    COMMENT = '[NONE] Table used to persist outbox event records.';
