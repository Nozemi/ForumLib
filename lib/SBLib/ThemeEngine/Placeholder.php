<?php
    namespace SBLib\ThemeEngine;

    class Placeholder {
        protected $category;
        protected $option;
        protected $arguments;
        protected $placeholder;

        public function __construct($placeholder, MainEngine $engine) {
            $options = explode('::', trim(str_replace($engine->getWrapper(MainEngine::END), '', str_replace($engine->getWrapper(MainEngine::START), '', $placeholder))));

            $this->category  = $options[0];
            $this->option    = explode('(', $options[1])[0];
            $this->arguments = explode(',', str_replace(')', '', explode('(', $options[1])[1]));
        }

        public function getObject() {
            return $this;
        }

        public function getCategory() {
            return $this->category;
        }

        public function getOption() {
            return $this->option;
        }

        public function getPlaceholder() {
            return $this->placeholder;
        }

        /**
         * @return array
         */
        public function getArguments() {
            return $this->arguments;
        }
    }