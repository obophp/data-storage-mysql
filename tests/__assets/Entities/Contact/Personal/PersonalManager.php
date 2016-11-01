<?php

namespace obo\DataStorage\Tests\Assets\Entities\Contact;

class PersonalManager extends \obo\DataStorage\Tests\Assets\Entities\ContactManager {

    /**
     * @param int|array $specification
     * @return Personal
     * @throws \obo\Exceptions\EntityNotFoundException
     */
    public static function personal($specification) {
        return parent::entity($specification);
    }

}
