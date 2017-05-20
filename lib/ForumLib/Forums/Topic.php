<?php
  namespace ForumLib\Forums;

  use ForumLib\Database\PSQL;
  use ForumLib\Users\Permissions;

  class Topic extends Base {
    public $enabled;
    public $categoryId;
    public $permissions;
    public $threads;
    public $threadCount;
    public $postCount;

    public function __construct(PSQL $SQL) {
      if(!is_null($SQL)) {
        $this->S = $SQL;
      } else {
        $this->lastError[] = 'Something went wrong while creating the topic object.';
        return false;
      }
    }

    public function createTopic($categoryId = null) {
      if(is_null($categoryId)) $categoryId = $this->categoryId;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        INSERT INTO `{{DBP}}topics` SET
           `categoryId`   = :categoryId
          ,`title`        = :title
          ,`description`  = :description
          ,`enabled`      = :enabled
      "));

      if($this->S->executeQuery(array(
        ':categoryId'   => $categoryId,
        ':title'        => $this->title,
        ':description'  => $this->description,
        ':enabled'      => $this->enabled
      ))) {
        $this->lastMessage[] = 'Successfully created new topic.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while creating topic.';
        }
        return false;
      }
    }

    public function getTopics($categoryId = null) {
      if(is_null($categoryId)) $categoryId = $this->categoryId;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        SELECT * FROM `{{DBP}}topics` WHERE `categoryId` = :categoryId ORDER BY `order` ASC
      "));
      if($this->S->executeQuery(array(
        ':categoryId' => $categoryId
      ))) {
        $tR = $this->S->fetchAll();

        $topics = array();

        for($i = 0; $i < count($tR); $i++) {
          $T = new Topic($this->S);
          $T->setId($tR[$i]['id'])
            ->setTitle($tR[$i]['title'])
            ->setDescription($tR[$i]['description'])
            ->setIcon($tR[$i]['icon'])
            ->setOrder($tR[$i]['order'])
            ->setEnabled($tR[$i]['enabled'])
            ->setCategoryId($tR[$i]['categoryId'])
            ->setPermissions($this->id)
            ->setThreads($this->id);
          $topics[] = $T;
        }

        $this->lastMessage[] = 'Successfully loaded topics.';
        return $topics;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Failed to get topics.';
        }
        return false;
      }
    }

    public function getTopic($id = null, $byId = true, $categoryId = null) {
      if(is_null($id)) $id = $this->id;

      if($byId) {
        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
          SELECT * FROM `{{DBP}}topics` WHERE `id` = :id;
        "));
      } else {
        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
          SELECT * FROM `{{DBP}}topics` WHERE MATCH(`title`) AGAINST(:id IN BOOLEAN MODE) "
          . (!is_null($categoryId) ? "AND `categoryId` = :categoryId;" : ";"))
        );
      }

      $params = array(
        ':id' => $id
      );

      if(!is_null($categoryId)) {
        $params[':categoryId'] = $categoryId;
      }

      if($this->S->executeQuery($params)) {
        $topic = $this->S->fetch();

        $T = new Topic($this->S);
        $T->setId($topic['id'])
          ->setTitle($topic['title'])
          ->setDescription($topic['description'])
          ->setIcon($topic['icon'])
          ->setOrder($topic['order'])
          ->setEnabled($topic['enabled'])
          ->setCategoryId($topic['categoryId'])
          ->setPermissions($this->id)
          ->setThreads($this->id);

        $this->lastMessage[] = 'Successfully fetched topic.';
        return $T;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Failed to get topic.';
        }
        return false;
      }
    }

    public function updateTopic($id = null) {
      if(is_null($id)) $id = $this->id;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        UPDATE `{{DBP}}topics` SET
           `categoryId`   = :categoryId
          ,`title`        = :title
          ,`description`  = :description
          ,`enabled`      = :enabled
        WHERE `id` = :id
      "));

      if($this->S->executeQuery(array(
        ':categoryId'   => $this->categoryId,
        ':title'        => $this->title,
        ':description'  => $this->description,
        ':enabled'      => $this->enabled,
        ':id'           => $id
      ))) {
        $this->lastMessage[] = 'Successfully updated topic.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while updating topic.';
        }
        return false;
      }
    }

    public function deleteTopic($id = null) {
      if(is_null($id)) $id = $this->id;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        DELETE FROM `{{DBP}}topics` WHERE `id` = :id
      "));

      if($this->S->executeQuery(array(
        ':id' => $id
      ))) {
        $this->lastMessage[] = 'Successfully deleted topic.';
        return true;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while deleting topic.';
        }
        return false;
      }
    }

    public function getLatestPost($_topicId = null) {
        if(is_null($_topicId)) $_topicId = $this->id;

        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
            SELECT
                 `P`.`id` `postId`
                ,`T`.`id` `threadId`
            FROM `{{DBP}}posts` `P`
                INNER JOIN `{{DBP}}threads` `T` ON `P`.`threadId` = `T`.`id`
                INNER JOIN `{{DBP}}topics` `F` ON `T`.`topicId` = `F`.`id`
            WHERE `F`.`id` = :topicId
            ORDER BY `P`.`postDate` DESC
            LIMIT 1
        "));
        $this->S->executeQuery(array(':topicId' => $_topicId));
        $result = $this->S->fetch();

        $P = new Post($this->S);
        $T = new Thread($this->S);

        $post = $P->getPost($result['postId']);
        $thread = $T->getThread($result['threadId']);

        return array(
            'thread' => $thread,
            'post'   => $post
        );
    }

      public function setThreadCount() {
          $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
          SELECT COUNT(*) `count` FROM `{{DBP}}threads` WHERE `topicId` = :topicId
        "));
          $this->S->executeQuery(array('topicId' => $this->id));
          $rslt = $this->S->fetch();

          $this->threadCount = $rslt['count'];
          return $this;
      }

      public function setPostCount() {
          $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
            SELECT
                COUNT(*) `count`
            FROM `{{DBP}}posts` `P`
                INNER JOIN `{{DBP}}threads` `T` ON `P`.`threadId` = `T`.`id`
                INNER JOIN `{{DBP}}topics` `F` ON `T`.`topicId` = `F`.`id`
            WHERE `F`.`id` = :topicId
            ORDER BY `P`.`postDate` DESC
        "));
        $this->S->executeQuery(array('topicId' => $this->id));
        $rslt = $this->S->fetch();

        $this->postCount = $rslt['count'];
        return $this;
    }

    public function checkThreadName($_title, Topic $_topic) {
        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
            SELECT `id` FROM `{{DBP}}threads` WHERE `topicId` = :topicId AND MATCH(`title`) AGAINST(:title IN BOOLEAN MODE)
        "));
        $this->S->executeQuery(array(
            ':topicId' => $_topic->id,
            ':title' => $_title
        ));
        return count($this->S->fetchAll());
    }

    public function setCategoryId($_cid) {
      $this->categoryId = $_cid;
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

    public function setThreads($_threadId = null) {
      if(is_null($_threadId)) $_threadId = $this->id;

      $T = new Thread($this->S);
      $this->threads = $T->getThreads($_threadId);
      return $this;
    }

    public function getType() {
      return __CLASS__;
    }
  }
