<?php
  namespace ForumLib\Utilities;

  class Installer {

    private $S;

    private $lastError = array();
    private $lastMessage = array();

    /*
      This is how to use the installer, by passing an array with the following keys:
        $install = array(
          'forum' => true/false,
          'blogg' => true/false
        )
    */
    public function __construct(PSQL $SQL, $install) {
      // Let's check if the $SQL is not a null.
      if(!is_null($SQL)) {
        $this->S = $SQL;
      } else {
        $this->lastError[] = 'Something went wrong while creating the installer object.';
        return false;
      }

      if(is_array($install)) {
        if(isset($install['forum'])) {
          $this->installForum(); // Will install everything required for the forum.
        }
        if(isset($install['blogg'])) {
          $this->installBlog(); // Will install everything required for the blog.
        }
      } else {
        $this->lastError[] = 'Something went wrong while installing. Did you specify what to install?';
        return false;
      }
    }

    private function installForum() {
      $this->installUsers();
      $this->installGroups();
      $this->installForumPermissions();

      // Rest of the forum installation.
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        CREATE TABLE `{{DBP}}categories` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `title` varchar(255) DEFAULT NULL,
          `description` varchar(255) DEFAULT NULL,
          `order` int(2) DEFAULT 0,
          `enabled` tinyint(1) DEFAULT 1
          PRIMARY KEY (`cid`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;

        CREATE TABLE `{{DBP}}topics` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `categoryId` int(11) DEFAULT NULL,
          `title` varchar(255) DEFAULT NULL,
          `description` varchar(255) DEFAULT NULL,
          `enabled` tinyint(1) DEFAULT 1,
          `order` int(2) DEFAULT 0
          PRIMARY KEY (`tid`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;

        CREATE TABLE `{{DBP}}threads` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `title` varchar(255) DEFAULT NULL,
          `topicId` int(11) DEFAULT NULL,
          `authorId` int(11) DEFAULT NULL,
          `dateCreated` datetime DEFAULT NULL,
          `lastEdited` datetime DEFAULT NULL,
          `sticky` tinyint(1) DEFAULT NULL,
          `closed` tinyint(1) DEFAULT NULL,
          PRIMARY KEY (`tid`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;

        CREATE TABLE `{{DBP}}posts` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `post_content_html` longtext,
          `post_content_text` longtext,
          `authorId` int(11) DEFAULT NULL,
          `threadId` int(11) DEFAULT NULL,
          `postDate` datetime DEFAULT NULL,
          `editDate` datetime DEFAULT NULL,
          PRIMARY KEY (`pid`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
      "));
      if($this->S->executeQuery()) {
        $this->lastMessage[] = 'Forum was successfully installed.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError = $this->S->getLastError();
        } else {
          $this->lastError = 'Something went wrong while installing forum.';
        }
        return false;
      }
    }

    private function installBlog() {
      $this->installUsers();
      $this->installBlogPermissions();

      // Rest of the blogg installation.
    }

    private function installUsers() {
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        CREATE TABLE `{{DBP}}users` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
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
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
      "));
      if($this->S->executeQuery()) {
        $this->lastMessage[] = 'Users were successfully installed.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while installing users.';
        }
        return false;
      }
    }

    private function installGroups() {
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        CREATE TABLE `{{DBP}}groups` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `title` varchar(255) DEFAULT NULL,
          `desc` varchar(255) DEFAULT NULL,
          `order` int(2) DEFAULT NULL,
          PRIMARY KEY (`gid`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
      "));
      if($this->S->executeQuery()) {
        $this->lastMessage[] = 'Groups were successfully installed.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while installing groups.';
        }
        return false;
      }
    }

    private function installForumPermissions() {
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        CREATE TABLE `{{DBP}}permissions` (
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
          PRIMARY KEY (`pid`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
      "));
      if($this->S->executeQuery()) {
        $this->lastMessage[] = 'Forum permissions were successfully installed.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while installing forum permissions.';
        }
        return false;
      }
    }

    private function installBlogPermissions() {
      // Placeholder for now. We'll see what to do about this later on.
    }

    public function getLastError() {
      return end($this->lastError);
    }

    public function getLastMessage() {
      return end($this->lastMessage);
    }

    public function getErrors() {
      return $this->lastError;
    }

    public function getMessages() {
      return $this->lastMessage;
    }
  }
