CREATE TABLE `email_attachments`(
    `id` CHAR(36) NOT NULL,
    `email_id` CHAR(36) NOT NULL,
    `file` VARCHAR(255) NOT NULL,
    `created` DATETIME NOT NULL,
    `modified` DATETIME NOT NULL,
    PRIMARY KEY(`id`)
);

CREATE TABLE `email_destinations`(
    `id` CHAR(36) NOT NULL,
    `email_id` CHAR(36) NOT NULL,
    `type` ENUM('to', 'cc', 'bcc') NOT NULL default 'to',
    `name` VARCHAR(255) default NULL,
    `email` VARCHAR(255) NOT NULL,
    `created` DATETIME NOT NULL,
    `modified` DATETIME NOT NULL,
    PRIMARY KEY(`id`)
);

CREATE TABLE `emails`(
    `id` CHAR(36) NOT NULL,
    `template` VARCHAR(255) NOT NULL,
    `from_name` VARCHAR(255) default NULL,
    `from_email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `variables` BLOB default NULL,
    `html` BLOB default NULL,
    `text` BLOB default NULL,
    `queued` DATETIME NOT NULL,
    `processed` DATETIME,
    `failed` INT NOT NULL default 0,
    `sent` DATETIME,
    `created` DATETIME NOT NULL,
    `modified` DATETIME NOT NULL,
    PRIMARY KEY(`id`)
);

ALTER TABLE `email_attachments`
    ADD KEY `email_id`(`email_id`),
    ADD CONSTRAINT `email_attachments__emails` FOREIGN KEY(`email_id`) REFERENCES `emails`(`id`);

ALTER TABLE `email_destinations`
    ADD KEY `email_id`(`email_id`),
    ADD CONSTRAINT `email_destinations__emails` FOREIGN KEY(`email_id`) REFERENCES `emails`(`id`);
