<?php

namespace BillbeeBricklink\BricklinkApi;

use Closure;
use Exception;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ApiResponseHandler
{
    public static function create(?LoggerInterface $logger=null): Closure
    {
        return function (callable $handler) use ($logger) {
            return function (RequestInterface $request, array $options) use ($handler, $logger) {
                return $handler($request, $options)->then(
                    function (ResponseInterface $response) use ($request, $logger) {
                        // Handle successful response
                        return self::handleResponse($response, $request, $logger);
                    },
                    function ($reason) use ($request) {
                        // Handle error response
                        return self::handleError($reason, $request);
                    }
                );
            };
        };
    }

    private static function handleResponse(Response $response, Request $request, ?LoggerInterface $logger): Response
    {
        $statusCode = $response->getStatusCode();
        $bl_response = json_decode((string) $response->getBody());

        if (!is_null($bl_response)) {
            $statusCode = $bl_response->meta->code;
        } elseif (in_array($statusCode, [200, 201])) {
            throw new Exception($response->getBody() . " cannot be JSON decoded.");
        }

        // Check for custom handling on status codes
        switch ($statusCode) {
            case 200:
            case 201:
            case 204;
                // Successful response, return it as is
                return $response;

            case 400:
                // Handle client error
                throw new Exception("Client error: " . $response->getBody(), 400);

            case 401:
                // Handle authentication error
                throw new Exception("Authentication error: " . $response->getBody(), 401);

            case 404:
                // Handle resource not found error
                throw new Exception("Resource not found (404): " . str_replace(
                        '/api/store/v1/', '', $request->getUri()->getPath()), 404);

            case 500:
                // Handle server error
                throw new Exception("Server error: " . $response->getBody(), 500);

            default:
                // Unexpected status code
                throw new Exception("Unexpected status code: $statusCode", $statusCode);
        }
    }

    private static function handleError($reason, Request $request)
    {
        if ($reason instanceof RequestException && $reason->hasResponse()) {
            $response = $reason->getResponse();
            $statusCode = $response->getStatusCode();
            $body = $response->getBody();

            // Log the error or handle retries if necessary
            error_log("Error response: $statusCode - $body");

            // Custom handling based on status codes
            if ($statusCode === 429) {
                throw new Exception("Rate limit exceeded", 429);
            } elseif ($statusCode >= 500) {
                throw new Exception("Server error occurred: $body", $statusCode);
            }
        }

        throw new Exception("Request error: " . $reason->getMessage());
    }

    // not used currently, add self::log($request, $response, $logger) for logging request and response
    private static function log($request, $response, $logger) : void
    {
        if (!is_null($logger)) {
            $logger->info("Request/Response data: ", [
                "Request Method" => $request->getMethod(),
                "Request Target" => $request->getRequestTarget(),
                "Request Headers" => $request->getHeaders(),
                "Request Body" => $request->getBody(),
                "Response Reason phrase" => $response->getReasonPhrase(),
                "Response Headers" => $response->getHeaders(),
                "Response Body" => $response->getBody()]);
        }
    }
}
