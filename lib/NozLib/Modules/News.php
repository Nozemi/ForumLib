<?php
  namespace NozLib\Modules;

  class News {
    public $id;
    public $title;
    public $content_html;
    public $content_text;
    public $author;
    public $date_posted;
    public $date_last_edit;

    private $S;

    private $lastError;
    private $lastMessage;

    public function __construct(PSQL $SQL) {
      // We'll check if the required parameters are filled.
      if(!is_null($SQL)) {
        $this->S = $SQL;
      } else {
        $this->lastError = 'Failed to make news object.';
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
        $this->lastMessage = 'News successfully posted.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError = $S->getLastMessage();
          return false;
        } else {
          $this->lastError = 'Something went wrong while posting news.';
          return false;
        }
      }
    }

    public function updateNews() {

    }

    public function getNews() {

    }

    public function getLastError() {
      return $this->lastError;
    }

    public function getLastMessage() {
      return $this->lastMessage;
    }
  }
