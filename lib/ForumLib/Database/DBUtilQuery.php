<?php
    namespace ForumLib\Database;

    use ForumLib\Utilities\Logger;
    use \PDO;

    class DBUtilQuery {
        protected $name;
        protected $query;
        protected $parameters;

        protected $db_util;

        public function setName($name) {
            $this->name = $name;
            return $this;
        }

        public function getName() {
            return $this->name;
        }

        public function setQuery($query) {
            $this->query = $query;
            return $this;
        }

        public function getQuery() {
            return $this->query;
        }

        public function setParameters($parameters) {
            $this->parameters = $parameters;
            return $this;
        }

        public function addParameter($name, $value, $type = PDO::PARAM_STR) {
            $this->parameters[] = array(
                'name'  => $name,
                'value' => $value,
                'type'  => $type
            );
            return $this;
        }

        public function getParameters() {
            return $this->parameters;
        }

        public function setDBUtil(DBUtil $db_util) {
            $this->db_util = $db_util;
            return $this;
        }

        public function execute() {
            if($this->db_util instanceof DBUtil) {
                $this->db_util->runQuery($this);
            } else {
                new Logger('You need to provide the DBUtil class in order to execute the query.', Logger::WARNING, __FILE__, __LINE__);
                return false;
            }

            return true;
        }
    }