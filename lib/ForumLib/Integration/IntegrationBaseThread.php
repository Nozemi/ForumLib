<?php
    namespace ForumLib\Integration;

    use ForumLib\Forums\Thread;
    use ForumLib\Forums\Post;

    abstract class IntegrationBaseThread extends IntegrationBase {
        abstract public function getThreads($topicId, Thread $thread);
        abstract public function createThread(Post $post);
    }