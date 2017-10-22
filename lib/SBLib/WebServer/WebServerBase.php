<?php
    namespace SBLib\WebServer;

    use SBLib\Utilities\Config;

    abstract class WebServerBase {
        protected $config;
        protected $themeDirectory;

        public function __construct() {
            $this->config = new Config;
            $this->themeDirectory = $this->config->getConfigValue('rootDirectory', '/') . '/themes/';
        }

        /**
         * Create Web Server Config File
         * Generate the configs for the respective server. (For instance the .htaccess file for Apache with URL rewriting)
         *
         * @param $directory
         * @param array $options
         *
         * @return mixed
         */
        abstract public function createConfigs($directory, array $options);
        abstract public function validateThemesFolders();
        abstract public function validateConfigFolders();
        abstract public function configGenerator();
    }