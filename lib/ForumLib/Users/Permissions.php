<?php
  namespace ForumLib\Users;

  class Permissions {
    private $id;
    private $OI; // Object Instance.

    private $S; // PSQL Object Instance.

    private $lastError = array();
    private $lastMessage = array();

    private $type; // Helps us decide where to get the permissions from. Whether we're talking about a category, topic or thread.

    private $canRead;   // true/false - Decides whether or not a user can read the category/topic/thread.
    private $canPost;   // true/false - Decides whether or not a user can post in the category/topic/thread.
    private $canMod;    // true/false - Decides whether or not a user has moderation permissions in the category/topic/thread.
    private $canAdmin;  // true/false - Decides whether or not a user has administration permissions in the category/topic/thread.

    /*
      User spesific permissions will override any permissions defined on the usergroups.
      The user will also use the permissions from the highest ranking group that the user account is a member of.
    */
    private $userId;    // This is defined whenever this is a user spesific permission.
    private $groupId;   // This is defined whenever this is a group spesific permission.

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
        $this->lastError[] = 'Something went wrong while getting permissions. [1]';
        return false;
      }

      /*
        We'll need to know where to get the permissions from, wheter it's a category, topic or thread.
        To do this, we have the method getType() in those three objects, to tell us what the object is.
      */
      switch($this->OI->getType()) {
        case 'ForumLib\Forums\Thread':
          $this->type = 2;
          $query = "SELECT * FROM `{{DBP}}permissions` WHERE `threadId` = :id";
          break;
        case 'ForumLib\Forums\Topic':
          $this->type = 1;
          $query = "SELECT * FROM `{{DBP}}permissions` WHERE `topicId` = :id";
          break;
        case 'ForumLib\Forums\Category':
        default:
          $this->type = 0;
          $query = "SELECT * FROM `{{DBP}}permissions` WHERE `categoryId` = :id";
          break;
      }

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', $query));
      if($this->S->executeQuery(array(
        ':id' => $this->id
      ))) {
        $perms = $this->S->fetchAll(); // Let's get the query results.

        $this->canRead  = $perms['read'];
        $this->canPost  = $perms['post'];
        $this->canMod   = $perms['mod'];
        $this->canAdmin = $perms['admin'];

        if(is_null($perms['userId'])) {
          $this->userId   = null;
          $this->groupId  = $perms['groupId'];
        } else {
          $this->groupId  = null;
          $this->userId   = $perms['userId'];
        }

        $this->lastMessage[] = 'Successfully loaded permissions.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while getting permissions. [2]';
        }
        return false;
      }
    }

    public function canRead() {
      return $this->canRead;
    }

    public function canPost() {
      return $this->canPost;
    }

    public function canMod() {
      return $this->canMod;
    }

    public function canAdmin() {
      return $this->canAdmin;
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
