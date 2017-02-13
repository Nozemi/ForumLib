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
      if(!is_null($SQL)) {
        $this->S = $SQL;
      } else {
        $this->lastError = 'Something went wrong while creating the post object.';
        return false;
      }
    }

    public function createPost() {

    }

    // Takes one parameter, which would be the thread ID.
    public function getPosts($tid = null) {

    }

    public function updatePost() {

    }

    public function deletePost($pid = null) {

    }

    public function getLastError() {
      return $this->lastError;
    }

    public function getLastMessage() {
      return $this->lastMessage;
    }
  }
