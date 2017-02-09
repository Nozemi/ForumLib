<?php
  namespace ForumLib\Forums;

  class Category {
    public $title;
    public $description;
    public $order;
    public $enabled;

    private $S;

    private $lastError;
    private $lastMessage;

    public function __construct() {

    }
  }
