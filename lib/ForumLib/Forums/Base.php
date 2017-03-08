<?php
  namespace ForumLib\Forums;

  abstract class Base {
    public $id;               // Id counts for all the objects under \Forums namespace. (Category, Topic, Thread and Post)
    public $title;            // Title counts for all the objects under \Forums namespace. (Category, Topic, Thread and Post)
    public $description;      // Description for the object. This would be for Category and Topic.
    public $icon;             // Is supposed to hold an icon, originally intended for a font-awesome class. (home (as in fa-home))
    public $order;            // This is for the Category and Topic objects.

    private $S;               // The PSQL object.

    private $lastError = array();
    private $lastMessage = array();

    abstract public function getType();

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

    public function setIcon($_icon) {
      $this->icon = $_icon;
      return $this;
    }

    public function setOrder($_order) {
      $this->order = $_order;
      return $this;
    }

    public function getURL() {
      return strtolower(str_replace('--', '-', preg_replace("/[^a-z0-9._-]+/i", "", str_replace(' ', '-', $this->title))));
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
