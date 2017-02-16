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

    public $posts;

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
      if(is_null($cid)) $cid = $this->id;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        SELECT * FROM `{{DBP}}threads` WHERE `topicId` = :topicId
      "));
      if($this->S->executeQuery(array(
        ':topicId' => $this->topicId
      ))) {
        $this->lastMessage[] = 'Successfully loaded threads.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while getting threads.';
        }
        return false;
      }
    }

    public function createThread(Post $post) {
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        INSERT INTO `{{DBP}}threads` (
           `title`
          ,`topicId`
          ,`authorId`
          ,`dateCreated`
          ,`lastEdited`
          ,`sticky`
          ,`closed`
        ) VALUES (
           :title
          ,:topicId
          ,:authorId
          ,:dateCreated
          ,:lastEdited
          ,:sticky
          ,:closed
        );

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
        ':title'        => $this->title,
        ':topicId'      => $this->topicId,
        ':authorId'     => $this->author->id,
        ':dateCreated'  => date('Y-m-d H:i:s', time()),
        ':lastEdited'   => date('Y-m-d H:i:s', time()),
        ':sticky'       => 0,
        ':closed'       => 0,

        ':post_content_html'  => $post->post_html,
        ':post_content_text'  => $post->post_text,
        ':authorId'           => $post->author->id,
        ':threadId'           => $this->S->getLastInsertId(),
        ':postDate'           => date('Y-m-d H:i:s', time()),
        ':editDate'           => date('Y-m-d H:i:s', time())
      ))) {
        $this->lastMessage[] = 'Successfully created thread.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while posting thread.';
        }
        return false;
      }
    }

    public function getThread($id = null) {
      if(is_null($id)) $id = $this->id;

      // We'll need to load the thread and it's posts. Currently it just loads the thread.
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        SELECT * FROM `{{DBP}}threads` WHERE `tid` = :tid
      "));
      if($this->S->executeQuery(array(
        ':tid' => $id
      ))) {
        $this->lastMessage[] = 'Successfully loaded thread.';
        return true; // Rather than returning true, we'll return the thread.
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while loading thread.';
        }
        return false;
      }
    }

    public function updateThread() {
      $this->S->prepareQuery($this->S->executeQuery('{{DBP}}', "
        UPDATE `{{DBP}}threads` SET
           `title`        = :title
          ,`topicId`      = :topicId
          ,`authorId`     = :authorId
          ,`dateCreated`  = :dateCreated
          ,`lastEdited`   = :lastEdited
          ,`sticky`       = :sticky
          ,`closed`       = :closed
        WHERE `tid` = :tid
      "));
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
