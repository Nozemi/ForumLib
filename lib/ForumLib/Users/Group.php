<?php
  namespace ForumLib\Users;

  use ForumLib\Database\PSQL;

  class Group {
    public $id;
    public $name;
    public $description;
    public $banned;
    public $admin;

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

    public function getGroups() {
        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
            SELECT * FROM `{{DBP}}groups`
        "));

        if($this->S->executeQuery()) {
            $gRps = $this->S->fetchAll();

            $groups = array();
            foreach($gRps as $group) {
                $gR = new Group($this->S);
                $groups[] = $gR->getGroup($group['id']);
            }

            return $groups;
        } else {
            if(defined('DEBUG')) {
                $this->lastError[] = $this->S->getLastError();
            } else {
                $this->lastError[] = 'Something went wrong while getting groups.';
            }
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

        if(empty($gR)) {
            $this->lastError[] = 'Failed to get group.';
            return false;
        }

        $group = new Group($this->S);
        $group->setId($gR['id'])
          ->setDescription($gR['desc'])
          ->setName($gR['title'])
          ->setAdmin($gR['admin'])
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
