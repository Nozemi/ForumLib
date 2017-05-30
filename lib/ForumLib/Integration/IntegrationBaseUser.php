<?php
    namespace ForumLib\Integration;

    use ForumLib\Users\User;

    abstract class IntegrationBaseUser extends IntegrationBase  {
        abstract public function login($username = 0, User $user);
        abstract public function register(User $user);
        abstract public function setPassword($p1, $p2 = null, $login = false, User $user);
        abstract public function updateAccount(User $user);
        abstract public function usernameExists($username, User $user);
        abstract public function sessionController(User $user);
        abstract public function getStatus($id, User $user);
        abstract public function getOnlineCount(User $user);
        abstract public function getCurrentPage($id, User $user);
        abstract public function getLatestPosts(User $user);
        abstract public function getUser($id = null, $byId = true, User $user);
        abstract public function getRegisteredUsers(User $user);
        abstract public function setPostCount($id, User $user);
    }