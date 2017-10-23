<?php
    namespace SBLib\ThemeEngine;

    class Placeholder {
        protected $_category;
        protected $_option;
        protected $_arguments;
        protected $_placeholder;

        public function __construct($placeholder) {
            if(is_array($placeholder)) { return false; }

            $options = explode('::', trim(str_replace('{{', '', str_replace('}}', '', $placeholder))));

            $this->_category  = $options[0];
            $this->_option    = explode('(', $options[1])[0];

            $arguments = explode('(', $options[1]);
            $arguments = end($arguments);
            $arguments = str_replace(')','', $arguments);
            $arguments = explode(',', $arguments);
            foreach($arguments as $argument) {
                $this->_arguments[] = $argument;
            }

            $this->_placeholder = $placeholder;
            return true;
        }

        public function getObject() {
            return $this;
        }

        public function getCategory() {
            return $this->_category;
        }

        public function getOption() {
            return $this->_option;
        }

        /**
         * Returns the placeholder that should be replaced within the template file.
         *
         * @return mixed
         */
        public function get() {
            return $this->_placeholder;
        }
        /**
         * @deprecated - Use Placeholder::get instead
         * @return mixed
         */
        public function getPlaceholder() {
            return $this->_placeholder;
        }

        /**
         * @return array
         */
        public function getArguments() {
            return $this->_arguments;
        }
    }