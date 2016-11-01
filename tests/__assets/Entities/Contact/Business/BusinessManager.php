<?php

namespace obo\DataStorage\Tests\Assets\Entities\Contact;

class BusinessManager extends \obo\DataStorage\Tests\Assets\Entities\ContactManager {

    /**
     * @param int|array $specification
     * @return Contact
     * @throws \obo\Exceptions\EntityNotFoundException
     */
    public static function contact($specification) {
        return parent::entity($specification);
    }

}
