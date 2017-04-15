<?php
  namespace ForumLib\Users;

  use ForumLib\Utilities\PSQL;

  class Group {
    public $id;
    public $name;
    public $description;
    public $banned;

    private $S;

    private $lastError = array();
    private $lastMessage = array();

    public function __construct(PSQL $SQL) {
      // Let's check if the $SQL is not a null.
      if(!is_null($SQL)) {
        $this->S = $SQL;
      } else {
        $this->lastError[] = 'Something went wrong while creating the category object.';
        return false;
      }
    }

    public function getGroup($_id) {
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        SELECT * FROM `{{DBP}}groups` WHERE `id` = :id
      "));
      if($this->S->executeQuery(array(
        ':id' => $_id
      ))) {
        $gR = $this->S->fetch();

        $group = new Group($this->S);
        $group->setId($gR['id'])
          ->setDescription($gR['description'])
          ->setName($gR['name'])
          ->setBanned($gR['banned'])
          ->unsetSQL();

        $this->lastMessage[] = 'Successfully loaded group.';
        return $group;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while getting group.';
        }
        return false;
      }
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
