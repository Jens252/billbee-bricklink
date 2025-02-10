<?php

namespace BillbeeBricklink\Helpers;

use DateTime; // DateTime class for handling date and time operations

class Time
{
    /**
     * Convert a Bricklink datetime string to a DateTime object.
     *
     * @param string $datetime The Bricklink datetime string (in the format 'Y-m-d\TH:i:s.v\Z').
     * @return DateTime A DateTime object representing the given datetime string.
     */
    public static function fromBl(string $datetime) : DateTime
    {
        // Convert the Bricklink datetime string into a DateTime object using the specified format
        return DateTime::createFromFormat('Y-m-d\TH:i:s.v\Z', $datetime);
    }

    /**
     * Convert a DateTime object to a Bricklink datetime string.
     *
     * @param DateTime $datetime The DateTime object to be converted.
     * @return string A string representation of the datetime in the format 'Y-m-d\TH:i:s.v\Z'.
     */
    public static function toBl(DateTime $datetime) : string
    {
        // Convert the DateTime object into a Bricklink datetime string using the specified format
        return $datetime->format('Y-m-d\TH:i:s.v\Z');
    }
}
