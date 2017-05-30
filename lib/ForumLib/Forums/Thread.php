<?php
  namespace ForumLib\Forums;

  use ForumLib\Database\PSQL;
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

    public function __construct(PSQL $SQL) {
        if(!is_null($SQL)) {
        $this->S = $SQL;

        $C = new Config;
            $this->config = $C->config;
            switch(array_column($this->config, 'integration')[0]) {
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
      if(is_null($topicId)) $topicId = $this->topicId;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        SELECT * FROM (
            SELECT
                 `T`.*
                ,`P`.`postDate`
            FROM `{{DBP}}posts` `P`
                INNER JOIN `{{DBP}}threads` `T` ON `P`.`threadId` = `T`.`id`
                INNER JOIN `{{DBP}}topics` `F` ON `T`.`topicId` = `F`.`id`
            WHERE `F`.`id` = :topicId
            ORDER BY `P`.`postDate` DESC ) `threads`
        GROUP BY `id` ORDER BY `postDate` DESC
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
          ,`originalPost`
        ) VALUES (
           :post_content_html
          ,:post_content_text
          ,:pAuthorId
          ,LAST_INSERT_ID()
          ,:postDate
          ,:editDate
          ,1
        );
      "));
      if($this->S->executeQuery(array(
        ':title'        => $this->title,
        ':topicId'      => $this->topicId,
        ':authorId'     => $this->author->id,
        ':dateCreated'  => date('Y-m-d H:i:s'),
        ':lastEdited'   => date('Y-m-d H:i:s'),
        ':sticky'       => 0,
        ':closed'       => 0,

        ':post_content_html'  => $post->post_html,
        ':post_content_text'  => $post->post_text,
        ':pAuthorId'          => $post->author->id,
        ':postDate'           => date('Y-m-d H:i:s'),
        ':editDate'           => date('Y-m-d H:i:s')
      ))) {
        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "SELECT `id` FROM `{{DBP}}threads` ORDER BY `dateCreated` DESC LIMIT 1;"));
        $this->S->executeQuery();

        $result = $this->S->fetch();
        $this->setId($result['id']);

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

    public function getThread($id = null, $byId = true, $topicId = null) {
        if(is_null($id)) $id = $this->id;

        // We'll need to load the thread and it's posts. Currently it just loads the thread.
        if($byId) {
            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                SELECT * FROM `{{DBP}}threads` WHERE `id` = :id
            "));
        } else {
            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                SELECT * FROM `{{DBP}}threads` WHERE MATCH(`title`) AGAINST(:id IN BOOLEAN MODE) "
                . (!is_null($topicId) ? "AND `topicId` = :topicId;" : ";"))
            );

            $id = '+' . str_replace('-', ' +', $id);
        }

        $params = array(
            ':id' => $id
        );

        if(!is_null($topicId)) {
            $params[':topicId'] = $topicId;
        }

      if($this->S->executeQuery($params)) {
        $this->lastMessage[] = 'Successfully loaded thread.';
        $tR = $this->S->fetch();

        $thread = new Thread($this->S);
        $thread->setId($tR['id'])
          ->setTitle($tR['title'])
          ->setClosed($tR['closed'])
          ->setPosted($tR['dateCreated'])
          ->setEdited($tR['lastEdited'])
          ->setSticky($tR['sticky'])
          ->setAuthor($tR['authorId'])
          ->setTopicId($tR['topicId'])
          ->setLatestPost($tR['id'])
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
        ':id'           => $id
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
        SET @threadId = :id;

        DELETE FROM `{{DBP}}threads` WHERE `id` = @threadId;
        DELETE FROM `{{DBP}}posts` WHERE `threadId` = @threadId;
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

    public function setLatestPost($_threadId = null) {
        if(is_null($_threadId)) $_threadId = $this->id;

        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
          SELECT `id`, `postDate` FROM `for1234_posts` WHERE `threadId` = :threadId ORDER BY `postDate` DESC LIMIT 1
        "));

        $this->S->executeQuery(array(':threadId' => $_threadId));
        $pst = $this->S->fetch();

        $P = new Post($this->S);
        $this->latestPost = $P->getPost($pst['id']);

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
