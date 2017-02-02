<?php

namespace obo\DataStorage\Tests\Assets\Entities\Contact\Business;

class ExtendedManager extends \obo\DataStorage\Tests\Assets\Entities\Contact\BusinessManager {

    /**
     * @param int|array $specification
     * @return obo\DataStorage\Tests\Assets\Entities\Contact\Business\Extended
     * @throws \obo\Exceptions\EntityNotFoundException
     */
    public static function extended($specification) {
        return parent::entity($specification);
    }

}
