<?php

namespace BillbeeBricklink\Transformers;

use Billbee\CustomShopApi\Model\ProductImage; // ProductImage model used in the CustomShop API

class ProductImageTransform
{
    /**
     * Transform an image URL into a ProductImage object.
     *
     * @param string $image_url The image URL to be transformed.
     * @return ProductImage A populated ProductImage object.
     */
    public static function toProductImage(string $image_url): ProductImage
    {
        // Check if the image URL does not start with 'http', and if not, prepend 'https:' to the URL
        if (!str_starts_with($image_url, "http")) {
            $image_url = "https:" . $image_url;
        }

        // Instantiate a new ProductImage object
        $productImage = new ProductImage();

        // Set the URL of the product image
        $productImage->setUrl($image_url);

        // Set the default status of the image (true for this case)
        $productImage->setIsDefault(true);

        // Set the position of the image (set to 1 for this case, indicating it's the primary image)
        $productImage->setPosition(1);

        // Return the populated ProductImage object
        return $productImage;
    }
}
