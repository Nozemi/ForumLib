<?php
    namespace SBLib\TemplatingEngine;

    use SBLib\Database\DBUtil;
    use SBLib\Database\DBUtilQuery;
    use SBLib\Utilities\Config;

    abstract class AbstractElement {
        protected $_DBUtil;
        protected $_Config;

        protected $_initializer;
        protected $_templates;

        public function __construct() {
            if($GLOBALS['Config'] instanceof Config) {
                $this->_Config = $GLOBALS['Config'];
            } else {
                $this->_Config = new Config;
            }

            if($GLOBALS['DBUtil'] instanceof DBUtil) {
                $this->_DBUtil = $GLOBALS['DBUtil'];
            } else {
                if(!empty($this->_Config->get('dbName'))) {
                    $dbDetails = [];
                    foreach($this->_Config->getAll('database') as $key => $value) {
                        $dbDetails[strtolower(str_replace('db', '', $key))] = $value;
                    }
                    $this->_DBUtil = new DBUtil((object) $dbDetails);
                }
            }

            $this->_initializer = $this->getInitializer();

            $getAllowed = new DBUtilQuery();
            $getAllowed->setName('getAllowed')
                ->setQuery("
                    SELECT
                        `allowedType`
                    FROM `{[PREFIX}}template_elements`
                    WHERE `type` = :elementType;
                ")
                ->addParameter(':elementType', $this->_initializer)
                ->execute()
                ->result();

            $getTemplates = new DBUtilQuery();
            $getTemplates->setName('getTemplates')
                ->setQuery("
                    SELECT
                        `templateFile`
                    FROM `{{PREFIX}}template_elements`
                    WHERE `type` = :elementType;
                ")
                ->addParameter(':elementType', $this->_initializer)
                ->execute()
                ->result();

            print_r($getTemplates);
            print_r($getAllowed);
        }

        abstract protected function getInitializer();
    }
