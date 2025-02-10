<?php

namespace BillbeeBricklink;

require '../vendor/autoload.php'; // Autoload Bricklink API and Billbee SDKs

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use BillbeeBricklink\BricklinkApi\Bricklink;
use Billbee\CustomShopApi\Http\{Request, RequestHandlerPool};
use Billbee\CustomShopApi\Security\KeyAuthenticator;
use BillbeeBricklink\Repository\{OrderRepository, ShippingProfileRepository, ProductsRepository, StockSyncRepository};

// Create a logger instance
$logger = new Logger('logger');
$logger->pushHandler(new StreamHandler('../logs/app.log'));

// Load configuration from config.ini
$config = new IniParser('../config.ini');
$gateway = new Bricklink($config);

// Authentication by Key
$authenticator = new KeyAuthenticator($config->get('billbee', 'secret_key'));

$handler = new RequestHandlerPool($authenticator, [
    new OrderRepository($gateway, $logger),
    new ShippingProfileRepository($gateway, $logger),
    new ProductsRepository($gateway, $logger, $config->get('settings', 'import_types'),
        (bool) $config->get('settings', 'import_stockroom'),
        (bool) $config->get('settings', 'multiple_stockrooms')),
    new StockSyncRepository($gateway, $logger, $config->get('settings', 'max_quantity_for_sets'),
        (bool) $config->get('settings', 'multiple_stockrooms')),
]);

// In the next step we create a request object from the current HTTP request
// and let the RequestHandlerPool process it
$request = Request::createFromGlobals();
$response = $handler->handle($request);

// Finally, the response is sent to the client
$response->send();
