<?php
    namespace ForumLib\Integration\Nozum;

    use ForumLib\Forums\Post;
    use ForumLib\Forums\Thread;
    use ForumLib\Integration\IntegrationBaseThread;

    class NozumThread extends IntegrationBaseThread {
        public function getThreads($topicId, Thread $thread) {
            // TODO: Implement getThreads() method.
        }

        public function createThread(Post $post) {
            // TODO: Implement createThread() method.
        }

        public function getThread($id, $byId, $topicId, Thread $thread) {
            // TODO: Implement getThread() method.
        }

        public function updateThread($id, Thread $thread) {
            // TODO: Implement updateThread() method.
        }

        public function deleteThread($id, Thread $thread) {
            // TODO: Implement deleteThread() method.
        }

        public function setLatestPost($id, Thread $thread) {
            // TODO: Implement setLatestPost() method.
        }
    }