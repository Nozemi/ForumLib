<?php
    namespace ForumLib\Integration\Nozum;

    use ForumLib\Forums\Post;
    use ForumLib\Forums\Thread;
    use ForumLib\Integration\IntegrationBaseThread;

    class NozumThread extends IntegrationBaseThread {

        public function getThreads($topicId, Thread $thread) {
            if(is_null($topicId)) $topicId = $thread->topicId;

            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
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
            "));

            if($this->S->executeQuery(array(
                ':topicId' => $topicId
            ))) {
                $tR = $this->S->fetchAll();

                $threads = array();

                for($i = 0; $i < count($tR); $i++) {
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
            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
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
            "));
            if($this->S->executeQuery(array(
                ':title'        => $this->title,
                ':topicId'      => $this->topicId,
                ':authorId'     => $this->author->id,
                ':dateCreated'  => date('Y-m-d H:i:s'),
                ':lastEdited'   => date('Y-m-d H:i:s'),
                ':sticky'       => 0,
                ':closed'       => 0,

                ':post_content_html'  => $post->post_html,
                ':post_content_text'  => $post->post_text,
                ':pAuthorId'          => $post->author->id,
                ':postDate'           => date('Y-m-d H:i:s'),
                ':editDate'           => date('Y-m-d H:i:s')
            ))) {
                $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "SELECT `id` FROM `{{DBP}}threads` ORDER BY `dateCreated` DESC LIMIT 1;"));
                $this->S->executeQuery();

                $result = $this->S->fetch();
                $this->setId($result['id']);

                $this->lastMessage[] = 'Successfully created thread.';
                return true;
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = $this->S->getLastError();
                } else {
                    $this->lastError[] = 'Something went wrong while posting thread.';
                }
                return false;
            }
        }

        public function getThread($id, $byId, $topicId, Thread $thread) {
            if(is_null($id)) $id = $thread->id;

            // We'll need to load the thread and it's posts. Currently it just loads the thread.
            if($byId) {
                $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                  SELECT * FROM `{{DBP}}threads` WHERE `id` = :id
                "));
            } else {
                $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                    SELECT * FROM `{{DBP}}threads` WHERE MATCH(`title`) AGAINST(:id IN BOOLEAN MODE) "
                    . (!is_null($topicId) ? "AND `topicId` = :topicId;" : ";"))
                );

                $id = '+' . str_replace('-', ' +', $id);
            }

            $params = array(
                ':id' => $id
            );

            if(!is_null($topicId)) {
                $params[':topicId'] = $topicId;
            }

            if($this->S->executeQuery($params)) {
                $this->lastMessage[] = 'Successfully loaded thread.';
                $tR = $this->S->fetch();

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
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = $this->S->getLastError();
                } else {
                    $this->lastError[] = 'Something went wrong while loading thread.';
                }
                return false;
            }
        }

        public function updateThread($id, Thread $thread) {
            if(is_null($id)) $id = $thread->id;

            $this->S->prepareQuery($this->S->executeQuery('{{DBP}}', "
                UPDATE `{{DBP}}threads` SET
                     `title`        = :title
                    ,`topicId`      = :topicId
                    ,`authorId`     = :authorId
                    ,`dateCreated`  = :dateCreated
                    ,`lastEdited`   = :lastEdited
                    ,`sticky`       = :sticky
                    ,`closed`       = :closed
                WHERE `id` = :id
            "));

            if($this->S->executeQuery(array(
                ':title'        => $thread->title,
                ':topicId'      => $thread->topicId,
                ':authorId'     => $thread->author->id,
                ':dateCreated'  => $thread->posted,
                ':lastEdited'   => $thread->edited,
                ':sticky'       => $thread->sticky,
                ':closed'       => $thread->closed,
                ':id'           => $id
            ))) {
                $this->lastMessage[] = 'Successfully updated thread.';
                return true;
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = $this->S->getLastError();
                } else {
                    $this->lastError[] = 'Something went wrong while updating thread.';
                }
                return false;
            }
        }

        public function deleteThread($id, Thread $thread) {
            if(is_null($id)) $id = $thread->id;

            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                SET @threadId = :id;
                
                DELETE FROM `{{DBP}}threads` WHERE `id` = @threadId;
                DELETE FROM `{{DBP}}posts` WHERE `threadId` = @threadId;
            "));

            if($this->S->executeQuery(array(
                ':id' => $id
            ))) {
                $this->lastMessage[] = 'Successfully deleted thread.';
                return true;
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = $this->S->getLastError();
                } else {
                    $this->lastError[] = 'Something went wrong while deleting thread.';
                }
                return false;
            }
        }

        public function setLatestPost($id, Thread $thread) {
            if(is_null($id)) $id = $thread->id;

            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
              SELECT `id`, `postDate` FROM `for1234_posts` WHERE `threadId` = :threadId ORDER BY `postDate` DESC LIMIT 1
            "));

            $this->S->executeQuery(array(':threadId' => $id));
            $pst = $this->S->fetch();

            $P = new Post($this->S);
            return $P->getPost($pst['id']);
        }
    }