<?php
  namespace NozLib\Utilities;

  class Config {
    public $configDir;
    public $config;

    private $lastError;

    public function __construct($cnfDir = 'config') {
      $this->configDir = $MISC::findFile($cnfDir);
      if(!file_exists($this->configDir)) {
        $this->lastError = 'Config directory wasn\'t found.';
        return false;
      }

      $this->config = array();
      foreach(glob($this->configDir . '*.conf.json') as $file) {
        $this->config[basename($file,'.conf.json')] = json_decode(file_get_contents($file), true);
      }
    }

    public function getLastError() {
      return $this->lastError;
    }
  }
