<?php

namespace BillbeeBricklink\Repository;

use BillbeeBricklink\BricklinkApi\{Bricklink, ResponseHelper};
use BillbeeBricklink\Transformers\ProductTransform;
use Billbee\CustomShopApi\Repository\ProductsRepositoryInterface;
use Billbee\CustomShopApi\Model\{PagedData, Product};
use Billbee\CustomShopApi\Exception\ProductNotFoundException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Exception;

class ProductsRepository implements ProductsRepositoryInterface
{
    private Bricklink $gateway;
    private Client $client;
    private LoggerInterface $logger;
    private ?string $importSelectedTypesOnly;
    private bool $importStockroom;
    private bool $multiStockroom;

    public function __construct(Bricklink $gateway, LoggerInterface $logger,
        string $importSelectedTypesOnly=null, bool $importStockroom=false, bool $multiStockroom=false)
    {
        $this->gateway = $gateway;
        $this->client = $this->gateway->client;
        $this->logger = $logger;
        $this->importSelectedTypesOnly = $importSelectedTypesOnly; // Give types to import as list, e.g. ['PART', 'GEAR']
        $this->importStockroom = $importStockroom; // Also import Stockroom items
        $this->multiStockroom = $multiStockroom; // Multiple Stockroom activated on Bricklink?
    }

    /** @inheritDoc
     * @throws Exception If fetching store inventory fails
     */
    public function getProducts(int $page, int $pageSize): PagedData
    {
        try {
            // Build request parameters based on stockroom and item type filters
            $parameters = ["status" => "Y"]; // Available items
            if ($this->importStockroom) {
                $parameters["status"] .= ",S"; // Add stockroom item
                if ($this->multiStockroom) {
                    $parameters["status"] .= ",B,C"; // Add additional stockrooms
                }
            }
            if ($this->importSelectedTypesOnly) {
                $parameters['item_type'] = $this->importSelectedTypesOnly; // Filter by specific item types
            }

            // Fetch inventory data from the Bricklink API
            $bl_inventory = ResponseHelper::getData($this->client->get(
                "inventories?" . http_build_query($parameters)));
        } catch (Exception|GuzzleException $e) {
            // Log the error and rethrow a generic exception
            $this->logger->error("Failed to fetch Bricklink store inventory: " . $e->getMessage());
            throw new Exception("Failed to fetch Bricklink store inventory.");
        }

        // Paginate the fetched inventory
        $bl_inventory_on_page = array_slice($bl_inventory, ($page - 1) * $pageSize, $pageSize);

        // Transform Bricklink inventory data into Product objects
        $bl_products = array_map(function ($bl_inventory_item) {
            return $this->getProductFromStoreInventoryResource($bl_inventory_item);
        }, $bl_inventory_on_page);
        return new PagedData($bl_products, count($bl_inventory));
    }

    /** @inheritDoc */
    public function getProduct(string $productId): Product
    {
        try {
            // Fetch inventory item by ID from the Bricklink API
            $bl_inventory_item = ResponseHelper::getData($this->client->get("inventories/$productId"));
        } catch (Exception|GuzzleException $e) {
            // Log the error and throw a ProductNotFoundException with a detailed message
            $this->logger->error(
                "Failed to fetch Bricklink inventory $productId: " . $e->getMessage());
            throw new ProductNotFoundException(
                "Failed to fetch Bricklink inventory $productId. Item sold out without 'Retain' option set?");
        }
        // Transform the inventory item into a Product object
        return $this->getProductFromStoreInventoryResource($bl_inventory_item);
    }

    /**
     * Transform Bricklink inventory data into a Product object.
     * @param array $bl_inventory_item The inventory data fetched from Bricklink.
     * @return Product The transformed product.
     */
    private function getProductFromStoreInventoryResource(array $bl_inventory_item): Product
    {
        try {
            // Fetch catalog data for the inventory item
            $bl_type = $bl_inventory_item['item']['type'];
            $bl_no = $bl_inventory_item['item']['no'];
            $bl_catalog_item = ResponseHelper::getData($this->client->get("items/$bl_type/$bl_no"));
        } catch (Exception|GuzzleException $e) {
            // Log the error and proceed with null catalog data
            $this->logger->error("Failed to fetch Bricklink catalog data for $bl_type/$bl_no: " . $e->getMessage());
            $bl_catalog_item = null;
        }

        // Transform the inventory and catalog data into a Product object
        return ProductTransform::toProduct($bl_inventory_item, $bl_catalog_item);
    }
}
