<?php
    namespace SBLib\Utilities;

    use SBLib\Utilities\MISC;

    class Config {
        public $configDirectory;
        public $config;

        private $lastError = array();
        private $lastMessage = array();

        /*
        Config object adds all the configuration variables from the config directory's
        config files. These files needs to be json, and end with .conf.json. For example: main.conf.json

        Where you put the config directory doesn't matter. You can specify it's path in the constructor.
        */
        public function __construct($cnfDir = 'config') {
            $this->configDirectory = MISC::findFile($cnfDir); // Finds the config directory.

            // Checks and handles the error upon config directory not existing.
            if(!file_exists($this->configDirectory)) {
                $this->lastError[] = 'Config directory wasn\'t found.';
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
                        $this->lastError[] = $ex->getMessage();
                    } else {
                        $this->lastError[] = 'Something went wrong during the config loading.';
                    }
                    return false;
                }
            }
        }

        /**
         * @return mixed
         */
        public function getConfigValue($key, $defaultValue = null) {
            $configVal = null;

            if(is_array($this->config)) {
                if (isset(array_column($this->config, $key)[0])) {
                    $configVal = array_column($this->config, $key)[0];
                } else if (!empty(array_column($this->config, $key))) {
                    $configVal = array_column($this->config, $key);
                }
            }

            if($configVal == null || $configVal == '') {
                return $defaultValue;
            }

            return $configVal;
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
