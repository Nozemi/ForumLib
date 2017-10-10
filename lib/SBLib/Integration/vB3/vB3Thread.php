<?php
    namespace SBLib\Integration\vB3;

    use SBLib\Database\DBUtilQuery;
    use SBLib\Forums\Post;
    use SBLib\Forums\Thread;
    use SBLib\Integration\IntegrationBaseThread;

    use \PDO;

    class vB3Thread extends IntegrationBaseThread {

        public function getThreads($topicId, Thread $thread) {
            if(is_null($topicId)) $topicId = $thread->topicId;

            $getThreads = new DBUtilQuery;
            $getThreads->setName('getThreads')
                ->setMultipleRows(true)
                ->setDBUtil($this->S)
                ->setQuery("
                    SELECT
                      *
                    FROM `thread`
                    WHERE `forumid` = :topicId
                    ORDER BY `dateline` DESC
                ")
                ->addParameter(':topicId', $topicId, PDO::PARAM_INT)
                ->execute();

            $tR = $getThreads->result();

            $threads = array();

            for($i = 0; $i < count($tR); $i++) {
                $T = new Thread($this->S);
                $T->setId($tR[$i]['threadid'])
                    ->setTitle($tR[$i]['title'])
                    ->setAuthor($tR[$i]['postuserid'])
                    ->setPosted($tR[$i]['dateline'])
                    ->setTopicId($tR[$i]['forumid']);
                $threads[] = $T;
            }

            $this->lastMessage[] = 'Successfully loaded threads.';
            return $threads;
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