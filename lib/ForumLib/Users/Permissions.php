<?php
  namespace ForumLib\Users;

  use ForumLib\Database\PSQL;

  use ForumLib\Forums\Category;
  use ForumLib\Forums\Topic;
  use ForumLib\Forums\Thread;
  use ForumLib\Forums\Post;

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

    public function __construct(PSQL $_SQL, $_id = null) {
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

    public function checkPermissions(User $_user, $_object = null) {
        if(is_null($_object)) $_object = $this->OI;

        $query = 'No query.';

        if($_object instanceof Category) {

        }

        if($_object instanceof Topic) {
            $query = "SELECT * FROM `{{DBP}}permissions` WHERE `topicId` = :id AND `groupId` = :gid";
        }

        if($_object instanceof Thread) {

        }

        if($_object instanceof Post) {

        }

        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', $query));
        $this->S->executeQuery(array(':id' => $_object->id, ':gid' => $_user->groupId));

        if($this->S->getLastError()) {
            return $this->S->getLastError();
        }
        return $this->S->fetch();
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

        $users = array(); $groups = array();

        for($i = 0; $i < count($perms); $i++) {
          $P = new permissions($this->S);

          if(is_null($perms['userId'])) {
            $P->setUserId(null)
              ->setGroupId($perms[$i]['groupId']);
          } else {
            $P->setGroupId(null)
              ->setUserId($perms[$i]['userId']);
          }

          $P->setPost($perms[$i]['post'])
            ->setRead($perms[$i]['read'])
            ->setMod($perms[$i]['mod'])
            ->setAdmin($perms[$i]['admin']);

          if(is_null($P->getUserId)) {
            $groups[] = $P;
          } else {
            $users[] = $P;
          }
        }

        $this->lastMessage[] = 'Successfully loaded permissions.';

        return array(
          'users'   => $users,
          'groups'  => $groups
        );
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
