<?php
    namespace ForumLib\Integration\vB3;

    use ForumLib\Forums\Post;
    use ForumLib\Forums\Thread;
    use ForumLib\Integration\IntegrationBaseThread;

    class vB3Thread extends IntegrationBaseThread {

        public function getThreads($topicId, Thread $thread) {
            if(is_null($topicId)) $topicId = $thread->topicId;

            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                SELECT
                  *
                FROM `thread`
                WHERE `forumid` = :topicId
                ORDER BY `dateline` DESC
            "));

            if($this->S->executeQuery(array(
                ':topicId' => $topicId
            ))) {
                $tR = $this->S->fetchAll();

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
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = $this->S->getLastError();
                } else {
                    $this->lastError[] = 'Something went wrong while getting threads.';
                }
                return false;
            }
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