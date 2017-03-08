<?php
  namespace ForumLib\Forums;

  use ForumLib\Utilities\PSQL;
  use ForumLib\Users\Permissions;
  use ForumLib\Users\User;

  class Thread extends Base {
    public $author;
    public $sticky;
    public $closed;
    public $posted;
    public $edited;
    public $topicId;
    public $permissions;
    public $posts;

    public function __construct(PSQL $SQL) {
      if(!is_null($SQL)) {
        $this->S = $SQL;
      } else {
        $this->lastError[] = 'Something went wrong while creating the thread object.';
        return false;
      }
    }

    public function getThreads($topicId = null) {
      if(is_null($topicId)) $topicId = $this->topicId;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        SELECT * FROM `{{DBP}}threads` WHERE `topicId` = :topicId
      "));
      if($this->S->executeQuery(array(
        ':topicId' => $topicId
      ))) {
        $tR = $this->S->fetchAll();

        $threads = array();

        for($i = 0; $i < count($tR); $i++) {
          $T = new Thread($this->S);
          $T->setId($tR[$i]['id'])
            ->setTitle($tR[$i]['title'])
            ->setAuthor($tR[$i]['authorId'])
            ->setSticky($tR[$i]['sticky'])
            ->setClosed($tR[$i]['closed'])
            ->setPosted($tR[$i]['dateCreated'])
            ->setEdited($tR[$i]['lastEdited'])
            ->setTopicId($tR[$i]['topicId'])
            ->setPermissions($this->id);
          $threads[] = $T;
        }
        $this->lastMessage[] = 'Successfully loaded threads.';
        return $threads;
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
        SELECT * FROM `{{DBP}}threads` WHERE `id` = :id
      "));
      if($this->S->executeQuery(array(
        ':id' => $id
      ))) {
        $this->lastMessage[] = 'Successfully loaded thread.';
        $tR = $this->S->fetch();

        $thread = new Thread($this->S);
        $thread->setId($tR['id'])
          ->setTitle($tR['title'])
          ->setClosed($tR['closed'])
          ->setPosted($tR['dateCreated'])
          ->setEdited($tR['lastEdited'])
          ->setSticky($tR['sticky'])
          ->setAutor($tR['authorId'])
          ->setTopicId($tR['topicId'])
          ->setPosts($this->id);

        return $thread;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while loading thread.';
        }
        return false;
      }
    }

    public function updateThread($id = null) {
      if(is_null($id)) $id = $this->id;

      $this->S->prepareQuery($this->S->executeQuery('{{DBP}}', "
        UPDATE `{{DBP}}threads` SET
           `title`        = :title
          ,`topicId`      = :topicId
          ,`authorId`     = :authorId
          ,`dateCreated`  = :dateCreated
          ,`lastEdited`   = :lastEdited
          ,`sticky`       = :sticky
          ,`closed`       = :closed
        WHERE `id` = :id
      "));

      if($this->S->executeQuery(array(
        ':title'        => $this->title,
        ':topicId'      => $this->topicId,
        ':authorId'     => $this->author->id,
        ':dateCreated'  => $this->posted,
        ':lastEdited'   => $this->edited,
        ':sticky'       => $this->sticky,
        ':closed'       => $this->closed,
        ':id'           => $this->id
      ))) {
        $this->lastMessage[] = 'Successfully updated thread.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while updating thread.';
        }
        return false;
      }
    }

    public function deleteThread($id = null) {
      if(is_null($id)) $id = $this->id;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        DELETE FROM `{{DBP}}threads` WHERE `id` = :id
      "));

      if($this->S->executeQuery(array(
        ':id' => $id
      ))) {
        $this->lastMessage[] = 'Successfully deleted thread.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while deleting thread.';
        }
        return false;
      }
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
  }
