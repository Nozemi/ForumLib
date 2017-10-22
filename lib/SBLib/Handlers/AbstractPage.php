<?php
    namespace SBLib\Handlers;

    use SBLib\ThemeEngine\MainEngine;
    use SBLib\Utilities\Config;

    use SBLib\Database\DBUtil;
    use SBLib\Database\DBUtilException;

    abstract class AbstractPage {
        protected $_validParams;
        protected $_params;

        protected $_themeEngine;
        protected $_config;

        protected $_plugins;

        private $_dbUtil;

        public function __construct ($params, $configDirectory = null) {
            $paramArray = explode('/', $params, count($this->_validParams));

            $this->_params = (object) [];
            foreach($this->_validParams as $key => $value) {
                if(count($paramArray) > $key) {
                    $this->_params->$value = $paramArray[$key];
                }
            }

            $this->_config = new Config($configDirectory);
            try {
                $this->_dbUtil = new DBUtil((object) array(
                    'host'   => $this->_config->getConfigValue('dbHost'),
                    'port'   => $this->_config->getConfigValue('dbPort'),
                    'name'   => $this->_config->getConfigValue('dbName'),
                    'user'   => $this->_config->getConfigValue('dbUser'),
                    'pass'   => $this->_config->getConfigValue('dbPass'),
                    'prefix' => $this->_config->getConfigValue('dbPrefix')
                ));
            } catch(DBUtilException $exception) {
                echo $exception->getMessage();
            }

            if($this->_dbUtil instanceof DBUtil) {
                $this->_themeEngine = new MainEngine($this->_dbUtil);
            }
        }

        public function getDBUtil() {
            return $this->_dbUtil;
        }

        public function setDBUtil(DButil $_dbUtil) {
            $this->_dbUtil = $_dbUtil;
            return $this;
        }

        public function registerPlugin(object $plugin) {
            $this->_plugins[$plugin->name] = $plugin;
        }
    }