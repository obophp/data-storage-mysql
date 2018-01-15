<?php

namespace obo\DataStorage\Tests\Assets;

class Storage {

    /**
     * @var array
     */
    private static $config = [
        "host" => "127.0.0.1",
        "defaultDatabase" => "testDb",
        "username" => "root",
        "databases" => [
            "testDb" => "obo-test",
            "testDb2" => "obo-test2"
        ]
    ];

    /**
     * @var \obo\DataStorage\Connection
     */
    private static $connection = null;

    /**
     *
     * @var \obo\DataStorage\MySQL
     */
    private static $dataStorage = null;

    /**
     * @return array
     */
    public static function getConfig() {
        return static::$config;
    }

    /**
     * @return \obo\DataStorage\Connection
     */
    public static function getConnection() {
        if (static::$connection === null) {
            static::$connection = new \obo\DataStorage\Connection(static::$config);
        }

        return static::$connection;
    }

    /**
     * @return \obo\DataStorage\MySQL
     */
    public static function getMySqlDataStorage() {
        if (static::$dataStorage === null) {
            static::$dataStorage = new \obo\DataStorage\MySQL(static::getConnection(), new \obo\DataStorage\DataConverters\DefaultMysqlDataConverter());
        }

        return static::$dataStorage;
    }

}
