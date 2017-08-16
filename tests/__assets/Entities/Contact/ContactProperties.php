<?php

namespace obo\DataStorage\Tests\Assets\Entities;

class ContactProperties extends \obo\EntityProperties {

    /**
     * @obo-storageName(testDb)
     * @obo-repositoryName(Contacts)
     * @obo-autoIncrement
     */
    public $id = 0;

    /**
     * @obo-dataType(string)
     * @obo-storageName(testDb)
     * @obo-repositoryName(Contacts)
     */
    public $email = "";

    /**
     * @obo-dataType(string)
     * @obo-storageName(testDb)
     * @obo-repositoryName(Contacts)
     */
    public $phone = "";

    /**
     * @obo-dataType(string)
     * @obo-storageName(testDb2)
     * @obo-repositoryName(Contacts)
     */
    public $fax = "";

    /**
     * @obo-one(targetEntity="Address", cascade = save, eager = true)
     * @obo-storageName(testDb)
     * @obo-repositoryName(Contacts)
     */
    public $address;

    /**
     * @obo-storageName(testDb)
     * @obo-repositoryName(Contacts)
     * @obo-many(targetEntity="Address", connectViaProperty="id", cascade = save)
     */
    public $homeAddresses;

    /**
     * @obo-storageName(testDb)
     * @obo-repositoryName(Contacts)
     * @obo-many(targetEntity="Address", connectViaRepository="testDb2.RelationshipBetweenContactAndOtherAddresses", cascade = save)
     */
    public $otherAddresses;

}
