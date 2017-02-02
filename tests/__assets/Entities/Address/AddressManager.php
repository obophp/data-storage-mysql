<?php

namespace obo\DataStorage\Tests\Assets\Entities;

class AddressManager extends \obo\EntityManager {

    public static function constructQuery() {
        return parent::constructQuery();
    }

    /**
     * @param int|array $specification
     * @return \obo\DataStorage\Tests\Assets\Entities\Address
     * @throws \obo\Exceptions\EntityNotFoundException
     */
    public static function address($specification) {
        return parent::entity($specification);
    }

    /**
     * @param array $data
     * @return Contact
     */
    public static function entityFromArray($data, $loadOriginalData = false, $overwriteOriginalData = true) {
        return parent::entityFromArray($data, $loadOriginalData, $overwriteOriginalData);
    }

}
