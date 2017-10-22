<?php
    namespace SBLib\ThemeEngine;

    use SBLib\Database\DBUtil;
    use SBLib\Database\DBUtilQuery;

    use SBLib\Utilities\Config;
    use SBLib\Utilities\MISC;

    use SBLib\Users\User;
    use SBLib\Users\Group;

    use SBLib\Forums\Thread;
    use SBLib\Forums\Topic;
    use SBLib\Forums\Category;
    use SBLib\Forums\Various;

    use SBLib\Plugin\PluginBase;

    /**
     * @var  $_SQL DBUtil
     * @var  $_Config Config
     *
     */

    class MainEngine {
        public $name; // Theme name
        public $directory; // Theme directory

        protected $_config; // Theme config (theme.json file within the theme folder)
        protected $_rootDir;

        protected $_templates; // Templates loaded from the HTML files.
        protected $_varWrapperStart;
        protected $_varWrapperEnd;

        protected $_SQL; // DBUtil object
        protected $_Config; // Config object

        protected $_lastError; // Array of last error messages
        protected $_lastMessage; // Array of last info messages

        const START = 0;
        const END = 1;

        protected function customParse($_template) {
            return $_template;
        }

        /**
         * NewThemeEngine constructor.
         * @param $_name String - Theme name
         * @param DBUtil|null $SQL
         * @param Config|null $Config
         */
        public function __construct(DBUtil $SQL, $_name = 'slickboard', Config $Config = null) {
            if($Config == null) {
                $Config = new Config;
            }

            $this->_SQL         = $SQL;
            $this->_Config      = $Config;
            $this->name         = $_name;
            $this->directory    = MISC::findFile('themes/' . $this->name);
            $this->_rootDir      = ($Config->getConfigValue('siteRoot') ? '/' . $Config->getConfigValue('siteRoot') . '/' : '/');

            if($this->validateTheme()) {
                $this->setConfig();
                $this->setTemplates();

                if($this->_config) {
                    $this->_varWrapperStart  = MISC::findKey('varWrapper1', $this->_config);
                    $this->_varWrapperEnd    = MISC::findKey('varWrapper2', $this->_config);
                } else {
                    $this->_varWrapperStart  = '{{';
                    $this->_varWrapperEnd    = '}}';
                }
            } else {
                $this->_lastError[] = 'Failed to create object.';
                return false;
            }

            return true;
        }

        private function validateTheme() {
            // TODO: Check if name is specified
            // TODO: Check if theme directory is found.
            if(!$this->_Config instanceof Config) {
                $this->_lastError[] = 'Config was not successfully provided.';
                return false;
            }

            return true;
        }

        public function getWrapper($place) {
            if($place == self::START) {
                return $this->_varWrapperStart;
            } else {
                return $this->_varWrapperEnd;
            }
        }

        private function setTemplates() {
            foreach(glob($this->directory . '/*', GLOB_ONLYDIR) as $dir) {
                $dir = explode('/', $dir);
                $dir = end($dir);

                $this->_templates['page_' . $dir] = array();

                foreach(glob($this->directory . '/' . $dir . '/*.template.html') as $file) {
                    $this->_templates['page_' . $dir][basename($file, '.template.html')] = file_get_contents($file);
                }
            }

            return $this;
        }

        private function setConfig() {
            $configFile = $this->directory . '/theme.json';
            if(file_exists($configFile)) {
                $this->_lastMessage[] = 'Theme config was successfully loaded.';
                $this->_config = json_decode(file_get_contents($configFile), true);
            } else {
                $this->_lastMessage[] = 'No theme config was present.';
                $this->_config = false;
            }

            return $this;
        }

        public function getConfig() {
            return $this->_config;
        }

        public function getName() {
            return $this->name;
        }

        public function getDBUtil() {
            return $this->_SQL;
        }

        public function getTemplate($_template, $_page = null) {
            $tmp = $this->_templates;

            if($_page) {
                $tmp = $this->_templates['page_' . $_page];
            }

            return $tmp;

            //return $this->parseTemplate(MISC::findKey($_template, $tmp));
        }

        protected function parseTemplate($_template) {
            $matches = $this->findPlaceholders($_template);

            foreach($matches as $match) {
                /** @var $match Placeholder */
                switch($match->getCategory()) {
                    case 'forum':
                    case 'forums':
                        $Forums = new Forums($this);

                        switch($match->getOption()) {
                            case 'latestNews':
                                // TODO: Implement a way to decide between a blogging kind of system, or use a forum topic.
                                if($this->_Config instanceof Config) {
                                    $T = new Topic($this->_SQL);
                                    $top = $T->getTopic(MISC::findKey('newsForum', $this->_Config->config));
                                    $top->setThreads();

                                    $html = '';
                                    $amount = (isset($match->getArguments()[0]) ? $match->getArguments()[0] : 3);
                                    $amount = ($amount > count($top->threads) ? count($top->threads) : $amount);

                                    for($i = 0; $i < $amount; $i++) {
                                        $html .= $Forums->parseForum($this->getTemplate('portal_news'), $top->threads[$i]);
                                    }

                                    $_template = $this->replaceVariable($match->getPlaceholder(), $_template, $html);
                                }
                                break;
                            case 'latestPosts':
                            case 'recentPosts':
                                $V = new Various($this->_SQL);
                                $threads = $V->getLatestPosts();
                                $html = '';
                                $amount = (isset($match->getArguments()[0]) ? $match->getArguments()[0] : 10);
                                $amount = ($amount > count($threads) ? count($threads) : $amount);

                                for($i = 0; $i < $amount; $i++) {
                                    $html .= $Forums->parseForum($this->getTemplate('portal_latest_post_list_item', 'portal'), $threads[$i]);
                                }

                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, $html);
                                break;
                            case 'categories':
                                $C = new Category($this->_SQL);
                                $cats = $C->getCategories();

                                $html = '';

                                foreach($cats as $cat) {
                                    $html .= $Forums->parseForum($this->getTemplate('category_view', 'forums'), $cat);
                                }

                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, $html);
                                break;
                            case 'groupPerms':
                                $G = new Group($this->_SQL);
                                $P = new Profile($this);

                                $groups = $G->getGroups();

                                $html = '';

                                foreach($groups as $group) {
                                    $html .= $P->parseGroup($this->getTemplate('admin_categories_group_perms', 'admin'), $group);
                                }

                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, $html);
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
                    case 'structure':
                        $_template = $this->replaceVariable($match->getPlaceholder(), $_template, $this->getTemplate($match->getOption()));
                        break;
                    case 'theme':
                        switch($match->getOption()) {
                            case 'name':
                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, $this->name);
                                break;
                            case 'dir':
                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, ($this->_rootDir ? $this->_rootDir : '') . '/' . $this->directory . '/');
                                break;
                            case 'assets':
                            case 'assetsDir':
                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, ($this->_rootDir ? $this->_rootDir : '') . '/' . $this->directory . '/_assets/');
                                break;
                            case 'imgDir':
                            case 'img':
                            case 'imgs':
                            case 'images':
                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, ($this->_rootDir ? $this->_rootDir : '') . '/' . $this->directory . '/_assets/img/');
                                break;
                        }
                        break;
                    case 'site':
                        switch($match->getOption()) {
                            case 'captchaPublicKey':
                                $C = new Config;
                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, MISC::findKey('captchaPublicKey', $C->config));
                                break;
                            case 'topicName':
                                $C = new Category($this->_SQL);
                                $T = new Topic($this->_SQL);
                                $cat = $C->getCategory($_GET['category'], false);
                                $top = $T->getTopic($_GET['topic'], false, $cat->id);

                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, $top->title);
                                break;
                            case 'topicUrl':
                                $C = new Category($this->_SQL);
                                $T = new Topic($this->_SQL);
                                $cat = $C->getCategory($_GET['category'], false);
                                $top = $T->getTopic($_GET['topic'], false, $cat->id);

                                $url = '/forums/' . $cat->getURL() . '/' . $top->getURL() . '/';
                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, $url);
                                break;
                            case 'topicId':
                                $C = new Category($this->_SQL);
                                $T = new Topic($this->_SQL);
                                $cat = $C->getCategory($_GET['category'], false);
                                $top = $T->getTopic($_GET['topic'], false, $cat->id);

                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, $top->id);
                                break;
                            case 'name':
                            case 'siteName':
                                if($this->_Config instanceof Config) {
                                    $_template = $this->replaceVariable($match->getPlaceholder(), $_template, MISC::findKey('name', $this->_Config->config));
                                } else {
                                    $_template = $this->replaceVariable($match->getPlaceholder(), $_template, 'Undefined');
                                }
                                break;
                            case 'desc':
                            case 'description':
                                if(!$this->_Config instanceof Config) {
                                    $_template = $this->replaceVariable($match->getPlaceholder(), $_template, MISC::findKey('description', $this->_Config->config));
                                }
                                break;
                            case 'rootDir':
                            case 'rootDirectory':
                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, $this->_rootDir);
                                break;
                            case 'currPage':
                            case 'currentPage':
                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, MISC::getPageName($_SERVER['SCRIPT_FILENAME'], $this->_SQL));
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

                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, $html);
                                break;
                            case 'pageName':
                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, MISC::getPageName($_SERVER['SCRIPT_FILENAME'], $this->_SQL));
                                break;
                            case 'userNav':
                                if(empty($_SESSION)) {
                                    $html = $this->getTemplate('main_navigation_guest');
                                } else {
                                    $html = $this->getTemplate('main_navigation_user');
                                }
                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, $html);
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
                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, $html);
                                break;
                            case 'onlineCount':
                                $U = new User($this->_SQL);
                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, $U->getOnlineCount()['total']);
                                break;
                            case 'onlineMembers':
                                $U = new User($this->_SQL);
                                $P = new Profile($this);

                                $html = '';
                                for($i = 0; $i < $U->getOnlineCount()['memberCount']; $i++) {
                                    $html .= $P->parseProfile(
                                        $this->getTemplate('portal_online_users_user', 'portal'),
                                        $U->getUser($U->getOnlineCount()['members'][$i]['userId'])
                                    );

                                    if($i != ($U->getOnlineCount()['memberCount'] - 1)) {
                                        $html .= ', ';
                                    }
                                }

                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, $html);
                                break;
                            case 'guestCount':
                                $U = new User($this->_SQL);
                                $guests = ($U->getOnlineCount()['guestCount'] == 1 ? 'guest is' : 'guests are');
                                $_template = $this->replaceVariable($match->getPlaceholder(), $_template, $U->getOnlineCount()['guestCount'] . ' ' . $guests);
                                break;
                        }
                        break;
                    case 'pagination':
                        // TODO: Need to do something here. Idk just what yet.
                        switch($match->getOption()) {
                            case 'links':
                                break;
                        }
                        break;
                    case 'content':
                        $contentQuery = new DBUtilQuery;
                        $contentQuery->setName('contentQuery')
                            ->setMultipleRows(false)
                            ->setQuery("SELECT `value` FROM `{{PREFIX}}content_strings` WHERE `key` = :key")
                            ->addParameter(':key', $match->getOption(), \PDO::PARAM_STR);
                        $this->_SQL->runQuery($contentQuery);

                        // TODO: Add some kind of error checking here.
                        $content = (object) $this->_SQL->getResultByName($contentQuery->getName());
                        $_template = $this->replaceVariable($match->getPlaceholder(), $_template, $content->value);
                        break;
                    case 'custom':
                    default:
                        if($match->getOption()) {
                            if(class_exists($match->getOption())) {
                                /** @var PluginBase $plugin */
                                $pluginClass = $match->getOPtion();
                                $plugin = new $pluginClass($this);
                                $_template = $plugin->customParse($_template);
                            }
                        }
                        break;
                }
            }
        }

        /**
         * Finds all placeholder variables within the template files.
         * @param $_template
         * @return mixed
         */
        protected function findPlaceholders($_template) {
            preg_match_all('/' . $this->_varWrapperStart . '(.*?)' . $this->_varWrapperEnd . '/', $_template, $tmpMatches);

            $matches = array();
            foreach($tmpMatches as $match) {
                $ph = new Placeholder($match, $this);
                $matches[] = $ph->getObject();
            }

            $matches = array();
            foreach($matches as $match) {
                $ph = new Placeholder($match, $this);
                $matches[] = $ph->getObject();
            }

            return $matches;
        }

        protected function replaceVariable($_placeholder, $_template, $_replacement, $file = 'NONE', $line = 0) {
            /*if(basename($file) == 'Profile.php') {
                print_r($_replacement); echo "{$file} - {$line}<hr>";

                new Logger("Replacing variable {$_match}, with {$_replacement}.", Logger::DEBUG, $file, $line);
            }*/

            return str_replace($_placeholder, $_replacement, $_template);
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