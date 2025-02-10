<?php

namespace BillbeeBricklink\BricklinkApi;

use Psr\Http\Message\ResponseInterface;
use Exception;
use stdClass;

class ResponseHelper
{
    /**
     * Extract data from the JSON response.
     *
     * @param ResponseInterface $response The response object.
     * @param bool $as_array Return data as associative array if true.
     * @return array|stdClass The extracted data.
     * @throws Exception If data is not found or decoding fails.
     */
    public static function getData(ResponseInterface $response, bool $as_array = true) : array|stdClass
    {
        // Decode the JSON response
        $bl_response = json_decode((string) $response->getBody(), $as_array);

        // Check for JSON decoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decode error: ' . json_last_error_msg());
        }

        // Handle the response based on the format
        if ($as_array) {
            if (isset($bl_response['data'])) {
                return $bl_response['data'];
            } else {
                throw new Exception("No data found. " . $bl_response['meta']);
            }
        } else {
            if (property_exists($bl_response, 'data')) {
                return $bl_response->data;
            } else {
                throw new Exception("No data found. " . $bl_response->meta);
            }
        }
    }
}
