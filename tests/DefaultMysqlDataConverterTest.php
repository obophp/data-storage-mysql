<?php

namespace obo\DataStorage\Tests;

use Tester\Assert;

require __DIR__ . "/bootstrap.php";

/**
 * @testCase
 */
class DataConverterTest extends \Tester\TestCase {

    /**
     * @var \obo\Carriers\PropertyInformationCarrier
     */
    public $propertyInformation;

    public function setUp() {
        parent::setUp();
        $this->propertyInformation = new \obo\Carriers\PropertyInformationCarrier();
    }

    public function testDeserializeArray() {
        $dataConverter = new \obo\DataStorage\Tests\Assets\DefaultMysqlDataConverter();

        Assert::equal(["foo" => "bar"], $dataConverter::deserializeToArray('a:1:{s:3:"foo";s:3:"bar";}', $this->propertyInformation));

        Assert::equal([], $dataConverter::deserializeToArray("", $this->propertyInformation));

        Assert::equal([], $dataConverter::deserializeToArray(null, $this->propertyInformation));

        $propertyInformation = $this->propertyInformation;
        Assert::exception(function () use ($dataConverter, $propertyInformation) {
            \error_reporting(\error_reporting() ^ \E_NOTICE);
            $dataConverter::deserializeToArray("{some;wrong;string}", $propertyInformation);
            \error_reporting(\E_ALL);
        }, '\obo\Exceptions\Exception');
    }

    public function testDeserializeObject() {
        $dataConverter = new \obo\DataStorage\Tests\Assets\DefaultMysqlDataConverter();

        Assert::type('stdClass', $dataConverter::deserializeToObject('O:8:"stdClass":0:{}', $this->propertyInformation));

        Assert::equal(null, $dataConverter::deserializeToObject("", $this->propertyInformation));

        Assert::equal(null, $dataConverter::deserializeToObject(null, $this->propertyInformation));

        $propertyInformation = $this->propertyInformation;
        Assert::exception(function () use ($dataConverter, $propertyInformation) {
            \error_reporting(\error_reporting() ^ \E_NOTICE);
            $dataConverter::deserializeToObject("{some;wrong;string}", $propertyInformation);
            \error_reporting(\E_ALL);
        }, '\obo\Exceptions\Exception');
    }

}

$testCase = new DataConverterTest();
$testCase->run();
