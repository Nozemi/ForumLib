<?php
  namespace ForumLib\Forums;

  class Thread {
    public $id;
    public $title;
    public $author;
    public $sticky;
    public $closed;
    public $posted;
    public $topic;

    private $S;

    private $lastError = array();
    private $lastMessage = array();

    public function __construct() {
      if(!is_null($SQL)) {
        $this->S = $SQL;
      } else {
        $this->lastError[] = 'Something went wrong while creating the thread object.';
        return false;
      }
    }

    public function getThreads($cid = null) {

    }

    public function createThread() {

    }

    public function getThread() {

    }

    public function updateThread() {

    }

    public function deleteThread($tid = null) {

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
