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

    private static $addressData = [
        "street" => "My Street",
        "houseNumber" => "123",
        "town" => "My Town",
        "postalCode" => 12345

    ];

    /**
     * @var string[]
     */
    private static $expectedDataForQuery = [
        [
            'id' => 1,
            'email' => 'john@doe.com',
            'phone' => '+420123456789',
            'fax' => '+420987654321',
            'address' => 1,
            'address_id' => 1,
            'address_street' => 'My Street',
            'address_houseNumber' => '123',
            'address_town' => 'My Town',
            'address_postalCode' => 12345
        ]
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
    const CONTACTS_REPOSITORY = "Contacts";

    /**
     * @var string
     */
    const RELATIONSHIP_BETWEEN_CONTACT_AND_ADDRESS_REPOSITORY = "RelationshipBetweenContactAndOtherAddresses";

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
     * @param string $classname
     * @return \obo\Carriers\QueryCarrier
     */
    protected function createContactQueryCarrier() {
        $queryCarrier = new \obo\Carriers\QueryCarrier();
        $queryCarrier->setDefaultEntityClassName(Assets\Entities\Contact::class);
        return $queryCarrier->select(Assets\Entities\ContactManager::constructSelect());
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
        return $this->connection->select("*")->from($repositoryName)->fetch();
    }

    /**
     * @param string $repositoryName
     * @return int
     * @throws \Dibi\DriverException
     */
    protected function countRecords($repositoryName) {
        $repositoryName = "[" . str_replace(".", "].[", $repositoryName) . "]";
        return (int)$this->connection->select("COUNT(*)")->from($repositoryName)->fetchSingle();
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

    public function testConstructQuery() {
        $queryCarrier = $this->createContactQueryCarrier();
        $expectedQuery = "SELECT  `Contacts`.`id` AS `id`, `Contacts`.`email` AS `email`, `Contacts`.`phone` AS `phone`, `Contacts`.`fax` AS `fax`, `Contacts`.`address` AS `address`, `obo\DataStorage\Tests\Assets\Entities\Contact_address->obo\DataStorage\Tests\Assets\Entities\Address`.`id` AS `address_id`, `obo\DataStorage\Tests\Assets\Entities\Contact_address->obo\DataStorage\Tests\Assets\Entities\Address`.`street` AS `address_street`, `obo\DataStorage\Tests\Assets\Entities\Contact_address->obo\DataStorage\Tests\Assets\Entities\Address`.`houseNumber` AS `address_houseNumber`, `obo\DataStorage\Tests\Assets\Entities\Contact_address->obo\DataStorage\Tests\Assets\Entities\Address`.`town` AS `address_town`, `obo\DataStorage\Tests\Assets\Entities\Contact_address->obo\DataStorage\Tests\Assets\Entities\Address`.`postalCode` AS `address_postalCode` FROM `Contacts`LEFT JOIN `Address` as `obo\DataStorage\Tests\Assets\Entities\Contact_address->obo\DataStorage\Tests\Assets\Entities\Address` ON `Contacts`.`address` = `obo\DataStorage\Tests\Assets\Entities\Contact_address->obo\DataStorage\Tests\Assets\Entities\Address`.`id`";
        $actualQuery = $this->storage->constructQuery($queryCarrier);
        Assert::equal($actualQuery, $expectedQuery);
    }

    public function testDataForQuery() {
        $queryCarrier = $this->createContactQueryCarrier();
        $actualData = $this->storage->dataForQuery($queryCarrier);
        Assert::equal($actualData, static::$expectedDataForQuery);
    }

    public function testCountRecordsForQuery() {
        $queryCarrier = $this->createContactQueryCarrier();
        Assert::equal($this->storage->countRecordsForQuery($queryCarrier), 1);
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
        $this->storage->removeEntity($entity);
        $deletedEntity = $this->selectEntity(static::CONTACTS_REPOSITORY, $entity->primaryPropertyValue());
        Assert::false($deletedEntity, "Contact entity with ID " . $entity->primaryPropertyValue() . "should be deleted");
    }

    public function testCountEntitiesInRelationship() {
        Assert::equal($this->countRelationshipBetweenContactAndAddress(), 1);
    }

    public function testDataForEntitiesInRelationship() {
        $queryCarrier = $this->createContactQueryCarrier();
        $owner = $this->getContactEntity();
        $targetEntity = $this->getAddressEntity();
        $actualData = $this->storage->dataForEntitiesInRelationship($queryCarrier, "Address", $owner, $targetEntity);
        Assert::equal($actualData, static::$expectedDataForQuery);
    }

    public function testCreateRelationshipBetweenEntities() {
        Assert::equal($this->countRecords(static::RELATIONSHIP_BETWEEN_CONTACT_AND_ADDRESS_REPOSITORY), 1);
        $this->storage->createRelationshipBetweenEntities(static::RELATIONSHIP_BETWEEN_CONTACT_AND_ADDRESS_REPOSITORY, [$this->createContactEntity(), $this->createAddressEntity()]);
        Assert::equal($this->countRecords(static::RELATIONSHIP_BETWEEN_CONTACT_AND_ADDRESS_REPOSITORY), 2);
    }

    public function testRemoveRelationshipBetweenEntities() {
        Assert::equal($this->countRecords(static::RELATIONSHIP_BETWEEN_CONTACT_AND_ADDRESS_REPOSITORY), 1);
        $this->storage->removeRelationshipBetweenEntities(static::RELATIONSHIP_BETWEEN_CONTACT_AND_ADDRESS_REPOSITORY, [$this->getContactEntity(), $this->getAddressEntity()]);
        Assert::equal($this->countRecords(static::RELATIONSHIP_BETWEEN_CONTACT_AND_ADDRESS_REPOSITORY), 0);
    }

    public function testInformationForEntity() {
        $queryCarrier = $this->createContactQueryCarrier();
        $entityInformation = $queryCarrier->getDefaultEntityEntityInformation();
        $informationForEntity = $this->storage->informationForEntity($entityInformation);
        Assert::equal($informationForEntity["table"], static::CONTACTS_REPOSITORY, "Repository Contacts not found");
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
