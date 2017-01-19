<?php

namespace obo\DataStorage\Tests\Assets\Entities;

class AddressProperties extends \obo\EntityProperties {

    /** @obo-autoIncrement */
    public $id = 0;

    /**
     * @obo-one(targetEntity="property:ownerEntity", , cascade = "save, delete")
     */
    public $owner = null;

    public $ownerEntity = "";

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

    /**
     * @obo-many(targetEntity = "Contact", connectViaProperty = "address")
     */
    public $defaultContacts = null;

    /**
     * @obo-many(targetEntity = "Contact", connectViaRepository = "obo-test2.RelationshipBetweenContactAndOtherAddresses")
     */
    public $contacts = null;

}
