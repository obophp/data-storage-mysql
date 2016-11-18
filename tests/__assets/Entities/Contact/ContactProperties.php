<?php

namespace obo\DataStorage\Tests\Assets\Entities;

class ContactProperties extends \obo\EntityProperties {

    /**
     * @obo-storageName(obo-test)
     * @obo-repositoryName(Contacts)
     * @obo-autoIncrement
     */
    public $id = 0;

    /**
     * @obo-dataType(string)
     * @obo-storageName(obo-test)
     * @obo-repositoryName(Contacts)
     */
    public $email = "";

    /**
     * @obo-dataType(string)
     * @obo-storageName(obo-test)
     * @obo-repositoryName(Contacts)
     */
    public $phone = "";

    /**
     * @obo-dataType(string)
     * @obo-storageName(obo-test2)
     * @obo-repositoryName(Contacts)
     */
    public $fax = "";

    /**
     * @obo-one(targetEntity="\obo\DataStorage\Tests\Assets\Entities\Address", cascade = save, eager = true)
     * @obo-storageName(obo-test)
     * @obo-repositoryName(Contacts)
     */
    public $address;

    /**
     * @obo-many(targetEntity="\obo\DataStorage\Tests\Assets\Entities\Address", connectViaProperty="id", cascade = save)
     */
    public $homeAddresses;

    /**
     * @obo-many(targetEntity="\obo\DataStorage\Tests\Assets\Entities\Address", connectViaRepository="obo-test2.RelationshipBetweenContactAndOtherAddresses", cascade = save)
     */
    public $otherAddresses;

}
