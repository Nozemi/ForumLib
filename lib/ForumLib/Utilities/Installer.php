<?php
  namespace ForumLib\Utilities;

  class Installer {

    private $S;

    private lastError = array();
    private lastMessage = array();

    public function __constructor(PSQL $SQL, $install) {
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
    }

    private function installBlog() {
      $this->installUsers();
      $this->installBlogPermissions();

      // Rest of the blogg installation.
    }

    private function installUsers() {

    }

    private function installGroups() {
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        CREATE TABLE `pref_groups` (
          `gid` int(11) NOT NULL AUTO_INCREMENT,
          `title` varchar(255) DEFAULT NULL,
          `desc` varchar(255) DEFAULT NULL,
          `order` int(2) DEFAULT NULL,
          PRIMARY KEY (`gid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
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
    }

    private function installBlogPermissions() {

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
