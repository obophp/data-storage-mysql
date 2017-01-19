<?php

namespace obo\DataStorage\Tests\Assets\Entities\Contact;

class PersonalManager extends \obo\DataStorage\Tests\Assets\Entities\ContactManager {

    public static function constructSelect() {
        return parent::constructSelect();
    }

    /**
     * @param int|array $specification
     * @return Personal
     * @throws \obo\Exceptions\EntityNotFoundException
     */
    public static function personal($specification) {
        return parent::entity($specification);
    }

    /**
     * @param array $data
     * @return \obo\DataStorage\Tests\Assets\Entities\Contact\Personal
     */
    public static function entityFromArray($data, $loadOriginalData = false, $overwriteOriginalData = true) {
        return parent::entityFromArray($data, $loadOriginalData, $overwriteOriginalData);
    }

}
