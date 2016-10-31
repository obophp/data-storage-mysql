DROP TABLE IF EXISTS `Contacts`;
CREATE TABLE `Contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `fax` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `address` int(11) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `Contacts` (`id`, `email`, `phone`, `fax`, `address`) VALUES (1, 'john@doe.com', '+420123456789', '+420987654321', 1);


DROP TABLE IF EXISTS `PersonalContacts`;
CREATE TABLE `PersonalContacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `surname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `PersonalContacts` (`id`, `firstname`, `surname`) VALUES (1, 'John', 'Doe');


DROP TABLE IF EXISTS `BusinessContacts`;
CREATE TABLE `BusinessContacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `companyName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `BusinessContacts` (`id`, `companyName`) VALUES (1, 'My company s.r.o.');

DROP TABLE IF EXISTS `Address`;
CREATE TABLE `Address` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `street` VARCHAR(255) NOT NULL,
  `houseNumber` VARCHAR(255) NOT NULL,
  `town` VARCHAR(255) NOT NULL,
  `postalCode` int(5) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `Address` (`id`, `street`, `houseNumber`, `town`, `postalCode`) VALUES (1, 'My Street', '123', 'My Town', '12345');


DROP TABLE IF EXISTS `RelationshipBetweenContactAndOtherAddresses`;
CREATE TABLE `RelationshipBetweenContactAndOtherAddresses` (
  `Contacts` INT NOT NULL,
  `Address` INT NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `RelationshipBetweenContactAndOtherAddresses` (`Contacts`, `Address`) VALUES (1, 1);
