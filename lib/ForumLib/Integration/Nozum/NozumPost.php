<?php
    namespace ForumLib\Integration\Nozum;

    use ForumLib\Forums\Post;
    use ForumLib\Forums\Thread;
    use ForumLib\Integration\IntegrationBasePost;

    class NozumPost extends IntegrationBasePost {

        public function createPost(Post $post) {
            if(empty($post->post_html) && empty($post->post_text)) {
                $this->lastError[] = 'Post content can\'t be empty.';
                return false;
            }

            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                INSERT INTO `{{DBP}}posts` (
                     `post_content_html`
                    ,`post_content_text`
                    ,`authorId`
                    ,`threadId`
                    ,`postDate`
                    ,`editDate`
                ) VALUES (
                     :post_content_html
                    ,:post_content_text
                    ,:authorId
                    ,:threadId
                    ,:postDate
                    ,:editDate
                );
            "));

            if($this->S->executeQuery(array(
                ':post_content_html' => $post->post_html,
                ':post_content_text' => $post->post_text,
                ':authorId'          => $post->author->id,
                ':threadId'          => $post->threadId,
                ':postDate'          => date('Y-m-d H:i:s'),
                ':editDate'          => date('Y-m-d H:i:s')
            ))) {
                $this->lastMessage[] = 'Post successfully created.';
                return true;
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = $this->S->getLastError();
                } else {
                 $this->lastError[] = 'Something went wrong while submitting post.';
                }
                return false;
            }
        }

        public function getPosts($threadId, Post $post) {
            if(is_null($threadId)) $threadId = $post->threadId;

            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                SELECT * FROM `{{DBP}}posts` WHERE `threadId` = :threadId ORDER BY `postDate` ASC"
            ));

            if($this->S->executeQuery(array(
                ':threadId' => $threadId
            ))) {
                $this->lastMessage[] = 'Successfully fetched posts.';

                $posts = $this->S->fetchAll();
                $thePosts = array();

                for($i = 0; $i < count($posts); $i++) {
                    $thePost = new Post($this->S);
                    $thePost->setId($posts[$i]['id'])
                        ->setThreadId($posts[$i]['threadId'])
                        ->setAuthor($posts[$i]['authorId'])
                        ->setPostDate($posts[$i]['postDate'])
                        ->setEditDate($posts[$i]['editDate'])
                        ->setHTML($posts[$i]['post_content_html'])
                        ->setText($posts[$i]['post_content_text'])
                        ->setOriginalPost($posts[$i]['originalPost']);

                    $thePosts[] = $thePost;
                }

                return $thePosts;
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = $this->S->getLastError();
                } else {
                    $this->lastError[] = 'Something went wrong while fetching posts.';
                }

                return false;
            }
        }

        public function getPost($id, Post $post) {
            if(is_null($id)) $id = $post->id;

            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "SELECT * FROM `{{DBP}}posts` WHERE `id` = :id"));
            if($this->S->executeQuery(array(
                ':id' => $id
            ))) {
                $rpost = $this->S->fetch();

                $thePost = new Post($this->S);
                $thePost->setId($rpost['id'])
                    ->setThreadId($rpost['threadId'])
                    ->setAuthor($rpost['authorId'])
                    ->setPostDate($rpost['postDate'])
                    ->setEditDate($rpost['editDate'])
                    ->setHTML($rpost['post_content_html'])
                    ->setText($rpost['post_content_text'])
                    ->setOriginalPost($rpost['originalPost']);

                $this->lastMessage[] = 'Successfully fetched posts.';

                return $thePost;
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = $this->S->getLastError();
                } else {
                    $this->lastError[] = 'Something went wrong while fetching post.';
                }

                return false;
            }
        }

        public function updatePost(Post $post) {
            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                UPDATE `{{DBP}}posts` SET
                   `post_content_html`  = :post_content_html
                  ,`post_content_text`  = :post_content_text
                  ,`authorId`           = :authorId
                  ,`threadId`           = :threadId
                  ,`editDate`           = :editDate
                WHERE `postId` = :postId
            "));
            if($this->S->executeQuery(array(
                ':post_content_html' => $post->post_html,
                ':post_content_text' => $post->post_text,
                ':authorId'          => $post->author->id,
                ':threadId'          => $post->threadId,
                ':editDate'          => date('Y-m-d H:i:s', time())
            ))) {
                $this->lastMessage[] = 'Successfully edited post.';

                return true;
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = $this->S->getLastError();
                } else {
                    $this->lastError[] = 'Something went wrong while updating post.';
                }

                return false;
            }
        }

        public function deletePost($id, Post $post) {
            if(is_null($id)) $id = $post->id;

            $P = new Post($this->S);
            $rpost = $P->getPost($id);

            if($rpost->originalPost) {
                $T = new Thread($this->S);
                $thread = $T->getThread($post->threadId);
                $thread->deleteThread();
            }

            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                DELETE FROM `{{DBP}}posts` WHERE `id` = :id
            "));

            if($this->S->executeQuery(array(
                ':id' => $id
            ))) {
                $this->lastMessage[] = 'Successfully deleted post.';

                return true;
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = $this->S->getLastError();
                } else {
                    $this->lastError[] = 'Something went wrong while deleting post.';
                }

                return false;
            }
        }
    }