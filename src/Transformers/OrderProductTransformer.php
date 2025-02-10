<?php

namespace BillbeeBricklink\Transformers;

// Import necessary classes
use Billbee\CustomShopApi\Model\OrderProduct; // OrderProduct model used in the CustomShop API
use BillbeeBricklink\Helpers\SKU; // Helper for generating SKU (Stock Keeping Unit) identifiers

class OrderProductTransformer
{
    /**
     * Transform a response array into an OrderProduct object.
     *
     * @param array $response The response array containing order product details.
     * @return OrderProduct A populated OrderProduct object.
     */
    public static function toOrderProduct(array $response): OrderProduct
    {
        // Instantiate a new OrderProduct object
        $orderProduct = new OrderProduct();

        // Calculate discount percentage if the final unit price differs from the original unit price
        if ($response['unit_price_final'] != $response['unit_price']) {
            $orderProduct->discountPercent = 1 - $response['unit_price_final'] / $response['unit_price'];
        }

        // Map the product quantity to the OrderProduct object
        $orderProduct->quantity = $response['quantity'];

        // Map the original unit price to the OrderProduct object
        $orderProduct->unitPrice = $response['unit_price'];

        // Assign the inventory ID from the response to the productId property
        $orderProduct->productId = $response['inventory_id'];

        // Assign the product name from the response to the name property
        $orderProduct->name = $response['item']['name'];

        // Generate and assign an SKU using the SKU helper, based on item details
        $orderProduct->sku = SKU::fromBl(
            $response['item']['no'],           // Item number
            $response['item']['type'],         // Item type (e.g., SET, PART)
            $response['color_id'] ?? null      // Optional color ID
        );

        // Return the populated OrderProduct object
        return $orderProduct;
    }
}
