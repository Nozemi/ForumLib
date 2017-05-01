<?php
    namespace ForumLib\ThemeEngine;

    use ForumLib\Database\PSQL;

    use ForumLib\Utilities\Config;
    use ForumLib\Utilities\MISC;

    use ForumLib\Users\User;

    use ForumLib\Forums\Thread;
    use ForumLib\Forums\Topic;
    use ForumLib\Forums\Category;

    /**
     * @var  $_SQL PSQL
     * @var  $_Config Config
     *
     */

    class ThemeEngine {
        public $name; // Theme name
        public $directory; // Theme directory

        protected $config; // Theme config (theme.json file within the theme folder)

        protected $templates; // Templates loaded from the HTML files.
        protected $varWrapper1;
        protected $varWrapper2;

        protected $_SQL; // PSQL object
        protected $_Config; // Config object

        protected $lastError; // Array of last error messages
        protected $lastMessage; // Array of last info messages

        abstract protected function customParse($_template);

        /**
         * NewThemeEngine constructor.
         * @param $_name String - Theme name
         * @param PSQL|null $SQL
         * @param Config|null $Config
         */
        public function __construct($_name, PSQL $SQL = null, Config $Config = null) {
            $this->_SQL         = $SQL;
            $this->_Config      = $Config;
            $this->name         = $_name;
            $this->directory    = MISC::findFile('themes/' . $this->name);

            if($this->validateTheme()) {

            } else {
                $this->lastError[] = 'Failed to create object.';
                return false;
            }
        }

        private function validateTheme() {
            // TODO: Check if name is specified
            // TODO: Check if theme directory is found.

            return true;
        }

        private function setTemplates() {
            foreach(glob($this->directory . '/*', GLOB_ONLYDIR) as $dir) {
                $dir = explode('/', $dir);
                $dir = end($dir);

                $this->templates['page_' . $dir] = array();

                foreach(glob($this->directory . '/' . $dir . '/*.template.html') as $file) {
                    $this->templates['page_' . $dir][basename($file, '.template.html')] = file_get_contents($file);
                }
            }

            return $this;
        }

        private function setConfig() {
            $configFile = $this->directory . '/theme.json';
            if(file_exists($configFile)) {
                $this->lastMessage[] = 'Theme config was successfully loaded.';
                $this->config = json_decode(file_get_contents($configFile));
            } else {
                $this->lastMessage[] = 'No theme config was present.';
                $this->config = false;
            }

            return $this;
        }

        protected function getTemplate($_template, $_page = null) {
            $tmp = $this->templates;

            if($_page) {
                $tmp = $this->templates['page_' . $_page];
            }

            return $this->parseTemplate(MISC::findKey($_template, $tmp));
        }

        protected function parseTemplate($_template) {
            $matches = $this->findPlaceholders($_template);

            foreach($matches[1] as $match) {
                $template = explode('::', $match);

                switch($template[0]) {
                    case 'forum':
                    case 'forums':
                        switch($template[1]) {
                            case 'latestNews':
                                break;
                            case 'latestPosts':
                                break;
                            default:
                                $fItem = null;

                                if(isset($_GET['category']) && isset($_GET['topic']) && isset($_GET['thread'])) {
                                    $Thread = new Thread($this->_SQL);
                                    $fItem = $Thread->getThread($_GET['threadId']);
                                }

                                if(isset($_GET['category']) && isset($_GET['topic']) && !isset($_GET['thread'])) {
                                    $Topic      = new Topic($this->_SQL);
                                    $Category   = new Category($this->_SQL);

                                    $cat = $Category->getCategory($_GET['category'], false);

                                    $fItem = $Topic->getTopic($_GET['topic'], false, $cat->id);
                                }

                                if(isset($_GET['category']) && !isset($_GET['topic']) && !isset($_GET['thread'])) {
                                    $Category   = new Category($this->_SQL);
                                    $fItem = $Category->getCategory($_GET['category'], false);
                                }

                                $fParser  = new Forums($this);
                                $_template = $fParser->parseForum($_template, $fItem);
                                break;
                        }
                        break;
                    case 'user':
                        if(isset($_GET['username'])) {
                            $Profile    = new Profile($this);
                            $User       = new User($this->_SQL);
                            $user       = $User->getUser($_GET['user'], false);

                            if($user instanceof User) {
                                $_template = $Profile->parseProfile($_template, $user);
                            }
                        }
                        break;
                    case 'theme':
                        switch($template[1]) {
                            case 'name':
                                $_template = $this->replaceVariable($match, $_template, $this->name);
                                break;
                            case 'dir':
                                $_template = $this->replaceVariable($match, $_template, $this->directory);
                                break;
                            case 'assets':
                            case 'assetsDir':
                                $_template = $this->replaceVariable($match, $_template, $this->directory . '/_assets/');
                                break;
                            case 'imgDir':
                            case 'img':
                            case 'imgs':
                            case 'images':
                                $_template = $this->replaceVariable($match, $_template, $this->directory . '/_assets/img/');
                                break;
                        }
                        break;
                    case 'site':
                        switch($template[1]) {
                            case 'name':
                            case 'siteName':
                                if(!$this->_Config instanceof Config) {
                                    $_template = $this->replaceVariable($match, $_template, MISC::findKey('name', $this->_Config->config));
                                }
                                break;
                            case 'desc':
                            case 'description':
                                if(!$this->_Config instanceof Config) {
                                    $_template = $this->replaceVariable($match, $_template, MISC::findKey('description', $this->_Config->config));
                                }
                                break;
                            case 'rootDir':
                            case 'rootDirectory':
                                $rootDir = MISC::findFile('themes');
                                $rootDir = $rootDir . '../';

                                $_template = $this->replaceVariable($match, $_template, $rootDir);
                                break;
                            case 'currPage':
                            case 'currentPage':
                                $_template = $this->replaceVariable($match, $_template, MISC::getPageName($_SERVER['SCRIPT_FILENAME']));
                                break;
                            case 'members':
                            case 'membersList':
                            case 'listMembers':
                                $U = new User($this->_SQL);
                                $P = new Profile($this);

                                $html = '';
                                foreach($U->getRegisteredUsers() as $user) {
                                    $usr = $U->getUser($user['id']);
                                    $html .= $P->parseProfile($this->getTemplate('member_item'), $usr);
                                }

                                $_template = $this->replaceVariable($match, $_template, $html);
                                break;
                        }
                        break;
                    case 'pagination':
                        switch($template[1]) {
                            case 'links':
                                break;
                        }
                        break;
                    default:
                    case 'custom':
                        if(class_exists($template[1])) {
                            $plugin = new $template[1]($this);
                            $_template = $plugin->customParse($_template);
                        }
                        break;
                }
            }

            return $_template;
        }

        /**
         * Finds all placeholder variables within the template files.
         * @param $_template
         * @return mixed
         */
        protected function findPlaceholders($_template) {
            if($this->config) {
                $varWrapperStart = MISC::findKey('varWrapper1', $this->config);
                $varWrapperEnd = MISC::findKey('varWrapper2', $this->config);
            } else {
                $varWrapperStart = '{{';
                $varWrapperEnd = '}}';
            }

            preg_match_all('/' . $varWrapperStart . '(.*?)' . $varWrapperEnd . '/', $_template, $matches);

            return $matches;
        }

        protected function replaceVariable($_match, $_template, $_replacement) {
            return str_replace($this->varWrapper1 . $_match . $this->varWrapper2, $_replacement, $_template);
        }
    }