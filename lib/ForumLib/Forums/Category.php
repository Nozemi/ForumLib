<?php
  namespace ForumLib\Forums;

  use ForumLib\Utilities\PSQL;

  class Category {
    public $id;
    public $title;
    public $description;
    public $order;
    public $enabled;
    public $permissions = array();

    private $S;

    private $lastError = array();
    private $lastMessage = array();

    public function __construct(PSQL $SQL) {
      // Let's check if the $SQL is not a null.
      if(!is_null($SQL)) {
        $this->S = $SQL;
      } else {
        $this->lastError[] = 'Something went wrong while creating the category object.';
        return false;
      }
    }

    public function getCategories() {
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "SELECT * FROM `{{DBP}}categories` ORDER BY `order` ASC"));
      if($this->S->executeQuery()) {
        $this->lastMessage[] = 'Successfully fetched categories.';

        $theCategories = array();
        $qR = $this->S->fetchAll();

        for($i = 0; $i < count($qR); $i++) {
          $theCategories[$i] = new Category($this->S);
          $theCategories[$i]
            ->setTitle($qR[$i]['title'])
            ->setDescription($qR[$i]['description'])
            ->setOrder($qR[$i]['order']);
        }

        return $theCategories;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while fetching the categories.';
        }
        return false;
      }
    }

    public function getCategory($id) {
      $this->id = $id;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        SELECT
          *
        FROM `{{DBP}}categories`
        WHERE `cid` = :cid
      "));
      if($this->S->executeQuery(array(
        ':cid' => $this->id
      ))) {
        $this->lastMessage[] = 'The category was successfully loaded.';

        $cat = $this->S->fetch(); // Let's get the query result.

        $theCategory = new Category($this->S);
        $theCategory
          ->setTitle($cat['title'])
          ->setDescription($cat['description'])
          ->setOrder($cat['order']);

        return $theCategory;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Failed to get category.';
        }
        return false;
      }
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
        $this->lastMessage[] = 'Succefully created category.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while creating the new category.';
        }
        return false;
      }
    }

    public function updateCategory() {
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        UPDATE `{{DBP}}categories` SET
           `title`        = :title
          ,`description`  = :description
          ,`order`        = :order
        WHERE `cid` = :cid
      "));
      if($this->S->executeQuery(array(
        ':title'        => $this->title,
        ':description'  => $this->description,
        ':order'        => $this->order,
        ':cid'          => $this->id
      ))) {
        $this->lastMessage[] = 'Successfully updated the category.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while updating category.';
        }
        return false;
      }
    }

    public function deleteCategory($cid = null) {
      if(is_null($cid)) {
        $cid = $this->id;
      }

      // We'll have to fill in a few more delete queries. So that sub topics, threads and post are deleted as well.
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        DELETE FROM `{{DBP}}categories` WHERE `cid` = :cid;
      "));
      if($this->S->executeQuery(array(
        ':cid' => $cid
      ))) {
        $this->lastMessage[] = 'Successfully deleted category.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while deleting category.';
        }
        return false;
      }
    }

    public function setTitle($_title) {
      $this->title = $_title;
    }

    public function setDescription($_description) {
      $this->description = $_description;
    }

    public function setOrder($_order) {
      $this->order = $_order;
    }

    public function setEnabled($_enabled) {
      $this->enabled = $_enabled;
    }

    public function getType() {
      return __CLASS__ ;
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
