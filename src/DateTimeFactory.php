<?php
/** src/App/DateTimeFactory.php */

namespace App;

use DateTime;
use DateTimeZone;

/**
 * DateTimeFactory class
 */
class DateTimeFactory
{
    /**
     * Gets date time object for current datetime
     * @return DateTime
     */
    public function getNow()
    {
        return new DateTime("now", new DateTimeZone("UTC"));
    }
}
