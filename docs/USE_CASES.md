* [Register a point of interest](#register-a-point-of-interest)

## Register a point of interest

#### Records a point of interest at the coordinates a GPS receiver reported for it.

A point of interest carries a name and the coordinates it sits at on the plane, both measured in metres from the origin
and never negative. The same name may be registered more than once, as long as each registration sits at different
coordinates.

**POST** `{{points-of-interest-dns}}/points-of-interest`

### Headers

| Header         |  Type  | Description               | Constraints               | Required |
|:---------------|:------:|:--------------------------|:--------------------------|:--------:|
| `Content-Type` | String | The request content type. | Must be application/json. |   Yes    |

### Request

**Body parameters**:

| Parameter              |  Type   | Description                                  | Constraints                              | Required |
|:-----------------------|:-------:|:---------------------------------------------|:-----------------------------------------|:--------:|
| `name`                 | String  | Name the point of interest is known by.      | Between 1 and 255 characters.            |   Yes    |
| `point`                | Object  | Coordinates the point of interest sits at.   | Must carry both coordinates.             |   Yes    |
| `point.x_coordinate`   | Integer | X coordinate in metres from the origin.      | Between 0 and 4294967295.                |   Yes    |
| `point.y_coordinate`   | Integer | Y coordinate in metres from the origin.      | Between 0 and 4294967295.                |   Yes    |

```json
{
    "name": "Pub",
    "point": {
        "x_coordinate": 12,
        "y_coordinate": 8
    }
}
```

### Response

- `201 Created`

  **Description**: Indicates that the point of interest was recorded and its identifier was assigned.

  **Content-Type**: application/json

  **Body**:
  ```json
  {
      "id": "01a0c5bc-b590-7339-ae61-cfa3b061335c"
  }
  ```

- `409 Conflict`

  **Description**: Indicates that this name is already registered at these coordinates.

  **Content-Type**: application/json

  **Body**:
  ```json
  {
      "code": "POINT_OF_INTEREST_ALREADY_EXISTS",
      "message": "A point of interest with this name already exists at these coordinates."
  }
  ```

- `422 Unprocessable Entity`

  **Description**: Indicates that one or more of the provided values are invalid.

  **Content-Type**: application/json

  **Body**:
  ```json
  {
      "code": "INVALID_REQUEST",
      "message": "`.point` must be present"
  }
  ```

  or when a coordinate is not an integer:

  ```json
  {
      "code": "INVALID_REQUEST",
      "message": "`.point.x_coordinate` must be an integer"
  }
  ```

  or when a coordinate falls outside the accepted range:

  ```json
  {
      "code": "COORDINATE_OUT_OF_RANGE",
      "message": "Coordinate is out of range. Current <-1>, Minimum <0>, Maximum <4294967295>."
  }
  ```

  or when the name is empty:

  ```json
  {
      "code": "INVALID_NAME",
      "message": "Name cannot be empty."
  }
  ```

  or when the name is longer than the accepted maximum:

  ```json
  {
      "code": "INVALID_NAME",
      "message": "Name is too long. Current <256> characters, Maximum <255> characters."
  }
  ```

- `500 Internal Server Error`

  **Description**: Indicates that an unexpected error occurred on the server while processing the request.

  **Content-Type**: application/json

  **Body**:
  ```json
  {
      "code": "INTERNAL_ERROR",
      "message": "An unexpected error occurred."
  }
  ```
