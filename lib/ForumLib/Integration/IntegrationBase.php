<?php
    namespace ForumLib\Integration;

    use ForumLib\Database\DBUtil;

    abstract class IntegrationBase {
        protected $lastMessage;
        protected $lastError;

        protected $S;

        public function __construct(DBUtil $sql) {
            $this->S = $sql;
        }

        public function getLastError() {
            return end($this->lastError);
        }

        public function getLastMessage() {
            return end($this->lastMessage);
        }

        public function getErrors() {
            return $this->lastError;
        }

        public function getMessages() {
            return $this->lastMessage;
        }
    }