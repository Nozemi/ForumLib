<?php
  namespace NozLib\Modules;

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
    public $username;  // Account username.
    public $group;     // Array of account group details
    public $email;     // Account email address.
    public $lastlogin; // Array of last login detials.
    public $about;     // Array of information.
    public $firstname; // First name.
    public $lastname;  // Last name.
    public $avatar;    // Avatar URL

    private $password;

    private $S;
    private $lastError;
    private $lastMessage;

    public function __construct(PSQL $SQL) {
      // We'll check if the required parameters are filled.
      if(!is_null($S)) {
        $this->S = $SQL;

        // Getting IP address for the user.
        $ipadr = (is_null($_SERVER['HTTP_CF_CONNECTING_IP'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['HTTP_CF_CONNECTING_IP'];
        $this->lastlogin = array(
          'date'  => date('Y-m-d H:i:s', time()),
          'ip'    => $ipadr
        );
      } else {
        $this->lastError = 'Something went wrong with the user.';
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
          $S->prepareQuery($S->replacePrefix('{{DBP}}', "SELECT * FROM `{{DBP}}users` WHERE `email` = :username"));
          break;
        case 2:
          break;
        default:
          $S->prepareQuery($S->replacePrefix('{{DBP}}', "SELECT * FROM `{{DBP}}users` WHERE `username` = :username"));
          break;
      }

      // Check if the query executes alright, and return an error if it doesn't.
      if($S->executeQuery(array(
        ':username' => $this->username
      ))) {
        $details = $S->fetch();
      } else {
        if(defined('DEBUG')) {
          $this->lastError = $S->getLastError();
          return false;
        } else {
          $this->lastError = 'Something went wrong during login.';
          return false;
        }
      }
    }

    public function register() {
      if(is_null($this->password) || is_null($this->username)) {
        $this->lastError = 'Username and/or password is missing.';
        return false;
      } else {
        $this->S->prepareQuery($S->replacePrefix('{{DBP}}', "
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
          $this->lastMessage = 'Account was successfully registered.';
          return true;
        } else {
          if(defined('DEBUG')) {
            $this->lastError = $S->getLastError();
            return false;
          } else {
            $this->lastError = 'Something went wrong during registration';
            return false;
          }
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
        $this->lastError = 'Passwords doesn\'t match.';
        return false;
      }
    }

    public function updateAccount() {
      $S->prepareQuery($S->replacePrefix('{{DBP}}', "
        UPDATE `{{DBP}}users` SET
           `email`      = :email
          ,`password`   = :password
          ,`firstname`  = :firstname
          ,`lastname`   = :lastname
          ,`avatar`     = :avatar
          ,`group`      = :group
        WHERE `username` = :username
      "));
      if($S->executeQuery(array(
        ':email'      => $this->email,
        ':password'   => $this->password,
        ':firstname'  => $this->firstname,
        ':lastname'   => $this->lastname,
        ':avatar'     => $this->avatar,
        ':group'      => $this->group
      ))) {
        $this->lastMessage = 'Account was successfully updated';
        return true;
      } else {
        $this->lastError = $S->getLastError();
        return false;
      }
    }

    public function getLastMessage() {
      return $this->lastMessage;
    }

    public function getLastError() {
      return $this->lastError;
    }
  }
