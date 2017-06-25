<?php
  namespace ForumLib\Users;

  use ForumLib\Database\DBUtil;

  use ForumLib\Integration\Nozum\NozumPermissions;
  use ForumLib\Integration\vB3\vB3Permissions;
  use ForumLib\Utilities\Config;

  class Permissions {
    public $id;
    public $OI; // Object Instance.

    private $S; // PSQL Object Instance.

    private $lastError = array();
    private $lastMessage = array();

    public $type; // Helps us decide where to get the permissions from. Whether we're talking about a category, topic or thread.

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

    public function __construct(DBUtil $_SQL, $_id = null) {
      // We'll check if the required parameters are filled.
      if(!is_null($_SQL)) {
        $this->S = $_SQL;
          $C = new Config;
          $this->config = $C->config;
          switch(array_column($this->config, 'integration')[0]) {
              case 'vB3':
                  $this->integration = new vB3Permissions($this->S);
                  break;
              case 'Nozum':
              default:
                  $this->integration = new NozumPermissions($this->S);
                  break;
          }
      } else {
        $this->lastError[] = 'Failed to make comment object.';
      }

      if(!is_null($_id)) {
        $this->id = $_id;
      }
    }

    public function checkPermissions(User $_user, $_object = null) {
        return $this->integration->checkPermissions($_user, $_object, $this);
    }

    public function getPermissions($_id = null) {
        return $this->integration->getPermissions($_id, $this);
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

    public function getUserId() {
      return $this->userId;
    }

    public function getGroupId() {
      return $this->groupId;
    }

    public function setRead($_read) {
      $this->canRead = $_read;
      return $this;
    }

    public function setPost($_post) {
      $this->canPost = $_post;
      return $this;
    }

    public function setMod($_mod) {
      $this->canMod = $_mod;
      return $this;
    }

    public function setAdmin($_admin) {
      $this->canAdmin = $_admin;
      return $this;
    }

    public function setUserId($_uid) {
      $this->userId = $_uid;
      return $this;
    }

    public function setGroupId($_gid) {
      $this->groupId = $_gid;
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
