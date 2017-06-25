<?php
    namespace ForumLib\Integration;

    use ForumLib\Forums\Thread;
    use ForumLib\Forums\Post;

    abstract class IntegrationBaseThread extends IntegrationBase {
        abstract public function getThreads($topicId, Thread $thread);
        abstract public function createThread(Thread $thread, Post $post);
        abstract public function getThread($id, $byId, $topicId, Thread $thread);
        abstract public function updateThread($id, Thread $thread);
        abstract public function deleteThread($id, Thread $thread);
        abstract public function setLatestPost($id, Thread $thread);
    }