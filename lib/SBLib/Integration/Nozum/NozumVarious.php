<?php
    namespace SBLib\Integration\Nozum;

    use SBLib\Database\DBUtilQuery;
    use SBLib\Forums\Thread;
    use SBLib\Forums\Various;
    use SBLib\Integration\IntegrationBaseVarious;

    class NozumVarious extends IntegrationBaseVarious {

        public function getLatestPosts(Various $various) {
            $latestPosts = new DBUtilQuery;
            $latestPosts->setName('latestPosts')
                ->setQuery("
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
                ")
                ->setDBUtil($this->S)
                ->execute();

            $trds = $this->S->getResultByName($latestPosts->getName());

            $threads = array();
            foreach($trds as $trd) {
                $T = new Thread($this->S);
                $threads[] = $T->getThread($trd['threadId']);
            }

            return $threads;
        }
    }