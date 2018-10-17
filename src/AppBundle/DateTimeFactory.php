<?php
/** src/AppBundle/DateTimeFactory.php */

namespace AppBundle;

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
