CREATE TABLE points_of_interest
(
    id           BINARY(16)   NOT NULL COMMENT '[NONE] The point of interest identifier in Version 7 UUID format (e.g., 0190d09e-a7a8-7e89-b48c-09fff0f0f0f0).',
    name         VARCHAR(255) NOT NULL COMMENT '[NONE] The name the point of interest is known by (e.g., Lanchonete).',
    x_coordinate INT UNSIGNED NOT NULL COMMENT '[NONE] The X coordinate of the point of interest on the plane, measured in metres from the origin (e.g., 27).',
    y_coordinate INT UNSIGNED NOT NULL COMMENT '[NONE] The Y coordinate of the point of interest on the plane, measured in metres from the origin (e.g., 12).',
    created_at   TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) COMMENT '[NONE] The UTC date and time when the record was created in ISO 8601 format (e.g., 2026-02-13T08:49:44.931408+00:00).',
    PRIMARY KEY (id),
    INDEX idx_points_of_interest_x_coordinate_y_coordinate (x_coordinate, y_coordinate),
    INDEX idx_points_of_interest_created_at_id (created_at DESC, id DESC),
    CONSTRAINT unq_points_of_interest_name_x_coordinate_y_coordinate UNIQUE (name, x_coordinate, y_coordinate)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_0900_ai_ci
    COMMENT = '[NONE] Table used to persist the points of interest a GPS receiver can be guided to.';
