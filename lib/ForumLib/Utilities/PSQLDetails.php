<?php
  namespace ForumLib\Utilities;

  class PSQLDetails {
    private $dbuser; // Database Username
    private $dbpass; // Database Password
    private $dbhost; // Database Host
    private $dbname; // Database Name
    private $dbpref; // Database Prefix

    private $lastError = array();

    public function __construct($name, $user = 'root', $pass = '', $host = 'localhost', $prefix = '') {
      if(is_null($name) || $name == '') {
        $this->lastError = 'The database name cannot be empty.';
        return false;
      }

      $this->dbuser = $user;
      $this->dbpass = $pass;
      $this->dbhost = $host;
      $this->dbname = $name;
      $this->dbpref = $prefix;
    }

    public function getDetails() {
      return array(
        'dbhost' => $this->dbhost,
        'dbname' => $this->dbname,
        'dbuser' => $this->dbuser,
        'dbpass' => $this->dbpass,
        'dbpref' => $this->dbpref
      );
    }

    public function getLastError() {
      return end($this->lastError);
    }

    public function getErrors() {
      return $this->lastError;
    }
  }
