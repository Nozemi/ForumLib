<?php
  namespace ForumLib\Users;

  use ForumLib\Utilities\PSQL;

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
    public $uid;       // Account User ID.
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

    private $password;

    private $S;
    private $lastError = array();
    private $lastMessage = array();

    public function __construct(PSQL $SQL, $_uid = null) {
      // We'll check if the required parameters are filled.
      if(!is_null($SQL)) {
        $this->S = $SQL;

        // Getting IP address for the user.
        $ipadr = (is_null($_SERVER['HTTP_CF_CONNECTING_IP'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['HTTP_CF_CONNECTING_IP'];
        $this->lastlogin = array(
          'date'  => date('Y-m-d H:i:s', time()),
          'ip'    => $ipadr
        );
      } else {
        $this->lastError[] = 'Something went wrong with the user.';
        return false;
      }
    }

    public function login($uname = 0) {
      /*
        If $uname is provided in the method as 1, it will use email as username.
        If $uname is provided in the method as 2, both username and email will be checked for a match.
        If $uname isn't provided, it'll treat the username as a username.
      */
      switch($uname) {
        case 1:
          $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "SELECT * FROM `{{DBP}}users` WHERE `email` = :username"));
          break;
        case 2:
          break;
        default:
          $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "SELECT * FROM `{{DBP}}users` WHERE `username` = :username"));
          break;
      }

      // Check if the query executes alright, and return an error if it doesn't.
      if($this->S->executeQuery(array(
        ':username' => $this->username
      ))) {
        $details = $this->S->fetch();

        if(password_verify($this->password, $details['password'])) {
          $this->lastMessage[] = 'Successfully logged in.';
          $user = $this->getUser();

          return $user;
        } else {
          $this->lastError[] = 'Incorrect username or password.';
          return false;
        }
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong during login.';
        }
        return false;
      }
    }

    public function register() {
      if(is_null($this->password) || is_null($this->username)) {
        $this->lastError[] = 'Username and/or password is missing.';
        return false;
      } else {
        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
          INSERT INTO `{{DPB}}users` (
             `username`
            ,`password`
            ,`regdate`
            ,`regip`
            ,`email`
            ,`firstname`
            ,`lastname`
          ) VALUES (
             :username
            ,:password
            ,:regdate
            ,:regip
            ,:email
            ,:firstname
            ,:lastname
          );
        "));

        // Check if the query executes alright, and return an error if it doesn't.
        if($this->S->executeQuery(array(
          ':username'   => $this->username,
          ':password'   => $this->password,
          ':regdate'    => date('Y-m-d H:i:s', time()),
          ':regip'      => $this->lastlogin['ip'],
          ':email'      => $this->email,
          ':firstname'  => $this->firstname,
          ':lastname'   => $this->lastname
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
    }

    // Set the password if the passwords match.
    public function setPassword($p1, $p2 = null, $login = false) {
      if($p1 == $p2) {
        // If $p1 and $p2 matches (both passwords provided), it'll hash the password, and store it in the object.
        $this->password = password_hash($p1, PASSWORD_BCRYPT);
        return true;
      } else if(is_null($p2) && $login = true) {
        // If password 2 is empty and $login is true, it'll store the clear text password in the object.
        $this->password = $p1;
        return true;
      } else {
        $this->lastError[] = 'Passwords doesn\'t match.';
        return false;
      }
    }

    public function updateAccount() {
      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        UPDATE `{{DBP}}users` SET
           `email`      = :email
          ,`password`   = :password
          ,`firstname`  = :firstname
          ,`lastname`   = :lastname
          ,`avatar`     = :avatar
          ,`group`      = :group
        WHERE `username` = :username;
      "));
      if($this->S->executeQuery(array(
        ':email'      => $this->email,
        ':password'   => $this->password,
        ':firstname'  => $this->firstname,
        ':lastname'   => $this->lastname,
        ':avatar'     => $this->avatar,
        ':group'      => $this->group,
        ':username'   => $this->username
      ))) {
        $this->lastMessage[] = 'Account was successfully updated.';
        return true;
      } else {
        $this->lastError[] = $this->S->getLastError();
        return false;
      }
    }

    public function getUser($_uid = null) {
      if(!is_null($_uid)) $this->uid = $_uid;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        SELECT
           `username`
          ,`avatar`
          ,`group`
          ,`firsname`
          ,`lastname`
          ,`lastlogindate`
          ,`regdate`
          ,`lastip`
          ,`regip`
          ,`email`
        FROM `{{DBP}}users`
        WHERE `uid` = :uid
      "));

      if($this->S->executeQuery(array(
        ':uid' => $this->uid
      ))) {
        $uR = $this->S->fetch();
        $user = new User($this->S);
        $user->setId($uR['uid'])
          ->setAvatar($uR['avatar'])
          ->setGroup($uR['group'])
          ->setFirstname($uR['firstname'])
          ->setLastname($uR['lastname'])
          ->setLastLogin($uR['lastlogindate'])
          ->setRegDate($uR['regdate'])
          ->setLastIP($uR['lastip'])
          ->setRegIP($uR['regip'])
          ->setEmail($uR['email'])
          ->setUsername($uR['username']);

        return $user;
      } else {
        if(defined('DEBUG')) {
          $this->lastError[] = $this->S->getLastError();
        } else {
          $this->lastError[] = 'Something went wrong while getting the user.';
        }
        return false;
      }
    }

    public function setId($_id) {
      $this->id = $_id;
      return $this;
    }

    public function setAvatar($_avatar) {
      $this->avatar = $_avatar;
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
