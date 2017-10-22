<?php
    namespace SBLib\Utilities;

    class Config {
        public $configDirectory;
        public $config;

        private $_lastError = array();
        private $_lastMessage = array();

        /*
        Config object adds all the configuration variables from the config directory's
        config files. These files needs to be json, and end with .conf.json. For example: main.conf.json

        Where you put the config directory doesn't matter. You can specify it's path in the constructor.
        */
        public function __construct($configDirectory = null) {
            if($configDirectory == null) {
                $configDirectory = 'config';
            }

            $this->configDirectory = MISC::findDirectory($configDirectory); // Finds the config directory.

            // Checks and handles the error upon config directory not existing.
            if(!file_exists($this->configDirectory)) {
                $this->_lastError[] = 'Config directory wasn\'t found.';
                return false;
            }

            // Let's verify that the config folder has a .htacccess file, and that it blocks any remote
            // connections to access the config files. This is extremely critical to security, at least
            // if database credentials and info is stored in those.
            $this->ensureSecureConfigs();

            // Loads all the configs into an array.
            $this->config = array();
            foreach(glob($this->configDirectory . '/*.conf.json') as $file) {
                $this->config[basename($file,'.conf.json')] = json_decode(file_get_contents($file), true);
            }
        }

        private function ensureSecureConfigs() {
            // Let's check whether or not the .htaccess file is present.
            if(!file_exists($this->configDirectory . '/.htaccess')) {
                try {
                    // Open and write to the file if it doesn't exist.
                    $accessFile = fopen($this->configDirectory . "/.htaccess", "w");
                    $text = "Order deny,allow\nDeny from all\nAllow from 127.0.0.1";
                    fwrite($accessFile, $text);
                    fclose($accessFile);
                } catch(\Exception $ex) {
                    // Catch the error (if any) when attempting to create the file.
                    if(defined('DEBUG')) {
                        $this->_lastError[] = $ex->getMessage();
                    } else {
                        $this->_lastError[] = 'Something went wrong during the config loading.';
                    }
                    return false;
                }
            }
        }

        /**
         * @deprecated - Use Config::get instead.
         * @param $name
         * @param null $default
         * @param bool $file
         * @return mixed
         */
        public function getConfigValue($name, $default = null, $file = null) {
            return self::get($name, $default, $file);
        }

        /**
         * Gets a config value from the config array.
         *
         * @param string $name
         * @param string $file
         * @param mixed $default
         *
         * @return mixed
         */
        public function get($name, $default = null, $file = null) {
            $configVal = null;

            if(is_array($this->config)) {
                if($file === null) {
                    if(count(array_column($this->config, $name)) > 0) {
                        $configVal = array_column($this->config, $name)[0];
                    } else {
                        $configVal = array_column($this->config, $name);
                    }
                } else {
                    if(count(array_column($this->config->$file, $name)) > 0) {
                        $configVal = array_column($this->config->$file, $name)[0];
                    } else {
                        $configVal = array_column($this->config->$file, $name);
                    }
                }
            }

            if(empty($configVal)) {
                return $default;
            }

            return $configVal;
        }

        public function getLastError() {
            return end($this->_lastError);
        }

        public function getLastMessage() {
            return end($this->_lastMessage);
        }

        public function getErrors() {
            return $this->_lastError;
        }

        public function getMessages() {
            return $this->_lastMessage;
        }
    }
