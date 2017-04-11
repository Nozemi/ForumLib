<?php
  namespace ForumLib\Utilities;

  class Language {
      private $lang;

      private $lastError = array();
      private $lastMessage = array();

      public function __construct($_lang = 'en_US', Config $config = null) {
          if($config instanceof Config) {
              $this->lang = MISC::findKey('lang', $config->config);
          } else {
              $this->lang = $_lang;
          }
      }

      public function getLanguage() {
          if(MISC::findFile('langs/' . $this->lang . '.json')) {
              $langFile = MISC::findFile('langs/' . $this->lang . '.json');

              if(file_exists($langFile)) {
                  return json_decode(file_get_contents($langFile), true);
              } else {
                  $this->lastError[] = 'Unable to load language';

                  $L = new self;
                  return $L->getLanguage();
              }
          } else {
              $this->lastError[] = 'Unable to load language.';
              return false;
          }
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
