<?php
    namespace SBLib\ThemeEngine;

    use SBLib\Database\DBUtil;
    use SBLib\Forums\Thread;
    use SBLib\Forums\Topic;
    use SBLib\Plugin\PluginBase;
    use SBLib\Utilities\Config;
    use SBLib\Utilities\Logger;
    use SBLib\Utilities\MISC;

    class MainEngine {
        protected $_name;
        protected $_directory;
        protected $_config;

        protected $_templates;

        protected $_DBUtil;
        protected $_Config;

        protected $_rootDirectory;

        /**
         * Method is supposed to be overridden in a plugin, or another class that extends MainEngine (this class).
         *
         * @param string $template
         * @return string $template
         */
        protected function customParse($template) {
            return $template;
        }

        public function __construct($theme = 'slickboard', $directory = '/themes/') {
            if(!is_string($directory) || !is_string($theme)) {
                print_r(debug_backtrace());
                new Logger("\$theme or \$directory is not a string.", Logger::INFO, __CLASS__, __LINE__);
                $this->__destruct();
                return false;
            }

            $this->_name = $theme;

            if($GLOBALS['Config'] instanceof Config) {
                $this->_Config = $GLOBALS['Config'];
            } else {
                $this->_Config = new Config;
            }

            if($GLOBALS['DBUtil'] instanceof DBUtil) {
                $this->_DBUtil = $GLOBALS['DBUtil'];
            } else {
                if(!empty($this->_Config->get('dbName'))) {
                    $dbDetails = [];
                    foreach($this->_Config->getAll('database') as $key => $value) {
                        $dbDetails[strtolower(str_replace('db', '', $key))] = $value;
                    }
                    $this->_DBUtil = new DBUtil((object) $dbDetails);
                }
            }

            $this->_rootDirectory = MISC::getRootDirectory();
            $this->_directory = $this->_rootDirectory->server . $directory . $this->_name;

            foreach(glob($this->_directory . '/*/*.template.html') as $file) {
                $this->_templates['page_' . basename(dirname($file))]['template_' . basename($file, '.template.html')] = file_get_contents($file);
            }

            foreach(glob($this->_directory . '/*.template.html') as $file) {
                $this->_templates['template_' . basename($file, '.template.html')] = file_get_contents($file);
            }

            if($this->validateTheme()) {
                new Logger("TemplateEngine was successfully initialized.", Logger::INFO, __CLASS__, __LINE__);
                return true;
            } else {
                $this->__destruct();
                return false;
            }
        }

        public function __destruct() {
            $this->_DBUtil = null;
            $this->_Config = null;
            $this->_templates = null;
            $this->_rootDirectory = null;
        }

        private function validateTheme() {
            if(!$this->_Config instanceof Config) {
                new Logger("TemplateEngine didn't have a valid Config object instance.", Logger::ERROR, __CLASS__, __LINE__);
                return false;
            }

            if(!$this->_DBUtil instanceof DBUtil) {
                new Logger("TemplateEngine didn't have a valid DBUtil object instance.", Logger::ERROR, __CLASS__, __LINE__);
                return false;
            }

            if(!file_exists($this->_directory)) {
                new Logger("Theme's directory ({$this->_directory}) was not found.", Logger::ERROR, __CLASS__, __LINE__);
                return false;
            }

            if(!is_array($this->_templates)) {
                new Logger("Theme doesn't appear to have any template files.", Logger::ERROR, __CLASS__, __LINE__);
                return false;
            }

            return true;
        }

        public function getTemplate($templateName, $pageName = null) {
            if($pageName === null) {
                $template = MISC::findKey('template_' . $templateName, $this->_templates);
            } else {
                $pageTemplates = MISC::findKey('page_' . $pageName, $this->_templates);
                $template = MISC::findKey('template_' . $templateName, $pageTemplates);
            }

            return $this->parseTemplate($template);
        }

        protected function findPlaceholders($template) {
            preg_match_all("/{{(.*?)}}/", $template, $matches);

            if(!empty($matches) && !empty($matches[0])) {
                $placeholders = [];
                foreach($matches[0] as $match) {
                    $placeholders[] = new Placeholder($match);
                }

                return $placeholders;
            }

            return [];
        }

        public function parseTemplate($template) {
            $placeholders = $this->findPlaceholders($template);

            $replacements = [];
            foreach($placeholders as $placeholder) {
                if($placeholder instanceof Placeholder) {
                    switch ($placeholder->getCategory()) {
                        case 'forums':
                        case 'forum':
                            $ForumParser = new ForumParser();

                            switch($placeholder->getOption()) {
                                case 'latestNews':
                                case 'news':
                                    $Topic = new Topic($this->_DBUtil);
                                    $newsTopic = $Topic->getTopic($this->_Config->get('newsForum'));
                                    $newsTopic->setThreads();

                                    $html = '';
                                    $amount = (empty($placeholder->getArguments()[0]) ? 3 : $placeholder->getArguments()[0]);
                                    for($i = 0; $i < $amount; $i++) {
                                        $thread = $newsTopic->getThread($i);
                                        if($thread instanceof Thread) {
                                            $html .= $ForumParser->parseThread($thread, $this->getTemplate('portal_news_item', 'portal'));
                                        }
                                    }

                                    $replacements[$placeholder->get()] = $html;
                                    break;
                            }
                            break;
                        case 'user':
                        case 'profile':
                        case 'member':
                            break;
                        case 'site':
                            break;
                        case 'structure':
                            break;
                        case 'content':
                            break;
                        case 'pagination':
                            break;
                        case 'theme':
                            break;
                        case 'custom':
                        default:
                            if($placeholder->getOption()) {
                                /** @var PluginBase $plugin */
                                $pluginClass = $placeholder->getOption();
                                if(class_exists($pluginClass)) {
                                    $plugin = new $pluginClass;
                                    $template = $plugin->customParse($template);
                                }
                            }
                            break;
                    }
                }
            }

            return str_replace(array_keys($replacements), array_values($replacements), $template);
        }
    }