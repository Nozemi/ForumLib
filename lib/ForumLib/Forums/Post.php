<?php
  namespace ForumLib\Forums;

  class Post {
    public $id;
    public $thread;
    public $author;
    public $post_html;
    public $post_text;
    public $post_date;
    public $post_last_edit;

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
