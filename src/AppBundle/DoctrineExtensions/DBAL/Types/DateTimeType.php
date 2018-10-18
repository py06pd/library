<?php
/** src/AppBundle/DoctrineExtensions/DBAL/Types/DateTimeType.php */
namespace AppBundle\DoctrineExtensions\DBAL\Types;

use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType as BaseDateTimeType;

/**
 * Class DateTimeType
 * @package AppBundle\DoctrineExtensions\DBAL\Types
 */
class DateTimeType extends BaseDateTimeType
{
    static private $utc;

    /**
     * Convert php datetime to database value
     * @param DateTime         $value
     * @param AbstractPlatform $platform
     * @return mixed|null
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof DateTime) {
            $value->setTimezone(self::getUtc());
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    /**
     * Convert database value to php datetime
     * @param                  $value
     * @param AbstractPlatform $platform
     * @return bool|DateTime|false|mixed
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value || $value instanceof DateTime) {
            return $value;
        }

        $converted = DateTime::createFromFormat($platform->getDateTimeFormatString(), $value, self::getUtc());

        if (!$converted) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getDateTimeFormatString()
            );
        }

        return $converted;
    }

    /**
     * Get utc datetime zone
     * @return DateTimeZone
     */
    private static function getUtc()
    {
        return self::$utc ? self::$utc : self::$utc = new DateTimeZone('UTC');
    }
}
