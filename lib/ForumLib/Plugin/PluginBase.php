<?php
    namespace ForumLib\Plugin;

    use ForumLib\ThemeEngine\MainEngine;

    abstract class PluginBase extends MainEngine {
        protected $engine;

        public function __construct(MainEngine $themeEngine) {
            $this->engine = parent::__construct($themeEngine->getName(), $themeEngine->getDBUtil(), $themeEngine->_Config);
        }

        /**
         * This method is meant to be overridden.
         *
         * @param $_template
         * @return mixed
         */
        public function customParse($_template) {
            return $_template;
        }

        public function pageTop() {}
        public function pageBottom() {}
        public function pageMiddle() {}
    }
