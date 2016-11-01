<?php

namespace obo\DataStorage\Tests\Assets\Entities;

/**
 * @obo-repositoryName(Contacts)
 * @property string $email
 * @property string $phone
 * @property string $fax
 * @property obo\DataStorage\Tests\Assets\Entities\Address $address
 * @property obo\DataStorage\Tests\Assets\Entities\Address[] $homeAddressse
 * @property obo\DataStorage\Tests\Assets\Entities\Address[] $otherAddresses
 */
class Contact extends \obo\Entity {

}
