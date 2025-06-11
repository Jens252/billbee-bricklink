<?php

namespace BillbeeBricklink\Repository;

use BillbeeBricklink\BricklinkApi\ResponseHelper;
use BillbeeBricklink\Helpers\OrderStatus;
use BillbeeBricklink\Helpers\Time;
use BillbeeBricklink\Transformers\OrderCommentTransformer;
use BillbeeBricklink\Transformers\OrderProductTransformer;
use BillbeeBricklink\Transformers\OrderTransformer;
use BillbeeBricklink\BricklinkApi\Bricklink;
use Billbee\CustomShopApi\Exception\OrderNotFoundException;
use Billbee\CustomShopApi\Model\{PagedData, Order, OrderProduct};
use Billbee\CustomShopApi\Repository\OrdersRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use DateTime;
use Exception;

class OrderRepository implements OrdersRepositoryInterface
{
    // Dependencies for Bricklink API, HTTP client, and logger
    private Bricklink $gateway;
    private Client $client;
    private LoggerInterface $logger;

    // Constructor to initialize API gateway, client, and logger
    public function __construct(Bricklink $gateway, LoggerInterface $logger, bool $group_parts=false)
    {
        $this->gateway = $gateway;
        $this->client = $this->gateway->client;
        $this->logger = $logger;
        $this->group_parts = $group_parts;
    }

    /** @inheritDoc */
    public function getOrders($page, $pageSize, DateTime $modifiedSince): PagedData
    {
        try {
            // Fetch all orders from the Bricklink API
            $bl_orders = ResponseHelper::getData($this->client->get("orders?status=-purged"));

            // Filter orders modified since the specified date
            $bl_orders_after_modified_since = array_filter($bl_orders, function ($bl_order) use ($modifiedSince) {
                return Time::fromBl($bl_order['date_status_changed']) > $modifiedSince;
            });

            // Paginate the filtered orders
            $bl_orders_on_page = array_slice($bl_orders_after_modified_since, ($page - 1) * $pageSize, $pageSize);

            // Fetch detailed information for each order on the current page
            $my_orders = array_map(function ($bl_order) {
                return $this->getOrder($bl_order['order_id']);
            }, $bl_orders_on_page);

            // Return paged data with the list of orders and total count
            return new PagedData($my_orders, count($bl_orders_after_modified_since));
        } catch (GuzzleException|Exception $e) {
            // Log and handle errors
            $this->logger->error("Failed to fetch orders: " . $e->getMessage());
            return new PagedData();
        }
    }

    /** @inheritDoc */
    public function getOrder($orderId): Order
    {
        try {
            // Fetch order details from the Bricklink API
            $bl_order = ResponseHelper::getData($this->client->get("orders/$orderId"));

            // Transform the Bricklink order data to a standard Order object
            $order = OrderTransformer::toOrder($bl_order);

            // Fetch and populate order items
            $this->getOrderItems($order);

            // Fetch and populate order comments
            $this->getOrderComments($order, $bl_order['seller_name']);

            // Log successful fetch
            $this->logger->info("Successfully fetched order $orderId");

            return $order;
        } catch (GuzzleException|Exception $e) {
            // Log and throw an exception if order fetching fails
            $this->logger->error("Failed to fetch order $orderId: " . $e->getMessage());
            throw new OrderNotFoundException("Failed to fetch order $orderId");
        }
    }

    /** @inheritDoc */
    public function acknowledgeOrder($orderId): void
    {
        try {
            // Retrieve order remark and status
            $order = ResponseHelper::getData($this->client->get("orders/$orderId"));
            $remark = $order['remarks'];
            $status = $order['status'];

            // Add a "Billbee" remark if not already present
            if (!str_contains($remark, "Billbee")) {
                $this->client->put("orders/$orderId", ['json' => ["remarks" => "Billbee " . $remark]]);
            }
            // Set status to 'processing' if current state is 'pending'
            if (strtolower($status) == 'pending') {
                $this->setOrderState($orderId, 16, '');
            }
        } catch (GuzzleException|Exception $e) {
            // Log errors and throw appropriate exceptions
            $this->logger->error("Failed to acknowledge order $orderId: " . $e->getMessage());
            if ($e->getCode() === 404) {
                throw new OrderNotFoundException("Order $orderId not found");
            } else {
                throw new Exception("Failed to acknowledge order $orderId");
            }
        }
    }

