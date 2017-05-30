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
            // TODO: Implement getTopic() method.
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