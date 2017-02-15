<?php
  namespace ForumLib\News;

  class News {
    public $id;
    public $title;
    public $content_html;
    public $content_text;
    public $author;
    public $date_posted;
    public $date_last_edit;

    private $S;

    private $lastError = array();
    private $lastMessage = array();

    public function __construct(PSQL $SQL) {
      // We'll check if the required parameters are filled.
      if(!is_null($SQL)) {
        $this->S = $SQL;
      } else {
        $this->lastError[] = 'Failed to make news object.';
        return false;
      }
    }

    public function postNews() {
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        INSERT INTO `{{DBP}}news` (
           `title`
          ,`content_html`
          ,`content_text`
          ,`author_id`
          ,`date_posted`
          ,`last_edit`
        ) VALUES (
           :title
          ,:content_html
          ,:content_text
          ,:author_id
          ,:date_posted
          ,:last_edit
        );
      "));
      if($this->S->executeQuery(array(
        ':title'        => $this->title,
        ':content_html' => $this->content_html,
        ':content_text' => $this->content_text,
        ':author_id'    => $this->author->uid,
        ':date_posted'  => $this->date_posted,
        ':last_edit'    => $this->date_last_edit
      ))) {
        $this->lastMessage[] = 'News successfully posted.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $S->getLastMessage();
        } else {
          $this->lastError[] = 'Something went wrong while posting news.';
        }
        return false;
      }
    }

    public function updateNews() {
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        UPDATE `{{DBP}}news` SET
           `title`          = :title
          ,`content_html`   = :content_html
          ,`content_text`   = :content_text
          ,`author_id`      = :author_id
          ,`last_edit`      = :last_edit
        WHERE `nid` = :nid;
      "));
      if($this->S->executeQuery(array(
        ':title'        => $this->title,
        ':content_html' => $this->content_html,
        ':content_text' => $this->content_text,
        ':author_id'    => $this->author->uid,
        ':last_edit'    => $this->date_last_edit,
        ':nid'          => $this->id
      ))) {
        $this->lastMessage[] = 'News was successfully updated.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while updating news.';
        }
        return false;
      }
    }

    public static function getNews($id = null) {
      if(is_null($id)) {
        // If $id is null (which it is by default), we'll fetch all news items.
        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "SELECT * FROM `{{DBP}}news` ORDER BY `date_posted` DESC;"));
        if($this->S->executeQuery()) {
          $this->lastMessage[] = 'All news was successfully fetched.';
          return true;
        } else {
          if(defined('DEBUG')) {
            $this->lastError[] = $this->S->getLastError();
          } else {
            $this->lastError[] = 'Something went wrong while fetching all news.';
          }
          return false;
        }
      } else {
        // If $id isn't null, we'll fetch the news item with that exact id from the database.
        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "SELECT * FROM `{{DBP}}news` WHERE `nid` = :nid"));
        if($this->S->executeQuery(array(':nid' => $id))) {
          $this->lastMessage = 'News item was successfully fetched.';
          return true;
        } else {
          if(defined('DEBUG')) {
            $this->lastError[] = $this->S->getLastError();
          } else {
            $this->lastError[] = 'Something went wrong while fetching news item.';
          }
          return false;
        }
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
