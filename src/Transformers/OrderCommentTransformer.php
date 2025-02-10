<?php

namespace BillbeeBricklink\Transformers;

// Import necessary classes
use Billbee\CustomShopApi\Model\OrderComment; // OrderComment model used in the CustomShop API
use BillbeeBricklink\Helpers\Time; // Helper for processing and formatting time from Bricklink responses

class OrderCommentTransformer
{
    /**
     * Transform a response array into an OrderComment object.
     *
     * @param array $response The response array containing order comment details.
     * @param string $sellerName The name of the seller to identify the source of the comment.
     * @return OrderComment A populated OrderComment object.
     */
    public static function toOrderComment(array $response, string $sellerName): OrderComment
    {
        // Instantiate a new OrderComment object
        $orderComment = new OrderComment();

        // Convert the 'dateSent' field to a standardized date format using the Time helper
        $orderComment->dateAdded = Time::fromBl($response['dateSent']);

        // Map the 'subject' field from the response to the name property, or set it to null if not present
        $orderComment->name = $response['subject'] ?? null;

        // Assign the 'body' field from the response to the comment property
        $orderComment->comment = $response['body'];

        // Determine if the comment is from the customer based on the 'to' field in the response
        // If the 'to' field matches the seller's name, the comment is from the customer
        $orderComment->fromCustomer = ($response['to'] == $sellerName);

        // Return the populated OrderComment object
        return $orderComment;
    }
}
