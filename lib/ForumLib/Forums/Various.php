<?php
    namespace ForumLib\Forums;

    use ForumLib\Utilities\PSQL;

    class Various {

        private $S;

        private $lastError = array();
        private $lastMessage = array();

        public function __construct(PSQL $_SQL) {
            if($_SQL instanceof PSQL) {
                $this->S = $_SQL;
            } else {
                $this->lastError[] = 'The parameter provided wasn\'t an instance of PSQL.';
                $this->__destruct();
            }
        }

        public function __destruct() {
            $this->S = null;
        }

        public function getLatestPosts() {
            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                SELECT 
                    *
                FROM (
                    SELECT
                         `P`.`id` `postId`
                        ,`P`.`postDate` `postDate`
                        ,`T`.`id` `threadId`
                    FROM `{{DBP}}posts` `P`
                        INNER JOIN `{{DBP}}threads` `T` ON `T`.`id` = `P`.`threadId`
                    ORDER BY `P`.`postDate` DESC
                ) `latestThreads`
                GROUP BY `threadId`
                    ORDER BY `postDate` DESC
            "));
            $this->S->executeQuery();

            $trds = $this->S->fetchAll();

            $threads = array();
            foreach($trds as $trd) {
                $T = new Thread($this->S);
                $threads[] = $T->getThread($trd['threadId']);
            }

            return $threads;
        }
    }