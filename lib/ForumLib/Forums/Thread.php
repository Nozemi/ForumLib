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

    private $lastError;
    private $lastMessage;

    public function __construct() {

    }

    public function getLastError() {
      return $this->lastError;
    }

    public function getLastMessage() {
      return $this->lastMessage;
    }
  }
