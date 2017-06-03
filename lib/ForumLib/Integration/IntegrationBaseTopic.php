<?php
    namespace ForumLib\Integration;

<<<<<<< HEAD
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
=======
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
>>>>>>> 615a34eea3757a7329b41b8f2d8bd5f54f42e90f
        abstract public function checkThreadName($title, Topic $topic);
    }