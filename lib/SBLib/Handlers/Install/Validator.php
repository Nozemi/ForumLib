<?php
    namespace SBLib\Handlers\Install;

    use SBLib\Database\DBUtil;
    use SBLib\Utilities\MISC;

    class Validator {
        private $_data;

        private $_errors  = [];
        private $_success = [];

        public function __construct($postData) {
            $this->_data = (object) $postData;

            // Let's set every input field to true by default.
            foreach($postData as $key => $value) {
                $this->_success[$key] = true;
            }

            // Let's override input fields that has a default value.
            // We'll want to give them a message by default, letting the user know what to expect by not filling them out.
            $this->_success['dbHost']   = ['status' => true, 'message' => 'Will use default host. (<i>localhost</i>)'];
            $this->_success['dbPort']   = ['status' => true, 'message' => 'Will use default port. (<i>3306</i>)'];
            $this->_success['dbUser']   = ['status' => true, 'message' => 'Will use default port. (<i>root</i>)'];
            $this->_success['dbPass']   = ['status' => true, 'message' => 'Will use empty password by default.'];
            $this->_success['dbPrefix'] = ['status' => true, 'message' => 'There is no default prefix. Meaning tables won\'t have a prefix at all.'];

            $this->_success['forumIntegration'] = ['status' => true, 'message' => 'Will use default integration. (<i>SBLibIntegration</i>)'];
            $this->_success['forumLanguage']    = ['status' => true, 'message' => 'Will use default language. (<i>English</i>)'];
            $this->_success['forumTheme']       = ['status' => true, 'message' => 'Will use default theme. (<i>Slickboard</i>)'];
        }

        public function validateDatabase() {
            // First of all, let's check if the database name is specified,
            // as this is required to connect to the database.
            if(!$this->_data->dbName) {
                // If the database name isn't specified, we need to send an error to the user.
                $this->_errors['dbName'] = 'A database name is required in order to connect to the database.';
                $this->_success['dbName'] = false;
            } else if($this->_data->dbName) {
                // Now we'll try to connect to the database with the provided details.
                try {
                    $db = new DBUtil((object) [
                        'host'   => $this->_data->dbHost,
                        'port'   => $this->_data->dbPort,
                        'name'   => $this->_data->dbName,
                        'user'   => $this->_data->dbUser,
                        'pass'   => $this->_data->dbPass,
                        'prefix' => $this->_data->dbPrefix
                    ]);

                    // Let's check if the connection is initialized.
                    if(!$db->isInitialized()) {
                        // If the connection wasn't successful, we'll provide the user with the latest known error.
                        $this->_errors[] = 'Database connection didn\'t initialize successfully. (<i>' . $db->getLastError() . '</i>)';

                        // Now let's act on the latest known error code.
                        switch($db->getLastErrorCode()) {
                            case 1045:
                                // Access denied for user - usually means incorrect username or password.
                                $this->_errors['dbUser'] = $this->_errors['dbPass'] = 'Access denied. Usually this is because the provided username and/or password is incorrect.';
                                $this->_success['dbUser'] = $this->_success['dbPass'] = false;
                                break;
                            case 2002:
                                // Means that it's unable to connect to the server.
                                // Usually the reason for this is the hostname provided.
                                $this->_errors['dbHost'] = 'There is a potential error with the host. Please verify that the host is correct and connectable.';
                                $this->_success['dbHost'] = false;
                                break;
                        }
                    }
                } catch(\Exception $exception) {
                    $this->_errors[] = 'Unable to connect to database. (<i>' . $exception->getMessage() . '</i>)';
                    $this->_errors['dbUser'] = 'Please verify that the username is correct.';
                    $this->_errors['dbPass'] = 'Please verify that the password is correct.';
                    $this->_success['dbUser'] = false;
                    $this->_success['dbPass'] = false;
                }
            }

            return $this;
        }

        public function validateForum() {
            // Let's validate the forum installation settings.

            if(!$this->_data->forumName) {
                $this->_errors['forumName'] = 'Forum name isn\'t set.';
                $this->_success['forumName'] = false;
            }

            if(!$this->_data->forumDescription) {
                $this->_errors['forumDescription'] = 'Forum description isn\'t set.';
                $this->_success['forumDescription'] = false;
            }

            if(!$this->_data->contactEmail) {
                $this->_errors['contactEmail'] = 'Contact email isn\'t set.';
                $this->_success['contactEmail'] = false;
            } else if($this->_data->contactEmail) {
                if(!filter_var($this->_data->contactEmail, FILTER_VALIDATE_EMAIL)) {
                    $this->_errors['contactEmail'] = 'Invalid contact email. Please provide a valid email address.';
                    $this->_success['contactEmail'] = false;
                }
            }

            // Now we'll need a bit more complicated validation for the site root directory.
            if($this->_data->rootDirectory) {
                $rootDirectory = MISC::getRootDirectory()->server . $this->_data->rootDirectory;
                if(!file_exists($rootDirectory . '/index.php')) {
                    $this->_errors[] = '<strong>[Root Directory]</strong> Are you sure the root directory is correct? Could not find the index.php file for the forums in there. (Path: ' . realpath($rootDirectory) . ')';
                }

                if(!is_writeable($rootDirectory)) {
                    $this->_errors[] = '<strong>[Root Directory]</strong> The root directory isn\'t writeable. Please make sure the webserver is allowed to write files in there. (Path: ' . realpath($rootDirectory) . ')';
                }

                if(!is_writeable($rootDirectory) || !file_exists($rootDirectory . '/index.php')) {
                    $this->_errors['rootDirectory'] = 'Please reference the errors displayed at the top of the page.';
                    $this->_success['rootDirectory'] = false;
                }
            }

            return $this;
        }

        public function validateAdmin() {
            if(!$this->_data->adminEmail) {
                $this->_errors['adminEmail'] = 'Email for admin account is required.';
                $this->_success['adminEmail'] = false;
            } else if($this->_data->adminEmail) {
                if(!filter_var($this->_data->adminEmail, FILTER_VALIDATE_EMAIL)) {
                    $this->_errors['adminEmail'] = 'Email is invalid. Please use the correct format. (<i>name@provider.ext</i>)';
                    $this->_success['adminEmail'] = false;
                }
            }

            if(!$this->_data->adminUsername) {
                $this->_errors['adminUsername'] = 'Username for admin account is required.';
                $this->_success['adminUsername'] = false;
            }

            if(!$this->_data->adminPassword) {
                $this->_errors['adminPassword'] = 'Password for admin account is required.';
                $this->_success['adminPassword'] = false;
            }

            return $this;
        }

        public function getErrors() {
            return $this->_errors;
        }

        public function getSuccess() {
            return $this->_success;
        }
    }