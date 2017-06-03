<?php
    namespace ForumLib\Integration;

<<<<<<< HEAD
=======
    use ForumLib\Database\PSQL;

>>>>>>> 615a34eea3757a7329b41b8f2d8bd5f54f42e90f
    abstract class IntegrationBase {
        protected $lastMessage;
        protected $lastError;

        protected $S;

        public function __construct(PSQL $sql) {
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