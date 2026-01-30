# HTTP Request Extension for EspoCRM

This extension provides custom formula functions for EspoCRM, allowing users to send **HTTP/REST requests** directly from record formulas (e.g., before-save scripts, calculated fields, or workflow actions).

This is ideal for integrating EspoCRM with third-party APIs for data validation, real-time lookups, or triggering external systems.

## Features

* **`ext\astroRequest\send(URL, [METHOD], [DATA], [HEADERS])`**: Sends a cURL-based HTTP request (GET, POST, PUT, PATCH, DELETE).
    * **Data Handling**: Automatically encodes `DATA` arrays/objects into JSON.
    * **Headers**: Supports custom headers passed as a dictionary/map.
* **`ext\astroRequest\lastResponse([FIELD])`**: Retrieves the result of the immediately preceding `send` call.
    * Can return the full response object or a specific field (`statusCode`, `body`, `headers`, `error`).

## Installation

1.  **Package the Extension**: Zip the all the extension files.
2.  **Upload**: Navigate to **Administration** > **Extensions**.
3.  **Install**: Upload and install the zipped file.

## Usage in Formulas

The functions are designed to be called sequentially: first `send`, then `lastResponse`.

### Example: Sending Data (POST Request)

This example shows sending the entity ID to a external API

```php
// Base data
$url = 'https://your-url.here/api/v1/testing';
$method = 'POST';

// Create data object
$data = object\create();
object\set($data, 'id', entity\attribute('id'));

// Create headers object
$headers = object\create();
object\set($headers, 'Authorization', 'Bearer your-token-here');

// Send request
$success = ext\astroRequest\send($url, $method, $data, $headers);

// Optional for the formula sandbox
if($success) {
   $statusCode = ext\astroRequest\lastResponse('statusCode');
   output\printLine($statusCode);
   $responseBody = ext\astroRequest\lastResponse('body');
   output\printLine($responseBody);
} else {
   $error = ext\astroRequest\lastResponse('error');
   output\printLine($error);
}
```

### License

EspoCRM is an open-source project licensed under [GNU AGPLv3](https://raw.githubusercontent.com/espocrm/espocrm/master/LICENSE.txt).