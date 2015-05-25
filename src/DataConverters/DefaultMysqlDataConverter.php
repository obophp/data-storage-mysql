<?php

/**
 * This file is part of the Obo framework for application domain logic.
 * Obo framework is based on voluntary contributions from different developers.
 *
 * @link https://github.com/obophp/obo
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

namespace obo\DataStorage\Convertors;

class DefaultMysqlDataConverter extends \obo\Object implements \obo\DataStorage\Interfaces\IDataConverter {
    /**
     * @param string $combinationCode
     * @return string
     * @throws \obo\Exceptions\Exception
     */
    public function convertFilterForCombinationCode($combinationCode) {
        switch ($combinationCode) {
            case "Dtinyint->Oentity":
            case "Dtinyint->Ointeger":
            case "Dtinyint->Onumber":
            case "Dsmallint->Oentity":
            case "Dsmallint->Ointeger":
            case "Dsmallint->Onumber":
            case "Dmediumint->Oentity":
            case "Dmediumint->Ointeger":
            case "Dmediumint->Onumber":
            case "Dint->Oentity":
            case "Dint->Ointeger":
            case "Dint->Onumber":
            case "Dbigint->Oentity":
            case "Dbigint->Ointeger":
            case "Dbigint->Onumber":
            case "Ddecimal->Onumber":
            case "Dfloat->Ofloat":
            case "Dfloat->Onumber":
            case "Ddouble->Ofloat":
            case "Ddouble->Onumber":
            case "Ddate->OdateTime":
            case "Ddatetime->OdateTime":
            case "Dtimestamp->Onumber":
            case "Dtime->OdateTime":
            case "Dyear->OdateTime":
            case "Dchar->Oentity":
            case "Dchar->Onumber":
            case "Dchar->Ostring":
            case "Dvarchar->Oentity":
            case "Dvarchar->Ostring":
            case "Dtinytext->Ostring":
            case "Dtext->Ostring":
            case "Dmediumtext->Ostring":
            case "Dlongtext->Ostring":
            case "Denum->Oentity":
            case "Denum->Onumber":
            case "Dtinyint->Omixed":
            case "Dsmallint->Omixed":
            case "Dmediumint->Omixed":
            case "Dint->Omixed":
            case "Dbigint->Omixed":
            case "Ddecimal->Omixed":
            case "Dfloat->Omixed":
            case "Ddouble->Omixed":
            case "Ddate->Omixed":
            case "Ddatetime->Omixed":
            case "Dtimestamp->Omixed":
            case "Dtime->Omixed":
            case "Dyear->Omixed":
            case "Dchar->Omixed":
            case "Dvarchar->Omixed":
            case "Dtinytext->Omixed":
            case "Dtext->Omixed":
            case "Dmediumtext->Omixed":
            case "Dlongtext->Omixed":
            case "Denum->Omixed":
            case "Oarray->Dset":
            case "OdateTime->Ddate":
            case "OdateTime->Ddatetime":
            case "OdateTime->Dtime":
            case "OdateTime->Dyear":
            case "Oentity->Denum":
            case "Ofloat->Dfloat":
            case "Ofloat->Ddouble":
            case "Ofloat->Denum":
            case "Ointeger->Dtinyint":
            case "Ointeger->Dsmallint":
            case "Ointeger->Dmediumint":
            case "Ointeger->Dint":
            case "Ointeger->Dbigint":
            case "Ointeger->Ddecimal":
            case "Ointeger->Dtimestamp":
            case "Ointeger->Dtime":
            case "Ointeger->Denum":
            case "Onumber->Denum":
            case "Ostring->Dchar":
            case "Ostring->Dvarchar":
            case "Ostring->Dtinytext":
            case "Ostring->Dtext":
            case "Ostring->Dmediumtext":
            case "Ostring->Dlongtext":
            case "Ostring->Denum":
            case "Omixed->Dtinyint":
            case "Omixed->Dsmallint":
            case "Omixed->Dmediumint":
            case "Omixed->Dint":
            case "Omixed->Dbigint":
            case "Omixed->Ddecimal":
            case "Omixed->Dfloat":
            case "Omixed->Ddouble":
            case "Omixed->Ddate":
            case "Omixed->Ddatetime":
            case "Omixed->Dtimestamp":
            case "Omixed->Dtime":
            case "Omixed->Dyear":
            case "Omixed->Dchar":
            case "Omixed->Dvarchar":
            case "Omixed->Dtinytext":
            case "Omixed->Dtext":
            case "Omixed->Dmediumtext":
            case "Omixed->Dlongtext":
            case "Denum->Ostring":
            case "Omixed->Denum": return null;
            case "Dtinyint->Ofloat":
            case "Dsmallint->Ofloat":
            case "Dmediumint->Ofloat":
            case "Dint->Ofloat":
            case "Dbigint->Ofloat":
            case "Ddecimal->Ofloat":
            case "Dchar->Ofloat":
            case "Dvarchar->Ofloat":
            case "Denum->Ofloat":
            case "Ointeger->Dfloat":
            case "Ointeger->Ddouble":
            case "Onumber->Dfloat":
            case "Ostring->Ddecimal":
            case "Ostring->Dfloat":
            case "Ostring->Ddouble": return "toFloat";
            case "Dtinyint->Ostring":
            case "Dsmallint->Ostring":
            case "Dmediumint->Ostring":
            case "Dint->Ostring":
            case "Dbigint->Ostring":
            case "Ddecimal->Ostring":
            case "Dfloat->Ostring":
            case "Ddouble->Ostring":
            case "Ddatetime->Ostring":
            case "Dtimestamp->Ostring":
            case "Oentity->Dchar":
            case "Oentity->Dvarchar":
            case "Oentity->Dtinytext":
            case "Oentity->Dtext":
            case "Oentity->Dmediumtext":
            case "Oentity->Dlongtext":
            case "Ofloat->Dchar":
            case "Ofloat->Dvarchar":
            case "Ofloat->Ddecimal":
            case "Ointeger->Dchar":
            case "Ointeger->Dvarchar":
            case "Onumber->Ddecimal":
            case "Onumber->Ddouble":
            case "Onumber->Dchar":
            case "Ostring->Ddecimal":
            case "Ostring->Dfloat":
            case "Ostring->Ddouble":
            case "Onumber->Dvarchar": return "toString";
            case "Dtinyint->Oboolean":
            case "Dsmallint->Oboolean":
            case "Dmediumint->Oboolean":
            case "Dint->Oboolean":
            case "Dbigint->Oboolean": return "toBoolean";
            case "Ddecimal->Ointeger":
            case "Dfloat->Ointeger":
            case "Ddouble->Ointeger":
            case "Dtimestamp->Ointeger":
            case "Dchar->Ointeger":
            case "Dvarchar->Ointeger":
            case "Denum->Ointeger":
            case "Oboolean->Dtinyint":
            case "Oboolean->Dsmallint":
            case "Oboolean->Dmediumint":
            case "Oboolean->Dint":
            case "Oboolean->Dbigint":
            case "Oentity->Dtinyint":
            case "Oentity->Dsmallint":
            case "Oentity->Dmediumint":
            case "Oentity->Dint":
            case "Oentity->Dbigint":
            case "Ofloat->Dtinyint":
            case "Ofloat->Dsmallint":
            case "Ofloat->Dmediumint":
            case "Ofloat->Dint":
            case "Ofloat->Dbigint":
            case "Onumber->Dtinyint":
            case "Onumber->Dsmallint":
            case "Onumber->Dmediumint":
            case "Onumber->Dint":
            case "Onumber->Dbigint":
            case "Onumber->Dtimestamp":
            case "Ostring->Dtimestamp":
            case "Ddate->Ostring":
            case "Dtime->Ostring":
            case "Dyear->Ostring":
            case "Ostring->Dtinyint":
            case "Ostring->Dsmallint":
            case "Ostring->Dmediumint":
            case "Ostring->Dint":
            case "Ostring->Dbigint": return "toInteger";
            case "OdateTime->Denum":
            case "OdateTime->Dchar":
            case "OdateTime->Dvarchar":return "dateTimeToString";
            case "Dtimestamp->OdateTime": return "timeStampToDateTime";
            case "Dchar->Oarray":
            case "Dchar->Oobject":
            case "Dvarchar->Oarray":
            case "Dvarchar->Oobject":
            case "Dtinytext->Oarray":
            case "Dtinytext->Oobject":
            case "Dtext->Oarray":
            case "Dtext->Oobject":
            case "Dmediumtext->Oarray":
            case "Dmediumtext->Oobject":
            case "Dlongtext->Oarray":
            case "Dlongtext->Oobject": return "deserialize";
            case "Dchar->OdateTime":
            case "Dvarchar->OdateTime":
            case "Ostring->Ddate":
            case "Ostring->Ddatetime":
            case "Ostring->Dtime":
            case "Denum->OdateTime":
            case "Ostring->Dyear": return "stringToDateTime";
            case "Dset->Oarray": return "toArray";
            case "Oarray->Dchar":
            case "Oarray->Dvarchar":
            case "Oarray->Dtinytext":
            case "Oarray->Dtext":
            case "Oarray->Dmediumtext":
            case "Oarray->Dlongtext":
            case "Oobject->Dchar":
            case "Oobject->Dvarchar":
            case "Oobject->Dtinytext":
            case "Oobject->Dtext":
            case "Oobject->Dmediumtext":
            case "Oobject->Dlongtext": return "serialize";
            case "OdateTime->Dtimestamp": return "dateTimeToTimestamp";

            default:
                throw new \obo\Exceptions\Exception("For combination {$combinationCode} does not exist filter");
        }
    }

    /**
     * @param mixed $value
     * @return float
     */
    public static function toFloat($value) {
        return (float) $value;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public static function toString($value) {
        return (string) $value;
    }

    /**
     * @param mixed $value
     * @return boolean
     */
    public static function toBoolean($value) {
        return (boolean) $value;
    }

    /**
     * @param mixed $value
     * @return integer
     */
    public static function toInteger($value) {
        return (integer) $value;
    }

    /**
     * @param mixed $value
     * @return array
     */
    public static function toArray($value) {
        return (array) $value;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public static function serialize($value) {
        return \serialize($value);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public static function deserialize($value) {
        return \unserialize($value);
    }

    /**
     * @param \DateTime $value
     * @return string
     */
    public static function dateTimeToString(\DateTime $value) {
        return $value->format("Y-m-d H:i:s");
    }

    /**
     * @param integer $value
     * @return \DateTime
     */
    public static function timeStampToDateTime($value) {
        $dateTime = new \DateTime;
        $dateTime->setTimestamp($value);
        return $dateTime;
    }

    /**
     * @param string $value
     * @return \DateTime
     */
    public static function stringToDateTime($value) {
        return new \DateTime($value);
    }

    /**
     * @param \DateTime $value
     * @return integer
     */
    public static function dateTimeToTimestamp(\DateTime $value) {
        return $value->getTimestamp();
    }

}
