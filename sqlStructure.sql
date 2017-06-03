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
CREATE TABLE `prefix_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `order` int(2) DEFAULT '0',
  `enabled` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;

CREATE TABLE `prefix_content_strings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(45) DEFAULT NULL,
  `value` longtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_UNIQUE` (`key`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;

CREATE TABLE `prefix_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `order` int(2) DEFAULT NULL,
  `admin` tinyint(1) DEFAULT '0',
  `discordId` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;

CREATE TABLE `prefix_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupId` int(11) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `categoryId` int(11) DEFAULT NULL,
  `topicId` int(11) DEFAULT NULL,
  `threadId` int(11) DEFAULT NULL,
  `read` tinyint(4) DEFAULT NULL,
  `post` tinyint(4) DEFAULT NULL,
  `mod` tinyint(4) DEFAULT NULL,
  `admin` tinyint(4) DEFAULT NULL,
  `reply` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;

CREATE TABLE `prefix_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_content_html` longtext,
  `post_content_text` longtext,
  `authorId` int(11) DEFAULT NULL,
  `threadId` int(11) DEFAULT NULL,
  `postDate` datetime DEFAULT NULL,
  `editDate` datetime DEFAULT NULL,
  `originalPost` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;

CREATE TABLE `prefix_threads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `topicId` int(11) DEFAULT NULL,
  `authorId` int(11) DEFAULT NULL,
  `dateCreated` datetime DEFAULT NULL,
  `lastEdited` datetime DEFAULT NULL,
  `sticky` tinyint(1) DEFAULT NULL,
  `closed` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;

CREATE TABLE `prefix_topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoryId` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT '1',
  `order` int(2) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;

CREATE TABLE `prefix_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT '{{theme::imgdir}}user/avatar.jpg',
  `group` int(11) DEFAULT NULL,
  `regip` varchar(255) DEFAULT NULL,
  `lastip` varchar(255) DEFAULT NULL,
  `regdate` datetime DEFAULT NULL,
  `lastlogindate` datetime DEFAULT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `about` longtext,
  `location` varchar(45) DEFAULT NULL,
  `discordId` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_UNIQUE` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;

CREATE TABLE `prefix_users_session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `lastActive` datetime DEFAULT NULL,
  `ipAddress` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `lastPage` varchar(255) DEFAULT NULL,
  `phpSessId` varchar(255) DEFAULT NULL,
  `userAgent` longtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phpSessId_UNIQUE` (`phpSessId`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;


