<?php
namespace Extensions\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;

class UTCDateTimeType extends \Doctrine\DBAL\Types\DateTimeType
{
	static private $utc = null;

	public function convertToDatabaseValue($value, AbstractPlatform $platform)
	{
		if ($value === null) {
			return null;
		}
		
		if (!self::$utc) {
            self::$utc = new \DateTimeZone('UTC');
        }

        $value->setTimeZone(self::$utc);

		return $value->format($platform->getDateTimeFormatString());
	}

	public function convertToPHPValue($value, AbstractPlatform $platform)
	{
		if ($value === null) {
			return null;
		}

		if (!self::$utc) {
            self::$utc = new \DateTimeZone('UTC');
        }

        $val = \DateTime::createFromFormat(
				$platform->getDateTimeFormatString(),
				$value,
				self::$utc
		);
		if (!$val) {
			throw ConversionException::conversionFailed($value, $this->getName());
		}
		return $val;
	}
}