<?php
    namespace SBLib\Handlers\Install;

    use SBLib\Database\DBUtil;
    use SBLib\Database\DBUtilQuery;

    use SBLib\Users\User;
    use SBLib\Users\Group;

    use SBLib\Forums\Category;
    use SBLib\Forums\Topic;
    use SBLib\Forums\Thread;
    use SBLib\Forums\Post;

    /**
     * Class Database
     * The database installation class. This will install the database structure, as long as the
     * actual database schema is created beforehand.
     *
     * @package SBLib\Installer
     */
    class Installer {
        private $_queries;
        private $_structureFile;

        private $_data;
        private $_adminUser;
        /**
         * @var Group $_defaultGroup
         */
        private $_defaultGroup;
        /**
         * @var Topic $_newsTopic
         */
        private $_newsTopic;

        private $_dbUtil;

        public function __construct(DBUtil $dbUtil, $data) {
            $this->_structureFile = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/structure.sql';
            $this->_dbUtil = $dbUtil;
            $this->_data = $data;

            $this->_addTables();
            $dbUtil->addQueries($this->_queries)
                ->runQueries();

            $this->_addAdmin();
            $this->_prepareForum($this->_adminUser);

            $this->_generateConfigs();

            if(empty($dbUtil->getLastErrorCode())) {
                return true;
            } else {
                return ['message' => $dbUtil->getLastError(), 'code' => $dbUtil->getLastErrorCode()];
            }
        }

        private function _addTables() {
            if(file_exists($this->_structureFile)) {
                $queryData = file_get_contents($this->_structureFile);
                $tablesQuery = new DBUtilQuery();
                $tablesQuery->setName('tablesQuery')
                    ->setQuery($queryData);

                $this->_queries[] = $tablesQuery;
                return true;
            } else {
                // TODO: Handle the error upon the structure file not being found.
                return false;
            }
        }

        private function _addAdmin() {
            $adminGroup = new Group($this->_dbUtil);
            $adminGroup->setName('Administrator')
                ->setOrder(1)
                ->setAdmin(true)
                ->setDescription('Forum administrators')
                ->create();

            $adminUser = new User($this->_dbUtil);
            $adminUser->setUsername($this->_data->adminUsername)
                ->setEmail($this->_data->adminEmail)
                ->setPassword($this->_data->adminPassword, $this->_data->adminPassword)
                ->setGroup($adminGroup)
                ->register();

            $this->_adminUser = $adminUser;
        }

        private function _prepareForum(User $adminUser) {
            $defaultGroup = new Group($this->_dbUtil);
            $defaultGroup->setName('Member')
                ->setOrder(2)
                ->setDescription('Default forum member group')
                ->create();

            $this->_defaultGroup = $defaultGroup;

            $firstCategory = new Category($this->_dbUtil);
            $firstCategory->setEnabled(true)
                ->setTitle('First Category')
                ->setDescription('This is the first category, automagically generated by the forum installer.')
                ->createCategory();

            $firstTopic = new Topic($this->_dbUtil);
            $firstTopic->setEnabled(true)
                ->setTitle('First Topic')
                ->setDescription('This is the first topic, which also is automagically generated by the forum installer.')
                ->setCategoryId($firstCategory->getId())
                ->createTopic();

            $this->_newsTopic = $firstTopic;

            $firstPost = new Post($this->_dbUtil);
            $firstPost->setAuthor($adminUser->getId())
                ->setHTML("
            <h1>Welcome to {$this->_data->forumName}!</h1>
            
            <p>We've recently installed the forum, and this is some automagically generated content.
            The content was generated by the forum installer. It can however be deleted.</p>
            
            <p>If you're a visitor, you're probably a bit early on in your adventure here. Please
            stay tuned while the forum is configured and set up.</p>
            
            <p>Kind Regards,<br />
            Administrator, {$adminUser->username}</p>
        ");

            $firstThread = new Thread($this->_dbUtil);
            $firstThread->setTitle("Welcome to {$this->_data->forumName}!")
                ->setAuthor($adminUser->getId())
                ->setTopicId($firstTopic->getId())
                ->createThread(null, $firstPost);
        }

        private function _generateConfigs() {
            $config = [
                'database' => [
                    'dbUser'            => $this->_data->dbUser,
                    'dbPass'            => $this->_data->dbPass,
                    'dbHost'            => $this->_data->dbHost,
                    'dbPort'            => $this->_data->dbPort,
                    'dbName'            => $this->_data->dbName,
                    'dbPrefix'          => $this->_data->dbPrefix
                ],
                'main'     => [
                    'name'              => $this->_data->siteName,
                    'description'       => $this->_data->description,
                    'lang'              => $this->_data->language,
                    'theme'             => $this->_data->theme,
                    'captchaPublicKey'  => $this->_data->reCaptchaPublicKey,
                    'captchaPrivateKey' => $this->_data->reCaptchaPrivateKey,
                    'siteRoot'          => $this->_data->siteRoot,
                    'defaultGroup'      => $this->_data->_defaultGroup->getId(),
                    'newsForum'         => $this->_data->_newsTopic->getId()
                ]
            ];

            foreach($config as $name => $values) {
                // TODO: Generate the config files.
                $options = json_encode($values);
                $configFile = fopen($this->_data->rootDirectory . '/config/' . $name . '.conf.json', 'w');
                fwrite($configFile, $options);
                fclose($configFile);
            }

            // Write install.lock
            $installLock = fopen($this->_data->rootDirectory . '/Installer/install.lock', 'w');
            fwrite($installLock,'Locked installation');
            fclose($installLock);
        }
    }