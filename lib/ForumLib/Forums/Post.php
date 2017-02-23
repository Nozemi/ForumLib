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
    public $permissions = array();

    private $S;

    private $lastError = array();
    private $lastMessage = array();

    public function __construct() {
      if(!is_null($SQL)) {
        $this->S = $SQL;
      } else {
        $this->lastError[] = 'Something went wrong while creating the post object.';
        return false;
      }
    }

    public function createPost() {
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        INSERT INTO `{{DBP}}posts` (
           `post_content_html`
          ,`post_content_text`
          ,`authorId`
          ,`threadId`
          ,`postDate`
          ,`editDate`
        ) VALUES (
           :post_content_html
          ,:post_content_text
          ,:authorId
          ,:threadId
          ,:postDate
          ,:editDate
        );
      "));
      if($this->S->executeQuery(array(
        ':post_content_html'  => $this->post_html,
        ':post_content_text'  => $this->post_text,
        ':authorId'           => $this->author->id,
        ':threadId'           => $this->thread->id,
        ':postDate'           => date('Y-m-d H:i:s', time()),
        ':editDate'           => date('Y-m-d H:i:s', time())
      ))) {
        $this->lastMessage[] = 'Post successfully created.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while submitting post.';
        }
        return false;
      }
    }

    // Takes one parameter, which would be the thread ID.
    public function getPosts($tid = null) {
      if(is_null($tid)) $tid = $this->thread->id;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "SELECT * FROM `{{DBP}}posts` WHERE `threadId` = :threadId ORDER BY `postDate` DESC"));
      if($this->S->executeQuery(array(
        ':threadId' => $tid
      ))) {
        $this->lastMessage = 'Successfully fetched posts.';
        return $this->S->fetchAll();
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while fetching posts.';
        }
        return false;
      }
    }

    public function updatePost() {
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        UPDATE `{{DBP}}posts` SET
           `post_content_html`  = :post_content_html
          ,`post_content_text`  = :post_content_text
          ,`authorId`           = :authorId
          ,`threadId`           = :threadId
          ,`editDate`           = :editDate
        WHERE `postId` = :postId
      "));
      if($this->S->executeQuery(array(
        ':post_content_html'  => $this->post_html,
        ':post_content_text'  => $this->post_text,
        ':authorId'           => $this->author->id,
        ':threadId'           => $this->threadId,
        ':editDate'           => date('Y-m-d H:i:s', time())
      ))) {
        $this->lastMessage[] = 'Successfully edited post.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while updating post.';
        }
        return false;
      }
    }

    public function deletePost($pid = null) {
      if(is_null($pid)) $pid = $this->id;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        DELETE FROM `{{DBP}}posts` WHERE `pid` = :postId
      "));

      if($this->S->executeQuery(array(
        ':postId' => $pid
      ))) {
        $this->lastMessage[] = 'Successfully deleted post.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while deleting post.';
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
