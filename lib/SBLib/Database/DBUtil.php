<?php
namespace SBLib\Database;

use SBLib\Utilities\Logger;

class DBUtil {
    const MySQL       = 0;
    const MsSQL       = 1;
    const SQLite      = 2;
    const PostgresSQL = 3;
    const Oracle      = 4;

    private $_connectionInfo;

    private $_errors = [];

    private $_queryQueue;
    private $_queryResults;

    /** @var  \PDO $_pdoConnection */
    private $_pdoConnection;

    /**
     *
     * @param object $details
     * @throws DBUtilException
     */
    public function __construct($details) {
        $this->_connectionInfo = (object) array('host' => 'localhost', 'name' => null, 'port' => 3306, 'user' => 'root', 'pass' => '', 'prefix' => '', 'type' => self::MySQL);

        foreach((object) $details as $key => $detail) {
            $this->_connectionInfo->$key = $detail;
        }

        if(!$this->isValid()) {
            new Logger('Invalid database details', Logger::ERROR, __CLASS__, __LINE__);
            throw new DBUtilException('Invalid database details.');
        }

        $this->initialize();
    }

    private function isValid() {
        if($this->_connectionInfo->name === null) {
            return false;
        }

        return true;
    }

    private function initialize() {
        switch($this->_connectionInfo->type) {
            case self::MySQL:
                $this->_pdoConnection = $this->newMySQLConnection();
                new Logger('MySQL connection initialized.', Logger::INFO, __CLASS__, __LINE__);
                break;
            default:
                new Logger('The type is not yet supported.', Logger::ERROR, __CLASS__, __LINE__);
                throw new DBUtilException('Unsupported database type.');
                break;
        }
    }

    public function isInitialized() {
        if($this->_pdoConnection instanceof \PDO) {
            if ($this->_pdoConnection->getAttribute(\PDO::ATTR_CONNECTION_STATUS)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return null|\PDO
     */
    private function newMySQLConnection() {
        $connection = null;

        try {
            $options = [
                \PDO::ATTR_TIMEOUT => 4
            ];

            $connection = new \PDO('mysql:host=' . $this->_connectionInfo->host . ';dbname=' . $this->_connectionInfo->name . ';charset=utf8', $this->_connectionInfo->name, $this->_connectionInfo->pass, $options);

            $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            $this->_queryQueue = array();
        } catch(\PDOException $exception) {
            $this->_errors[] = ['message' => $exception->getMessage(), 'code' => $exception->getCode()];
            new Logger($exception->getMessage(), Logger::ERROR, __CLASS__, __LINE__);
        }

        return $connection;
    }

    /**
     * @param String $query
     * @return String $query
     */
    private function replacePrefix($query) {
        $query = str_replace('{{PREFIX}}', $this->_connectionInfo->prefix, $query);
        $query = str_replace('{{PREF}}', $this->_connectionInfo->prefix, $query);
        $query = str_replace('{{DBP}}', $this->_connectionInfo->prefix, $query);

        return $query;
    }

    /**
     * Adds a query with it's parameters to the query queue.
     *
     * @param DBUtilQuery $query
     *
     * @return $this
     */
    public function addQuery(DBUtilQuery $query) {
        if($query->getName() !== null) {
            $this->_queryQueue[$query->getName()] = $query;
        } else {
            $this->_queryQueue[] = $query;
        }

        new Logger('Query added to the queue.', Logger::DEBUG, __CLASS__, __LINE__);
        return $this;
    }

    /**
     * Add an array of "DBUtilQuery"s to the query_queue.
     *
     * @param $queries
     * @return $this
     */
    public function addQueries($queries) {
        if(is_array($queries)) {
            foreach($queries as $query) {
                $this->addQuery($query);
            }

            new Logger('Queries added to the queue.', Logger::DEBUG, __CLASS__, __LINE__);
            return $this;
        }

        new Logger('Failed to add to the queue.', Logger::ERROR, __CLASS__, __LINE__);
        return $this;
    }

    public function getQueue() {
        return $this->_queryQueue;
    }

    public function runQueries() {
        foreach($this->_queryQueue as $query) {
            $this->executeQuery($query);
        }

        new Logger('The query queue have been run.', Logger::DEBUG, __CLASS__, __LINE__);
    }

    /**
     * @param DBUtilQuery $query
     * @return mixed
     */
    public function runQuery(DBUtilQuery $query) {
        $this->executeQuery($query);
        return $this;
    }

    public function runQueryByName($name) {
        $this->executeQuery($this->_queryQueue[$name]);
        return $this;
    }

    public function getConnection() {
        return $this->_pdoConnection;
    }

    private function executeQuery(DBUtilQuery $query) {
        $this->_queryResults = array();

        try {
            $statement = $this->_pdoConnection->prepare($this->replacePrefix($query->getQuery()));

            if (function_exists('get_magic_quotes') && get_magic_quotes_gpc()) {
                function undo_magic_quotes_gpc(&$array) {
                    foreach ($array as &$value) {
                        if (is_array($value)) {
                            undo_magic_quotes_gpc($value);
                        } else {
                            $value = stripslashes($value);
                        }
                    }
                }

                undo_magic_quotes_gpc($query['parameters']);
            }

            if(is_array($query->getParameters())) {
                foreach ($query->getParameters() as $parameter) {
                    $statement->bindParam($parameter['name'], $parameter['value'], (isset($parameter['type']) ? $parameter['type'] : \PDO::PARAM_STR));
                }
            }

            $statement->execute();

            if($query->getName() === null) {
                if($query->getMultipleRows()) {
                    $this->_queryResults[] = $statement->fetchAll();
                } else {
                    $this->_queryResults[] = $statement->fetch();
                }
            } else {
                if($query->getMultipleRows()) {
                    $this->_queryResults[$query->getName()] = $statement->fetchAll();
                } else {
                    $this->_queryResults[$query->getName()] = $statement->fetch();
                }
            }

            new Logger('Query [' . (($query->getName() !== null) ? $query->getName() : 'N/A') . '] ran successfully.', Logger::INFO, __CLASS__, __LINE__);
            return true;
        } catch(\PDOException $exception) {
            new Logger($exception->getMessage(), Logger::ERROR, __FILE__, __LINE__);
            return false;
        }
    }

    public function getResults() {
        return $this->_queryResults;
    }

    public function getResultByName($name) {
        if(!empty($this->_queryResults[$name])) {
            return $this->_queryResults[$name];
        }
    }

    public function getLastInsertId() {
        return $this->_pdoConnection->lastInsertId();
    }

    public function getLastError() {
        return end($this->_errors)['message'];
    }

    public function getLastErrorCode() {
        return end($this->_errors)['code'];
    }
}