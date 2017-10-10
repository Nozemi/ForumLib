<?php
    namespace SBLib\Plugin;

    use SBLib\Utilities\Logger;
    use SBLib\Utilities\MISC;

    use SBLib\ThemeEngine\MainEngine;

    class PluginHandler {

        const PAGE_TOP      = 0;
        const PAGE_BOTTOM   = 1;
        const PAGE_MIDDLE   = 2;

        protected $plugins = array();
        protected $themeEngine;

        /**
         * PluginHandler constructor.
         * @param string $pluginsDirectory
         */
        public function __construct($pluginsDirectory = 'plugins') {
            $this->loadPlugins($pluginsDirectory);
        }

        /**
         * @param string $pluginsDirectory
         *
         * @return bool
         */
        private function loadPlugins($pluginsDirectory) {
            $pluginsDirectory = MISC::findFile($pluginsDirectory);

            if(file_exists($pluginsDirectory)) {
                $tmp_plugins = array();

                foreach (glob($pluginsDirectory . '/*/*.json') as $file) {
                    $config = json_decode(file_get_contents($file), true);

                    $tmp_plugins[] = array(
                        'name'      => $config['name'],
                        'priority'  => $config['priority'],
                        'directory' => dirname($file),
                        'mainClass' => $config['mainClass'],
                        'classes'   => $config['classes']
                    );
                }

                MISC::array_sort_key($tmp_plugins, 'priority');

                foreach($tmp_plugins as $tmp_plugin) {
                    $plugin = new Plugin($tmp_plugin['name']);
                    $plugin->setMainClass($tmp_plugin['mainClass'])
                        ->setDirectory($tmp_plugin['directory'])
                        ->setPriority($tmp_plugin['priority'])
                        ->setClasses($tmp_plugin['classes']);

                    $this->plugins[] = $plugin;
                    new Logger('Plugin ['. $plugin->getName() .'] successfully loaded.', Logger::INFO, __FILE__, __LINE__);
                }

                new Logger('Plugins successfully loaded.', Logger::INFO, __FILE__, __LINE__);
                return true;
            } else {
                new Logger('Plugins directory wasn\'t found. (' . $pluginsDirectory . ')', Logger::WARNING, __FILE__, __LINE__);
                return false;
            }
        }

        /**
         * @return array
         */
        public function getPlugins() {
            return $this->plugins;
        }

        public function requireClasses() {
            foreach($this->getPlugins() as $plugin) {
                /** Plugin $plugin */
                require($plugin->getDirectory() . '/' . $plugin->getMainClass() . '.php');

                foreach($plugin->getClasses() as $class) {
                    require($plugin->getDirectory() . '/' . $class . '.php');
                }
            }
        }

        /**
         * @param int $hook
         * @return bool
         */
        public function executePlugin($hook = PluginHandler::PAGE_TOP) {
            foreach($this->getPlugins() as $plugin) {
                /** @var Plugin $plugin */
                /** @var PluginBase $thePlugin */

                $className = $plugin->getMainClass();
                $thePlugin = new $className($this->themeEngine);

                if(!is_subclass_of($thePlugin, 'ForumLib\Plugin\PluginBase')) {
                    new Logger("Plugin [{$plugin->getName()}] did not extend PluginBase class in ForumLib.", Logger::ERROR, __FILE__, __LINE__);
                    return false;
                }

                switch($hook) {
                    case PluginHandler::PAGE_TOP:
                        $thePlugin->pageTop();
                        break;
                    case PluginHandler::PAGE_BOTTOM:
                    case PluginHandler::PAGE_MIDDLE:
                    default:
                        break;
                }
            }

            return true;
        }

        public function setThemeEngine(MainEngine $themeEngine) {
            $this->themeEngine = $themeEngine;
            return $this;
        }

        public function getThemeEngine() {
            return $this->themeEngine;
        }
    }



