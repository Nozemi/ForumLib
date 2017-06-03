<?php
    namespace ForumLib\Integration;

    use ForumLib\Forums\Topic;

    abstract class IntegrationBaseTopic extends IntegrationBase {
        abstract public function createTopic($categoryId, Topic $top);
        abstract public function getTopics($categoryId, Topic $top);
        abstract public function getTopic($id, $byId, $categoryId, Topic $top);
        abstract public function updateTopic($categoryId, Topic $top);
        abstract public function deleteTopic($categoryId, Topic $top);
        abstract public function getLatestPost($topId, Topic $top);
        abstract public function setThreadCount(Topic $top);
        abstract public function setPostCount(Topic $top);
        abstract public function checkThreadName($title, Topic $topic);
    }