<?php

/**
 * This file is part of the Obo framework for application domain logic.
 * Obo framework is based on voluntary contributions from different developers.
 *
 * @link https://github.com/obophp/data-storage-dibi
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

namespace obo\DataStorage;

class MySQL extends \obo\Object implements \obo\Interfaces\IDataStorage {

    const PROCESS_SELECT = "select";
    const PROCESS_WHERE = "where";
    const PROCESS_ORDER_BY = "orderBy";
    const PROCESS_JOIN = "join";

    const JOIN_TYPE_INNER = "INNER_JOIN";
    const JOIN_TYPE_LEFT = "LEFT_JOIN";
    const JOIN_KEY_PREFIX = "jk";

    /**
     * @var \obo\DataStorage\Connection
     */
    protected $connection = null;

    /**
     * @var \DibiTranslator
     */
    protected $dibiTranslator = null;

    /**
     * @var \obo\DataStorage\Interfaces\IDataConverter
     */
    protected $dataConverter = null;

    /**
     *  @var \obo\Interfaces\ICache
     */
    protected $cache = null;

    /**
     * @var \obo\Carriers\EntityInformationCarrier[]
     */
    protected $informations = null;

    /**
     * @var array
     */
    protected $joinKeys = null;

    /**
     * @var array
     */
    protected $joinKeysAliases = null;

    /**
     * @var int
     */
    protected $joinKeyCounter = 0;

    /**
     * @var string
     */
    protected $defaultStorageName = null;

    /**
     * @var string
     */
    protected $parameterPlaceholder = \obo\Interfaces\IQuerySpecification::PARAMETER_PLACEHOLDER;

    /**
     * @param \obo\DataStorage\Connection $connection
     * @param \obo\DataStorage\Interfaces\IDataConverter $dataConverter
     * @param \obo\Interfaces\ICache $cache
     * @throws \obo\Exceptions\Exception
     */
    public function __construct(\obo\DataStorage\Connection $connection, \obo\DataStorage\Interfaces\IDataConverter $dataConverter, \obo\Interfaces\ICache $cache = null) {
        if ($connection->getConfig("driver") !== "mysqli" AND $connection->getConfig("driver") !== "mysql") throw new \obo\Exceptions\Exception("Wrong driver has been set for dibi connection. Mysql or mysqli driver was expected.");
        $this->connection = $connection;
        $this->dataConverter = $dataConverter;
        $this->cache = $cache;

        if ($this->connection->getConfig("database") === null) {
            throw new \obo\Exceptions\Exception("No database is selected for the current connection.");
        }

        $this->defaultStorageName = $this->connection->getDefaultStorageName();
    }

    /**
     * @return \obo\DataStorage\Connection
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * @return \DibiTranslator
     */
    protected function getDibiTranslator() {
        if ($this->dibiTranslator instanceof \DibiTranslator) return $this->dibiTranslator;
        return $this->dibiTranslator = new \DibiTranslator($this->getConnection());
    }

    /**
     * @param \obo\Carriers\EntityInformationCarrier $entityInformation
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getStorageNameForEntity(\obo\Carriers\EntityInformationCarrier $entityInformation) {
        if (!empty($entityInformation->storageName)) {
            return $this->connection->getStorageNameByAlias($entityInformation->storageName);
        }

        return $this->defaultStorageName;
    }

    /**
     * @param \obo\Carriers\PropertyInformationCarrier $propertyInformation
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getStorageNameForProperty(\obo\Carriers\PropertyInformationCarrier $propertyInformation) {
        if (!empty($propertyInformation->storageName)) {
            return $this->connection->getStorageNameByAlias($propertyInformation->storageName);
        }

        return $this->getStorageNameForEntity($propertyInformation->entityInformation);
    }

    /**
     * @param string $repository
     * @return string
     */
    protected function extractStorageName($repository) {
        $storage = explode(".", $repository);
        if (count($storage) == 2) {
            return $storage[0];
        } else {
            return $this->defaultStorageName;
        }
    }

    /**
     * @param string $repository
     * @return string
     */
    protected function extractRepositoryName($repository) {
        $storage = explode(".", $repository);
        if (count($storage) == 2) {
            return $storage[1];
        } else {
            return $repository;
        }
    }

    /**
     * @param \obo\Carriers\QueryCarrier $queryCarrier
     * @param boolean $asArray
     * @return string
     * @throws \DibiException
     * @throws \DibiPcreException
     * @throws \obo\Exceptions\AutoJoinException
     * @throws \obo\Exceptions\Exception
     * @throws \obo\Exceptions\PropertyNotFoundException
     */
    public function constructQuery(\obo\Carriers\QueryCarrier $queryCarrier, $asArray = false) {
        if ($queryCarrier->getDefaultEntityClassName() === null) throw new \obo\Exceptions\Exception("Default entity hasn't been set for QueryCarrier");
        $query = "";
        $data = [];
        $queryCarrier = clone $queryCarrier;
        $joins = [];
        $needDistinct = false;

        $entityInformation = $queryCarrier->getDefaultEntityEntityInformation();
        $storageName = $this->getStorageNameForEntity($entityInformation);
        $repositoryName = $entityInformation->repositoryName;

        $primaryPropertyColumn = $entityInformation->informationForPropertyWithName($entityInformation->primaryPropertyName)->columnName;
        $select = $queryCarrier->getSelect();
        $where = $queryCarrier->getWhere();
        $orderBy = $queryCarrier->getOrderBy();
        $join = $queryCarrier->getJoin();

        $needDistinct = $this->process($queryCarrier->getDefaultEntityClassName(), $select, $joins, self::PROCESS_SELECT) || $needDistinct;
        $needDistinct = $this->process($queryCarrier->getDefaultEntityClassName(), $where, $joins, self::PROCESS_WHERE) || $needDistinct;
        $needDistinct = $this->process($queryCarrier->getDefaultEntityClassName(), $orderBy, $joins, self::PROCESS_ORDER_BY) || $needDistinct;
        $needDistinct = $this->process($queryCarrier->getDefaultEntityClassName(), $join, $joins, self::PROCESS_JOIN) || $needDistinct;


        $join["query"] .= \implode($joins, " ");

        if ("COUNT([{$storageName}].[{$repositoryName}].[{$primaryPropertyColumn}])" === \trim($select["query"], " ,")) {
            $query .= "SELECT COUNT(" . ($needDistinct ? "DISTINCT " : "") . "[{$storageName}].[{$repositoryName}].[{$primaryPropertyColumn}])";
        } else {
            $query .= "SELECT " . ($needDistinct ? "DISTINCT " : "") . rtrim($select["query"], ",");
        }

        $data = \array_merge($data, $select["data"]);

        if ($queryCarrier->getFrom()["query"] === "") {
            $query .= " FROM " . "[{$storageName}].[{$repositoryName}]";
        } else {
            $query .= " FROM " . rtrim($queryCarrier->getFrom()["query"], ",");
            $data = \array_merge($data, $queryCarrier->getFrom()["data"]);
        }

        if ($join["query"] !== ""){
            $query .= $join["query"];
            $data = \array_merge($data, $join["data"]);
        }

        if ($where["query"] !== "") {
            $query .= " WHERE " . \preg_replace("#^ *(AND|OR) *#i", "", $where["query"]);
            $data = \array_merge($data, $where["data"]);
        }

        if ($orderBy["query"] !== "") {
            $query .= " ORDER BY " . rtrim($orderBy["query"], ",");
            $data = \array_merge($data, $orderBy["data"]);
        }

        if ($queryCarrier->getLimit()["query"] !== "") {
            $query .= " LIMIT " . $queryCarrier->getLimit()["query"];
            $data = \array_merge($data, $queryCarrier->getLimit()["data"]);
        }

        if ($queryCarrier->getOffset()["query"] !== "") {
            $query .= " OFFSET " . $queryCarrier->getOffset()["query"];
            $data = \array_merge($data, $queryCarrier->getOffset()["data"]);
        }

        $this->clearJoinKeys();

        if ($asArray) return \array_merge([$query], $data);

        return $this->getDibiTranslator()->translate(\array_merge([$query], $data));
    }

    /**
     * @param \obo\Carriers\QueryCarrier $queryCarrier
     * @return array
     */
    public function dataForQuery(\obo\Carriers\QueryCarrier $queryCarrier) {
        return $this->convertDataForExport($this->connection->fetchAll($this->constructQuery($queryCarrier, true)), $queryCarrier->getDefaultEntityEntityInformation());
    }

    /**
     * @param \obo\Carriers\QueryCarrier $queryCarrier
     * @return int
     */
    public function countRecordsForQuery(\obo\Carriers\QueryCarrier $queryCarrier) {
        $queryCarrier = clone $queryCarrier;
        $entityInformation = $queryCarrier->getDefaultEntityEntityInformation();
        $storageName = $this->getStorageNameForEntity($entityInformation);
        $repositoryName = $entityInformation->repositoryName;

        $primaryPropertyColumn = $entityInformation->informationForPropertyWithName($entityInformation->primaryPropertyName)->columnName;
        $queryCarrier->rewriteSelect("COUNT([{$storageName}].[{$repositoryName}].[{$primaryPropertyColumn}])");
        return (int) $this->connection->fetchSingle($this->constructQuery($queryCarrier, true));
    }

    /**
     * @param \obo\Entity $entity
     * @throws \obo\Exceptions\Exception
     */
    public function insertEntity(\obo\Entity $entity) {
        if ($entity->isBasedInRepository()) throw new \obo\Exceptions\Exception("Can't insert entity into storage. Entity is already persisted.");
        $convertedData = $this->convertDataForImport($entity->changedProperties($entity->entityInformation()->persistablePropertiesNames, true, true), $entity->entityInformation());
        $entityInformation = $entity->entityInformation();
        $informationForEntity = $this->informationForEntity($entityInformation);
        $entityStorageName = $this->getStorageNameForEntity($entityInformation);
        $repositoryName = $entityInformation->repositoryName;
        $primaryPropertyColumnName = $entityInformation->informationForPropertyWithName($entityInformation->primaryPropertyName)->columnName;

        if (count($convertedData) > 1 AND $informationForEntity["storages"][$entityStorageName]["transactionEnabled"]) {
            $this->connection->begin();
        }

        $lastInsertId = null;

        foreach ($convertedData as $storageName => $storageData) {
            foreach ($storageData as $repositoryName => $data) {
                if ($lastInsertId) {
                    $data[$primaryPropertyColumnName] = $lastInsertId;
                }
                $this->connection->query("INSERT INTO [{$storageName}].[{$repositoryName}] ", $data);
                if (!$lastInsertId) {
                    $lastInsertId = $this->connection->getInsertId();
                }
            }
        }

        if (count($convertedData) > 1 AND $informationForEntity["storages"][$entityStorageName]["transactionEnabled"]) $this->connection->commit();
        if ($entity->entityInformation()->informationForPropertyWithName($entity->entityInformation()->primaryPropertyName)->autoIncrement) $entity->setValueForPropertyWithName($lastInsertId, $entity->entityInformation()->primaryPropertyName);
    }

    /**
     * @param \obo\Entity $entity
     * @return void
     */
    public function updateEntity(\obo\Entity $entity) {
        $primaryPropertyName = $entity->entityInformation()->primaryPropertyName;
        $primaryPropertyColumnName = $entity->informationForPropertyWithName($primaryPropertyName)->columnName;
        $entityInformation = $entity->entityInformation();
        $informationForEntity = $this->informationForEntity($entityInformation);
        $entityStorageName = $this->getStorageNameForEntity($entityInformation);
        $primaryPropertyPlaceholder = $informationForEntity["storages"][$entityStorageName]["repositories"][$entity->entityInformation()->repositoryName]["columns"][$primaryPropertyColumnName]["placeholder"];
        $changedProperties = $entity->changedProperties($entity->entityInformation()->persistablePropertiesNames, true, true);
        $convertedData = $this->convertDataForImport($changedProperties, $entity->entityInformation());
        if (count($convertedData) > 1 AND $informationForEntity["storages"][$entityStorageName]["transactionEnabled"]) $this->connection->begin();

        foreach ($convertedData as $storageName => $storageData) {
            foreach ($storageData as $repositoryName => $data) {
                $affectedRowsCount = $this->connection->query("UPDATE [{$storageName}].[{$repositoryName}] SET %a", $data, "WHERE [{$storageName}].[{$repositoryName}].[{$primaryPropertyColumnName}] = {$primaryPropertyPlaceholder}", $entity->primaryPropertyValue());
                if ($affectedRowsCount == 0) {
                    $recordsCount = $this->connection->query("SELECT COUNT([{$storageName}].[{$repositoryName}].[{$primaryPropertyColumnName}]) FROM [{$storageName}].[{$repositoryName}] WHERE [{$primaryPropertyColumnName}] = {$primaryPropertyPlaceholder}", $entity->primaryPropertyValue())->fetchSingle();
                    if ($recordsCount === FALSE || $recordsCount === 0) {
                        $this->connection->query("INSERT INTO [{$storageName}].[{$repositoryName}] ", [$primaryPropertyColumnName => $entity->primaryPropertyValue()]);
                        $this->connection->query("UPDATE [{$storageName}].[{$repositoryName}] SET %a", $data, "WHERE [{$storageName}].[{$repositoryName}].[{$primaryPropertyColumnName}] = {$primaryPropertyPlaceholder}", $entity->primaryPropertyValue());
                    }
                }
            }
        }

        if (count($convertedData) > 1 AND $informationForEntity["storages"][$entityStorageName]["transactionEnabled"]) $this->connection->commit();
    }

    /**
     * @param \obo\Entity $entity
     * @return void
     */
    public function removeEntity(\obo\Entity $entity) {
        $primaryPropertyColumnName = $entity->informationForPropertyWithName($entity->entityInformation()->primaryPropertyName)->columnName;
        $entityInformation = $entity->entityInformation();
        $informationForEntity = $this->informationForEntity($entityInformation);
        $entityStorageName = $this->getStorageNameForEntity($entityInformation);
        $primaryPropertyPlaceholder = $informationForEntity["storages"][$entityStorageName]["repositories"][$entity->entityInformation()->repositoryName]["columns"][$primaryPropertyColumnName]["placeholder"];
        $convertedData = $this->convertDataForImport($entity->changedProperties($entity->entityInformation()->persistablePropertiesNames, true, true), $entity->entityInformation());
        if (count($informationForEntity["storages"][$entityStorageName]["repositories"]) > 1 AND $informationForEntity["storages"][$entityStorageName]["transactionEnabled"]) $this->connection->begin();

        foreach ($convertedData as $storageName => $storageData) {
            foreach ($storageData as $repositoryName => $data) {
                $this->connection->query("DELETE FROM [{$storageName}].[{$repositoryName}] WHERE [{$storageName}].[{$repositoryName}].[{$primaryPropertyColumnName}] = {$primaryPropertyPlaceholder} LIMIT 1", $entity->primaryPropertyValue());
            }
        }

        if (count($informationForEntity["storages"][$entityStorageName]["repositories"]) > 1 AND $informationForEntity["storages"][$entityStorageName]["transactionEnabled"]) $this->connection->commit();
    }

    /**
     * @param \obo\Carriers\QueryCarrier $specification
     * @param string $repositoryName
     * @param \obo\Entity $owner
     * @param string $targetEntity
     * @return int
     */
    public function countEntitiesInRelationship(\obo\Carriers\QueryCarrier $specification, $repositoryName, \obo\Entity $owner, $targetEntity) {
        return $this->countRecordsForQuery($this->constructJoinQueryForRelationship($specification, $this->extractStorageName($repositoryName), $this->extractRepositoryName($repositoryName), $owner, $targetEntity), $targetEntity::entityInformation()->primaryPropertyName);
    }

    /**
     * @param \obo\Carriers\QueryCarrier $specification
     * @param string $repositoryName
     * @param \obo\Entity $owner
     * @param string $targetEntity
     * @return array
     */
    public function dataForEntitiesInRelationship(\obo\Carriers\QueryCarrier $specification, $repositoryName, \obo\Entity $owner, $targetEntity) {
        return $this->dataForQuery($this->constructJoinQueryForRelationship($specification, $this->extractStorageName($repositoryName), $this->extractRepositoryName($repositoryName), $owner, $targetEntity));
    }

    /**
     * @param \obo\Carriers\QueryCarrier $specification
     * @param string $storageName
     * @param string $repositoryName
     * @param \obo\Entity $owner
     * @param string $targetEntity
     */
    protected function constructJoinQueryForRelationship(\obo\Carriers\QueryCarrier $specification, $storageName, $repositoryName, \obo\Entity $owner, $targetEntity) {
        $targetEntityPropertyNameForSoftDelete = $targetEntity::entityInformation()->propertyNameForSoftDelete;
        $ownerStorageName = $this->getStorageNameForEntity($owner->entityInformation());
        $ownerRepositoryName = $owner->entityInformation()->repositoryName;
        $ownerPrimaryColumnName = $owner->entityInformation()->informationForPropertyWithName($owner->entityInformation()->primaryPropertyName)->columnName;
        $targetEntityStorageName = $this->getStorageNameForEntity($targetEntity::entityInformation());
        $targetEntityRepositoryName = $targetEntity::entityInformation()->repositoryName;
        $targetPrimaryColumnName = $targetEntity::informationForPropertyWithName($targetEntity::entityInformation()->primaryPropertyName)->columnName;
        $ownerJoinKeyPart = $this->createJoinKeyPart($storageName, $repositoryName, $ownerRepositoryName);
        $targetJoinKeyPart = $this->createJoinKeyPart($targetEntityStorageName, $targetEntityRepositoryName, $ownerPrimaryColumnName);
        $joinKeyAlias = $this->createJoinKeyAlias($ownerJoinKeyPart, $targetJoinKeyPart, static::JOIN_TYPE_INNER);

        if ($targetEntityPropertyNameForSoftDelete === "") {
            $specification->join("INNER JOIN [{$storageName}].[{$repositoryName}] ON [{$storageName}].[{$repositoryName}].[{$ownerRepositoryName}] = " . $this->informationForEntity($owner->entityInformation())["storages"][$ownerStorageName]["repositories"][$ownerRepositoryName]["columns"][$ownerPrimaryColumnName]["placeholder"] ." AND [{$storageName}].[{$repositoryName}].[{$ownerRepositoryName}] = [{$targetPrimaryColumnName}]", $owner->primaryPropertyValue());
        } else {
            $softDeleteJoinQuery = "AND [{$storageName}].[{$repositoryName}].[{$targetEntityRepositoryName}].[{$targetEntity::informationForPropertyWithName($targetEntityPropertyNameForSoftDelete)->columnName}] = %b";
            $specification->join("INNER JOIN [{$storageName}].[{$repositoryName}] ON [{$storageName}].[{$repositoryName}].[{$ownerRepositoryName}] = " . $this->informationForEntity($owner->entityInformation())["storages"][$ownerStorageName]["repositories"][$owner->repositoryName]["columns"][$ownerPrimaryColumnName]["placeholder"] . " AND [{$storageName}].[{$repositoryName}].[{$ownerRepositoryName}] = [{$targetPrimaryColumnName}]" . $softDeleteJoinQuery, $owner->primaryPropertyValue(), false);
        }

        return $specification;
    }

    /**
     * @param string $repositoryName
     * @param \obo\Entity[]
     * @return void
     * @throws \obo\Exceptions\Exception
     */
    public function createRelationshipBetweenEntities($repositoryName, array $entities) {
        $storageName = $this->extractStorageName($repositoryName);
        $repositoryName = $this->extractRepositoryName($repositoryName);

        if (\obo\obo::$developerMode) {
            if (!$this->existsRepositoryWithName($storageName, $repositoryName)) throw new \obo\Exceptions\Exception("Relationship can't be created. Repository with the name '{$repositoryName}' located in storage with name '{$storageName}' does not exist.");
            if (\count($entities) !== 2) throw new \obo\Exceptions\Exception("Relationship can't be created. Two entities were expected but " . \count($entities) . " given.");

            foreach ($entities as $entity) {
                if (!$entity instanceof \obo\Entity) throw new \obo\Exceptions\Exception("Relationship can't be created. Entities must be of \obo\Entity instance");
            }
        }

        $entityInformation = $entity->entityInformation();
        $informationForEntity = $this->informationForEntity($entityInformation);
        $this->connection->query("INSERT INTO [{$storageName}].[{$repositoryName}] ", [$entities[0]->entityInformation()->repositoryName => $entities[0]->primaryPropertyValue(), $entities[1]->entityInformation()->repositoryName => $entities[1]->primaryPropertyValue()]);
    }

    /**
     * @param string $repositoryName
     * @param array $entities
     * @return void
     * @throws \obo\Exceptions\Exception
     */
    public function removeRelationshipBetweenEntities($repositoryName, array $entities) {
        $repositoryStorageName = $this->extractStorageName($repositoryName);
        $repositoryRepositoryName = $this->extractRepositoryName($repositoryName);

        if (\obo\obo::$developerMode) {
            if (!$this->existsRepositoryWithName($repositoryStorageName, $repositoryRepositoryName)) throw new \obo\Exceptions\Exception("Relationship can't deleted repository with the name '{$repositoryRepositoryName}' located in storage with name '{$repositoryStorageName}' does not exist");
            if (\count($entities) !== 2) throw new \obo\Exceptions\Exception("Relationship can't be deleted. Two entities were expected but " . \count($entities) . " given. ");

            foreach ($entities as $entity) {
                if (!$entity instanceof \obo\Entity) throw new \obo\Exceptions\Exception("Relationship can't be deleted. Entities must be of \obo\Entity instance.");
            }
        }

        $this->connection->query("DELETE FROM [{$repositoryStorageName}].[{$repositoryRepositoryName}] WHERE [{$entities[0]->entityInformation()->repositoryName}] = {$entities[0]->primaryPropertyValue()} AND [{$entities[1]->entityInformation()->repositoryName}] = " . $this->informationForEntity($entities[1]->entityInformation())["storages"][$this->getStorageNameForEntity($entities[1]->entityInformation())]["repositories"][$entities[1]->entityInformation()->repositoryName]["columns"][$entities[1]->entityInformation()->informationForPropertyWithName($entities[1]->entityInformation()->primaryPropertyName)->columnName]["placeholder"], $entities[1]->primaryPropertyValue());
    }

    /**
     * @param \obo\Carriers\EntityInformationCarrier $entityInformation
     * @return array
     */
    public function informationForEntity(\obo\Carriers\EntityInformationCarrier $entityInformation) {
        return isset($this->informations[$entityInformation->className]) ? $this->informations[$entityInformation->className] : $this->loadInformationForEntity($entityInformation);
    }

    /**
     * @param \obo\Carriers\EntityInformationCarrier $entityInformation
     * @return array
     */
    protected function loadInformationForEntity(\obo\Carriers\EntityInformationCarrier $entityInformation) {
        if (\obo\obo::$developerMode OR $this->cache === null) {
            $information = $this->createInformationForEntity($entityInformation);
        } else {
            if (null === ($information = $this->cache->load($entityInformation->className))) {
                $this->cache->store($entityInformation->className, $information = $this->createInformationForEntity($entityInformation));
            }
        }

        return $this->informations[$entityInformation->className] = $information;
    }

    /**
     * @param \obo\Carriers\EntityInformationCarrier $entityInformation
     * @return array
     * @throws \obo\Exceptions\Exception
     * @throws \obo\Exceptions\PropertyNotFoundException
     */
    protected function createInformationForEntity(\obo\Carriers\EntityInformationCarrier $entityInformation) {
        $information = [
            "storages" => [
                $this->getStorageNameForEntity($entityInformation) => [
                    "repositories" => [
                        $entityInformation->repositoryName => [
                            "columns" => [],
                            "status" => [],
                        ],
                    ],
                    "transactionEnabled" => true,
                ]
            ],
        ];

        $this->loadColumnsForRepository($this->getStorageNameForEntity($entityInformation), $entityInformation->repositoryName, $information);
        $this->loadStatusForRepository($this->getStorageNameForEntity($entityInformation), $entityInformation->repositoryName, $information);

        foreach ($entityInformation->persistablePropertiesNames as $persistablePropertyName) {
            $propertyInformation = $entityInformation->informationForPropertyWithName($persistablePropertyName);
            $propertyStorageName = $this->getStorageNameForProperty($propertyInformation);
            $propertyRepositoryName = $propertyInformation->repositoryName ?: $entityInformation->repositoryName;
            $informationForPropertyStorageName = &$information["storages"][$propertyStorageName];

            if (!isset($informationForPropertyStorageName["repositories"][$propertyRepositoryName])) {
                $this->loadColumnsForRepository($propertyStorageName, $propertyRepositoryName, $information);
                $this->loadStatusForRepository($propertyStorageName, $propertyRepositoryName, $information);
            }

            if (!isset($informationForPropertyStorageName["repositories"][$propertyRepositoryName]["columns"][$propertyInformation->columnName])) {
                throw new \obo\Exceptions\Exception("Column '{$propertyInformation->columnName}' does not exist for persistable property '{$persistablePropertyName}' in table '{$propertyRepositoryName}' and storage with name '{$propertyStorageName}'");
            }

            $informationForPropertyStorageName["repositories"][$propertyRepositoryName]["columns"][$propertyInformation->columnName]["propertyName"] = $propertyInformation->name;
            $informationForPropertyStorageName["repositories"][$propertyRepositoryName]["columns"][$propertyInformation->columnName]["nullable"] = $propertyInformation->nullable;
            $informationForPropertyStorageName["repositories"][$propertyRepositoryName]["columns"][$propertyInformation->columnName]["autoIncrement"] = $propertyInformation->autoIncrement;
            $informationForPropertyStorageName["repositories"][$propertyRepositoryName]["columns"][$propertyInformation->columnName]["exportFilter"] = $this->dataConverter->convertFilterForCombinationCode("D" . $informationForPropertyStorageName["repositories"][$propertyRepositoryName]["columns"][$propertyInformation->columnName]["type"] . "->O" . $propertyInformation->dataType->dataTypeClass());
            $informationForPropertyStorageName["repositories"][$propertyRepositoryName]["columns"][$propertyInformation->columnName]["importFilter"] = $this->dataConverter->convertFilterForCombinationCode("O" . $propertyInformation->dataType->dataTypeClass() . "->D" . $informationForPropertyStorageName["repositories"][$propertyRepositoryName]["columns"][$propertyInformation->columnName]["type"]);
            $informationForPropertyStorageName["repositories"][$propertyRepositoryName]["toColumnName"][$propertyInformation->name] = $propertyInformation->columnName;
            $informationForPropertyStorageName["transactionEnabled"] = $informationForPropertyStorageName["repositories"][$propertyRepositoryName]["status"]["Engine"] === "InnoDB";
        }

        return $this->informations[$entityInformation->className] = $information;
    }

    /**
     * @param string $storageName
     * @param string $repositoryName
     * @param array $information
     */
    protected function loadColumnsForRepository($storageName, $repositoryName, array &$information) {
        foreach ($this->connection->fetchAll("SHOW COLUMNS FROM [{$storageName}].[{$repositoryName}]") as $row) {
            $information["storages"][$storageName]["repositories"][$repositoryName]["columns"][$row->Field] = [
                "field" => $row->Field,
                "type" => $type = preg_replace("#[^a-z]+.*$#", "", $row->Type),
                "placeholder" => $this->placeholderForColumnType($type),
                "null" => $row->Null,
                "key" => $row->Key,
                "default" => $row->Default,
                "extra" => $row->Extra,
            ];
        }
    }

    /**
     * @param string $storageName
     * @param string $repositoryName
     * @param array $information
     */
    protected function loadStatusForRepository($storageName, $repositoryName, array &$information) {
        $information["storages"][$storageName]["repositories"][$repositoryName]["status"] = $this->connection->fetch("SHOW TABLE STATUS FROM [{$storageName}] WHERE [name] = %s", $repositoryName)->toArray();
    }

    /**
     * @param array $data
     * @param \obo\Carriers\EntityInformationCarrier $entityInformation
     * @return array
     */
    protected function convertDataForExport(array $data, \obo\Carriers\EntityInformationCarrier $entityInformation) {
        $convertedData = [];
        $defaultEntityInformation = $entityInformation;

        foreach ($data as $row) {
            $convertedRow = [];
            $nullEntities = [];
            foreach ($row as $columnName => $columnValue) {
                if ($defaultEntityInformation->existInformationForPropertyWithName($columnName)) {
                    $parts = [$columnName];
                } else {
                    $parts = \explode("_", $columnName);
                }

                foreach ($parts as $position => $property) {
                    if ($position !== 0 AND $defaultEntityInformation->informationForPropertyWithName($parts[$position - 1])->relationship !== null) {
                        $connectedEntity = $defaultEntityInformation->informationForPropertyWithName($parts[$position - 1])->relationship->entityClassNameToBeConnected;
                        $defaultEntityInformation = $connectedEntity::entityInformation();
                    }
                    if ($defaultEntityInformation->primaryPropertyName === $property AND $columnValue === null){
                        \barDump($defaultEntityInformation);
                        \barDump($property);
                        \barDump($columnValue);
                        \barDump($data);
                    }

                    if ($defaultEntityInformation->primaryPropertyName === $property AND $columnValue === null) $nullEntities[$parts[$position - 1]] = $parts[$position - 1];
                }

                $information = $this->informationForEntity($defaultEntityInformation);
                $propertyInformation = $defaultEntityInformation->informationForPropertyWithName($property);
                $propertyStorageName = $this->getStorageNameForProperty($propertyInformation);
                $storageInformation = &$information["storages"][$propertyStorageName];
                $propertyRepositoryName = $propertyInformation->repositoryName ?: $defaultEntityInformation->repositoryName;
                $propertyInformationArray = $storageInformation["repositories"][$propertyRepositoryName]["columns"][$storageInformation["repositories"][$propertyRepositoryName]["toColumnName"][$property]];
                $convertedRow[$columnName] = ($propertyInformationArray["exportFilter"] === null OR ($columnValue === null AND $propertyInformationArray["nullable"])) ? $columnValue : $this->dataConverter->{$propertyInformationArray["exportFilter"]}($columnValue);
                $defaultEntityInformation = $entityInformation;
            }

            foreach ($nullEntities as $nullEntity) {
                $convertedRow = \array_intersect_key($convertedRow, \array_flip(\array_filter(\array_keys($convertedRow), function($key) use ($nullEntity) {return \strpos($key, "_" . $nullEntity. "_") === false;
                })));
            }

            $convertedData[] = $convertedRow;
        }

        return $convertedData;
    }

    /**
     * @param array $data
     * @param \obo\Carriers\EntityInformationCarrier $entityInformation
     * @return array
     */
    protected function convertDataForImport(array $data, \obo\Carriers\EntityInformationCarrier $entityInformation) {
        $convertedData = [];

        $information = $this->informationForEntity($entityInformation);
        $entityStorageName = $this->getStorageNameForEntity($entityInformation);

        foreach ($data as $propertyName => $propertyValue) {
            $propertyInformation = $entityInformation->informationForPropertyWithName($propertyName);
            $propertyRepositoryName = $propertyInformation->repositoryName ?: $entityInformation->repositoryName;
            $propertyStorageName = $this->getStorageNameForProperty($propertyInformation);
            $storageInformation = &$information["storages"][$propertyStorageName];
            $entityInformationForPropertyRepositoryName = &$storageInformation["repositories"][$propertyRepositoryName];

            if ($entityInformationForPropertyRepositoryName["columns"][$entityInformationForPropertyRepositoryName["toColumnName"][$propertyName]]["autoIncrement"]) continue;
            $convertedData[$propertyStorageName][$propertyRepositoryName][$entityInformationForPropertyRepositoryName["toColumnName"][$propertyName]] = ($entityInformationForPropertyRepositoryName["columns"][$entityInformationForPropertyRepositoryName["toColumnName"][$propertyName]]["importFilter"] === null OR $propertyValue === null) ? $propertyValue : $this->dataConverter->{$entityInformationForPropertyRepositoryName["columns"][$entityInformationForPropertyRepositoryName["toColumnName"][$propertyName]]["importFilter"]}($propertyValue);
        }

        return $convertedData;
    }

    /**
     * @param string $storageName
     * @param string $repositoryName
     * @return boolean
     */
    protected function existsRepositoryWithName($storageName, $repositoryName) {
        return (boolean) $this->connection->fetchSingle("SHOW TABLES FROM %n LIKE %s;", $storageName, $repositoryName);
    }

    protected function placeholderForColumnType($columnType) {
        switch ($columnType) {
            case "int":
            case "tinyint":
            case "smallint":
            case "mediumint":
            case "bigint":
                return "%i";
            case "float":
            case "double":
            case "decimal":
                return "%f";
            case "date":
            case "datetime":
            case "timestamp":
            case "time":
            case "year":
                return "%t";
            case "char":
            case "varchar":
            case "text":
            case "tinytext":
            case "mediumtext":
            case "longtext":
            case "enum":
                return "%s";
            case "blob":
            case "tinyblob":
            case "mediumblob":
            case "longblob":
                return "%bin";

            default:
                throw new \obo\Exceptions\Exception("There is no placeholder for column type '{$columnType}'");
        }
    }

    /**
     * @param string $input
     * @return string
     */
    protected static function createComment($input) {
        if (!empty($input)) {
            return " /** " . $input . " */ ";
        }
    }

    /**
     * @param string alias
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getJoinKeyByAlias($alias) {
        if (isset($this->joinKeysAliases[$alias])) {
            return $this->joinKeysAliases[$alias];
        }
        throw new \InvalidArgumentException("Join key with alias '{$alias}' does not exist");
    }

    /**
     * @param string $ownerJoinKeyPart
     * @param string $ownedJoinKeyPart
     * @param string $joinType
     * @return string
     */
    protected function createJoinKeyAlias($ownerJoinKeyPart, $ownedJoinKeyPart, $joinType) {
        $result = $ownerJoinKeyPart . "->" . $joinType . "->" . $ownedJoinKeyPart;
        if (isset($this->joinKeys[$result])) {
            return $this->joinKeys[$result];
        }

        $joinKeyAlias = static::JOIN_KEY_PREFIX . (++$this->joinKeyCounter);
        $this->joinKeys[$result] = $joinKeyAlias;
        $this->joinKeysAliases[$joinKeyAlias] = $result;

        return $this->joinKeys[$result];
    }

    /**
     * @param string $entityStorageName
     * @param string $entityRepositoryName
     * @param string $columnName
     * @param string $entityClassName
     * @param string $propertyName
     * @return string
     */
    protected function createJoinKeyPart($entityStorageName, $entityRepositoryName, $columnName) {
        return "{$entityStorageName}:{$entityRepositoryName}:{$columnName}";
    }

    protected function clearJoinKeys() {
        $this->joinKeys = null;
        $this->joinKeysAliases = null;
        $this->joinKeyCounter = 0;
    }

    /**
     * @param string $defaultEntityClassName
     * @param array $part
     * @param array $joins
     * @param int $type
     * @return bool
     * @throws \obo\Exceptions\AutoJoinException
     */
    protected function process($defaultEntityClassName, array &$part, array &$joins, $type) {
        $needDistinct = false;
        $originalDefaultEntityClassName = $defaultEntityClassName;
        self::processJunctions($part["query"], $joins, $defaultEntityClassName, $type);
        \preg_match_all("#(\{(.*?)\}\.?)+#", $part["query"], $blocks);

        foreach ($blocks[0] as $block) {
            $defaultEntityClassName = $originalDefaultEntityClassName;
            $defaultEntityInformation = $defaultEntityClassName::entityInformation();
            $joinKeyAlias = null;
            $selectItemAlias = null;
            $ownerStorageName = $this->getStorageNameForEntity($defaultEntityInformation);
            $ownerRepositoryName = $defaultEntityInformation->repositoryName;

            $items = \explode("}.{", trim($block, "{}"));
            if (($countItems = count($items)) > 1) {
                $selectItemAlias = null;
                foreach ($items as $key => $item) {
                    $defaultPropertyInformation = $defaultEntityClassName::informationForPropertyWithName($item);
                    $ownerStorageName = $this->getStorageNameForProperty($defaultPropertyInformation);
                    $ownerRepositoryName = $defaultPropertyInformation->repositoryName ?: $ownerRepositoryName;
                    $ownerColumnName = $defaultPropertyInformation->columnName;

                    if (($defaultPropertyInformation->relationship) === null OR $key + 1 === count($items)) {
                        break;
                    }

                    $entityClassNameToBeConnected = $defaultPropertyInformation->relationship->entityClassNameToBeConnected;
                    $entityInformationToBeConnected = $entityClassNameToBeConnected::entityInformation();
                    $ownedStorageName = $this->getStorageNameForEntity($entityInformationToBeConnected);
                    $ownedRepositoryName = $entityInformationToBeConnected->repositoryName;
                    $ownedEntityPrimaryColumnName = $defaultEntityClassName::informationForPropertyWithName($defaultEntityInformation->primaryPropertyName)->columnName;
                    $ownerJoinKeyPart = $this->createJoinKeyPart($ownerStorageName, $ownerRepositoryName, $ownerColumnName);
                    $ownedJoinKeyPart = null;

                    if (isset($defaultPropertyInformation->relationship->entityClassNameToBeConnectedInPropertyWithName) AND $defaultPropertyInformation->relationship->entityClassNameToBeConnectedInPropertyWithName)
                        throw new \obo\Exceptions\AutoJoinException("Functionality autojoin can't be used in non-static relationship ONE for property with name '{$defaultPropertyInformation->name}'");

                    if ($defaultPropertyInformation->relationship instanceof \obo\Relationships\One AND ($countItems - 1) !== $key) {
                        $ownedEntityPrimaryColumnName = $entityClassNameToBeConnected::informationForPropertyWithName($entityInformationToBeConnected->primaryPropertyName)->columnName;
                        $propertyNameForSoftDelete = $entityInformationToBeConnected->propertyNameForSoftDelete ? $entityInformationToBeConnected->informationForPropertyWithName($entityInformationToBeConnected->propertyNameForSoftDelete)->columnName : null;
                        $selectItemAlias .= "{$item}_";

                        if ($defaultPropertyInformation->relationship->connectViaProperty AND $defaultPropertyInformation->relationship->ownerNameInProperty) {
                            $foreignKey[0] = $defaultPropertyInformation->relationship->connectViaProperty;
                            $foreignKey[1] = $defaultPropertyInformation->relationship->ownerNameInProperty;
                            $ownerJoinKeyPart = $this->createJoinKeyPart($ownerStorageName, $ownerRepositoryName, $foreignKey[1]);
                            $ownedJoinKeyPart = $this->createJoinKeyPart($ownedStorageName, $ownedRepositoryName, $foreignKey[0]);
                            $joinKeyAlias = $this->createJoinKeyAlias($ownerJoinKeyPart, $ownedJoinKeyPart, static::JOIN_TYPE_LEFT);
                            $joinKey = $this->getJoinKeyByAlias($joinKeyAlias);
                            $join = self::oneInverseDynamicRelationshipJoinQuery(
                                $ownedStorageName,
                                $ownedRepositoryName,
                                $joinKeyAlias,
                                $joinKey,
                                $ownerStorageName,
                                $ownerRepositoryName,
                                $defaultEntityInformation->name,
                                $foreignKey,
                                $ownedEntityPrimaryColumnName,
                                $propertyNameForSoftDelete
                            );
                        } elseif ($defaultPropertyInformation->relationship->connectViaProperty) {
                            $foreignKeyColumnName = $defaultPropertyInformation->relationship->connectViaProperty;
                            $ownedJoinKeyPart = $this->createJoinKeyPart($ownedStorageName, $ownedRepositoryName, $foreignKeyColumnName);
                            $joinKeyAlias = $this->createJoinKeyAlias($ownerJoinKeyPart, $ownedJoinKeyPart, static::JOIN_TYPE_LEFT);
                            $joinKey = $this->getJoinKeyByAlias($joinKeyAlias);
                            $join = self::oneInverseRelationshipJoinQuery(
                                $ownedStorageName,
                                $ownedRepositoryName,
                                $joinKeyAlias,
                                $joinKey,
                                $ownerStorageName,
                                $ownerRepositoryName,
                                $foreignKeyColumnName,
                                $ownedEntityPrimaryColumnName,
                                $propertyNameForSoftDelete
                            );
                        } else {
                            $ownerJoinKeyPart = $this->createJoinKeyPart($ownerStorageName, $ownerRepositoryName, $defaultEntityInformation->propertiesInformation[$defaultPropertyInformation->relationship->ownerPropertyName]->columnName);
                            $ownedJoinKeyPart = $this->createJoinKeyPart($ownedStorageName, $ownedRepositoryName, $ownedEntityPrimaryColumnName);
                            $joinKeyAlias = $this->createJoinKeyAlias($ownerJoinKeyPart, $ownedJoinKeyPart, static::JOIN_TYPE_LEFT);
                            $joinKey = $this->getJoinKeyByAlias($joinKeyAlias);
                            $join = self::oneRelationshipJoinQuery(
                                $ownedStorageName,
                                $ownedRepositoryName,
                                $joinKeyAlias,
                                $joinKey,
                                $ownerStorageName,
                                $ownerRepositoryName,
                                $defaultEntityInformation->propertiesInformation[$defaultPropertyInformation->relationship->ownerPropertyName]->columnName, //$foreignKeyColumnName
                                $ownedEntityPrimaryColumnName,
                                $propertyNameForSoftDelete
                            );
                        }
                    }

                    if ($defaultPropertyInformation->relationship instanceof \obo\Relationships\Many) {
                        $needDistinct = true;
                        $propertyNameForSoftDelete = $entityInformationToBeConnected->propertyNameForSoftDelete ? $entityInformationToBeConnected->informationForPropertyWithName($entityInformationToBeConnected->propertyNameForSoftDelete)->columnName : null;

                        if ($defaultPropertyInformation->relationship->connectViaRepositoryWithName === "") {
                            $ownerColumnName = $entityInformationToBeConnected->propertiesInformation[$defaultPropertyInformation->relationship->connectViaPropertyWithName]->columnName;
                            $ownerJoinKeyPart = $this->createJoinKeyPart($ownerStorageName, $ownerRepositoryName, $ownerColumnName);
                            $ownedJoinKeyPart = $this->createJoinKeyPart($ownedStorageName, $ownedRepositoryName, $ownedEntityPrimaryColumnName);
                            $joinKeyAlias = $this->createJoinKeyAlias($ownerJoinKeyPart, $ownedJoinKeyPart, static::JOIN_TYPE_LEFT);
                            $joinKey = $this->getJoinKeyByAlias($joinKeyAlias);
                            $join = self::manyViaPropertyRelationshipJoinQuery(
                                $ownedStorageName,
                                $ownedRepositoryName,
                                $joinKeyAlias,
                                $joinKey,
                                $ownerStorageName,
                                $ownerRepositoryName,
                                $ownerColumnName,
                                $ownedEntityPrimaryColumnName,
                                $propertyNameForSoftDelete
                            );

                            if ($defaultPropertyInformation->relationship->ownerNameInProperty !== "") {
                                $join .= self::manyViaPropertyRelationshipExtendsJoinQuery(
                                    $joinKeyAlias,
                                    $joinKey,
                                    $defaultPropertyInformation->relationship->ownerNameInProperty, //$ownerNameInPropertyWithName
                                    $defaultPropertyInformation->entityInformation->name //$ownerName
                                );
                            }

                        } elseif ($defaultPropertyInformation->relationship->connectViaPropertyWithName === "") {
                            $ownerEntityPrimaryColumnName = $defaultEntityClassName::informationForPropertyWithName($defaultEntityInformation->primaryPropertyName)->columnName;
                            $ownedEntityPrimaryColumnName = $entityClassNameToBeConnected::informationForPropertyWithName($entityInformationToBeConnected->primaryPropertyName)->columnName;
                            $connectViaRepositoryWithName = $defaultPropertyInformation->relationship->connectViaRepositoryWithName;
                            $connectViaRepositoryStorageName = $this->extractStorageName($connectViaRepositoryWithName);
                            $connectViaRepositoryRepositoryName = $this->extractRepositoryName($connectViaRepositoryWithName);
                            $connectViaRepositoryEntityJoinKeyPartForOwnerEntity = $this->createJoinKeyPart($connectViaRepositoryStorageName, $connectViaRepositoryRepositoryName, $ownerRepositoryName);
                            $connectViaRepositoryEntityJoinKeyPartForOwnedEntity = $this->createJoinKeyPart($connectViaRepositoryStorageName, $connectViaRepositoryRepositoryName, $ownedRepositoryName);
                            $ownerJoinKeyPart = $this->createJoinKeyPart($ownerStorageName, $ownerRepositoryName, $ownerEntityPrimaryColumnName);
                            $ownedJoinKeyPart = $this->createJoinKeyPart($ownedStorageName, $ownedRepositoryName, $ownedEntityPrimaryColumnName);
                            $joinKeyAliasForConnectRepositoryAndOwnedEntity = $this->createJoinKeyAlias($connectViaRepositoryEntityJoinKeyPartForOwnedEntity, $ownedJoinKeyPart, static::JOIN_TYPE_LEFT);
                            $joinKeyAliasForConnectRepositoryAndOwnerEntity = $this->createJoinKeyAlias($connectViaRepositoryEntityJoinKeyPartForOwnerEntity, $ownerJoinKeyPart, static::JOIN_TYPE_LEFT);
                            $joinKeyForConnectRepositoryAndOwnedEntity = $this->getJoinKeyByAlias($joinKeyAliasForConnectRepositoryAndOwnedEntity);
                            $joinKeyForConnectRepositoryAndOwnerEntity = $this->getJoinKeyByAlias($joinKeyAliasForConnectRepositoryAndOwnerEntity);
                            $joinKeyAlias = $this->createJoinKeyAlias($joinKeyForConnectRepositoryAndOwnedEntity, $joinKeyForConnectRepositoryAndOwnerEntity, static::JOIN_TYPE_LEFT);
                            $joinKey = $this->getJoinKeyByAlias($joinKeyAlias);

                            $join = self::manyViaRepositoryRelationshipJoinQuery(
                                $joinKeyAlias,
                                $joinKey,
                                $defaultPropertyInformation->relationship->connectViaRepositoryWithName, //$connectViaRepositoryWithName
                                $ownerStorageName,
                                $ownerRepositoryName,
                                $ownedStorageName,
                                $ownedRepositoryName,
                                $ownerEntityPrimaryColumnName,
                                $ownedEntityPrimaryColumnName,
                                $propertyNameForSoftDelete
                            );
                        }
                    }

                    $defaultEntityClassName = $entityClassNameToBeConnected;
                    $defaultEntityInformation = $defaultEntityClassName::entityInformation();
                    $ownerRepositoryName = $joinKeyAlias;
                    $joins[$joinKeyAlias] = $join;
                }
            } else {
                $defaultPropertyInformation = $defaultEntityClassName::informationForPropertyWithName($items[0]);
            }

            if ($defaultPropertyInformation->repositoryName) {
                $ownerRepositoryName = $defaultPropertyInformation->repositoryName;
            }

            $ownerStorageName = $this->getStorageNameForProperty($defaultPropertyInformation);

            $matches = [];
            \preg_match("#\{([^\ ]*?\}\.\{[^\ ]*?)*[^\{\}]*?\}[^\{]*?([^\{]*)#", $part["query"], $matches);
            if (isset($matches[2]) AND \strpos($matches[2], $this->parameterPlaceholder) !== false) {
                $segment = \preg_replace("#(\{(.*?)\}\.?)+#", "[{$ownerStorageName}].[{$ownerRepositoryName}].[{$defaultPropertyInformation->columnName}]", $matches[0], 1);
                if (isset($this->informationForEntity($defaultPropertyInformation->entityInformation)["storages"][$ownerStorageName]["repositories"][$ownerRepositoryName])) {
                    $segment = \str_replace($this->parameterPlaceholder, $this->informationForEntity($defaultPropertyInformation->entityInformation)["storages"][$ownerStorageName]["repositories"][$ownerRepositoryName]["columns"][$defaultPropertyInformation->columnName]["placeholder"], $segment);
                }
                $part["query"] = \str_replace($matches[0], $segment, $part["query"]);
            } else {
                $part["query"] = \preg_replace("#(\{(.*?)\}\.?)+#", "[{$ownerStorageName}].[{$ownerRepositoryName}].[{$defaultPropertyInformation->columnName}]". ($type === self::PROCESS_SELECT ? " AS [{$selectItemAlias}{$defaultPropertyInformation->name}]" : ""), $part["query"], 1);
            }
        }

        return $needDistinct;
    }

    /**
     * @param string $query
     * @param array $joins
     * @param string $defaultEntityClassName
     * @param int $type
     * @return void
     */
    protected function processJunctions(&$query, array &$joins, $defaultEntityClassName, $type) {
        $defaultEntityInformation = $defaultEntityClassName::entityInformation();
        $defaultEntityInformationOboName = $defaultEntityInformation->name;
        $defaultEntityStorageName = $this->getStorageNameForEntity($defaultEntityInformation);
        $defaultEntityRepositoryName = $defaultEntityInformation->repositoryName;
        $defaultEntityPrimaryPropertyInformation = $defaultEntityInformation->informationForPropertyWithName($defaultEntityInformation->primaryPropertyName);
        $defaultEntityPrimaryPropertyColumnName = $defaultEntityPrimaryPropertyInformation->columnName;
        $defaultEntityJoinPart = $this->createJoinKeyPart($defaultEntityStorageName, $defaultEntityRepositoryName, $defaultEntityPrimaryPropertyInformation->columnName);

        if (\preg_match_all("#(\{\*([A-Za-z0-9_\.\-]+?\,[A-Za-z0-9\\\_]+?)\*\})\ *?=\ *?(" . \preg_quote(\obo\Interfaces\IQuerySpecification::PARAMETER_PLACEHOLDER) . ")#", $query, $blocks)) {
            foreach ($blocks[0] as $key => $block) {
                $parts = \explode(",", $blocks[2][$key]);
                $connectedEntityClassName = $parts[1];
                $connectedEntityInformation = $connectedEntityClassName::entityInformation();
                $connectedEntityStorageName = $this->getStorageNameForEntity($connectedEntityInformation);
                $connectedEntityPrimaryPropertyInformation = $connectedEntityInformation->informationForPropertyWithName($connectedEntityInformation->primaryPropertyName);
                $connectedEntityPrimaryPropertyStorageName = $this->getStorageNameForProperty($connectedEntityPrimaryPropertyInformation);

                $parts0 = str_replace(".", "_", $parts[0]);
                $connectedEntityJoinPart = $this->createJoinKeyPart($connectedEntityStorageName, $connectedEntityInformation->repositoryName, $connectedEntityPrimaryPropertyInformation->columnName);
                $joinKeyAlias = $this->createJoinKeyAlias($defaultEntityJoinPart, $connectedEntityJoinPart, static::JOIN_TYPE_INNER);
                $joinKey = $this->getJoinKeyByAlias($joinKeyAlias);
                $joins[$joinKeyAlias] = " INNER JOIN [{$parts[0]}] AS [{$joinKeyAlias}] ON [{$joinKeyAlias}].[{$defaultEntityRepositoryName}] = [{$defaultEntityStorageName}].[{$defaultEntityRepositoryName}].[{$defaultEntityPrimaryPropertyInformation->columnName}]";
                $newBlock = \str_replace($blocks[1][$key], "[{$joinKeyAlias}].[{$connectedEntityInformation->repositoryName}]", $block);
                $newBlock = \str_replace(
                    $blocks[3][$key],
                    $this->informationForEntity($connectedEntityInformation)["storages"][$connectedEntityPrimaryPropertyStorageName]["repositories"][$connectedEntityInformation->repositoryName]["columns"][$connectedEntityPrimaryPropertyInformation->columnName]["placeholder"],
                    $newBlock
                );
                $query = \str_replace($block, $newBlock, $query);
            }
        }

        if ($type === self::PROCESS_WHERE) {
            foreach ($defaultEntityInformation->propertiesInformation as $propertyInformation) {
                $propertyStorageName = $this->getStorageNameForProperty($propertyInformation);
                $propertyRepositoryName = (!empty($propertyInformation->repositoryName)) ? $propertyInformation->repositoryName : $propertyInformation->entityInformation->repositoryName;
                if (($propertyStorageName === $defaultEntityStorageName) && ($defaultEntityRepositoryName === $propertyRepositoryName)) {
                    continue;
                }
                $connectedEntityJoinPart = $this->createJoinKeyPart($propertyStorageName, $propertyInformation->repositoryName, $defaultEntityPrimaryPropertyColumnName);
                $joinKeyAlias = $this->createJoinKeyAlias($defaultEntityJoinPart, $connectedEntityJoinPart, $propertyInformation->declaringClassOboNameSameAsParent ? static::JOIN_TYPE_INNER : static::JOIN_TYPE_INNER);
                $joinKey = $this->getJoinKeyByAlias($joinKeyAlias);
                $joinType = ($propertyInformation->declaringClassOboNameSameAsParent) ? "LEFT" : "INNER";
                $joins = [$joinKeyAlias => " {$joinType} JOIN [{$propertyStorageName}].[{$propertyRepositoryName}] ON [{$propertyStorageName}].[{$propertyRepositoryName}].[{$defaultEntityPrimaryPropertyColumnName}] = [{$defaultEntityStorageName}].[{$defaultEntityRepositoryName}].[{$defaultEntityPrimaryPropertyColumnName}]"] + $joins;
            }
        }
    }

    /**
     * @param string $ownedStorageName
     * @param string $ownedRepositoryName
     * @param string $joinKeyAlias
     * @param string $joinKey
     * @param string $ownerStorageName
     * @param string $ownerRepositoryName
     * @param string $foreignKeyColumnName
     * @param string $ownedEntityPrimaryColumnName
     * @param string $columnNameForSoftDelete
     * @return string
     */
    protected static function oneRelationshipJoinQuery($ownedStorageName, $ownedRepositoryName, $joinKeyAlias, $joinKey, $ownerStorageName, $ownerRepositoryName, $foreignKeyColumnName, $ownedEntityPrimaryColumnName, $columnNameForSoftDelete) {
        $softDeleteClause = $columnNameForSoftDelete ? " AND [{$joinKeyAlias}].[{$columnNameForSoftDelete}] = 0" : "";
        return "LEFT JOIN [{$ownedStorageName}].[{$ownedRepositoryName}] AS [{$joinKeyAlias}] ON [{$ownerStorageName}].[{$ownerRepositoryName}].[{$foreignKeyColumnName}] = [{$joinKeyAlias}].[{$ownedEntityPrimaryColumnName}]{$softDeleteClause}" . static::createComment($joinKeyAlias . " => " . $joinKey);
    }

    /**
     * @param string $ownedStorageName
     * @param string $ownedRepositoryName
     * @param string $joinKeyAlias
     * @param string $joinKey
     * @param string $ownerStorageName
     * @param string $ownerRepositoryName
     * @param string $foreignKeyColumnName
     * @param string $ownedEntityPrimaryColumnName
     * @param string $columnNameForSoftDelete
     * @return string
     */
    protected static function oneInverseRelationshipJoinQuery($ownedStorageName, $ownedRepositoryName, $joinKeyAlias, $joinKey, $ownerStorageName, $ownerRepositoryName, $foreignKeyColumnName, $ownedEntityPrimaryColumnName, $columnNameForSoftDelete) {
        $softDeleteClause = $columnNameForSoftDelete ? " AND [{$joinKeyAlias}].[{$columnNameForSoftDelete}] = 0" : "";
        return "LEFT JOIN [{$ownedStorageName}].[{$ownedRepositoryName}] AS [{$joinKeyAlias}] ON [{$joinKeyAlias}] .[{$foreignKeyColumnName}] = [{$ownerStorageName}].[{$ownerRepositoryName}].[{$ownedEntityPrimaryColumnName}] {$softDeleteClause}" . static::createComment($joinKey);;
    }

    /**
     * @param string $ownedStorageName
     * @param string $ownedRepositoryName
     * @param string $joinKeyAlias
     * @param string $joinKey
     * @param string $ownerStorageName
     * @param string $ownerRepositoryName
     * @param string $ownerName
     * @param string $foreignKey
     * @param string $ownedEntityPrimaryColumnName
     * @param string $columnNameForSoftDelete
     * @return string
     */
    protected static function oneInverseDynamicRelationshipJoinQuery($ownedStorageName, $ownedRepositoryName, $joinKeyAlias, $joinKey, $ownerStorageName, $ownerRepositoryName, $ownerName, $foreignKey, $ownedEntityPrimaryColumnName, $columnNameForSoftDelete) {
        $softDeleteClause = $columnNameForSoftDelete ? " AND [{$joinKeyAlias}].[{$columnNameForSoftDelete}] = 0" : "";
        return "LEFT JOIN [{$ownedStorageName}].[{$ownedRepositoryName}] AS [{$joinKeyAlias}] ON [{$joinKeyAlias}].[{$foreignKey[0]}] = [{$ownerStorageName}].[{$ownerRepositoryName}].[{$ownedEntityPrimaryColumnName}] AND [{$joinKeyAlias}].[{$foreignKey[1]}] = '{$ownerName}'  {$softDeleteClause}" . static::createComment($joinKey);;
    }

    /**
     * @param string $ownedStorageName
     * @param string $ownedRepositoryName
     * @param string $joinKeyAlias
     * @param string $joinKey
     * @param string $ownerStorageName
     * @param string $ownerRepositoryName
     * @param string $foreignKeyColumnName
     * @param string $ownedEntityPrimaryColumnName
     * @param string $columnNameForSoftDelete
     * @return string
     */
    protected static function manyViaPropertyRelationshipJoinQuery($ownedStorageName, $ownedRepositoryName, $joinKeyAlias, $joinKey, $ownerStorageName, $ownerRepositoryName, $foreignKeyColumnName, $ownedEntityPrimaryColumnName, $columnNameForSoftDelete) {
        $softDeleteClause = $columnNameForSoftDelete ? " AND [{$joinKeyAlias}].[{$columnNameForSoftDelete}] = 0" : "";
        return "LEFT JOIN [{$ownedStorageName}].[{$ownedRepositoryName}] AS [{$joinKeyAlias}] ON [{$joinKeyAlias}].[{$foreignKeyColumnName}] = [{$ownerStorageName}].[{$ownerRepositoryName}].[{$ownedEntityPrimaryColumnName}]{$softDeleteClause}" . static::createComment($joinKey);
    }

    /**
     * @param string $joinKeyAlias
     * @param string $joinKey
     * @param string $ownerNameInPropertyWithName
     * @param string $ownerClassName
     * @return string
     */
    protected static function manyViaPropertyRelationshipExtendsJoinQuery($joinKeyAlias, $joinKey, $ownerNameInPropertyWithName, $ownerClassName) {
        return " AND [{$joinKeyAlias}].[{$ownerNameInPropertyWithName}] = '{$ownerClassName}'" . static::createComment($joinKey);
    }

    /**
     * @param string $joinKeyAlias
     * @param string $joinKey
     * @param string $connectViaRepositoryWithName
     * @param string $ownerStorageName
     * @param string $ownerRepositoryName
     * @param string $ownedStorageName
     * @param string $ownedRepositoryName
     * @param string $ownerPrimaryPropertyColumnName
     * @param string $ownedPrimaryPropertyColumnName
     * @param string $columnNameForSoftDelete
     * @return string
     */
    protected static function manyViaRepositoryRelationshipJoinQuery($joinKeyAlias, $joinKey, $connectViaRepositoryWithName, $ownerStorageName, $ownerRepositoryName, $ownedStorageName, $ownedRepositoryName, $ownerPrimaryPropertyColumnName, $ownedPrimaryPropertyColumnName, $columnNameForSoftDelete) {
        $softDeleteClause = $columnNameForSoftDelete ? " AND [{$joinKeyAlias}].[{$columnNameForSoftDelete}] = 0" : "";
        return "LEFT JOIN [{$connectViaRepositoryWithName}]
                ON [{$connectViaRepositoryWithName}].[{$ownerRepositoryName}]
                = [{$ownerStorageName}].[{$ownerRepositoryName}].[{$ownerPrimaryPropertyColumnName}]
                LEFT JOIN [{$ownedStorageName}].[{$ownedRepositoryName}] AS [{$joinKeyAlias}]
                ON [{$connectViaRepositoryWithName}].[{$ownedRepositoryName}]
                = [{$joinKeyAlias}].[{$ownedPrimaryPropertyColumnName}]{$softDeleteClause}" . static::createComment($joinKeyAlias . " => " . $joinKey);
    }

}
