<?php
  namespace ForumLib\Forums;

  class Topic {
    public $id;
    public $order;
    public $title;
    public $description;
    public $enabled;
    public $category;

    private $S;

    private $lastError;
    private $lastMessage;

    public function __construct() {
      if(!is_null($SQL)) {
        $this->S = $SQL;
      } else {
        $this->lastError = 'Something went wrong while creating the topic object.';
        return false;
      }
    }

    public function createTopic() {

    }

    public function getTopics() {

    }

    public function getTopic($cid = null) {

    }

    public function updateTopic() {

    }

    public function deleteTopic($id = null) {

    }

    public function getLastError() {
      return $this->lastError;
    }

    public function getLastMessage() {
      return $this->lastMessage;
    }
  }
