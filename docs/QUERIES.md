* [Find points of interest](#find-points-of-interest)

## Find points of interest

#### Lists the points of interest recorded by the service, most recently registered first.

The page is a forward-only keyset cursor, so there is no page number and no total. The next cursor is carried in
`links.next` whenever `meta.has_next` is true, and the `links.next` entry is absent otherwise.

A proximity search narrows the page to the points sitting within `distance` of the reference point (`x_coordinate`,
`y_coordinate`). The three parameters travel together, and the distance is the straight line between the two points on
the plane, compared as less than or equal. The proximity survives the cursor, so every page of a walk stays inside the
same circle.

**GET** `{{points-of-interest-dns}}/points-of-interest`

### Request

**Path and query parameters**:

| Parameter      |  Type   | Description                                                                   | Constraints                                                                                                                       | Required |
|:---------------|:-------:|:------------------------------------------------------------------------------|:-----------------------------------------------------------------------------------------------------------------------------------|:--------:|
| `sort`         | String  | Deterministic ordering of the page.                                           | Sortable by `id`, `name` and `created_at`, with a leading minus marking descending. Default: `-created_at,-id`.                    |    No    |
| `filter`       | String  | Filter over the point of interest fields.                                     | `name` under `==`, `=in=` and `=sw=`. `x_coordinate` and `y_coordinate` under `==`, `=le=` and `=ge=`, with integer values.        |    No    |
| `distance`     | Integer | Maximum straight line distance from the reference point. E.g., `10`.          | Must be a non-negative integer. Travels together with `x_coordinate` and `y_coordinate`.                                          |    No    |
| `x_coordinate` | Integer | X coordinate of the reference point the search is centered on. E.g., `20`.    | Must be a non-negative integer. Travels together with `distance` and `y_coordinate`.                                              |    No    |
| `y_coordinate` | Integer | Y coordinate of the reference point the search is centered on. E.g., `10`.    | Must be a non-negative integer. Travels together with `distance` and `x_coordinate`.                                              |    No    |
| `page[size]`   | Integer | Items per page. E.g., `20`.                                                   | Must be between 1 and 100. Default: 20.                                                                                           |    No    |
| `page[cursor]` | String  | Opaque forward-only cursor carried by `links.next` of the previous response.  | Must be a token this endpoint issued, never a value the client builds.                                                            |    No    |

### Response

- `200 OK`

  **Description**: Indicates that the points of interest page was recovered.

  **Headers**:
  | Header |  Type  | Description                                                                      | Constraints                                                                           | Required |
  |:-------|:------:|:---------------------------------------------------------------------------------|:--------------------------------------------------------------------------------------|:--------:|
  | `Link` | String | RFC 8288 navigation carrying the same targets as the `links` object of the body. | Carries the `self` target always, and the `next` target when `meta.has_next` is true. |   Yes    |

  **Content-Type**: application/json

  **Body**:
  ```json
  {
      "data": [
          {
              "id": "01a0c5bc-b593-71fd-98cc-32d4c55dbabd",
              "name": "Churrascaria",
              "point": {
                  "x_coordinate": 28,
                  "y_coordinate": 2
              },
              "created_at": "2026-09-21T10:00:00.000000+00:00"
          }
      ],
      "meta": {
          "per_page": 1,
          "has_next": true
      },
      "links": {
          "self": "/points-of-interest?page[size]=1",
          "next": "/points-of-interest?page[cursor]=WyIyMDI2LTA5LTIxVDEwOjAwOjAwLjAwMDAwMCswMDowMCIsIjAxYTBjNWJjLWI1OTMtNzFmZC05OGNjLTMyZDRjNTVkYmFiZCJd&page[size]=1"
      }
  }
  ```

- `422 Unprocessable Entity`

  **Description**: Indicates that a query parameter failed validation.

  **Content-Type**: application/json

  **Body**:
  ```json
  {
      "code": "INVALID_REQUEST",
      "message": "A proximity search takes distance, x_coordinate, and y_coordinate together."
  }
  ```

  or when a proximity parameter is not a non-negative integer:

  ```json
  {
      "code": "INVALID_REQUEST",
      "message": "The <distance> must be a non-negative integer."
  }
  ```

  or when the sort targets a field that is not sortable:

  ```json
  {
      "code": "INVALID_REQUEST",
      "message": "Sort field <unknown> is not allowed."
  }
  ```

  or when the filter targets a field that is not filterable:

  ```json
  {
      "code": "INVALID_REQUEST",
      "message": "Filter field <created_at> is not allowed."
  }
  ```

  or when the page size is above the maximum:

  ```json
  {
      "code": "INVALID_REQUEST",
      "message": "Page size <9999> must be less than or equal to 100."
  }
  ```

  or when the cursor cannot be decoded:

  ```json
  {
      "code": "INVALID_REQUEST",
      "message": "Cursor token <broken> is invalid and could not be decoded."
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
