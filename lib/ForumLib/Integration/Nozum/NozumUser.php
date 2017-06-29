<?php
    namespace ForumLib\Integration\Nozum;

    use ForumLib\Database\DBUtil;
    use ForumLib\Database\DBUtilQuery;
    use ForumLib\Forums\Post;
    use ForumLib\Forums\Thread;
    use ForumLib\Integration\IntegrationBaseUser;
    use ForumLib\Users\User;
    use ForumLib\Utilities\Config;

    use ForumLib\Utilities\Logger;
    use \PDO;

    class NozumUser extends IntegrationBaseUser {

        public function login($username = 0, User $user) {
            $login = new DBUtilQuery;
            $login->setName('login')
                ->setMultipleRows(false)
                ->setDBUtil($this->S)
                ->addParameter(':username', $user->username, PDO::PARAM_STR);

            /*
            If $uname is provided in the method as 1, it will use email as username.
            If $uname is provided in the method as 2, both username and email will be checked for a match.
            If $uname isn't provided, it'll treat the username as a username.
            */
            switch($username) {
                case 1:
                    $login->setQuery("SELECT * FROM `{{DBP}}users` WHERE `email` = :username");
                    break;
                case 2:
                    break;
                case 0:
                default:
                    $login->setQuery("SELECT * FROM `{{DBP}}users` WHERE `username` = :username");
                    break;
            }

            $details = $login->execute()->result();

            if(password_verify($user->password, $details['password'])) {
                new Logger("{$user->username} logged in.", Logger::DEBUG, __FILE__, __LINE__);
                $user = $this->getUser($details['id'], true, $user);

                $lastLogin = new DBUtilQuery;
                $lastLogin->setName('lastLogin')
                    ->setMultipleRows(false)
                    ->setQuery("UPDATE `{{DBP}}users` SET `lastip` = :lastip,`lastlogindate` = :lastlogindate WHERE `id` = :id")
                    ->addParameter(':lastip', (isset($user->lastLogin['ip']) ? $user->lastLogin['ip'] : '0.0.0.0'), PDO::PARAM_STR)
                    ->addParameter(':lastlogindate', (isset($user->lastLogin['date']) ? $user->lastLogin['date'] : ''), PDO::PARAM_STR)
                    ->addParameter(':id', $user->id, PDO::PARAM_INT)
                    ->setDBUtil($this->S)
                    ->execute();

                return $user;
            } else {
                new Logger("{$user->username} failed to log in.", Logger::DEBUG, __FILE__, __LINE__);
                return false;
            }
        }

        public function register(User $user) {
            if($user->usernameExists($user->username)) {
                $this->lastError[] = 'Username already in use.';
                return false;
            }

            if(!preg_match('/^[^\W_]+$/', $user->username)) {
                $this->lastError[] = 'Sorry, username may only contain alphanumeric characters. (A-Z,a-z,0-9)';
                return false;
            }

            if(is_null($user->password) || is_null($user->username)) {
                $this->lastError[] = 'Username and/or password is missing.';
                return false;
            } else {
                $C = new Config;

                $register = new DBUtilQuery;
                $register->setName('register')
                    ->setQuery("
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
                    ")
                    ->addParameter(':username', $user->username, PDO::PARAM_STR)
                    ->addParameter(':password', $user->password, PDO::PARAM_STR)
                    ->addParameter(':regdate', date('Y-m-d H:i:s'), PDO::PARAM_STR)
                    ->addParameter(':regip', $user->lastLogin['ip'], PDO::PARAM_STR)
                    ->addParameter(':email', $user->email, PDO::PARAM_STR)
                    ->addParameter(':firstname', $user->firstname, PDO::PARAM_STR)
                    ->addParameter(':lastname', $user->lastname, PDO::PARAM_STR)
                    ->addParameter(':group', ($user->group->id ? $user->group->id : $C->getConfigValue('defaultGroup')), PDO::PARAM_INT)
                    ->setDBUtil($this->S)
                    ->execute();
            }

            return true;
        }

        public function setPassword($p1, $p2 = null, $login = false, User $user) {
            if($p1 == $p2) {
                // If $p1 and $p2 matches (both passwords provided), it'll hash the password, and store it in the object.
                $this->password = password_hash($p1, PASSWORD_BCRYPT);
                return $this;
            } else if(is_null($p2) && $login == true) {
                // If password 2 is empty and $login is true, it'll store the clear text password in the object.
                return $p1;
            } else {
                $this->lastError[] = 'Passwords doesn\'t match.';
                return false;
            }
        }

        public function updateAccount(User $user) {
            $updateAccount = new DBUtilQuery;
            $updateAccount->setName('updateAccount')
                ->setQuery("
                    UPDATE `{{DBP}}users` SET
                         `email`      = :email
                        ,`password`   = :password
                        ,`firstname`  = :firstname
                        ,`lastname`   = :lastname
                        ,`avatar`     = :avatar
                        ,`group`      = :group
                    WHERE `username` = :username;
                ")
                ->addParameter(':email', $user->email, PDO::PARAM_STR)
                ->addParameter(':password', $user->password, PDO::PARAM_STR)
                ->addParameter(':firstname', $user->firstname, PDO::PARAM_STR)
                ->addParameter(':lastname', $user->lastname, PDO::PARAM_STR)
                ->addParameter(':avatar', $user->avatar, PDO::PARAM_STR)
                ->addParameter(':group', $user->groupId, PDO::PARAM_INT)
                ->addParameter(':username', $user->username, PDO::PARAM_STR)
                ->setDBUtil($this->S)
                ->execute();

            return true;
        }

        public function usernameExists($username, User $user) {
            if(!$username) $username = $user->username; // TODO: Remove this. Not really necessary.

            $getUsername = new DBUtilQuery;
            $getUsername->setName('getUsername')
                ->setQuery("SELECT `id`, `username` FROM `{{DBP}}users` WHERE `username` = :username")
                ->addParameter(':username', $username, PDO::PARAM_STR)
                ->setDBUtil($this->S)
                ->execute();

            $usr = $this->S->getResultByName($getUsername->getName());

            if(!empty($usr)) {
                return true;
            } else {
                return false;
            }
        }

        public function sessionController(User $user) {
            $sessionController = new DBUtilQuery;
            $sessionController->setName('sessionController')
                ->setMultipleRows(false)
                ->setQuery("
                    INSERT INTO `{{DBP}}users_session` SET
                         `uid`        = :uid
                        ,`lastActive` = :lastActive
                        ,`ipAddress`  = :ipAddress
                        ,`created`    = :created
                        ,`lastPage`   = :lastPage
                        ,`phpSessId`  = :phpSessId
                        ,`userAgent`  = :userAgent
                    ON DUPLICATE KEY UPDATE
                         `uid`        = :uid
                        ,`lastActive` = :lastActive
                        ,`ipAddress`  = :ipAddress
                        ,`lastPage`   = :lastPage
                        ,`phpSessId`  = :phpSessId
                        ,`userAgent`  = :userAgent;
                ")
                ->setParameters(array(
                    array(':uid', ($user->id ? $user->id : 0), PDO::PARAM_INT),
                    array(':lastActive', date('Y-m-d H:i:s'), PDO::PARAM_STR),
                    array(':ipAddress', $user->lastIp, PDO::PARAM_STR),
                    array(':created', date('Y-m-d H:i:s')),
                    array(':lastPage', 'N/A', PDO::PARAM_STR),
                    array(':phpSessId', session_id(), PDO::PARAM_STR),
                    array(':userAgent', $_SERVER['HTTP_USER_AGENT'], PDO::PARAM_STR)
                ))
                ->setDBUtil($this->S)
                ->execute();
        }

        public function getStatus($id, User $user) {
            if(is_null($id)) $id = $user->id;

            if($id == 0) {
                $this->lastError[] = 'No valid user to get status from.';
                return false;
            }

            $status = 0;

            $getStatus = new DBUtilQuery;
            $getStatus->setName('getStatus')
                ->setMultipleRows(false)
                ->setQuery("
                    SELECT
                      *
                    FROM `{{DBP}}users_session`
                    WHERE `uid` = :uid
                    ORDER BY `lastActive` DESC
                    LIMIT 1
                ")
                ->addParameter(':uid', $id, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();

            $result = $getStatus->result();

            if((strtotime($result['lastActive']) + 180) >= time()) {
                $status = 1;
            }

            return $status;
        }

        public function getOnlineCount(User $user) {
            $onlineCount = new DBUtilQuery;
            $onlineCount->setName('onlineCount')
                ->setQuery("SELECT * FROM (SELECT * FROM `{{PREFIX}}users_session` ORDER BY `lastActive` DESC) `sessions` GROUP BY `uid`")
                ->setDBUtil($this->S)
                ->execute();

            $sessions = $onlineCount->result();

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

        public function getCurrentPage($id, User $user) {
            // TODO: Implement getCurrentPage() method.
        }

        public function getLatestPosts(User $user) {
            if($user->id == null) {
                $this->lastError[] = 'No user was specified. Please specify a user first.';
                return false;
            }

            if(!$this->S instanceof DBUtil) {
                $this->lastError[] = 'No instance of PSQL was found in the User object instance.';
                return false;
            }

            $getLatestPosts = new DBUtilQuery;
            $getLatestPosts->setName('getLatestPosts')
                ->setQuery("
                    SELECT
                         `P`.`id` `postId`
                        ,`T`.`id` `threadId`
                    FROM `for1234_posts` `P`
                    INNER JOIN `for1234_threads` `T` ON `T`.`id` = `P`.`threadId`
                    WHERE `P`.`authorId` = :authorId
                    ORDER BY `P`.`postDate` DESC
                ")
                ->addParameter(':authorId', $user->id, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();

            $tmpPosts = $getLatestPosts->result();

            $threads = array();

            foreach($tmpPosts as $pst) {
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

        public function getUser($id = null, $byId = true, User $user) {
            if(is_null($id)) $id = $user->id;

            $getUser = new DBUtilQuery;
            $getUser->setName('getUser')
                ->setMultipleRows(false)
                ->setQuery("
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
                ")
                ->setDBUtil($this->S);

            if($byId) {
                $getUser->addParameter(':id', $id, PDO::PARAM_INT);
            } else {
                $getUser->addParameter(':id', $id, PDO::PARAM_STR);
            }

            $getUser->execute();

            $uR = $getUser->result();

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
        }

        public function getRegisteredUsers(User $user) {
            $registeredUsers = new DBUtilQuery;
            $registeredUsers->setName('registeredUsers')
                ->setQuery("
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
                ")
                ->setDBUtil($this->S)
                ->execute();

            return $registeredUsers->result();
        }

        public function setPostCount($id, User $user) {
            if(is_null($id)) $id = $user->id;

            $this->postCount = 0;

            $postCount = new DBUtilQuery;
            $postCount->setName('postCount')
                ->setMultipleRows(false)
                ->setQuery("SELECT COUNT(`id`) `count` FROM `{{DBP}}posts` WHERE `authorId` = :userId")
                ->addParameter(':userId', $id, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();

            $result = $postCount->result();
            return $result['count'];
        }
    }