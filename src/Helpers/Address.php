<?php

namespace BillbeeBricklink\Helpers;

class Address
{
    public static function fromBl($address) : ?array
    {
        // Normalize the address by removing extra spaces and commas
        $normalizedAddress = trim(preg_replace('/\s+/', ' ', str_replace(',', ' ', $address)));

        // Updated regex pattern to allow alphanumeric house numbers (e.g., 123d or 123A)
        $pattern = '/^(\d+[\w\-\/]*)\s+(.+)|(.+?)\s+(\d+[\w\-\/]*)$/i';

        if (preg_match($pattern, $normalizedAddress, $matches)) {
            if (!empty($matches[1])) {
                // Case: house number comes first (e.g., 123b North Main Street)
                $houseNumber = $matches[1];
                $street = $matches[2];
            } else {
                // Case: street comes first (e.g., North Main Street 123b)
                $street = $matches[3];
                $houseNumber = $matches[4];
            }

            // Validate if both street and house number are not empty
            if (!empty($street) && !empty($houseNumber)) {
                return [
                    'street' => trim($street),
                    'house_number' => trim($houseNumber)
                ];
            }
        }

        // Return null if address format is unrecognized
        return null;
    }
}
