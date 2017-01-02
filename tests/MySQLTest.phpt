<?php

namespace obo\DataStorage\Tests;

use Tester\Assert;

require __DIR__ . "/bootstrap.php";

class MySQLTest extends \Tester\TestCase {

    /**
     * @var string[]
     */
    private $queryLog = [];

    /**
     * @var \obo\DataStorage\MySQL
     */
    private $storage;

    /**
     * @var \Dibi\Connection
     */
    private $connection;

    /**
     * @var string[]
     */
    private static $contactData = [
        "email" => "john@doe.com",
        "phone" => "+420123456789",
        "fax" => "+420987654321",
    ];

    /**
     * @var string[]
     */
    private static $personalContactData = [
        "firstname" => "John",
        "surname" => "Doe",
        "email" => "john@doe.com",
        "phone" => "+420123456789",
        "fax" => "+420987654321",
    ];

    private static $addressData = [
        'owner' => 1,
        'ownerEntity' => 'obo\\DataStorage\\Tests\\Assets\\Entities\\Contact\\Personal',
        "street" => "My Street",
        "houseNumber" => "123",
        "town" => "My Town",
        "postalCode" => 12345

    ];

    /**
     * @var string[]
     */
    private static $expectedDataForQuery = [
        'id' => 1,
        'email' => 'john@doe.com',
        'phone' => '+420123456789',
        'fax' => '+420987654321',
        'address' => 1,
        'address_id' => 1,
        'address_owner' => 1,
        'address_ownerEntity' => 'obo\\DataStorage\\Tests\\Assets\\Entities\\Contact\\Personal',
        'address_street' => 'My Street',
        'address_houseNumber' => '123',
        'address_town' => 'My Town',
        'address_postalCode' => 12345
    ];


    /**
     * @var int
     */
    const DEFAULT_ENTITY_ID = 1;

    /**
     * @var int
     */
    const INSERTED_ENTITY_ID = 2;

    /**
     * @var string
     */
    const CONTACTS_REPOSITORY = "obo-test.Contacts";

    /**
     * @var string
     */
    const RELATIONSHIP_BETWEEN_CONTACT_AND_ADDRESS_REPOSITORY = "obo-test2.RelationshipBetweenContactAndOtherAddresses";

    /**
     * @var string
     */
    const TEST_SQL_FILE = "test.sql";

    /**
     * @var string
     */
    const TEST_FILE_PATH = "temp/log/query.log";


    public function __construct() {
        $this->storage = Assets\Storage::getMySqlDataStorage();
        $this->connection = Assets\Storage::getConnection();
        $this->connection->onEvent[] = function(\DibiEvent $event) {
            $this->queryLog[] = $event->sql;
        };
    }

    protected function setUp() {
        parent::setUp();
        Assets\Storage::getConnection()->loadFile(__DIR__ . DIRECTORY_SEPARATOR . "__assets" . DIRECTORY_SEPARATOR . static::TEST_SQL_FILE);
    }

    /**
     * @return \obo\Carriers\QueryCarrier
     */
    protected function createContactQueryCarrier() {
        $queryCarrier = new \obo\Carriers\QueryCarrier();
        $queryCarrier->setDefaultEntityClassName(Assets\Entities\Contact::class);
        return $queryCarrier->select(Assets\Entities\ContactManager::constructSelect());
    }

    /**
     * @return \obo\Carriers\QueryCarrier
     */
    protected function createPersonalContactQueryCarrier() {
        $queryCarrier = new \obo\Carriers\QueryCarrier();
        $queryCarrier->setDefaultEntityClassName(Assets\Entities\Contact\Personal::class);
        return $queryCarrier->select(Assets\Entities\Contact\PersonalManager::constructSelect());
    }

    /**
     * @return \obo\Carriers\QueryCarrier
     */
    protected function createAddressQueryCarrier() {
        $queryCarrier = new \obo\Carriers\QueryCarrier();
        $queryCarrier->setDefaultEntityClassName(Assets\Entities\Address::class);
        return $queryCarrier->select(Assets\Entities\AddressManager::constructSelect());
    }

    /**
     * @return \obo\DataStorage\Tests\Assets\Entities\Contact
     */
    protected function createContactEntity($save = true) {
        $entity = Assets\Entities\ContactManager::entityFromArray(static::$contactData);
        if ($save) {
            $entity->save();
        }
        return $entity;
    }

    /**
     * @return \obo\DataStorage\Tests\Assets\Entities\Address
     */
    protected function createAddressEntity($save = true) {
        $entity = Assets\Entities\AddressManager::entityFromArray(static::$addressData);
        if ($save) {
            $entity->save();
        }
        return $entity;
    }

    /**
     * @return \obo\DataStorage\Tests\Assets\Entities\Contact
     * @throws \obo\Exceptions\EntityNotFoundException
     */
    protected function getContactEntity($id = self::DEFAULT_ENTITY_ID) {
        return Assets\Entities\ContactManager::contact($id);
    }

