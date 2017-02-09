<?php
  namespace NozLib\Utilities;

  class Config {
    public $configDirectory;
    public $config;

    private $lastError;

    public function __construct($cnfDir = 'config') {
      $this->configDirectory = MISC::findFile($cnfDir); // Finds the config directory.

      // Checks and handles the error upon config directory not existing.
      if(!file_exists($this->configDirectory)) {
        $this->lastError = 'Config directory wasn\'t found.';
        return false;
      }

      // Loads all the configs into an array.
      $this->config = array();
      foreach(glob($this->configDirectory . '*.conf.json') as $file) {
        $this->config[basename($file,'.conf.json')] = json_decode(file_get_contents($file), true);
      }
    }

    public function getLastError() {
      return $this->lastError;
    }
  }
