<?php
    namespace ForumLib\Utilities;

    class ThemeEngine {
        public $name;
        public $directory;
        public $templates = array();
        public $site = array(
          'tabTitle'    => 'Tab Title'
        );

        private $pHolderWrapper = array('{{','}}');

        private $lastError      = array();
        private $lastMessage    = array();

        private $config = null;

        public function __construct($_name, Config $_config = null) {
            $this->name = $_name;
            $this->directory = MISC::findFile('themes/' . $this->name);

            if(!MISC::findFile($this->directory)) {
                $this->lastError[] = 'No theme found';
                return false;
            } else {
                foreach(glob($this->directory . '/*', GLOB_ONLYDIR) as $dir) {
                    $dir = end(explode('/', $dir));
                    $this->templates['page_'.$dir] = array();

                    foreach(glob($this->directory . '/' . $dir . '/*.template.html') as $file) {
                        $this->templates['page_'.$dir][basename($file,'.template.html')] = file_get_contents($file);
                    }
                }

                if($_config instanceof Config) {
                    $this->config = $_config;
                }
            }
        }

        public function getTemplate($_template, $_page = null) {
            if($_page) {
                return $this->parseTemplate(MISC::findKey($_template, $this->templates['page_'.$_page]));
            } else {
                return $this->parseTemplate(MISC::findKey($_template, $this->templates));
            }
        }

        private function parseTemplate($_template) {
            preg_match_all('/' . $this->pHolderWrapper[0] . '(.*)' . $this->pHolderWrapper[1] . '/', $_template, $matches);

            foreach($matches[1] as $match) {
                $template = explode('::', $match);

                switch($template[0]) {
                    default:
                    case 'lang':
                        $L = new Language(null, $this->config);
                        $_template = $this->replaceVariable($template[0], $template[1], $_template, MISC::findKey($template[1], $L->getLanguage()));
                        break;
                    case 'site':
                        if($template[1] == 'tabTitle') {
                            $_template = $this->replaceVariable($template[0], $template[1], $_template, $this->site[$template[1]]);
                        } else if($template[1] == 'latestNews') {
                            $html = '';
                            for($i = 0; $i < 5; $i++) {
                                $html .= $this->getTemplate('post_view');
                            }
                            $_template = $this->replaceVariable($template[0], $template[1], $_template, $html);
                        }
                        break;
                    case 'structure':
                        $_template = $this->replaceVariable($template[0], $template[1], $_template, $this->getTemplate($template[1]));
                        break;
                }
            }

            return $_template;
        }

        private function replaceVariable($_type, $_name, $_template, $_replacement) {
            return str_replace(
                $this->pHolderWrapper[0] . $_type . '::' . $_name . $this->pHolderWrapper[1],
                $_replacement,
                $_template
            );
        }


        public function setName($_name) {
            $this->name = $_name;
            return $this;
        }

        public function setDirectory($_directory) {
            $this->directory = $_directory;
            return $this;
        }


        public function getLastError() {
            return end($this->lastError);
        }

        public function getLastMessage() {
            return end($this->lastMessage);
        }

        public function getErrors() {
            return $this->lastError;
        }

        public function getMessages() {
            return $this->lastMessage;
        }
    }