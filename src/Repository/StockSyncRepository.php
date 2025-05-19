<?php

namespace BillbeeBricklink\Repository;

use BillbeeBricklink\BricklinkApi\{Bricklink, ResponseHelper};
use Billbee\CustomShopApi\Repository\StockSyncRepositoryInterface;
use Billbee\CustomShopApi\Exception\ProductNotFoundException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class StockSyncRepository implements StockSyncRepositoryInterface
{
    private Bricklink $gateway;
    private Client $client;
    private LoggerInterface $logger;
    private ?int $maxQuantityForSets;
    private bool $multiStockroom;

    public function __construct(Bricklink $gateway, LoggerInterface $logger, int $maxQuantityForSets=null,
        bool $multiStockroom=false)
    {
        $this->gateway = $gateway;
        $this->client = $this->gateway->client;
        $this->logger = $logger;
        $this->maxQuantityForSets = $maxQuantityForSets; // Set a maximum Quantity for sets on Bricklink
        $this->multiStockroom = $multiStockroom; // Multiple Stockroom activated on Bricklink?
    }

    /** @inheritDoc */
    public function setStock(string $productId, float $quantity): void
    {
        try {
            // Fetch current inventory data from the Bricklink API
            $product = ResponseHelper::getData($this->client->get("inventories/$productId"));

            // Do not sync parts (this would ignore partout on BrickLink)
            if ($product['item']['type'] != 'PART') {
                // Check that quantity is not negative
                $quantity = max(0, (int) $quantity);

                // If a max quantity is set for sets, ensure the quantity doesn't exceed the limit
                if (isset($this->maxQuantityForSets) and $product['item']['type'] == 'SET') {
                    $quantity = min($this->maxQuantityForSets, $quantity);
                }

                // Prepare parameters for the stock update request
                $parameters = ['quantity' => $quantity - $product['quantity'], 'is_retain' => true];

                // Handle scenarios where stock needs to be moved to or out of the stockroom
                if ($quantity == 0) {
                    $parameters['is_stock_room'] = true;
                    $parameters['stock_room_id'] = $this->multiStockroom ? 'C' : 'S';
                } elseif ($quantity > 0 & $product['quantity'] == 0) {
                    $parameters['is_stock_room'] = false;
                }

                // Send the stock update request
                $this->client->put("inventories/$productId", ['json' => $parameters]);
            }
        } catch (GuzzleException $e) {
            // Log the error and throw a ProductNotFoundException if the update fails
            $this->logger->error("Stock update failed for $productId:" . $e->getMessage());
            throw new ProductNotFoundException("Stock update failed for $productId.");
        }
    }
}
