<?php
    namespace ForumLib\ThemeEngine;

    use ForumLib\Database\DBUtil;
    use ForumLib\Database\DBUtilQuery;

    use ForumLib\Utilities\Config;
    use ForumLib\Utilities\MISC;

    use ForumLib\Users\User;
    use ForumLib\Users\Group;

    use ForumLib\Forums\Thread;
    use ForumLib\Forums\Topic;
    use ForumLib\Forums\Category;
    use ForumLib\Forums\Various;

    use ForumLib\Plugin\PluginBase;

    /**
     * @var  $_SQL DBUtil
     * @var  $_Config Config
     *
     */

    class MainEngine {
        public $name; // Theme name
        public $directory; // Theme directory

        protected $config; // Theme config (theme.json file within the theme folder)
        protected $rootDir;

        protected $templates; // Templates loaded from the HTML files.
        protected $varWrapperStart;
        protected $varWrapperEnd;

        protected $_SQL; // PSQL object
        protected $_Config; // Config object

        protected $lastError; // Array of last error messages
        protected $lastMessage; // Array of last info messages

        protected function customParse($_template) {
            return $_template;
        }

        /**
         * NewThemeEngine constructor.
         * @param $_name String - Theme name
         * @param DBUtil|null $SQL
         * @param Config|null $Config
         */
        public function __construct($_name, DBUtil $SQL = null, Config $Config = null) {
            $this->_SQL         = $SQL;
            $this->_Config      = $Config;
            $this->name         = $_name;
            $this->directory    = MISC::findFile('themes/' . $this->name);
            $this->rootDir      = array_column($Config->config, 'siteRoot');

            if($this->validateTheme()) {
                $this->setConfig();
                $this->setTemplates();

                if($this->config) {
                    $this->varWrapperStart = MISC::findKey('varWrapper1', $this->config);
                    $this->varWrapperEnd = MISC::findKey('varWrapper2', $this->config);
                } else {
                    $this->varWrapperStart = '{{';
                    $this->varWrapperEnd = '}}';
                }
            } else {
                $this->lastError[] = 'Failed to create object.';
                return false;
            }
        }

        private function validateTheme() {
            // TODO: Check if name is specified
            // TODO: Check if theme directory is found.
            if(!$this->_Config instanceof Config) {
                $this->lastError[] = 'Config was not successfully provided.';
                return false;
            }

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
                $this->config = json_decode(file_get_contents($configFile), true);
            } else {
                $this->lastMessage[] = 'No theme config was present.';
                $this->config = false;
            }

            return $this;
        }

        public function getConfig() {
            return $this->config;
        }

        public function getName() {
            return $this->name;
        }

        public function getDBUtil() {
            return $this->_SQL;
        }

        public function getTemplate($_template, $_page = null) {
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
                    case 'structure':
                        $_template = $this->replaceVariable($match, $_template, $this->getTemplate($template[1]));
                        break;
                    case 'forum':
                    case 'forums':
                        $Forums = new Forums($this);

                        switch($template[1]) {
                            case 'latestNews':
                                // TODO: Implement a way to decide between a blogging kind of system, or use a forum topic.
                                if($this->_Config instanceof Config) {
                                    $Forums = new Forums($this);

                                    $T = new Topic($this->_SQL);
                                    $top = $T->getTopic(MISC::findKey('newsForum', $this->_Config->config));
                                    $top->setThreads();
									
                                    $html = '';
                                    $amount = (isset($template[2]) ? $template[2] : 3);
                                    $amount = ($amount > count($top->threads) ? count($top->threads) : $amount);
                                    for($i = 0; $i < $amount; $i++) {
                                        $html .= $Forums->parseForum($this->getTemplate('portal_news'), $top->threads[$i]);
                                    }
                                    $_template = $this->replaceVariable($match, $_template, $html);
                                }
                                break;
                            case 'latestPosts':
                            case 'recentPosts':
                                $V = new Various($this->_SQL);
                                $threads = $V->getLatestPosts();
                                $html = '';
                                $amount = (isset($template[2]) ? $template[2] : 10);
                                $amount = ($amount > count($threads) ? count($threads) : $amount);
                                for($i = 0; $i < $amount; $i++) {
                                    $html .= $Forums->parseForum($this->getTemplate('portal_latest_post_list_item', 'portal'), $threads[$i]);
                                }
                                $_template = $this->replaceVariable($match, $_template, $html);
                                break;
                            case 'categories':
                                $C = new Category($this->_SQL);
                                $cats = $C->getCategories();

                                $html = '';

                                foreach($cats as $cat) {
                                    $html .= $Forums->parseForum($this->getTemplate('category_view', 'forums'), $cat);
                                }

                                $_template = $this->replaceVariable($match, $_template, $html);
                                break;
                            case 'groupPerms':
                                $G = new Group($this->_SQL);
                                $P = new Profile($this);

                                $groups = $G->getGroups();

                                $html = '';

                                foreach($groups as $group) {
                                    $html .= $P->parseGroup($this->getTemplate('admin_categories_group_perms', 'admin'), $group);
                                }

                                $_template = $this->replaceVariable($match, $_template, $html);
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

                                $_template = $Forums->parseForum($_template, $fItem);
                                break;
                        }
                        break;
                    case 'user':
                    case 'profile':
                        if(isset($_GET['username']) || isset($_SESSION['user']['username'])) {
                            $username = (isset($_GET['username']) ? $_GET['username'] : $_SESSION['user']['username']);

                            $Profile    = new Profile($this);
                            $User       = new User($this->_SQL);
                            $user       = $User->getUser(str_replace('_', ' ', $username), false);

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

                                $_template = $this->replaceVariable($match, $_template, ($this->rootDir ? '/' . $this->rootDir : '') . '/' . $this->directory . '/');
                                break;
                            case 'assets':
                            case 'assetsDir':
                                $_template = $this->replaceVariable($match, $_template, ($this->rootDir ? '/' . $this->rootDir : '') . '/' . $this->directory . '/_assets/');
                                break;
                            case 'imgDir':
                            case 'img':
                            case 'imgs':
                            case 'images':
                                $_template = $this->replaceVariable($match, $_template, ($this->rootDir ? '/' . $this->rootDir : '') . '/' . $this->directory . '/_assets/img/');
                                break;
                        }
                        break;
                    case 'site':
                        switch($template[1]) {
                            case 'captchaPublicKey':
                                $C = new Config;
                                $_template = $this->replaceVariable($match, $_template, MISC::findKey('captchaPublicKey', $C->config));
                                break;
                            case 'topicName':
                                $C = new Category($this->_SQL);
                                $T = new Topic($this->_SQL);
                                $cat = $C->getCategory($_GET['category'], false);
                                $top = $T->getTopic($_GET['topic'], false, $cat->id);

                                $_template = $this->replaceVariable($match, $_template, $top->title);
                                break;
                            case 'topicUrl':
                                $C = new Category($this->_SQL);
                                $T = new Topic($this->_SQL);
                                $cat = $C->getCategory($_GET['category'], false);
                                $top = $T->getTopic($_GET['topic'], false, $cat->id);

                                $url = '/forums/' . $cat->getURL() . '/' . $top->getURL() . '/';
                                $_template = $this->replaceVariable($match, $_template, $url);
                                break;
                            case 'topicId':
                                $C = new Category($this->_SQL);
                                $T = new Topic($this->_SQL);
                                $cat = $C->getCategory($_GET['category'], false);
                                $top = $T->getTopic($_GET['topic'], false, $cat->id);

                                $_template = $this->replaceVariable($match, $_template, $top->id);
                                break;
                            case 'name':
                            case 'siteName':
                                if($this->_Config instanceof Config) {
                                    $_template = $this->replaceVariable($match, $_template, MISC::findKey('name', $this->_Config->config));
                                } else {
                                    $_template = $this->replaceVariable($match, $_template, 'Undefined');
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
                            case 'pageName':
                                $_template = $this->replaceVariable($match, $_template, MISC::getPageName($_SERVER['SCRIPT_FILENAME']));
                                break;
                            case 'userNav':
                                if(empty($_SESSION)) {
                                    $html = $this->getTemplate('main_navigation_guest');
                                } else {
                                    $html = $this->getTemplate('main_navigation_user');
                                }
                                $_template = $this->replaceVariable($match, $_template, $html);
                                break;
                            case 'pagination':
                                $html = '';
                                $count = 1;
                                if($_GET['page'] == 'newthread') {
                                    $_GET['page'] = 'forums';
                                    $_GET['action'] = 'New Thread';
                                }
                                foreach($_GET as $key => $value) {
                                    if($key != 'threadId') {
                                        $tmpl = ((count($_GET) > 1 && $count != count($_GET)) ? 'pagination_link' : 'pagination_active');
                                        $html .= $this->parsePaginationLink($this->getTemplate($tmpl), $key, $value);
                                        $count++;
                                    }
                                }
                                $_template = $this->replaceVariable($match, $_template, $html);
                                break;
                            case 'onlineCount':
                                $U = new User($this->_SQL);
                                $_template = $this->replaceVariable($match, $_template, $U->getOnlineCount()['total']);
                                break;
                            case 'onlineMembers':
                                $U = new User($this->_SQL);
                                $UT = new Profile($this);

                                $html = '';
                                for($i = 0; $i < $U->getOnlineCount()['memberCount']; $i++) {
                                    $html .= $UT->parseProfile(
                                        $this->getTemplate('portal_online_users_user', 'portal'),
                                        $U->getUser($U->getOnlineCount()['members'][$i]['userId'])
                                    );

                                    if($i != ($U->getOnlineCount()['memberCount'] - 1)) {
                                        $html .= ', ';
                                    }
                                }

                                $_template = $this->replaceVariable($match, $_template, $html);
                                break;
                            case 'guestCount':
                                $U = new User($this->_SQL);
                                $guests = ($U->getOnlineCount()['guestCount'] == 1 ? 'guest is' : 'guests are');
                                $_template = $this->replaceVariable($match, $_template, $U->getOnlineCount()['guestCount'] . ' ' . $guests);
                                break;
                        }
                        break;
                    case 'pagination':
                        switch($template[1]) {
                            case 'links':
                                break;
                        }
                        break;
                    case 'content':
                        $contentQuery = new DBUtilQuery;
                        $contentQuery->setName('contentQuery')
                            ->setQuery("SELECT `value` FROM `{{PREFIX}}content_strings` WHERE `key` = :key")
                            ->addParameter(':key', $template[1], \PDO::PARAM_STR);
                        $this->_SQL->runQuery($contentQuery);

                        $content = $this->_SQL->getResultByName($contentQuery->getName());
                        $_template = $this->replaceVariable($match, $_template, $content['value']);
                        break;
                    default:
                    case 'custom':
                        if(isset($template[1])) {
                            if (class_exists($template[1])) {
                                /** @var PluginBase $plugin */
                                $plugin = new $template[1]($this);
                                $_template = $plugin->customParse($_template);
                            }
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
            preg_match_all('/' . $this->varWrapperStart . '(.*?)' . $this->varWrapperEnd . '/', $_template, $matches);

            return $matches;
        }

        protected function replaceVariable($_match, $_template, $_replacement) {
            return str_replace($this->varWrapperStart . $_match . $this->varWrapperEnd, $_replacement, $_template);
        }

        // Validate the themes that are loaded.
        public static function getThemes() {
            $validThemes = array();

            foreach(glob('themes/*', GLOB_ONLYDIR) as $dir) {
                $validThemes[] = dirname($dir);
            }

            return $validThemes;
        }

        private function parsePaginationLink($_template, $key, $value) {
            $matches = $this->findPlaceholders($_template);

            $cat = $top = $trd = null;

            if(isset($_GET['category'])) {
                $C = new Category($this->_SQL);
                $cat = $C->getCategory($_GET['category'], false);
            }
            if(isset($_GET['topic'])) {
                $T = new Topic($this->_SQL);
                $top = $T->getTopic($_GET['topic'], false, $cat->id);
            }

            $Tr = new Thread($this->_SQL);

            if(isset($_GET['thread']) && !isset($_GET['threadId'])) {
                $trd = $Tr->getThread($_GET['thread'], false, $top->id);
            } else if(isset($_GET['threadId'])) {
                $trd = $Tr->getThread($_GET['threadId']);
            }

            foreach($matches[1] as $match) {
                $template = explode('::', $match);
                switch($template[1]) {
                    case 'linkTitle':
                        switch($key) {
                            case 'category':
                                $_template = $this->replaceVariable($match, $_template, $cat->title);
                                break;
                            case 'topic':
                                $_template = $this->replaceVariable($match, $_template, $top->title);
                                break;
                            case 'thread':
                                $_template = $this->replaceVariable($match, $_template, $trd->title);
                                break;
                            default:
                                $_template = $this->replaceVariable($match, $_template, ucwords($value));
                                break;
                        }
                        break;
                    case 'linkURL':
                        switch($key) {
                            case 'category':
                                $url = '/forums/' . $cat->getURL();
                                break;
                            case 'topic':
                                $url = '/forums/' . $cat->getURL() . '/' . $top->getURL();
                                break;
                            case 'thread':
                                $url = '/forums/' . $cat->getURL() . '/' . $top->getURL() . '/' . $trd->getURL();
                                break;
                            default:
                                $url = '/' . $value;
                                break;
                        }
                        $_template = $this->replaceVariable($match, $_template, $url);
                        break;
                }
            }
            return $_template;
        }
    }