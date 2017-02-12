<?php
  namespace ForumLib\Forums;

  use ForumLib\Utilities\PSQL;

  class Category {
    public $id;
    public $title;
    public $description;
    public $order;
    public $enabled;

    private $S;

    private $lastError;
    private $lastMessage;

    public function __construct(PSQL $SQL) {
      if(!is_null($SQL)) {
        $this->S = $SQL;
      } else {
        $this->lastError = 'Something went wrong while creating the category object.';
        return false;
      }
    }

    public function getCategories() {
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "SELECT * FROM `{{DBP}}categories` ORDER BY `order` ASC"));
      if($this->S->executeQuery()) {
        $this->lastMessage = 'Successfully fetched categories.';
        return $this->S->fetchAll();
      } else {
        if(defined('DEBUG')) {
          $this->lastError = $this->S->getLastError();
        } else {
          $this->lastError = 'Something went wrong while fetching the categories.';
        }
        return false;
      }
    }

    public function getCategory($id) {
      $this->id = $id;

      // Rest of the process goes here...
    }

    public function createCategory() {
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        INSERT INTO `{{DBP}}categories` (
           `title`
          ,`description`
          ,`order`
        ) VALUES (
           :title
          ,:description
          ,:order
        );
      "));
      if($this->S->executeQuery(array(
        ':title'        => $this->title,
        ':description'  => $this->description,
        ':order'        => $this->order
      ))) {
        $this->lastMessage = 'Succefully created category.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError = $this->S->getLastError();
        } else {
          $this->lastError = 'Something went wrong while creating the new category.';
        }
        return false;
      }
    }

    public function getLastError() {
      return $this->lastError;
    }

    public function getLastMessage() {
      return $this->lastMessage;
    }
  }
