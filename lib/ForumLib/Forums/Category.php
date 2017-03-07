<?php
  namespace ForumLib\Forums;

  use ForumLib\Utilities\PSQL;
  use ForumLib\Users\Permissions;

  class Category {
    public $id;
    public $title;
    public $description;
    public $order;
    public $enabled;
    public $permissions;
    public $topics;

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
            ->setId($qR[$i]['id'])
            ->setTitle($qR[$i]['title'])
            ->setDescription($qR[$i]['description'])
            ->setOrder($qR[$i]['order'])
            ->setEnabled($qR[$i]['enabled'])
            ->setPermissions($this->id)
            ->setTopics($this->id);
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

    public function getCategory($id = null, $byId = true) {
      if(is_null($id)) $id = $this->id;

      if($byId) {
        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
          SELECT * FROM `{{DBP}}categories` WHERE `id` = :id;
        "));
      } else {
        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
          SELECT * FROM `{{DBP}}categories` WHERE MATCH(`title`) AGAINST(:id IN BOOLEAN MODE);
        "));
      }

      if($this->S->executeQuery(array(
        ':id' => $id
      ))) {
        $this->lastMessage[] = 'The category was successfully loaded.';

        $cat = $this->S->fetch(); // Let's get the query result.

        $theCategory = new Category($this->S);
        $theCategory
          ->setId($cat['id'])
          ->setTitle($cat['title'])
          ->setDescription($cat['description'])
          ->setOrder($cat['order'])
          ->setEnabled($cat['enabled']);

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
        WHERE `cid` = :cid;
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

    public function deleteCategory($id = null) {
      if(is_null($id)) $id = $this->id;

      // We'll have to fill in a few more delete queries. So that sub topics, threads and post are deleted as well.
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        DELETE FROM `{{DBP}}categories` WHERE `id` = :id;
      "));
      if($this->S->executeQuery(array(
        ':id' => $id
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

    public function setId($_id) {
      $this->id = $_id;
      return $this;
    }

    public function setTitle($_title) {
      $this->title = $_title;
      return $this;
    }

    public function setDescription($_description) {
      $this->description = $_description;
      return $this;
    }

    public function setOrder($_order) {
      $this->order = $_order;
      return $this;
    }

    public function setEnabled($_enabled) {
      $this->enabled = $_enabled;
      return $this;
    }

    public function setPermissions($_id = null) {
      if(is_null($_id)) $_id = $this->id;

      $P = new Permissions($this->S, $_id, $this);
      $this->permissions = $P->getPermissions();
      return $this;
    }

    public function setTopics($_categoryId = null) {
      if(is_null($_categoryId)) $_categoryId = $this->id;

      $T = new Topic($this->S);
      $this->topics = $T->getTopics($_categoryId);
      return $this;
    }

    public function getURL() {
      return strtolower(str_replace('--', '-', preg_replace("/[^a-z0-9._-]+/i", "", str_replace(' ', '-', $this->title))));
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
