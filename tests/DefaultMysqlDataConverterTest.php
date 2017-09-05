<?php

namespace obo\DataStorage\Tests;

use Tester\Assert;

require __DIR__ . "/bootstrap.php";

/**
 * @testCase
 */
class DataConverterTest extends \Tester\TestCase {

    public function testDeserializeArray() {
        $dataConverter = new \obo\DataStorage\Tests\Assets\DefaultMysqlDataConverter();

        Assert::equal(["foo" => "bar"], $dataConverter::deserializeToArray('a:1:{s:3:"foo";s:3:"bar";}'));

        Assert::equal([], $dataConverter::deserializeToArray(""));

        Assert::equal([], $dataConverter::deserializeToArray(null));

        Assert::exception(function () use ($dataConverter) {
            \error_reporting(\error_reporting() ^ \E_NOTICE);
            $dataConverter::deserializeToArray("{some;wrong;string}");
            \error_reporting(\E_ALL);
        }, '\obo\Exceptions\Exception');
    }

    public function testDeserializeObject() {
        $dataConverter = new \obo\DataStorage\Tests\Assets\DefaultMysqlDataConverter();

        Assert::type('stdClass', $dataConverter::deserializeToObject('O:8:"stdClass":0:{}'));

        Assert::equal(null, $dataConverter::deserializeToObject(""));

        Assert::equal(null, $dataConverter::deserializeToObject(null));

        Assert::exception(function () use ($dataConverter) {
            \error_reporting(\error_reporting() ^ \E_NOTICE);
            $dataConverter::deserializeToObject("{some;wrong;string}");
            \error_reporting(\E_ALL);
        }, '\obo\Exceptions\Exception');
    }

}

$testCase = new DataConverterTest();
$testCase->run();
