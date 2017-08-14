<?php

namespace obo\DataStorage;

class Connection extends \Dibi\Connection {

    /**
     * @var array
     */
    private $databases;

    /**
     * @var string
     */
    private $defaultDatabase;

    /**
     * @var string
     */
    const DATABASE_KEY = "database";

    /**
     * @var string
     */
    const DEFAULT_DATABASE_KEY = "defaultDatabase";

    /**
     * @var string
     */
    const DATABASES_KEY = "databases";

    /**
     * @var string
     */
    const ALL_QUERIES_TIMER_ENABLED_KEY = "allQueriesTimerEnabled";

    /**
     * @var string
     */
    const DEFAULT_TIMER_NAME = "__defaultTimer";

    /**
     * @var bool
     */
    protected $queriesTimerEnabled = false;

    /**
     * @var bool
     */
    protected $allQueriesTimerEnabled = false;

    /**
     * @var float
     */
    protected $queriesTime = 0;

    /**
     * @var array
     */
    protected $timers = [];

    /**
     * Additional connection options:
     *   - defaultDatabase (string)
     *   - databases (associative array of string) e.g. databaseAlias => databaseName
     *
     * @param mixed $config connection parameters
     * @param string $name connection name
     * @throws \Exception
     */
    public function __construct($config, $name = null) {
        if (is_string($config)) {
            parse_str($config, $config);
        } elseif ($config instanceof Traversable) {
            $tmp = [];
            foreach ($config as $key => $val) {
                $tmp[$key] = $val instanceof Traversable ? iterator_to_array($val) : $val;
            }
            $config = $tmp;
        } elseif (!is_array($config)) {
            throw new \InvalidArgumentException('Configuration must be array, string or object.');
        }

        if (!isset($config[static::DATABASES_KEY])) {
            throw new \InvalidArgumentException('Configuration key databases has to be defined.');
        }

        if (!is_array($config[static::DATABASES_KEY])) {
            throw new \InvalidArgumentException('Database configuration has to an array.');
        }

        if (!isset($config[static::DEFAULT_DATABASE_KEY])) {
            throw new \InvalidArgumentException('Default database is not defined.');
        }

        if (!isset($config[static::DATABASES_KEY][$config[static::DEFAULT_DATABASE_KEY]])) {
            throw new \InvalidArgumentException(\sprintf('Configuration for default database with name %s does not exists.', $config[static::DEFAULT_DATABASE_KEY]));
        }

        if (isset($config[static::ALL_QUERIES_TIMER_ENABLED_KEY])) {
            $this->allQueriesTimerEnabled = $config[static::ALL_QUERIES_TIMER_ENABLED_KEY];
        }

        $this->databases = $config[static::DATABASES_KEY];
        $this->defaultDatabase = $this->getStorageNameByAlias($config[static::DEFAULT_DATABASE_KEY]);

        unset($config[static::DATABASES_KEY]);
        unset($config[static::DEFAULT_DATABASE_KEY]);
        $config[static::DATABASE_KEY] = $this->defaultDatabase;
        parent::__construct($config, $name);
    }

    /**
     * @return float
     */
    public function getTotalTime() {
        return $this->allQueriesTimerEnabled ? $this->queriesTime : null;
    }

    /**
     * @param string $timerName
     * @return void
     */
    public function startTimer($timerName = null) {
        $this->queriesTimerEnabled = true;
        if ($timerName === null) $timerName = self::DEFAULT_TIMER_NAME;
        $this->timers[$timerName] = $this->queriesTime;
    }

    /**
     * @param string $timerName
     * @return float
     */
    public function readTimer($timerName = null) {
        if ($timerName === null) $timerName = self::DEFAULT_TIMER_NAME;
        return isset($this->timers[$timerName]) ? $this->queriesTime - $this->timers[$timerName] : null;
    }

    /**
     * @param string $alias
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getStorageNameByAlias($alias) {
        if (!isset($this->databases[$alias])) {
            throw new \InvalidArgumentException(\sprintf('Database with alias %s does not exist in the configuration.', $alias));
        }
        return $this->databases[$alias];
    }

    /**
     * @return string
     */
    public function getDefaultStorageName() {
        return $this->defaultDatabase;
    }

    /**
     * @param string $storageName
     * @return void
     */
    protected function switchToStorageWithName($storageName) {
        $this->query("USE [" . $storageName . "]");
    }

    /**
     * @param string $storageAlias
     * @throws \InvalidArgumentException
     * @return void
     */
    protected function switchToStorageWithAlias($storageAlias) {
        $this->switchToStorageWithName($this->getStorageNameByAlias($storageAlias));
    }

    /**
     * @return void
     */
    protected function switchToDefaultStorage() {
        $this->switchToStorageWithName($this->getDefaultStorageName());
    }

    /**
     * Import SQL dump from file to database with the given alias
     * @param string $filename
     * @param string $storageAlias
     * @return int count of sql commands
     */
    public function loadFileToStorageWithAlias($filename, $storageAlias) {
        $this->switchToStorageWithAlias($storageAlias);
        parent::loadFile($filename);
        $this->switchToDefaultStorage();
    }

    /**
     * @param  array|mixed
     * @return \Dibi\Result|int   result set object (if any)
     */
    public function executeQuery($args) {
        $args = func_get_args();

        if ($this->queriesTimerEnabled) {
            $this->queriesTime -= microtime(true);
            $result = parent::query($args);
            $this->queriesTime += microtime(true);
            return $result;
        }

        return parent::query($args);
    }

    /**
     * @param  array|mixed
     * @return \Dibi\Row|bool
     */
    public function fetch($args) {
        $args = func_get_args();

        if ($this->queriesTimerEnabled) {
            $this->queriesTime -= microtime(true);
            $result = parent::fetch($args);
            $this->queriesTime += microtime(true);
            return $result;
        }

        return parent::fetch($args);
    }

    /**
     * @param  array|mixed
     * @return \Dibi\Row[]
     */
    public function fetchAll($args) {
        $args = func_get_args();

        if ($this->queriesTimerEnabled) {
            $this->queriesTime -= microtime(true);
            $result = parent::fetchAll($args);
            $this->queriesTime += microtime(true);
            return $result;
        }

        return parent::fetchAll($args);
    }

    /**
     * @param  array|mixed
     * @return string|bool
     */
    public function fetchSingle($args) {
        $args = func_get_args();

        if ($this->queriesTimerEnabled) {
            $this->queriesTime += -microtime(true);
            $result = parent::fetchSingle($args);
            $this->queriesTime += microtime(true);
            return $result;
        }

        return parent::fetchSingle($args);
    }

}
