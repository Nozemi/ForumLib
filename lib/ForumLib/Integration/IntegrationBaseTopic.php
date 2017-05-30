<?php
    namespace ForumLib\Integration;

    use ForumLib\Forums\Category;
    use ForumLib\Forums\Topic;

    abstract class IntegrationBaseTopic extends IntegrationBase {
        abstract public function createTopic($categoryId, Category $cat);
        abstract public function getTopics($categoryId, Category $cat);
        abstract public function getTopic($id, $byId, $categoryId, Category $cat);
        abstract public function updateTopic($categoryId, Category $cat);
        abstract public function deleteTopic($categoryId, Category $cat);
        abstract public function getLatestPost($topId, Category $cat);
        abstract public function setThreadCount(Category $cat);
        abstract public function setPostCount(Category $cat);
        abstract public function checkThreadName($title, Topic $topic);
    }