<?php
    namespace SBLib\ThemeEngine;

    class Placeholder {
        protected $_category;
        protected $_option;
        protected $_arguments;
        protected $_placeholder;

        public function __construct($placeholder, MainEngine $engine) {
            if(is_array($placeholder)) {
                print_r($placeholder);
                return false;
            }

            $options = explode('::', trim(str_replace($engine->getWrapper(MainEngine::END), '', str_replace($engine->getWrapper(MainEngine::START), '', $placeholder))));

            $this->_category  = $options[0];
            $this->_option    = explode('(', $options[1])[0];

            //$args = explode(',', str_replace(')', '', explode('(', $options[1])));
            //if(count($args) > 1) {
            //    $this->_arguments = $args[1];
            //}

            $this->_placeholder = $placeholder;
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