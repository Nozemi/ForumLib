<?php
  namespace ForumLib\Users;

  use ForumLib\Database\PSQL;

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

    private $password;

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
          $user = $this->getUser($details['id']);

          $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
            UPDATE `{{DBP}}users` SET `lastip` = :lastip,`lastlogindate` = :lastlogindate WHERE `id` = :id
          "));
          $this->S->executeQuery(array(
              ':lastip' => $this->lastLogin['ip'],
              ':lastlogindate' => $this->lastLogin['date'],
              ':id' => $user->id
          ));

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
      if($this->usernameExists($this->username)) {
          $this->lastError[] = 'Username already in use.';
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
    }

    // Set the password if the passwords match.
    public function setPassword($p1, $p2 = null, $login = false) {
      if($p1 == $p2) {
        // If $p1 and $p2 matches (both passwords provided), it'll hash the password, and store it in the object.
        $this->password = password_hash($p1, PASSWORD_BCRYPT);
        return $this;
      } else if(is_null($p2) && $login == true) {
        // If password 2 is empty and $login is true, it'll store the clear text password in the object.
        $this->password = $p1;
        return $this;
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

    public function getUser($_id = null, $byId = true) {
      if(is_null($_id)) $_id = $this->id;

      $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
        SELECT
           `id`
          ,`username`
          ,`avatar`
          ,`group`
          ,`firstname`
          ,`lastname`
          ,`lastlogindate`
          ,`regdate`
          ,`lastip`
          ,`regip`
          ,`email`
          ,`about`
          ,`location`
        FROM `{{DBP}}users`
        WHERE `" . ($byId ? 'id' : 'username') . "` = :id
      "));

      if($this->S->executeQuery(array(
        ':id' => $_id
      ))) {
        $uR = $this->S->fetch();
        $user = new User($this->S);
        $user->setId($uR['id'])
          ->setAvatar($uR['avatar'])
          ->setGroup($uR['group'])
          ->setGroupId(($user->group ? $user->group->id : 0))
          ->setFirstname($uR['firstname'])
          ->setLastname($uR['lastname'])
          ->setLastLogin($uR['lastlogindate'])
          ->setRegDate($uR['regdate'])
          ->setLastIP($uR['lastip'])
          ->setRegIP($uR['regip'])
          ->setEmail($uR['email'])
          ->setUsername($uR['username'])
          ->setAbout($uR['about'])
          ->setLocation($uR['location'])
          ->setPostCount($uR['id'])
          ->unsetSQL();

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

    public function usernameExists($_username = null) {
        if(!$_username) {
            $_username = $this->username;
        }

        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
            SELECT `id`, `username` FROM `{{DBP}}users` WHERE `username` = :username
        "));
        if($this->S->executeQuery(array(
            ':username' => $_username
        ))) {
            $usr = $this->S->fetch();
            if(!empty($usr)) {
                return true;
            } else {
                return false;
            }
        } else {
            if(defined('DEBUG')) {
                $this->lastError[] = $this->S->getLastError();
                return false;
            } else {
                $this->lastError[] = 'Something went wrong while checking username.';
                return false;
            }
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

        foreach($psts as $pst) {
            $P = new Post($this->S);
            $T = new Thread($this->S);

            $thread = $T->getThread($pst['threadId']);
            $post = $P->getPost($pst['postId']);

            $threads[] = array(
              'thread' => $thread,
                'post' => $post
            );
        }

        return $threads;
    }

    public function getRegisteredUsers() {
        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
            SELECT
               `U`.`id`
              ,`U`.`username`
              ,`U`.`regdate`
              ,`U`.`lastlogindate`
              ,`U`.`group`
              ,`G`.`title`
            FROM `{{DBP}}users` `U`
              INNER JOIN `{{DBP}}groups` `G` ON `G`.`id` = `U`.`group`
            ORDER BY `username` ASC
        "));
        $this->S->executeQuery();
        return $this->S->fetchAll();
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
        if(is_null($_id)) $_id = $this->id;

        $this->postCount = 0;

        $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
            SELECT COUNT(`id`) `count` FROM `{{DBP}}posts` WHERE `authorId` = :userId
        "));
        $this->S->executeQuery(array(':userId' => $_id));

        $result = $this->S->fetch();
        $this->postCount = $result['count'];

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
