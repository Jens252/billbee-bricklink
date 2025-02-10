<?php

namespace BillbeeBricklink;

require '../vendor/autoload.php'; // Autoload Bricklink API and Billbee SDKs

use BillbeeBricklink\BricklinkApi\Bricklink;
use BillbeeBricklink\BricklinkApi\ResponseHelper;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Exception;

// Create a logger instance
$logger = new Logger('logger');
$logger->pushHandler(new StreamHandler('../logs/app.log'));

// Load configuration from config.ini
$config = new IniParser('../test_config.ini');
$gateway = new Bricklink($config);

//
try {
    // Retrieve available items from Bricklink inventory
    $bl_inventory = ResponseHelper::getData($gateway->client->get("inventories?status=Y,N,R"));
    // Set retain option
    array_map(function ($item) use ($gateway) {
        $inventory_id = $item['inventory_id'];
        if (!$item['is_retain']) {
            $gateway->client->put("inventories/$inventory_id", ['json' => ['is_retain' => true]]);
        }
    }, $bl_inventory);
} catch (Exception|GuzzleException $e) {
    $logger->error($e->getMessage());
}
