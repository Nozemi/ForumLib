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

    }

    public function getLastError() {
      return $this->lastError;
    }

    public function getLastMessage() {
      return $this->lastMessage;
    }
  }
