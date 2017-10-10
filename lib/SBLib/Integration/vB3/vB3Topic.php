<?php
    namespace SBLib\Integration\vB3;

    use SBLib\Database\DBUtilQuery;
    use SBLib\Forums\Topic;
    use SBLib\Integration\IntegrationBaseTopic;

    use \PDO;

    class vB3Topic extends IntegrationBaseTopic {

        public function createTopic($categoryId, Topic $top) {
            // TODO: Implement createTopic() method.
        }

        public function getTopics($categoryId, Topic $top) {
            // TODO: Implement getTopics() method.
        }

        public function getTopic($id, $byId, $categoryId, Topic $top) {
            if(is_null($id)) $id = $top->id;

            $getTopic = new DBUtilQuery;
            $getTopic->setName('getTopic')
                ->setDBUtil($this->S)
                ->setMultipleRows(false);

            if($byId) {
                $getTopic->setQuery("SELECT * FROM `{{DBP}}forum` WHERE `forumid` = :id;")
                    ->addParameter(':id', $id, PDO::PARAM_INT);
            } else {
                $getTopic->setQuery(
                        "SELECT * FROM `{{DBP}}forum` WHERE MATCH(`title`) AGAINST(:id IN BOOLEAN MODE) "
                        . (!is_null($categoryId) ? "AND `parentid` = :categoryId;" : ";")
                    )
                    ->addParameter(':id', $id, PDO::PARAM_INT)
                    ->addParameter(':categoryId', $categoryId, PDO::PARAM_INT);
            }

            $topic = $getTopic->execute()->result();

            $T = new Topic($this->S);
            $T->setId($topic['forumid'])
                ->setTitle($topic['title'])
                ->setDescription($topic['description'])
                ->setOrder($topic['displayorder'])
                ->setCategoryId($topic['parentid'])
                ->setPermissions($topic['forumid'])
                ->setThreads($topic['forumid']);

            $this->lastMessage[] = 'Successfully fetched topic.';
            return $T;
        }

        public function updateTopic($categoryId, Topic $top) {
            // TODO: Implement updateTopic() method.
        }

        public function deleteTopic($categoryId, Topic $top) {
            // TODO: Implement deleteTopic() method.
        }

        public function getLatestPost($topId, Topic $top) {
            // TODO: Implement getLatestPost() method.
        }

        public function setThreadCount(Topic $top) {
            // TODO: Implement setThreadCount() method.
        }

        public function setPostCount(Topic $top) {
            // TODO: Implement setPostCount() method.
        }

        public function checkThreadName($title, Topic $top) {
            // TODO: Implement checkThreadName() method.
        }
    }