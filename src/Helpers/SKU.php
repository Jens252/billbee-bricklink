<?php

namespace BillbeeBricklink\Helpers;

class SKU
{
    /**
     * Converts a BrickLink product ID to a standardized SKU format.
     *
     * @param string $productId The BrickLink product ID.
     * @param string $BlType The BrickLink item type (e.g., SET, PART).
     * @param int|null $color_id The color ID (only relevant for parts).
     * @param bool $SetNoIsSKU Whether to strip the "-1" suffix from SET numbers.
     * @return string The formatted SKU.
     */
    public static function fromBl(string $productId, string $BlType, int $color_id = null, bool $SetNoIsSKU = true): string
    {
        if ($BlType == "SET") {
            // If the product ID starts with at least three digits and ends with "-1", remove "-1" if SetNoIsSKU is true.
            if (is_numeric(substr($productId, 0, 3)) && str_ends_with($productId, "-1") && $SetNoIsSKU) {
                return substr($productId, 0, -2);
            } else {
                return $productId;
            }
        } elseif ($BlType == "PART") {
            // For parts, prepend "P" and pad the color ID to three digits.
            return "P" . str_pad($color_id, 3, "0", STR_PAD_LEFT) . $productId;
        } else {
            // Default case: Prefix the product ID with the first letter of its type.
            return $BlType[0] . $productId;
        }
    }

    /**
     * Converts a SKU back into a BrickLink product ID and type.
     *
     * @param string $sku The SKU to convert.
     * @return array An array containing the BrickLink product ID, type, and optionally color ID.
     */
    public static function toBl(string $sku): array
    {
        if (is_numeric($sku)) {
            // If the SKU is purely numeric, assume it's a SET and append "-1".
            return [$sku . "-1", 'SET'];
        } else {
            // Determine the BrickLink type based on the first character of the SKU.
            $blType = getTypeByInitial($sku[0]);

            if ($blType == "SET") {
                return [$sku, 'SET'];
            } elseif ($blType == "PART") {
                // Extract the color ID and product ID for parts.
                return [substr($sku, 4), 'PART', (int) substr($sku, 1, 3)];
            } else {
                return [substr($sku, 1), $blType];
            }
        }
    }
}

/**
 * Determines the BrickLink item type based on the first character of the SKU.
 *
 * @param string $initial The first character of the SKU.
 * @return string The corresponding BrickLink item type.
 */
function getTypeByInitial(string $initial): string
{
    return match ($initial) {
        'M' => 'MINIFIG',
        'P' => 'PART',
        'B' => 'BOOK',
        'G' => 'GEAR',
        'C' => 'CATALOG',
        'I' => 'INSTRUCTION',
        'U' => 'UNSORTED_LOT',
        'O' => 'ORIGINAL_BOX',
        default => 'SET', // Default to SET if the initial does not match any of the above.
    };
}
