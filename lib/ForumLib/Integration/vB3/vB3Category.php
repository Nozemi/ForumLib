<?php
    namespace ForumLib\Integration\vB3;

    use ForumLib\Forums\Category;
    use ForumLib\Integration\IntegrationBaseCategory;

    class vB3Category extends IntegrationBaseCategory {

        public function getCategories() {
            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "SELECT * FROM `{{DBP}}forum` WHERE `parentid` = -1"));

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
                        ->setTopics($qR[$i]['forumid']);
                }

                return $theCategories;
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = 'Err:' . $this->S->getLastError();
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
            // TODO: Implement deleteCategory() method.
        }
    }