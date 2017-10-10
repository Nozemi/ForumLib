<?php
    namespace SBLib\Plugin;

    use SBLib\Utilities\Logger;

    class Plugin {
        protected $name;
        protected $directory;
        protected $classes;
        protected $mainClass;
        protected $priority;

        public function __construct($name = '') {
            $this->name = $name;
        }

        public function setName($name) {
            $this->name = $name;
            return $this;
        }

        public function getName() {
            return $this->name;
        }

        public function setDirectory($directory) {
            $this->directory = $directory;
            return $this;
        }

        public function getDirectory() {
            return $this->directory;
        }

        public function setClasses($classes) {
            if(!is_array($classes)) {
                new Logger('$classes needs to be an array.', Logger::ERROR, __CLASS__, __LINE__);
                return $this;
            }

            foreach($classes as $class) {
                $this->classes[] = $class;
            }

            return $this;
        }

        public function getClasses() {
            return (is_array($this->classes) ? $this->classes : array());
        }

        public function setMainClass($mainClass) {
            $this->mainClass = $mainClass;
            return $this;
        }

        public function getMainClass() {
            return $this->mainClass;
        }

        public function setPriority($priority) {
            $this->priority = $priority;
            return $this;
        }

        public function getPriority() {
            return $this->priority;
        }
    }