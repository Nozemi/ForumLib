<?php
namespace ForumLib\Database;

use ForumLib\Utilities\Logger;

class DBUtil {
    const MySQL      = 0;
    const MsSQL      = 1;
    const SQLite     = 2;
    const PostgreSQL = 3;
    const Oracle     = 4;

    private $connection_info;

    private $query_queue;
    private $query_results;

    /** @var  \PDO $pdo_connection */
    private $pdo_connection;

    /**
     * DBUtil constructor.
     * @param object $details
     * @throws DBUtilException
     */
    public function __construct($details = (object) array('host' => 'localhost', 'name' => null, 'port' => 3306, 'user' => 'root', 'pass' => '', 'prefix' => '', 'type' => self::MySQL)) {
        $this->connection_info->host    = (isset($details->host)    ? $details->host    : 'localhost');
        $this->connection_info->name    = (isset($details->name)    ? $details->name    : null);
        $this->connection_info->port    = (isset($details->port)    ? $details->port    : 3306);
        $this->connection_info->user    = (isset($details->user)    ? $details->user    : 'root');
        $this->connection_info->pass    = (isset($details->pass)    ? $details->pass    : '');
        $this->connection_info->prefix  = (isset($details->prefix)  ? $details->prefix  : '');
        $this->connection_info->type    = (isset($details->type)    ? $details->type    : self::MySQL);

        $this->connection_info = (object) $details;

        if(!$this->isValid()) {
            new Logger('Invalid database details', Logger::ERROR, __CLASS__, __LINE__);
            throw new DBUtilException('Invalid database details.');
        }

        $this->initialize();
    }

    private function isValid() {
        if($this->connection_info->name === null) {
            return false;
        }

        return true;
    }

    private function initialize() {
        switch($this->connection_info->type) {
            case self::MySQL:
                $this->pdo_connection = $this->newMySQLConnection();
                new Logger('MySQL connection initialized.', Logger::INFO, __CLASS__, __LINE__);
                break;
            default:
                new Logger('The type is not yet supported.', Logger::ERROR, __CLASS__, __LINE__);
                throw new DBUtilException('Unsupported database type.');
                break;
        }
    }

    public function isInitialized() {
        if($this->pdo_connection->getAttribute(\PDO::ATTR_CONNECTION_STATUS)) {
            return true;
        }

        return false;
    }

    /**
     * @return null|\PDO
     */
    private function newMySQLConnection() {
        $connection = null;

        try {
            $connection = new \PDO('mysql:host=' . $this->connection_info->host . ';dbname=' . $this->connection_info->name . ';charset=utf8', $this->connection_info->name, $this->connection_info->pass);

            $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            $this->query_queue = array();
        } catch(\PDOException $exception) {
            new Logger($exception->getMessage(), Logger::ERROR, __CLASS__, __LINE__);
        }

        return $connection;
    }

    /**
     * @param String $query
     * @return String $query
     */
    private function replacePrefix($query) {
        $query = str_replace('{{PREFIX}}', $this->connection_info->prefix, $query);
        $query = str_replace('{{PREF}}', $this->connection_info->prefix, $query);
        $query = str_replace('{{DBP}}', $this->connection_info->prefix, $query);

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
            $this->query_queue[$query->getName()] = $query;
        } else {
            $this->query_queue[] = $query;
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
        foreach($queries as $query) {
            $this->addQuery($query);
        }

        new Logger('Queries added to the queue.', Logger::DEBUG, __CLASS__, __LINE__);
        return $this;
    }

    public function getQueue() {
        return $this->query_queue;
    }

    public function runQueries() {
        foreach($this->query_queue as $query) {
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
        $this->executeQuery($this->query_queue[$name]);
        return $this;
    }

    public function getConnection() {
        return $this->pdo_connection;
    }

    private function executeQuery(DBUtilQuery $query) {
        $this->query_results = array();

        try {
            $statement = $this->pdo_connection->prepare($this->replacePrefix($query->getQuery()));

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
                    $this->query_results[] = $statement->fetchAll();
                } else {
                    $this->query_results[] = $statement->fetch();
                }
            } else {
                if($query->getMultipleRows()) {
                    $this->query_results[$query->getName()] = $statement->fetchAll();
                } else {
                    $this->query_results[$query->getName()] = $statement->fetch();
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
        return $this->query_results;
    }

    public function getResultByName($name) {
        return $this->query_results[$name];
    }

    public function getLastInsertId() {
        return $this->pdo_connection->lastInsertId();
    }
}