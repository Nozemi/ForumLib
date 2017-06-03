<?php
  namespace ForumLib\Users;

  use ForumLib\Database\PSQL;

<<<<<<< HEAD
=======
  use ForumLib\Integration\Nozum\NozumUser;
  use ForumLib\Integration\vB3\vB3User;
>>>>>>> 615a34eea3757a7329b41b8f2d8bd5f54f42e90f
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

<<<<<<< HEAD
        $this->lastLogin = array(
          'date'  => date('Y-m-d H:i:s'),
          'ip'    => $ipadr
        );

        $this->lastIp = $ipadr;
      } else {
        $this->lastError[] = 'Something went wrong with the user.';
        return false;
      }
    }
=======
            $this->lastLogin = array(
                'date'  => date('Y-m-d H:i:s'),
                'ip'    => $ipadr
            );
>>>>>>> 615a34eea3757a7329b41b8f2d8bd5f54f42e90f

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
<<<<<<< HEAD
      if($this->usernameExists($this->username)) {
          $this->lastError[] = 'Username already in use.';
          return false;
      }

      if(!preg_match('/^[^\W_]+$/', $this->username)) {
          $this->lastError[] = 'Sorry, username may only contain alphanumeric characters. (A-Z,a-z,0-9)';
          return false;
      }

      if(is_null($this->password) || is_null($this->username)) {
        $this->lastError[] = 'Username and/or password is missing.';
        return false;
      } else {
        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
          INSERT INTO `{{DBP}}users` (
             `username`
            ,`password`
            ,`regdate`
            ,`regip`
            ,`email`
            ,`firstname`
            ,`lastname`
            ,`group`
          ) VALUES (
             :username
            ,:password
            ,:regdate
            ,:regip
            ,:email
            ,:firstname
            ,:lastname
            ,:group
          );
        "));

        $C = new Config;
        // Check if the query executes alright, and return an error if it doesn't.
        if($this->S->executeQuery(array(
          ':username'   => $this->username,
          ':password'   => $this->password,
          ':regdate'    => date('Y-m-d H:i:s', time()),
          ':regip'      => $this->lastLogin['ip'],
          ':email'      => $this->email,
          ':firstname'  => $this->firstname,
          ':lastname'   => $this->lastname,
          ':group'      => ($this->group->id ? $this->group->id : MISC::findKey('defaultGroup', $C->config))
        ))) {
          $this->lastMessage[] = 'Account was successfully registered.';
          return true;
        } else {
          if(defined('DEBUG')) {
            $this->lastError[] = $this->S->getLastError();
          } else {
            $this->lastError[] = 'Something went wrong during registration.';
          }
          return false;
        }
      }
=======
        return $this->integration->register($this);
>>>>>>> 615a34eea3757a7329b41b8f2d8bd5f54f42e90f
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
<<<<<<< HEAD
        $C = new Config;
        if(array_column($C->config, 'integration')[0] == 'vB3') return false;

        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
            INSERT INTO `{{DBP}}users_session` SET
               `uid` = :uid
              ,`lastActive` = :lastActive
              ,`ipAddress` = :ipAddress
              ,`created` = :created
              ,`lastPage` = :lastPage
              ,`phpSessId` = :phpSessId
              ,`userAgent` = :userAgent
            ON DUPLICATE KEY UPDATE
               `uid` = :uid
              ,`lastActive` = :lastActive
              ,`ipAddress` = :ipAddress
              ,`lastPage` = :lastPage
              ,`phpSessId` = :phpSessId
              ,`userAgent` = :userAgent;
        "));

        if($this->S->executeQuery(array(
            ':uid'          => ($this->id ? $this->id : 0),
            ':lastActive'   => date('Y-m-d H:i:s'),
            ':ipAddress'    => $this->lastIp,
            ':created'      => date('Y-m-d H:i:s'),
            ':lastPage'     => 'N/A',
            ':phpSessId'    => session_id(),
            ':userAgent'    => $_SERVER['HTTP_USER_AGENT']
        ))) {

        } else {
            if(defined('DEBUG')) {
                $this->lastError[] = $this->S->getLastError();
                return false;
            } else {
                $this->lastError[] = 'Something went wrong while running session controller.';
                return false;
            }
        }
    }

    public function getStatus($_uid = null) {
        $C = new Config;
        if(array_column($C->config, 'integration')[0] == 'vB3') return false;

        if(is_null($_uid)) $_uid = $this->id;

        if($_uid == 0) {
            $this->lastError[] = 'No valid user to get status from.';
            return false;
        }

        $status = 0;

        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
          SELECT
            *
          FROM `{{DBP}}users_session`
          WHERE `uid` = :uid
          ORDER BY `lastActive` DESC
          LIMIT 1
        "));

        if($this->S->executeQuery(array(
            ':uid' => $_uid
        ))) {
            $result = $this->S->fetch();

            if((strtotime($result['lastActive']) + 180) >= time()) {
                $status = 1;
            }
        } else {
            if(defined('DEBUG')) {
                $this->lastError[] = $this->S->getLastError();
                return false;
            } else {
                $this->lastError[] = 'Something went wrong while getting current status for user with ID ' . $_uid . '.';
                return false;
            }
        }

        return $status;
    }

    public function getCurrentPage($_uid = null) {

    }

    public function getOnlineCount() {
        $C = new Config;
        if(array_column($C->config, 'integration')[0] == 'vB3') {
            return array(
                'members' => 0,
                'memberCount' => 0,
                'guestCount' => 0,
                'total' => 0
            );
        }

        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
            SELECT
                *
            FROM (
                SELECT * FROM `{{DBP}}users_session` ORDER BY `lastActive` DESC
            ) `sessions`
            GROUP BY `uid`
        "));
        if($this->S->executeQuery()) {
            $sessions = $this->S->fetchAll();

            $guestCount = 0;
            $onlineUsers = array();
            foreach($sessions as $session) {
                if((strtotime($session['lastActive']) + 180) >= time()) {
                    if($session['uid'] == 0) {
                        $guestCount++;
                    } else {
                        $onlineUsers[] = array(
                            'lastActive' => $session['lastActive'],
                            'userId'     => $session['uid']
                        );
                    }
                }
            }

            return array(
                'members' => $onlineUsers,
                'memberCount' => count($onlineUsers),
                'guestCount' => $guestCount,
                'total' => (count($onlineUsers) + $guestCount)
            );
        }
    }

    public function getLatestPosts() {
        if($this->id == null) {
            $this->lastError[] = 'No user was specified. Please specify a user first.';
            return false;
        }

        if(!$this->S instanceof PSQL) {
            $this->lastError[] = 'No instance of PSQL was found in the User object instance.';
            return false;
        }

        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
            SELECT
                 `P`.`id` `postId`
                ,`T`.`id` `threadId`
            FROM `for1234_posts` `P`
              INNER JOIN `for1234_threads` `T` ON `T`.`id` = `P`.`threadId`
            WHERE `P`.`authorId` = :authorId
            ORDER BY `P`.`postDate` DESC
        "));

        $this->S->executeQuery(array(':authorId' => $this->id));

        $psts = $this->S->fetchAll();

        $threads = array();
=======
        return $this->integration->sessionController($this);
    }
>>>>>>> 615a34eea3757a7329b41b8f2d8bd5f54f42e90f

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
