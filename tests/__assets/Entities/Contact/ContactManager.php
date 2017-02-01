<?php

namespace obo\DataStorage\Tests\Assets\Entities;

class ContactManager extends \obo\EntityManager {

    public static function constructSelect() {
        return parent::constructSelect();
    }

    /**
     * @param int|array $specification
     * @return \obo\DataStorage\Tests\Assets\Entities\Contact
     * @throws \obo\Exceptions\EntityNotFoundException
     */
    public static function contact($specification) {
        return parent::entity($specification);
    }

    /**
     * @return \obo\DataStorage\Tests\Assets\Entities\Contact[]
     */
    public static function contactsAsCollection() {
        $specification = \obo\Carriers\QuerySpecification::instance();
        $street = "Street";
        $specification->where("AND {otherAddresses}.{street} LIKE ?", "%{$street}%");
        return parent::findEntitiesAsCollection($specification);
    }

    /**
     * @param array $data
     * @return \obo\DataStorage\Tests\Assets\Entities\Contact
     */
    public static function entityFromArray($data, $loadOriginalData = false, $overwriteOriginalData = true) {
        return parent::entityFromArray($data, $loadOriginalData, $overwriteOriginalData);
    }

    /**
     * @return \obo\Carriers\QuerySpecification
     */
    public static function getQuerySpecification() {
        return \obo\Carriers\QueryCarrier::instance();
    }

}
