<?php

    namespace ForumLib\Forums;

    use ForumLib\Utilities\PSQL;
    use ForumLib\Users\User;

    class Post extends Base {
        public $threadId;
        public $author;
        public $post_html;
        public $post_text;
        public $post_date;
        public $post_last_edit;

        public function __construct(PSQL $SQL) {
            if(!is_null($SQL)) {
                $this->S = $SQL;
            } else {
                $this->lastError[] = 'Something went wrong while creating the post object.';

                return false;
            }
        }

        public function createPost() {
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
                  ':post_content_html' => $this->post_html,
                  ':post_content_text' => $this->post_text,
                  ':authorId'          => $this->author->id,
                  ':threadId'          => $this->thread->id,
                  ':postDate'          => date('Y-m-d H:i:s', time()),
                  ':editDate'          => date('Y-m-d H:i:s', time())
              ))
            ) {
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

        // Takes one parameter, which would be the thread ID.
        public function getPosts($threadId = null) {
            if(is_null($threadId)) $threadId = $this->threadId;

            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                SELECT * FROM `{{DBP}}posts` WHERE `threadId` = :threadId ORDER BY `postDate` DESC"
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
                        ->setText($posts[$i]['post_content_text']);

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

        public function getPost($id = null) {
            if(is_null($id))
                $id = $this->id;

            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "SELECT * FROM `{{DBP}}posts` WHERE `id` = :id ORDER BY `postDate` DESC"));
            if($this->S->executeQuery(array(
                                          ':id' => $id
                                      ))
            ) {
                $post = $this->S->fetch();

                $thePost = new Post($S);
                $thePost->setId($post['id'])
                    ->setThreadId($post['threadId'])
                    ->setAuthor($post['authorId'])
                    ->setPostDate($post['postDate'])
                    ->setEditDate($post['editDate'])
                    ->setHTML($post['post_content_html'])
                    ->setText($post['post_content_text']);

                $this->lastMessage = 'Successfully fetched posts.';

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

        public function updatePost() {
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
                                          ':post_content_html' => $this->post_html,
                                          ':post_content_text' => $this->post_text,
                                          ':authorId'          => $this->author->id,
                                          ':threadId'          => $this->threadId,
                                          ':editDate'          => date('Y-m-d H:i:s', time())
                                      ))
            ) {
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

        public function deletePost($id = null) {
            if(is_null($id))
                $id = $this->id;

            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        DELETE FROM `{{DBP}}posts` WHERE `id` = :id
      "));

            if($this->S->executeQuery(array(
                                          ':id' => $id
                                      ))
            ) {
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

        public function setThreadId($_tid) {
            $this->threadId = $_tid;

            return $this;
        }

        public function setAuthor($_uid) {
            $U = new User($this->S);
            $this->author = $U->getUser($_uid);

            return $this;
        }

        public function setPostDate($_date) {
            $this->post_date = $_date;

            return $this;
        }

        public function setEditDate($_date) {
            $this->post_last_edit = $_date;

            return $this;
        }

        public function setHTML($_html) {
            $this->post_html = $_html;

            return $this;
        }

        public function setText($_text) {
            $this->post_text = $_text;

            return $this;
        }

        public function getType() {
            return __CLASS__;
        }
    }
