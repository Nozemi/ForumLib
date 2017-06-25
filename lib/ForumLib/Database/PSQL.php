<?php

namespace ForumLib\Database;

use \PDO;
use \PDOException;

/**
 * Class PSQL
 * @package ForumLib\Database
 *
 * @deprecated use /ForumLib/Database/DBUtil
 */
class PSQL {
    // Database Details - Filled with default information.
    private $dbuser;
    private $dbpass;
    private $dbhost;
    private $dbname;
    private $dbpref;

    // The PDO connection object.
    private $db;

    // Query Details
    private $statement;
    private $result;

    private $lastError = array();
    private $lastMessage = array();

    /**
     * PSQL constructor.
     *
     * @param $details
     */
    // MySQL Database Operation Object Constructor.
    public function __construct($details) {
        // Take constructor parameters, and update the database details.
        $this->dbuser = $details['dbuser'];
        $this->dbpass = $details['dbpass'];
        $this->dbhost = $details['dbhost'];
        $this->dbname = $details['dbname'];
        $this->dbpref = $details['dbpref'];

        if ($this->open()) {
            return true;
        } else {
            return false;
        }
    }

    // Open Database Connection.

    /**
     * @return bool
     * @deprecated
     */
    public function open() {
        // Check if database details are set.
        if (!is_null($this->dbuser) || !is_null($this->dbpass) || !is_null($this->dbname) || !is_null($this->dbhost)) {
            // Set character encoding to UTF-8.
            //$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8');

            try {
                // Try to open the connection.
                $dbTemp = new PDO('mysql:host=' . $this->dbhost . ';dbname=' . $this->dbname . ';charset=utf8', $this->dbuser, $this->dbpass);

                $dbTemp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);       // Make PDO throw exceptions.
                $dbTemp->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);  // Sets the default fetch style. Currently using ASSOC. (ASSOC = returns an array indexed by column name as returned in your result set)

                $this->db = $dbTemp;

                // If there is any result already, we'll be clearing that.
                $this->result = null;
                $this->statement = null;

                $this->lastMessage[] = 'Database connected successfully.';
                return true;
            } catch (PDOException $ex) {
                // Handle PDO error. In this case the PDO connection error.
                if (defined('DEBUG')) {
                    $this->lastError[] = $ex->getMessage();
                } else {
                    $this->lastError[] = 'Something went wrong while connecting to the database.';
                }
                return false;
            }
        } else {
            // Handle the error upon either username, password, host or name being null.
            $this->lastError[] = 'Missing database details.';
            return false;
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

    /**
     * @param $query
     * @return bool
     * @deprecated
     */
    public function prepareQuery($query) {
        if (is_null($this->db) && !$this->db instanceof PDO) {
            $this->lastError[] = 'No database connection.';
            return false;
        } else if ($this->db instanceof PDO) {
            try {
                $this->statement = $this->db->prepare($query);

                return true;
            } catch (PDOException $ex) {
                // Handle prepare exception.
                if (defined('DEBUG')) {
                    $this->lastError[] = $ex->getMessage();
                } else {
                    $this->lastError[] = 'The database is having issues. Please try again.';
                }

                return false;
            }
        }
    }

    // Execute with the query parameters (if any).

    /**
     * @param null $params
     * @return bool
     * @deprecated
     */
    public function executeQuery($params = null) {
        if (is_null($this->db)) {
            $this->lastError[] = 'No database connection.';
            return false;
        }
        if (!is_null($this->statement)) {
            try {
                /** - Let's figure out this part some time later.
                 * /**/
                if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
                    /**/
                    function undo_magic_quotes_gpc(&$array) {
                        /**/
                        foreach ($array as &$value) {
                            /**/
                            if (is_array($value)) {
                                /**/
                                undo_magic_quotes_gpc($value);
                                /**/
                            } else {
                                /**/
                                $value = stripslashes($value);
                                /**/
                            }
                            /**/
                        }
                        /**/
                    }

                    /**/
                    undo_magic_quotes_gpc($params);
                    /**/
                }

                $this->result = $this->statement->execute($params);
                $this->lastMessage[] = 'Query ran successfully.';
                return true;
            } catch (PDOException $ex) {
                // Handle result exception.
                if (defined('DEBUG')) {
                    $this->lastError[] = $ex->getMessage();
                } else {
                    $this->lastError[] = 'The database is having issues. Please try again.';
                }
                return false;
            }
        }
    }

    // Fetch single row from the query.

    /**
     * @return bool
     * @deprecated
     */
    public function fetch() {
        if (!is_null($this->statement)) {
            try {
                return $this->statement->fetch();
            } catch (PDOException $ex) {
                if (defined('DEBUG')) {
                    $this->lastError[] = $ex->getMessage();
                } else {
                    $this->lastError[] = 'Something went wrong while trying to fetch data.';
                }
            }
        } else {
            $this->lastError[] = 'There is nothing to fetch.';
            return false;
        }
    }

    // Fetch all queryies from the query.

    /**
     * @return bool
     * @deprecated
     */
    public function fetchAll() {
        if (!is_null($this->statement)) {
            return $this->statement->fetchAll();
        } else {
            $this->lastError[] = 'There is nothing to fetch.';
            return false;
        }
    }

    public function getLastInsertId() {
        return $this->db->lastInsertId();
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
