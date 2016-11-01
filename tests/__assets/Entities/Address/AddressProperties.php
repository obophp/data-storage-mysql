<?php

namespace obo\DataStorage\Tests\Assets\Entities;

class AddressProperties extends \obo\EntityProperties {

    /** @obo-autoIncrement */
    public $id = 0;

    /**
     * @obo-dataType(string)
     */
    public $street = "";

    /**
     * @obo-dataType(string)
     */
    public $houseNumber = "";

    /**
     * @obo-dataType(string)
     */
    public $town = "";

    /**
     * @obo-dataType(integer)
     */
    public $postalCode = "";

}
