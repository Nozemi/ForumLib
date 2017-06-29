<?php
    namespace ForumLib\Integration\vB3;

    use ForumLib\Database\DBUtilQuery;
    use ForumLib\Forums\Post;
    use ForumLib\Integration\IntegrationBasePost;

    use \PDO;

    class vB3Post extends IntegrationBasePost {

        public function createPost(Post $post) {
            // TODO: Implement createPost() method.
        }

        public function getPosts($threadId, Post $post) {
            if(is_null($threadId)) $threadId = $post->threadId;

            $getPosts = new DBUtilQuery;
            $getPosts->setName('getPosts')
                ->setMultipleRows(true)
                ->setDBUtil($this->S)
                ->setQuery("SELECT * FROM `{{DBP}}post` WHERE `threadid` = :threadId ORDER BY `dateline` ASC")
                ->addParameter(':threadId', $threadId, PDO::PARAM_INT)
                ->execute();

            $posts = $getPosts->result();
            $thePosts = array();

            for($i = 0; $i < count($posts); $i++) {
                $thePost = new Post($this->S);
                $thePost->setId($posts[$i]['postid'])
                    ->setThreadId($posts[$i]['threadid'])
                    ->setAuthor($posts[$i]['userid'])
                    ->setPostDate($posts[$i]['dateline'])
                    ->setHTML(nl2br($posts[$i]['pagetext']))
                    ->setText(nl2br($posts[$i]['pagetext']));

                $thePosts[] = $thePost;
            }

            return $thePosts;
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