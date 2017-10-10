<?php
    namespace SBLib\Integration\vB3;

    use SBLib\Database\DBUtilQuery;
    use SBLib\Forums\Category;
    use SBLib\Integration\IntegrationBaseCategory;

    use \PDO;

    class vB3Category extends IntegrationBaseCategory {

        public function getCategories() {
            $getCategories = new DBUtilQuery;
            $getCategories->setName('getCategories')
                ->setMultipleRows(true)
                ->setQuery("SELECT * FROM `{{DBP}}forum` WHERE `parentid` = -1")
                ->setDBUtil($this->S)
                ->execute();

            $theCategories = array();
            $qR = $getCategories->result();

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
        }
		
        public function getCategory($id, $byId, Category $cat) {
            if(is_null($id)) $id = $cat->id;

            $getCategory = new DBUtilQuery;
            $getCategory->setName('getCategory')
                ->setMultipleRows(false)
                ->setDBUtil($this->S);

            if($byId) {
                $getCategory->setQuery("SELECT * FROM `{{DBP}}forum` WHERE `forumid` = :id AND `parentid` = -1;");
                $getCategory->addParameter(':id', $id, PDO::PARAM_INT);
            } else {
                $id = str_replace('-', ' +', $id);

                $getCategory->setQuery("SELECT * FROM `{{DBP}}forum` WHERE MATCH(`title`) AGAINST(:id IN BOOLEAN MODE) AND `parentid` = -1;");
                $getCategory->addParameter(':id', $id, PDO::PARAM_STR);
            }

            $tmpCat = $getCategory->result(); // Let's get the query result.

            $theCategory = new Category($this->S);
            $theCategory->setId($tmpCat['id'])
                ->setTitle($tmpCat['title'])
                ->setDescription($tmpCat['description'])
                ->setOrder($tmpCat['order'])
                ->setEnabled($tmpCat['enabled']);

            return $theCategory;
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