    /**
     * @return \obo\DataStorage\Tests\Assets\Entities\Contact\Personal
     * @throws \obo\Exceptions\EntityNotFoundException
     */
    protected function getPersonalContactEntity($id = self::DEFAULT_ENTITY_ID) {
        return Assets\Entities\Contact\PersonalManager::personal($id);
    }

    /**
     * @return \obo\DataStorage\Tests\Assets\Entities\Address
     * @throws \obo\Exceptions\EntityNotFoundException
     */
    protected function getAddressEntity($id = self::DEFAULT_ENTITY_ID) {
        return Assets\Entities\AddressManager::address($id);
    }

    /**
     * @return DibiRow|false
     * @throws \Dibi\DriverException
     */
    protected function selectEntity($repositoryName, $id) {
        $repositoryName = "[" . str_replace(".", "].[", $repositoryName) . "]";
        return $this->connection->select("*")->from($repositoryName)->where("id = %i", $id)->fetch();
    }

    /**
     * @return int
     */
    protected function countRelationshipBetweenContactAndAddress() {
        $queryCarrier = $this->createContactQueryCarrier();
        return $this->storage->countEntitiesInRelationship(
            $queryCarrier,
            static::RELATIONSHIP_BETWEEN_CONTACT_AND_ADDRESS_REPOSITORY,
            $this->getContactEntity(),
            Assets\Entities\Address::class
        );
    }

    /**
     * @return array
     */
    protected function getExpectedDataForEntity() {
        $expectedData = [];
        for($i = 0; $i <= 1; $i++) {
            $expectedData[$i] = static::$expectedDataForQuery;
            $expectedData[$i]["id"] = $i+1;
        }
        return $expectedData;
    }

    public function testConstructQuery() {
        $queryCarrier = $this->createContactQueryCarrier();
        $personalContact = $this->createPersonalContactQueryCarrier();
        $expectedQuery = "SELECT  `obo-test`.`Contacts`.`id` AS `id`, `obo-test`.`Contacts`.`email` AS `email`, `obo-test`.`Contacts`.`phone` AS `phone`, `obo-test2`.`Contacts`.`fax` AS `fax`, `obo-test`.`Contacts`.`address` AS `address`, `obo-test2`.`jk1`.`id` AS `address_id`, `obo-test2`.`jk1`.`owner` AS `address_owner`, `obo-test2`.`jk1`.`ownerEntity` AS `address_ownerEntity`, `obo-test2`.`jk1`.`street` AS `address_street`, `obo-test2`.`jk1`.`houseNumber` AS `address_houseNumber`, `obo-test2`.`jk1`.`town` AS `address_town`, `obo-test2`.`jk1`.`postalCode` AS `address_postalCode` FROM `obo-test`.`Contacts` INNER JOIN `obo-test2`.`Contacts` ON `obo-test2`.`Contacts`.`id` = `obo-test`.`Contacts`.`id` LEFT JOIN `obo-test2`.`Address` AS `jk1` ON `obo-test`.`Contacts`.`address` = `jk1`.`id` /** jk1 => obo-test:Contacts:address->LEFT_JOIN->obo-test2:Address:id */ ";
        $actualQuery = $this->storage->constructQuery($queryCarrier);
        Assert::equal($expectedQuery, $actualQuery);
    }

    public function testDataForQuery() {
        $queryCarrier = $this->createContactQueryCarrier();
        $actualData = $this->storage->dataForQuery($queryCarrier);
        Assert::equal($this->getExpectedDataForEntity(), $actualData);
    }

    public function testCountRecordsForQuery() {
        $queryCarrier = $this->createContactQueryCarrier();
        Assert::equal($this->storage->countRecordsForQuery($queryCarrier), 2);
    }

    public function testInsertEntity() {
        $entity = $this->createContactEntity(false);
        $this->storage->insertEntity($entity);
        $entity->save();

        $selectedEntity = $this->selectEntity(static::CONTACTS_REPOSITORY, $entity->primaryPropertyValue());
        Assert::true($selectedEntity !== FALSE, "Contact entity with ID " . $entity->primaryPropertyValue() . "should be inserted in database");

        Assert::exception(
            function () use ($entity) {
                $this->storage->insertEntity($entity);
            },
            \obo\Exceptions\Exception::class
        );
    }

    public function testUpdateEntity() {
        $entity = $this->getContactEntity();
        $newEmail = "test@test.com";
        $entity->email = $newEmail;
        $this->storage->updateEntity($entity);

        $updatedEntity = $this->selectEntity(static::CONTACTS_REPOSITORY, $entity->primaryPropertyValue());
        Assert::equal($newEmail, $updatedEntity["email"], "Contact entity with ID " . $entity->primaryPropertyValue() . "should be updated");
    }

