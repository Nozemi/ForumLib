<?php
    namespace ForumLib\Integration\vB3;

    use ForumLib\Forums\Post;
    use ForumLib\Integration\IntegrationBasePost;

    class vB3Post extends IntegrationBasePost {

        public function createPost(Post $post) {
            // TODO: Implement createPost() method.
        }

        public function getPosts($threadId, Post $post) {
            if(is_null($threadId)) $threadId = $post->threadId;

            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                SELECT * FROM `{{DBP}}post` WHERE `threadid` = :threadId ORDER BY `dateline` ASC"
            ));

            if($this->S->executeQuery(array(
                ':threadId' => $threadId
            ))) {
                $this->lastMessage[] = 'Successfully fetched posts.';

                $posts = $this->S->fetchAll();
                $thePosts = array();

                for($i = 0; $i < count($posts); $i++) {
                    $thePost = new Post($this->S);
                    $thePost->setId($posts[$i]['postid'])
                        ->setThreadId($posts[$i]['threadid'])
                        ->setAuthor($posts[$i]['userid'])
                        ->setPostDate($posts[$i]['dateline'])
                        ->setHTML(str_replace("\n", "<br />", $posts[$i]['pagetext']))
                        ->setText(str_replace("\n", "<br />", $posts[$i]['pagetext']));

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
            // TODO: Implement getPost() method.
        }

        public function updatePost(Post $post) {
            // TODO: Implement updatePost() method.
        }

        public function deletePost($id, Post $post) {
            // TODO: Implement deletePost() method.
        }
    }