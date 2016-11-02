<?php

namespace obo\DataStorage\Tests\Assets\Entities;

class ContactProperties extends \obo\EntityProperties {

    /** @obo-autoIncrement */
    public $id = 0;

    /**
     * @obo-dataType(string)
     */
    public $email = "";

    /**
     * @obo-dataType(string)
     */
    public $phone = "";

    /**
     * @obo-dataType(string)
     */
    public $fax = "";

    /**
     * @obo-one(targetEntity="\obo\DataStorage\Tests\Assets\Entities\Address", cascade = save, eager = true)
     */
    public $address;

    /**
     * @obo-many(targetEntity="\obo\DataStorage\Tests\Assets\Entities\Address", connectViaProperty="id", cascade = save)
     */
    public $homeAddresses;

    /**
     * @obo-many(targetEntity="\obo\DataStorage\Tests\Assets\Entities\Address", connectViaRepository="RelationshipBetweenContactAndOtherAddresses", cascade = save)
     */
    public $otherAddresses;

}