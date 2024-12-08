CREATE TABLE points_interest
(
    name         VARCHAR(255) NOT NULL COMMENT 'Nome do ponto de interesse.',
    x_coordinate INT          NOT NULL COMMENT 'Coordenada X do ponto de interesse.',
    y_coordinate INT          NOT NULL COMMENT 'Coordenada Y do ponto de interesse.',
    created_at   TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) COMMENT 'Data em que o registro foi inserido.',
    INDEX points_interest_idx01 (name),
    INDEX points_interest_idx02 (x_coordinate),
    INDEX points_interest_idx03 (y_coordinate),
    CONSTRAINT point_uk01
        UNIQUE (name, x_coordinate, y_coordinate)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci COMMENT ='Tabela usada para persistir os pontos de interesse.';
