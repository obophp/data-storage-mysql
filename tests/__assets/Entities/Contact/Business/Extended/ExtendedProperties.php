<?php

namespace obo\DataStorage\Tests\Assets\Entities\Contact\Business;

class ExtendedProperties extends \obo\DataStorage\Tests\Assets\Entities\Contact\BusinessProperties {

    /**
     * @obo-autoIncrement
     */
    public $id = 0;

    /**
     * @obo-dataType(string)
     */
    public $executiveOfficer = "";

}
