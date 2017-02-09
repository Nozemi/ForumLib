<?php
  namespace ForumLib\Forums;

  class Category {
    public $id;
    public $title;
    public $description;
    public $order;
    public $enabled;

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
