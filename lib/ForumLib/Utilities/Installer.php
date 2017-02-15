<?php
  namespace ForumLib\Utilities;

  class Installer {

    private $S;

    private lastError;
    private lastMessage;

    public function __constructor(PSQL $SQL, $install) {
      // Let's check if the $SQL is not a null.
      if(!is_null($SQL)) {
        $this->S = $SQL;
      } else {
        $this->lastError = 'Something went wrong while creating the installer object.';
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
        $this->lastError = 'Something went wrong while installing. Did you specify what to install?';
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

    }

    private function installForumPermissions() {
    }

    private function installBlogPermissions() {

    }
  }
