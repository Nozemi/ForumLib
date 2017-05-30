<?php
    namespace ForumLib\Integration;

    use ForumLib\Forums\Category;

    abstract class IntegrationBaseCategory extends IntegrationBase {
        abstract public function getCategories();
        abstract public function getCategory($id, $byId, Category $cat);
        abstract public function createCategory(Category $cat);
        abstract public function updateCategory(Category $cat);
        abstract public function deleteCategory($id, Category $cat);
    }