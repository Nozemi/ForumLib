<?php
  namespace ForumLib\Forums;

  class Topic {
    public $id;
    public $order;
    public $title;
    public $description;
    public $enabled;
    public $categoryId;

    private $S;

    private $lastError = array();
    private $lastMessage = array();

    public function __construct() {
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

    public function getTopics($tid = null) {
      if(is_null($cid)) $cid = $this->categoryId;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        SELECT * FROM `{{DBP}}topics` WHERE `categoryId` = :categoryId
      "));
      if($this->S->executeQuery(array(
        ':categoryId' => $cid
      ))) {
        $this->lastMessage[] = 'Successfully loaded topics.';
        return $this->S->fetchAll();
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
        $this->lastMessage[] = 'Successfully fetched topic.';
        return $this->S->fetch();
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
