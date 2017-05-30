<?php
    namespace ForumLib\Integration\vB3;

    use ForumLib\Database\PSQL;
    use ForumLib\Forums\Category;
    use ForumLib\Integration\IntegrationBaseCategory;

    class vB3Category extends IntegrationBaseCategory {
        protected $lastMessage;
        protected $lastError;

        protected $S;

        public function __construct(PSQL $sql) {
            $this->S = $sql;
        }

        public function getCategories() {
            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "SELECT * FROM `forum` WHERE `parentid` = -1"));

            if($this->S->executeQuery()) {
                $this->lastMessage[] = 'Successfully fetched categories.';

                $theCategories = array();
                $qR = $this->S->fetchAll();

                for($i = 0; $i < count($qR); $i++) {
                    $theCategories[$i] = new Category($this->S);
                    $theCategories[$i]
                        ->setId($qR[$i]['forumid'])
                        ->setTitle($qR[$i]['title'])
                        ->setDescription($qR[$i]['description_clean'])
                        ->setOrder($qR[$i]['displayorder'])
                        ->setTopics($qR[$i]['id']);
                }

                return $theCategories;
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = $this->S->getLastError();
                } else {
                    $this->lastError[] = 'Something went wrong while fetching the categories.';
                }
                return false;
            }
        }

        public function getCategory() {
            // TODO: Implement getCategory() method.
        }

        public function createCategory() {
            // TODO: Implement createCategory() method.
        }

        public function updateCategory() {
            // TODO: Implement updateCategory() method.
        }

        public function deleteCategory() {
            // TODO: Implement deleteCategory() method.
        }
    }