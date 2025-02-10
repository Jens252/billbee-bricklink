<?php

namespace BillbeeBricklink\Transformers;

// Import necessary classes
use Billbee\CustomShopApi\Model\Address; // Address model used in the CustomShop API
use BillbeeBricklink\Helpers\Address as AddressHelper; // Helper for processing Street and House number

class AddressTransform
{
    /**
     * Transform a response array into an Address object.
     *
     * @param array $response The response array containing shipping address details.
     * @return Address A fully populated Address object.
     */
    public static function toAddress(array $response): Address
    {
        // Instantiate a new Address object
        $address = new Address();

        // Map the first and last name from the response to the Address object
        $address->firstName = $response['shipping']['address']['name']['first'];
        $address->lastName = $response['shipping']['address']['name']['last'];

        // Retrieve the main address line (address1) from the response
        $address1 = $response['shipping']['address']['address1'];

        // Use AddressHelper to parse the address into street and house number if possible
        $address1_split = AddressHelper::fromBl($address1);

        // If parsing is successful, set street and house number separately
        if (!empty($address1_split)) {
            $address->street = $address1_split['street'];
            $address->houseNumber = $address1_split['house_number'];
        } else {
            // Otherwise, assign the entire address line to the street field
            $address->street = $address1;
        }

        // Map additional address details from the response
        $address->address2 = $response['shipping']['address']['address2']; // Second address line (optional)
        $address->postcode = $response['shipping']['address']['postal_code']; // Postal code
        $address->city = $response['shipping']['address']['city']; // City name
        $address->countryCode = $response['shipping']['address']['country_code']; // Country code (e.g., "US")
        $address->state = $response['shipping']['address']['state']; // State or region

        // Return the populated Address object
        return $address;
    }
}