    public function testRemoveEntity() {
        $entity = $this->getContactEntity();
        $entityKey = $entity->primaryPropertyValue();
        $this->storage->removeEntity($entity);
        $deletedEntity = $this->selectEntity(static::CONTACTS_REPOSITORY, $entityKey);
        Assert::false($deletedEntity, "Contact entity with ID " . $entityKey . "should be deleted");
    }

    public function testCountEntitiesInRelationship() {
        Assert::equal($this->countRelationshipBetweenContactAndAddress(), 2);
    }

//    public function testDataForEntitiesInRelationship() {
//        $queryCarrier = $this->createContactQueryCarrier();
//        $owner = $this->getContactEntity();
//        $targetEntity = $this->getAddressEntity();
//        $actualData = $this->storage->dataForEntitiesInRelationship($queryCarrier, static::RELATIONSHIP_BETWEEN_CONTACT_AND_ADDRESS_REPOSITORY, $owner, $targetEntity);
//        Assert::equal($this->getExpectedDataForEntity(), $actualData);
//    }

    public function testCreateRelationshipBetweenEntities() {
        Assert::equal($this->countRelationshipBetweenContactAndAddress(), 2);
        $this->storage->createRelationshipBetweenEntities(static::RELATIONSHIP_BETWEEN_CONTACT_AND_ADDRESS_REPOSITORY, [$this->getContactEntity(), $this->createAddressEntity()]);
        Assert::equal($this->countRelationshipBetweenContactAndAddress(), 3);
    }

    public function testRemoveRelationshipBetweenEntities() {
        Assert::equal($this->countRelationshipBetweenContactAndAddress(), 2);
        $this->storage->removeRelationshipBetweenEntities(static::RELATIONSHIP_BETWEEN_CONTACT_AND_ADDRESS_REPOSITORY, [$this->getContactEntity(), $this->getAddressEntity()]);
        Assert::equal($this->countRelationshipBetweenContactAndAddress(), 1);
    }

    public function testInformationForEntity() {
        $contactQueryCarrier = $this->createContactQueryCarrier();
        $contactEntityInformation = $contactQueryCarrier->getDefaultEntityEntityInformation();
        $informationForContactEntity = $this->storage->informationForEntity($contactEntityInformation);
        Assert::true(isset($informationForContactEntity["storages"]["obo-test"]["repositories"]["Contacts"]) && isset($informationForContactEntity["storages"]["obo-test2"]["repositories"]["Contacts"]), "Repository Contacts located in storage with name obo-test and obo-test2 not found");

        $addressQueryCarrier = $this->createAddressQueryCarrier();
        $addressEntityInformation = $addressQueryCarrier->getDefaultEntityEntityInformation();
        $informationForAddressEntity = $this->storage->informationForEntity($addressEntityInformation);
        Assert::true(isset($informationForAddressEntity["storages"]["obo-test2"]["repositories"]["Address"]), "Repository Address located in storage with name obo-test2 is not properly indexed");
    }

    public function testSelectEntities() {
        $contact = $this->getContactEntity();
        $addresses = $contact->otherAddresses->asArray();
        foreach ($addresses as $address) {
            Assert::type(Assets\Entities\Address::class, $address);
            Assert::type(Assets\Entities\Contact\Personal::class, $address->owner);
            Assert::equal(
                static::$personalContactData,
                $address->owner->propertiesAsArray([
                    "firstname" => true,
                    "surname" => true,
                    "email" => true,
                    "phone" => true,
                    "fax" => true
                ])
            );
            foreach ($address->defaultContacts as $contact) {
                Assert::type(Assets\Entities\Contact::class, $contact);
            }
        }

        $address = $this->getAddressEntity();
        $contacts = $address->contacts->asArray();

        foreach ($contacts as $contact) {
            Assert::type(Assets\Entities\Contact::class, $contact);
        }

        Assert::exception(
            function () {
                $this->getContactEntity(3);
            },
            \obo\Exceptions\EntityNotFoundException::class
        );

        $personalContact = $this->getPersonalContactEntity();
        $entity = $personalContact->propertiesAsArray();

        $addresses = Assets\Entities\ContactManager::contactsAsCollection();
        Assert::true(is_array($addresses->asArray()));
        throw new \Exception("AHOJ");
    }

    public function tearDown() {
        parent::tearDown();

        $data = [];
        foreach ($this->queryLog as $record) {
            $data = $record . "\n";
        }

        $file = __DIR__ . DIRECTORY_SEPARATOR . static::TEST_FILE_PATH;
        file_put_contents($file, $data, FILE_APPEND);
        }

    public function __destruct() {
        $file = __DIR__ . DIRECTORY_SEPARATOR . static::TEST_FILE_PATH;
        file_put_contents($file, "\n", FILE_APPEND);
    }

}

$testCase = new MySQLTest();
$testCase->run();
