<?php

namespace BillbeeBricklink\Helpers;

class PaymentMethod
{
    /**
     * Convert a Bricklink payment method string to a corresponding integer identifier.
     *
     * @param string $payment_method The payment method string (e.g., 'PayPal (Onsite)', 'Credit/Debit (Powered by Stripe)', 'IBAN').
     * @return int The corresponding integer identifier for the payment method.
     */
    public static function fromBl(string $payment_method) : int
    {
        // Use a match expression to return the corresponding integer based on the payment method string
        return match ($payment_method) {
            'IBAN', 'Bank Transfer' => 1,  // Bank Transfer
            'COD (Cash On Delivery)' => 2, // Nachnahme
            'PayPal (Onsite)', 'PayPal' => 3,  // PayPal
            'Cash (no COD)' => 4, // Cash
            'Visa/MasterCard', 'American Express' => 31, // Credit card
            'Credit/Debit (Powered by Stripe)' => 63,  // Stripe
            default => 22, // Return 22 for any unrecognized payment method (default case)
        };
    }
}
