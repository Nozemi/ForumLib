<?php
  namespace ForumLib\Forums;

  use ForumLib\Database\PSQL;
  use ForumLib\Users\Permissions;
  use ForumLib\Utilities\Config;

  use ForumLib\Integration\Nozum\NozumCategory;
  use ForumLib\Integration\vB3\vB3Category;

  class Category extends Base {
    public $enabled;
    public $permissions;
    public $topics;
    public $config;
    private $integration;

    public function __construct(PSQL $SQL) {
      // Let's check if the $Database is not a null.
      if(!is_null($SQL)) {
        $this->S = $SQL;
        $C = new Config;
        $this->config = $C->config;
        switch(array_column($this->config, 'integration')[0]) {
            case 'vB3':
                $this->integration = new vB3Category($this->S);
                break;
            case 'Nozum':
            default:
                $this->integration = new NozumCategory($this->S);
                break;
        }
      } else {
        $this->lastError[] = 'Something went wrong while creating the category object.';
        return false;
      }
    }

    public function getCategories() {
        return $this->integration->getCategories();
    }

    public function getCategory($id = null, $byId = true) {
        return $this->integration->getCategory($id, $byId, $this);
    }

    public function createCategory() {
        return $this->integration->createCategory($this);
    }

    public function updateCategory() {
        return $this->integration->updateCategory($this);
    }

    public function deleteCategory($id = null) {
        return $this->integration->deleteCategory($id, $this);
    }

    public function setEnabled($_enabled) {
      $this->enabled = $_enabled;
      return $this;
    }

    public function setPermissions($_id = null) {
      if(is_null($this->id)) $this->id = $_id;

      $P = new Permissions($this->S, $this->id, $this);
      $this->permissions = $P->getPermissions();
      return $this;
    }

    public function setTopics($_cid = null) {
      if(is_null($this->id)) $this->id = $_cid;

      $T = new Topic($this->S);
      $this->topics = $T->getTopics();
      return $this;
    }

    public function getType() {
      return __CLASS__;
    }
  }
