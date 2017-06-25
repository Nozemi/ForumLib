<?php
    namespace ForumLib\Database;

    use ForumLib\Utilities\Logger;

    class DBUtil {
        const MySQL      = 0;
        const MsSQL      = 1;
        const SQLite     = 2;
        const PostgreSQL = 3;
        const Oracle     = 4;

        private $database_host;
        private $database_port;
        private $database_name;
        private $database_user;
        private $database_pass;
        private $database_prefix;
        private $database_type;

        private $query_queue;
        private $query_results;

        /** @var  \PDO $pdo_connection */
        private $pdo_connection;

        /**
         * DBUtil constructor.
         * @param array $details
         * @throws DBUtilException
         */
        public function __construct($details = array('host' => 'localhost', 'name' => null, 'port' => 3306, 'user' => 'root', 'pass' => '', 'prefix' => '', 'type' => self::MySQL)) {
            $this->database_host   = (isset($details['host'])   ? $details['host']   : 'localhost');
            $this->database_name   = (isset($details['name'])   ? $details['name']   : null);
            $this->database_port   = (isset($details['port'])   ? $details['port']   : 3306);
            $this->database_user   = (isset($details['user'])   ? $details['user']   : 'root');
            $this->database_pass   = (isset($details['pass'])   ? $details['pass']   : '');
            $this->database_prefix = (isset($details['prefix']) ? $details['prefix'] : '');
            $this->database_type   = (isset($details['type'])   ? $details['type']   : self::MySQL);

            if(!$this->isValid()) {
                new Logger('Invalid database details', Logger::ERROR, __CLASS__, __LINE__);
                throw new DBUtilException('Invalid database details.');
            }

            $this->initialize();
        }

        private function isValid() {
            if($this->database_name === null) {
                return false;
            }

            return true;
        }

        private function initialize() {
            switch ($this->database_type) {
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
                $connection = new \PDO('mysql:host=' . $this->database_host . ';dbname=' . $this->database_name . ';charset=utf8', $this->database_user, $this->database_pass);

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
            $query = str_replace('{{PREFIX}}', $this->database_prefix, $query);
            $query = str_replace('{{PREF}}', $this->database_prefix, $query);
            $query = str_replace('{{DBP}}', $this->database_prefix, $query);

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
        }

        public function runQueryByName($name) {
            $this->executeQuery($this->query_queue[$name]);
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

                foreach ($query->getParameters() as $parameter) {
                    $statement->bindParam($parameter['name'], $parameter['value'], (isset($parameter['type']) ? $parameter['type'] : \PDO::PARAM_STR));
                }

                $statement->execute();

                if($statement->rowCount() > 1) {
                    if($query->getName() === null) {
                        $this->query_results[] = $statement->fetch();
                    } else {
                        $this->query_results[$query->getName()] = $statement->fetch();
                    }
                } else if($statement->rowCount() == 1) {
                    if($query->getName() === null) {
                        $this->query_results[] = $statement->fetchAll();
                    } else {
                        $this->query_results[$query->getName()] = $statement->fetchAll();
                    }
                } else {
                    if($query->getName() === null) {
                        $this->query_results[] = 'Query ran successfully, but nothing was returned.';
                    } else {
                        $this->query_results[$query->getName()] = 'Query ran successfully, but nothing was returned.';
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
            return end($this->query_results[$name]);
        }

        public function getLastInsertId() {
            return $this->pdo_connection->lastInsertId();
        }
    }