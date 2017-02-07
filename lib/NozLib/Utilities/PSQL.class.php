<?php
  namespace NozLib\Utilities;

  class PSQL
  {
    // Database Details - Filled with default information.
    private $dbuser;
    private $dbpass;
    private $dbhost;
    private $dbname;
    private $dbpref;

    // The PDO connection object.
    private $db;

    // Query Details
    private $statment;
    private $result;

    private $debug;

    // MySQL Database Operation Object Constructor.
    public function __construct($user, $pass, $name, $host, $pref, $dbg = false) {
      // Take constructor parameters, and update the database detials.
      $this->dbuser = $user;
      $this->dbpass = $pass;
      $this->dbhost = $host;
      $this->dbname = $name;
      $this->dbpref = $pref;
      $this->debug  = $dbg;
    }

    // Open Database Connection.
    public function open() {
      // Check if database details are set.
      if(!is_null($this->dbuser) || !isnull($this->dbpass) || !isnull($this->dbname) || !isnull($this->dbhost)) {
        // Set character encoding to UTF-8.
        $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8');

        try {
          // Try to open the connection.
          $dbTemp = new PDO('mysql:host=' . $this->dbhost . ';dbname=' . $this->dbname . ';charset=utf8', $this->dbuser, $this->dbpass, $options);

          $dbTemp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);       // Make PDO throw exceptions.
          $dbTemp->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);  // Sets the default fetch style. Currently using ASSOC. (ASSOC = returns an array indexed by column name as returned in your result set)

          $this->db = $dbTemp;

          // If there is any result already, we'll be clearing that.
          $this->result = null;
          $this->statement = null;
        } catch(PDOException $ex) {
          // Handle PDO error. In this case the PDO connection error.
          if($this->debug) {
            return $ex->getMessage();
          } else {
            return 'Something went wrong while connecting to the database.';
          }
        }
      } else {
        // Handle the error upon either username, password, host or name being null.
        return 'Missing database credentials and/or details.';
      }
    }

    // Close Database Connection.
    public function close() {
      $this->db = null;

      $this->dbuser = null;
      $this->dbname = null;
      $this->dbpass = null;
      $this->dbpref = null;
      $this->dbhost = null;

      $this->result = null;
    }

    public function replacePrefix($pHolder, $query) {
      return str_replace($pHolder, $this->dbpref, $query); // Replaces the prefix placeholder.
    }

    // Prepares the query for execution.
    public function prepareQuery($query) {
      try {
        $this->statement = $this->db->prepare($query);
      } catch(PDOException $ex) {
        // Handle prepare exception.
        if($this->debug) {
          return $ex->getMessage();
        } else {
          return 'The database is having issues. Please try again.';
        }
      }
    }

    // Execute with the query parameters (if any).
    public function queryResult($params = null) {
      if(!is_null($this->statement)) {
        try {
          $this->result = $this->statement->execute($params);
        } catch(PDOException $ex) {
          // Handle result exception.
          if($this->debug) {
            return $ex->getMessage();
          } else {
            return 'The database is having issues. Please try again.';
          }
        }
      }
    }

    // Fetch single row from the query.
    public function fetch() {
      if(!is_null($this->statement)) {
        return $this->statement->fetch();
      } else {
        return 'There is nothing to fetch.';
      }
    }

    // Fetch all queryies from the query.
    public function fetchAll() {
      if(!is_null($this->statement)) {
        return $this->statement->fetchAll();
      } else {
        return 'There is nothing to fetch.';
      }
    }
  }
