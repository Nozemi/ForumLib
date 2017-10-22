<?php
    namespace SBLib\Integration\Nozum;

    use SBLib\Database\DBUtilQuery;
    use SBLib\Forums\Post;
    use SBLib\Forums\Thread;
    use SBLib\Integration\IntegrationBaseThread;

    use \PDO;

    class NozumThread extends IntegrationBaseThread {

        public function getThreads($topicId, Thread $thread) {
            if(is_null($topicId)) $topicId = $thread->topicId;

            $getThreads = new DBUtilQuery;
            $getThreads->setName('getThreads')
                ->setQuery("
                    SELECT * FROM (
                        SELECT
                             `T`.*
                            ,`P`.`postDate`
                        FROM `{{DBP}}posts` `P`
                            INNER JOIN `{{DBP}}threads` `T` ON `P`.`threadId` = `T`.`id`
                            INNER JOIN `{{DBP}}topics` `F` ON `T`.`topicId` = `F`.`id`
                        WHERE `F`.`id` = :topicId
                        ORDER BY `P`.`postDate` DESC ) `threads`
                    GROUP BY `id` ORDER BY `postDate` DESC
                ")
                ->addParameter(':topicId', $topicId, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();

            $tR = $getThreads->result();
            $threads = array();

            if(is_array($tR)) {
                for ($i = 0; $i < count($tR); $i++) {
                    $T = new Thread($this->S);
                    $T->setId($tR[$i]['id'])
                        ->setTitle($tR[$i]['title'])
                        ->setAuthor($tR[$i]['authorId'])
                        ->setSticky($tR[$i]['sticky'])
                        ->setClosed($tR[$i]['closed'])
                        ->setPosted($tR[$i]['dateCreated'])
                        ->setEdited($tR[$i]['lastEdited'])
                        ->setTopicId($tR[$i]['topicId'])
                        ->setPermissions($T->id);
                    $threads[] = $T;
                }
            }


            $this->lastMessage[] = 'Successfully loaded threads.';
            return $threads;
        }

        public function createThread(Thread $thread, Post $post) {
            $createThread = new DBUtilQuery;
            $createThread->setName('createThread')
                ->setQuery("
                    INSERT INTO `{{DBP}}threads` (
                         `title`
                        ,`topicId`
                        ,`authorId`
                        ,`dateCreated`
                        ,`lastEdited`
                        ,`sticky`
                        ,`closed`
                    ) VALUES (
                         :title
                        ,:topicId
                        ,:authorId
                        ,:dateCreated
                        ,:lastEdited
                        ,:sticky
                        ,:closed
                    );
                    
                    INSERT INTO `{{DBP}}posts` (
                         `post_content_html`
                        ,`post_content_text`
                        ,`authorId`
                        ,`threadId`
                        ,`postDate`
                        ,`editDate`
                        ,`originalPost`
                    ) VALUES (
                         :post_content_html
                        ,:post_content_text
                        ,:pAuthorId
                        ,LAST_INSERT_ID()
                        ,:postDate
                        ,:editDate
                        ,1
                    );
                ")
                ->setParameters(array(
                    array(':title', $thread->title, PDO::PARAM_STR),
                    array(':topicId', $thread->topicId, PDO::PARAM_INT),
                    array(':authorId', $thread->author->id, PDO::PARAM_INT),
                    array(':dateCreated', date('Y-m-d H:i:s'), PDO::PARAM_STR),
                    array(':lastEdited', date('Y-m-d H:i:s'), PDO::PARAM_STR),
                    array(':sticky', 0, PDO::PARAM_BOOL),
                    array(':closed', 0, PDO::PARAM_BOOL),

                    array(':post_content_html', $post->post_html, PDO::PARAM_STR),
                    array(':post_content_text', $post->post_text, PDO::PARAM_STR),
                    array(':pAuthorId', $post->author->id, PDO::PARAM_INT),
                    array(':postDate', date('Y-m-d H:i:s'), PDO::PARAM_STR),
                    array(':editDate', date('Y-m-d H:i:s'), PDO::PARAM_STR)
                ))
                ->setDBUtil($this->S)
                ->execute();

            return $this->S->getLastInsertId();
        }

        public function getThread($id, $byId, $topicId, Thread $thread) {
            if(is_null($id)) $id = $thread->id;

            $getThread = new DBUtilQuery;
            $getThread->setName('getThread')
                ->setMultipleRows(false)
                ->setDBUtil($this->S);

            // We'll need to load the thread and it's posts. Currently it just loads the thread.
            if($byId) {
                $getThread->setQuery("SELECT * FROM `{{DBP}}threads` WHERE `id` = :id")
                    ->addParameter(':id', $id, PDO::PARAM_INT);
            } else {
                $id = '+' . str_replace('-', ' +', $id);

                $getThread->setQuery("SELECT * FROM `{{DBP}}threads` WHERE MATCH(`title`) AGAINST(:id IN BOOLEAN MODE) "
                    . (!is_null($topicId) ? "AND `topicId` = :topicId;" : ";"))
                    ->addParameter(':id', $id, PDO::PARAM_STR)
                    ->addParameter(':topicId', $topicId, PDO::PARAM_INT);
            }

            $getThread->execute();

            $tR = $this->S->getResultByName($getThread->getName());

            $theThread = new Thread($this->S);
            $theThread->setId($tR['id'])
                ->setTitle($tR['title'])
                ->setClosed($tR['closed'])
                ->setPosted($tR['dateCreated'])
                ->setEdited($tR['lastEdited'])
                ->setSticky($tR['sticky'])
                ->setAuthor($tR['authorId'])
                ->setTopicId($tR['topicId'])
                ->setLatestPost($tR['id'])
                ->setPosts($theThread->id);

            return $theThread;
        }

        public function updateThread($id, Thread $thread) {
            if(is_null($id)) $id = $thread->id;

            $updateThread = new DBUtilQuery;
            $updateThread->setName('updateThread')
                ->setQuery("
                    UPDATE `{{DBP}}threads` SET
                         `title`        = :title
                        ,`topicId`      = :topicId
                        ,`authorId`     = :authorId
                        ,`dateCreated`  = :dateCreated
                        ,`lastEdited`   = :lastEdited
                        ,`sticky`       = :sticky
                        ,`closed`       = :closed
                    WHERE `id` = :id                
                ")
                ->addParameter(':title', $thread->title, PDO::PARAM_STR)
                ->addParameter(':topicId', $thread->topicId, PDO::PARAM_INT)
                ->addParameter(':authorId', $thread->author->id, PDO::PARAM_INT)
                ->addParameter(':dateCreated', $thread->posted, PDO::PARAM_STR)
                ->addParameter(':lastEdited', $thread->edited, PDO::PARAM_STR)
                ->addParameter(':sticky', $thread->sticky, PDO::PARAM_BOOL)
                ->addParameter(':closed', $thread->closed, PDO::PARAM_BOOL)
                ->addParameter(':id', $id, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();
        }

        public function deleteThread($id, Thread $thread) {
            if(is_null($id)) $id = $thread->id;

            $deleteThread = new DBUtilQuery;
            $deleteThread->setName('deleteThread')
                ->setQuery("
                    SET @threadId = :id;
                    
                    DELETE FROM `{{DBP}}threads` WHERE `id` = @threadId;
                    DELETE FROM `{{DBP}}posts` WHERE `threadId` = @threadId;
                ")
                ->addParameter(':id', $id, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();
        }

        public function setLatestPost($id, Thread $thread) {
            if(is_null($id)) $id = $thread->id;

            $latestPost = new DBUtilQuery;
            $latestPost->setName('threadLatestPost')
                ->setMultipleRows(false)
                ->setQuery("SELECT `id`, `postDate` FROM `for1234_posts` WHERE `threadId` = :threadId ORDER BY `postDate` DESC LIMIT 1")
                ->addParameter(':threadId', $id, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();

            $pst = $latestPost->result();

            $P = new Post($this->S);
            return $P->getPost($pst['id']);
        }
    }