<?php
    namespace ForumLib\Integration\vB3;

    use ForumLib\Forums\Topic;
    use ForumLib\Integration\IntegrationBaseTopic;

    class vB3Topic extends IntegrationBaseTopic {

        public function createTopic($categoryId, Topic $top) {
            // TODO: Implement createTopic() method.
        }

        public function getTopics($categoryId, Topic $top) {
            // TODO: Implement getTopics() method.
        }

        public function getTopic($id, $byId, $categoryId, Topic $top) {
            if(is_null($id)) $id = $top->id;

            if($byId) {
                $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                  SELECT * FROM `{{DBP}}forum` WHERE `forumid` = :id;
                "));
            } else {
                $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                    SELECT * FROM `{{DBP}}forum` WHERE MATCH(`title`) AGAINST(:id IN BOOLEAN MODE) "
                    . (!is_null($categoryId) ? "AND `parentid` = :categoryId;" : ";"))
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
                $T->setId($topic['forumid'])
                    ->setTitle($topic['title'])
                    ->setDescription($topic['description'])
                    ->setOrder($topic['displayorder'])
                    ->setCategoryId($topic['parentid'])
                    ->setPermissions($topic['forumid'])
                    ->setThreads($topic['forumid']);

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

        public function checkThreadName($title, Topic $topic) {
            // TODO: Implement checkThreadName() method.
        }
    }