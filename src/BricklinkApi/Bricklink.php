<?php

namespace BillbeeBricklink\BricklinkApi;

// Import necessary classes
use BillbeeBricklink\IniParser;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

class Bricklink
{
    // Declare private properties for configuration and the handler stack
    private IniParser $config; // Holds the configuration data parsed from an .ini file
    private HandlerStack $stack; // Used to manage middleware layers for HTTP requests
    public ClientInterface $client; // HTTP client for making API requests

    /**
     * Constructor to initialize the Bricklink class
     *
     * @param IniParser $config Configuration parser instance
     */
    public function __construct(IniParser $config)
    {
        $this->config = $config; // Assign the configuration instance to the property
        $this->stack = HandlerStack::create(); // Create a new Guzzle HandlerStack

        // Add OAuth1 authentication middleware to the stack
        $this->stack->push(new Oauth1([
            'consumer_key'    => $this->config->get('bricklink', 'consumer_key'), // Consumer key for API authentication
            'consumer_secret' => $this->config->get('bricklink', 'consumer_secret'), // Consumer secret for API authentication
            'token'           => $this->config->get('bricklink', 'token_value'), // Access token for API authentication
            'token_secret'    => $this->config->get('bricklink', 'token_secret'), // Token secret for API authentication
        ]));

        // Add custom API response handling middleware to the stack
        $this->stack->push(ApiResponseHandler::create());

        // Initialize the HTTP client with configuration and middleware
        $this->client = new Client([
            'base_uri' => $this->config->get('bricklink', 'base_uri'), // Base URI for API requests
            'handler'  => $this->stack, // Use the custom handler stack
            'auth'     => 'oauth', // Specify OAuth for authentication
            'headers'    => [ // Set default HTTP headers
                'Accept' => 'application/json', // Expect JSON responses from the API
                'Content-Type' => 'application/json', // Send requests in JSON format
                "User-Agent" => "Mozilla/5.0" // Set a user-agent header
            ],
            'http_errors' => $this->config->get('bricklink', 'http_errors') ?? true, // Toggle HTTP error handling
            'timeout' => $this->config->get('bricklink', 'timeout') ?? 15, // Set request timeout (default: 15 seconds)
            'connect_timeout' => $this->config->get('bricklink', 'connect_timeout') ?? 5, // Set connection timeout (default: 5 seconds)
        ]);
    }
}
