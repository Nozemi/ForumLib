<?php
  namespace ForumLib\Users;

  use ForumLib\Database\PSQL;

  use ForumLib\Integration\Nozum\NozumGroup;
  use ForumLib\Integration\vB3\vB3Group;
  use ForumLib\Utilities\Config;

  class Group {
    public $id;
    public $name;
    public $description;
    public $banned;
    public $admin;

    private $S;

    private $integration;

    private $lastError = array();
    private $lastMessage = array();

    public function __construct(PSQL $SQL) {
      // Let's check if the $SQL is not a null.
      if(!is_null($SQL)) {
        $this->S = $SQL;
          $C = new Config;
          $this->config = $C->config;
          switch(array_column($this->config, 'integration')[0]) {
              case 'vB3':
                  $this->integration = new vB3Group($this->S);
                  break;
              case 'Nozum':
              default:
                  $this->integration = new NozumGroup($this->S);
                  break;
          }
      } else {
        $this->lastError[] = 'Something went wrong while creating the category object.';
        return false;
      }
    }

    public function getGroups() {
        return $this->integration->getGroups($this);
    }

    public function getGroup($_id) {
        return $this->integration->getGroup($_id, $this);
    }

    public function unsetSQL() {
      $this->S = null;
      return $this;
    }

    public function setId($_id) {
      $this->id = $_id;
      return $this;
    }

    public function setName($_name) {
      $this->name = $_name;
      return $this;
    }

    public function setDescription($_desc) {
      $this->description = $_desc;
      return $this;
    }

    public function setBanned($_banned) {
      $this->banned = $_banned;
      return $this;
    }

    public function setAdmin($_admin) {
      $this->admin = $_admin;
      return $this;
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