    /** @inheritDoc */
    public function setOrderState($orderId, $newStateId, $comment): void
    {
        try {
            // Convert Billbee state ID to Bricklink-compatible state
            $newState = OrderStatus::toBl($newStateId);

            if (in_array($newState, ['processing', 'ready', 'paid', 'packed', 'shipped', 'completed',])) {
                // Update order status via the API
                $this->client->put("orders/$orderId/status", ['json' => ["field" => "status", "value" => $newState]]);
            } elseif ($newState === 'filed') {
                $this->client->put("orders/$orderId", ['json' => ["is_filed" => true]]);
            }
        } catch (GuzzleException $e) {
            // Log errors and throw appropriate exceptions
            $this->logger->error("Failed to update status for order $orderId: " . $e->getMessage());
            if ($e->getCode() === 404) {
                throw new OrderNotFoundException("Order $orderId not found");
            } else {
                throw new Exception("Failed to update order $orderId");
            }
        }
    }

    /**
     * Get items for a specific order.
     * @param Order $order The order to populate with items.
     * @throws Exception If the order items cannot be fetched.
     */
    public function getOrderItems(Order $order): void
    {
        try {
            // Fetch order items from the Bricklink API
            $bl_order_items = ResponseHelper::getData($this->client->get("orders/$order->orderId/items"));
            
            $combined_parts_value = 0;
            $combined_parts_quantity = 0;
            
            // Transform and populate items into the Order object
            foreach ($bl_order_items as $batch_items) {
                foreach ($batch_items as $order_item) {
                    if ($order_item['item']['type'] != 'PART' or !$this->group_parts) {
                        $order->items[] = OrderProductTransformer::toOrderProduct($order_item);
                    } else {
                        // Get total price and quantity of parts not listed individually
                        $price_with_discounts = min($order_item['unit_price'], $order_item['unit_price_final']);
                        $combined_parts_value += $price_with_discounts * $order_item['quantity'];
                        $combined_parts_quantity += $order_item['quantity'];
                    }
                }
            }
            if ($combined_parts_quantity > 0) {
                // Create combined entry for parts not listed individually
                $combinedParts = new OrderProduct();
                $combinedParts->name = "LEGO Parts ($combined_parts_quantity pieces)";
                $combinedParts->quantity = 1;
                $combinedParts->unitPrice = round($combined_parts_value, 2);
                $order->items[] = $combinedParts;
            }
        } catch (GuzzleException|Exception $e) {
            // Log errors and throw an exception
            $this->logger->error("Failed to fetch order items for $order->orderId: " . $e->getMessage());
            throw new Exception("Failed to fetch order items for $order->orderId");
        }
    }

    /**
     * Get comments for a specific order.
     * @param Order $order The order to populate with comments.
     * @param string $sellerName The name of the seller.
     */
    public function getOrderComments(Order $order, string $sellerName): void
    {
        try {
            // Fetch order comments/messages from the Bricklink API
            $bl_order_comments = ResponseHelper::getData($this->client->get("orders/$order->orderId/messages"));

            // Filter and transform relevant comments into the Order object
            foreach ($bl_order_comments as $order_comment) {
                if ($order_comment['body'] != "Seller left you feedback."
                    && $order_comment['body'] != "You left seller feedback."
                    && strlen($order_comment['body']) < 2000) {
                    $order->comments[] = OrderCommentTransformer::toOrderComment($order_comment, $sellerName);
                }
            }
        } catch (GuzzleException|Exception $e) {
            // Log errors but do not interrupt execution
            $this->logger->error("Failed to fetch order comments for $order->orderId: " . $e->getMessage());
        }
    }
}
