DROP TABLE IF EXISTS `obo-test`.`Contacts`;
CREATE TABLE `obo-test`.`Contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `address` int(11) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `obo-test`.`Contacts` (`id`, `email`, `phone`, `address`) VALUES (1, 'john@doe.com', '+420564215785', 1);
INSERT INTO `obo-test`.`Contacts` (`id`, `email`, `phone`, `address`) VALUES (2, 'jack@adams.com', '+420789562157', 2);
INSERT INTO `obo-test`.`Contacts` (`id`, `email`, `phone`, `address`) VALUES (3, 'info@mycompany.com', '+420457659215', 3);
INSERT INTO `obo-test`.`Contacts` (`id`, `email`, `phone`, `address`) VALUES (4, 'test@example.com', '+420023568451', 3);
INSERT INTO `obo-test`.`Contacts` (`id`, `email`, `phone`, `address`) VALUES (5, 'test@example.com', '+420541569872', 3);


DROP TABLE IF EXISTS `obo-test`.`PersonalContacts`;
CREATE TABLE `obo-test`.`PersonalContacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `surname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `obo-test`.`PersonalContacts` (`id`, `firstname`, `surname`) VALUES (1, 'John', 'Doe');
INSERT INTO `obo-test`.`PersonalContacts` (`id`, `firstname`, `surname`) VALUES (2, 'Jack', 'Adams');


DROP TABLE IF EXISTS `obo-test`.`BusinessContacts`;
CREATE TABLE `obo-test`.`BusinessContacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `companyName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `obo-test`.`BusinessContacts` (`id`, `companyName`) VALUES (3, 'My company s.r.o.');

DROP TABLE IF EXISTS `obo-test`.`ExtendedBusinessContacts`;
CREATE TABLE `obo-test`.`ExtendedBusinessContacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `executiveOfficer` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `obo-test`.`ExtendedBusinessContacts` (`id`, `executiveOfficer`) VALUES (3, 'John Smith');

DROP TABLE IF EXISTS `obo-test2`.`Contacts`;
CREATE TABLE `obo-test2`.`Contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fax` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `obo-test2`.`Contacts` (`id`, `fax`) VALUES (1, '+420999999999');
INSERT INTO `obo-test2`.`Contacts` (`id`, `fax`) VALUES (2, '+420888888888');
INSERT INTO `obo-test2`.`Contacts` (`id`, `fax`) VALUES (3, '+420777777777');
INSERT INTO `obo-test2`.`Contacts` (`id`, `fax`) VALUES (4, '+420666666666');


DROP TABLE IF EXISTS `obo-test2`.`Address`;
CREATE TABLE `obo-test2`.`Address` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `owner` INT(11) NOT NULL,
  `ownerEntity` VARCHAR(255) NOT NULL,
  `street` VARCHAR(255) NOT NULL,
  `houseNumber` VARCHAR(255) NOT NULL,
  `town` VARCHAR(255) NOT NULL,
  `postalCode` int(5) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `obo-test2`.`Address` (`id`, `owner`, `ownerEntity`, `street`, `houseNumber`, `town`, `postalCode`) VALUES (1, 1, 'PersonalContact', 'Arcata Main Street', '123', 'Arcata', '12345');
INSERT INTO `obo-test2`.`Address` (`id`, `owner`, `ownerEntity`, `street`, `houseNumber`, `town`, `postalCode`) VALUES (2, 2, 'PersonalContact', 'Benicia Main Street', '456', 'Benicia', '67890');
INSERT INTO `obo-test2`.`Address` (`id`, `owner`, `ownerEntity`, `street`, `houseNumber`, `town`, `postalCode`) VALUES (3, 3, 'BusinessContact', 'Lakeport Main Street', '789', 'Lakeport', '54321');

DROP TABLE IF EXISTS `obo-test2`.`RelationshipBetweenContactAndOtherAddresses`;
CREATE TABLE `obo-test2`.`RelationshipBetweenContactAndOtherAddresses` (
  `Contacts` INT NOT NULL,
  `Address` INT NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `obo-test2`.`RelationshipBetweenContactAndOtherAddresses` (`Contacts`, `Address`) VALUES (1, 1);
INSERT INTO `obo-test2`.`RelationshipBetweenContactAndOtherAddresses` (`Contacts`, `Address`) VALUES (1, 2);
INSERT INTO `obo-test2`.`RelationshipBetweenContactAndOtherAddresses` (`Contacts`, `Address`) VALUES (2, 3);
INSERT INTO `obo-test2`.`RelationshipBetweenContactAndOtherAddresses` (`Contacts`, `Address`) VALUES (3, 3);
