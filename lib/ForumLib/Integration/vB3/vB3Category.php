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

        public function getCategory($id, $byId, Category $cat) {
            if(is_null($id)) $id = $cat->id;

            if($byId) {
                $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                  SELECT * FROM `{{DBP}}categories` WHERE `id` = :id;
                "));
            } else {
                $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                  SELECT * FROM `{{DBP}}categories` WHERE MATCH(`title`) AGAINST(:id IN BOOLEAN MODE);
                "));

                $id = str_replace('-', ' +', $id);
            }

            if($this->S->executeQuery(array(
                ':id' => $id
            ))) {
                $this->lastMessage[] = 'The category was successfully loaded.';

                $rcat = $this->S->fetch(); // Let's get the query result.

                $theCategory = new Category($this->S);
                $theCategory
                    ->setId($rcat['id'])
                    ->setTitle($rcat['title'])
                    ->setDescription($rcat['description'])
                    ->setOrder($rcat['order'])
                    ->setEnabled($rcat['enabled']);

                return $theCategory;
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = $this->S->getLastError();
                } else {
                    $this->lastError[] = 'Failed to get category.';
                }
                return false;
            }
        }

        public function createCategory(Category $cat) {
            // TODO: Implement createCategory() method.
        }

        public function updateCategory(Category $cat) {
            // TODO: Implement updateCategory() method.
        }

        public function deleteCategory($id, Category $cat) {
            // TODO: Implement deleteCategory() method.
        }
    }