<?php
  namespace ForumLib\News;

  class Comment {
    public $text;
    public $html;
    public $author;
    public $newsId;
    public $date;
    public $visible;

    private $S;

    private $lastMessage = array();
    private $lastError = array();

    public function __construct(PSQL $SQL) {
      // We'll check if the required parameters are filled.
      if(!is_null($SQL)) {
        $this->S = $SQL;
      } else {
        $this->lastError[] = 'Failed to make comment object.';
        return false;
      }
    }

    public function createComment() {
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        INSERT INTO `{{DBP}}comments` SET
           `authorId` = :authorId
          ,`newsId`   = :newsId
          ,`text`     = :text
          ,`html`     = :html
          ,`date`     = :date
          ,`visible`  = :visible
      "));

      if($this->S->executeQuery(array(
        ':authorId'   => $author->id,
        ':newsId'     => $newsId,
        ':text'       => $text,
        ':html'       => $html,
        ':date'       => date('Y-m-d H:i:s', time()),
        ':visible'    => $visible
      ))) {
        $this->lastMessage[] = 'Successfully created new comment.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while creating the comment.';
        }
        return false;
      }
    }

    public function getLastError() {
      return end($this->lastError);
    }

    public function getErrors() {
      return $this->lastError;
    }

    public function getLastMessage() {
      return end($this->lastMessage);
    }

    public function getMessages() {
      return $this->lastMessage;
    }
  }
