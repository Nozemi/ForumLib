<?php
    namespace ForumLib\Integration\Nozum;

    use ForumLib\Database\PSQL;
    use ForumLib\Forums\Post;
    use ForumLib\Forums\Thread;
    use ForumLib\Integration\IntegrationBaseUser;
    use ForumLib\Users\User;
    use ForumLib\Utilities\Config;

    class NozumUser extends IntegrationBaseUser {

        public function login($username = 0, User $user) {
            /*
            If $uname is provided in the method as 1, it will use email as username.
            If $uname is provided in the method as 2, both username and email will be checked for a match.
            If $uname isn't provided, it'll treat the username as a username.
            */
            switch($username) {
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
                ':username' => $user->username
            ))) {
                $details = $this->S->fetch();

                if(password_verify($user->password, $details['password'])) {
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

        public function register(User $user) {
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
        }

        public function setPassword($p1, $p2 = null, $login = false, User $user) {
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

        public function updateAccount(User $user) {
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

        public function usernameExists($username, User $user) {
            if(!$username) $username = $user->username;

            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
              SELECT `id`, `username` FROM `{{DBP}}users` WHERE `username` = :username
            "));
            if($this->S->executeQuery(array(
                ':username' => $username
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

        public function sessionController(User $user) {
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
                ':uid'          => ($user->id ? $user->id : 0),
                ':lastActive'   => date('Y-m-d H:i:s'),
                ':ipAddress'    => $user->lastIp,
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

        public function getStatus($id, User $user) {
            $C = new Config;
            if(array_column($C->config, 'integration')[0] == 'vB3') return false;

            if(is_null($id)) $id = $user->id;

            if($id == 0) {
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
                ':uid' => $id
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
                    $this->lastError[] = 'Something went wrong while getting current status for user with ID ' . $id . '.';
                    return false;
                }
            }

            return $status;
        }

        public function getOnlineCount(User $user) {
            $C = new Config;

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

        public function getCurrentPage($id, User $user) {
            // TODO: Implement getCurrentPage() method.
        }

        public function getLatestPosts(User $user) {
            if($user->id == null) {
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

            $this->S->executeQuery(array(':authorId' => $user->id));

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

        public function getUser($id = null, $byId = true, User $user) {
            if(is_null($id)) $id = $user->id;

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
                ':id' => $id
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

        public function getRegisteredUsers(User $user) {
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

        public function setPostCount($id, User $user) {
            if(is_null($id)) $id = $user->id;

            $this->postCount = 0;

            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
              SELECT COUNT(`id`) `count` FROM `{{DBP}}posts` WHERE `authorId` = :userId
            "));

            $this->S->executeQuery(array(':userId' => $id));

            $result = $this->S->fetch();
            $this->postCount = $result['count'];

            return $this;
        }
    }