<?php

    namespace ForumLib\Utilities;

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

        private $config         = null; // Stores the config object, if it was specified upon initializing the object.
        private $themeConf      = null; // Stores the theme config, gotten from the theme root (theme.json file).

        /**
         * ThemeEngine constructor.
         *
         * @param             $_name    string - Theme name (the name of the direcotry under "themes"-directory)
         * @param Config|null $_config  Config - Config object that is optional. Currently to parse language strings.
         */
        public function __construct($_name, Config $_config = null) {
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
                    $dir = end(explode('/', $dir));

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
            // Gets all the variable placeholders in the current template, so that we can parse them and fill them with
            // what they're supposed to have.
            preg_match_all('/' . $this->pHolderWrapper[0] . '(.*)' . $this->pHolderWrapper[1] . '/', $_template, $matches);

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
                            // TODO: Clean up this stuff. No need to hardcode this stuff like this.
                            $html = '';
                            for($i = 0; $i < 5; $i++) {
                                $html .= $this->getTemplate('portal_news');
                            }
                            $_template = $this->replaceVariable($template[0], $template[1], $_template, $html);
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
                            $_template = $this->replaceVariable($template[0], $template[1], $_template, $this->directory . '/');
                        } else {
                            $_template = $this->replaceVariable($template[0], $template[1], $_template, $this->getPlugins($template[1]));
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