<?php
    namespace SBLib\Integration\Nozum;

    use SBLib\Database\DBUtilQuery;
    use SBLib\Forums\Thread;
    use SBLib\Forums\Topic;
    use SBLib\Forums\Post;
    use SBLib\Integration\IntegrationBaseTopic;

    use \PDO;

    class NozumTopic extends IntegrationBaseTopic {

        public function createTopic($categoryId, Topic $top) {
            if(is_null($categoryId)) $categoryId = $top->categoryId;

            $createTopic = new DBUtilQuery;
            $createTopic->setName('createTopic')
                ->setQuery("
                    INSERT INTO `{{DBP}}topics` SET
                       `categoryId`   = :categoryId
                      ,`title`        = :title
                      ,`description`  = :description
                      ,`enabled`      = :enabled
                      ,`order`        = :order
                ")
                ->addParameter(':categoryId', $categoryId, PDO::PARAM_INT)
                ->addParameter(':title', $top->title, PDO::PARAM_STR)
                ->addParameter(':description', $top->description, PDO::PARAM_STR)
                ->addParameter(':enabled', $top->enabled, PDO::PARAM_BOOL)
                ->addParameter(':order', $top->order, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();
        }

        public function getTopics($categoryId, Topic $top) {
            if(is_null($categoryId)) $categoryId = $top->categoryId;

            $getTopics = new DBUtilQuery;
            $getTopics->setName('getTopics')
                ->setQuery("SELECT * FROM `{{DBP}}topics` WHERE `categoryId` = :categoryId ORDER BY `order` ASC")
                ->addParameter(':categoryId', $categoryId, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();

            $tR = $this->S->getResultByName($getTopics->getName());

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
        }

        public function getTopic($id, $byId, $categoryId, Topic $top) {
            if(is_null($id)) $id = $top->id;

            $getTopic = new DBUtilQuery;
            $getTopic->setName('getTopic')
                ->setDBUtil($this->S)
                ->setMultipleRows(false);

            if($byId) {
                $getTopic->setQuery("SELECT * FROM `{{DBP}}topics` WHERE `id` = :id;")
                    ->addParameter(':id', $id, PDO::PARAM_INT);
            } else {
                $getTopic->setQuery("SELECT * FROM `{{DBP}}topics` WHERE MATCH(`title`) AGAINST(:id IN BOOLEAN MODE) "
                    . (!is_null($categoryId) ? "AND `categoryId` = :categoryId;" : ";"))
                    ->addParameter(':id', $id, PDO::PARAM_STR)
                    ->addParameter(':categoryId', $categoryId, PDO::PARAM_INT);
            }

            $getTopic->execute();

            $topic = $getTopic->result();

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
        }

        public function updateTopic($id, Topic $top) {
            if(is_null($id)) $id = $top->id;

            $updateTopic = new DBUtilQuery;
            $updateTopic->setName('updateTopic')
                ->setQuery("
                    UPDATE `{{DBP}}topics` SET
                         `categoryId`   = :categoryId
                        ,`title`        = :title
                        ,`description`  = :description
                        ,`enabled`      = :enabled
                        ,`order`        = :order
                    WHERE `id` = :id
                ")
                ->addParameter(':categoryId', $this->categoryId, PDO::PARAM_INT)
                ->addParameter(':title', $this->title, PDO::PARAM_STR)
                ->addParameter(':description', $this->description, PDO::PARAM_STR)
                ->addParameter(':enabled', $this->enabled, PDO::PARAM_BOOL)
                ->addParameter(':order', $this->order, PDO::PARAM_INT)
                ->addParameter(':id', $id, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();
        }

        public function deleteTopic($id, Topic $top) {
            if(is_null($id)) $id = $top->id;

            $deleteTopic = new DBUtilQuery;
            $deleteTopic->setName('deleteTopic')
                ->setQuery("DELETE FROM `{{DBP}}topics` WHERE `id` = :id")
                ->addParameter(':id', $id, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();
        }

        public function getLatestPost($topId, Topic $top) {
            if(is_null($topId)) $topId = $top->id;

            $latestPost = new DBUtilQuery;
            $latestPost->setName('topicLatestPost')
                ->setMultipleRows(false)
                ->setQuery("
                    SELECT
                        `P`.`id` `postId`
                        ,`T`.`id` `threadId`
                    FROM `{{DBP}}posts` `P`
                        INNER JOIN `{{DBP}}threads` `T` ON `P`.`threadId` = `T`.`id`
                        INNER JOIN `{{DBP}}topics` `F` ON `T`.`topicId` = `F`.`id`
                    WHERE `F`.`id` = :topicId
                    ORDER BY `P`.`postDate` DESC
                    LIMIT 1
                ")
                ->addParameter(':topicId', $topId, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();

            $result = $latestPost->result();

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
            $threadCount = new DBUtilQuery;
            $threadCount->setName('threadCount')
                ->setMultipleRows(false)
                ->setQuery("SELECT COUNT(*) `count` FROM `{{DBP}}threads` WHERE `topicId` = :topicId")
                ->addParameter(':topicId', $top->id, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();

            $result = $threadCount->result();

            return $result['count'];
        }

        public function setPostCount(Topic $top) {
            $postCount = new DBUtilQuery;
            $postCount->setName('postCount')
                ->setMultipleRows(false)
                ->setQuery("
                    SELECT
                      COUNT(*) `count`
                    FROM `{{DBP}}posts` `P`
                        INNER JOIN `{{DBP}}threads` `T` ON `P`.`threadId` = `T`.`id`
                        INNER JOIN `{{DBP}}topics` `F` ON `T`.`topicId` = `F`.`id`
                    WHERE `F`.`id` = :topicId
                    ORDER BY `P`.`postDate` DESC
                ")
                ->addParameter(':topicId', $top->id, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();

            $result = $postCount->result();

            return $result['count'];
        }

        public function checkThreadName($_title, Topic $_topic) {
            $threadName = new DBUtilQuery;
            $threadName->setName('threadName')
                ->setQuery("SELECT `id` FROM `{{DBP}}threads` WHERE `topicId` = :topicId AND MATCH(`title`) AGAINST(:title IN BOOLEAN MODE)")
                ->addParameter(':topicId', $_topic->id, PDO::PARAM_INT)
                ->addParameter(':title', $_title, PDO::PARAM_STR)
                ->setDBUtil($this->S)
                ->execute();

            return count($this->S->getResultByName($threadName->getName()));
        }
    }