<?php
    namespace ForumLib\ThemeEngine;

    use ForumLib\Users\User;

    use ForumLib\Utilities\Config;
    use ForumLib\Utilities\PSQL;
    use ForumLib\Utilities\MISC;

    use ForumLib\Forums\Various;
    use ForumLib\Forums\Category;
    use ForumLib\Forums\Topic;
    use ForumLib\Forums\Thread;

    class ThemeEngine {
        public $name;                   // Theme name
        public $directory;              // Theme directory
        public $templates   = array();  // Theme template files
        public $scripts     = array();  // Theme scripts
        public $styles      = array();  // Theme styles

        protected $pHolderWrapper = array('{{', '}}'); // The placeholder wrappers that will be parsed from
                                                     // this class to show the actual content.

        protected $lastError      = array(); // Stores errors produced by this class.
        protected $lastMessage    = array(); // Stores messages produced by this class.

        protected $sql            = null; // Stores the PSQL object that will be used to run database queries.
        protected $config         = null; // Stores the Config object, if it was specified upon initializing the object.
        protected $themeConf      = null; // Stores the theme config, gotten from the theme root (theme.json file).

        /**
         * Method to be overridden by plugins. This isn't supposed to do anything from this class.
         *
         * @param $_template
         *
         * @return mixed
         */
        public function customParse($_template) {
            return $_template;
        }

        /**
         * ThemeEngine constructor.
         *
         * @param             $_name    string - Theme name (the name of the direcotry under "themes"-directory)
         * @param Config|null $_config  Config - Config object that is optional. Currently to parse language strings.
         */
        public function __construct($_name, Config $_config = null, PSQL $_sql = null) {
            $this->name = $_name;
            $this->directory = MISC::findFile('themes/' . $this->name); // Finds the theme directory.

            // Checks if the theme actually exists.
            if(!MISC::findFile($this->directory)) {
                // Theme doesn't exist, so we'll be producing an error.
                $this->lastError[] = 'No theme found';
            } else {
                // Checks if there is a theme config file within the theme's directory.
                if(file_exists($this->directory . '/theme.json')) {
                    // Adds the theme configs to the object.
                    $this->themeConf = json_decode(file_get_contents($this->directory . '/theme.json'), true);
                }

                // A loop that loops through the folders within the theme folder.
                // This is what's getting the template files and inserting them into the templates array.
                foreach(glob($this->directory . '/*', GLOB_ONLYDIR) as $dir) {
                    $dir = explode('/', $dir);
                    $dir = end($dir);

                    $this->templates['page_' . $dir] = array();

                    // Loops through the templates within the directory found in the parent loop,
                    // and inserts it into the templates array.
                    foreach(glob($this->directory . '/' . $dir . '/*.template.html') as $file) {
                        $this->templates['page_' . $dir][basename($file, '.template.html')] = file_get_contents($file);
                    }
                }

                //$this->styles = $this->getStyles();     // Getting theme stylesheets.
                //$this->scripts = $this->getScripts();   // Getting theme scripts.

                // Checks if $_config is an instance of the class Config.
                if($_config instanceof Config) {
                    $this->config = $_config;
                }

                if($_sql instanceof PSQL) {
                    $this->sql = $_sql;
                }
            }
        }

        // Get theme styles.
        private function getStyles() {
            $styles = array();

            /*
             *
             * Checks whether or not the themeConf variable is an array.
             * If it's an array, it means the theme has a configuration file.
             * This is useful for when you need to specify the order of styles.
             *
             */
            if(is_array($this->themeConf)) {
                // Loops through the styles specified in the config, and adds them to the styles array.
                foreach($this->themeConf['styles'] as $style) {
                    $styles[] = $this->directory . '/_assets/css/' . $style . '.css';
                }
            } else {
                // Loops through the styles directory of the theme, and adds the styles to the styles array.
                foreach(glob($this->directory . '/_assets/css/*.css') as $style) {
                    $styles[] = $style;
                }
            }

            return $styles;
        }

        // Get theme scripts.
        private function getScripts() {
            $scripts = array();

            /*
             *
             * Checks whether or not the themeConf variable is an array.
             * If it's an array, it means the theme has a configuration file.
             * This is useful for when you need to specify the order of scripts.
             *
             */
            if(is_array($this->themeConf)) {
                // Loops through the styles specified in the config, and adds them to the scripts array.
                foreach($this->themeConf['scripts'] as $script) {
                    $scripts[] = $this->directory . '/_assets/scripts/' . $script . '.js';
                }
            } else {
                // Loops through the styles directory of the theme, and adds the scripts to the scripts array.
                foreach(glob($this->directory . '/_assets/scripts/*.js') as $script) {
                    $scripts[] = $script;
                }
            }

            return $scripts;
        }

        // Gets the theme's plugins if there is a config file that specifies them.
        private function getPlugins($_pos) {
            // If $this->themeConf isn't an array, it means that there was no valid theme configuration file.
            if(!is_array($this->themeConf)) {
                $this->lastError[] = 'Can\'t get plugins without having a configuration file defining them.';
                return false;
            }

            $plugins = '';
            $position = 'top';

            // Decides whether the plugin should be in the header or footer of the page.
            switch($_pos) {
                case 'pluginsTop':
                    $position = 'top';
                    break;
                case 'pluginsBottom':
                    $position = 'bottom';
                    break;
            }

            // Loops through the plugins in the $_pos supplied with the function.
            foreach($this->themeConf['plugins'][$position] as $plugin) {
                switch($plugin['type']) {
                    case 'css':
                        // TODO: Add style plugins functionality.
                        break;
                    case 'js':
                        $plugins .= '<script type="text/javascript" src="' . $this->directory . '/_assets/plugins/' . $plugin['source'] . '.js"></script>'."\r\n";
                        break;
                }
            }

            // Returns the HTML with the plugins.
            return $plugins;
        }

        /**
         * Gets and parses the template, then returns the HTML of the template.
         *
         * @param      $_template   string - Name of the template file (without the .template.html extension)
         * @param null $_page       string - Optional page for the template. This will have the method check for templates
         *                          within that directory of the theme. For example error pages would have errors
         *                          for $_page.
         *
         * @return string html
         */
        public function getTemplate($_template, $_page = null) {
            if($_page) {
                $tmp = $this->templates['page_' . $_page];
            } else {
                $tmp = $this->templates;
            }

            return $this->parseTemplate(MISC::findKey($_template, $tmp));
        }

        /**
         * @param $_template string - Name of the template file (without the .template.html extension)
         *
         * @return string
         */
        private function parseTemplate($_template) {
            $Forums = new Forums($this);
            // Gets all the variable placeholders in the current template, so that we can parse them and fill them with
            // what they're supposed to have.
            $matches = $this->getPlaceholders($_template);

            // Loops through all the placeholder variables in the template.
            foreach($matches[1] as $match) {
                // Splits the placeholder variable on ::, because in front of the ::, is the type
                // of placeholder it is, while after ::, is the value key name.
                $template = explode('::', $match);

                // TODO: Clean up the switch statement below.

                // $template[0] is the placeholder variable type, which we're going to handle
                // differently for each type.
                switch($template[0]) {
                    case 'lang':
                        //$L = new Language(null, $this->config);
                        //$_template = $this->replaceVariable($match, $_template, MISC::findKey($template[1], $L->getLanguage()));
                        break;
                    case 'site':
                        // TODO: Improve the way site variables are handled. Then document them all.
                        switch($template[1]) {
                            case 'siteName':
                                if(!$this->config instanceof Config) {
                                    $this->config = new Config;
                                }
                                $_template = $this->replaceVariable($match, $_template, MISC::findKey('name', $this->config->config));
                                break;
                            case 'pageName':
                                $_template = $this->replaceVariable($match, $_template, MISC::getPageName($_SERVER['SCRIPT_FILENAME']));
                                break;
                            case 'topicName':
                                $C = new Category($this->sql);
                                $T = new Topic($this->sql);
                                $cat = $C->getCategory($_GET['category'], false);
                                $top = $T->getTopic($_GET['topic'], false, $cat->id);

                                $_template = $this->replaceVariable($match, $_template, $top->title);
                                break;
                            case 'topicUrl':
                                $C = new Category($this->sql);
                                $T = new Topic($this->sql);
                                $cat = $C->getCategory($_GET['category'], false);
                                $top = $T->getTopic($_GET['topic'], false, $cat->id);

                                $url = '/forums/' . $cat->getURL() . '/' . $top->getURL() . '/';
                                $_template = $this->replaceVariable($match, $_template, $url);
                                break;
                            case 'topicId':
                                $C = new Category($this->sql);
                                $T = new Topic($this->sql);
                                $cat = $C->getCategory($_GET['category'], false);
                                $top = $T->getTopic($_GET['topic'], false, $cat->id);

                                $_template = $this->replaceVariable($match, $_template, $top->id);
                                break;
                            case 'latestNews':
                                if($this->config instanceof Config) {
                                    $T = new Topic($this->sql);
                                    $top = $T->getTopic(MISC::findKey('newsForum', $this->config->config));
                                    $top->setThreads();

                                    $html = '';

                                    $amount = ($template[2] ? $template[2] : 3);
                                    $amount = ($amount > count($top->threads) ? count($top->threads) : $amount);

                                    for($i = 0; $i < $amount; $i++) {
                                        $html .= $Forums->parseForum($this->getTemplate('portal_news'), $top->threads[$i]);
                                    }

                                    $_template = $this->replaceVariable($match, $_template, $html);
                                }
                                break;
                            case 'listMembers':
                                $U = new User($this->sql);
                                $P = new Profile($this);

                                $html = '';
                                foreach($U->getRegisteredUsers() as $user) {
                                    $usr = $U->getUser($user['id']);
                                    $html .= $P->parseProfile($this->getTemplate('member_item'), $usr);
                                }

                                $_template = $this->replaceVariable($match, $_template, $html);
                                break;
                            case 'recentPosts':
                                $V = new Various($this->sql);
                                $threads = $V->getLatestPosts();

                                $html = '';

                                $amount = ($template[2] ? $template[2] : 10);
                                $amount = ($amount > count($threads) ? count($threads) : $amount);

                                for($i = 0; $i < $amount; $i++) {
                                    $html .= $Forums->parseForum($this->getTemplate('portal_latest_post_list_item', 'portal'), $threads[$i]);
                                }

                                $_template = $this->replaceVariable($match, $_template, $html);
                                break;
                            case 'stylesheets':
                                $html = '';
                                foreach($this->styles as $style) {
                                    $html .= '<link rel="stylesheet" type="text/css" href="' . $style . '" />'."\r\n";
                                }
                                $_template = $this->replaceVariable($match, $_template, $html);
                                break;
                            case 'scripts':
                                $html = '';
                                foreach($this->scripts as $script) {
                                    $html .= '<script type="text/javascript" src="' . $script . '"></script>'."\r\n";
                                }
                                $_template = $this->replaceVariable($match, $_template, $html);
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
                                $_template = $this->replaceVariable($match, $_template, $this->getTemplate('pagination'));
                                break;
                            case 'captchaPublicKey':
                                $C = new Config;
                                $_template = $this->replaceVariable($match, $_template, MISC::findKey('captchaPublicKey', $C->config));
                                break;
                        }
                        break;
                    case 'structure':
                        $_template = $this->replaceVariable($match, $_template, $this->getTemplate($template[1]));
                        break;
                    case 'theme':
                        // Theme variables.
                        switch($template[1]) {
                            case 'imgDir':
                                $_template = $this->replaceVariable($match, $_template, '/' . $this->directory . '/_assets/img/');
                                break;
                            case 'dir':
                                $_template = $this->replaceVariable($match, $_template, '/' .$this->directory . '/');
                                break;
                            default:
                                $_template = $this->replaceVariable($match, $_template, $this->getPlugins($template[1]));
                                break;
                        }
                        break;
                    case 'forum':
                        $C = new Category($this->sql);
                        $html = '';

                        switch($template[1]) {
                            case 'categories':
                                $cats = $C->getCategories();

                                foreach($cats as $cat) {
                                    $html .= $Forums->parseForum($this->getTemplate('category_view', 'forums'), $cat);
                                }
                                break;
                            case 'topics':
                                $T = new Topic($this->sql);
                                $tops = $T->getTopics();

                                foreach($tops as $top) {
                                    $html .= $Forums->parseForum($this->getTemplate('topic_view', 'forums'), $top);
                                }
                                break;
                        }

                        $_template = $this->replaceVariable($match, $_template, $html);
                        break;
                    case 'content':
                        $this->sql->prepareQuery($this->sql->replacePrefix('{{DBP}}', "
                            SELECT
                                `value`
                            FROM `{{DBP}}content_strings`
                            WHERE `key` = :key
                        "));
                        if($this->sql->executeQuery(array(
                            ':key' => $template[1]
                        ))) {
                            $val = $this->sql->fetch();
                            $_template = $this->replaceVariable($match, $_template, $val['value']);
                        } else {
                            $this->lastError[] = 'Something went wrong while running query.';
                            return false;
                        }
                        break;
                    case 'threadList':
                    case 'threadView':
                        $C = new Category($this->sql);
                        $cat = $C->getCategory($_GET['category'], false);

                        $T = new Topic($this->sql);
                        $top = $T->getTopic($_GET['topic'], false, $cat->id);

                        $_template = $Forums->parseForum($_template, $top);
                        break;
                    case 'categoryView':
                        $C = new Category($this->sql);
                        $cat = $C->getCategory($_GET['category'], false);

                        $_template = $Forums->parseForum($_template, $cat);
                        break;
                    case 'pagination':
                        switch($template[1]) {
                            case 'links':
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
                        }
                        break;
                    case 'user':
                        $U = new User($this->sql);
                        $usr = $U->getUser($_SESSION['user']['id']);

                        switch($template[1]) {
                            case 'username':
                                $_template = $this->replaceVariable($match, $_template, $usr->username);
                                break;
                            case 'avatar':
                                $_template = $this->replaceVariable($match, $_template, $usr->avatar);
                                break;
                            case 'email':
                                $_template = $this->replaceVariable($match, $_template, $usr->email);
                                break;
                        }
                        break;
                    case 'profile':
                        $U = new User($this->sql);
                        $usr = $U->getUser($_GET['username'], false);

                        $P = new Profile($this);
                        $_template = $P->parseProfile($_template, $usr);
                        break;
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
         * @param $_match           string - Placeholder
         * @param $_template        string - Placeholder template to replace in
         * @param $_replacement     string - Value to replace with
         *
         * @return mixed HTML after the placeholder is replaced.
         */
        protected function replaceVariable($_match, $_template, $_replacement) {
            return str_replace(
                $this->pHolderWrapper[0] . $_match . $this->pHolderWrapper[1],
                $_replacement,
                $_template
            );
        }

        private function parsePaginationLink($_template, $key, $value) {
            $matches = $this->getPlaceholders($_template);

            $cat = $top = $trd = null;

            if(isset($_GET['category'])) {
                $C = new Category($this->sql);
                $cat = $C->getCategory($_GET['category'], false);
            }

            if(isset($_GET['topic'])) {
                $T = new Topic($this->sql);
                $top = $T->getTopic($_GET['topic'], false, $cat->id);
            }

            $Tr = new Thread($this->sql);
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

        public function getPlaceholders($_template) {
            preg_match_all('/' . $this->pHolderWrapper[0] . '(.*?)' . $this->pHolderWrapper[1] . '/', $_template, $matches);

            return $matches;
        }

        // Sets the theme's name.
        public function setName($_name) {
            $this->name = $_name;

            return $this;
        }

        // Sets the theme's directory.
        public function setDirectory($_directory) {
            $this->directory = $_directory;

            return $this;
        }

        public function getConfig() {
            return $this->themeConf;
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