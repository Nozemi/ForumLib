<?php
    namespace SBLib\WebServer;

    class Apache extends WebServerBase {
        // TODO: Apache URL rewriting and stuff.
        // We'll need to generate the appropriate .htaccess files for the website, and to protect the config directory.

        /**
         * Create Apache Configs
         *
         * @param $directory
         * @param array $options
         *
         * @return bool
         */
        public function createConfigs($directory = null, array $options = null) {
            // TODO: Implement createConfigs() method.

            return true;
        }

        /**
         * This method will write the .htaccess file in the necessary locations
         * in each theme folder, to make sure that the .html files are inaccessible
         * from the viewers web browsers.
         *
         * This will block \_assets/src folder from being viewed from the visitors browser.
         * While the rest of the \_assets folder is accessible (due to the browser needing access
         * to render the stylesheets, images and scripts.)
         */
        public function validateThemesFolders() {
            // TODO: Loop through all the themes, verify that they got what Apache configs they need.
        }

        /**
         * This will make sure that the config directory has a .htaccess file that
         * blocks the web browser from accessing the .json files stored inside there.
         */
        public function validateConfigFolders() {

        }

        public function configGenerator() {
            $currentTheme = $this->config->getConfigValue('theme','slickboard') . '/';
            $currentThemeDirectory = $this->themeDirectory . $currentTheme;

            $pages = [];
            foreach(glob($currentThemeDirectory . '/*') as $folder) {
                if(is_dir($folder)) {
                    $pages[] = $folder;
                }
            }


        }
    }