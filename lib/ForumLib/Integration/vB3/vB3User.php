<?php
    namespace ForumLib\Integration\vB3;

    use ForumLib\Database\PSQL;
    use ForumLib\Forums\Post;
    use ForumLib\Forums\Thread;
    use ForumLib\Integration\IntegrationBaseUser;
    use ForumLib\Users\User;
    use ForumLib\Utilities\Config;

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
            // TODO: Implement getUser() method.
        }

        public function getRegisteredUsers(User $user) {
            // TODO: Implement getRegisteredUsers() method.
        }

        public function setPostCount($id, User $user) {
            // TODO: Implement setPostCount() method.
        }
    }