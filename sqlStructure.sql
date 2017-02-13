/*
  This would be the MySQL database structure. You can copy/paste this script
  to install the database. For now, there won't be an installer, which means
  any prefix for the tables, you'll have to add yourself. You can do so by
  replaceing all "pref_" with whatever prefix you desire.
*/

/* Forum Part */
CREATE TABLE `pref_categories` (
  `cid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `order` int(2) DEFAULT 0,
  `enabled` tinyint(1) DEFAULT 1
  PRIMARY KEY (`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `pref_topics` (
  `tid` int(11) NOT NULL AUTO_INCREMENT,
  `categoryId` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT 1,
  `order` int(2) DEFAULT 0
  PRIMARY KEY (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `pref_threads` (
  `tid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `topicId` int(11) DEFAULT NULL,
  `authorId` int(11) DEFAULT NULL,
  `dateCreated` datetime DEFAULT NULL,
  `lastEdited` datetime DEFAULT NULL,
  `sticky` tinyint(1) DEFAULT NULL,
  `closed` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `pref_posts` (
  `pid` int(11) NOT NULL AUTO_INCREMENT,
  `post_content_html` longtext,
  `post_content_text` longtext,
  `authorId` int(11) DEFAULT NULL,
  `threadId` int(11) DEFAULT NULL,
  `postDate` datetime DEFAULT NULL,
  `editDate` datetime DEFAULT NULL,
  PRIMARY KEY (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/* Users Part */
CREATE TABLE `pref_users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `group` int(11) DEFAULT NULL,
  `regip` varchar(255) DEFAULT NULL,
  `lastip` varchar(255) DEFAULT NULL,
  `regdate` datetime DEFAULT NULL,
  `lastlogindate` datetime DEFAULT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `pref_groups` (
  `gid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `order` int(2) DEFAULT NULL,
  PRIMARY KEY (`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `pref_permissions` (
  `pid` int(11) NOT NULL AUTO_INCREMENT,
  `groupId` int(11) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `categoryId` int(11) DEFAULT NULL,
  `topicId` int(11) DEFAULT NULL,
  `threadId` int(11) DEFAULT NULL,
  `read` tinyint(4) DEFAULT NULL,
  `post` tinyint(4) DEFAULT NULL,
  `mod` tinyint(4) DEFAULT NULL,
  `admin` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
