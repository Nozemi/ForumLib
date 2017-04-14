<?php
    namespace ForumLib\Utilities;

    use ForumLib\Forums\Category;
    use ForumLib\Forums\Topic;
    use ForumLib\Forums\Thread;
    use ForumLib\Forums\Post;

    class ThemeEngine {
        public $name;                   // Theme name
        public $directory;              // Theme directory
        public $templates   = array();  // Theme template files
        public $scripts     = array();  // Theme scripts
        public $styles      = array();  // Theme styles

        private $pHolderWrapper = array('{{', '}}'); // The placeholder wrappers that will be parsed from
                                                     // this class to show the actual content.

        private $lastError      = array(); // Stores errors produced by this class.
        private $lastMessage    = array(); // Stores messages produced by this class.

        private $sql            = null; // Stores the PSQL object that will be used to run database queries.
        private $config         = null; // Stores the Config object, if it was specified upon initializing the object.
        private $themeConf      = null; // Stores the theme config, gotten from the theme root (theme.json file).

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

                $this->styles = $this->getStyles();     // Getting theme stylesheets.
                $this->scripts = $this->getScripts();   // Getting theme scripts.

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

        private function parseTemplate2($_template) {
            preg_match_all('/' . $this->pHolderWrapper[0] . '(.*?)' . $this->pHolderWrapper[1] . '/', $_template, $matches);

            foreach($matches[1] as $match) {
                $phldr = explode('::', $match);
                switch($phldr[0]) {
                    case 'structure':
                        $_template = $this->replaceVariable($phldr[0], $phldr[1], $_template, $this->getTemplate($phldr[1]));
                        break;
                    case 'theme':
                        switch($phldr[1]) {
                            case 'imgDir':
                                $_template = $this->replaceVariable($phldr[0], $phldr[1], $_template, '/' . $this->directory . '/_assets/img/');
                                break;
                            case 'dir':
                                $_template = $this->replaceVariable($phldr[0], $phldr[1], $_template, '/' .$this->directory . '/');
                                break;
                        }
                        break;
                    case 'site':
                        switch($phldr[1]) {
                            case 'latestNews':
                                $html = '';
                                for($i = 0; $i < 5; $i++) {
                                    $html .= $this->getTemplate('portal_news');
                                }
                                $_template = $this->replaceVariable($phldr[0], $phldr[1], $_template, $html);
                                break;
                        }
                        break;
                }
            }

            return $_template;
        }

        /**
         * @param $_template string - Name of the template file (without the .template.html extension)
         *
         * @return string
         */
        private function parseTemplate($_template) {
            // Gets all the variable placeholders in the current template, so that we can parse them and fill them with
            // what they're supposed to have.
            preg_match_all('/' . $this->pHolderWrapper[0] . '(.*?)' . $this->pHolderWrapper[1] . '/', $_template, $matches);

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
                        $L = new Language(null, $this->config);
                        $_template = $this->replaceVariable($template[0], $template[1], $_template, MISC::findKey($template[1], $L->getLanguage()));
                        break;
                    case 'site':
                        // TODO: Improve the way site variables are handled. Then document them all.
                        if($template[1] == 'tabTitle') {
                            $_template = $this->replaceVariable($template[0], $template[1], $_template, MISC::getTabTitle($_SERVER['SCRIPT_FILENAME']));
                        } else if($template[1] == 'latestNews') {
                            if($this->config instanceof Config) {
                                $T = new Topic($this->sql);
                                $top = $T->getTopic(MISC::findKey('newsForum', $this->config->config));
                                $top->setThreads();

                                $html = '';

                                foreach($top->threads as $thread) {
                                    $html .= $this->parseForum($this->getTemplate('portal_news'), $thread);
                                }
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $html);
                            }
                        } else if ($template[1] == 'stylesheets') {
                            $html = '';
                            foreach($this->styles as $style) {
                                $html .= '<link rel="stylesheet" type="text/css" href="' . $style . '" />'."\r\n";
                            }
                            $_template = $this->replaceVariable($template[0], $template[1], $_template, $html);
                        } else if($template[1] == 'scripts') {
                            $html = '';
                            foreach($this->scripts as $script) {
                                $html .= '<script type="text/javascript" src="' . $script . '"></script>'."\r\n";
                            }
                            $_template = $this->replaceVariable($template[0], $template[1], $_template, $html);
                        } else if($template[1] == 'userNav') {
                            if(empty($_SESSION)) {
                                $html = $this->getTemplate('main_navigation_guest');
                            } else {
                                $html = $this->getTemplate('main_navigation_user');
                            }
                            $_template = $this->replaceVariable($template[0], $template[1], $_template, $html);
                        } else if($template[1] == 'pagination') {
                            $_template = $this->replaceVariable($template[0], $template[1], $_template, $this->getTemplate('pagination'));
                        }
                        break;
                    case 'structure':
                        $_template = $this->replaceVariable($template[0], $template[1], $_template, $this->getTemplate($template[1]));
                        break;
                    case 'theme':
                        // Theme variables.
                        if($template[1] == 'imgDir') {
                            $_template = $this->replaceVariable($template[0], $template[1], $_template, '/' . $this->directory . '/_assets/img/');
                        } else if($template[1] == 'dir') {
                            $_template = $this->replaceVariable($template[0], $template[1], $_template, '/' .$this->directory . '/');
                        } else {
                            $_template = $this->replaceVariable($template[0], $template[1], $_template, $this->getPlugins($template[1]));
                        }
                        break;
                    case 'forum':
                        $C = new Category($this->sql);
                        $html = '';

                        if(strpos($template[1], '|')) {
                            $myTemp = explode('|', $template[1]);
                            $template[1] = $myTemp[0];
                            $template[2] = $myTemp[1];
                        }

                        if($template[1] == 'categories') {
                            $cats = $C->getCategories();

                            foreach($cats as $cat) {
                                $html .= $this->parseForum($this->getTemplate('category_view', 'forums'), $cat);
                            }
                        } else if($template[1] == 'topics') {
                            $T = new Topic($this->sql);
                            $tops = $T->getTopics();

                            foreach($tops as $top) {
                                $html .= $this->parseForum($this->getTemplate('topic_view', 'forums'), $top);
                            }
                        } else if($template[1] == 'category') {
                            $html = $this->getTemplate('category_view','forums');
                            $_template = $this->replaceVariable($template[0], $template[1].'|'.$template[2], $_template, $html);
                        }

                        $_template = $this->replaceVariable($template[0], $template[1], $_template, $html);
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
                            $_template = $this->replaceVariable($template[0], $template[1], $_template, $val['value']);
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

                        $_template = $this->parseForum($_template, $top);
                        break;
                    case 'pagination':
                        switch($template[1]) {
                            case 'links':
                                $html = '';

                                $count = 1;
                                foreach($_GET as $key => $value) {
                                    $tmpl = ((count($_GET) > 1 && $count != count($_GET)) ? 'pagination_link' : 'pagination_active');
                                    $html .= $this->parsePaginationLink($this->getTemplate($tmpl), $key, $value);
                                    $count++;
                                }

                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $html);
                                break;
                        }
                        break;
                    default:
                        break;
                }
            }

            return $_template;
        }

        /**
         * @param $_type            string - Placeholder type
         * @param $_name            string - Placeholder key name
         * @param $_template        string - Placeholder template to replace in
         * @param $_replacement     string - Value to replace with
         *
         * @return mixed HTML after the placeholder is replaced.
         */
        private function replaceVariable($_type, $_name, $_template, $_replacement) {
            return str_replace(
                $this->pHolderWrapper[0] . $_type . '::' . $_name . $this->pHolderWrapper[1],
                $_replacement,
                $_template
            );
        }

        private function parsePaginationLink($_template, $key, $value) {
            preg_match_all('/' . $this->pHolderWrapper[0] . '(.*?)' . $this->pHolderWrapper[1] . '/', $_template, $matches);

            $cat = $top = $trd = null;

            if(isset($_GET['category'])) {
                $C = new Category($this->sql);
                $cat = $C->getCategory($_GET['category'], false);
            }

            if(isset($_GET['topic'])) {
                if($cat instanceof Category) {
                    $T = new Topic($this->sql);
                    $top = $T->getTopic($_GET['topic'], false, $cat->id);
                }
            }

            if(isset($_GET['thread'])) {
                if($top instanceof Topic) {
                    $Tr = new Thread($this->sql);
                    $trd = $Tr->getThread($_GET['thread'], false, $top->id);
                }
            }

            foreach($matches[1] as $match) {
                $template = explode('::', $match);

                switch($template[1]) {
                    case 'linkTitle':
                        switch($key) {
                            case 'category':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $cat->title);
                                break;
                            case 'topic':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $top->title);
                                break;
                            case 'thread':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $trd->title);
                                break;
                            default:
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, ucwords($value));
                                break;
                        }
                        break;
                    case 'linkURL':
                        switch($key) {
                            case 'category':
                                $url = '/forums/';
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
                        $_template = $this->replaceVariable($template[0], $template[1], $_template, $url);
                        break;
                }
            }

            return $_template;
        }

        private function parseForum($_template, $_fObject) {
            preg_match_all('/' . $this->pHolderWrapper[0] . '(.*?)' . $this->pHolderWrapper[1] . '/', $_template, $matches);

            foreach($matches[1] as $match) {
                $template = explode('::', $match);

                switch($template[0]) {
                    case 'category':
                        switch($template[1]) {
                            case 'header':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $_fObject->title);
                                break;
                            case 'description':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $_fObject->description);
                                break;
                            case 'topics':
                                $html = '';
                                $T = new Topic($this->sql);
                                $tops = $T->getTopics($_fObject->id);
                                foreach($tops as $top) {
                                    $html .= $this->parseForum($this->getTemplate('topic_view', 'forums'), $top);
                                }
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $html);
                                break;
                        }
                        break;
                    case 'topic':
                        $C = new Category($this->sql);
                        $cat = $C->getCategory($_fObject->categoryId);

                        switch($template[1]) {
                            case 'header':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $_fObject->title);
                                break;
                            case 'description':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $_fObject->description);
                                break;
                            case 'url':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template,
                                    '/forums/' . $cat->getURL() . '/' . $_fObject->getURL()
                                );
                                break;
                        }
                        break;
                    case 'thread':
                        /** @var $_fObject Thread */
                        switch($template[1]) {
                            case 'title':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $_fObject->title);
                                break;
                            case 'lastResponderAvatar':
                                $avatar = $_fObject->author->avatar;
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $avatar);
                                break;
                            case 'poster':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $_fObject->author->username);
                                break;
                            case 'postDate':
                            case 'lastReplyDate':
                                $date = MISC::parseDate($_fObject->posted, $this->config, array('howLongAgo' => true));
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $date);
                                break;
                            case 'viewCount':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, 0);
                                break;
                            case 'replyCount':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, 0);
                                break;
                            case 'lastResponder':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, 'Username');
                                break;
                            case 'url':
                                $top = new Topic($this->sql);
                                $top = $top->getTopic($_fObject->topicId);

                                $cat = new Category($this->sql);
                                $cat = $cat->getCategory($top->categoryId);

                                $_template = $this->replaceVariable($template[0], $template[1], $_template,
                                    '/forums/' . $cat->getURL() . '/' . $top->getURL() . '/' . $_fObject->getURL());
                                break;
                        }
                        break;
                    case 'post':
                        /** @val $_fObject Post */
                        if($_fObject instanceof Post) {
                            switch($template[1]) {
                                case 'poster':
                                    $_template = $this->replaceVariable($template[0], $template[1], $_template, $_fObject->author->username);
                                    break;
                                case 'posterAvatar':
                                    $_template = $this->replaceVariable($template[0], $template[1], $_template, $_fObject->author->avatar);
                                    break;
                                case 'posterMemberSince':
                                    $date = MISC::parseDate($_fObject->author->regDate, $this->config, array('howLongAgo' => true));
                                    $_template = $this->replaceVariable($template[0], $template[1], $_template, $date);
                                    break;
                                case 'content':
                                    $content = (isset($_fObject->post_html) ? $_fObject->post_html : '<p>' . $_fObject->post_text . '</p>');
                                    $_template = $this->replaceVariable($template[0], $template[1], $_template, $content);
                                    break;
                                case 'posted':
                                    $date = MISC::parseDate($_fObject->post_date, $this->config, array('howLongAgo' => true));
                                    $_template = $this->replaceVariable($template[0], $template[1], $_template, $date);
                                    break;
                                case 'threadTitle':
                                    $T = new Thread($this->sql);
                                    $trd = $T->getThread($_fObject->threadId);
                                    $_template = $this->replaceVariable($template[0], $template[1], $_template, $trd->title);
                            }
                        }
                        break;
                    case 'threadList':
                        switch($template[1]) {
                            case 'header':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $_fObject->title);
                                break;
                            case 'description':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $_fObject->description);
                                break;
                            case 'threads':
                                $html = '';

                                /** @var $_fObject Topic */
                                $_fObject->setThreads();

                                if(!empty($_fObject->threads)) {
                                    foreach($_fObject->threads as $thread) {
                                        $html .= $this->parseForum($this->getTemplate('thread_view', 'forums'), $thread);
                                    }
                                } else {
                                    $html = $this->getTemplate('no_threads_msg', 'misc');
                                }

                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $html);
                                break;
                        }
                        break;
                    case 'threadView':
                        /** @val $_fObject Thread */

                        $C = new Category($this->sql);
                        $cat = $C->getCategory($_GET['category'], false);

                        $T = new Topic($this->sql);
                        $top = $T->getTopic($_GET['topic'], false, $cat->id);

                        $TR = new Thread($this->sql);
                        $trd = $TR->getThread($_GET['thread'], false, $top->id);
                        $trd->setPosts();

                        switch($template[1]) {
                            case 'title':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $trd->title);
                                break;
                            case 'posts':
                                $html = '';

                                foreach($trd->posts as $post) {
                                    $html .= $this->parseForum($this->getTemplate('post_view', 'forums'), $post);
                                }

                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $html);
                                break;
                        }
                        break;
                    case 'news':
                        switch($template[1]) {
                            case 'title':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $_fObject->title);
                                break;
                            case 'author':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $_fObject->author->username);
                                break;
                            case 'authorAvatar':
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $_fObject->author->avatar);
                                break;
                            case 'posted':
                                $date = MISC::parseDate($_fObject->posted, $this->config, array('howLongAgo' => true));
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $date);
                                break;
                            case 'content':
                                /** @var Post $post */
                                $_fObject->setPosts();
                                $post = $_fObject->posts[0];

                                $content = (!empty($post->post_html) ? $post->post_html : '<p>' . $post->post_text . '</p>');
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $content);
                                break;
                            case 'url':
                                $T = new Topic($this->sql);
                                $top = $T->getTopic($_fObject->topicId);

                                $C = new Category($this->sql);
                                $cat = $C->getCategory($top->categoryId);

                                $url = '/forums/' . $cat->getURL() . '/' . $top->getURL() . '/' . $_fObject->getURL();
                                $_template = $this->replaceVariable($template[0], $template[1], $_template, $url);
                                break;
                        }
                        break;
                    default:
                        break;
                }
            }

            return $_template;
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