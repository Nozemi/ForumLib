<?php
    namespace ForumLib\Integration\Nozum;

    use ForumLib\Forums\Category;
    use ForumLib\Integration\IntegrationBaseCategory;

    class NozumCategory extends IntegrationBaseCategory {

        public function getCategories() {
            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "SELECT * FROM `{{DBP}}categories` ORDER BY `order` ASC"));

            if($this->S->executeQuery()) {
                $this->lastMessage[] = 'Successfully fetched categories.';

                $theCategories = array();
                $qR = $this->S->fetchAll();

                for($i = 0; $i < count($qR); $i++) {
                    $theCategories[$i] = new Category($this->S);
                    $theCategories[$i]
                        ->setId($qR[$i]['id'])
                        ->setTitle($qR[$i]['title'])
                        ->setDescription($qR[$i]['description'])
                        ->setOrder($qR[$i]['order'])
                        ->setEnabled($qR[$i]['enabled'])
                        ->setPermissions($qR[$i]['id'])
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
            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                INSERT INTO `{{DBP}}categories` (
                   `title`
                  ,`description`
                  ,`order`
                ) VALUES (
                   :title
                  ,:description
                  ,:order
                );
              "));
            if($this->S->executeQuery(array(
                ':title'        => $cat->title,
                ':description'  => $cat->description,
                ':order'        => $cat->order
            ))) {
                $this->lastMessage[] = 'Succefully created category.';
                return true;
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = $this->S->getLastError();
                } else {
                    $this->lastError[] = 'Something went wrong while creating the new category.';
                }
                return false;
            }
        }

        public function updateCategory(Category $cat) {
            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                UPDATE `{{DBP}}categories` SET
                   `title`        = :title
                  ,`description`  = :description
                  ,`order`        = :order
                WHERE `id` = :id;
              "));
            if($this->S->executeQuery(array(
                ':title'        => $cat->title,
                ':description'  => $cat->description,
                ':order'        => $cat->order,
                ':id'           => $cat->id
            ))) {
                $this->lastMessage[] = 'Successfully updated the category.';
                return true;
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = $this->S->getLastError();
                } else {
                    $this->lastError[] = 'Something went wrong while updating category.';
                }
                return false;
            }
        }

        public function deleteCategory($id = null, Category $cat) {
            if(is_null($id)) $id = $cat->id;

            // We'll have to fill in a few more delete queries. So that sub topics, threads and post are deleted as well.
            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                DELETE FROM `{{DBP}}categories` WHERE `id` = :id;
              "));
            if($this->S->executeQuery(array(
                ':id' => $id
            ))) {
                $this->lastMessage[] = 'Successfully deleted category.';
                return true;
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = $this->S->getLastError();
                } else {
                    $this->lastError[] = 'Something went wrong while deleting category.';
                }
                return false;
            }
        }
    }