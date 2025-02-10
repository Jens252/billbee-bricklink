<?php

namespace BillbeeBricklink\Transformers;

use Billbee\CustomShopApi\Model\Product; // Product model used in the CustomShop API
use BillbeeBricklink\Helpers\SKU; // Helper for generating SKU (Stock Keeping Unit) identifiers

class ProductTransform
{
    /**
     * Transform a response array into a Product object.
     *
     * @param array $bl_inventory_item The inventory item data from the Bricklink response.
     * @param array|null $bl_catalog_item Optional catalog item data from Bricklink.
     * @return Product A populated Product object.
     */
    public static function toProduct(array $bl_inventory_item, array $bl_catalog_item = null): Product
    {
        // Instantiate a new Product object
        $product = new Product();

        // Generate SKU using the SKU helper, based on item details (item number, item type, and optional color ID)
        $product->setSku(SKU::fromBl($bl_inventory_item['item']['no'], $bl_inventory_item['item']['type'],
            $bl_inventory_item['color_id'] ?? null));

        // Set the product title (name from the inventory item)
        $product->setTitle($bl_inventory_item['item']['name']);

        // Set the product ID from the inventory item
        $product->setId($bl_inventory_item['inventory_id']);

        // Set the quantity available in the inventory
        $product->setQuantity($bl_inventory_item['quantity']);

        // Calculate the price with a discount based on the sale rate and set the price
        $product->setPrice($bl_inventory_item['unit_price'] * (1 - $bl_inventory_item['sale_rate'] / 100));

        // Set the product description from the inventory item data
        $product->setDescription($bl_inventory_item['description']);

        // If catalog item data is provided, map the additional details
        if (isset($bl_catalog_item)) {
            // Convert weight from grams to kilograms and set the weight
            $product->setWeightInKg($bl_catalog_item['weight'] / 1000);

            // Set the dimensions (length, width, height) in centimeters
            $product->setLengthInCm($bl_catalog_item['dim_x']);
            $product->setWidthInCm($bl_catalog_item['dim_y']);
            $product->setHeightInCm($bl_catalog_item['dim_z']);

            // Set the product image using the image URL from the catalog item data
            $product->setImages([ProductImageTransform::toProductImage($bl_catalog_item['image_url'])]);
        }

        // Set the manufacturer name (hardcoded as LEGO in this case)
        $product->setManufacturer("LEGO");

        // Return the populated Product object
        return $product;
    }
}
