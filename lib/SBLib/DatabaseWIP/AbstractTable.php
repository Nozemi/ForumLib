<?php
    namespace SBLib\Database;

    abstract class AbstractTable {
        protected $_columns;

        abstract public function __construct();

        protected function create() {
            // TODO: Create the table if it doesn't exist.
            if(empty($this->_columns)) {
                return false;
            }

            return true;
        }

        protected function delete() {

        }

        protected function truncate() {

        }

        protected function addColumn() {

        }

        protected function removeColumn() {

        }

        protected function updateColumn() {

        }
    }