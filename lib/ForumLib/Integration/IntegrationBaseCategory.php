<?php
    namespace ForumLib\Integration;

<<<<<<< HEAD
    abstract class IntegrationBaseCategory extends IntegrationBase {
        abstract public function getCategories();
        abstract public function getCategory();
        abstract public function createCategory();
        abstract public function updateCategory();
        abstract public function deleteCategory();
=======
    use ForumLib\Forums\Category;

    abstract class IntegrationBaseCategory extends IntegrationBase {
        abstract public function getCategories();
        abstract public function getCategory($id, $byId, Category $cat);
        abstract public function createCategory(Category $cat);
        abstract public function updateCategory(Category $cat);
        abstract public function deleteCategory($id, Category $cat);
>>>>>>> 615a34eea3757a7329b41b8f2d8bd5f54f42e90f
    }