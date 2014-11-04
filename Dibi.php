<?php

/**
 * This file is part of framework Obo Development version (http://www.obophp.org/)
 * @link http://www.obophp.org/
 * @author Adam Suba, http://www.adamsuba.cz/
 * @copyright (c) 2011 - 2014 Adam Suba
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

namespace obo\DataStorage;

class Dibi extends \obo\Object implements \obo\Interfaces\IDataStorage {

    protected $dibiConnection = null;

    /**
     * @param \DibiConnection $dibiConnection
     */
    public function __construct(\DibiConnection $dibiConnection) {
        $this->dibiConnection = $dibiConnection;
    }

    /**
     * @param string $repositoryName
     * @return boolean
     */
    public function existsRepositoryWithName($repositoryName) {
        return (boolean) $this->dibiConnection->fetchSingle($query = "SHOW TABLES LIKE '{$repositoryName}';");
    }

    /**
     * @param string $repositoryName
     * @return array
     */
    public function columnsInRepositoryWithName($repositoryName) {
        $query = "SHOW COLUMNS FROM [{$repositoryName}];";
        $tableColumns = array();
        foreach ($this->dibiConnection->fetchAll($query) as $column) $tableColumns[$column->Field] = $column->Type;
        return $tableColumns;
    }

    /**
     * @param \obo\Carriers\QueryCarrier $queryCarrier
	 * @param boolean $asArray
     * @return string
     */
    public function constructQuery(\obo\Carriers\QueryCarrier $queryCarrier, $asArray = false) {
        $query = "";
        $data = [];
        $queryCarrier = clone $queryCarrier;

        if (!is_null($queryCarrier->getDefaultEntityClassName())) {
            $joins = array();

            $select = $queryCarrier->getSelect();
            $where = $queryCarrier->getWhere();
            $orderBy = $queryCarrier->getOrderBy();
            $join = $queryCarrier->getJoin();

            $this->convert($queryCarrier->getDefaultEntityClassName(), $select, $joins);
            $this->convert($queryCarrier->getDefaultEntityClassName(), $where, $joins);
            $this->convert($queryCarrier->getDefaultEntityClassName(), $orderBy, $joins);
            $this->convert($queryCarrier->getDefaultEntityClassName(), $join, $joins);

            $queryCarrier->join($joins);
            $join = $queryCarrier->getJoin();
        }


        $query.= "SELECT " . rtrim($select["query"],",");
        $data = \array_merge($data, $select["data"]);

        if ($queryCarrier->getFrom()["query"] === "") {
            $defaultEntityClassName = $queryCarrier->getDefaultEntityClassName();
            $query.= " FROM [".$defaultEntityClassName::entityInformation()->repositoryName."]";
        } else {
            $query.= " FROM " . rtrim($queryCarrier->getFrom()["query"],",");
            $data = \array_merge($data, $queryCarrier->getFrom()["data"]);
        }

        $query.= rtrim($join["query"], ",");
        $data = \array_merge($data, $join["data"]);

        if ($where["query"] !== "") {
            $query.= " WHERE " . \preg_replace("#^ *(AND|OR) *#i", "", $where["query"]);
            $data = \array_merge($data, $where["data"]);
        }

        if ($orderBy["query"] !== "") {
            $query.= " ORDER BY " . rtrim($orderBy["query"], ",");
            $data = \array_merge($data, $orderBy["data"]);
        }

        if ($queryCarrier->getLimit()["query"] !== "") {
            $query.= " LIMIT " . $queryCarrier->getLimit()["query"];
            $data = \array_merge($data, $queryCarrier->getLimit()["data"]);
        }

        if ($queryCarrier->getOffset()["query"] !== "") {
            $query.= " OFFSET " . $queryCarrier->getOffset()["query"];
            $data = \array_merge($data, $queryCarrier->getOffset()["data"]);
        }

        if ($asArray) return \array_merge(array($query), $data);
        return (new \DibiTranslator($this->dibiConnection))->translate(\array_merge(array($query), $data));
    }

    /**
     * @param \obo\Entity $entity
     * @return array
     */
    public function dataForEntity(\obo\Entity $entity) {
        $tableName = $entity->entityInformation()->repositoryName;
        $primaryPropertyName = $entity->entityInformation()->primaryPropertyName;
        $primaryPropertyColumnName = $entity->informationForPropertyWithName($primaryPropertyName)->columnName;
        $query = "SELECT * FROM [{$tableName}] WHERE [{$tableName}].[{$primaryPropertyColumnName}] = %i LIMIT 1";
        $data = $this->dibiConnection->fetchAll($query, $entity->primaryPropertyValue());
        return isset($data[0]) ? (array) $data[0] : array();
    }

    /**
     * @param \obo\Carriers\QueryCarrier $queryCarrier
     * @return array
     */
    public function dataFromQuery(\obo\Carriers\QueryCarrier $queryCarrier) {
        return $this->dibiConnection->fetchAll($this->constructQuery($queryCarrier, true));
    }

    /**
     * @param \obo\Carriers\QueryCarrier $queryCarrier
     * @param string $primaryPropertyName
     * @return int
     */
    public function countRecordsForQuery(\obo\Carriers\QueryCarrier $queryCarrier, $primaryPropertyName) {
        $queryCarrier->select("COUNT(DISTINCT {{$primaryPropertyName}})");
        return (int) $this->dibiConnection->fetchSingle($this->constructQuery($queryCarrier, true));
    }

    /**
     * @param \obo\Entity $entity
     * @return void
     */
    public function insertEntity(\obo\Entity $entity) {
        if ($entity->isBasedInRepository()) {
            $this->updateEntity($entity);
        } else {
            $this->dibiConnection->query("INSERT INTO [{$entity->entityInformation()->repositoryName}] ", $entity->entityInformation()->propertiesNamesToColumnsNames($entity->dataWhoNeedToStore($entity->entityInformation()->columnsNamesToPropertiesNames($entity->entityInformation()->repositoryColumns))));
            $entity->setValueForPropertyWithName($this->dibiConnection->getInsertId(), $entity->entityInformation()->primaryPropertyName);
        }
    }

    /**
     * @param \obo\Entity $entity
     * @return void
     */
    public function updateEntity(\obo\Entity $entity) {
        $primaryPropertyName = $entity->entityInformation()->primaryPropertyName;
        $primaryPropertyColumnName = $entity->informationForPropertyWithName($primaryPropertyName)->columnName;
        $this->dibiConnection->query("UPDATE [{$entity->entityInformation()->repositoryName}] SET %a", $entity->entityInformation()->propertiesNamesToColumnsNames($entity->dataWhoNeedToStore($entity->entityInformation()->columnsNamesToPropertiesNames($entity->entityInformation()->repositoryColumns))), "WHERE [{$entity->entityInformation()->repositoryName}].[{$primaryPropertyColumnName}] = %i", $entity->primaryPropertyValue());
    }

    /**
     * @param \obo\Entity $entity
     * @return void
     */
    public function removeEntity(\obo\Entity $entity) {
        $primaryPropertyColumnName = $entity->informationForPropertyWithName($entity->entityInformation()->primaryPropertyName)->columnName;
        $this->dibiConnection->query("DELETE FROM [{$entity->entityInformation()->repositoryName}] WHERE [{$entity->entityInformation()->repositoryName}].[{$primaryPropertyColumnName}] = %i LIMIT 1", $entity->primaryPropertyValue());
    }

    /**
     * @param string $repositoryName
     * @param \obo\Entity[]
     * @throws \obo\Exceptions\Exception
     * @return void
     */
    public function createRelationshipBetweenEntities($repositoryName, array $entities) {

        if (\obo\obo::$developerMode) {
            if (!$this->existsRepositoryWithName($repositoryName)) throw new \obo\Exceptions\Exception("Relationship can not be created. Repository with the name '{$repositoryName}' does not exist.");
            if (\count($entities) !== 2) throw new \obo\Exceptions\Exception("Relationship can not be created. Two entities were expected but " . \count($entities) . " given.");

            foreach ($entities as $entity) {
                if (!$entity instanceof \obo\Entity) throw new \obo\Exceptions\Exception("Relationship can not be created. Entities must be of \obo\Entity instance");
            }
        }

        $this->dibiConnection->query("INSERT INTO [{$repositoryName}] ", [$entities[0]->entityInformation()->repositoryName => $entities[0]->primaryPropertyValue(), $entities[1]->entityInformation()->repositoryName => $entities[1]->primaryPropertyValue()]);
    }

    /**
     * @param string $repositoryName
     * @param array $entities
     * @throws \obo\Exceptions\Exception
     * @return void
     */
    public function removeRelationshipBetweenEntities($repositoryName, array $entities) {

        if (\obo\obo::$developerMode) {
            if (!$this->existsRepositoryWithName($repositoryName)) throw new \obo\Exceptions\Exception("Relationship can not deleted repository with the name '{$repositoryName}' does not exist");
            if (\count($entities) !== 2) throw new \obo\Exceptions\Exception("Relationship can not be deleted. Two entities were expected but " . \count($entities) . " given. ");

            foreach ($entities as $entity) {
                if (!$entity instanceof \obo\Entity) throw new \obo\Exceptions\Exception("Relationship can not be deleted. Entities must be of \obo\Entity instance.");
            }
        }

        $this->dibiConnection->query("DELETE FROM [{$repositoryName}] WHERE [{$entities[0]->entityInformation()->repositoryName}] = {$entities[0]->primaryPropertyValue()} AND [{$entities[1]->entityInformation()->repositoryName}] = {$entities[1]->primaryPropertyValue()}");

    }

    /**
     * @param string $columnName
     * @param \obo\Entity $entity
     * @return boolean
     */
    protected function existRepositoryColumnWithNameForEntity($columnName, \obo\Entity $entity) {
        if (!$this->existRepositoryForEntity($entity->entityInformation())) return false;
        $tableColumn = self::columnsInRepositoryForEntity($entity);
        return isset($tableColumn[$columnName]);
    }


    /**
     * @param string $defaultEntityClassName
     * @param array $part
     * @param array $joins
     * @throws \obo\Exceptions\AutoJoinException
     * @return void
     */
    protected function convert($defaultEntityClassName, array &$part, array &$joins) {
        $originalDefaultEntityClassName = $defaultEntityClassName;
        \preg_match_all("#(\{(.*?)\}\.?)+#", $part["query"], $blocks);
        foreach ($blocks[0] as $block) {
            $defaultEntityClassName = $originalDefaultEntityClassName;
            $joinKey = null;
            $ownerRepositoryName = $defaultEntityClassName::entityInformation()->repositoryName;
            $items = \explode("}.{", trim($block, "{}"));

            if (count($items) > 1) {
                foreach ($items as $item) {
                    $defaultPropertyInformation = $defaultEntityClassName::informationForPropertyWithName($item);
                    if (\is_null(($defaultPropertyInformation->relationship))) break;

                    if (isset($defaultPropertyInformation->relationship->entityClassNameToBeConnectedInPropertyWithName)
                            AND $defaultPropertyInformation->relationship->entityClassNameToBeConnectedInPropertyWithName)
                        throw new \obo\Exceptions\AutoJoinException("Functionality autojoin can not be used in non-static relationship ONE for property with name '{$defaultPropertyInformation->name}'");

                    $defaultEntityInformation = $defaultEntityClassName::entityInformation();
                    $entityClassNameToBeConnected = $defaultPropertyInformation->relationship->entityClassNameToBeConnected;
                    $joinKey = "{$defaultEntityClassName}->{$entityClassNameToBeConnected}";
                    $entityToBeConnectedInformation = $entityClassNameToBeConnected::entityInformation();

                    if ($defaultPropertyInformation->relationship instanceof \obo\Relationships\One) {

                        $join = self::oneRelationshipJoinQuery(
                                    $entityToBeConnectedInformation->repositoryName,//$ownedRepositoryName
                                    $joinKey,//$joinKey
                                    $ownerRepositoryName,//$ownerRepositoryName
                                    $defaultEntityInformation->propertiesInformation[$defaultPropertyInformation->relationship->ownerPropertyName]->columnName,//$foreignKeyColumnName
                                    $entityClassNameToBeConnected::informationForPropertyWithName($entityToBeConnectedInformation->primaryPropertyName)->columnName,//$ownedEntityPrimaryColumnName
                                    $entityToBeConnectedInformation->propertyNameForSoftDelete ? $entityToBeConnectedInformation->informationForPropertyWithName($entityToBeConnectedInformation->propertyNameForSoftDelete)->columnName : null//$propertyNameForSoftDelete
                                );

                    } elseif ($defaultPropertyInformation->relationship instanceof \obo\Relationships\Many) {

                        if (\is_null($defaultPropertyInformation->relationship->connectViaRepositoryWithName)) {

                            $join = self::manyViaPropertyRelationshipJoinQuery(
                                        $entityToBeConnectedInformation->repositoryName,//$ownedRepositoryName
                                        $joinKey,//$joinKey
                                        $ownerRepositoryName,//$ownerRepositoryName
                                        $entityToBeConnectedInformation->propertiesInformation[$defaultPropertyInformation->relationship->connectViaPropertyWithName]->columnName,//$foreignKeyColumnName
                                        $defaultEntityClassName::informationForPropertyWithName($defaultEntityInformation->primaryPropertyName)->columnName,//$ownedEntityPrimaryColumnName
                                        $entityToBeConnectedInformation->propertyNameForSoftDelete ? $entityToBeConnectedInformation->informationForPropertyWithName($entityToBeConnectedInformation->propertyNameForSoftDelete)->columnName : null//$propertyNameForSoftDelete
                                    );

                            if (!\is_null($defaultPropertyInformation->relationship->ownerNameInProperty)) {
                                $join .= self::manyViaPropertyRelationshipExtendsJoinQuery(
                                            $joinKey,//$joinKey
                                            $defaultPropertyInformation->relationship->ownerNameInProperty,//$ownerNameInPropertyWithName
                                            $defaultPropertyInformation->entityInformation->className//$ownerClassName
                                        );
                            }

                        } elseif (\is_null($defaultPropertyInformation->relationship->connectViaPropertyWithName)) {
                            $join = self::manyViaRepostioryRelationshipJoinQuery(
                                        $joinKey,//$joinKey
                                        $defaultPropertyInformation->relationship->connectViaRepositoryWithName,//$connectViaRepositoryWithName
                                        $ownerRepositoryName,//$ownerRepositoryName
                                        $entityToBeConnectedInformation->repositoryName,//$ownedRepositoryName
                                        $defaultEntityClassName::informationForPropertyWithName($defaultEntityInformation->primaryPropertyName)->columnName,//$ownerPrimaryPropertyColumnName
                                        $entityClassNameToBeConnected::informationForPropertyWithName($entityToBeConnectedInformation->primaryPropertyName)->columnName,//$ownedPrimaryPropertyColumnName
                                        $entityToBeConnectedInformation->propertyNameForSoftDelete ? $entityToBeConnectedInformation->informationForPropertyWithName($entityToBeConnectedInformation->propertyNameForSoftDelete)->columnName : null//$propertyNameForSoftDelete
                                    );
                        }
                    }

                    $defaultEntityClassName = $entityClassNameToBeConnected;
                    $ownerRepositoryName = $joinKey;
                    $joins[$joinKey] = $join;
                }
            } else {
                $defaultPropertyInformation = $defaultEntityClassName::informationForPropertyWithName($items[0]);
            }

            $part["query"] = \preg_replace("#(\{(.*?)\}\.?)+#", "[{$ownerRepositoryName}].[{$defaultPropertyInformation->columnName}]", $part["query"], 1);
        }

    }

    /**
     * @param string $ownedRepositoryName
     * @param string $joinKey
     * @param string $ownerRepositoryName
     * @param string $foreignKeyColumnName
     * @param string $ownedEntityPrimaryColumnName
     * @param string $columnNameForSoftDelete
     * @return string
     */
    protected static function oneRelationshipJoinQuery($ownedRepositoryName, $joinKey, $ownerRepositoryName, $foreignKeyColumnName, $ownedEntityPrimaryColumnName, $columnNameForSoftDelete) {
        $softDeleteClausule = $columnNameForSoftDelete ? " AND [{$joinKey}].[{$columnNameForSoftDelete}] = 0" : "";
        return "LEFT JOIN [{$ownedRepositoryName}] as [{$joinKey}] ON [{$ownerRepositoryName}].[{$foreignKeyColumnName}] = [{$joinKey}].[{$ownedEntityPrimaryColumnName}]{$softDeleteClausule}";
    }

    /**
     * @param string $ownedRepositoryName
     * @param string $joinKey
     * @param string $ownerRepositoryName
     * @param string $foreignKeyColumnName
     * @param string $ownedEntityPrimaryColumnName
     * @param string $columnNameForSoftDelete
     * @return string
     */
    protected static function manyViaPropertyRelationshipJoinQuery($ownedRepositoryName, $joinKey, $ownerRepositoryName, $foreignKeyColumnName, $ownedEntityPrimaryColumnName, $columnNameForSoftDelete) {
        $softDeleteClausule = $columnNameForSoftDelete ? " AND [{$joinKey}].[{$columnNameForSoftDelete}] = 0" : "";
        return "LEFT JOIN [{$ownedRepositoryName}] as [{$joinKey}] ON [{$joinKey}].[{$foreignKeyColumnName}] = [{$ownerRepositoryName}].[{$ownedEntityPrimaryColumnName}]{$softDeleteClausule}";
    }

    /**
     * @param string $joinKey
     * @param string $ownerNameInPropertyWithName
     * @param string $ownerClassName
     * @return string
     */
    protected static function manyViaPropertyRelationshipExtendsJoinQuery($joinKey, $ownerNameInPropertyWithName, $ownerClassName) {
        return " AND [{$joinKey}].[{$ownerNameInPropertyWithName}] = '{$ownerClassName}'";
    }

    /**
     * @param string $joinKey
     * @param string $connectViaRepositoryWithName
     * @param string $ownerRepositoryName
     * @param string $ownedRepositoryName
     * @param string $ownerPrimaryPropertyColumnName
     * @param string $ownedPrimaryPropertyColumnName
     * @param string $columnNameForSoftDelete
     * @return string
     */
    protected static function manyViaRepostioryRelationshipJoinQuery($joinKey, $connectViaRepositoryWithName, $ownerRepositoryName, $ownedRepositoryName, $ownerPrimaryPropertyColumnName, $ownedPrimaryPropertyColumnName, $columnNameForSoftDelete) {
        $softDeleteClausule = $columnNameForSoftDelete ? " AND [{$joinKey}].[{$columnNameForSoftDelete}] = 0" : "";
        return "LEFT JOIN [{$connectViaRepositoryWithName}]
                ON [{$connectViaRepositoryWithName}].[{$ownerRepositoryName}]
                = [{$ownerRepositoryName}].[{$ownerPrimaryPropertyColumnName}]
                LEFT JOIN [{$ownedRepositoryName}] as [{$joinKey}]
                ON [{$connectViaRepositoryWithName}].[{$ownedRepositoryName}]
                = [{$joinKey}].[{$ownedPrimaryPropertyColumnName}]{$softDeleteClausule}";
    }

}