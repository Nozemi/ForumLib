<?php

    namespace ForumLib\Forums;

    use ForumLib\Database\PSQL;

    use ForumLib\Integration\Nozum\NozumPost;
    use ForumLib\Integration\vB3\vB3Post;

    use ForumLib\Users\User;
    use ForumLib\Utilities\Config;

    class Post extends Base {
        public $threadId;
        public $author;
        public $post_html;
        public $post_text;
        public $post_date;
        public $post_last_edit;
        public $originalPost;

        private $integration;

        public function __construct(PSQL $SQL) {
            if(!is_null($SQL)) {
                $this->S = $SQL;

                $C = new Config;
                $this->config = $C->config;
                switch(array_column($this->config, 'integration')[0]) {
                    case 'vB3':
                        $this->integration = new vB3Post($this->S);
                        break;
                    case 'Nozum':
                    default:
                        $this->integration = new NozumPost($this->S);
                        break;
                }
            } else {
                $this->lastError[] = 'Something went wrong while creating the post object.';
                return false;
            }
        }

        public function createPost() {
            return $this->integration->createPost($this);
        }

        // Takes one parameter, which would be the thread ID.
        public function getPosts($threadId = null) {
            return $this->integration->getPosts($threadId, $this);
        }

        public function getPost($id = null) {
            return $this->integration->getPost($id, $this);
        }

        public function updatePost() {
            return $this->integration->updatePost($this);
        }

        public function deletePost($id = null) {
            return $this->integration->deletePost($id, $this);
        }

        public function setThreadId($_tid) {
            $this->threadId = $_tid;

            return $this;
        }

        public function setOriginalPost($_originalPost) {
            $this->originalPost = $_originalPost;

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
