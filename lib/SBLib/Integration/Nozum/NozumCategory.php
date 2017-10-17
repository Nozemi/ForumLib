<?php
    namespace SBLib\Integration\Nozum;

    use SBLib\Forums\Category;
    use SBLib\Integration\IntegrationBaseCategory;

    use SBLib\Database\DBUtilQuery;

    use \PDO;

    class NozumCategory extends IntegrationBaseCategory {

        public function getCategories() {
            $getCategories = new DBUtilQuery;
            $getCategories->setName('getCategories')
                ->setQuery("SELECT * FROM `{{DBP}}categories` ORDER BY `order` ASC")
                ->setDBUtil($this->S)
                ->execute();

            $qR = $this->S->getResultByName($getCategories->getName());

            $theCategories = array();

            for($i = 0; $i < count($qR); $i++) {
                $theCategories[$i] = new Category($this->S);
                $theCategories[$i]->setId($qR[$i]['id'])
                    ->setTitle($qR[$i]['title'])
                    ->setDescription($qR[$i]['description'])
                    ->setOrder($qR[$i]['order'])
                    ->setEnabled($qR[$i]['enabled'])
                    ->setPermissions($qR[$i]['id'])
                    ->setTopics($qR[$i]['id']);
            }
            return $theCategories;
        }

        public function getCategory($id, $byId, Category $cat) {
            if(is_null($id)) $id = $cat->id;

            $getCategory = new DBUtilQuery;
            $getCategory->setName('getCategory')
                ->setMultipleRows(false);

            if($byId) {
                $getCategory->setQuery("SELECT * FROM `{{DBP}}categories` WHERE `id` = :id;");
                $getCategory->addParameter(':id', $id, PDO::PARAM_INT);
            } else {
                $id = str_replace('-', ' +', $id);

                $getCategory->setQuery("SELECT * FROM `{{DBP}}categories` WHERE MATCH(`title`) AGAINST(:id IN BOOLEAN MODE);");
                $getCategory->addParameter(':id', $id, PDO::PARAM_STR);
            }

            $getCategory->setDBUtil($this->S)
                ->execute();

            $tmpCat = $this->S->getResultByName($getCategory->getName());

            $theCategory = new Category($this->S);
            $theCategory
                ->setId($tmpCat['id'])
                ->setTitle($tmpCat['title'])
                ->setDescription($tmpCat['description'])
                ->setOrder($tmpCat['order'])
                ->setEnabled($tmpCat['enabled']);

            return $theCategory;
        }

        public function createCategory(Category $cat) {
            $createCategory = new DBUtilQuery;
            $createCategory->setName('createCategory')
                ->setQuery("
                    INSERT INTO `{{DBP}}categories` (
                       `title`
                      ,`description`
                      ,`order`
                    ) VALUES (
                       :title
                      ,:description
                      ,:order
                    );
                ")
                ->addParameter(':title', $cat->title, PDO::PARAM_STR)
                ->addParameter(':description', $cat->description, PDO::PARAM_STR)
                ->addParameter(':order', $cat->order, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();

            return $this->S->getLastInsertId();
        }

        public function updateCategory(Category $cat) {
            $updateQuery = new DBUtilQuery;
            $updateQuery->setName('updateQuery')
                ->setQuery("
                    UPDATE `{{DBP}}categories` SET
                       `title`        = :title
                      ,`description`  = :description
                      ,`order`        = :order
                    WHERE `id` = :id;
                ")
                ->addParameter(':title', $cat->title, PDO::PARAM_STR)
                ->addParameter(':description', $cat->description, PDO::PARAM_STR)
                ->addParameter(':order', $cat->order, PDO::PARAM_INT)
                ->addParameter(':id', $cat->id, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();
        }

        public function deleteCategory($id = null, Category $cat) {
            if(is_null($id)) $id = $cat->id;

            $deleteCategory = new DBUtilQuery;
            $deleteCategory->setName('deleteCategory')
                ->setQuery("
                    DELETE FROM `{{DBP}}categories` WHERE `id` = :id;
                ")
                ->addParameter(':id', $id, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();
        }
    }