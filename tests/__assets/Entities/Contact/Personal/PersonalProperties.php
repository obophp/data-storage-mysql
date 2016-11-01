<?php

namespace obo\DataStorage\Tests\Assets\Entities\Contact;

class PersonalProperties extends \obo\DataStorage\Tests\Assets\Entities\ContactProperties {

    /** @obo-autoIncrement */
    public $id = 0;

    /** @obo-dataType(string) */
    public $firstname = "";

    /** @obo-dataType(string) */
    public $surname = "";

}
