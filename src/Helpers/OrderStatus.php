<?php

namespace BillbeeBricklink\Helpers;

class OrderStatus
{
    /**
     * Convert a Bricklink order status string to a corresponding integer identifier.
     *
     * @param string $status The order status string (e.g., 'pending', 'processing', 'paid', etc.).
     * @return int The corresponding integer identifier for the order status.
     */
    public static function fromBl(string $status) : int
    {
        // Use a match expression to return the corresponding integer based on the lowercase status string
        return match (strtolower($status)) {
            'pending', 'updated', 'processing' => 1,  // 'pending', 'updated' or 'processing' map to 1
            'ready' => 2,  //  'ready' maps to 2
            'paid' => 3,  // 'paid' maps to 3
            'packed' => 13,  // 'packed' maps to 13
            'shipped' => 4,  // 'shipped' maps to 4
            'received', 'completed' => 7,  // 'received' or 'completed' map to 7
            'cancelled' => 8,  // 'cancelled' maps to 8
            default => 0, // Return 0 for any unrecognized status string
        };
    }

    /**
     * Convert an order status integer identifier to the corresponding Bricklink status string.
     *
     * @param int $status_id The order status identifier (e.g., 1, 2, 3, etc.).
     * @return string The corresponding status string (e.g., 'pending', 'processing', 'paid', etc.).
     */
    public static function toBl(int $status_id): string
    {
        // Use a match expression to return the corresponding status string based on the integer status ID
        return match ($status_id) {
            16 => 'processing',
            2, 14 => 'ready',
            3 => 'paid',
            13 => 'packed',
            4 => 'shipped',
            7 => 'completed',
            9 => 'filed',
            default => '',
        };
    }
}
