<?php
    namespace ForumLib\Integration;

    abstract class IntegrationBaseCategory extends IntegrationBase {
        abstract public function getCategories();
        abstract public function getCategory();
        abstract public function createCategory();
        abstract public function updateCategory();
        abstract public function deleteCategory();
    }