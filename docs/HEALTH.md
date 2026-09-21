* [Liveness check](#liveness-check)
* [Readiness check](#readiness-check)

## Liveness check

#### Reports that the process is running and able to answer requests.

**GET** `{{points-of-interest-dns}}/health/liveness`

### Response

- `200 OK`

  **Description**: Indicates that the process is alive.

  **Content-Type**: application/json

  **Body**:
  ```json
  {
      "status": "UP"
  }
  ```

## Readiness check

#### Reports that every critical dependency of the service is reachable.

The probe runs one critical check against the database and answers a diagnostic body instead of the standard error
envelope.

**GET** `{{points-of-interest-dns}}/health/readiness`

### Response

- `200 OK`

  **Description**: Indicates that the service is ready to handle requests.

  **Content-Type**: application/json

  **Body**:
  ```json
  {
      "checks": [
          {
              "status": "UP",
              "critical": true,
              "component": "database",
              "duration_in_milliseconds": 4.59
          }
      ],
      "status": "HEALTHY"
  }
  ```

- `503 Service Unavailable`

  **Description**: Indicates that a critical dependency is down, so the service cannot handle requests. The failing
  entry carries a detail describing the failure, and the aggregate status reports UNAVAILABLE.

  **Content-Type**: application/json

  **Body**:
  ```json
  {
      "checks": [
          {
              "detail": "An exception occurred in the driver: SQLSTATE[HY000] [2002] Connection refused",
              "status": "DOWN",
              "critical": true,
              "component": "database",
              "duration_in_milliseconds": 2001.37
          }
      ],
      "status": "UNAVAILABLE"
  }
  ```
