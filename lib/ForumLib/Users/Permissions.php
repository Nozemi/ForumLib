<?php
  namespace ForumLib\Users;

  class Permissions {
    private $id;
    private $OI; // Object Instance.

    private $S; // PSQL Object Instance.

    private $lastError = array();
    private $lastMessage = array();

    private $type;

    public function __construct(PSQL $_SQL, $_id = null, $_OI) {
      // We'll check if the required parameters are filled.
      if(!is_null($_SQL)) {
        $this->S = $_SQL;
      } else {
        $this->lastError[] = 'Failed to make comment object.';
        return false;
      }

      if(!is_null($_id)) {
        $this->id = $_id;
      }
    }

    public function getPermissions($_id = null) {
      if(is_null($this->id) && !is_null($_id)) {
        $this->id = $_id;
      } else {
        $this->lastError[] = 'Something went wrong while getting permissions.';
        return false;
      }

      /*
        We'll need to know where to get the permissions from, wheter it's a category, topic or thread.
        To do this, we have the method getType() in those three objects, to tell us what the object is.
      */
      switch($this->OI->getType()) {
        case 'Forums\Thread':
          $this->type = 2;
          $query = "SELECT * FROM `{{DBP}}permissions` WHERE `threadId` = :id";
          break;
        case 'Forums\Topic':
          $this->type = 1;
          $query = "SELECT * FROM `{{DBP}}permissions` WHERE `topicId` = :id";
          break;
        case 'Forums\Category':
        default:
          $this->type = 0;
          $query = "SELECT * FROM `{{DBP}}permissions` WHERE `categoryId` = :id";
          break;
      }

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', $query));
      if($this->S->executeQuery(array(
        ':id' => $this->id
      ))) {

      } else {

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
