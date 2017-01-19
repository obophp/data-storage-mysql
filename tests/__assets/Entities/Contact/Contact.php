<?php

namespace obo\DataStorage\Tests\Assets\Entities;

/**
 * @obo-name(Contact)
 * @obo-storageName(testDb)
 * @obo-repositoryName(Contacts)
 * @property string $email
 * @property string $phone
 * @property string $fax
 * @property obo\DataStorage\Tests\Assets\Entities\Address $address
 * @property obo\DataStorage\Tests\Assets\Entities\Address[] $homeAddress
 * @property obo\DataStorage\Tests\Assets\Entities\Address[] $otherAddresses
 */
class Contact extends \obo\Entity {

}
