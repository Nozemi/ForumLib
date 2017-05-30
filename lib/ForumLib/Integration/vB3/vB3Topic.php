<?php
    namespace ForumLib\Integration\vB3;

    use ForumLib\Forums\Category;
    use ForumLib\Forums\Topic;
    use ForumLib\Integration\IntegrationBaseTopic;

    class vB3Topic extends IntegrationBaseTopic {
        public function createTopic($categoryId, Category $cat) {
            // TODO: Implement createTopic() method.
        }

        public function getTopics($categoryId, Category $cat) {
            // TODO: Implement getTopics() method.
        }

        public function getTopic($id, $byId, $categoryId, Category $cat) {
            // TODO: Implement getTopic() method.
        }

        public function updateTopic($categoryId, Category $cat) {
            // TODO: Implement updateTopic() method.
        }

        public function deleteTopic($categoryId, Category $cat) {
            // TODO: Implement deleteTopic() method.
        }

        public function getLatestPost($topId, Category $cat) {
            // TODO: Implement getLatestPost() method.
        }

        public function setThreadCount(Category $cat) {
            // TODO: Implement setThreadCount() method.
        }

        public function setPostCount(Category $cat) {
            // TODO: Implement setPostCount() method.
        }

        public function checkThreadName($title, Topic $topic) {
            // TODO: Implement checkThreadName() method.
        }
    }