/*
  This would be the MySQL database structure. You can copy/paste this script
  to install the database. For now, there won't be an installer, which means
  any prefix for the tables, you'll have to add yourself. You can do so by
  replacing all "pref_" with whatever prefix you desire.
*/

/* Forum Part */
/*
  This would be the MySQL database structure. You can copy/paste this script
  to install the database. For now, there won't be an installer, which means
  any prefix for the tables, you'll have to add yourself. You can do so by
  replacing all "pref_" with whatever prefix you desire.
*/

/* Forum Part */
CREATE TABLE IF NOT EXISTS `{{PREFIX}}categories` (
    `id`          INT(11) NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(255)     DEFAULT NULL,
    `description` VARCHAR(255)     DEFAULT NULL,
    `order`       INT(2)           DEFAULT '0',
    `enabled`     TINYINT(1)       DEFAULT '1',
    PRIMARY KEY (`id`)
)
    ENGINE = MyISAM
    AUTO_INCREMENT = 0
    DEFAULT CHARSET = latin1;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}content_strings` (
    `id`    INT(11) NOT NULL AUTO_INCREMENT,
    `key`   VARCHAR(45)      DEFAULT NULL,
    `value` LONGTEXT,
    PRIMARY KEY (`id`),
    UNIQUE KEY `key_UNIQUE` (`key`)
)
    ENGINE = MyISAM
    AUTO_INCREMENT = 0
    DEFAULT CHARSET = latin1;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}groups` (
    `id`        INT(11) NOT NULL AUTO_INCREMENT,
    `title`     VARCHAR(255)     DEFAULT NULL,
    `desc`      VARCHAR(255)     DEFAULT NULL,
    `order`     INT(2)           DEFAULT NULL,
    `admin`     TINYINT(1)       DEFAULT '0',
    `discordId` VARCHAR(255)     DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `title_UNIQUE` (`title`)
)
    ENGINE = InnoDB
    AUTO_INCREMENT = 0
    DEFAULT CHARSET = latin1;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}permissions` (
    `id`         INT(11) NOT NULL AUTO_INCREMENT,
    `groupId`    INT(11)          DEFAULT NULL,
    `userId`     INT(11)          DEFAULT NULL,
    `categoryId` INT(11)          DEFAULT NULL,
    `topicId`    INT(11)          DEFAULT NULL,
    `threadId`   INT(11)          DEFAULT NULL,
    `read`       TINYINT(4)       DEFAULT NULL,
    `post`       TINYINT(4)       DEFAULT NULL,
    `mod`        TINYINT(4)       DEFAULT NULL,
    `admin`      TINYINT(4)       DEFAULT NULL,
    `reply`      TINYINT(4)       DEFAULT NULL,
    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    AUTO_INCREMENT = 0
    DEFAULT CHARSET = latin1;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}posts` (
    `id`                INT(11) NOT NULL AUTO_INCREMENT,
    `post_content_html` LONGTEXT,
    `post_content_text` LONGTEXT,
    `authorId`          INT(11)          DEFAULT NULL,
    `threadId`          INT(11)          DEFAULT NULL,
    `postDate`          DATETIME         DEFAULT NULL,
    `editDate`          DATETIME         DEFAULT NULL,
    `originalPost`      TINYINT(1)       DEFAULT NULL,
    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    AUTO_INCREMENT = 0
    DEFAULT CHARSET = latin1;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}threads` (
    `id`          INT(11) NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(255)     DEFAULT NULL,
    `topicId`     INT(11)          DEFAULT NULL,
    `authorId`    INT(11)          DEFAULT NULL,
    `dateCreated` DATETIME         DEFAULT NULL,
    `lastEdited`  DATETIME         DEFAULT NULL,
    `sticky`      TINYINT(1)       DEFAULT NULL,
    `closed`      TINYINT(1)       DEFAULT NULL,
    PRIMARY KEY (`id`)
)
    ENGINE = MyISAM
    AUTO_INCREMENT = 0
    DEFAULT CHARSET = latin1;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}topics` (
    `id`          INT(11) NOT NULL AUTO_INCREMENT,
    `categoryId`  INT(11)          DEFAULT NULL,
    `title`       VARCHAR(255)     DEFAULT NULL,
    `description` VARCHAR(255)     DEFAULT NULL,
    `icon`        VARCHAR(255)     DEFAULT NULL,
    `enabled`     TINYINT(1)       DEFAULT '1',
    `order`       INT(2)           DEFAULT '0',
    PRIMARY KEY (`id`)
)
    ENGINE = MyISAM
    AUTO_INCREMENT = 0
    DEFAULT CHARSET = latin1;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}users` (
    `id`            INT(11) NOT NULL AUTO_INCREMENT,
    `username`      VARCHAR(255)     DEFAULT NULL,
    `password`      VARCHAR(255)     DEFAULT NULL,
    `email`         VARCHAR(255)     DEFAULT NULL,
    `avatar`        VARCHAR(255)     DEFAULT '{{theme::imgdir}}user/avatar.jpg',
    `group`         INT(11)          DEFAULT NULL,
    `regip`         VARCHAR(255)     DEFAULT NULL,
    `lastip`        VARCHAR(255)     DEFAULT NULL,
    `regdate`       DATETIME         DEFAULT NULL,
    `lastlogindate` DATETIME         DEFAULT NULL,
    `firstname`     VARCHAR(255)     DEFAULT NULL,
    `lastname`      VARCHAR(255)     DEFAULT NULL,
    `about`         LONGTEXT,
    `location`      VARCHAR(45)      DEFAULT NULL,
    `discordId`     VARCHAR(45)      DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username_UNIQUE` (`username`)
)
    ENGINE = InnoDB
    AUTO_INCREMENT = 0
    DEFAULT CHARSET = latin1;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}users_session` (
    `id`         INT(11) NOT NULL AUTO_INCREMENT,
    `uid`        INT(11)          DEFAULT NULL,
    `lastActive` DATETIME         DEFAULT NULL,
    `ipAddress`  VARCHAR(255)     DEFAULT NULL,
    `created`    DATETIME         DEFAULT NULL,
    `lastPage`   VARCHAR(255)     DEFAULT NULL,
    `phpSessId`  VARCHAR(255)     DEFAULT NULL,
    `userAgent`  LONGTEXT,
    PRIMARY KEY (`id`),
    UNIQUE KEY `phpSessId_UNIQUE` (`phpSessId`)
)
    ENGINE = MyISAM
    AUTO_INCREMENT = 0
    DEFAULT CHARSET = latin1;
