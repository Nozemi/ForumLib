<?php
  namespace NozLib\Utilities;

  class PSQLDetails {
    private $dbuser; // Database Username
    private $dbpass; // Database Password
    private $dbhost; // Database Host
    private $dbname; // Database Name
    private $dbpref; // Database Prefix

    private $lastError;

    public function __construct($user = 'root', $pass = '', $host = 'localhost', $name, $prefix = '') {
      if(is_null($name)) {
        $this->lastError = 'The database name cannot be null.';
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
        'dbpass' => $this->dbname,
        'dbpref' => $this->dbpref
      );
    }

    public function getLastError() {
      return $this->lastError;
    }
  }
