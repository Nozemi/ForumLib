<?php
    namespace SBLib\Integration\vB3;

    use SBLib\Database\DBUtilQuery;
    use SBLib\Integration\IntegrationBaseUser;
    use SBLib\Users\User;

    use \PDO;

    class vB3User extends IntegrationBaseUser {

        public function login($username = 0, User $user) {
            // TODO: Implement login() method.
        }

        public function register(User $user) {
            // TODO: Implement register() method.
        }

        public function setPassword($p1, $p2 = null, $login = false, User $user) {
            // TODO: Implement setPassword() method.
        }

        public function updateAccount(User $user) {
            // TODO: Implement updateAccount() method.
        }

        public function usernameExists($username, User $user) {
            // TODO: Implement usernameExists() method.
        }

        public function sessionController(User $user) {
            // TODO: Implement sessionController() method.
        }

        public function getStatus($id, User $user) {
            // TODO: Implement getStatus() method.
        }

        public function getOnlineCount(User $user) {
            // TODO: Implement getOnlineCount() method.
        }

        public function getCurrentPage($id, User $user) {
            // TODO: Implement getCurrentPage() method.
        }

        public function getLatestPosts(User $user) {
            // TODO: Implement getLatestPosts() method.
        }

        public function getUser($id = null, $byId = true, User $user) {
            if(is_null($id)) $id = $user->id;

            $getUser = new DBUtilQuery;
            $getUser->setName('getUser')
                ->setMultipleRows(false)
                ->setDBUtil($this->S)
                ->setQuery("
                    SELECT
                         `username`
                        ,`userid`
                        ,`avatarrevision`
                        ,`usergroupid`
                        ,`lastvisit`
                        ,`joindate`
                        ,`ipaddress`
                        ,`email`
                        ,`ipaddress`
                    FROM `{{DBP}}user`
                    WHERE `" . ($byId ? 'userid' : 'username') . "` = :id
                ");

            if($byId) {
                $getUser->addParameter(':id', $id, PDO::PARAM_INT);
            } else {
                $getUser->addParameter(':id', $id, PDO::PARAM_STR);
            }

            $uR = $getUser->result();
            $user = new User($this->S);
            $user->setId($uR['userid'])
                ->setAvatar('/customavatars/avatar' . $uR['userid'] . '_' . $uR['avatarrevision'] . '.gif')
                ->setGroupId(($user->group ? $user->group->id : 0))
                ->setLastLogin($uR['lastvisit'])
                ->setRegDate($uR['joindate'])
                ->setLastIP($uR['ipaddress'])
                ->setEmail($uR['email'])
                ->setUsername($uR['username'])
                ->unsetSQL();

            return $user;
        }

        public function getRegisteredUsers(User $user) {
            // TODO: Implement getRegisteredUsers() method.
        }

        public function setPostCount($id, User $user) {
            // TODO: Implement setPostCount() method.
        }
    }