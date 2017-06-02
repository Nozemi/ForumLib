<?php
    namespace ForumLib\Integration\Nozum;

    use ForumLib\Database\PSQL;
    use ForumLib\Forums\Thread;
    use ForumLib\Forums\Topic;
    use ForumLib\Forums\Post;
    use ForumLib\Integration\IntegrationBaseTopic;

    class NozumTopic extends IntegrationBaseTopic {

        public function createTopic($categoryId, Topic $top) {
            if(is_null($categoryId)) $categoryId = $top->categoryId;

            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                INSERT INTO `{{DBP}}topics` SET
                   `categoryId`   = :categoryId
                  ,`title`        = :title
                  ,`description`  = :description
                  ,`enabled`      = :enabled
                  ,`order`        = :order
              "));

            if($this->S->executeQuery(array(
                ':categoryId'   => $categoryId,
                ':title'        => $top->title,
                ':description'  => $top->description,
                ':enabled'      => $top->enabled,
                ':order'        => $top->order
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

        public function getTopics($categoryId, Topic $top) {
            if(is_null($categoryId)) $categoryId = $top->categoryId;

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
                        ->setPermissions($tR[$i]['id'])
                        ->setThreads($tR[$i]['id']);
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

        public function getTopic($id, $byId, $categoryId, Topic $top) {
            if(is_null($id)) $id = $top->id;

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
                    ->setPermissions($topic['id'])
                    ->setThreads($topic['id']);

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

        public function updateTopic($id, Topic $top) {
            if(is_null($id)) $id = $top->id;

            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                UPDATE `{{DBP}}topics` SET
                    `categoryId`   = :categoryId
                    ,`title`        = :title
                    ,`description`  = :description
                    ,`enabled`      = :enabled
                    ,`order`        = :order
                WHERE `id` = :id
            "));

            if($this->S->executeQuery(array(
                ':categoryId'   => $this->categoryId,
                ':title'        => $this->title,
                ':description'  => $this->description,
                ':enabled'      => $this->enabled,
                ':order'        => $this->order,
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

        public function deleteTopic($id, Topic $top) {
            if(is_null($id)) $id = $top->id;

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

        public function getLatestPost($topId, Topic $top) {
            if(is_null($topId)) $topId = $top->id;

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
            $this->S->executeQuery(array(':topicId' => $topId));
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

        public function setThreadCount(Topic $top) {
            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                SELECT COUNT(*) `count` FROM `{{DBP}}threads` WHERE `topicId` = :topicId
            "));

            $this->S->executeQuery(array('topicId' => $top->id));
            $rslt = $this->S->fetch();

            $this->threadCount = $rslt['count'];
            return $this;
        }

        public function setPostCount(Topic $top) {
            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                SELECT
                  COUNT(*) `count`
                FROM `{{DBP}}posts` `P`
                    INNER JOIN `{{DBP}}threads` `T` ON `P`.`threadId` = `T`.`id`
                    INNER JOIN `{{DBP}}topics` `F` ON `T`.`topicId` = `F`.`id`
                WHERE `F`.`id` = :topicId
                ORDER BY `P`.`postDate` DESC
            "));

            $this->S->executeQuery(array('topicId' => $top->id));
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
    }