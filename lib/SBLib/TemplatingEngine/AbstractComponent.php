<?php
    namespace SBLib\TemplatingEngine;

    abstract class AbstractComponent {
        protected $_initializer;

        public function construct() {
            $this->_initializer = $this->getInitializer();
        }

        abstract protected function getInitializer();
    }