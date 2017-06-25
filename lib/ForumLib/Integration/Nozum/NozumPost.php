<?php
    namespace ForumLib\Integration\Nozum;

    use ForumLib\Database\DBUtil;
    use ForumLib\Database\DBUtilQuery;
    use ForumLib\Forums\Post;
    use ForumLib\Forums\Thread;
    use ForumLib\Integration\IntegrationBasePost;

    use \PDO;

    class NozumPost extends IntegrationBasePost {

        public function createPost(Post $post) {
            if(empty($post->post_html) && empty($post->post_text)) {
                $this->lastError[] = 'Post content can\'t be empty.';
                return false;
            }

            $createPost = new DBUtilQuery;
            $createPost->setName('createPost')
                ->setQuery("
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
                ")
                ->addParameter(':post_content_html', $post->post_html, PDO::PARAM_STR)
                ->addParameter(':post_content_text', $post->post_text, PDO::PARAM_STR)
                ->addParameter(':authorId', $post->author->id, PDO::PARAM_INT)
                ->addParameter(':threadId', $post->threadId, PDO::PARAM_INT)
                ->addParameter(':postDate', date('Y-m-d H:i:s'), PDO::PARAM_STR)
                ->addParameter(':editDate', date('Y-m-d H:i:s'), PDO::PARAM_STR)
                ->setDBUtil($this->S)
                ->execute();

            return true;
        }

        public function getPosts($threadId, Post $post) {
            if(is_null($threadId)) $threadId = $post->threadId;

            $getPosts = new DBUtilQuery;
            $getPosts->setName('getPosts')
                ->setQuery("SELECT * FROM `{{DBP}}posts` WHERE `threadId` = :threadId ORDER BY `postDate` ASC")
                ->addParameter(':threadId', $threadId, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();

            $posts = $this->S->getResultByName($getPosts->getName());

            $thePosts = array();

            for($i = 0; $i < count($posts); $i++) {
                $thePost = new Post($this->S);
                $thePost->setId($posts[$i]['id'])
                    ->setThreadId($posts[$i]['threadId'])
                    ->setAuthor($posts[$i]['authorId'])
                    ->setPostDate($posts[$i]['postDate'])
                    ->setLastEdited($posts[$i]['editDate'])
                    ->setHTML($posts[$i]['post_content_html'])
                    ->setText($posts[$i]['post_content_text'])
                    ->setOriginalPost($posts[$i]['originalPost']);

                $thePosts[] = $thePost;
            }

            return $thePosts;
        }

        public function getPost($id, Post $post) {
            if(is_null($id)) $id = $post->id;

            $getPost = new DBUtilQuery;
            $getPost->setName('getPost')
                ->setQuery("SELECT * FROM `{{DBP}}posts` WHERE `id` = :id")
                ->addParameter(':id', $id, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();

            $tmpPost = $this->S->getResultByName($getPost->getName());

            $thePost = new Post($this->S);
            $thePost->setId($tmpPost['id'])
                ->setThreadId($tmpPost['threadId'])
                ->setAuthor($tmpPost['authorId'])
                ->setPostDate($tmpPost['postDate'])
                ->setLastEdited($tmpPost['editDate'])
                ->setHTML($tmpPost['post_content_html'])
                ->setText($tmpPost['post_content_text'])
                ->setOriginalPost($tmpPost['originalPost']);

            $this->lastMessage[] = 'Successfully fetched posts.';

            return $thePost;
        }

        public function updatePost(Post $post) {
            $updatePost = new DBUtilQuery;
            $updatePost->setName('updatePost')
                ->setQuery("
                    UPDATE `{{DBP}}posts` SET
                       `post_content_html`  = :post_content_html
                      ,`post_content_text`  = :post_content_text
                      ,`authorId`           = :authorId
                      ,`threadId`           = :threadId
                      ,`editDate`           = :editDate
                    WHERE `postId` = :postId
                ")
                ->addParameter(':post_content_html', $post->post_html, PDO::PARAM_STR)
                ->addParameter(':post_content_text', $post->post_text, PDO::PARAM_STR)
                ->addParameter(':authorId', $post->author->id, PDO::PARAM_INT)
                ->addParameter(':threadId', $post->threadId, PDO::PARAM_INT)
                ->addParameter(':editDate', date('Y-m-d H:i:s'), PDO::PARAM_STR)
                ->setDBUtil($this->S)
                ->execute();
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

            $deletePost = new DBUtilQuery;
            $deletePost->setName('deletePost')
                ->setQuery("DELETE FROM `{{DBP}}posts` WHERE `id` = :id")
                ->addParameter(':id', $id, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();
        }
    }