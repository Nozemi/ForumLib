<?php
  namespace ForumLib\Forums;

  use ForumLib\Utilities\PSQL;
  use ForumLib\Users\Permissions;

  class Topic {
    public $id;
    public $order;
    public $title;
    public $description;
    public $icon;
    public $enabled;
    public $categoryId;
    public $permissions;
    public $threads;

    private $S;

    private $lastError = array();
    private $lastMessage = array();

    public function __construct(PSQL $SQL) {
      if(!is_null($SQL)) {
        $this->S = $SQL;
      } else {
        $this->lastError[] = 'Something went wrong while creating the topic object.';
        return false;
      }
    }

    public function createTopic($categoryId = null) {
      if(is_null($categoryId)) $categoryId = $this->categoryId;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        INSERT INTO `{{DBP}}topics` SET
           `categoryId`   = :categoryId
          ,`title`        = :title
          ,`description`  = :description
          ,`enabled`      = :enabled
      "));

      if($this->S->executeQuery(array(
        ':categoryId'   => $categoryId,
        ':title'        => $this->title,
        ':description'  => $this->description,
        ':enabled'      => $this->enabled
      ))) {
        $this->lastMessage[] = 'Successfully created new topic.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while creating topic.';
        }
        return false;
      }
    }

    public function getTopics($categoryId = null) {
      if(is_null($categoryId)) $categoryId = $this->categoryId;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        SELECT * FROM `{{DBP}}topics` WHERE `categoryId` = :categoryId ORDER BY `order` ASC
      "));
      if($this->S->executeQuery(array(
        ':categoryId' => $categoryId
      ))) {
        $tR = $this->S->fetchAll();

        $topics = array();

        for($i = 0; $i < count($tR); $i++) {
          $T = new Topic($this->S);
          $T->setId($tR[$i]['id'])
            ->setTitle($tR[$i]['title'])
            ->setDescription($tR[$i]['description'])
            ->setIcon($tR[$i]['icon'])
            ->setOrder($tR[$i]['order'])
            ->setEnabled($tR[$i]['enabled'])
            ->setCategoryId($tR[$i]['categoryId'])
            ->setPermissions($this->id)
            ->setThreads($this->id);
          $topics[] = $T;
        }

        $this->lastMessage[] = 'Successfully loaded topics.';
        return $topics;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Failed to get topics.';
        }
        return false;
      }
    }

    public function getTopic($id = null, $byId = true) {
      if(is_null($id)) $id = $this->id;

      if($byId) {
        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
          SELECT * FROM `{{DBP}}topics` WHERE `id` = :id;
        "));
      } else {
        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
          SELECT * FROM `{{DBP}}topics` WHERE MATCH(`title`) AGAINST(:id IN BOOLEAN MODE);
        "));
      }
      if($this->S->executeQuery(array(
        ':id' => $id
      ))) {
        $topic = $this->S->fetch();

        $T = new Topic($this->S);
        $T->setId($topic['id'])
          ->setTitle($topic['title'])
          ->setDescription($topic['description'])
          ->setIcon($topic['icon'])
          ->setOrder($topic['order'])
          ->setEnabled($topic['enabled'])
          ->setCategoryId($topic['categoryId'])
          ->setPermissions($this->id)
          ->setThreads($this->id);

        $this->lastMessage[] = 'Successfully fetched topic.';
        return $T;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Failed to get topic.';
        }
        return false;
      }
    }

    public function updateTopic($id = null) {
      if(is_null($id)) $id = $this->id;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        UPDATE `{{DBP}}topics` SET
           `categoryId`   = :categoryId
          ,`title`        = :title
          ,`description`  = :description
          ,`enabled`      = :enabled
        WHERE `id` = :id
      "));

      if($this->S->executeQuery(array(
        ':categoryId'   => $this->categoryId,
        ':title'        => $this->title,
        ':description'  => $this->description,
        ':enabled'      => $this->enabled,
        ':id'           => $id
      ))) {
        $this->lastMessage[] = 'Successfully updated topic.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while updating topic.';
        }
        return false;
      }
    }

    public function deleteTopic($id = null) {
      if(is_null($id)) $id = $this->id;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        DELETE FROM `{{DBP}}topics` WHERE `id` = :id
      "));

      if($this->S->executeQuery(array(
        ':id' => $id
      ))) {
        $this->lastMessage[] = 'Successfully deleted topic.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while deleting topic.';
        }
        return false;
      }
    }

    public function setId($_id) {
      $this->id = $_id;
      return $this;
    }

    public function setTitle($_title) {
      $this->title = $_title;
      return $this;
    }

    public function setDescription($_desc) {
      $this->description = $_desc;
      return $this;
    }

    public function setIcon($_icon) {
      $this->icon = $_icon;
      return $this;
    }

    public function setOrder($_order) {
      $this->order = $_order;
      return $this;
    }

    public function setCategoryId($_cid) {
      $this->categoryId = $_cid;
      return $this;
    }

    public function setEnabled($_enabled) {
      $this->enabled = $_enabled;
      return $this;
    }

    public function setPermissions($_id = null) {
      if(is_null($_id)) $_id = $this->id;

      $P = new Permissions($this->S, $_id, $this);
      $this->permissions = $P->getPermissions();
      return $this;
    }

    public function setThreads($_threadId = null) {
      if(is_null($_threadId)) $_threadId = $this->id;

      $T = new Thread($this->S);
      $this->threads = $T->getThreads($_threadId);
      return $this;
    }

    public function getURL() {
      return strtolower(str_replace('--', '-', preg_replace("/[^a-z0-9._-]+/i", "", str_replace(' ', '-', $this->title))));
    }

    public function getType() {
      return __CLASS__;
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
