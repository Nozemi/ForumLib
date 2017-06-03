<?php
    namespace ForumLib\Integration\vB3;

<<<<<<< HEAD
    use ForumLib\Database\PSQL;
=======
>>>>>>> 615a34eea3757a7329b41b8f2d8bd5f54f42e90f
    use ForumLib\Forums\Category;
    use ForumLib\Integration\IntegrationBaseCategory;

    class vB3Category extends IntegrationBaseCategory {
<<<<<<< HEAD
        protected $lastMessage;
        protected $lastError;

        protected $S;

        public function __construct(PSQL $sql) {
            $this->S = $sql;
        }

        public function getCategories() {
            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "SELECT * FROM `forum` WHERE `parentid` = -1"));
=======

        public function getCategories() {
            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "SELECT * FROM `{{DBP}}forum` WHERE `parentid` = -1"));
>>>>>>> 615a34eea3757a7329b41b8f2d8bd5f54f42e90f

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
<<<<<<< HEAD
                        ->setTopics($qR[$i]['id']);
=======
                        ->setTopics($qR[$i]['forumid']);
>>>>>>> 615a34eea3757a7329b41b8f2d8bd5f54f42e90f
                }

                return $theCategories;
            } else {
                if(defined('DEBUG')) {
<<<<<<< HEAD
                    $this->lastError[] = $this->S->getLastError();
=======
                    $this->lastError[] = 'Err:' . $this->S->getLastError();
>>>>>>> 615a34eea3757a7329b41b8f2d8bd5f54f42e90f
                } else {
                    $this->lastError[] = 'Something went wrong while fetching the categories.';
                }
                return false;
            }
        }

<<<<<<< HEAD
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
=======
        public function getCategory($id, $byId, Category $cat) {
            if(is_null($id)) $id = $cat->id;

            if($byId) {
                $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                  SELECT * FROM `{{DBP}}forum` WHERE `forumid` = :id AND `parentid` = -1;
                "));
            } else {
                $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                  SELECT * FROM `{{DBP}}forum` WHERE MATCH(`title`) AGAINST(:id IN BOOLEAN MODE) AND `parentid` = -1;
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
>>>>>>> 615a34eea3757a7329b41b8f2d8bd5f54f42e90f
            // TODO: Implement deleteCategory() method.
        }
    }