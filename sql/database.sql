CREATE DATABASE IF NOT EXISTS `csv_test`
    DEFAULT CHARACTER SET = 'utf8mb4';

CREATE TABLE IF NOT EXISTS `contacts` (
    `id` int NOT NULL AUTO_INCREMENT,
    `salutation` VARCHAR(255) COLLATE utf8_general_ci DEFAULT NULL,
    `first_name` VARCHAR(255) COLLATE utf8_general_ci DEFAULT NULL,
    `last_name` VARCHAR(255) COLLATE utf8_general_ci NOT NULL,
    `birthdate` DATE COLLATE utf8_general_ci DEFAULT NULL,
    `country` VARCHAR(255) COLLATE utf8_general_ci DEFAULT NULL,
    `email` VARCHAR(255) COLLATE utf8_general_ci DEFAULT NULL,
    `phone_number` VARCHAR(255) COLLATE utf8_general_ci DEFAULT NULL,
    `languages` VARCHAR(255) COLLATE utf8_general_ci DEFAULT NULL,
    `create_time` TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `update_time` TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE contacts ADD key (`last_name`);
ALTER TABLE contacts ADD UNIQUE KEY (`email`);