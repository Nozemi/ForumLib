<?php
  namespace ForumLib\Forums;

  use ForumLib\Utilities\PSQL;
  use ForumLib\Users\Permissions;

  class Topic {
    public $id;
    public $order;
    public $title;
    public $description;
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

    public function createTopic($cid = null) {
      if(is_null($cid)) $cid = $this->categoryId;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        INSERT INTO `{{DBP}}topics` SET
           `categoryId`   = :categoryId
          ,`title`        = :title
          ,`description`  = :description
          ,`enabled`      = :enabled
      "));

      if($this->S->executeQuery(array(
        ':categoryId'   => $this->categoryId,
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

    public function getTopics($cid = null) {
      if(is_null($cid)) $cid = $this->categoryId;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        SELECT * FROM `{{DBP}}topics` WHERE `categoryId` = :categoryId
      "));
      if($this->S->executeQuery(array(
        ':categoryId' => $cid
      ))) {
        $tR = $this->S->fetchAll();

        $topics = array();

        for($i = 0; $i < count($tR); $i++) {
          $T = new Topic($this->S);
          $T->setId($tR[$i]['id'])
            ->setTitle($tR[$i]['title'])
            ->setDescription($tR[$i]['description'])
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

    public function getTopic($cid = null) {
      if(is_null($tid)) $tid = $this->topicId;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        SELECT * FROM `{{DBP}}topics` WHERE `tid` = :tid
      "));
      if($this->S->executeQuery(array(
        ':tid' => $tid
      ))) {
        $topic = $this->S->fetch();

        $T = new Topic($this->S);
        $T->setId($topic['id'])
          ->setTitle($topic['title'])
          ->setDescription($topic['description'])
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

    public function updateTopic($tid = null) {
      if(is_null($tid)) $tid = $this->id;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        UPDATE `{{DBP}}topics` SET
           `categoryId`   = :categoryId
          ,`title`        = :title
          ,`description`  = :description
          ,`enabled`      = :enabled
        WHERE `tid` = :tid
      "));

      if($this->S->executeQuery(array(
        ':categoryId'   => $this->categoryId,
        ':title'        => $this->title,
        ':description'  => $this->description,
        ':enabled'      => $this->enabled,
        ':tid'          => $tid
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
        DELETE FROM `{{DBP}}topics` WHERE `tid` = :tid
      "));

      if($this->S->executeQuery(array(
        ':tid' => $tid
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

    public function setOrder($_order) {
      $this->order = $_order;
      return $this;
    }

    public function setCategoryId($_cid) {
      $this->categoryId = $_cid;
      return $this;
    }

    public function setPermissions($_id = null) {
      if(is_null($this->id)) $this->id = $_id;

      $P = new Permissions($this->S, $this->id, $this);
      $this->permissions = $P->getPermissions();
      return $this;
    }

    public function setThreads($_tid = null) {
      if(is_null($this->id)) $this->id = $_tid;

      $T = new Thread($this->S);
      $this->threads = $T->getThreads();
      return $this;
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
