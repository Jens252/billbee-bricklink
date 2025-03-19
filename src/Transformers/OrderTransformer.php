<?php

namespace BillbeeBricklink\Transformers;

// Import necessary classes
use Billbee\CustomShopApi\Model\Order; // Order model used in the CustomShop API
use Billbee\CustomShopApi\Model\OrderProduct;
use BillbeeBricklink\Helpers\{Time, PaymentMethod, OrderStatus}; // Helpers for time conversion, payment methods, and order statuses

class OrderTransformer
{
    /**
     * Transform a response array into an Order object.
     *
     * @param array $response The response array containing order details.
     * @return Order A populated Order object.
     */
    public static function toOrder(array $response): Order
    {
        // Instantiate a new Order object
        $order = new Order();

        // Map order ID and order number (both set to the same value)
        $order->orderId = $response['order_id'];
        $order->orderNumber = $response['order_id'];

        // Map the currency code used in the order
        $order->currencyCode = $response['cost']['currency_code'];

        // Map the buyer's nickname
        $order->nickName = $response['buyer_name'];

        // Map the shipping cost
        $order->shipCost = round((float) $response['cost']['grand_total'] - (float) $response['cost']['subtotal']
            - (float) $response['cost']['salesTax'], 2);

        // Add sales Tax as extra item if charged for order
        if ((float) $response['cost']['salesTax'] > 0) {
            $salesTaxEntry = new OrderProduct();
            $salesTaxEntry->name = 'State Sales Tax';
            $salesTaxEntry->quantity = 1;
            $salesTaxEntry->unitPrice = round((float) $response['cost']['salesTax'], 2);
            $order->items[] = $salesTaxEntry;
        };

        // Transform and map the buyer's address using AddressTransform
        $order->invoiceAddress = AddressTransform::toAddress($response);

        // Convert and map the order date
        $order->orderDate = Time::fromBl($response['date_ordered']);

        // Map the buyer's email address
        $order->email = $response['buyer_email'];

        // Map the phone number associated with the shipping address
        $order->phone1 = $response['shipping']['address']['phone_number'];

        // Map the payment date if it exists
        if (isset($response['payment']['date_paid'])) {
            $order->payDate = Time::fromBl($response['payment']['date_paid']);
        }

        // Map the shipping date if it exists
        if (isset($response['shipping']['date_shipped'])) {
            $order->shipDate = Time::fromBl($response['shipping']['date_shipped']);
        }

        // Convert and map the payment method using PaymentMethod helper
        $order->paymentMethod = PaymentMethod::fromBl($response['payment']['method']);

        // Convert and map the order status using OrderStatus helper
        $order->statusId = OrderStatus::fromBl($response['status']);

        // Map the seller's remarks or set to null if not provided
        $order->sellerComment = $response['remarks'] ?? null;

        // Map the shipping profile ID
        $order->shippingProfileId = $response['shipping']['method_id'];

        // Return the populated Order object
        return $order;
    }
}
