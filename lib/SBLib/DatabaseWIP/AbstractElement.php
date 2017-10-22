<?php
    namespace SBLib\Database;

    abstract class AbstractElement {
        protected $_name;
        protected $_type;
        protected $_size;
        protected $_default;
        protected $_primary;
        protected $_unique;
        protected $_notNull;
        protected $_autoIncrement;

        public function __construct($name, $size, $default = false, $autoIncrement = false, $primary = false, $unique = false, $notNull = false) {
            $this->_setName($name)
                ->_setSize($size)
                ->_setDefault($default)
                ->_setAutoIncrement($autoIncrement)
                ->_setPrimary($primary)
                ->_setUnique($unique)
                ->_setNotNull($notNull);
        }

        protected function _setName($name) {
            $this->_name = $name;
            return $this;
        }

        protected function _setType($type) {
            $this->_type = $type;
            return $this;
        }

        /**
         * @param int $size
         * @return $this
         */
        protected function _setSize(int $size) {
            $this->_size = $size;
            return $this;
        }

        /**
         * @param mixed $default
         * @return $this
         */
        protected function _setDefault($default) {
            $this->_default = $default;
            return $this;
        }

        /**
         * @param bool $primary
         * @return $this
         */
        protected function _setPrimary(bool $primary) {
            $this->_primary = $primary;
            return $this;
        }

        /**
         * @param bool $unique
         * @return $this
         */
        protected function _setUnique(bool $unique) {
            $this->_unique = $unique;
            return $this;
        }

        /**
         * @param bool $notNull
         * @return $this
         */
        protected function _setNotNull(bool $notNull) {
            $this->_notNull = $notNull;
            return $this;
        }

        /**
         * @param bool $autoIncrement
         * @return $this
         */
        protected function _setAutoIncrement(bool $autoIncrement) {
            $this->_autoIncrement = $autoIncrement;
            return $this;
        }
    }