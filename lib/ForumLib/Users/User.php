<?php
  namespace ForumLib\Users;

  use ForumLib\Database\PSQL;

  use ForumLib\Integration\Nozum\NozumUser;
  use ForumLib\Integration\vB3\vB3User;
  use ForumLib\Utilities\MISC;
  use ForumLib\Utilities\Config;

  use ForumLib\Forums\Post;
  use ForumLib\Forums\Thread;

  /*
    The User object requires the PSQL class in order to function.
    It also requires a table, with whatever prefix you desire, the important part is to end it with users. (e.g. site2341_users)
    Within the table, there needs to be following columns:
    - username (varchar(50))
    - password (varchar(255))
    - email (varchar(100))
    - avatar (varchar(255))
    - group (int(1))
    - regip (varchar(15)) - Only supports IPv4 for now.
    - regdate (datetime)
    - lastlogin (datetime)
    - lastloginip (varchar(15)) - Only supports IPv4 for now.
    - firstname (varchar(30))
    - lastname (varchar(30))
  */

  class User {
    public $id;       // Account User ID.
    public $username;  // Account username.
    public $groupId;
    public $group;     // Group Object
    public $email;     // Account email address.
    public $lastLogin;
    public $lastIp;
    public $regIp;
    public $regDate;
    public $about;     // Array of information.
    public $firstname; // First name.
    public $lastname;  // Last name.
    public $avatar;    // Avatar URL.
    public $latestPosts;
    public $location;
    public $postCount;

    public $password;

    private $integration;

    private $S;
    private $lastError = array();
    private $lastMessage = array();

    public function __construct(PSQL $SQL, $_uid = null) {
        // We'll check if the required parameters are filled.
        if(!is_null($SQL)) {
            $this->S = $SQL;

            // Getting IP address for the user.
            $ipadr = '0.0.0.0';

            if(isset($_SERVER['SERVER_ADDR'])) {
                $ipadr = $_SERVER['SERVER_ADDR'];
            }

            if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ipadr = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }

            if(isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                $ipadr = $_SERVER['HTTP_CF_CONNECTING_IP'];
            }

            $this->lastLogin = array(
                'date'  => date('Y-m-d H:i:s'),
                'ip'    => $ipadr
            );

            $this->lastIp = $ipadr;

            $C = new Config;
            $this->config = $C->config;
            switch(array_column($this->config, 'integration')[0]) {
                case 'vB3':
                    $this->integration = new vB3User($this->S);
                    break;
                case 'Nozum':
                default:
                    $this->integration = new NozumUser($this->S);
                    break;
            }
        } else {
            $this->lastError[] = 'Something went wrong with the user.';
            return false;
        }
    }

    public function login($uname = 0) {
        return $this->integration->login($uname, $this);
    }

    public function register() {
        return $this->integration->register($this);
    }

    // Set the password if the passwords match.
    public function setPassword($p1, $p2 = null, $login = false) {
        return $this->integration->setPassword($p1, $p2, $login, $this);
    }

    public function updateAccount() {
        return $this->integration->updateAccount($this);
    }

    public function getUser($_id = null, $byId = true) {
        return $this->integration->getUser($_id, $byId, $this);
    }

    public function usernameExists($_username = null) {
        return $this->integration->usernameExists($_username, $this);
    }

    public function sessionController() {
        return $this->integration->sessionController($this);
    }

    public function getStatus($_uid = null) {
        return $this->integration->getStatus($_uid, $this);
    }

    public function getCurrentPage($_uid = null) {
        return $this->integration->getCurrentPage($_uid, $this);
    }

    public function getOnlineCount() {
        return $this->integration->getOnlineCount($this);
    }

    public function getLatestPosts() {
        return $this->integration->getLatestPosts($this);
    }

    public function getRegisteredUsers() {
        return $this->integration->getRegisteredUsers($this);
    }

    public function unsetSQL() {
        $this->S = null;
        return $this;
    }

    public function setSQL(PSQL $_SQL) {
        if($_SQL instanceof PSQL) {
            $this->S = $_SQL;
            $this->lastMessage[] = 'Database was successfully set.';
        } else {
            $this->lastError[] = 'Parameter was not provided as an instance of PSQL.';
        }
        return $this;
    }

    public function setId($_id) {
      $this->id = $_id;
      return $this;
    }

    public function setPostCount($_id = null) {
        $this->postCount = $this->integration->setPostCount($_id, $this);
        return $this;
    }

    public function setAvatar($_avatar) {
      $this->avatar = $_avatar;
      return $this;
    }

    public function setAbout($_about) {
        $this->about = $_about;
        return $this;
    }

    public function setGroupId($_gid) {
      $this->groupId = $_gid;
      return $this;
    }

    public function setGroup($_gid) {
      $G = new Group($this->S);
      $this->group = $G->getGroup($_gid);
      return $this;
    }

    public function setFirstname($_firstname) {
      $this->firstname = $_firstname;
      return $this;
    }

    public function setLastname($_lastname) {
      $this->lastname = $_lastname;
      return $this;
    }

    public function setLocation($_location) {
        $this->location = $_location;
        return $this;
    }

    public function setLastLogin($_date) {
      $this->lastLogin = $_date;
      return $this;
    }

    public function setRegDate($_date) {
      $this->regDate = $_date;
      return $this;
    }

    public function setLastIP($_ip) {
      $this->lastIp = $_ip;
      return $this;
    }

    public function setRegIP($_ip) {
      $this->regIp = $_ip;
      return $this;
    }

    public function setEmail($_email) {
      $this->email = $_email;
      return $this;
    }

    public function setUsername($_username) {
      $this->username = $_username;
      return $this;
    }

    public function getURL() {
        $url = $this->username;

        $url = str_replace(' ', '_', $url);

        return $url;
    }

    public function getLastMessage() {
      return end($this->lastMessage);
    }

    public function getLastError() {
      return end($this->lastError);
    }

    public function getErrors() {
      return $this->lastError;
    }

    public function getMessages() {
      return $this->lastMessage;
    }
  }
