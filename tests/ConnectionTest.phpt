<?php

namespace obo\DataStorage\Tests;

use Tester\Assert;

require __DIR__ . "/bootstrap.php";

class ConnectionTest extends \Tester\TestCase {

    /**
     * @var string
     */
    const NONEXISTENT_ALIAS = "nonexistentAlias";

    /**
     * @var string
     */
    const MAIN_SQL_FILE = "test.sql";

    /**
     * @var string
     */
    const LOAD_SQL_FILE = "load.sql";

    /**
     * @var \obo\DataStorage\Connection
     */
    private $connection;

    /**
     * @var array
     */
    private $config;

    public function __construct() {
        $this->connection = \obo\DataStorage\Tests\Assets\Storage::getConnection();
        $this->config = \obo\DataStorage\Tests\Assets\Storage::getConfig();
    }

    protected function setUp() {
        parent::setUp();
        Assets\Storage::getConnection()->loadFile(__DIR__ . DIRECTORY_SEPARATOR . "__assets" . DIRECTORY_SEPARATOR . static::MAIN_SQL_FILE);
    }

    public function testGetStorageNameByAlias() {
        $storageList = $this->config[\obo\DataStorage\Connection::DATABASES_KEY];

        foreach ($storageList as $alias => $storage) {
            Assert::equal($storage, $this->connection->getStorageNameByAlias($alias));
        }

        Assert::exception(
            function () {
                $this->connection->getStorageNameByAlias(self::NONEXISTENT_ALIAS);
            },
            \InvalidArgumentException::class
        );
    }

    public function testGetDefaultStorageName() {
        Assert::equal(
            $this->connection->getStorageNameByAlias($this->config[\obo\DataStorage\Connection::DEFAULT_DATABASE_KEY]),
            $this->connection->getDefaultStorageName()
        );
    }

    public function testLoadFileToStorage() {
        $file = __DIR__ . DIRECTORY_SEPARATOR . "__assets" . DIRECTORY_SEPARATOR . static::LOAD_SQL_FILE;
        $storageList = $this->config[\obo\DataStorage\Connection::DATABASES_KEY];

        foreach ($storageList as $alias => $storage) {
            $this->connection->loadFileToStorageWithAlias($file, $alias);
            $tableLoaded = (boolean)$this->connection->fetchSingle("SHOW TABLES FROM %n LIKE %s;", $storage, "LoadFileTest");
            Assert::true($tableLoaded, "SQL file was not property loaded");
        }

        Assert::exception(
            function () use ($file) {
                $this->connection->loadFileToStorageWithAlias($file, self::NONEXISTENT_ALIAS);
            },
            \InvalidArgumentException::class
        );
    }
}

$testCase = new ConnectionTest();
$testCase->run();
