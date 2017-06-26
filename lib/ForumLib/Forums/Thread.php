<?php
  namespace ForumLib\Forums;

  use ForumLib\Database\DBUtil;

  use ForumLib\Integration\Nozum\NozumThread;
  use ForumLib\Integration\vB3\vB3Thread;

  use ForumLib\Users\Permissions;
  use ForumLib\Users\User;
  use ForumLib\Utilities\Config;

  class Thread extends Base {
    public $author;
    public $sticky;
    public $closed;
    public $posted;
    public $edited;
    public $topicId;
    public $permissions;
    public $posts;
    public $latestPost;

    public function __construct(DBUtil $SQL) {
        if(!is_null($SQL)) {
            $this->S = $SQL;
            $this->config = new Config;

            switch($this->config->getConfigValue('integration')) {
                case 'vB3':
                    $this->integration = new vB3Thread($this->S);
                    break;
                case 'Nozum':
                default:
                    $this->integration = new NozumThread($this->S);
                    break;
            }
        } else {
            $this->lastError[] = 'Something went wrong while creating the thread object.';
            return false;
        }
    }

    public function getThreads($topicId = null) {
        return $this->integration->getThreads($topicId, $this);
    }

    public function createThread(Thread $thread, Post $post) {
        return $this->integration->createThread($thread, $post);
    }

    public function getThread($id = null, $byId = true, $topicId = null) {
        return $this->integration->getThread($id, $byId, $topicId, $this);
    }

    public function updateThread($id = null) {
        return $this->integration->updateThread($id, $this);
    }

    public function deleteThread($id = null) {
        return $this->integration->deleteThread($id, $this);
    }

    public function setLatestPost($_threadId = null) {
        $this->latestPost = $this->integration->setLatestPost($_threadId, $this);
        return $this;
    }

    public function setAuthor($_uid) {
      $U = new User($this->S);
      $this->author = $U->getUser($_uid);
      return $this;
    }

    public function setSticky($_sticky) {
      $this->sticky = $_sticky;
      return $this;
    }

    public function setClosed($_closed) {
      $this->closed = $_closed;
      return $this;
    }

    public function setPosted($_posted) {
      $this->posted = $_posted;
      return $this;
    }

    public function setEdited($_edited) {
      $this->edited = $_edited;
      return $this;
    }

    public function setTopicId($_tid) {
      $this->topicId = $_tid;
      return $this;
    }

    public function setPosts($_id = null) {
      if(is_null($_id)) $_id = $this->id;

      $P = new Post($this->S);
      $this->posts = $P->getPosts($_id);

      return $this;
    }

    public function setPermissions($_id = null) {
      if(is_null($_id)) $_id = $this->id;

      $P = new Permissions($this->S, $_id, $this);
      $this->permissions = $P->getPermissions();
      return $this;
    }

    public function getType() {
      return __CLASS__;
    }

    public function getURL() {
        $url = $this->id . '-' . strtolower(str_replace('--', '-', preg_replace("/[^a-z0-9._-]+/i", "", str_replace(' ', '-', $this->title))));

        return $url;
    }
  }
