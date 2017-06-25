<?php
    namespace ForumLib\Forums;

    use ForumLib\Database\DBUtil;
    use ForumLib\Users\Permissions;
    use ForumLib\Utilities\Config;

    use ForumLib\Integration\vB3\vB3Topic;
    use ForumLib\Integration\Nozum\NozumTopic;

    class Topic extends Base {
        public $enabled;
        public $categoryId;
        public $permissions;
        public $threads;
        public $threadCount;
        public $postCount;

        public function __construct(DBUtil $SQL) {
            if(!is_null($SQL)) {
                $this->S = $SQL;

                $C = new Config;
                $this->config = $C->config;

                switch(array_column($this->config, 'integration')) {
                    case 'vB3':
                        $this->integration = new vB3Topic($this->S);
                        break;
                    case 'Nozum':
                    default:
                        $this->integration = new NozumTopic($this->S);
                        break;
                }
            } else {
                $this->lastError[] = 'Something went wrong while creating the topic object.';
                return false;
            }
        }

        public function createTopic($categoryId = null) {
            return $this->integration->createTopic($categoryId, $this);
        }

        public function getTopics($categoryId = null) {
            return $this->integration->getTopics($categoryId, $this);
        }

        public function getTopic($id = null, $byId = true, $categoryId = null) {
            return $this->integration->getTopic($id, $byId, $categoryId, $this);
        }

        public function updateTopic($id = null) {
            return $this->integration->updateTopic($id, $this);
        }

        public function deleteTopic($id = null) {
            return $this->integration->deleteTopic($id, $this);
        }

        public function getLatestPost($_topicId = null) {
            return $this->integration->getLatestPost($_topicId, $this);
        }

        public function setThreadCount() {
            return $this->integration->setThreadCount($this);
        }

        public function setPostCount() {
            return $this->integration->setPostCount($this);
        }

        public function checkThreadName($_title, Topic $_topic) {
            return $this->integration->checkThreadName($_title, $_topic);
        }

        public function setCategoryId($_cid) {
            $this->categoryId = $_cid;
            return $this;
        }

        public function setEnabled($_enabled) {
            $this->enabled = $_enabled;
            return $this;
        }

        public function setPermissions($_id = null) {
            if(is_null($_id)) $_id = $this->id;

            $P = new Permissions($this->S, $_id, $this);
            $this->permissions = $P->getPermissions();
            return $this;
        }

        public function setThreads($_threadId = null) {
            if(is_null($_threadId)) $_threadId = $this->id;

            $T = new Thread($this->S);
            $this->threads = $T->getThreads($_threadId);
            return $this;
        }

        public function getType() {
            return __CLASS__;
        }
    }
