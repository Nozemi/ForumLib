<?php
    namespace SBLib\Database;

    use SBLib\Utilities\Logger;
    use \PDO;

    class DBUtilQuery {
        protected $name;
        protected $query;
        protected $parameters = array();
        protected $multipleRows = true;

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

        public function setMultipleRows($trueFalse = false) {
            $this->multipleRows = $trueFalse;
            return $this;
        }

        public function getMultipleRows() {
            return $this->multipleRows;
        }

        public function setParameters($parameters) {
            foreach($parameters as $parameter) {
                if(!isset($parameter[0]) || !isset($parameter[1])) {
                    new Logger('Parameters weren\'t supplied correctly. See DBUtilQuery::addParameter() method.', Logger::ERROR, __FILE__, __LINE__);
                    return false;
                }

                if(isset($parameter[2])) {
                    $this->addParameter($parameter[0], $parameter[1], $parameter[2]);
                } else {
                    $this->addParameter($parameter[0], $parameter[1]);
                }
            }

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

            return $this;
        }

        public function result() {
            if($this->db_util instanceof DBUtil) {
                return $this->db_util->getResultByName($this->getName());
            } else {
                new Logger('You need to provide the DBUtil class in order to execute the query.', Logger::WARNING, __FILE__, __LINE__);
                return false;
            }
        }
    }